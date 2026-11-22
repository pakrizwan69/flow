/* Popup UI Mode Synchronizer — Displays "Nano Banana Only" when launched from Nano Banana Pro
 * (using Cookie Slots 11–15), and standard "Google Flow" UI when launched from Google Flow (Slots 1–10). */
(() => {
  'use strict';

  let currentMode = 'google_flow';
  let currentSlot = 'slot_1';
  let applying = false;
  let rafScheduled = false;
  let observer = null;

  const OBSERVE_OPTS = { childList: true, subtree: true, characterData: true };

  function parseSlotNum(slotId) {
    const m = String(slotId || '').match(/(\d+)/);
    return m ? parseInt(m[1], 10) : null;
  }

  function setTextIfChanged(el, nextText) {
    if (el && el.textContent !== nextText) {
      el.textContent = nextText;
    }
  }

  function setStyleIfChanged(el, prop, val) {
    if (el && el.style && el.style[prop] !== val) {
      el.style[prop] = val;
    }
  }

  function ensureNanoBanner() {
    let banner = document.getElementById('nanoOnlyModeBanner');
    if (!banner) {
      const hub = document.getElementById('serverHubCard');
      if (!hub || !hub.parentElement) return null;
      banner = document.createElement('div');
      banner.id = 'nanoOnlyModeBanner';
      banner.style.cssText = [
        'display:none',
        'align-items:center',
        'justify-content:space-between',
        'gap:8px',
        'padding:9px 13px',
        'margin-bottom:10px',
        'border-radius:12px',
        'background:linear-gradient(135deg, rgba(245,158,11,0.20), rgba(234,179,8,0.10))',
        'border:1px solid rgba(245,158,11,0.45)',
        'color:#fde68a',
        'font-size:11.5px',
        'font-weight:800',
        'letter-spacing:0.04em',
        'box-shadow:0 6px 20px rgba(245,158,11,0.12)'
      ].join(';');

      const leftSpan = document.createElement('span');
      leftSpan.textContent = '🍌 NANO BANANA ONLY';

      const rightPill = document.createElement('span');
      rightPill.id = 'nanoOnlySlotPill';
      rightPill.style.cssText = 'font-size:10px;padding:2px 8px;border-radius:999px;background:rgba(245,158,11,0.25);border:1px solid rgba(251,191,36,0.4);color:#fef3c7;';
      rightPill.textContent = 'IMAGES ONLY';

      banner.appendChild(leftSpan);
      banner.appendChild(rightPill);
      hub.parentElement.insertBefore(banner, hub);
    }
    return banner;
  }

  function pauseObserver() {
    if (observer) {
      try {
        observer.disconnect();
      } catch {}
    }
  }

  function resumeObserver() {
    if (observer) {
      try {
        observer.takeRecords();
        const target = document.getElementById('mainView') || document.body;
        if (target) observer.observe(target, OBSERVE_OPTS);
      } catch {}
    }
  }

  function applyModeLabels() {
    if (applying) return;
    applying = true;
    pauseObserver();
    try {
      const isNano = currentMode === 'nano_banana';

      if (isNano) {
        const banner = ensureNanoBanner();
        if (banner) {
          setStyleIfChanged(banner, 'display', 'flex');
          const pill = document.getElementById('nanoOnlySlotPill');
          setTextIfChanged(pill, 'IMAGES ONLY');
        }
      } else {
        const existingBanner = document.getElementById('nanoOnlyModeBanner');
        if (existingBanner) {
          setStyleIfChanged(existingBanner, 'display', 'none');
        }
      }

      // Login & Main view header tags
      const tags = document.querySelectorAll('.version-tag');
      tags.forEach((tag) => {
        if (isNano) {
          setTextIfChanged(tag, 'NANO BANANA ONLY');
          setStyleIfChanged(tag, 'background', 'rgba(245, 158, 11, 0.22)');
          setStyleIfChanged(tag, 'color', '#fcd34d');
          setStyleIfChanged(tag, 'borderColor', 'rgba(245, 158, 11, 0.45)');
        } else {
          setTextIfChanged(tag, 'EXTENSION');
          setStyleIfChanged(tag, 'background', '');
          setStyleIfChanged(tag, 'color', '');
          setStyleIfChanged(tag, 'borderColor', '');
        }
      });

      // Session Hub Card Title
      const cardTitleSpans = document.querySelectorAll('#serverHubCard .card-title span');
      if (cardTitleSpans.length >= 2) {
        const titleSpan = cardTitleSpans[cardTitleSpans.length - 1];
        setTextIfChanged(titleSpan, isNano ? 'Nano Banana Only' : 'Google Flow session');
      }

      // Inject / Cycle button text
      const cycleTitle = document.getElementById('injectCycleTitle');
      if (cycleTitle && isNano) {
        setTextIfChanged(cycleTitle, 'Nano Banana Only • Connected');
      }

      const cycleSub = document.getElementById('injectCycleSub');
      if (cycleSub && isNano) {
        const rawSub = cycleSub.textContent || '';
        if (/Server\s+\d+|Nano\s+Slot/i.test(rawSub)) {
          setTextIfChanged(cycleSub, 'Click to switch Nano Banana server');
        }
      }

      // Ready Card
      const readyTitle = document.getElementById('readyTitleText');
      if (readyTitle) {
        if (isNano) {
          setTextIfChanged(readyTitle, 'Nano Banana Only');
        } else if (readyTitle.textContent === 'Nano Banana Only') {
          setTextIfChanged(readyTitle, 'Flow AI Studio Ready');
        }
      }

      const readyDesc = document.getElementById('readyDescText');
      if (readyDesc && isNano) {
        const rawDesc = readyDesc.textContent || '';
        if (!/locked|upgrade|offline|failed/i.test(rawDesc)) {
          setTextIfChanged(readyDesc, 'Nano Banana Pro • Images Mode Ready');
        }
      }

      // Launch Button
      const launchText = document.getElementById('launchBtnText');
      if (launchText) {
        const curTxt = (launchText.textContent || '').trim();
        if (isNano) {
          if (curTxt === 'Open Google Flow') {
            setTextIfChanged(launchText, 'Open Nano Banana Pro');
          }
        } else if (curTxt === 'Open Nano Banana Pro') {
          setTextIfChanged(launchText, 'Open Google Flow');
        }
      }
    } finally {
      resumeObserver();
      applying = false;
    }
  }

  function scheduleApply() {
    if (rafScheduled) return;
    rafScheduled = true;
    const runner = () => {
      rafScheduled = false;
      applyModeLabels();
    };
    if (typeof requestAnimationFrame === 'function') {
      requestAnimationFrame(runner);
    } else {
      setTimeout(runner, 16);
    }
  }

  function refreshFromStorage() {
    try {
      chrome.storage.local.get(['launchMode', 'activeSlot'], (st) => {
        void chrome.runtime?.lastError;
        currentMode = st?.launchMode === 'nano_banana' ? 'nano_banana' : 'google_flow';
        currentSlot = st?.activeSlot || (currentMode === 'nano_banana' ? 'slot_11' : 'slot_1');
        applyModeLabels();
      });
    } catch {}
  }

  try {
    observer = new MutationObserver(() => {
      if (!applying) scheduleApply();
    });
    const target = document.getElementById('mainView') || document.body;
    if (target) observer.observe(target, OBSERVE_OPTS);
  } catch {}

  refreshFromStorage();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', refreshFromStorage, { once: true });
  }

  try {
    chrome.storage.onChanged.addListener((changes, area) => {
      if (area === 'local' && (changes.launchMode || changes.activeSlot)) {
        refreshFromStorage();
      }
    });
  } catch {}
})();
