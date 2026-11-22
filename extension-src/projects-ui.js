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
      date.textContent = '▦ Server ' + srvNum + ' · ' + dateStr;
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
      open.className = 'pakflow-project-open'; open.textContent = '▶ Open This Project';
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
      setText(savedCount, 'Server ' + (currentSlot?.replace('slot_', '') || '1') + ' · ' + count + ' Saved');
      $('pakflow-fp-toggle-btn').title = 'My Saved Projects (Active: Server ' + (currentSlot?.replace('slot_', '') || '1') + ')';
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
  new MutationObserver(schedule).observe(document, { childList: true, characterData: true, subtree: true });
  schedule();
})();
