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
      if (typeof thumbnail === 'string' && /^(?:https:\/\/|data:image\/)/i.test(thumbnail)) {
        const img = document.createElement('img'); img.alt = String(project.title || 'Flow Video Project');
        img.loading = 'lazy';
        if (/^https:\/\//i.test(thumbnail)) img.referrerPolicy = 'no-referrer';
        img.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:cover;z-index:1;';
        img.addEventListener('error', () => img.remove(), { once: true });
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

  // ── Real Thumbnail Harvester for My Saved Projects History (zero extra network requests) ──
  let lastThumbHarvestAt = 0;
  function extractMediaThumbFromContainer(container) {
    if (!container || !container.querySelectorAll) return '';
    // 1. Direct media <img> (skip tiny icons / avatars)
    const imgs = container.querySelectorAll('img');
    for (const im of imgs) {
      if (im.closest('#pakflow-my-projects-root')) continue;
      const src = String(im.currentSrc || im.src || '').trim();
      if (!/^https:\/\//i.test(src)) continue;
      if (/googleusercontent\.com\/a\/|avatar|profile|favicon|logo/i.test(src)) continue;
      const r = im.getBoundingClientRect();
      if ((im.naturalWidth >= 100 || r.width >= 100) && (im.naturalHeight >= 60 || r.height >= 60)) {
        return src;
      }
    }
    // 2. <video poster="..."> or frame snapshot via canvas
    const vids = container.querySelectorAll('video');
    for (const v of vids) {
      if (v.closest('#pakflow-my-projects-root')) continue;
      const poster = String(v.poster || '').trim();
      if (/^https:\/\//i.test(poster)) return poster;
      try {
        if (v.readyState >= 2 && v.videoWidth >= 100 && v.videoHeight >= 60) {
          const w = 280;
          const h = Math.max(120, Math.round((v.videoHeight / v.videoWidth) * w));
          const canvas = document.createElement('canvas');
          canvas.width = w;
          canvas.height = h;
          const ctx = canvas.getContext('2d');
          if (ctx) {
            ctx.drawImage(v, 0, 0, w, h);
            const dataUrl = canvas.toDataURL('image/jpeg', 0.62);
            if (dataUrl && dataUrl.length > 200 && dataUrl.length < 65000) return dataUrl;
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
    const titleUpdates = new Map(); // normalized title -> thumbnail

    // Case A: Inside a specific project editor /project/<id>
    const mProj = location.pathname.match(/\/project\/([^/?#]+)/i);
    if (mProj && mProj[1]) {
      const pid = String(mProj[1]).toLowerCase();
      const thumb = extractMediaThumbFromContainer(document.body);
      if (thumb) updates.set(pid, thumb);
    } else {
      // Case B: Google Flow home/projects grid — match ONLY exact /project/<id> links already owned by this user
      const links = document.querySelectorAll('a[href*="/project/"]');
      for (const a of links) {
        if (a.closest('#pakflow-my-projects-root')) continue;
        const m = String(a.getAttribute('href') || '').match(/\/project\/([^/?#]+)/i);
        if (!m || !m[1]) continue;
        const thumb = extractMediaThumbFromContainer(a) || extractMediaThumbFromContainer(a.parentElement);
        if (thumb) updates.set(String(m[1]).toLowerCase(), thumb);
      }
    }

    if (updates.size === 0) return;

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
            const nextThumb = updates.get(pid) || '';
            if (nextThumb && p.thumbnail !== nextThumb) {
              changed = true;
              return { ...p, thumbnail: nextThumb };
            }
            return p;
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
            try { refreshProjects?.(); } catch {}
          });
        }
      });
    } catch {}
  }

  // ── Native Black Privacy Overlay Safeguard on Google Flow Homepage (/) ──
  // Uses a dedicated #pakflow-home-privacy-shield (so core_module.js cannot remove it) +
  // hides all shared /project/ cards on flow.google.com/ while keeping ONLY Google Flow's
  // genuine "+ New project" button and bottom-left "My Saved Projects" accessible.
  // Removed immediately inside /project/<id> and never shown on /about.
  function syncFlowHomePrivacyOverlay() {
    if (window.top !== window) return;
    const host = String(location.hostname || '').toLowerCase();
    const path = String(location.pathname || '/');
    const isHome = (host === 'flow.google.com' && (path === '/' || path === ''))
      || (/(?:^|\.)labs\.google$/i.test(host) && /^\/fx\/tools\/flow\/?$/i.test(path));
    const shield = $('pakflow-home-privacy-shield');
    const legacyOv = $('ct-blur-overlay');

    if (!isHome) {
      if (document.documentElement) document.documentElement.removeAttribute('data-pakflow-flow-home');
      if (shield) shield.remove();
      if (legacyOv) legacyOv.remove();
      return;
    }

    // Do not cover Google's sign-in page if genuinely signed out
    const hasSignInCta = Boolean(document.querySelector('a[href*="accounts.google.com/ServiceLogin"], a[href*="accounts.google.com/v3/signin"]'));
    if (hasSignInCta && !document.querySelector('a[href*="/project/"]')) {
      if (document.documentElement) document.documentElement.removeAttribute('data-pakflow-flow-home');
      if (shield) shield.remove();
      if (legacyOv) legacyOv.remove();
      return;
    }

    if (document.documentElement) {
      document.documentElement.setAttribute('data-pakflow-flow-home', 'true');
    }

    // Mount shield on document.body once ready (or documentElement at document_start to prevent any flash)
    const targetParent = document.body || document.documentElement;
    if (targetParent) {
      if (!shield) {
        const ov = document.createElement('div');
        ov.id = 'pakflow-home-privacy-shield';
        ov.setAttribute('aria-hidden', 'true');
        targetParent.appendChild(ov);
      } else if (document.body && shield.parentElement !== document.body) {
        document.body.appendChild(shield);
      }
    }

    // Mark & hide every shared account project card in the background grid
    const projLinks = document.querySelectorAll('a[href*="/project/"]');
    for (const link of projLinks) {
      if (link.closest('#pakflow-my-projects-root')) continue;
      if (link.getAttribute('data-pakflow-hidden-shared-card') !== 'true') {
        link.setAttribute('data-pakflow-hidden-shared-card', 'true');
      }
      // Also hide the immediate card container if it does NOT contain the "+ New project" button
      const card = link.parentElement;
      if (card && card !== document.body && card !== document.documentElement && !card.querySelector('[data-elevate-btn="true"], [data-pakflow-elevated-new-btn="true"]')) {
        const cardLinks = card.querySelectorAll('a[href*="/project/"]');
        if (cardLinks.length === 1 && card.getAttribute('data-pakflow-hidden-shared-card') !== 'true') {
          card.setAttribute('data-pakflow-hidden-shared-card', 'true');
        }
      }
    }

    // Elevate Google Flow's genuine "+ New project" button cleanly above the black shield
    const buttons = document.querySelectorAll('button, [role="button"]');
    for (const btn of buttons) {
      if (btn.closest('#pakflow-my-projects-root') || btn.closest('#pakflow-access-lock-overlay')) continue;
      const txt = String(btn.innerText || btn.textContent || '').replace(/\s+/g, ' ').trim();
      if (txt.length <= 36 && /^(?:add\s*)?\+?\s*new\s+project$/i.test(txt)) {
        if (btn.getAttribute('data-elevate-btn') !== 'true') {
          btn.setAttribute('data-elevate-btn', 'true');
        }
        if (btn.getAttribute('data-pakflow-elevated-new-btn') !== 'true') {
          btn.setAttribute('data-pakflow-elevated-new-btn', 'true');
        }
        // Lift any ancestor stacking context (z-index / transform / filter / isolation) so "+ New project" sits on top of the black shield
        let anc = btn.parentElement;
        for (let d = 0; d < 10 && anc && anc !== document.body && anc !== document.documentElement; d++) {
          const cs = getComputedStyle(anc);
          if (cs) {
            const createsStack = (cs.position !== 'static' && cs.zIndex !== 'auto')
              || (cs.transform && cs.transform !== 'none')
              || (cs.filter && cs.filter !== 'none')
              || (cs.backdropFilter && cs.backdropFilter !== 'none')
              || cs.isolation === 'isolate';
            if (createsStack) {
              const zi = parseInt(cs.zIndex, 10);
              if (!Number.isFinite(zi) || zi < 2147483200) {
                if (cs.position === 'static') anc.style.setProperty('position', 'relative', 'important');
                anc.style.setProperty('z-index', '2147483200', 'important');
              }
            }
          }
          anc = anc.parentElement;
        }
      }
    }
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

  new MutationObserver(() => { schedule(); checkProjectNotFoundRecovery(); syncFlowHomePrivacyOverlay(); }).observe(document, { childList: true, characterData: true, subtree: true });
  setInterval(() => { checkProjectNotFoundRecovery(); harvestProjectThumbnails(); syncFlowHomePrivacyOverlay(); }, 1500);
  syncFlowHomePrivacyOverlay();
  schedule();
})();
