/* PakFlow <-> Website Mode Bridge (Nano Banana Pro vs Google Flow) */
(() => {
  'use strict';

  function markBridgeReady() {
    try {
      if (document.documentElement) {
        document.documentElement.setAttribute('data-pakflow-nano-ready', 'true');
      }
    } catch {}
  }

  markBridgeReady();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', markBridgeReady, { once: true });
  }

  function readSiteToken() {
    try {
      const keys = ['flow_token', 'token', 'flowbypak_token', 'auth_token', 'jwt'];
      for (const k of keys) {
        const v = localStorage.getItem(k);
        if (v && typeof v === 'string' && v.trim().length > 10) {
          return v.trim().replace(/^"|"$/g, '');
        }
      }
      const rawBridge = localStorage.getItem('pakflow_auth_bridge') || localStorage.getItem('__flow_auth__');
      if (rawBridge) {
        const parsed = JSON.parse(rawBridge);
        if (parsed?.token) return String(parsed.token).trim();
      }
    } catch {}
    return '';
  }

  function readSiteUser() {
    try {
      const raw = localStorage.getItem('flow_user_cache') || localStorage.getItem('user');
      if (raw) return JSON.parse(raw);
      const rawBridge = localStorage.getItem('pakflow_auth_bridge') || localStorage.getItem('__flow_auth__');
      if (rawBridge) {
        const parsed = JSON.parse(rawBridge);
        if (parsed?.user) return parsed.user;
        if (parsed?.email) return parsed;
      }
    } catch {}
    return null;
  }

  function triggerHardLogoutSync() {
    try {
      chrome.runtime.sendMessage({ type: 'PAKFLOW_HARD_LOGOUT', apiBase: window.location.origin }, () => {
        void chrome.runtime?.lastError;
      });
    } catch {}
  }

  function readOrCreateSiteDeviceId() {
    try {
      let id = localStorage.getItem('pakflow_device_id');
      if (!id || typeof id !== 'string' || id.trim().length < 4) {
        const rawBridge = localStorage.getItem('pakflow_auth_bridge');
        if (rawBridge) {
          const parsed = JSON.parse(rawBridge);
          if (parsed?.deviceId) id = String(parsed.deviceId).trim();
        }
      }
      if (!id || typeof id !== 'string' || id.trim().length < 4) {
        id = 'dev_' + Math.random().toString(36).slice(2, 12);
        localStorage.setItem('pakflow_device_id', id);
      }
      return id.trim();
    } catch {}
    return '';
  }

  function reconcilePortalConnectionState() {
    try {
      const path = String(window.location.pathname || '/').toLowerCase();
      if (path.startsWith('/admin') || path.startsWith('/reseller')) return;
      const tok = readSiteToken();
      if (tok) {
        chrome.runtime.sendMessage(
          {
            type: 'PAKFLOW_ENSURE_COOKIES_READY',
            token: tok,
            deviceId: readOrCreateSiteDeviceId(),
            user: readSiteUser(),
            apiBase: window.location.origin
          },
          () => { void chrome.runtime?.lastError; }
        );
      } else if (path === '/login' || path === '/signup' || path === '/register') {
        chrome.runtime.sendMessage(
          {
            type: 'PAKFLOW_PORTAL_UNAUTHENTICATED',
            apiBase: window.location.origin,
            pathname: path
          },
          () => { void chrome.runtime?.lastError; }
        );
      }
    } catch {}
  }

  window.addEventListener('__bf_logout__', triggerHardLogoutSync);

  window.addEventListener('storage', (ev) => {
    try {
      if (!ev) return;
      const authKeys = ['pakflow_token', 'token', 'flow_token', 'flowbypak_token', 'pakflow_auth_bridge'];
      if (ev.key === null || (authKeys.includes(ev.key) && !ev.newValue && !readSiteToken())) {
        triggerHardLogoutSync();
      } else if (authKeys.includes(ev.key) && ev.newValue) {
        reconcilePortalConnectionState();
      }
    } catch {}
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => setTimeout(reconcilePortalConnectionState, 120), { once: true });
  } else {
    setTimeout(reconcilePortalConnectionState, 120);
  }

  window.addEventListener('message', (event) => {
    if (event.source !== window) return;
    const data = event.data;
    if (!data || typeof data !== 'object') return;

    if (data.type === 'PAKFLOW_SITE_LOGOUT' || data.type === 'PAKFLOW_PORTAL_LOGOUT') {
      triggerHardLogoutSync();
      return;
    }

    if (data.type === 'PAKFLOW_SITE_LOGIN' && data.token) {
      try {
        chrome.runtime.sendMessage(
          {
            type: 'PAKFLOW_ENSURE_COOKIES_READY',
            token: String(data.token),
            deviceId: String(data.deviceId || readOrCreateSiteDeviceId()),
            user: data.user || readSiteUser(),
            apiBase: window.location.origin,
            force: true
          },
          () => { void chrome.runtime?.lastError; }
        );
      } catch {}
      return;
    }

    if (data.type === 'PAKFLOW_SET_MODE') {
      const mode = data.mode === 'nano_banana' ? 'nano_banana' : 'google_flow';
      const slotId = (data.slotId && data.slotId !== 'auto') ? String(data.slotId) : '';
      const openFlow = Boolean(data.openFlow);
      const requestId = data.requestId || '';
      const token = data.token || readSiteToken();
      const user = data.user || readSiteUser();
      const deviceId = readOrCreateSiteDeviceId();
      const apiBase = window.location.origin;

      try {
        chrome.runtime.sendMessage(
          {
            type: 'PAKFLOW_SET_LAUNCH_MODE',
            mode,
            slotId,
            openFlow,
            token,
            deviceId,
            user,
            apiBase
          },
          (resp) => {
            const err = chrome.runtime?.lastError;
            window.postMessage(
              {
                type: 'PAKFLOW_MODE_RESULT',
                requestId,
                mode,
                success: !err && Boolean(resp?.success !== false),
                openedByExtension: Boolean(resp?.openedByExtension),
                slotId: resp?.slotId || slotId,
                error: err ? err.message : (resp?.error || null)
              },
              '*'
            );
          }
        );
      } catch (e) {
        window.postMessage(
          {
            type: 'PAKFLOW_MODE_RESULT',
            requestId,
            mode,
            success: false,
            openedByExtension: false,
            error: e?.message || 'Extension bridge unavailable'
          },
          '*'
        );
      }
    }
  });

  // Relay live credit updates from extension background directly to the open PakFlow User Panel
  try {
    if (chrome?.runtime?.onMessage) {
      chrome.runtime.onMessage.addListener((msg) => {
        if (!msg || typeof msg !== 'object') return;
        if (msg.type === 'PAKFLOW_CREDITS_UPDATED' && typeof msg.credits === 'number') {
          window.postMessage(
            {
              type: 'PAKFLOW_CREDITS_UPDATED',
              credits: msg.credits,
              creditsTotal: msg.creditsTotal,
              deducted: msg.deducted || 0
            },
            '*'
          );
        }
      });
    }
  } catch {}
})();
