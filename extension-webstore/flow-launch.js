/* Open Flow only after the selected session has actually finished syncing.
 * Also scopes /api/cookies & /api/slots requests by launchMode ('nano_banana' -> slots 11..15, 'google_flow' -> slots 1..10).
 * Mobile-compatible (Kiwi, Mises, Lemur, Edge Android, and Desktop Chrome). */
(() => {
  'use strict';

  const origFetch = globalThis.fetch ? globalThis.fetch.bind(globalThis) : null;
  if (origFetch) {
    globalThis.fetch = async function (input, init) {
      try {
        const rawUrl = typeof input === 'string' ? input : (input && input.url ? input.url : String(input));
        const u = new URL(rawUrl, location.origin);
        const p = u.pathname.replace(/\/+$/, '');
        if (/\/(?:api\/)?(?:user\/login|auth\/login)$/i.test(p)) {
          const stDev = await new Promise(res => {
            try { chrome.storage.local.get(['pakflow_device_id'], s => res(s || {})); } catch { res({}); }
          });
          let devId = typeof stDev.pakflow_device_id === 'string' ? stDev.pakflow_device_id.trim() : '';
          if (!devId) {
            devId = 'dev_' + Math.random().toString(36).slice(2, 12);
            try { chrome.storage.local.set({ pakflow_device_id: devId }); } catch {}
          }
          const nextInit = Object.assign({}, init || {});
          const hdrs = new Headers(nextInit.headers || (input && input.headers) || {});
          if (!hdrs.has('X-Device-Id')) hdrs.set('X-Device-Id', devId);
          nextInit.headers = hdrs;
          return origFetch(u.toString(), nextInit);
        }
        if (/\/(?:api\/)?(?:cookies|slots|cookies\/active)$/i.test(p)) {
          const st = await new Promise(res => {
            try {
              chrome.storage.local.get(['launchMode', 'activeSlot'], s => res(s || {}));
            } catch {
              res({});
            }
          });
          const isNano = st.launchMode === 'nano_banana';
          if (!u.searchParams.has('mode')) {
            u.searchParams.set('mode', isNano ? 'nano' : 'flow');
          }
          const curNum = parseInt(String(st.activeSlot || '').replace(/\D/g, ''), 10);
          if (isNano && (isNaN(curNum) || curNum < 11 || curNum > 15)) {
            await new Promise(r => chrome.storage.local.set({ activeSlot: 'slot_11', sessionLoopIndex: 0 }, () => r()));
          } else if (!isNano && !isNaN(curNum) && curNum >= 11) {
            await new Promise(r => chrome.storage.local.set({ activeSlot: 'slot_1', sessionLoopIndex: 0 }, () => r()));
          }
          return origFetch(u.toString(), init);
        }
      } catch {}
      return origFetch(input, init);
    };
  }
})();

