/* PakFlow: project presentation and selected-server refresh. */
(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  let root, drawer, backdrop, savedCount, previousFocus, scheduled = false;
  let currentSlot = 'slot_1', currentOwnerEmail = '', currentAccountHash = '', isUserLoggedIn = true, refreshProjects;
  const normalizeSlot = slot => typeof slot === 'string' && slot.trim() && slot !== 'active' ? slot.trim() : 'slot_1';
  function updateServer(state) {
    void chrome.runtime?.lastError;
    if (!state) return;
    let changed = false;
    const rawSlot = (typeof state.lastInjectedSlot === 'string' && state.lastInjectedSlot.trim() && state.lastInjectedSlot !== 'active')
      ? state.lastInjectedSlot
      : state.activeSlot;
    const slot = typeof rawSlot === 'string' && rawSlot.trim() && rawSlot !== 'active'
      ? normalizeSlot(rawSlot)
      : 'slot_' + ((Number.isInteger(state.sessionLoopIndex) && state.sessionLoopIndex >= 0 ? state.sessionLoopIndex : 0) + 1);
    if (slot !== currentSlot) { currentSlot = slot; changed = true; }
    const nextEmail = String(state.userEmail || state.user?.email || state.pakflow_user?.email || state.pf_projects_owner_email || '').trim().toLowerCase();
    if (nextEmail !== currentOwnerEmail) { currentOwnerEmail = nextEmail; changed = true; }
    const nextAccHash = String(state.pf_active_account_hash || '').trim();
    if (nextAccHash !== currentAccountHash) { currentAccountHash = nextAccHash; changed = true; }
    const nextLogged = !(state.isLoggedIn === false || state.isLoggedIn === 'false' || state.flowSessionDetached === true);
    if (nextLogged !== isUserLoggedIn) { isUserLoggedIn = nextLogged; changed = true; }
    if (changed) { refreshProjects?.(); schedule(); }
  }
  const serverStorageKeys = ['activeSlot', 'lastInjectedSlot', 'pf_active_account_hash', 'sessionLoopIndex', 'userEmail', 'user', 'pakflow_user', 'pf_projects_owner_email', 'isLoggedIn', 'flowSessionDetached'];
  try {
    if (chrome?.runtime?.id && chrome?.storage?.local) {
      chrome.storage.local.get(serverStorageKeys, updateServer);
      chrome.storage.onChanged.addListener((changes, area) => {
        try {
          if (!chrome?.runtime?.id) return;
          if (area === 'local' && serverStorageKeys.some((k) => Boolean(changes[k]))) {
            chrome.storage.local.get(serverStorageKeys, updateServer);
          }
        } catch {}
      });
    }
  } catch {}
  const setText = (node, value) => { if (node.textContent !== value) node.textContent = value; };
  globalThis.PakFlowUI = {
    filterProjects(projects) {
      if (!isUserLoggedIn || !Array.isArray(projects) || projects.length === 0) return [];
      const slot = normalizeSlot(currentSlot || 'slot_1');
      // Identical deterministic 3-key filter as PakFlowProjects.list() so badge count ALWAYS equals drawer count
      // (never shows a count on refresh that disappears when opened!).
      const seen = new Set();
      const activeOnly = [];
      for (const p of projects) {
        if (!p || !p.id) continue;
        const pOwner = String(p.ownerEmail || '').trim().toLowerCase();
        if (currentOwnerEmail && pOwner && pOwner !== currentOwnerEmail) continue;
        if (currentAccountHash && p.accountHash) {
          if (p.accountHash !== currentAccountHash) continue;
        } else if (normalizeSlot(p.slot) !== slot) {
          continue;
        }
        const id = String(p.id).toLowerCase();
        if (seen.has(id)) continue;
        seen.add(id);
        activeOnly.push(p);
      }
      if (savedCount) {
        setText(savedCount, 'Session ' + slot.replace('slot_', '') + ' · ' + activeOnly.length + ' Saved');
      }
      const badgeEl = $('pakflow-fp-count');
      if (badgeEl) setText(badgeEl, String(activeOnly.length));
      return activeOnly;
    },
    setProjectRefresh(callback) { refreshProjects = callback; queueMicrotask(callback); },
    upgradeCard(card, project) {
      card.classList.add('pakflow-project-card');
      const info = card.children[0], actions = card.children[1];
      info.className = 'pakflow-project-info';
      const title = info.querySelector('span');
      const rawTitle = String(project.title || '').trim();
      const isGeneric = !rawTitle || /^google\s*flow\s*[-–—]\s*ai\s*creative\s*studio/i.test(rawTitle) || /^(?:flow|google\s*flow|new\s*project)$/i.test(rawTitle);
      const displayTitle = isGeneric
        ? (project.prompt ? String(project.prompt).slice(0, 56) : ('Project ' + String(project.id || '').slice(0, 6).toUpperCase()))
        : rawTitle;
      title.textContent = displayTitle;
      title.title = displayTitle;
      title.className = 'pakflow-project-title';
      const date = document.createElement('div');
      date.className = 'pakflow-project-date';
      const rawDate = project.updatedAt || project.createdAt;
      const parsedDate = new Date(rawDate);
      const srvNum = normalizeSlot(project.slot).replace('slot_', '');
      const dateStr = rawDate && Number.isFinite(parsedDate.getTime()) ? parsedDate.toLocaleString() : 'Saved project';
      date.textContent = '▦ Session ' + srvNum + ' · ' + dateStr;
      info.children[1]?.remove();
      info.append(date);
      const preview = document.createElement('div');
      preview.className = 'pakflow-project-preview';
      preview.style.cursor = 'pointer';
      const play = document.createElement('span'); play.className = 'pakflow-preview-play'; play.textContent = '▶';
      const caption = document.createElement('span'); caption.textContent = 'Flow Video Project';
      preview.append(play, caption);
      const thumbnail = project.thumbnail || project.thumbnailUrl || project.imageUrl;
      if (typeof thumbnail === 'string' && /^(?:https:\/\/|data:image\/)/i.test(thumbnail)) {
        const img = document.createElement('img'); img.alt = displayTitle;
        img.loading = 'lazy';
        // Never strip Referer on same-origin flow.google.com/asb/... thumbnails!
        if (/^https:\/\//i.test(thumbnail) && !/^https:\/\/flow\.google\.com\//i.test(thumbnail)) {
          img.referrerPolicy = 'no-referrer';
        }
        img.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:1;';
        img.addEventListener('error', () => { img.remove(); caption.style.display = ''; }, { once: true });
        preview.style.position = 'relative';
        preview.style.overflow = 'hidden';
        play.style.position = 'relative';
        play.style.zIndex = '2';
        caption.style.display = 'none';
        preview.prepend(img);
        img.src = thumbnail;
      }
      const [open, rename, remove] = actions.children;
      actions.className = 'pakflow-project-actions';
      const targetSlot = normalizeSlot(project.slot);
      const targetNum = parseInt(targetSlot.replace(/\D+/g, ''), 10) || 1;
      const targetUrl = project.url || ('https://flow.google.com/project/' + project.id);
      const rememberOpenedProject = () => {
        try { sessionStorage.setItem('pf_last_opened_project_id', String(project.id || '').toLowerCase()); } catch {}
      };
      preview.addEventListener('click', () => { rememberOpenedProject(); open?.click(); });
      info.addEventListener('click', rememberOpenedProject, true);
      open.addEventListener('click', rememberOpenedProject, true);
      const isDifferentSlot = targetSlot !== normalizeSlot(currentSlot);
      open.className = 'pakflow-project-open';
      open.textContent = isDifferentSlot ? ('▶ Switch S' + targetNum + ' & Open') : '▶ Open This Project';
      open.title = isDifferentSlot
        ? ('Created on Session ' + targetNum + ' — click to switch to Session ' + targetNum + ' and open')
        : 'Open project in Google Flow';
      if (isDifferentSlot && !open.dataset.pfSwitchBound) {
        open.dataset.pfSwitchBound = '1';
        open.addEventListener('click', (ev) => {
          const activeNow = normalizeSlot(currentSlot);
          if (targetSlot === activeNow) return;
          ev.preventDefault();
          ev.stopImmediatePropagation();
          rememberOpenedProject();
          const prevLabel = open.textContent;
          open.disabled = true;
          open.textContent = 'Switching to S' + targetNum + '...';
          try {
            chrome.runtime.sendMessage(
              { type: 'PAKFLOW_REINJECT_CURRENT', slotNumber: targetNum, openFlow: false, targetUrl },
              (res) => {
                void chrome.runtime?.lastError;
                open.disabled = false;
                open.textContent = prevLabel;
                if (!res || !res.navigated) {
                  window.location.href = targetUrl;
                }
              }
            );
          } catch {
            open.disabled = false;
            open.textContent = prevLabel;
            window.location.href = targetUrl;
          }
        }, true);
      }
      rename.className = 'pakflow-project-rename'; rename.setAttribute('aria-label', 'Rename ' + title.textContent);
      remove.className = 'pakflow-project-remove'; remove.textContent = '×';
      remove.setAttribute('aria-label', 'Remove ' + title.textContent);
      card.prepend(preview); card.append(remove);
      schedule();
    }
  };
  function isMobileLayout() {
    return Boolean(window.matchMedia?.('(max-width: 768px), (hover: none) and (pointer: coarse)').matches || window.innerWidth <= 768);
  }
  function updateMobileOffset() {
    const docStyle = document.documentElement?.style;
    if (!root || !docStyle) return;
    if (!isMobileLayout()) {
      if (docStyle.getPropertyValue('--pakflow-mobile-projects-bottom')) {
        docStyle.removeProperty('--pakflow-mobile-projects-bottom');
      }
      return;
    }
    let offset = 142;
    if (window.innerHeight >= 480) {
      const inputs = document.querySelectorAll('textarea,[contenteditable="true"],[role="textbox"]');
      for (const input of inputs) {
        if (root.contains(input)) continue;
        const ir = input.getBoundingClientRect();
        if (!ir.width || !ir.height || ir.bottom < window.innerHeight - 180) continue;
        let cardTop = ir.top, el = input;
        for (let i = 0; i < 6 && el.parentElement && el.parentElement !== document.body; i++) {
          el = el.parentElement;
          const r = el.getBoundingClientRect();
          if (r.width >= window.innerWidth * 0.65 && r.height >= 56 && r.height <= 260 && r.bottom >= window.innerHeight - 90) {
            cardTop = Math.min(cardTop, r.top);
          }
        }
        const needed = Math.round(window.innerHeight - cardTop + 14);
        if (needed > offset) offset = Math.min(220, needed);
      }
    }
    const nextVal = 'calc(' + offset + 'px + env(safe-area-inset-bottom, 0px))';
    if (docStyle.getPropertyValue('--pakflow-mobile-projects-bottom') !== nextVal) {
      docStyle.setProperty('--pakflow-mobile-projects-bottom', nextVal);
    }
  }
  function isOpen() { return drawer && drawer.style.display !== 'none'; }
  function sync() {
    scheduled = false;
    const next = $('pakflow-my-projects-root');
    if (next && next !== root) {
      root = next; drawer = $('pakflow-fp-drawer');
      const toggle = $('pakflow-fp-toggle-btn'), close = $('pakflow-fp-close');
      toggle.firstElementChild.textContent = '🎬';
      toggle.setAttribute('aria-controls', drawer.id); toggle.setAttribute('aria-haspopup', 'dialog');
      drawer.setAttribute('role', 'dialog'); drawer.setAttribute('aria-modal', 'true');
      drawer.setAttribute('aria-labelledby', 'pakflow-history-title'); drawer.tabIndex = -1;
      const header = drawer.firstElementChild;
      const heading = document.createElement('div'); heading.className = 'pakflow-history-heading';
      const title = document.createElement('h2'); title.id = 'pakflow-history-title'; title.textContent = '🎬 My Saved Projects History';
      savedCount = document.createElement('span'); savedCount.className = 'pakflow-saved-count';
      heading.append(title, savedCount); close.textContent = '× Close'; close.setAttribute('aria-label', 'Close saved projects');
      header.replaceChildren(heading, close); header.className = 'pakflow-history-header';
      backdrop = document.createElement('div'); backdrop.className = 'pakflow-history-backdrop'; backdrop.hidden = true;
      backdrop.addEventListener('click', () => { close.click(); sync(); }); root.prepend(backdrop);
      new MutationObserver(schedule).observe(root, { subtree: true, childList: true, characterData: true, attributes: true, attributeFilter: ['style'] });
      toggle.addEventListener('click', schedule); close.addEventListener('click', schedule);
    }
    if (root) {
      updateMobileOffset();
      const count = $('pakflow-fp-count')?.textContent.trim() || '0';
      setText(savedCount, 'Session ' + (currentSlot?.replace('slot_', '') || '1') + ' · ' + count + ' Saved');
      $('pakflow-fp-toggle-btn').title = 'My Saved Projects (Active: Session ' + (currentSlot?.replace('slot_', '') || '1') + ')';
      const open = isOpen(), wasOpen = root.classList.contains('pakflow-history-open');
      root.classList.toggle('pakflow-history-open', open); backdrop.hidden = !open;
      $('pakflow-fp-toggle-btn').setAttribute('aria-expanded', String(open));
      if (open && !wasOpen) { previousFocus = document.activeElement; $('pakflow-fp-close').focus(); }
      if (!open && wasOpen) { previousFocus?.focus(); previousFocus = null; }
    }
  }
  function schedule() { if (!scheduled) { scheduled = true; requestAnimationFrame(sync); } }
  window.addEventListener('resize', schedule, { passive: true });
  window.visualViewport?.addEventListener('resize', schedule, { passive: true });
  document.addEventListener('keydown', event => {
    if (!isOpen()) return;
    if (event.key === 'Escape') { event.preventDefault(); $('pakflow-fp-close').click(); sync(); }
    if (event.key === 'Tab') {
      const nodes = [...drawer.querySelectorAll('button,a[href],[tabindex="0"]')].filter(n => n.getClientRects().length && !n.disabled);
      const first = nodes[0], last = nodes[nodes.length - 1];
      if (event.shiftKey && (document.activeElement === first || !drawer.contains(document.activeElement))) { event.preventDefault(); last?.focus(); }
      else if (!event.shiftKey && (document.activeElement === last || !drawer.contains(document.activeElement))) { event.preventDefault(); first?.focus(); }
    }
  });
  // Silent self-healing on /404?reason=project — NEVER expose internal "Google account / cookies rotated" messages to users!
  let pnfCleanupHandled = false;
  function checkProjectNotFoundRecovery() {
    if (window.top !== window || pnfCleanupHandled) return;
    const isProjectPath = /\/project\/([^/?#]+)/i.test(location.pathname);
    const is404ProjectPath = /^\/404(?:\/|$)/i.test(location.pathname) && /reason=project/i.test(location.search || '');
    if (!isProjectPath && !is404ProjectPath) return;
    const bodyText = (document.body && document.body.innerText) ? document.body.innerText : '';
    if (!is404ProjectPath && !/\bproject\s+not\s+found\.?/i.test(bodyText)) return;

    pnfCleanupHandled = true;
    const mId = location.pathname.match(/\/project\/([^/?#]+)/i);
    let projId = mId ? String(mId[1]).toLowerCase() : '';
    if (!projId) {
      try {
        const navEntry = performance.getEntriesByType && performance.getEntriesByType('navigation')[0];
        const navMatch = navEntry && navEntry.name ? String(navEntry.name).match(/\/project\/([^/?#]+)/i) : null;
        projId = (navMatch && navMatch[1]) ? String(navMatch[1]).toLowerCase() : String(sessionStorage.getItem('pf_last_opened_project_id') || '').toLowerCase();
      } catch {}
    }
    if (!projId) {
      location.replace('https://flow.google.com/');
      return;
    }

    // Permanently remove the dead/unavailable project ID from BOTH local storage and cloud DB so it never reappears on refresh
    try {
      chrome.runtime.sendMessage({ type: 'PAKFLOW_PRUNE_DEAD_PROJECT', id: projId }, () => {
        void chrome.runtime?.lastError;
        location.replace('https://flow.google.com/');
      });
    } catch {
      location.replace('https://flow.google.com/');
    }
  }

  // ── Real Thumbnail + Title Harvester for My Saved Projects History (zero extra network requests) ──
  let lastThumbHarvestAt = 0;
  function snapshotImageToDataUrl(im) {
    try {
      if (!im || !im.complete || !im.naturalWidth || !im.naturalHeight) return '';
      const w = 280;
      const h = Math.max(120, Math.min(240, Math.round((im.naturalHeight / im.naturalWidth) * w)));
      const canvas = document.createElement('canvas');
      canvas.width = w;
      canvas.height = h;
      const ctx = canvas.getContext('2d');
      if (!ctx) return '';
      ctx.drawImage(im, 0, 0, w, h);
      const dataUrl = canvas.toDataURL('image/jpeg', 0.68);
      return (dataUrl && dataUrl.length > 200 && dataUrl.length < 55000) ? dataUrl : '';
    } catch {
      return '';
    }
  }

  function extractMediaThumbFromContainer(container) {
    if (!container || !container.querySelectorAll) return '';
    // 0. Exact Google Flow project thumbnail selector (works even when hidden on homepage!)
    const flowThumb = container.querySelector('img.project-thumbnail, img[src*="/asb/"]');
    if (flowThumb && !flowThumb.closest('#pakflow-my-projects-root')) {
      const src = String(flowThumb.currentSrc || flowThumb.src || '').trim();
      if (/^(?:https:\/\/|blob:|data:image\/)/i.test(src)) {
        const snap = snapshotImageToDataUrl(flowThumb);
        if (snap) return snap;
        if (/^https:\/\//i.test(src)) return src;
      }
    }
    // 1. Direct media <img> (supports /asb/, storage.googleapis.com, googleusercontent.com, blob:, data:image/)
    const imgs = container.querySelectorAll('img');
    for (const im of imgs) {
      if (im.closest('#pakflow-my-projects-root')) continue;
      const src = String(im.currentSrc || im.src || '').trim();
      if (!/^(?:https:\/\/|blob:|data:image\/)/i.test(src)) continue;
      if (/googleusercontent\.com\/a\/|avatar|profile|favicon|logo/i.test(src)) continue;
      const r = im.getBoundingClientRect();
      if ((im.naturalWidth >= 80 || r.width >= 80) && (im.naturalHeight >= 50 || r.height >= 50)) {
        const snap = snapshotImageToDataUrl(im);
        if (snap) return snap;
        if (/^(?:https:\/\/|data:image\/)/i.test(src)) return src;
      }
    }
    // 2. <video poster="..."> or frame snapshot via canvas
    const vids = container.querySelectorAll('video');
    for (const v of vids) {
      if (v.closest('#pakflow-my-projects-root')) continue;
      const poster = String(v.poster || '').trim();
      if (/^https:\/\//i.test(poster)) return poster;
      try {
        if (v.readyState >= 2 && v.videoWidth >= 80 && v.videoHeight >= 50) {
          const w = 280;
          const h = Math.max(120, Math.min(240, Math.round((v.videoHeight / v.videoWidth) * w)));
          const canvas = document.createElement('canvas');
          canvas.width = w;
          canvas.height = h;
          const ctx = canvas.getContext('2d');
          if (ctx) {
            ctx.drawImage(v, 0, 0, w, h);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.65);
            if (dataUrl && dataUrl.length > 200 && dataUrl.length < 55000) return dataUrl;
          }
        }
      } catch {}
    }
    // 3. CSS background-image
    const styled = container.querySelectorAll('[style*="background-image"]');
    for (const el of styled) {
      if (el.closest('#pakflow-my-projects-root')) continue;
      const bg = String(el.style.backgroundImage || '');
      const m = bg.match(/url\(["']?(https:\/\/[^"')]+)["']?\)/i);
      if (m && m[1] && !/avatar|profile|logo/i.test(m[1])) return m[1];
    }
    return '';
  }

  function harvestProjectThumbnails() {
    if (window.top !== window) return;
    if (!chrome?.runtime?.id || !chrome?.storage?.local) return;
    const now = Date.now();
    if (now - lastThumbHarvestAt < 2500) return;
    lastThumbHarvestAt = now;

    const updates = new Map(); // id -> thumbnail
    const realTitles = new Map(); // id -> real Flow title

    // Case A: Inside a specific project editor /project/<id>
    let lastConfirmedEditorSig = '';
    const mProj = location.pathname.match(/\/project\/([^/?#]+)/i);
    if (mProj && mProj[1]) {
      const pid = String(mProj[1]).toLowerCase();
      try { sessionStorage.setItem('pf_last_opened_project_id', pid); } catch {}
      const thumb = extractMediaThumbFromContainer(document.body);
      if (thumb) updates.set(pid, thumb);
      // Extract real Google Flow project title once backend creates the project (e.g. "Nov 25 - 02:51 - Google Flow")
      const cleanDocTitle = String(document.title || '')
        .replace(/\s*[-|–—]\s*Google\s*Flow.*$/i, '')
        .replace(/^Google\s*Flow\s*[-|–—]\s*/i, '')
        .trim();
      const hasRealDocTitle = Boolean(
        cleanDocTitle
        && cleanDocTitle.length <= 90
        && !/^(?:flow|google\s*flow|new\s*project)$/i.test(cleanDocTitle)
        && !/ai\s*creative\s*studio|page\s*not\s*found|project\s*not\s*found/i.test(cleanDocTitle)
      );
      if (hasRealDocTitle) realTitles.set(pid, cleanDocTitle);
      // Confirm & persist this active project into My Projects the moment real media or real backend title exists!
      if (thumb || hasRealDocTitle) {
        const sig = pid + '|' + (hasRealDocTitle ? cleanDocTitle : '') + '|' + (thumb ? thumb.slice(0, 48) : '');
        if (sig !== globalThis.__pfLastConfirmedSig) {
          globalThis.__pfLastConfirmedSig = sig;
          try {
            chrome.runtime.sendMessage({
              type: 'PAKFLOW_CONFIRM_ACTIVE_PROJECT',
              id: pid,
              title: hasRealDocTitle ? cleanDocTitle : '',
              thumbnail: thumb || ''
            }, () => {
              void chrome.runtime?.lastError;
              if (!isOpen()) {
                try { refreshProjects?.(); } catch {}
              }
            });
          } catch {}
        }
      }
    } else {
      // Case B: Google Flow homepage grid — extract real thumbnails & titles ONLY for projects already owned by this user
      const links = document.querySelectorAll('a.project-thumbnail-container[href*="/project/"], a[href*="/project/"]');
      for (const a of links) {
        if (a.closest('#pakflow-my-projects-root')) continue;
        const m = String(a.getAttribute('href') || '').match(/\/project\/([^/?#]+)/i);
        if (!m || !m[1]) continue;
        const pid = String(m[1]).toLowerCase();
        const thumb = extractMediaThumbFromContainer(a) || extractMediaThumbFromContainer(a.closest('.project-card') || a.parentElement);
        if (thumb) updates.set(pid, thumb);
        const cardTitle = a.closest('.project-card')?.querySelector('.project-title-text')?.textContent?.trim();
        if (cardTitle && cardTitle.length <= 120) realTitles.set(pid, cardTitle);
      }
    }

    if (updates.size === 0 && realTitles.size === 0) return;

    const isGenericProjTitle = (t) => !t
      || /^google\s*flow\s*[-–—]\s*ai\s*creative\s*studio/i.test(t)
      || /^(?:flow|google\s*flow|new\s*project)$/i.test(t)
      || /^project\s*#?\d+$/i.test(t);

    try {
      chrome.storage.local.get(['myProjectsUnified', 'myProjectsByIp'], (st) => {
        void chrome.runtime?.lastError;
        if (!st) return;
        let changed = false;
        const patchList = (arr) => {
          if (!Array.isArray(arr)) return arr;
          return arr.map((p) => {
            if (!p || !p.id) return p;
            const pid = String(p.id).toLowerCase();
            let next = p;
            const nextThumb = updates.get(pid) || '';
            if (nextThumb && next.thumbnail !== nextThumb) {
              // Prefer durable data:image/jpeg over expiring https:// URLs
              if (!next.thumbnail || !next.thumbnail.startsWith('data:image/') || nextThumb.startsWith('data:image/')) {
                changed = true;
                next = { ...next, thumbnail: nextThumb };
              }
            }
            const nextTitle = realTitles.get(pid) || '';
            if (nextTitle && !next.customTitle && isGenericProjTitle(String(next.title || '').trim())) {
              changed = true;
              next = { ...next, title: nextTitle };
            }
            return next;
          });
        };
        const nextUnified = patchList(st.myProjectsUnified);
        const nextByIp = st.myProjectsByIp && typeof st.myProjectsByIp === 'object' ? { ...st.myProjectsByIp } : {};
        for (const k of Object.keys(nextByIp)) {
          nextByIp[k] = patchList(nextByIp[k]);
        }
        if (changed) {
          chrome.storage.local.set({ myProjectsUnified: nextUnified, myProjectsByIp: nextByIp }, () => {
            void chrome.runtime?.lastError;
            if (!isOpen()) {
              try { refreshProjects?.(); } catch {}
            }
          });
        }
      });
    } catch {}
  }

  // ── Native Black Privacy Cover + All-Language Genuine "+ New project" + Top-Right Profile on Homepage (/) ──
  let cachedRealNewBtn = null;
  let cachedTopProfileEl = null;
  let overlaySyncRaf = 0;

  function findRealGoogleNewProjectButton() {
    // 1) Language-independent Google Flow native button class (works on English, Vietnam "Dự án mới", Arabic, etc.)
    const byClass = document.querySelector('button.new-project-button, .new-project-button, button.projects-grid-new-project-button');
    if (byClass && !byClass.disabled) return byClass;

    // 2) Fallback: floating bottom CTA button with Material "add" / "add_2" icon or multi-language "New project" text
    let bestBtn = null;
    let bestScore = Infinity;
    let candidates = [];
    try { candidates = document.querySelectorAll('button, [role="button"]'); } catch {}
    for (const el of candidates) {
      if (!el) continue;
      if (el.closest && (el.closest('#pakflow-my-projects-root') || el.closest('#pakflow-access-lock-overlay'))) continue;
      if (el.disabled || (el.getAttribute && el.getAttribute('aria-disabled') === 'true')) continue;
      const rawTxt = String(el.textContent || '').replace(/\s+/g, ' ').trim();
      const aria = String((el.getAttribute && el.getAttribute('aria-label')) || '').replace(/\s+/g, ' ').trim();
      const hasAddIcon = Boolean(el.querySelector('i, .google-symbols, .material-icons, mat-icon')) && /^(?:add(?:_2|_circle)?|\+)\s*\S+/i.test(rawTxt);
      const isKnownLang = /new\s*project|dự\s*án\s*mới|nuevo\s*proyecto|nouveau\s*projet|neues\s*projekt|مشروع\s*جديد|नया\s*प्रोजेक्ट|새\s*프로젝트|新しいプロジェクト|新建项目/i.test(rawTxt + ' ' + aria);
      if (isKnownLang || (hasAddIcon && rawTxt.length <= 48 && getComputedStyle(el).position === 'fixed')) {
        const score = (el.tagName === 'BUTTON' ? 0 : 100) + rawTxt.length;
        if (score < bestScore) {
          bestScore = score;
          bestBtn = el;
        }
      }
    }
    return bestBtn;
  }

  function findTopRightProfileControl() {
    if (!document.body) return null;
    // Locate the compact top-right ULTRA + User Avatar pill in Google Flow's header
    const avatarImgs = document.querySelectorAll('img[src*="googleusercontent.com/a/"], [aria-label*="@"] img, a[aria-label*="Google Account" i], button[aria-label*="Google Account" i], a[aria-label*="Tài khoản Google" i], button[aria-label*="Tài khoản Google" i]');
    for (const node of avatarImgs) {
      if (node.closest('#pakflow-my-projects-root')) continue;
      let pill = node;
      let cur = node.parentElement;
      for (let d = 0; d < 6 && cur && cur !== document.body; d++) {
        const r = cur.getBoundingClientRect();
        if (r.top >= 0 && r.top <= 120 && r.right >= window.innerWidth * 0.65 && r.width <= 260 && r.height <= 80) {
          // Stop before grabbing the entire full-width header bar
          pill = cur;
        } else if (r.width > 260) {
          break;
        }
        cur = cur.parentElement;
      }
      const pr = pill.getBoundingClientRect();
      if (pr.width > 0 && pr.width <= 260 && pr.top >= 0 && pr.top <= 120) {
        return pill;
      }
    }
    // Fallback: look for compact top-right "ULTRA" badge container
    const spans = document.querySelectorAll('span, div, button');
    for (const s of spans) {
      if (s.closest('#pakflow-my-projects-root')) continue;
      if (String(s.textContent || '').trim().toUpperCase() !== 'ULTRA') continue;
      const r = s.getBoundingClientRect();
      if (r.top >= 0 && r.top <= 120 && r.left >= window.innerWidth * 0.65 && r.width > 0 && r.width < 160) {
        let box = s;
        let p = s.parentElement;
        for (let d = 0; d < 4 && p && p !== document.body; d++) {
          const pr = p.getBoundingClientRect();
          if (pr.width <= 240 && pr.height <= 76 && pr.top >= 0 && pr.top <= 120) box = p;
          else break;
          p = p.parentElement;
        }
        return box;
      }
    }
    return null;
  }

  function syncFlowHomePrivacyOverlay() {
    if (window.top !== window) return;
    const host = String(location.hostname || '').toLowerCase();
    const path = String(location.pathname || '/');
    const isHome = (host === 'flow.google.com' && (path === '/' || path === ''))
      || (/(?:^|\.)labs\.google$/i.test(host) && /^\/fx\/tools\/flow\/?$/i.test(path));

    if (!isHome) {
      if (document.documentElement && document.documentElement.hasAttribute('data-pakflow-flow-home')) {
        document.documentElement.removeAttribute('data-pakflow-flow-home');
      }
      if (cachedRealNewBtn) {
        try { cachedRealNewBtn.removeAttribute('data-pakflow-real-new-btn'); } catch {}
        cachedRealNewBtn = null;
      }
      if (cachedTopProfileEl) {
        try { cachedTopProfileEl.removeAttribute('data-pakflow-top-profile'); } catch {}
        cachedTopProfileEl = null;
      }
      return;
    }

    // Fast path when both genuine New Project button and top-right Profile pill are already tagged
    const btnReady = cachedRealNewBtn && cachedRealNewBtn.isConnected && cachedRealNewBtn.getAttribute('data-pakflow-real-new-btn') === 'true';
    const profReady = cachedTopProfileEl && cachedTopProfileEl.isConnected && cachedTopProfileEl.getAttribute('data-pakflow-top-profile') === 'true';
    if (btnReady && profReady) {
      if (document.documentElement && document.documentElement.getAttribute('data-pakflow-flow-home') !== 'true') {
        document.documentElement.setAttribute('data-pakflow-flow-home', 'true');
      }
      return;
    }

    const realNewBtn = btnReady ? cachedRealNewBtn : findRealGoogleNewProjectButton();
    const hasProjLinks = Boolean(document.querySelector('a[href*="/project/"]'));

    // Do not cover Google's sign-in screen if genuinely signed out
    if (!realNewBtn && !hasProjLinks) {
      const hasSignInCta = Boolean(document.querySelector(
        'a[href*="accounts.google.com/ServiceLogin"], a[href*="accounts.google.com/v3/signin"], button[aria-label*="Sign in" i]'
      ));
      if (hasSignInCta) {
        if (document.documentElement) document.documentElement.removeAttribute('data-pakflow-flow-home');
        return;
      }
    }

    if (document.documentElement && document.documentElement.getAttribute('data-pakflow-flow-home') !== 'true') {
      document.documentElement.setAttribute('data-pakflow-flow-home', 'true');
    }

    if (realNewBtn && realNewBtn !== cachedRealNewBtn) {
      if (cachedRealNewBtn) {
        try { cachedRealNewBtn.removeAttribute('data-pakflow-real-new-btn'); } catch {}
      }
      realNewBtn.setAttribute('data-pakflow-real-new-btn', 'true');
      cachedRealNewBtn = realNewBtn;
    }

    const topProf = profReady ? cachedTopProfileEl : findTopRightProfileControl();
    if (topProf && topProf !== cachedTopProfileEl) {
      if (cachedTopProfileEl) {
        try { cachedTopProfileEl.removeAttribute('data-pakflow-top-profile'); } catch {}
      }
      topProf.setAttribute('data-pakflow-top-profile', 'true');
      cachedTopProfileEl = topProf;
    }
  }

  function scheduleFlowHomeOverlaySync() {
    if (overlaySyncRaf) return;
    overlaySyncRaf = requestAnimationFrame(() => {
      overlaySyncRaf = 0;
      syncFlowHomePrivacyOverlay();
    });
  }

  try {
    const origPush = history.pushState;
    const origReplace = history.replaceState;
    if (typeof origPush === 'function') {
      history.pushState = function () {
        const res = origPush.apply(this, arguments);
        setTimeout(syncFlowHomePrivacyOverlay, 0);
        return res;
      };
    }
    if (typeof origReplace === 'function') {
      history.replaceState = function () {
        const res = origReplace.apply(this, arguments);
        setTimeout(syncFlowHomePrivacyOverlay, 0);
        return res;
      };
    }
    window.addEventListener('popstate', () => setTimeout(syncFlowHomePrivacyOverlay, 0));
  } catch {}

  new MutationObserver(() => { schedule(); checkProjectNotFoundRecovery(); scheduleFlowHomeOverlaySync(); }).observe(document, { childList: true, characterData: true, subtree: true });
  setInterval(() => { checkProjectNotFoundRecovery(); harvestProjectThumbnails(); syncFlowHomePrivacyOverlay(); }, 1500);
  syncFlowHomePrivacyOverlay();
  schedule();
})();
