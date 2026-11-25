/* Creator Tools Pro Compatibility Presence Heartbeat */
(() => {
  'use strict';
  if (globalThis.__ctpPresenceActive) return;
  globalThis.__ctpPresenceActive = true;

  const announce = () => {
    try {
      window.postMessage({ type: 'EXTENSION_CHECK', extensionName: 'CREATOR TOOLS PRO' }, '*');
    } catch {}
  };

  announce();
  setInterval(announce, 1500);
})();
