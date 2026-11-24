/* Show the PakFlow login email in Google's account menu and hide the credit
 * reset subtitle ("Resets on ..."). Display-only: does not switch the Google
 * session or change the extension's login data. */
(() => {
  'use strict';
  const emailPattern = /[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/g;
  const validEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  const resetLinePattern = /^(?:credits?\s+)?resets?\b(?:\s+(?:on|at|in|every|monthly|daily|weekly|tomorrow|today|next)\b|\s*:|\s+\d|\s+[a-z]{3,9}\s+\d)/i;
  const texts = new Map(), attributes = new Map(), names = new Map();
  let loginEmail = '', queued = false, styleInjected = false;

  function normalizeText(value) {
    return String(value || '').replace(/[\u200B-\u200D\uFEFF]/g, '').replace(/\s+/g, ' ').trim();
  }

  function isResetLineText(value) {
    const norm = normalizeText(value);
    return Boolean(norm && norm.length <= 140 && resetLinePattern.test(norm));
  }

  function ensureHideResetStyle() {
    if (styleInjected && document.getElementById('pakflow-hide-reset-style')) return;
    const root = document.head || document.documentElement;
    if (!root) return;
    const style = document.createElement('style');
    style.id = 'pakflow-hide-reset-style';
    style.textContent = '[data-pakflow-hide-reset="true"]{display:none!important;visibility:hidden!important;height:0!important;min-height:0!important;max-height:0!important;margin:0!important;padding:0!important;overflow:hidden!important;opacity:0!important;pointer-events:none!important;}[data-pakflow-unclickable-dots="true"],[data-pakflow-unclickable-dots="true"] *{pointer-events:none!important;cursor:default!important;}';
    root.appendChild(style);
    styleInjected = true;
  }

  let dotsBlockerAttached = false;
  function attachDotsBlocker() {
    if (dotsBlockerAttached) return;
    dotsBlockerAttached = true;
    const blockEvent = (e) => {
      const target = e.target && e.target.nodeType === 1 ? e.target : e.target?.parentElement;
      if (target && target.closest && target.closest('[data-pakflow-unclickable-dots="true"]')) {
        if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        e.stopPropagation();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
      }
    };
    for (const evt of ['click', 'auxclick', 'contextmenu', 'mousedown', 'mouseup', 'pointerdown', 'pointerup', 'touchstart', 'touchend', 'keydown']) {
      window.addEventListener(evt, blockEvent, true);
      document.addEventListener(evt, blockEvent, true);
    }
  }

  function markUnclickableDots(el) {
    if (!el || el.nodeType !== 1) return;
    if (el.getAttribute('data-pakflow-unclickable-dots') !== 'true') {
      el.setAttribute('data-pakflow-unclickable-dots', 'true');
      el.setAttribute('aria-disabled', 'true');
      el.setAttribute('tabindex', '-1');
    }
    if (el.style.getPropertyValue('pointer-events') !== 'none') {
      el.style.setProperty('pointer-events', 'none', 'important');
    }
    if (el.style.getPropertyValue('cursor') !== 'default') {
      el.style.setProperty('cursor', 'default', 'important');
    }
  }

  function disableUltraThreeDots() {
    if (/^ogs\.google\.com$/i.test(location.hostname)) return;
    if (!document.body) return;
    ensureHideResetStyle();
    attachDotsBlocker();

    const ultraEls = [];
    for (const el of document.querySelectorAll('span,div,p,a,button')) {
      if (el.closest('#pakflow-my-projects-root')) continue;
      if (normalizeText(el.textContent).toUpperCase() !== 'ULTRA') continue;
      const r = el.getBoundingClientRect();
      if (r.width > 0 && r.width < 180 && r.height > 0 && r.height < 70 && r.top >= 0 && r.top < 150) {
        ultraEls.push({ el, rect: r });
      }
    }
    if (!ultraEls.length) return;

    const btns = document.querySelectorAll('button,[role="button"],a');
    for (const btn of btns) {
      if (btn.closest('#pakflow-my-projects-root')) continue;
      if (btn.querySelector('img')) continue;
      const label = (btn.getAttribute('aria-label') || btn.getAttribute('title') || btn.getAttribute('data-tooltip') || '').trim();
      if (/google account|manage your google/i.test(label)) continue;
      if (/model|aspect|ratio|veo|imagen|create|project|credit|sign out/i.test(label)) continue;

      const bRect = btn.getBoundingClientRect();
      if (bRect.width <= 0 || bRect.height <= 0 || bRect.width > 72 || bRect.height > 72 || bRect.top > 150) continue;

      const btnText = normalizeText(btn.textContent);
      if (/^(1|2|3|4|5|6|7|8|9|0|\+|ultra|pro|flow)$/i.test(btnText)) continue;

      let nearUltra = false;
      for (const u of ultraEls) {
        if (btn === u.el || btn.contains(u.el) || u.el.contains(btn)) continue;
        if (Math.abs(bRect.top - u.rect.top) > 55) continue;
        const dxLeft = u.rect.left - bRect.right;
        const dxRight = bRect.left - u.rect.right;
        const dx = Math.max(dxLeft, dxRight, 0);
        if (dx <= 200) {
          nearUltra = true;
          break;
        }
      }
      if (!nearUltra) continue;

      const isSettingsIcon =
        /^(settings?|tune|preferences)$/i.test(btnText) ||
        /\b(settings?|preferences?)\b/i.test(label);
      const svgCircles = btn.querySelectorAll('svg circle').length === 3;
      const isMoreIcon =
        !isSettingsIcon && (
          /^(more_vert|more_horiz|⋮|⋯|\.\.\.)$/i.test(btnText) ||
          /\b(more(\s+(options|actions|menu))?|three\s*dots)\b/i.test(label) ||
          svgCircles ||
          btn.getAttribute('aria-haspopup') === 'menu'
        );
      if (isMoreIcon) {
        markUnclickableDots(btn);
      }
    }
  }

  function readLogin() {
    try {
      if (!chrome?.runtime?.id || !chrome?.storage?.local) return;
      chrome.storage.local.get(['userEmail', 'isLoggedIn'], state => {
        void chrome.runtime?.lastError;
        if (!state) return;
        const email = String(state.userEmail || '').trim();
        loginEmail = (state.isLoggedIn === true || state.isLoggedIn === 'true') && validEmail.test(email) ? email : '';
        schedule();
      });
    } catch {}
  }

  function inAccountMenu(element) {
    if (!element || element.nodeType !== 1) return false;
    if (element.closest('#pakflow-my-projects-root,input,textarea,[contenteditable="true"],script,style,noscript')) return false;
    // Google can render the account menu in a dedicated account iframe.
    if (/^ogs\.google\.com$/i.test(location.hostname)) return true;
    for (let node = element, depth = 0; node && depth < 10; node = node.parentElement || node.getRootNode?.()?.host, depth++) {
      if (node.tagName === 'BODY' || node.tagName === 'HTML') break;
      if (node.querySelector && node.querySelector('a[href*="flow_ai_credits_page"],a.upsell-link,[data-fake-total-credits],a[href*="myaccount.google.com"],a[href*="accounts.google.com/Logout"]')) return true;
      const text = node.textContent || '';
      if (text.length < 3000 && /Google Flow credits|Google Ultra Plan|manage your google account|manage subscription|create avatar|visible watermarking|sign out|tài khoản google|đăng xuất/i.test(text)) return true;
      if (node.matches && node.matches('[role="dialog"],[role="menu"],.cdk-overlay-pane') && (/\b(Google|credits|ULTRA)\b/i.test(text) || emailPattern.test(text))) {
        emailPattern.lastIndex = 0;
        return true;
      }
    }
    return false;
  }

  function canHideWholeElement(el) {
    if (!el || el.nodeType !== 1) return false;
    if (el.matches('html,body,head,script,style,a,button,input,textarea,select,img,svg')) return false;
    if (el.hasAttribute('data-fake-total-credits') || el.hasAttribute('data-fake-credits')) return false;
    if (el.querySelector('a,button,input,textarea,select,img,svg,[role="button"],[role="switch"],[role="menuitem"],[data-fake-total-credits],[data-fake-credits]')) return false;
    return true;
  }

  function markHiddenResetElement(el) {
    const baseText = normalizeText(el.textContent);
    let target = el;
    while (target.parentElement && canHideWholeElement(target.parentElement) && normalizeText(target.parentElement.textContent) === baseText) {
      target = target.parentElement;
    }
    if (target.getAttribute('data-pakflow-hide-reset') !== 'true') {
      target.setAttribute('data-pakflow-hide-reset', 'true');
      target.style.setProperty('display', 'none', 'important');
    }
  }

  function scanRootForResetLines(root) {
    if (!root || !root.querySelectorAll) return;
    const elements = root.querySelectorAll('*');
    for (const el of elements) {
      if (el.getAttribute('data-pakflow-hide-reset') === 'true') continue;
      if (!isResetLineText(el.textContent)) continue;
      if (!canHideWholeElement(el)) continue;
      if (!inAccountMenu(el)) continue;
      markHiddenResetElement(el);
    }
    const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
    let tn;
    while ((tn = walker.nextNode())) {
      const parent = tn.parentElement;
      if (!parent || parent.closest('[data-pakflow-hide-reset="true"],script,style,noscript,input,textarea,[contenteditable="true"]')) continue;
      if (!isResetLineText(tn.nodeValue)) continue;
      if (!inAccountMenu(parent)) continue;
      if (canHideWholeElement(parent) && normalizeText(parent.textContent) === normalizeText(tn.nodeValue)) {
        markHiddenResetElement(parent);
      } else {
        const prev = tn.previousSibling;
        if (prev && prev.nodeType === 1 && prev.tagName === 'BR') {
          prev.setAttribute('data-pakflow-hide-reset', 'true');
          prev.style.setProperty('display', 'none', 'important');
        }
        tn.nodeValue = '';
      }
    }
  }

  function hideCreditResetLines() {
    ensureHideResetStyle();
    if (!document.body) return;
    if (/^ogs\.google\.com$/i.test(location.hostname)) {
      scanRootForResetLines(document.body);
      return;
    }
    const roots = new Set();
    for (const container of document.querySelectorAll('[role="dialog"],[role="menu"],.cdk-overlay-pane')) {
      roots.add(container);
    }
    for (const anchor of document.querySelectorAll('a[href*="flow_ai_credits_page"],a.upsell-link,[data-fake-total-credits]')) {
      let box = anchor;
      for (let i = 0; i < 4 && box.parentElement && box.parentElement !== document.body; i++) {
        box = box.parentElement;
      }
      roots.add(box);
    }
    if (roots.size > 0) {
      for (const root of roots) scanRootForResetLines(root);
    } else if (/resets?\b/i.test(document.body.textContent || '')) {
      scanRootForResetLines(document.body);
    }
  }

  function updateText(node) {
    const current = node.nodeValue || '', record = texts.get(node);
    const original = record && current === record.last ? record.original : current;
    const account = loginEmail && inAccountMenu(node.parentElement);
    if (account) updateName(node.parentElement);
    const next = account ? original.replace(emailPattern, () => loginEmail) : original;
    if (next !== current) node.nodeValue = next;
    if (next !== original) texts.set(node, {original, last: next}); else texts.delete(node);
  }

  function updateName(emailElement) {
    const candidates = [emailElement.previousElementSibling, emailElement.parentElement?.previousElementSibling];
    for (const candidate of candidates) {
      if (!candidate || candidate.matches('button,a,h1,h2,img,svg') || candidate.querySelector('button,a,img,svg,input') || candidate.children.length > 2) continue;
      const text = candidate.textContent.trim();
      if (!text || text.length > 120 || text.includes('@') || /^(google|sign out|manage subscription)$/i.test(text)) continue;
      const old = names.get(candidate);
      const original = old && candidate.textContent === old.last ? old.original : candidate.textContent;
      if (candidate.textContent !== 'PakFlow') candidate.textContent = 'PakFlow';
      names.set(candidate, {original, last: 'PakFlow'}); break;
    }
  }

  function render() {
    queued = false;
    hideCreditResetLines();
    disableUltraThreeDots();
    for (const [element, record] of names) {
      if (!element.isConnected) names.delete(element);
      else if (!loginEmail) { if (element.textContent === record.last) element.textContent = record.original; names.delete(element); }
    }
    for (const [node] of texts) {
      if (!node.isConnected) texts.delete(node); else updateText(node);
    }
    if (loginEmail && document.body) {
      const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
      let node;
      while ((node = walker.nextNode())) {
        const parent = node.parentElement;
        if (!parent || parent.closest('script,style,noscript,input,textarea,[contenteditable="true"]')) continue;
        if ((node.nodeValue || '').includes('@') && inAccountMenu(parent)) updateText(node);
      }
    }
    // Multi-language safe: replace any email inside aria-label / title / data-email on account/avatar controls (works on Vietnam "Tài khoản Google" too)
    const candidates = new Set([
      ...document.querySelectorAll('[aria-label*="@"],[title*="@"],[data-email],[aria-label*="Google Account" i],[title*="Google Account" i]'),
      ...attributes.keys()
    ]);
    for (const element of candidates) {
      if (!element.isConnected) { attributes.delete(element); continue; }
      if (element.closest && element.closest('#pakflow-my-projects-root,input,textarea')) continue;
      const stored = attributes.get(element) || {};
      for (const name of ['aria-label', 'title', 'data-email']) {
        const current = element.getAttribute(name);
        if (current === null) { delete stored[name]; continue; }
        const record = stored[name], original = record && current === record.last ? record.original : current;
        emailPattern.lastIndex = 0;
        const hasEmailInAttr = emailPattern.test(original);
        emailPattern.lastIndex = 0;
        const accountLabel = hasEmailInAttr || /Google Account|Tài khoản Google/i.test(original) || inAccountMenu(element);
        let next = loginEmail && accountLabel ? original.replace(emailPattern, () => loginEmail) : original;
        if (loginEmail && /(?:Google Account|Tài khoản Google):\s*/i.test(next)) {
          next = next.replace(/((?:Google Account|Tài khoản Google):\s*)[^(@]+(?=\s*\()/i, '$1PakFlow ');
        }
        if (next !== current) element.setAttribute(name, next);
        if (next !== original) stored[name] = {original, last: next}; else delete stored[name];
      }
      if (Object.keys(stored).length) attributes.set(element, stored); else attributes.delete(element);
    }
  }

  function schedule() {
    if (!queued) {
      queued = true;
      requestAnimationFrame(render);
    }
  }

  chrome.storage.onChanged.addListener((changes, area) => {
    if (area === 'local' && (changes.userEmail || changes.isLoggedIn)) readLogin();
  });

  new MutationObserver(mutations => {
    for (const m of mutations) {
      if (m.type === 'characterData') {
        if (isResetLineText(m.target?.nodeValue)) { hideCreditResetLines(); break; }
      } else if (m.type === 'childList' && m.addedNodes.length) {
        let relevant = false;
        for (const n of m.addedNodes) {
          const txt = n.textContent || '';
          if (/resets?\b/i.test(txt) || (n.nodeType === 1 && (n.matches?.('a[href*="flow_ai_credits_page"],[role="dialog"],[role="menu"]') || n.querySelector?.('a[href*="flow_ai_credits_page"]')))) {
            relevant = true;
            break;
          }
        }
        if (relevant) { hideCreditResetLines(); break; }
      }
    }
    schedule();
  }).observe(document, {
    subtree: true,
    childList: true,
    characterData: true,
    attributes: true,
    attributeFilter: ['aria-label', 'title', 'data-email']
  });

  ensureHideResetStyle();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { hideCreditResetLines(); disableUltraThreeDots(); }, {once: true});
  } else {
    hideCreditResetLines();
    disableUltraThreeDots();
  }
  setInterval(disableUltraThreeDots, 500);
  readLogin();
})();
