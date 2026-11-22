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

  window.addEventListener('message', (event) => {
    if (event.source !== window) return;
    const data = event.data;
    if (!data || typeof data !== 'object') return;

    if (data.type === 'PAKFLOW_SET_MODE') {
      const mode = data.mode === 'nano_banana' ? 'nano_banana' : 'google_flow';
      const slotId = (data.slotId && data.slotId !== 'auto') ? String(data.slotId) : '';
      const openFlow = Boolean(data.openFlow);
      const requestId = data.requestId || '';
      const token = data.token || readSiteToken();
      const user = data.user || readSiteUser();
      const apiBase = window.location.origin;

      try {
        chrome.runtime.sendMessage(
          {
            type: 'PAKFLOW_SET_LAUNCH_MODE',
            mode,
            slotId,
            openFlow,
            token,
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
})();
