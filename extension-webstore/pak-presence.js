/* PakFlow Extension Presence Heartbeat */
(() => {
  'use strict';
  if (globalThis.__pakPresenceActive) return;
  globalThis.__pakPresenceActive = true;

  const announce = () => {
    try {
      window.postMessage({ type: 'EXTENSION_CHECK', extensionName: 'PAK FLOW' }, '*');
    } catch {}
  };

  const pingBg = () => {
    try {
      if (/flow\.google\.com|labs\.google/i.test(location.hostname) && chrome?.runtime?.id) {
        chrome.runtime.sendMessage({ type: 'PAKFLOW_KEEPALIVE_PING' }, () => { void chrome.runtime?.lastError; });
      }
    } catch {}
  };

  announce();
  pingBg();
  setInterval(announce, 1500);
  setInterval(pingBg, 15000);
})();
