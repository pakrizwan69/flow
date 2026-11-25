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
      const rawProjId = String(project.rawId || project.id || '').trim();
      const targetUrl = (project.url && /^https:\/\/(?:flow\.google\.com|(?:[a-z0-9-]+\.)?labs\.google)\//i.test(String(project.url)))
        ? String(project.url).trim()
        : ('https://flow.google.com/project/' + rawProjId);
      const rememberOpenedProject = () => {
        try {
          sessionStorage.setItem('pf_last_opened_project_id', String(project.id || '').toLowerCase());
          sessionStorage.setItem('pf_last_opened_project_meta', JSON.stringify({
            id: String(project.id || '').toLowerCase(),
            rawId: rawProjId,
            url: targetUrl,
            slotNumber: targetNum,
            accountHash: String(project.accountHash || '')
          }));
        } catch {}
      };
      const isDifferentSlot = project.accountHash && currentAccountHash
        ? (project.accountHash !== currentAccountHash)
        : (targetSlot !== normalizeSlot(currentSlot));
      open.className = 'pakflow-project-open';
      open.textContent = isDifferentSlot ? ('▶ Switch S' + targetNum + ' & Open') : '▶ Open This Project';
      open.title = isDifferentSlot
        ? ('Created on Session ' + targetNum + ' — click to switch to Session ' + targetNum + ' and open')
        : 'Open saved project URL in Google Flow';
      const triggerVerifiedOpen = (ev) => {
        if (ev) {
          ev.preventDefault();
          ev.stopImmediatePropagation();
        }
        if (open.disabled) return;
        rememberOpenedProject();
        const prevLabel = open.textContent;
        open.disabled = true;
        open.textContent = isDifferentSlot ? ('Switching to S' + targetNum + '...') : 'Opening Project...';
        try {
          chrome.runtime.sendMessage(
            {
              type: 'PAKFLOW_OPEN_SAVED_PROJECT',
              id: String(project.id || '').toLowerCase(),
              rawId: rawProjId,
              slotNumber: targetNum,
              accountHash: String(project.accountHash || ''),
              targetUrl
            },
            (res) => {
              void chrome.runtime?.lastError;
              open.disabled = false;
              open.textContent = prevLabel;
              if (!res || !res.navigated) {
                window.location.href = (res && res.targetUrl) ? res.targetUrl : targetUrl;
              }
            }
          );
        } catch {
          open.disabled = false;
          open.textContent = prevLabel;
          window.location.href = targetUrl;
        }
      };
      preview.addEventListener('click', triggerVerifiedOpen);
      info.addEventListener('click', rememberOpenedProject, true);
      if (!open.dataset.pfSwitchBound) {
        open.dataset.pfSwitchBound = '1';
        open.addEventListener('click', triggerVerifiedOpen, true);
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

    // First attempt self-heal: if DNR cookies had not finished syncing right after an extension update/reinstall,
    // re-inject the saved project's session cookies once and retry its exact saved URL instead of deleting it!
    try {
      const retryKey = 'pf_retried_proj_' + projId;
      const alreadyRetried = sessionStorage.getItem(retryKey) === '1';
      let savedMeta = null;
      try {
        const rawMeta = sessionStorage.getItem('pf_last_opened_project_meta');
        if (rawMeta) {
          const parsed = JSON.parse(rawMeta);
          if (parsed && parsed.id === projId) savedMeta = parsed;
        }
      } catch {}
      if (!alreadyRetried && savedMeta && savedMeta.url) {
        sessionStorage.setItem(retryKey, '1');
        chrome.runtime.sendMessage({
          type: 'PAKFLOW_OPEN_SAVED_PROJECT',
          id: projId,
          rawId: savedMeta.rawId || projId,
          slotNumber: Number(savedMeta.slotNumber) || 1,
          accountHash: savedMeta.accountHash || '',
          targetUrl: savedMeta.url,
          forceReinject: true
        }, (res) => {
          void chrome.runtime?.lastError;
          if (!res || !res.navigated) {
            location.replace(savedMeta.url);
          }
        });
        return;
      }
    } catch {}

    // Only prune if project still 404s after verified session cookie injection
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
    const realUrls = new Map(); // id -> { rawId, url }

    // Case A: Inside a specific project editor /project/<id>
    const mProj = location.pathname.match(/\/project\/([^/?#]+)/i);
    if (mProj && mProj[1]) {
      const rawPid = String(mProj[1]).trim();
      const pid = rawPid.toLowerCase();
      const exactEditorUrl = String(location.origin + location.pathname);
      realUrls.set(pid, { rawId: rawPid, url: exactEditorUrl });
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
      // Confirm & persist this active project into My Projects & Cloud DB ONLY when an actual <video> exists in the project!
      const hasGeneratedVideo = Boolean(document.querySelector('video'));
      if (hasGeneratedVideo) {
        const sig = pid + '|' + exactEditorUrl + '|' + (hasRealDocTitle ? cleanDocTitle : '') + '|' + (thumb ? thumb.slice(0, 48) : 'vid');
        if (sig !== globalThis.__pfLastConfirmedSig) {
          globalThis.__pfLastConfirmedSig = sig;
          try {
            chrome.runtime.sendMessage({
              type: 'PAKFLOW_CONFIRM_ACTIVE_PROJECT',
              id: pid,
              rawId: rawPid,
              url: exactEditorUrl,
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
      // Case B: Google Flow homepage grid — extract real URLs, thumbnails & titles ONLY for projects already owned by this user
      const links = document.querySelectorAll('a.project-thumbnail-container[href*="/project/"], a[href*="/project/"]');
      for (const a of links) {
        if (a.closest('#pakflow-my-projects-root')) continue;
        const hrefAttr = String(a.getAttribute('href') || '').trim();
        const m = hrefAttr.match(/\/project\/([^/?#]+)/i);
        if (!m || !m[1]) continue;
        const rawPid = String(m[1]).trim();
        const pid = rawPid.toLowerCase();
        try {
          const fullLink = new URL(hrefAttr, location.origin).href;
          realUrls.set(pid, { rawId: rawPid, url: fullLink });
        } catch {}
        const thumb = extractMediaThumbFromContainer(a) || extractMediaThumbFromContainer(a.closest('.project-card') || a.parentElement);
        if (thumb) updates.set(pid, thumb);
        const cardTitle = a.closest('.project-card')?.querySelector('.project-title-text')?.textContent?.trim();
        if (cardTitle && cardTitle.length <= 120) realTitles.set(pid, cardTitle);
      }
    }

    if (updates.size === 0 && realTitles.size === 0 && realUrls.size === 0) return;

    const patchPayload = [];
    const allIds = new Set([...updates.keys(), ...realTitles.keys(), ...realUrls.keys()]);
    for (const pid of allIds) {
      const urlInfo = realUrls.get(pid);
      patchPayload.push({
        id: pid,
        rawId: urlInfo?.rawId || '',
        url: urlInfo?.url || '',
        thumbnail: updates.get(pid) || '',
        title: realTitles.get(pid) || ''
      });
    }
    try {
      chrome.runtime.sendMessage({ type: 'PAKFLOW_PATCH_PROJECT_META', patches: patchPayload }, (res) => {
        void chrome.runtime?.lastError;
        if (res && res.ok && !isOpen()) {
          try { refreshProjects?.(); } catch {}
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

  // ── Homepage Top-Right [ ULTRA | Avatar ] Pill + Clickable Live Credits Popover (Matches In-Project UI) ──
  let homeUltraRoot = null;
  let homeUltraMenuOpen = false;
  let homeAccountState = {
    email: '',
    credits: 45000,
    plan: 'ultra',
    portalOrigin: 'https://flowbypak.com',
    avatarInitials: ''
  };

  function computeAvatarInitials(email, fallbackText) {
    const fb = String(fallbackText || '').trim();
    if (fb) {
      const cleanFb = fb.replace(/[^a-zA-Z0-9\u00C0-\u024F]/g, '').trim();
      if (cleanFb.length >= 1 && cleanFb.length <= 3) return cleanFb.slice(0, 2);
      const words = fb.split(/\s+/).filter(Boolean);
      if (words.length >= 2) {
        return (words[0].charAt(0) + words[1].charAt(0));
      }
      if (cleanFb.length > 3) {
        return cleanFb.charAt(0).toUpperCase() + cleanFb.charAt(1).toLowerCase();
      }
    }
    const local = String(email || '').split('@')[0].replace(/[^a-zA-Z0-9]/g, '').trim();
    if (local.length >= 2) return local.charAt(0).toUpperCase() + local.charAt(1).toLowerCase();
    if (local.length === 1) return local.toUpperCase();
    return 'PF';
  }

  function upgradeGoogleAvatarUrl(rawUrl) {
    // Keep Google's exact URL untouched so signed /ogw/ & /a/ avatars load directly from browser memory cache without 400 errors
    return String(rawUrl || '').trim();
  }

  function isMaterialIconElement(el) {
    if (!el || el.nodeType !== 1) return false;
    if (el.matches('i, mat-icon, svg, path, .google-symbols, .material-icons, [class*="material-symbols"], [class*="google-symbols"]')) return true;
    return false;
  }

  function findNativeHomeAvatarControl() {
    if (!document.body) return null;
    let bestEl = null;

    const candidates = document.querySelectorAll(
      'a[aria-label*="Google Account" i], button[aria-label*="Google Account" i], a[aria-label*="Tài khoản Google" i], button[aria-label*="Tài khoản Google" i], img[src*="googleusercontent.com"], img[src*="ggpht.com"], [aria-label*="@"] img, [aria-label*="@"]'
    );
    for (const node of candidates) {
      if (node.closest('#pakflow-my-projects-root') || node.closest('#pakflow-home-ultra-root') || node.closest('#pakflow-access-lock-overlay')) continue;
      let box = node;
      let cur = node.parentElement;
      for (let d = 0; d < 5 && cur && cur !== document.body; d++) {
        const cr = cur.getBoundingClientRect();
        if (cr.top >= 0 && cr.top <= 120 && cr.right >= window.innerWidth * 0.70 && cr.width >= 24 && cr.width <= 68 && cr.height >= 24 && cr.height <= 68 && Math.abs(cr.width - cr.height) <= 14) {
          box = cur;
        } else if (cr.width > 68) {
          break;
        }
        cur = cur.parentElement;
      }
      const br = box.getBoundingClientRect();
      if (br.width >= 22 && br.width <= 68 && br.height >= 22 && br.height <= 68 && br.top >= 0 && br.top <= 120 && br.right >= window.innerWidth * 0.70) {
        bestEl = box;
        break;
      }
    }

    // Fallback: rightmost circular avatar element in top-right corner
    if (!bestEl) {
      let bestRight = -1;
      for (const el of document.body.querySelectorAll('button, a, [role="button"], div, span')) {
        if (el.closest('#pakflow-my-projects-root') || el.closest('#pakflow-home-ultra-root') || el.closest('#pakflow-access-lock-overlay')) continue;
        if (isMaterialIconElement(el) || el.querySelector('i, mat-icon, .google-symbols, .material-icons')) continue;
        const r = el.getBoundingClientRect();
        if (r.top < 0 || r.top > 110 || r.right < window.innerWidth * 0.78 || r.width < 26 || r.width > 64 || Math.abs(r.width - r.height) > 10) continue;
        const hasImgOrRing = Boolean(el.querySelector('img, svg'));
        const txt = String(el.textContent || '').trim();
        const isMonogram = txt.length >= 1 && txt.length <= 2 && /^[A-Za-z0-9\u00C0-\u024F]+$/.test(txt);
        if ((hasImgOrRing || isMonogram) && r.right >= bestRight) {
          bestRight = r.right;
          bestEl = el;
        }
      }
    }

    if (!bestEl) return null;
    const outerRect = bestEl.getBoundingClientRect();
    let visRect = outerRect;
    // Measure tightest outer visual ring/avatar inside padded touch targets (e.g. 40x40 Google One SVG ring inside 48x48 <a>)
    let maxInnerSize = 0;
    for (const ch of bestEl.querySelectorAll('svg, img, span, div')) {
      const cr = ch.getBoundingClientRect();
      if (cr.width >= 26 && cr.height >= 26 && cr.width <= outerRect.width && Math.abs(cr.width - cr.height) <= 6) {
        if (cr.width >= maxInnerSize && cr.width <= 46) {
          maxInnerSize = cr.width;
          visRect = cr;
        }
      }
    }
    if (visRect.width > 46 && outerRect.width > 46) {
      const im = bestEl.querySelector('img');
      if (im) {
        const ir = im.getBoundingClientRect();
        if (ir.width >= 24 && ir.width <= 46) visRect = ir;
      }
    }
    return { el: bestEl, rect: outerRect, visRect };
  }

  function detectNativeGoogleAvatarInfo() {
    if (!document.body) return { imgSrc: '', initials: '', bgColor: '' };
    let foundImg = '';
    let foundInitials = '';
    let foundBg = '';

    // 1. Inspect top-right Google Account / ULTRA controls (works on both '/' and '/project/<id>')
    const nodes = document.querySelectorAll(
      '[data-pf-google-name], a[aria-label*="Google Account" i], button[aria-label*="Google Account" i], a[aria-label*="Tài khoản Google" i], button[aria-label*="Tài khoản Google" i], img[src*="googleusercontent.com"], img[src*="ggpht.com"], [aria-label*="@"]'
    );
    for (const n of nodes) {
      if (n.closest('#pakflow-my-projects-root') || n.closest('#pakflow-home-ultra-root')) continue;
      const gName = n.getAttribute?.('data-pf-google-name') || '';
      if (gName && !foundInitials) {
        foundInitials = computeAvatarInitials('', gName);
      }
      if (!foundInitials) {
        const ariaOrig = String(n.getAttribute?.('aria-label') || n.getAttribute?.('title') || '');
        const mAria = ariaOrig.match(/(?:Google Account|Tài khoản Google):\s*([^(@]+)(?=\s*\()/i);
        if (mAria && mAria[1] && mAria[1].trim().toLowerCase() !== 'pakflow') {
          foundInitials = computeAvatarInitials('', mAria[1].trim());
        }
      }
      const im = n.tagName === 'IMG' ? n : n.querySelector?.('img[src*="googleusercontent.com"], img[src*="ggpht.com"], img[src^="https://"]');
      if (im && (im.currentSrc || im.src) && !/favicon|logo|gstatic\.com\/images\/branding/i.test(im.currentSrc || im.src)) {
        const candidate = upgradeGoogleAvatarUrl(im.currentSrc || im.src);
        if (!/\/default-user/i.test(candidate)) {
          foundImg = candidate;
        }
      }
      if (!isMaterialIconElement(n) && !n.querySelector?.('i, mat-icon, .google-symbols, .material-icons')) {
        const rawTxt = String(n.textContent || '').replace(/\bULTRA\b/gi, '').replace(/\s+/g, '').trim();
        if (rawTxt.length >= 1 && rawTxt.length <= 2 && /^[A-Za-z0-9\u00C0-\u024F]+$/.test(rawTxt)) {
          foundInitials = rawTxt;
          try {
            const cs = getComputedStyle(n);
            if (cs && cs.backgroundColor && cs.backgroundColor !== 'rgba(0, 0, 0, 0)' && cs.backgroundColor !== 'transparent' && cs.backgroundColor !== 'rgb(0, 0, 0)') {
              foundBg = cs.backgroundColor;
            }
          } catch {}
        }
      }
      if (foundImg && foundInitials) break;
    }

    // 2. Scan top-right corner (rightmost circular element wins — works on both '/' native avatar & '/project/<id>' [ULTRA | Ti])
    if (!foundImg || !foundInitials) {
      let bestRightForImg = -1;
      let bestRightForTxt = -1;
      const topEls = document.body.querySelectorAll('img, button, a, span, div');
      for (const el of topEls) {
        if (el.closest('#pakflow-my-projects-root') || el.closest('#pakflow-home-ultra-root') || el.closest('#pakflow-access-lock-overlay')) continue;
        if (isMaterialIconElement(el)) continue;
        const r = el.getBoundingClientRect();
        if (r.top < 0 || r.top > 110 || r.left < window.innerWidth * 0.75 || r.width < 22 || r.width > 56 || Math.abs(r.width - r.height) > 6) continue;
        if (el.tagName === 'IMG' && (el.currentSrc || el.src) && !/favicon|logo|gstatic\.com\/images\/branding/i.test(el.currentSrc || el.src)) {
          const cand = upgradeGoogleAvatarUrl(el.currentSrc || el.src);
          if (!/\/default-user/i.test(cand) && r.right >= bestRightForImg) {
            bestRightForImg = r.right;
            foundImg = cand;
          }
        } else if (!el.querySelector('i, mat-icon, svg, .google-symbols, .material-icons, [class*="material-symbols"]')) {
          const t = String(el.textContent || '').trim();
          if (t.length >= 1 && t.length <= 2 && /^[A-Za-z0-9\u00C0-\u024F]+$/.test(t) && r.right >= bestRightForTxt) {
            bestRightForTxt = r.right;
            foundInitials = t;
            try {
              const bg = getComputedStyle(el).backgroundColor;
              if (bg && bg !== 'rgba(0, 0, 0, 0)' && bg !== 'transparent' && bg !== 'rgb(0, 0, 0)' && bg !== 'rgb(19, 19, 20)') {
                foundBg = bg;
              }
            } catch {}
          }
        }
      }
    }

    // 3. Persist / restore per active Google Account hash so '/' and '/project/<id>' ALWAYS show the exact same Google avatar
    const cacheKey = 'pf_google_av_' + (currentAccountHash || currentSlot || 'default');
    if (foundImg || foundInitials) {
      try {
        sessionStorage.setItem(cacheKey, JSON.stringify({ imgSrc: foundImg, initials: foundInitials, bgColor: foundBg }));
      } catch {}
    } else {
      try {
        const cachedRaw = sessionStorage.getItem(cacheKey);
        if (cachedRaw) {
          const parsed = JSON.parse(cachedRaw);
          if (parsed) return { imgSrc: parsed.imgSrc || '', initials: parsed.initials || '', bgColor: parsed.bgColor || '' };
        }
      } catch {}
    }

    return { imgSrc: foundImg, initials: foundInitials, bgColor: foundBg };
  }

  function ensureHomeUltraProfileUI() {
    if (!document.body) return null;
    let el = document.getElementById('pakflow-home-ultra-root');
    if (el) {
      homeUltraRoot = el;
      return el;
    }
    if (!document.getElementById('pakflow-home-ultra-style')) {
      const st = document.createElement('style');
      st.id = 'pakflow-home-ultra-style';
      st.textContent = `
        #pakflow-home-ultra-root { position: fixed; top: 14px; right: 18px; z-index: 2147483640; font-family: 'Google Sans', Roboto, Inter, system-ui, -apple-system, sans-serif; user-select: none; }
        #pakflow-home-ultra-pill { display: inline-flex; align-items: center; gap: 10px; height: 48px; padding: 4px 5px 4px 10px; border-radius: 999px; background: #131314; border: 1px solid rgba(255,255,255,0.10); cursor: pointer; box-shadow: 0 4px 18px rgba(0,0,0,0.5); transition: background .15s ease, border-color .15s ease; }
        #pakflow-home-ultra-pill:hover { background: #1b1b1d; border-color: rgba(255,255,255,0.20); }
        #pakflow-home-ultra-badge { display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; border-radius: 999px; background: #282a2c; color: #ffffff; font-size: 11.5px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; line-height: 1; }
        .pf-home-avatar-ring { width: 38px; height: 38px; border-radius: 50%; padding: 2.5px; background: linear-gradient(180deg, #c2d0ff 0%, #4c8df6 52%, #1b72e8 100%); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .pf-home-avatar-inner { width: 100%; height: 100%; border-radius: 50%; background: #8e24aa; color: #ffffff !important; font-size: 14px !important; font-weight: 500 !important; line-height: 1 !important; display: flex !important; align-items: center; justify-content: center; overflow: hidden; border: 2px solid #131314; text-indent: 0 !important; letter-spacing: 0 !important; position: relative; }
        .pf-home-avatar-inner img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
        #pakflow-home-ultra-popover { position: fixed; top: 66px; right: 18px; width: 356px; max-width: calc(100vw - 28px); border-radius: 26px; background: #1e1f20; border: 1px solid rgba(255,255,255,0.10); box-shadow: 0 24px 60px rgba(0,0,0,0.82); padding: 18px 18px 16px; color: #e3e3e3; z-index: 2147483641; }
        #pakflow-home-ultra-popover[hidden] { display: none !important; }
        .pf-hp-top { position: relative; display: flex; align-items: center; justify-content: center; min-height: 36px; margin-bottom: 10px; }
        .pf-hp-google { font-size: 19px; font-weight: 600; color: #ffffff; letter-spacing: -0.01em; }
        .pf-hp-close { position: absolute; right: 0; top: 0; width: 36px; height: 36px; border-radius: 50%; border: 0; background: #2b2c2f; color: #c4c7c5; font-size: 20px; line-height: 1; display: flex; align-items: center; justify-content: center; cursor: pointer; }
        .pf-hp-close:hover { background: #37393d; color: #ffffff; }
        .pf-hp-user-row { display: flex; align-items: center; gap: 12px; padding: 4px 2px 12px; }
        .pf-hp-user-meta { min-width: 0; flex: 1; }
        .pf-hp-user-name { font-size: 14px; font-weight: 600; color: #ffffff; line-height: 1.3; }
        .pf-hp-user-email { font-size: 12px; color: #9aa0a6; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
        .pf-hp-credits-box { background: #282a2c; border-radius: 18px; padding: 14px 16px; }
        .pf-hp-credits-line { display: flex; align-items: center; gap: 10px; font-size: 14.5px; font-weight: 500; color: #e3e3e3; }
        .pf-hp-credits-txt { text-decoration: underline; text-underline-offset: 3px; }
        .pf-hp-plan-pill { margin-top: 12px; width: 100%; padding: 10px 16px; border-radius: 999px; border: 0; background: #d3dbe5; color: #1f1f1f; font-size: 13px; font-weight: 600; text-align: center; cursor: default; }
        .pf-hp-manage-btn { margin-top: 10px; display: block; width: 100%; padding: 11px 16px; border-radius: 999px; border: 1px solid rgba(255,255,255,0.14); background: #161719; color: #e3e3e3; font-size: 13px; font-weight: 600; text-align: center; text-decoration: none; cursor: pointer; transition: background .15s ease; box-sizing: border-box; }
        .pf-hp-manage-btn:hover { background: #222427; }
        .pf-hp-foot { margin-top: 14px; text-align: center; font-size: 11px; color: #9aa0a6; line-height: 1.5; }
      `;
      (document.head || document.documentElement).appendChild(st);
    }

    el = document.createElement('div');
    el.id = 'pakflow-home-ultra-root';
    el.innerHTML = `
      <button type="button" id="pakflow-home-ultra-pill" aria-haspopup="dialog" aria-expanded="false" title="Google Account: PakFlow">
        <span id="pakflow-home-ultra-badge">ULTRA</span>
        <span class="pf-home-avatar-ring"><span class="pf-home-avatar-inner" id="pakflow-home-avatar-slot">PF</span></span>
      </button>
      <div id="pakflow-home-ultra-popover" role="dialog" aria-label="Account & Google Flow credits" hidden>
        <div class="pf-hp-top">
          <span class="pf-hp-google">Google</span>
          <button type="button" class="pf-hp-close" id="pakflow-home-ultra-close" aria-label="Close">&times;</button>
        </div>
        <div class="pf-hp-user-row">
          <span class="pf-home-avatar-ring" style="width:38px;height:38px;"><span class="pf-home-avatar-inner" id="pakflow-home-pop-avatar">PF</span></span>
          <div class="pf-hp-user-meta">
            <div class="pf-hp-user-name">PakFlow</div>
            <div class="pf-hp-user-email" id="pakflow-home-pop-email">user@pakflow.com</div>
          </div>
        </div>
        <div class="pf-hp-credits-box">
          <div class="pf-hp-credits-line">
            <span aria-hidden="true">🎬</span>
            <span class="pf-hp-credits-txt" id="pakflow-home-pop-credits">45000 Google Flow credits</span>
          </div>
          <div class="pf-hp-plan-pill" id="pakflow-home-pop-plan">Google Ultra Plan 20x</div>
        </div>
        <a class="pf-hp-manage-btn" id="pakflow-home-manage-link" href="https://flowbypak.com/dashboard" target="_blank" rel="noopener noreferrer">Manage subscription</a>
        <div class="pf-hp-foot">Privacy &middot; Terms of Service &middot; Licenses</div>
      </div>
    `;
    document.body.appendChild(el);
    homeUltraRoot = el;

    const pillBtn = el.querySelector('#pakflow-home-ultra-pill');
    const popBox = el.querySelector('#pakflow-home-ultra-popover');
    const closeBtn = el.querySelector('#pakflow-home-ultra-close');

    const setMenuOpen = (openState) => {
      homeUltraMenuOpen = Boolean(openState);
      if (popBox) popBox.hidden = !homeUltraMenuOpen;
      if (pillBtn) pillBtn.setAttribute('aria-expanded', String(homeUltraMenuOpen));
      if (homeUltraMenuOpen) refreshHomeUltraAccountData();
    };

    pillBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      // Prefer triggering Google's genuine native profile popover on homepage (identical to in-project UI)
      try {
        const nativeAnchor = (cachedTopProfileEl && cachedTopProfileEl.isConnected)
          ? cachedTopProfileEl
          : findNativeHomeAvatarControl();
        const nativeBtn = nativeAnchor
          ? (nativeAnchor.matches('button, a, [role="button"]')
              ? nativeAnchor
              : (nativeAnchor.closest('button, a, [role="button"]') || nativeAnchor.querySelector('button, a, [role="button"]') || nativeAnchor))
          : null;
        if (nativeBtn && typeof nativeBtn.click === 'function') {
          setMenuOpen(false);
          const hadDialogBefore = Boolean(document.querySelector('[data-radix-popper-content-wrapper], .cdk-overlay-pane, [role="dialog"]:not(#pakflow-home-ultra-popover)'));
          nativeBtn.click();
          if (!hadDialogBefore) {
            setTimeout(() => {
              const hasNativeOpen = Boolean(document.querySelector('[data-radix-popper-content-wrapper], .cdk-overlay-pane, [role="dialog"]:not(#pakflow-home-ultra-popover)'));
              if (!hasNativeOpen) setMenuOpen(true);
            }, 180);
          }
          return;
        }
      } catch {}
      setMenuOpen(!homeUltraMenuOpen);
    });
    closeBtn?.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      setMenuOpen(false);
    });
    document.addEventListener('click', (e) => {
      if (homeUltraMenuOpen && homeUltraRoot && !homeUltraRoot.contains(e.target)) {
        setMenuOpen(false);
      }
    });
    document.addEventListener('keydown', (e) => {
      if (homeUltraMenuOpen && e.key === 'Escape') {
        setMenuOpen(false);
      }
    });

    refreshHomeUltraAccountData();
    return el;
  }

  function renderHomeUltraAccountDOM(navCtrlOpt) {
    if (!homeUltraRoot || !homeUltraRoot.isConnected) return;
    const navCtrl = navCtrlOpt !== undefined ? navCtrlOpt : findNativeHomeAvatarControl();
    const nativeAv = detectNativeGoogleAvatarInfo();
    const initials = computeAvatarInitials(homeAccountState.email, nativeAv.initials);
    const pillBtn = homeUltraRoot.querySelector('#pakflow-home-ultra-pill');
    const popBox = homeUltraRoot.querySelector('#pakflow-home-ultra-popover');
    const av1 = homeUltraRoot.querySelector('#pakflow-home-avatar-slot');
    const ring1 = av1 ? av1.parentElement : null;
    const av2 = homeUltraRoot.querySelector('#pakflow-home-pop-avatar');
    const emailEl = homeUltraRoot.querySelector('#pakflow-home-pop-email');
    const credEl = homeUltraRoot.querySelector('#pakflow-home-pop-credits');
    const planEl = homeUltraRoot.querySelector('#pakflow-home-pop-plan');
    const manageEl = homeUltraRoot.querySelector('#pakflow-home-manage-link');

    const renderAvatarInto = (slotEl) => {
      if (!slotEl) return;
      if (nativeAv.bgColor) {
        slotEl.style.backgroundColor = nativeAv.bgColor;
      }
      if (nativeAv.imgSrc) {
        const curImg = slotEl.querySelector('img');
        if (!curImg || curImg.dataset.pfRawSrc !== nativeAv.imgSrc) {
          slotEl.innerHTML = '';
          const im = document.createElement('img');
          im.dataset.pfRawSrc = nativeAv.imgSrc;
          im.src = nativeAv.imgSrc;
          im.alt = initials;
          im.addEventListener('error', () => {
            im.remove();
            slotEl.textContent = initials;
          }, { once: true });
          slotEl.appendChild(im);
        }
      } else if (slotEl.textContent !== initials || slotEl.querySelector('img')) {
        slotEl.textContent = initials;
      }
    };

    // Dock pill around Google's 100% genuine native top-right avatar with a circular cutout window so real photo/Ti NEVER flickers or turns into a solid color circle!
    if (navCtrl && navCtrl.el && navCtrl.visRect && pillBtn && ring1 && av1) {
      if (isUserLoggedIn && document.documentElement?.getAttribute('data-pakflow-flow-home') === 'true') {
        if (cachedTopProfileEl && cachedTopProfileEl !== navCtrl.el) {
          try { cachedTopProfileEl.removeAttribute('data-pakflow-top-profile'); } catch {}
        }
        if (navCtrl.el.getAttribute('data-pakflow-top-profile') !== 'true') {
          navCtrl.el.setAttribute('data-pakflow-top-profile', 'true');
        }
        cachedTopProfileEl = navCtrl.el;
      }
      const vr = navCtrl.visRect;
      const avW = Math.max(28, Math.min(46, Math.round(vr.width)));
      const avH = Math.max(28, Math.min(46, Math.round(vr.height)));
      const padY = 4;
      const padR = 5;
      const pillH = avH + padY * 2;
      const topPx = Math.max(4, Math.round(vr.top - padY));
      const rightPx = Math.max(6, Math.round(window.innerWidth - vr.right - padR));

      homeUltraRoot.style.top = topPx + 'px';
      homeUltraRoot.style.right = rightPx + 'px';
      pillBtn.style.height = pillH + 'px';
      ring1.style.width = avW + 'px';
      ring1.style.height = avH + 'px';
      ring1.style.padding = '0';
      ring1.style.background = 'transparent';
      av1.style.setProperty('display', 'none', 'important');

      const holeR = Math.max(12, (Math.min(avW, avH) / 2) - 0.4).toFixed(1);
      const holeCxFromRight = (padR + avW / 2).toFixed(1);
      const maskVal = 'radial-gradient(circle ' + holeR + 'px at calc(100% - ' + holeCxFromRight + 'px) 50%, transparent 97%, #000 100%)';
      pillBtn.style.webkitMaskImage = maskVal;
      pillBtn.style.maskImage = maskVal;

      if (popBox) {
        popBox.style.top = (topPx + pillH + 6) + 'px';
        popBox.style.right = rightPx + 'px';
      }
    } else if (pillBtn && ring1 && av1) {
      homeUltraRoot.style.top = '14px';
      homeUltraRoot.style.right = '18px';
      pillBtn.style.height = '48px';
      pillBtn.style.webkitMaskImage = '';
      pillBtn.style.maskImage = '';
      ring1.style.width = '';
      ring1.style.height = '';
      ring1.style.padding = '';
      ring1.style.background = '';
      av1.style.removeProperty('display');
      renderAvatarInto(av1);
      if (popBox) {
        popBox.style.top = '66px';
        popBox.style.right = '18px';
      }
    }

    renderAvatarInto(av2);

    if (pillBtn && homeAccountState.email) {
      pillBtn.title = 'Google Account: PakFlow (' + homeAccountState.email + ')';
    }
    if (emailEl) emailEl.textContent = homeAccountState.email || 'Connected Account';
    if (credEl) credEl.textContent = Math.max(0, Math.round(Number(homeAccountState.credits) || 0)) + ' Google Flow credits';
    if (planEl) {
      const pLow = String(homeAccountState.plan || 'ultra').toLowerCase();
      planEl.textContent = pLow === 'demo' ? 'Google Flow Demo Plan' : (pLow === 'pro' || pLow === 'plus') ? 'Google Ultra Plan (Pro)' : 'Google Ultra Plan 20x';
    }
    if (manageEl && homeAccountState.portalOrigin) {
      manageEl.href = String(homeAccountState.portalOrigin).replace(/\/+$/, '') + '/dashboard';
    }
  }

  function refreshHomeUltraAccountData() {
    try {
      if (!chrome?.runtime?.id || !chrome?.storage?.local) return;
      chrome.storage.local.get(['userEmail', 'user', 'pakflow_user', 'credits', 'fakeBalance', 'userPlan', 'pakflowWorkingOrigin', 'apiBase', 'connectedFromSite'], (st) => {
        void chrome.runtime?.lastError;
        if (!st) return;
        const em = String(st.userEmail || st.user?.email || st.pakflow_user?.email || '').trim();
        const rawCr = st.credits !== undefined && st.credits !== null && st.credits !== ''
          ? Number(st.credits)
          : (st.fakeBalance !== undefined && st.fakeBalance !== null ? Number(st.fakeBalance) : Number(st.user?.credits ?? 45000));
        homeAccountState.email = em;
        homeAccountState.credits = Number.isFinite(rawCr) ? Math.max(0, Math.round(rawCr)) : 45000;
        homeAccountState.plan = String(st.userPlan || st.user?.plan || 'ultra').toLowerCase();
        homeAccountState.portalOrigin = String(st.pakflowWorkingOrigin || st.apiBase || st.connectedFromSite || 'https://flowbypak.com').replace(/\/+$/, '');
        renderHomeUltraAccountDOM();
      });
    } catch {}
  }

  try {
    if (chrome?.storage?.onChanged) {
      chrome.storage.onChanged.addListener((changes, area) => {
        if (area === 'local' && (changes.credits || changes.fakeBalance || changes.userEmail || changes.user || changes.userPlan)) {
          refreshHomeUltraAccountData();
        }
      });
    }
  } catch {}

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
      if (homeUltraRoot) {
        homeUltraRoot.style.display = 'none';
        homeUltraMenuOpen = false;
        const pop = homeUltraRoot.querySelector('#pakflow-home-ultra-popover');
        if (pop) pop.hidden = true;
      }
      try { detectNativeGoogleAvatarInfo(); } catch {}
      return;
    }

    const realNewBtn = (cachedRealNewBtn && cachedRealNewBtn.isConnected && cachedRealNewBtn.getAttribute('data-pakflow-real-new-btn') === 'true')
      ? cachedRealNewBtn
      : findRealGoogleNewProjectButton();
    const hasProjLinks = Boolean(document.querySelector('a[href*="/project/"]'));

    // Do not cover Google's sign-in screen if genuinely signed out
    if (!realNewBtn && !hasProjLinks) {
      const hasSignInCta = Boolean(document.querySelector(
        'a[href*="accounts.google.com/ServiceLogin"], a[href*="accounts.google.com/v3/signin"], button[aria-label*="Sign in" i]'
      ));
      if (hasSignInCta) {
        if (document.documentElement) document.documentElement.removeAttribute('data-pakflow-flow-home');
        if (cachedTopProfileEl) {
          try { cachedTopProfileEl.removeAttribute('data-pakflow-top-profile'); } catch {}
          cachedTopProfileEl = null;
        }
        if (homeUltraRoot) homeUltraRoot.style.display = 'none';
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

    const navCtrl = isUserLoggedIn ? findNativeHomeAvatarControl() : null;
    if (navCtrl && navCtrl.el) {
      if (cachedTopProfileEl && cachedTopProfileEl !== navCtrl.el) {
        try { cachedTopProfileEl.removeAttribute('data-pakflow-top-profile'); } catch {}
      }
      if (navCtrl.el.getAttribute('data-pakflow-top-profile') !== 'true') {
        navCtrl.el.setAttribute('data-pakflow-top-profile', 'true');
      }
      cachedTopProfileEl = navCtrl.el;
    } else if (cachedTopProfileEl) {
      try { cachedTopProfileEl.removeAttribute('data-pakflow-top-profile'); } catch {}
      cachedTopProfileEl = null;
    }

    const ultraUi = ensureHomeUltraProfileUI();
    if (ultraUi) {
      ultraUi.style.display = isUserLoggedIn ? 'block' : 'none';
      renderHomeUltraAccountDOM(navCtrl);
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
    window.addEventListener('resize', scheduleFlowHomeOverlaySync);
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
