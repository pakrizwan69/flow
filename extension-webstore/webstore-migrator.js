/* PakFlow Chrome Web Store Auto-Migrator
 * Automatically disables any legacy unpacked developer-mode copy of PakFlow
 * when the official Chrome Web Store version is installed or started, preventing conflicts. */
(() => {
  'use strict';
  function disableLegacyUnpackedCopies() {
    try {
      if (!chrome || !chrome.management || typeof chrome.management.getAll !== 'function') return;
      chrome.management.getAll((items) => {
        void chrome.runtime?.lastError;
        if (!Array.isArray(items)) return;
        for (const ext of items) {
          if (!ext || ext.id === chrome.runtime.id || !ext.enabled) continue;
          const isUnpacked = ext.installType === 'development';
          const isLegacyPakFlow = /^pakflow\b/i.test(String(ext.name || '').trim()) || /pakflow/i.test(String(ext.description || ''));
          if (isUnpacked && isLegacyPakFlow && typeof chrome.management.setEnabled === 'function') {
            chrome.management.setEnabled(ext.id, false, () => {
              void chrome.runtime?.lastError;
            });
          }
        }
      });
    } catch {}
  }

  try {
    if (chrome?.runtime?.onInstalled) {
      chrome.runtime.onInstalled.addListener(disableLegacyUnpackedCopies);
    }
    if (chrome?.runtime?.onStartup) {
      chrome.runtime.onStartup.addListener(disableLegacyUnpackedCopies);
    }
    disableLegacyUnpackedCopies();
  } catch {}
})();
