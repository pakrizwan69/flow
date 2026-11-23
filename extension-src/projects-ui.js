/* PakFlow: project presentation and selected-server refresh. */
(() => {
  'use strict';
  const $ = id => document.getElementById(id);
  let root, drawer, backdrop, savedCount, previousFocus, scheduled = false;
  let currentSlot = 'slot_1', refreshProjects;
  const normalizeSlot = slot => typeof slot === 'string' && slot.trim() && slot !== 'active' ? slot.trim() : 'slot_1';
  function updateServer(state) {
    void chrome.runtime?.lastError;
    if (!state) return;
    const slot = typeof state.activeSlot === 'string' && state.activeSlot.trim() && state.activeSlot !== 'active'
      ? normalizeSlot(state.activeSlot)
      : 'slot_' + ((Number.isInteger(state.sessionLoopIndex) && state.sessionLoopIndex >= 0 ? state.sessionLoopIndex : 0) + 1);
    if (slot !== currentSlot) { currentSlot = slot; refreshProjects?.(); schedule(); }
  }
  try {
    if (chrome?.runtime?.id && chrome?.storage?.local) {
      chrome.storage.local.get(['activeSlot', 'sessionLoopIndex'], updateServer);
      chrome.storage.onChanged.addListener((changes, area) => {
        try {
          if (!chrome?.runtime?.id) return;
          if (area === 'local' && (changes.activeSlot || changes.sessionLoopIndex)) {
            chrome.storage.local.get(['activeSlot', 'sessionLoopIndex'], updateServer);
          }
        } catch {}
      });
    }
  } catch {}
  const setText = (node, value) => { if (node.textContent !== value) node.textContent = value; };
  globalThis.PakFlowUI = {
    filterProjects(projects) {
      if (!Array.isArray(projects) || projects.length === 0) return [];
      const slot = currentSlot || 'slot_1';
      // Always keep all saved projects visible (prioritizing active server's projects first)
      // so users never see an empty "No projects found" screen after a server switch or shuffle.
      const seen = new Set();
      const activeFirst = [];
      const others = [];
      for (const p of projects) {
        if (!p || !p.id) continue;
        const id = String(p.id).toLowerCase();
        if (seen.has(id)) continue;
        seen.add(id);
        if (normalizeSlot(p.slot) === slot) activeFirst.push(p);
        else others.push(p);
      }
      return [...activeFirst, ...others];
    },
    setProjectRefresh(callback) { refreshProjects = callback; queueMicrotask(callback); },
    upgradeCard(card, project) {
      card.classList.add('pakflow-project-card');
      const info = card.children[0], actions = card.children[1];
      info.className = 'pakflow-project-info';
      const title = info.querySelector('span');
      title.title = String(project.title || 'Flow Video Project');
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
      const play = document.createElement('span'); play.className = 'pakflow-preview-play'; play.textContent = '▶';
      const caption = document.createElement('span'); caption.textContent = 'Flow Video Project';
      preview.append(play, caption);
      const thumbnail = project.thumbnail || project.thumbnailUrl || project.imageUrl;
      if (typeof thumbnail === 'string' && /^https:\/\//i.test(thumbnail)) {
        const img = document.createElement('img'); img.alt = String(project.title || 'Flow Video Project');
        img.loading = 'lazy'; img.referrerPolicy = 'no-referrer';
        img.addEventListener('error', () => img.remove(), { once: true });
        img.src = thumbnail; preview.prepend(img);
      }
      const [open, rename, remove] = actions.children;
      actions.className = 'pakflow-project-actions';
      const targetSlot = normalizeSlot(project.slot);
      const targetNum = parseInt(targetSlot.replace(/\D+/g, ''), 10) || 1;
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
          const prevLabel = open.textContent;
          open.disabled = true;
          open.textContent = 'Switching to S' + targetNum + '...';
          const targetUrl = project.url || ('https://flow.google.com/project/' + project.id);
          try {
            chrome.runtime.sendMessage(
              { type: 'PAKFLOW_REINJECT_CURRENT', slotNumber: targetNum, openFlow: false },
              (res) => {
                void chrome.runtime?.lastError;
                open.disabled = false;
                open.textContent = prevLabel;
                if (res && res.ok) {
                  window.location.href = targetUrl;
                } else {
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
  function checkProjectNotFoundRecovery() {
    if (window.top !== window) return;
    if (!/\/project\/([^/?#]+)/i.test(location.pathname)) {
      const old = $('pakflow-pnf-helper');
      if (old) old.remove();
      return;
    }
    const bodyText = (document.body && document.body.innerText) ? document.body.innerText : '';
    if (!/\bproject\s+not\s+found\.?/i.test(bodyText)) {
      const old = $('pakflow-pnf-helper');
      if (old) old.remove();
      return;
    }
    if ($('pakflow-pnf-helper')) return;
    const mId = location.pathname.match(/\/project\/([^/?#]+)/i);
    const projId = mId ? String(mId[1]).toLowerCase() : '';
    if (!projId) return;

    const mountHelper = (ownerSlot) => {
      if ($('pakflow-pnf-helper')) return;
      const box = document.createElement('div');
      box.id = 'pakflow-pnf-helper';
      box.style.cssText = 'position:fixed;left:50%;bottom:84px;transform:translateX(-50%);z-index:2147483000;max-width:440px;width:calc(100vw - 32px);padding:14px 16px;border-radius:12px;background:#0f172a;border:1px solid rgba(148,163,184,0.28);color:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;box-shadow:0 16px 40px rgba(0,0,0,0.55);text-align:center;';

      const activeSlot = normalizeSlot(currentSlot);
      const activeNum = parseInt(activeSlot.replace(/\D+/g, ''), 10) || 1;
      const ownerNum = ownerSlot ? (parseInt(normalizeSlot(ownerSlot).replace(/\D+/g, ''), 10) || 0) : 0;

      if (ownerNum > 0 && normalizeSlot(ownerSlot) !== activeSlot) {
        box.innerHTML =
          '<div style="font-size:13px;font-weight:700;margin-bottom:4px;color:#fcd34d;">Project belongs to Session ' + ownerNum + '</div>' +
          '<div style="font-size:12px;color:#cbd5e1;margin-bottom:12px;line-height:1.45;">You are currently on Session ' + activeNum + '. Switch back to Session ' + ownerNum + ' to open this project.</div>' +
          '<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">' +
            '<button id="pfPnfSwitchBtn" type="button" style="padding:8px 14px;border-radius:8px;border:0;background:#2563eb;color:#fff;font-weight:700;font-size:12.5px;cursor:pointer;">Switch to Session ' + ownerNum + ' &amp; Open</button>' +
          '</div>';
        document.body.appendChild(box);
        const btn = $('pfPnfSwitchBtn');
        if (btn) {
          btn.addEventListener('click', () => {
            btn.disabled = true;
            btn.textContent = 'Switching to Session ' + ownerNum + '...';
            try {
              chrome.runtime.sendMessage(
                { type: 'PAKFLOW_REINJECT_CURRENT', slotNumber: ownerNum, openFlow: false },
                () => { void chrome.runtime?.lastError; window.location.reload(); }
              );
            } catch { window.location.reload(); }
          });
        }
      } else {
        box.innerHTML =
          '<div style="font-size:13px;font-weight:700;margin-bottom:4px;color:#f8fafc;">Project unavailable on current Google account</div>' +
          '<div style="font-size:12px;color:#94a3b8;margin-bottom:12px;line-height:1.45;">This project was created on a different Google account or session cookies were rotated.</div>' +
          '<div style="display:flex;gap:8px;justify-content:center;flex-wrap:wrap;">' +
            '<button id="pfPnfGoHomeBtn" type="button" style="padding:7px 13px;border-radius:8px;border:0;background:#2563eb;color:#fff;font-weight:600;font-size:12px;cursor:pointer;">Go to Flow Home</button>' +
            '<button id="pfPnfRemoveBtn" type="button" style="padding:7px 13px;border-radius:8px;border:1px solid rgba(239,68,68,0.35);background:rgba(239,68,68,0.14);color:#fca5a5;font-weight:600;font-size:12px;cursor:pointer;">Remove from My Projects</button>' +
          '</div>';
        document.body.appendChild(box);
        const homeBtn = $('pfPnfGoHomeBtn');
        if (homeBtn) homeBtn.addEventListener('click', () => { window.location.href = 'https://flow.google.com/'; });
        const remBtn = $('pfPnfRemoveBtn');
        if (remBtn) {
          remBtn.addEventListener('click', () => {
            remBtn.disabled = true;
            try {
              chrome.storage.local.get(['myProjectsUnified', 'myProjectsByIp'], (st) => {
                void chrome.runtime?.lastError;
                const unified = Array.isArray(st?.myProjectsUnified)
                  ? st.myProjectsUnified.filter((p) => String(p?.id || '').toLowerCase() !== projId)
                  : [];
                const byIp = st?.myProjectsByIp && typeof st.myProjectsByIp === 'object' ? { ...st.myProjectsByIp } : {};
                for (const k of Object.keys(byIp)) {
                  if (Array.isArray(byIp[k])) {
                    byIp[k] = byIp[k].filter((p) => String(p?.id || '').toLowerCase() !== projId);
                  }
                }
                chrome.storage.local.set({ myProjectsUnified: unified, myProjectsByIp: byIp }, () => {
                  window.location.href = 'https://flow.google.com/';
                });
              });
            } catch { window.location.href = 'https://flow.google.com/'; }
          });
        }
      }
    };

    try {
      chrome.storage.local.get(['myProjectsUnified', 'myProjectsByIp'], (st) => {
        void chrome.runtime?.lastError;
        let foundSlot = '';
        const scan = (arr) => {
          if (!Array.isArray(arr) || foundSlot) return;
          for (const p of arr) {
            if (p && String(p.id || '').toLowerCase() === projId && p.slot) {
              foundSlot = normalizeSlot(p.slot);
              return;
            }
          }
        };
        scan(st?.myProjectsUnified);
        if (!foundSlot && st?.myProjectsByIp && typeof st.myProjectsByIp === 'object') {
          for (const arr of Object.values(st.myProjectsByIp)) scan(arr);
        }
        mountHelper(foundSlot);
      });
    } catch {}
  }

  new MutationObserver(() => { schedule(); checkProjectNotFoundRecovery(); }).observe(document, { childList: true, characterData: true, subtree: true });
  setInterval(checkProjectNotFoundRecovery, 2000);
  schedule();
})();
