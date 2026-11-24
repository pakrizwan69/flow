/* PakFlow Private Flow Workspace — replaces the shared Google Flow homepage (/)
 * with an isolated, private per-user workspace showing ONLY this user's own saved
 * projects + a working "+ New Project" button, so other shared users' projects
 * are NEVER visible or clickable. Automatically hides inside /project/<id>. */
(() => {
  'use strict';
  if (window.top !== window) return;
  if (globalThis.__pfPrivateHomeMounted) return;
  globalThis.__pfPrivateHomeMounted = true;

  const ROOT_ID = 'pakflow-private-home';
  const STYLE_ID = 'pakflow-private-home-style';
  const ACTIVE_ATTR = 'data-pakflow-private-home';

  let active = false;
  let creating = false;
  let searchQuery = '';
  let stateCache = {
    isLoggedIn: false,
    userEmail: '',
    userPlan: '',
    credits: null,
    activeSlot: 'slot_1',
    projects: [],
    locked: false
  };
  let lastRenderSig = '';
  let renderScheduled = false;

  function isFlowHomePath() {
    const host = String(location.hostname || '').toLowerCase();
    const p = String(location.pathname || '/');
    if (host === 'flow.google.com') {
      return p === '/' || p === '' || /^\/home\/?$/i.test(p);
    }
    if (/(?:^|\.)labs\.google$/i.test(host)) {
      return /^\/fx\/tools\/flow\/?$/i.test(p);
    }
    return false;
  }

  function isProjectEditorPath() {
    return /\/project\/([^/?#]+)/i.test(String(location.pathname || ''));
  }

  function normalizeSlot(slot) {
    const s = String(slot || '').trim();
    return s && s !== 'active' ? s : 'slot_1';
  }

  function slotNumberOf(slot) {
    const n = parseInt(String(slot || '').replace(/\D+/g, ''), 10);
    return Number.isFinite(n) && n > 0 ? n : 1;
  }

  function mergeUserProjects(unified, byIp) {
    const map = new Map();
    const ingest = (arr) => {
      if (!Array.isArray(arr)) return;
      for (const raw of arr) {
        if (!raw || typeof raw !== 'object' || !raw.id) continue;
        const id = String(raw.id).trim().toLowerCase();
        if (!id) continue;
        const item = {
          ...raw,
          id,
          slot: normalizeSlot(raw.slot),
          url: raw.url || ('https://flow.google.com/project/' + id),
          createdAt: Number(raw.createdAt || raw.updatedAt || Date.now()),
          updatedAt: Number(raw.updatedAt || raw.createdAt || Date.now())
        };
        const prev = map.get(id);
        if (!prev) {
          map.set(id, item);
        } else {
          const newer = (item.updatedAt || 0) >= (prev.updatedAt || 0) ? item : prev;
          const older = newer === item ? prev : item;
          map.set(id, {
            ...older,
            ...newer,
            thumbnail: newer.thumbnail || older.thumbnail || newer.thumbnailUrl || older.thumbnailUrl || '',
            title: prev.customTitle ? prev.title : (item.customTitle ? item.title : (newer.title || older.title)),
            customTitle: Boolean(prev.customTitle || item.customTitle),
            updatedAt: Math.max(prev.updatedAt || 0, item.updatedAt || 0)
          });
        }
      }
    };
    if (byIp && typeof byIp === 'object') {
      Object.values(byIp).forEach(ingest);
    }
    ingest(unified);
    return Array.from(map.values()).sort((a, b) => (b.updatedAt || b.createdAt || 0) - (a.updatedAt || a.createdAt || 0));
  }

  function ensureStyle() {
    if (document.getElementById(STYLE_ID)) return;
    const host = document.head || document.documentElement;
    if (!host) return;
    const st = document.createElement('style');
    st.id = STYLE_ID;
    st.textContent = `
      html[${ACTIVE_ATTR}="true"] {
        background: #070b14 !important;
        overflow: hidden !important;
      }
      html[${ACTIVE_ATTR}="true"] body > *:not(#${ROOT_ID}):not(#pakflow-access-lock-overlay):not(#pakflow-my-projects-root) {
        opacity: 0 !important;
        pointer-events: none !important;
      }
      #${ROOT_ID} {
        position: fixed !important;
        inset: 0 !important;
        z-index: 2147483500 !important;
        background: radial-gradient(ellipse at top, rgba(37,99,235,0.14), #070b14 55%) !important;
        color: #f8fafc !important;
        font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        box-sizing: border-box !important;
      }
      #${ROOT_ID} * { box-sizing: border-box; }
      #${ROOT_ID} .pfh-nav {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 14px 28px;
        border-bottom: 1px solid rgba(148,163,184,0.14);
        background: rgba(11,17,32,0.88);
        backdrop-filter: blur(12px);
        flex-shrink: 0;
        flex-wrap: wrap;
      }
      #${ROOT_ID} .pfh-brand {
        display: flex;
        align-items: center;
        gap: 11px;
        min-width: 0;
      }
      #${ROOT_ID} .pfh-logo {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 900;
        font-size: 16px;
        color: #fff;
        box-shadow: 0 6px 18px rgba(37,99,235,0.35);
        flex-shrink: 0;
      }
      #${ROOT_ID} .pfh-brand-title {
        font-size: 16px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #f8fafc;
        line-height: 1.2;
      }
      #${ROOT_ID} .pfh-brand-sub {
        font-size: 11px;
        color: #94a3b8;
        margin-top: 1px;
      }
      #${ROOT_ID} .pfh-right {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
      }
      #${ROOT_ID} .pfh-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        background: rgba(15,23,42,0.9);
        border: 1px solid rgba(148,163,184,0.22);
        font-size: 12px;
        font-weight: 700;
        color: #e2e8f0;
        white-space: nowrap;
      }
      #${ROOT_ID} .pfh-pill-blue {
        background: rgba(37,99,235,0.14);
        border-color: rgba(59,130,246,0.34);
        color: #93c5fd;
      }
      #${ROOT_ID} .pfh-pill-green {
        background: rgba(16,185,129,0.14);
        border-color: rgba(52,211,153,0.32);
        color: #6ee7b7;
      }
      #${ROOT_ID} .pfh-new-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px 20px;
        border-radius: 10px;
        border: 0;
        background: #2563eb;
        color: #ffffff;
        font-size: 13.5px;
        font-weight: 800;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(37,99,235,0.36);
        transition: background .15s ease, transform .12s ease;
        white-space: nowrap;
      }
      #${ROOT_ID} .pfh-new-btn:hover { background: #3b82f6; }
      #${ROOT_ID} .pfh-new-btn:active { transform: scale(0.98); }
      #${ROOT_ID} .pfh-new-btn:disabled { opacity: 0.7; cursor: wait; }
      #${ROOT_ID} .pfh-body {
        flex: 1;
        min-height: 0;
        overflow-y: auto;
        padding: 26px 28px 40px;
        scrollbar-width: thin;
        scrollbar-color: #334155 transparent;
      }
      #${ROOT_ID} .pfh-inner {
        max-width: 1240px;
        margin: 0 auto;
      }
      #${ROOT_ID} .pfh-head-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 20px;
        flex-wrap: wrap;
      }
      #${ROOT_ID} .pfh-h1 {
        margin: 0;
        font-size: 22px;
        font-weight: 800;
        letter-spacing: -0.02em;
        color: #f8fafc;
      }
      #${ROOT_ID} .pfh-sub {
        margin: 4px 0 0;
        font-size: 13px;
        color: #94a3b8;
      }
      #${ROOT_ID} .pfh-search {
        padding: 9px 14px;
        border-radius: 10px;
        border: 1px solid rgba(148,163,184,0.22);
        background: rgba(15,23,42,0.85);
        color: #f8fafc;
        font-size: 13px;
        min-width: 230px;
        outline: none;
      }
      #${ROOT_ID} .pfh-search:focus { border-color: #3b82f6; }
      #${ROOT_ID} .pfh-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
      }
      @media (max-width: 1120px) { #${ROOT_ID} .pfh-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
      @media (max-width: 780px) {
        #${ROOT_ID} .pfh-nav { padding: 12px 16px; }
        #${ROOT_ID} .pfh-body { padding: 18px 16px 32px; }
        #${ROOT_ID} .pfh-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
      }
      @media (max-width: 480px) { #${ROOT_ID} .pfh-grid { grid-template-columns: minmax(0, 1fr); } }
      #${ROOT_ID} .pfh-card {
        display: flex;
        flex-direction: column;
        background: #0f172a;
        border: 1px solid rgba(148,163,184,0.16);
        border-radius: 14px;
        overflow: hidden;
        position: relative;
        transition: border-color .15s ease, transform .15s ease;
      }
      #${ROOT_ID} .pfh-card:hover {
        border-color: rgba(59,130,246,0.48);
        transform: translateY(-2px);
      }
      #${ROOT_ID} .pfh-thumb {
        height: 152px;
        position: relative;
        background: linear-gradient(135deg, #0b1224, #1e293b);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        gap: 8px;
        color: #93c5fd;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        overflow: hidden;
      }
      #${ROOT_ID} .pfh-thumb img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
      }
      #${ROOT_ID} .pfh-play {
        position: relative;
        z-index: 2;
        width: 42px;
        height: 42px;
        border-radius: 999px;
        background: rgba(15,23,42,0.72);
        border: 1px solid rgba(148,163,184,0.34);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #fff;
        backdrop-filter: blur(4px);
      }
      #${ROOT_ID} .pfh-del {
        position: absolute;
        top: 8px;
        right: 8px;
        z-index: 3;
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid rgba(148,163,184,0.28);
        background: rgba(15,23,42,0.88);
        color: #fca5a5;
        font-size: 16px;
        line-height: 1;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      #${ROOT_ID} .pfh-del:hover { background: rgba(239,68,68,0.22); border-color: rgba(239,68,68,0.5); }
      #${ROOT_ID} .pfh-info {
        padding: 13px 14px 10px;
        min-width: 0;
      }
      #${ROOT_ID} .pfh-title {
        font-size: 13.5px;
        font-weight: 700;
        color: #f8fafc;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      #${ROOT_ID} .pfh-meta {
        font-size: 11.5px;
        color: #94a3b8;
        margin-top: 5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
      #${ROOT_ID} .pfh-actions {
        padding: 0 14px 14px;
        display: flex;
        gap: 8px;
      }
      #${ROOT_ID} .pfh-open {
        flex: 1;
        padding: 9px 12px;
        border-radius: 9px;
        border: 0;
        background: #2563eb;
        color: #fff;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
      }
      #${ROOT_ID} .pfh-open:hover { background: #3b82f6; }
      #${ROOT_ID} .pfh-open:disabled { opacity: 0.7; cursor: wait; }
      #${ROOT_ID} .pfh-empty {
        max-width: 480px;
        margin: 64px auto 0;
        padding: 36px 28px;
        border-radius: 18px;
        background: rgba(15,23,42,0.88);
        border: 1px solid rgba(148,163,184,0.18);
        text-align: center;
        box-shadow: 0 24px 64px rgba(0,0,0,0.45);
      }
      #${ROOT_ID} .pfh-empty-icon {
        width: 56px;
        height: 56px;
        margin: 0 auto 14px;
        border-radius: 14px;
        background: rgba(37,99,235,0.14);
        border: 1px solid rgba(59,130,246,0.32);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 26px;
      }
      #${ROOT_ID} .pfh-empty h2 {
        margin: 0 0 8px;
        font-size: 20px;
        font-weight: 800;
        color: #f8fafc;
      }
      #${ROOT_ID} .pfh-empty p {
        margin: 0 0 22px;
        font-size: 13.5px;
        line-height: 1.6;
        color: #94a3b8;
      }
      #${ROOT_ID} .pfh-status {
        margin-top: 12px;
        font-size: 12px;
        color: #93c5fd;
        min-height: 16px;
      }
      /* Keep My Saved Projects button hidden while on Private Home (already shown full-screen here!) */
      html[${ACTIVE_ATTR}="true"] #pakflow-my-projects-root {
        display: none !important;
      }
    `;
    host.appendChild(st);
  }

  function findGoogleNewProjectButton() {
    const elevated = document.querySelector('button[data-elevate-btn="true"]');
    if (elevated && !elevated.closest('#' + ROOT_ID)) return elevated;
    const nodes = document.querySelectorAll('button, a, [role="button"]');
    for (const el of nodes) {
      if (el.closest('#' + ROOT_ID) || el.closest('#pakflow-my-projects-root') || el.closest('#pakflow-access-lock-overlay')) continue;
      const txt = String(el.innerText || el.textContent || '').replace(/\s+/g, ' ').trim();
      const aria = String(el.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim();
      if (txt.length <= 36 && /^(?:add\s*)?\+?\s*new\s+project$/i.test(txt)) return el;
      if (aria && /\bnew\s+project\b/i.test(aria)) return el;
    }
    for (const el of nodes) {
      if (el.closest('#' + ROOT_ID) || el.closest('#pakflow-my-projects-root') || el.closest('#pakflow-access-lock-overlay')) continue;
      const txt = String(el.textContent || '').replace(/\s+/g, ' ').trim();
      if (txt.length <= 40 && /\bnew\s+project\b/i.test(txt)) return el;
    }
    return null;
  }

  function fireRealClick(el) {
    try { el.focus(); } catch {}
    const init = { bubbles: true, cancelable: true, view: window, button: 0, buttons: 1 };
    for (const type of ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click']) {
      try {
        const Ctor = type.startsWith('pointer') && typeof PointerEvent === 'function' ? PointerEvent : MouseEvent;
        el.dispatchEvent(new Ctor(type, init));
      } catch {
        try { el.dispatchEvent(new MouseEvent(type, init)); } catch {}
      }
    }
    try {
      if (typeof el.click === 'function') el.click();
    } catch {}
  }

  function setStatusMsg(msg) {
    const el = document.getElementById('pfh-status-msg');
    if (el) el.textContent = msg || '';
  }

  async function handleCreateNewProject(triggerBtn) {
    if (creating) return;
    creating = true;
    const prevTxt = triggerBtn ? triggerBtn.textContent : '';
    if (triggerBtn) {
      triggerBtn.disabled = true;
      triggerBtn.textContent = 'Creating Project...';
    }
    setStatusMsg('Opening a brand-new private Google Flow project...');

    try {
      // Wait up to 4.5s for Google Flow's real "+ New project" button to mount
      let btn = null;
      for (let i = 0; i < 45; i++) {
        btn = findGoogleNewProjectButton();
        if (btn) break;
        await new Promise((r) => setTimeout(r, 100));
      }

      if (btn) {
        // Temporarily ensure pointer-events work on the underlying button while clicking
        const prevPE = btn.style.pointerEvents;
        btn.style.pointerEvents = 'auto';
        fireRealClick(btn);
        btn.style.pointerEvents = prevPE;

        // Wait up to 5s for SPA navigation to /project/<id>
        for (let w = 0; w < 50; w++) {
          if (isProjectEditorPath()) {
            syncMode();
            return;
          }
          await new Promise((r) => setTimeout(r, 100));
        }
        // Retry click once if SPA didn't navigate yet
        const btn2 = findGoogleNewProjectButton();
        if (btn2) {
          btn2.style.pointerEvents = 'auto';
          fireRealClick(btn2);
        }
      } else {
        setStatusMsg('Syncing your active Flow session...');
        try {
          await new Promise((resolve) => {
            chrome.runtime.sendMessage({ type: 'SYNC_COOKIES_NOW' }, () => {
              void chrome.runtime?.lastError;
              resolve();
            });
          });
        } catch {}
        window.location.reload();
      }
    } finally {
      creating = false;
      if (triggerBtn && document.body?.contains(triggerBtn)) {
        triggerBtn.disabled = false;
        triggerBtn.textContent = prevTxt || '+ New Project';
      }
    }
  }

  function openProject(project, btnEl) {
    if (!project || !project.id) return;
    const targetSlot = normalizeSlot(project.slot);
    const targetNum = slotNumberOf(targetSlot);
    const activeSlot = normalizeSlot(stateCache.activeSlot);
    const targetUrl = project.url || ('https://flow.google.com/project/' + project.id);

    if (targetSlot === activeSlot) {
      window.location.href = targetUrl;
      return;
    }

    if (btnEl) {
      btnEl.disabled = true;
      btnEl.textContent = 'Switching to S' + targetNum + '...';
    }
    try {
      chrome.runtime.sendMessage(
        { type: 'PAKFLOW_REINJECT_CURRENT', slotNumber: targetNum, openFlow: false },
        () => {
          void chrome.runtime?.lastError;
          window.location.href = targetUrl;
        }
      );
    } catch {
      window.location.href = targetUrl;
    }
  }

  function removeProject(projId) {
    const cleanId = String(projId || '').toLowerCase();
    if (!cleanId) return;
    try {
      chrome.storage.local.get(['myProjectsUnified', 'myProjectsByIp'], (st) => {
        void chrome.runtime?.lastError;
        const nextUni = Array.isArray(st?.myProjectsUnified)
          ? st.myProjectsUnified.filter((p) => String(p?.id || '').toLowerCase() !== cleanId)
          : [];
        const nextByIp = st?.myProjectsByIp && typeof st.myProjectsByIp === 'object' ? { ...st.myProjectsByIp } : {};
        for (const k of Object.keys(nextByIp)) {
          if (Array.isArray(nextByIp[k])) {
            nextByIp[k] = nextByIp[k].filter((p) => String(p?.id || '').toLowerCase() !== cleanId);
          }
        }
        chrome.storage.local.set({ myProjectsUnified: nextUni, myProjectsByIp: nextByIp }, () => {
          void chrome.runtime?.lastError;
          loadStateAndRender(true);
        });
      });
    } catch {}
  }

  function ensureCurrentProjectSaved() {
    const m = String(location.pathname || '').match(/\/project\/([^/?#]+)/i);
    if (!m || !m[1]) return;
    const projId = String(m[1]).trim().toLowerCase();
    if (!projId || projId.length < 4) return;
    try {
      chrome.storage.local.get(['myProjectsUnified', 'myProjectsByIp', 'activeSlot', 'sessionLoopIndex'], (st) => {
        void chrome.runtime?.lastError;
        if (!st) return;
        const activeSlot = typeof st.activeSlot === 'string' && st.activeSlot.trim() && st.activeSlot !== 'active'
          ? normalizeSlot(st.activeSlot)
          : 'slot_' + ((Number.isInteger(st.sessionLoopIndex) && st.sessionLoopIndex >= 0 ? st.sessionLoopIndex : 0) + 1);
        const all = mergeUserProjects(st.myProjectsUnified, st.myProjectsByIp);
        if (all.some((p) => String(p.id || '').toLowerCase() === projId)) return;
        const now = Date.now();
        const entry = {
          id: projId,
          title: 'Google Flow - ' + new Date(now).toLocaleString([], { month: 'short', day: '2-digit', hour: '2-digit', minute: '2-digit' }),
          customTitle: false,
          slot: activeSlot,
          url: 'https://flow.google.com/project/' + projId,
          createdAt: now,
          updatedAt: now
        };
        const nextUni = [entry, ...all];
        chrome.storage.local.set({ myProjectsUnified: nextUni }, () => { void chrome.runtime?.lastError; });
      });
    } catch {}
  }

  function renderUI(force) {
    renderScheduled = false;
    if (!active) {
      const ex = document.getElementById(ROOT_ID);
      if (ex) ex.remove();
      if (document.documentElement) document.documentElement.removeAttribute(ACTIVE_ATTR);
      lastRenderSig = '';
      return;
    }

    ensureStyle();
    if (document.documentElement) {
      document.documentElement.setAttribute(ACTIVE_ATTR, 'true');
    }

    const host = document.body || document.documentElement;
    if (!host) {
      setTimeout(() => renderUI(true), 60);
      return;
    }

    const q = searchQuery.trim().toLowerCase();
    const activeSlot = normalizeSlot(stateCache.activeSlot);
    const activeNum = slotNumberOf(activeSlot);
    const filtered = stateCache.projects.filter((p) => {
      if (!q) return true;
      return String(p.title || '').toLowerCase().includes(q) || String(p.id || '').toLowerCase().includes(q);
    });

    const sig = JSON.stringify({
      email: stateCache.userEmail,
      credits: stateCache.credits,
      slot: activeSlot,
      q,
      list: filtered.map((p) => `${p.id}|${p.title || ''}|${p.slot || ''}|${p.thumbnail || ''}|${p.updatedAt || 0}`)
    });
    let root = document.getElementById(ROOT_ID);
    if (!force && root && sig === lastRenderSig) return;
    lastRenderSig = sig;

    if (!root) {
      root = document.createElement('div');
      root.id = ROOT_ID;
      host.appendChild(root);
    }

    const credNum = stateCache.credits !== null && Number.isFinite(Number(stateCache.credits))
      ? Math.max(0, Math.round(Number(stateCache.credits)))
      : null;

    const activeEl = document.activeElement;
    const wasSearchFocused = Boolean(activeEl && activeEl.classList && activeEl.classList.contains('pfh-search'));
    const selStart = wasSearchFocused ? (activeEl.selectionStart || 0) : 0;
    const selEnd = wasSearchFocused ? (activeEl.selectionEnd || 0) : 0;

    root.innerHTML = '';

    // ── Header ──
    const nav = document.createElement('header');
    nav.className = 'pfh-nav';

    const brand = document.createElement('div');
    brand.className = 'pfh-brand';
    const logo = document.createElement('div');
    logo.className = 'pfh-logo';
    logo.textContent = 'P';
    const bText = document.createElement('div');
    const bTitle = document.createElement('div');
    bTitle.className = 'pfh-brand-title';
    bTitle.textContent = 'PakFlow Studio';
    const bSub = document.createElement('div');
    bSub.className = 'pfh-brand-sub';
    bSub.textContent = 'Private Workspace · Only Your Projects';
    bText.append(bTitle, bSub);
    brand.append(logo, bText);

    const right = document.createElement('div');
    right.className = 'pfh-right';

    const sessPill = document.createElement('span');
    sessPill.className = 'pfh-pill pfh-pill-blue';
    sessPill.textContent = 'Session ' + activeNum;
    right.appendChild(sessPill);

    if (credNum !== null) {
      const credPill = document.createElement('span');
      credPill.className = 'pfh-pill pfh-pill-green';
      credPill.textContent = '⚡ ' + credNum.toLocaleString() + ' Credits';
      right.appendChild(credPill);
    }

    if (stateCache.userEmail) {
      const emPill = document.createElement('span');
      emPill.className = 'pfh-pill';
      emPill.textContent = stateCache.userEmail;
      right.appendChild(emPill);
    }

    const newBtn = document.createElement('button');
    newBtn.type = 'button';
    newBtn.className = 'pfh-new-btn';
    newBtn.textContent = '+ New Project';
    newBtn.addEventListener('click', () => handleCreateNewProject(newBtn));
    right.appendChild(newBtn);

    nav.append(brand, right);

    // ── Main Content ──
    const body = document.createElement('main');
    body.className = 'pfh-body';
    const inner = document.createElement('div');
    inner.className = 'pfh-inner';

    if (stateCache.projects.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'pfh-empty';
      const eIcon = document.createElement('div');
      eIcon.className = 'pfh-empty-icon';
      eIcon.textContent = '🎬';
      const eH2 = document.createElement('h2');
      eH2.textContent = 'Your Private Flow Workspace';
      const eP = document.createElement('p');
      eP.textContent = 'Every project you create is saved privately to your PakFlow account. Start a new AI video or image project below.';
      const eBtn = document.createElement('button');
      eBtn.type = 'button';
      eBtn.className = 'pfh-new-btn';
      eBtn.textContent = '+ Create New Project';
      eBtn.addEventListener('click', () => handleCreateNewProject(eBtn));
      const eSt = document.createElement('div');
      eSt.id = 'pfh-status-msg';
      eSt.className = 'pfh-status';
      empty.append(eIcon, eH2, eP, eBtn, eSt);
      inner.appendChild(empty);
    } else {
      const headRow = document.createElement('div');
      headRow.className = 'pfh-head-row';
      const headLeft = document.createElement('div');
      const h1 = document.createElement('h1');
      h1.className = 'pfh-h1';
      h1.textContent = 'My Projects (' + stateCache.projects.length + ')';
      const sub = document.createElement('p');
      sub.className = 'pfh-sub';
      sub.id = 'pfh-status-msg';
      sub.textContent = 'Only your own saved Flow projects are shown here.';
      headLeft.append(h1, sub);

      const search = document.createElement('input');
      search.type = 'search';
      search.className = 'pfh-search';
      search.placeholder = 'Search your projects...';
      search.value = searchQuery;
      search.addEventListener('input', (ev) => {
        searchQuery = ev.target.value || '';
        renderUI(true);
      });

      headRow.append(headLeft, search);
      inner.appendChild(headRow);

      const grid = document.createElement('div');
      grid.className = 'pfh-grid';

      if (filtered.length === 0) {
        const noRes = document.createElement('div');
        noRes.style.cssText = 'grid-column:1/-1;padding:36px;text-align:center;color:#94a3b8;font-size:13.5px;';
        noRes.textContent = 'No projects match "' + searchQuery + '".';
        grid.appendChild(noRes);
      } else {
        for (const p of filtered) {
          const card = document.createElement('div');
          card.className = 'pfh-card';

          const thumb = document.createElement('div');
          thumb.className = 'pfh-thumb';
          const play = document.createElement('div');
          play.className = 'pfh-play';
          play.textContent = '▶';
          thumb.appendChild(play);

          const thumbSrc = p.thumbnail || p.thumbnailUrl || p.imageUrl || '';
          if (typeof thumbSrc === 'string' && /^(?:https:\/\/|data:image\/)/i.test(thumbSrc)) {
            const img = document.createElement('img');
            img.alt = String(p.title || 'Flow Project');
            img.loading = 'lazy';
            if (/^https:\/\//i.test(thumbSrc)) img.referrerPolicy = 'no-referrer';
            img.addEventListener('error', () => img.remove(), { once: true });
            img.src = thumbSrc;
            thumb.prepend(img);
          }

          const delBtn = document.createElement('button');
          delBtn.type = 'button';
          delBtn.className = 'pfh-del';
          delBtn.title = 'Remove from My Projects';
          delBtn.textContent = '×';
          delBtn.addEventListener('click', (ev) => {
            ev.stopPropagation();
            removeProject(p.id);
          });
          card.appendChild(delBtn);

          const info = document.createElement('div');
          info.className = 'pfh-info';
          const title = document.createElement('div');
          title.className = 'pfh-title';
          title.textContent = String(p.title || 'Flow Project');
          title.title = title.textContent;
          const meta = document.createElement('div');
          meta.className = 'pfh-meta';
          const pSlotNum = slotNumberOf(p.slot);
          const dt = new Date(p.updatedAt || p.createdAt || Date.now());
          const dtStr = Number.isFinite(dt.getTime()) ? dt.toLocaleString() : 'Saved';
          meta.textContent = 'Session ' + pSlotNum + ' · ' + dtStr;
          info.append(title, meta);

          const actions = document.createElement('div');
          actions.className = 'pfh-actions';
          const openBtn = document.createElement('button');
          openBtn.type = 'button';
          openBtn.className = 'pfh-open';
          const diffSlot = normalizeSlot(p.slot) !== activeSlot;
          openBtn.textContent = diffSlot ? ('▶ Switch S' + pSlotNum + ' & Open') : '▶ Open Project';
          openBtn.addEventListener('click', () => openProject(p, openBtn));
          thumb.addEventListener('click', () => openProject(p, openBtn));
          actions.appendChild(openBtn);

          card.append(thumb, info, actions);
          grid.appendChild(card);
        }
      }
      inner.appendChild(grid);
    }

    body.appendChild(inner);
    root.append(nav, body);

    if (wasSearchFocused) {
      const sInput = root.querySelector('.pfh-search');
      if (sInput) {
        sInput.focus();
        try { sInput.setSelectionRange(selStart, selEnd); } catch {}
      }
    }
  }

  function scheduleRender(force) {
    if (force) lastRenderSig = '';
    if (renderScheduled) return;
    renderScheduled = true;
    requestAnimationFrame(() => renderUI(Boolean(force)));
  }

  function loadStateAndRender(force) {
    try {
      if (!chrome?.runtime?.id || !chrome?.storage?.local) return;
      chrome.storage.local.get([
        'isLoggedIn', 'userEmail', 'userPlan', 'credits', 'user', 'pakflow_user',
        'activeSlot', 'sessionLoopIndex', 'myProjectsUnified', 'myProjectsByIp', 'pf_flow_lock_state'
      ], (st) => {
        void chrome.runtime?.lastError;
        if (!st) return;
        const u = st.user || st.pakflow_user || {};
        stateCache.isLoggedIn = st.isLoggedIn === true || st.isLoggedIn === 'true';
        stateCache.userEmail = String(st.userEmail || u.email || '').trim();
        stateCache.userPlan = String(st.userPlan || u.plan || '').trim();
        const rawCred = st.credits !== undefined && st.credits !== null && st.credits !== ''
          ? st.credits
          : (u.credits !== undefined && u.credits !== null ? u.credits : null);
        stateCache.credits = rawCred !== null && Number.isFinite(Number(rawCred)) ? Number(rawCred) : null;
        stateCache.activeSlot = typeof st.activeSlot === 'string' && st.activeSlot.trim() && st.activeSlot !== 'active'
          ? normalizeSlot(st.activeSlot)
          : 'slot_' + ((Number.isInteger(st.sessionLoopIndex) && st.sessionLoopIndex >= 0 ? st.sessionLoopIndex : 0) + 1);
        stateCache.projects = mergeUserProjects(st.myProjectsUnified, st.myProjectsByIp);
        stateCache.locked = Boolean(st.pf_flow_lock_state && st.pf_flow_lock_state.locked);
        scheduleRender(force);
      });
    } catch {}
  }

  function syncMode() {
    const shouldBeActive = isFlowHomePath();
    if (isProjectEditorPath()) {
      ensureCurrentProjectSaved();
    }
    if (shouldBeActive !== active) {
      active = shouldBeActive;
      if (active) {
        ensureStyle();
        if (document.documentElement) document.documentElement.setAttribute(ACTIVE_ATTR, 'true');
        loadStateAndRender(true);
      } else {
        scheduleRender(true);
      }
    } else if (active && !document.getElementById(ROOT_ID) && (document.body || document.documentElement)) {
      scheduleRender(true);
    }
  }

  // Activate immediately at document_start before shared projects can paint!
  syncMode();

  // Watch SPA route transitions (Google Flow is a client-side SPA)
  try {
    const origPush = history.pushState;
    const origReplace = history.replaceState;
    if (typeof origPush === 'function') {
      history.pushState = function () {
        const res = origPush.apply(this, arguments);
        setTimeout(syncMode, 0);
        return res;
      };
    }
    if (typeof origReplace === 'function') {
      history.replaceState = function () {
        const res = origReplace.apply(this, arguments);
        setTimeout(syncMode, 0);
        return res;
      };
    }
    window.addEventListener('popstate', () => setTimeout(syncMode, 0));
  } catch {}

  // Live updates when projects, thumbnails, credits, or active slot change
  try {
    if (chrome?.storage?.onChanged) {
      chrome.storage.onChanged.addListener((changes, area) => {
        if (area !== 'local') return;
        if (
          changes.myProjectsUnified ||
          changes.myProjectsByIp ||
          changes.activeSlot ||
          changes.sessionLoopIndex ||
          changes.credits ||
          changes.userEmail ||
          changes.user ||
          changes.pf_flow_lock_state
        ) {
          loadStateAndRender(false);
        }
      });
    }
  } catch {}

  // Keep overlay mounted if Google Flow re-renders <body>
  try {
    const obs = new MutationObserver(() => {
      syncMode();
    });
    const startObs = () => {
      if (document.documentElement) {
        obs.observe(document.documentElement, { childList: true });
      }
      if (document.body) {
        obs.observe(document.body, { childList: true });
      }
    };
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', () => { startObs(); syncMode(); }, { once: true });
    } else {
      startObs();
    }
  } catch {}

  // Lightweight fallback route watcher (catches any navigation missed by history wrappers)
  let lastKnownPath = location.pathname;
  setInterval(() => {
    if (location.pathname !== lastKnownPath) {
      lastKnownPath = location.pathname;
      syncMode();
    } else if (active && !document.getElementById(ROOT_ID)) {
      syncMode();
    }
  }, 400);
})();