globalThis.PakFlowLauncher = (() => {
  let opening = false;

  function status(text, error = false) {
    const node = document.getElementById('status');
    if (node) {
      node.textContent = text;
      node.classList.remove('hidden');
      node.setAttribute('role', 'status');
      node.style.color = error ? '#fda4af' : '#c4b5fd';
    }
  }

  function sendMessagePromise(payload, timeoutMs = 32000) {
    return new Promise((resolve, reject) => {
      const timer = setTimeout(() => reject(new Error('Session setup timed out. Select your server again, then retry.')), timeoutMs);
      try {
        chrome.runtime.sendMessage(payload, response => {
          clearTimeout(timer);
          if (chrome.runtime.lastError) reject(new Error(chrome.runtime.lastError.message));
          else resolve(response);
        });
      } catch (error) {
        clearTimeout(timer);
        reject(error);
      }
    });
  }

  async function openOrReuseFlowTab() {
    const targetUrl = 'https://flow.google.com/';
    try {
      const allTabs = await new Promise(resolve => {
        try {
          chrome.tabs.query({}, list => {
            void chrome.runtime?.lastError;
            resolve(Array.isArray(list) ? list : []);
          });
        } catch {
          resolve([]);
        }
      });
      const landing = allTabs.find(tab => {
        const raw = tab && (tab.url || tab.pendingUrl);
        if (!raw) return false;
        try {
          const url = new URL(raw);
          if (url.hostname === 'flow.google.com') {
            return url.pathname === '/' || /^\/about(?:\/|$)/i.test(url.pathname);
          }
          if (/(?:^|\.)labs\.google$/i.test(url.hostname)) {
            return /^\/fx\/(?:about|tools\/flow)\/?$/i.test(url.pathname);
          }
          return false;
        } catch {
          return false;
        }
      });
      if (landing && landing.id !== undefined) {
        await chrome.tabs.update(landing.id, { url: targetUrl, active: true });
        return;
      }
      await chrome.tabs.create({ url: targetUrl, active: true });
    } catch {
      window.open(targetUrl, '_blank', 'noopener');
    }
  }

  return {
    async open() {
      if (opening) return;
      opening = true;
      const button = document.getElementById('launchFlowBtn');
      const label = document.getElementById('launchBtnText');
      let defaultBtnLabel = 'Open Google Flow';
      if (button) button.disabled = true;
      try {
        const state = await chrome.storage.local.get([
          'isLoggedIn', 'flowSessionDetached', 'activeSlot', 'userPlan', 'apiBase', 'connectedFromSite', 'launchMode'
        ]);
        const isNano = state.launchMode === 'nano_banana';
        defaultBtnLabel = isNano ? 'Open Nano Banana Pro' : 'Open Google Flow';
        if (label) label.textContent = isNano ? 'Connecting Nano Banana…' : 'Connecting Flow…';

        const plan = String(state.userPlan || '').toLowerCase();
        if (['basic', 'free', 'trial'].includes(plan)) {
          const base = state.apiBase || state.connectedFromSite || 'https://flowbypak.com';
          await chrome.tabs.create({ url: base + '/pricing' });
          return;
        }
        if (!(state.isLoggedIn === true || state.isLoggedIn === 'true') || state.flowSessionDetached) {
          throw new Error('Sign in to PakFlow and connect your server first.');
        }

        if (state.isLoggedIn === true) {
          await chrome.storage.local.set({ isLoggedIn: 'true' });
        }

        // Enforce proper slot range for current mode (and allocate round-robin Nano slot if out of range)
        const slotNum = parseInt(String(state.activeSlot || '').replace(/\D/g, ''), 10);
        if (isNano && (isNaN(slotNum) || slotNum < 11 || slotNum > 15)) {
          const rrRes = await sendMessagePromise({ type: 'PAKFLOW_SET_LAUNCH_MODE', mode: 'nano_banana', slotId: '', openFlow: false }).catch(() => null);
          state.activeSlot = rrRes?.slotId || 'slot_11';
        } else if (!isNano && !isNaN(slotNum) && slotNum >= 11) {
          await chrome.storage.local.set({ activeSlot: 'slot_1', sessionLoopIndex: 0 });
          state.activeSlot = 'slot_1';
        }

        status(isNano ? 'Connecting Nano Banana Pro. Please wait…' : 'Connecting the selected server. Please wait…');
        let result;
        let syncedOk = false;
        for (let attempt = 0; attempt < 45; attempt++) {
          result = await sendMessagePromise({ type: 'SYNC_COOKIES_NOW' });
          if (result?.success === true) {
            syncedOk = true;
            break;
          }
          if (result?.success === false || result?.error) {
            break;
          }
          if (result?.status === 'synced' && Number(result?.lastInjectedTime || 0) > 0) {
            const access = await sendMessagePromise({ type: 'CHECK_FLOW_ACCESS' }, 5000).catch(() => null);
            if (access?.allowed === true) {
              syncedOk = true;
              break;
            }
          }
          if (attempt < 44) await new Promise(resolve => setTimeout(resolve, 350));
        }

        if (!syncedOk) {
          const access = await sendMessagePromise({ type: 'CHECK_FLOW_ACCESS' }, 5000).catch(() => null);
          if (access?.allowed === true) {
            syncedOk = true;
          }
        }

        if (!syncedOk) {
          const latestErr = await chrome.storage.local.get(['lastSyncError']);
          throw new Error(result?.error || latestErr.lastSyncError || 'Server session could not be connected. Select the server again and retry.');
        }

        const latest = await chrome.storage.local.get(['activeSlot', 'flowSessionDetached', 'isLoggedIn']);
        if (latest.flowSessionDetached || !(latest.isLoggedIn === true || latest.isLoggedIn === 'true')) {
          throw new Error('PakFlow was signed out during setup. Sign in again.');
        }

        await openOrReuseFlowTab();
        status(isNano ? 'Nano Banana Pro opened (Nano Banana Only).' : 'Flow opened with the selected server.');
      } catch (error) {
        status(error.message || 'Could not open Flow. Please retry.', true);
      } finally {
        opening = false;
        if (button) button.disabled = false;
        if (label) label.textContent = defaultBtnLabel;
      }
    }
  };
})();
