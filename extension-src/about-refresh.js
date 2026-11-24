/* Automatically exit "/about" ("Create with Google Flow") and open https://flow.google.com/
 * both on initial page load and on SPA route changes, syncing cookies if needed. */
(() => {
  'use strict';
  if (window.top !== window) return;

  const AUTO_KEY = 'pakflow_about_auto_enter';
  let handling = false;
  let cachedCanEnter = false;

  function computeCanEnter(state) {
    if (!state || typeof state !== 'object') return false;
    const loggedIn = state.isLoggedIn === true || state.isLoggedIn === 'true';
    const plan = String(state.userPlan || '').trim().toLowerCase();
    const detached = state.flowSessionDetached === true || state.flowSessionDetached === 'true';
    const locked = Boolean(state.pf_flow_lock_state && state.pf_flow_lock_state.locked);
    return loggedIn && !detached && !locked && !['basic', 'free', 'trial'].includes(plan);
  }

  function refreshCachedState(cb) {
    try {
      chrome.storage.local.get(['isLoggedIn', 'flowSessionDetached', 'userPlan', 'activeSlot', 'pf_flow_lock_state'], (s) => {
        void chrome.runtime?.lastError;
        cachedCanEnter = computeCanEnter(s || {});
        if (typeof cb === 'function') cb(s || {});
      });
    } catch {
      cachedCanEnter = false;
      if (typeof cb === 'function') cb({});
    }
  }

  function isAboutUrl() {
    const host = location.hostname.toLowerCase();
    if (host === 'flow.google.com') {
      return /^\/about(?:\/|$)/i.test(location.pathname);
    }
    if (/(?:^|\.)labs\.google$/i.test(host)) {
      return /^\/fx\/about(?:\/|$)/i.test(location.pathname);
    }
    return false;
  }

  function isLabsLandingUrl() {
    const host = location.hostname.toLowerCase();
    return /(?:^|\.)labs\.google$/i.test(host) && /^\/fx\/(?:about|tools\/flow)\/?$/i.test(location.pathname);
  }

  function canAutoRedirectNow() {
    try {
      const raw = sessionStorage.getItem(AUTO_KEY);
      const data = raw ? JSON.parse(raw) : { count: 0, ts: 0 };
      const now = Date.now();
      if (now - (data.ts || 0) > 15000) {
        sessionStorage.setItem(AUTO_KEY, JSON.stringify({ count: 1, ts: now }));
        return true;
      }
      if ((data.count || 0) < 2) {
        sessionStorage.setItem(AUTO_KEY, JSON.stringify({ count: (data.count || 0) + 1, ts: data.ts || now }));
        return true;
      }
      return false;
    } catch {
      return true;
    }
  }

  function sendMsg(payload) {
    return new Promise(resolve => {
      try {
        chrome.runtime.sendMessage(payload, resp => {
          void chrome.runtime?.lastError;
          resolve(resp || null);
        });
      } catch {
        resolve(null);
      }
    });
  }

  function getState() {
    return new Promise(resolve => {
      refreshCachedState(resolve);
    });
  }

  refreshCachedState();

  // Auto-enter Google Flow the instant the user connects / logs in on the portal while this tab is on /about
  try {
    if (chrome?.storage?.onChanged) {
      chrome.storage.onChanged.addListener((changes, area) => {
        if (area !== 'local') return;
        if (
          changes.isLoggedIn ||
          changes.flowSessionDetached ||
          changes.userToken ||
          changes.pakflow_token ||
          changes.flow_token ||
          changes.lastSyncTimestamp ||
          changes.activeSlot ||
          changes.pf_flow_lock_state
        ) {
          refreshCachedState(() => {
            if (cachedCanEnter && (isAboutUrl() || isLabsLandingUrl())) {
              try { sessionStorage.removeItem(AUTO_KEY); } catch {}
              tryAutoEnterStudio();
            }
          });
        }
      });
    }
  } catch {}

  async function tryAutoEnterStudio() {
    if (handling || !isAboutUrl()) return;
    handling = true;
    try {
      // 1. Manual reload on /about always retries root flow.google.com immediately
      try {
        const navigation = performance.getEntriesByType('navigation')[0];
        const reloaded = navigation ? navigation.type === 'reload' : performance.navigation?.type === 1;
        if (reloaded && canAutoRedirectNow()) {
          location.replace('https://flow.google.com/');
          return;
        }
      } catch {}

      // 2. Check session state (allow empty plan if not yet cached, block only explicit free/basic/trial)
      const state = await getState();
      const loggedIn = state.isLoggedIn === true || state.isLoggedIn === 'true';
      const plan = String(state.userPlan || '').trim().toLowerCase();
      if (!loggedIn || state.flowSessionDetached || ['basic', 'free', 'trial'].includes(plan)) {
        return;
      }

      const access = await sendMsg({ type: 'CHECK_FLOW_ACCESS' });
      if (access && access.allowed === true) {
        if (canAutoRedirectNow()) {
          location.replace('https://flow.google.com/');
        }
        return;
      }

      const syncRes = await sendMsg({ type: 'SYNC_COOKIES_NOW' });
      if ((syncRes?.success === true || syncRes?.status === 'synced') && canAutoRedirectNow()) {
        location.replace('https://flow.google.com/');
      }
    } finally {
      handling = false;
    }
  }

  // Run immediately if currently on /about
  if (isAboutUrl()) {
    tryAutoEnterStudio();
  }

  // Watch SPA navigation (pushState / replaceState / popstate) so /about is caught even after load
  try {
    const origPush = history.pushState;
    const origReplace = history.replaceState;
    if (typeof origPush === 'function') {
      history.pushState = function () {
        const res = origPush.apply(this, arguments);
        if (isAboutUrl()) tryAutoEnterStudio();
        return res;
      };
    }
    if (typeof origReplace === 'function') {
      history.replaceState = function () {
        const res = origReplace.apply(this, arguments);
        if (isAboutUrl()) tryAutoEnterStudio();
        return res;
      };
    }
    window.addEventListener('popstate', () => {
      if (isAboutUrl()) tryAutoEnterStudio();
    });
  } catch {}

  // Intercept clicks on "Create with Google Flow" / "Create with Flow" on /about or labs.google
  // MUST call preventDefault / stopImmediatePropagation synchronously so flow-create-switch.js cannot hijack the server!
  document.addEventListener('click', event => {
    if (!isAboutUrl() && !isLabsLandingUrl()) return;
    if (!cachedCanEnter) return;
    const btn = event.target && event.target.closest ? event.target.closest('a,button,[role="button"]') : null;
    if (!btn) return;
    const label = String(btn.textContent || btn.getAttribute('aria-label') || '').replace(/\s+/g, ' ').trim();
    if (!/create with (?:google )?flow|try (?:google )?flow|launch flow|open flow/i.test(label)) return;

    event.preventDefault();
    event.stopPropagation();
    if (typeof event.stopImmediatePropagation === 'function') event.stopImmediatePropagation();

    (async () => {
      try {
        await sendMsg({ type: 'SYNC_COOKIES_NOW' });
      } catch {}
      location.replace('https://flow.google.com/');
    })();
  }, true);
})();