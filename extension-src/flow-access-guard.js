/* PakFlow Access Lock Guard — enforces portal-open, logged-in, and active subscription policy on Google Flow. */
(() => {
  'use strict';
  if (globalThis.__pfAccessGuardMounted) return;
  globalThis.__pfAccessGuardMounted = true;

  const OVERLAY_ID = 'pakflow-access-lock-overlay';
  let currentLock = null;
  let guardObserver = null;

  function getCopy(lockState) {
    const reason = String(lockState?.reason || 'logged_out');
    const portalBase = String(lockState?.portalUrl || 'https://flowbypak.com').replace(/\/+$/, '');
    if (reason === 'portal_closed') {
      return {
        icon: '🔒',
        badge: 'PORTAL TAB REQUIRED',
        title: 'Keep PakFlow Portal Open',
        message: lockState?.message || 'Your PakFlow dashboard tab is closed. Please keep your PakFlow portal open to continue using Google Flow.',
        ctaText: 'Open PakFlow Portal →',
        ctaHref: portalBase + '/dashboard'
      };
    }
    if (reason === 'expired') {
      return {
        icon: '⏳',
        badge: 'SUBSCRIPTION ENDED',
        title: 'Your Plan Has Expired',
        message: lockState?.message || 'Your PakFlow subscription has expired. Renew your plan to regain instant access to Google Flow.',
        ctaText: 'Renew Subscription →',
        ctaHref: portalBase + '/pricing'
      };
    }
    if (reason === 'basic') {
      return {
        icon: '⭐',
        badge: 'UPGRADE REQUIRED',
        title: 'Paid Plan Required',
        message: lockState?.message || 'Google Flow requires an active Pro or Max subscription on PakFlow.',
        ctaText: 'View Plans →',
        ctaHref: portalBase + '/pricing'
      };
    }
    return {
      icon: '🔑',
      badge: 'SIGNED OUT',
      title: 'Sign In to PakFlow',
      message: lockState?.message || 'You are currently logged out of PakFlow. Sign in on the PakFlow portal to use Google Flow.',
      ctaText: 'Sign In to PakFlow →',
      ctaHref: portalBase + '/login'
    };
  }

  function removeOverlay() {
    try {
      const existing = document.getElementById(OVERLAY_ID);
      if (existing) existing.remove();
      if (document.documentElement) {
        document.documentElement.style.removeProperty('overflow');
      }
    } catch {}
  }

  function renderOverlay(lockState) {
    try {
      if (!lockState || !lockState.locked) {
        removeOverlay();
        return;
      }
      const host = document.documentElement || document.body;
      if (!host) {
        setTimeout(() => renderOverlay(currentLock), 80);
        return;
      }
      const copy = getCopy(lockState);
      let el = document.getElementById(OVERLAY_ID);
      if (!el) {
        el = document.createElement('div');
        el.id = OVERLAY_ID;
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-modal', 'true');
        el.style.cssText = [
          'position:fixed',
          'inset:0',
          'z-index:2147483647',
          'background:radial-gradient(ellipse at top, rgba(88,28,135,0.45), rgba(5,5,10,0.97) 65%)',
          'backdrop-filter:blur(18px)',
          '-webkit-backdrop-filter:blur(18px)',
          'display:flex',
          'align-items:center',
          'justify-content:center',
          'padding:24px',
          'font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif',
          'color:#f5f5f7',
          'box-sizing:border-box'
        ].join(';');
        host.appendChild(el);
      }

      el.innerHTML = '';
      const card = document.createElement('div');
      card.style.cssText = [
        'width:100%',
        'max-width:440px',
        'background:rgba(16,16,24,0.94)',
        'border:1px solid rgba(168,85,247,0.35)',
        'border-radius:22px',
        'padding:32px 28px',
        'text-align:center',
        'box-shadow:0 28px 80px rgba(0,0,0,0.75), 0 0 60px rgba(139,92,246,0.18)',
        'box-sizing:border-box'
      ].join(';');

      const iconWrap = document.createElement('div');
      iconWrap.textContent = copy.icon;
      iconWrap.style.cssText = 'width:58px;height:58px;margin:0 auto 16px;border-radius:16px;background:rgba(168,85,247,0.16);border:1px solid rgba(168,85,247,0.35);display:flex;align-items:center;justify-content:center;font-size:26px;';

      const badge = document.createElement('div');
      badge.textContent = copy.badge;
      badge.style.cssText = 'display:inline-block;padding:4px 12px;border-radius:999px;background:rgba(239,68,68,0.16);border:1px solid rgba(239,68,68,0.38);color:#fca5a5;font-size:11px;font-weight:800;letter-spacing:0.08em;margin-bottom:12px;';

      const title = document.createElement('h2');
      title.textContent = copy.title;
      title.style.cssText = 'margin:0 0 10px;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.02em;';

      const msg = document.createElement('p');
      msg.textContent = copy.message;
      msg.style.cssText = 'margin:0 0 24px;font-size:14px;line-height:1.6;color:#a1a1aa;';

      const btn = document.createElement('a');
      btn.href = copy.ctaHref;
      btn.target = '_blank';
      btn.rel = 'noopener noreferrer';
      btn.textContent = copy.ctaText;
      btn.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:100%;padding:13px 20px;border-radius:12px;background:linear-gradient(135deg,#9333ea,#6366f1);color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;box-shadow:0 10px 26px rgba(124,58,237,0.38);box-sizing:border-box;';

      const foot = document.createElement('div');
      foot.textContent = 'PakFlow Protection • Access restores automatically once active';
      foot.style.cssText = 'margin-top:16px;font-size:11.5px;color:#71717a;';

      card.append(iconWrap, badge, title, msg, btn, foot);
      el.appendChild(card);

      if (document.documentElement) {
        document.documentElement.style.setProperty('overflow', 'hidden', 'important');
      }
    } catch {}
  }

  function applyState(state) {
    currentLock = state && typeof state === 'object' ? state : { locked: false };
    renderOverlay(currentLock);
  }

  try {
    if (chrome?.runtime?.id && chrome?.storage?.local) {
      chrome.storage.local.get(['pf_flow_lock_state'], (res) => {
        void chrome.runtime?.lastError;
        applyState(res?.pf_flow_lock_state || null);
      });
      chrome.storage.onChanged.addListener((changes, area) => {
        if (area === 'local' && changes.pf_flow_lock_state) {
          applyState(changes.pf_flow_lock_state.newValue || null);
        }
      });
    }
  } catch {}

  try {
    guardObserver = new MutationObserver(() => {
      if (currentLock && currentLock.locked && !document.getElementById(OVERLAY_ID)) {
        renderOverlay(currentLock);
      }
    });
    const startObserve = () => {
      if (document.documentElement) {
        guardObserver.observe(document.documentElement, { childList: true });
      }
    };
    if (document.documentElement) startObserve();
    else document.addEventListener('DOMContentLoaded', startObserve, { once: true });
  } catch {}
})();
