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
    if (reason === 'outdated_extension') {
      const latestVer = String(lockState?.latestVersion || '').replace(/^v/i, '');
      const curVer = String(lockState?.currentVersion || '').replace(/^v/i, '');
      const verSuffix = latestVer ? (' (v' + latestVer + ')') : '';
      return {
        iconType: 'update',
        badge: 'EXTENSION UPDATE REQUIRED',
        badgeTone: 'amber',
        title: 'Download & Install Latest Extension',
        versionPill: latestVer ? ((curVer ? ('Installed v' + curVer + '  →  ') : '') + 'Latest v' + latestVer) : '',
        message: lockState?.message || ('Download and install the latest PakFlow Extension' + verSuffix + ' from your user panel to continue using Google Flow.'),
        ctaText: 'Open User Panel →',
        ctaHref: portalBase + '/dashboard',
        secondaryCtaText: 'Direct Download Latest ZIP ↓',
        secondaryCtaHref: portalBase + '/downloads/latest',
        footerText: 'PakFlow Protection · Install the latest extension from your User Panel'
      };
    }
    if (reason === 'credits_exhausted') {
      return {
        icon: '⚡',
        badge: '0 CREDITS REMAINING',
        title: 'AI Credits Exhausted',
        message: lockState?.message || 'Your AI credits have reached 0. Please contact support or renew your plan to refill credits and continue using Google Flow.',
        ctaText: 'Open PakFlow Dashboard →',
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
          'background:radial-gradient(ellipse at top, rgba(37,99,235,0.22), rgba(11,17,32,0.97) 65%)',
          'backdrop-filter:blur(16px)',
          '-webkit-backdrop-filter:blur(16px)',
          'display:flex',
          'align-items:center',
          'justify-content:center',
          'padding:24px',
          'font-family:"Plus Jakarta Sans",-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif',
          'color:#f8fafc',
          'box-sizing:border-box'
        ].join(';');
        host.appendChild(el);
      }

      el.innerHTML = '';
      const card = document.createElement('div');
      card.style.cssText = [
        'width:100%',
        'max-width:420px',
        'background:#111827',
        'border:1px solid rgba(148,163,184,0.24)',
        'border-radius:16px',
        'padding:30px 26px',
        'text-align:center',
        'box-shadow:0 28px 80px rgba(0,0,0,0.75)',
        'box-sizing:border-box'
      ].join(';');

      const iconWrap = document.createElement('div');
      if (copy.iconType === 'update') {
        iconWrap.innerHTML = '<svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>';
        iconWrap.style.cssText = 'width:54px;height:54px;margin:0 auto 14px;border-radius:14px;background:rgba(245,158,11,0.16);border:1px solid rgba(251,191,36,0.38);display:flex;align-items:center;justify-content:center;';
      } else {
        iconWrap.innerHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>';
        iconWrap.style.cssText = 'width:52px;height:52px;margin:0 auto 14px;border-radius:12px;background:rgba(37,99,235,0.14);border:1px solid rgba(59,130,246,0.32);display:flex;align-items:center;justify-content:center;';
      }

      const badge = document.createElement('div');
      badge.textContent = copy.badge;
      badge.style.cssText = copy.badgeTone === 'amber'
        ? 'display:inline-block;padding:4px 11px;border-radius:999px;background:rgba(245,158,11,0.16);border:1px solid rgba(251,191,36,0.42);color:#fde68a;font-size:10.5px;font-weight:700;letter-spacing:0.06em;margin-bottom:10px;'
        : 'display:inline-block;padding:4px 11px;border-radius:999px;background:rgba(239,68,68,0.14);border:1px solid rgba(239,68,68,0.34);color:#fca5a5;font-size:10.5px;font-weight:700;letter-spacing:0.06em;margin-bottom:10px;';

      const title = document.createElement('h2');
      title.textContent = copy.title;
      title.style.cssText = 'margin:0 0 8px;font-size:20px;font-weight:800;color:#f8fafc;letter-spacing:-0.02em;';

      const msg = document.createElement('p');
      msg.textContent = copy.message;
      msg.style.cssText = 'margin:0 0 18px;font-size:13.5px;line-height:1.6;color:#94a3b8;';

      const btn = document.createElement('a');
      btn.href = copy.ctaHref;
      btn.target = '_blank';
      btn.rel = 'noopener noreferrer';
      btn.textContent = copy.ctaText;
      btn.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:100%;padding:12px 18px;border-radius:10px;background:#2563eb;color:#ffffff;font-size:13.5px;font-weight:700;text-decoration:none;box-shadow:0 8px 22px rgba(37,99,235,0.34);box-sizing:border-box;';

      const foot = document.createElement('div');
      foot.textContent = copy.footerText || 'PakFlow Protection · Access restores automatically once active';
      foot.style.cssText = 'margin-top:14px;font-size:11px;color:#64748b;';

      card.append(iconWrap, badge, title);
      if (copy.versionPill) {
        const vp = document.createElement('div');
        vp.textContent = copy.versionPill;
        vp.style.cssText = 'display:inline-block;margin:0 auto 12px;padding:4px 12px;border-radius:8px;background:rgba(30,41,59,0.9);border:1px solid rgba(148,163,184,0.24);color:#cbd5e1;font-size:11.5px;font-weight:600;';
        card.append(vp);
      }
      card.append(msg, btn);
      if (copy.secondaryCtaText && copy.secondaryCtaHref) {
        const btn2 = document.createElement('a');
        btn2.href = copy.secondaryCtaHref;
        btn2.target = '_blank';
        btn2.rel = 'noopener noreferrer';
        btn2.textContent = copy.secondaryCtaText;
        btn2.style.cssText = 'display:inline-flex;align-items:center;justify-content:center;width:100%;margin-top:9px;padding:10px 16px;border-radius:10px;background:rgba(255,255,255,0.06);border:1px solid rgba(148,163,184,0.28);color:#e2e8f0;font-size:12.5px;font-weight:600;text-decoration:none;box-sizing:border-box;';
        card.append(btn2);
      }
      card.append(foot);
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
