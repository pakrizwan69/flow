/* PakFlow — Nano Banana Only Mode Enforcer on flow.google.com
 * When launchMode === 'nano_banana':
 *   1. Forces flow.google.com into Image (Nano Banana) mode.
 *   2. Locks the Video tab/options so users can ONLY generate images.
 *   3. Shows "Open Google Flow — You're in Images tab" whenever a user clicks the Video option.
 * When launchMode === 'google_flow' (default):
 *   Leaves flow.google.com 100% untouched (both Image and Video work normally).
 */
(() => {
  'use strict';
  if (window.top !== window) return;

  const STYLE_ID = 'pakflow-nano-only-style';
  const TOAST_ID = 'pakflow-nano-video-toast';
  const DISABLED_ATTR = 'data-nano-video-disabled';
  const MODE_ATTR = 'data-pakflow-mode';
  const LOCKED_TITLE = "Open Google Flow — You're in Images tab";
  const VIDEO_ACTION_RE = /^(?:animate|create video|generate video|make video|turn into video|text to video|frames to video|ingredients to video)$/i;

  let isNanoMode = false;
  let rafScheduled = false;
  let observer = null;
  let lastImageClickTs = 0;
  let lastAutoOpenTs = 0;
  let lastToastTriggerTs = 0;
  let toastHideTimer = null;
  let toastBumpTimer = null;
  let toastFromPopover = false;
  let autoOpenedPopover = false;
  let autoSwitchPath = '';
  let autoSwitchAttempts = 0;

  function ensureStyleInjected() {
    if (document.getElementById(STYLE_ID)) return;
    const target = document.head || document.documentElement;
    if (!target) return;
    const style = document.createElement('style');
    style.id = STYLE_ID;
    style.textContent =
      'html[' + MODE_ATTR + '="nano_banana"] [' + DISABLED_ATTR + '="true"] {' +
      '  opacity: 0.45 !important;' +
      '  filter: grayscale(1) !important;' +
      '  cursor: not-allowed !important;' +
      '  pointer-events: auto !important;' +
      '  user-select: none !important;' +
      '}' +
      'html[' + MODE_ATTR + '="nano_banana"] [' + DISABLED_ATTR + '="true"] * {' +
      '  pointer-events: none !important;' +
      '  cursor: not-allowed !important;' +
      '}' +
      '#' + TOAST_ID + ' {' +
      '  position: fixed !important;' +
      '  z-index: 2147483647 !important;' +
      '  pointer-events: none !important;' +
      '  display: flex !important;' +
      '  align-items: center !important;' +
      '  gap: 9px !important;' +
      '  padding: 10px 16px !important;' +
      '  border-radius: 12px !important;' +
      '  background: linear-gradient(135deg, rgba(28, 29, 34, 0.98), rgba(17, 18, 22, 0.98)) !important;' +
      '  border: 1px solid rgba(251, 191, 36, 0.6) !important;' +
      '  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(251, 191, 36, 0.16) !important;' +
      '  color: #f9fafb !important;' +
      '  font-family: "Google Sans", "Google Sans Text", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;' +
      '  font-size: 13.5px !important;' +
      '  font-weight: 600 !important;' +
      '  line-height: 1.35 !important;' +
      '  letter-spacing: 0.1px !important;' +
      '  white-space: nowrap !important;' +
      '  user-select: none !important;' +
      '  opacity: 0 !important;' +
      '  visibility: hidden !important;' +
      '  transform: translateY(6px) scale(0.96) !important;' +
      '  transition: opacity 0.18s cubic-bezier(0.16, 1, 0.3, 1), transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), visibility 0.18s !important;' +
      '}' +
      '#' + TOAST_ID + '[data-visible="true"] {' +
      '  opacity: 1 !important;' +
      '  visibility: visible !important;' +
      '  transform: translateY(0) scale(1) !important;' +
      '}' +
      '#' + TOAST_ID + '[data-bump="true"] {' +
      '  animation: pfNanoToastBump 0.28s ease !important;' +
      '}' +
      '#' + TOAST_ID + ' .pf-nano-toast-badge {' +
      '  display: inline-flex !important;' +
      '  align-items: center !important;' +
      '  justify-content: center !important;' +
      '  width: 22px !important;' +
      '  height: 22px !important;' +
      '  border-radius: 7px !important;' +
      '  background: rgba(251, 191, 36, 0.16) !important;' +
      '  border: 1px solid rgba(251, 191, 36, 0.35) !important;' +
      '  font-size: 12px !important;' +
      '  flex-shrink: 0 !important;' +
      '}' +
      '#' + TOAST_ID + ' .pf-nano-toast-accent {' +
      '  color: #fbbf24 !important;' +
      '  font-weight: 700 !important;' +
      '}' +
      '@keyframes pfNanoToastBump {' +
      '  0%, 100% { transform: translateX(0) scale(1); }' +
      '  25% { transform: translateX(-4px) scale(1.02); }' +
      '  75% { transform: translateX(4px) scale(1.02); }' +
      '}';
    pauseObserver();
    target.appendChild(style);
    resumeObserver();
  }

  function ensureToastElement() {
    let toast = document.getElementById(TOAST_ID);
    if (toast) return toast;
    const host = document.body || document.documentElement;
    if (!host) return null;

    toast = document.createElement('div');
    toast.id = TOAST_ID;
    toast.setAttribute('data-visible', 'false');

    const badge = document.createElement('span');
    badge.className = 'pf-nano-toast-badge';
    badge.setAttribute('aria-hidden', 'true');
    badge.textContent = '🔒';

    const textWrap = document.createElement('span');
    const accent = document.createElement('span');
    accent.className = 'pf-nano-toast-accent';
    accent.textContent = 'Open Google Flow';

    const rest = document.createElement('span');
    rest.textContent = " — You're in Images tab";

    textWrap.appendChild(accent);
    textWrap.appendChild(rest);
    toast.appendChild(badge);
    toast.appendChild(textWrap);

    pauseObserver();
    host.appendChild(toast);
    resumeObserver();
    return toast;
  }

  function hideVideoLockedToast() {
    if (toastHideTimer) {
      clearTimeout(toastHideTimer);
      toastHideTimer = null;
    }
    toastFromPopover = false;
    const toast = document.getElementById(TOAST_ID);
    if (toast) {
      setAttrIfChanged(toast, 'data-visible', 'false');
      removeAttrIfPresent(toast, 'data-bump');
    }
  }

  function showVideoLockedToast(anchorEl) {
    const now = Date.now();
    if (now - lastToastTriggerTs < 160) return;
    lastToastTriggerTs = now;

    ensureStyleInjected();
    const toast = ensureToastElement();
    if (!toast) return;

    const popperOrGroup =
      (anchorEl && anchorEl.closest && anchorEl.closest('[data-radix-popper-content-wrapper], [role="dialog"], [role="tablist"]')) ||
      anchorEl;

    toastFromPopover = !!(anchorEl && anchorEl.closest && anchorEl.closest('[data-radix-popper-content-wrapper], [role="dialog"], [role="tablist"]'));

    const vw = window.innerWidth || (document.documentElement && document.documentElement.clientWidth) || 1280;
    const vh = window.innerHeight || (document.documentElement && document.documentElement.clientHeight) || 800;
    const tw = toast.offsetWidth || 320;
    const th = toast.offsetHeight || 42;

    let left = Math.max(12, Math.round((vw - tw) / 2));
    let top = 24;

    const ar = popperOrGroup && typeof popperOrGroup.getBoundingClientRect === 'function' ? popperOrGroup.getBoundingClientRect() : null;
    const br = anchorEl && typeof anchorEl.getBoundingClientRect === 'function' ? anchorEl.getBoundingClientRect() : null;
    const ref = ar && ar.width > 0 && ar.height > 0 ? ar : br;

    if (ref && ref.width > 0 && ref.height > 0) {
      left = Math.round(ref.left + (ref.width - tw) / 2);
      left = Math.max(12, Math.min(left, Math.max(12, vw - tw - 12)));
      top = Math.round(ref.top - th - 12);
      if (top < 12) {
        top = Math.min(Math.max(12, vh - th - 12), Math.round(ref.bottom + 12));
      }
    }

    if (toast.style) {
      toast.style.left = left + 'px';
      toast.style.top = top + 'px';
    }

    const alreadyVisible = toast.getAttribute('data-visible') === 'true';
    setAttrIfChanged(toast, 'data-visible', 'true');

    if (alreadyVisible) {
      removeAttrIfPresent(toast, 'data-bump');
      setAttrIfChanged(toast, 'data-bump', 'true');
      if (toastBumpTimer) clearTimeout(toastBumpTimer);
      toastBumpTimer = setTimeout(() => {
        removeAttrIfPresent(toast, 'data-bump');
      }, 300);
    }

    if (toastHideTimer) clearTimeout(toastHideTimer);
    toastHideTimer = setTimeout(() => {
      hideVideoLockedToast();
    }, 3200);
  }

  function pauseObserver() {
    if (!observer) return;
    observer.disconnect();
  }

  function resumeObserver() {
    if (!observer) return;
    const root = document.documentElement || document;
    if (!root) return;
    observer.takeRecords();
    observer.observe(root, { childList: true, subtree: true });
  }

  function setAttrIfChanged(el, name, val) {
    if (el.getAttribute(name) !== val) {
      el.setAttribute(name, val);
    }
  }

  function removeAttrIfPresent(el, name) {
    if (el.hasAttribute(name)) {
      el.removeAttribute(name);
    }
  }

  function extractVisibleLabel(el) {
    if (!el) return '';
    const raw = (el.textContent || '').replace(/\s+/g, ' ').trim();
    if (/^(image|images|video|videos)$/i.test(raw)) {
      return raw.toLowerCase();
    }
    let out = '';
    const stack = [el];
    while (stack.length) {
      const node = stack.pop();
      if (!node) continue;
      if (node.nodeType === 3) {
        out += ' ' + node.nodeValue;
        continue;
      }
      if (node.nodeType !== 1) continue;
      const tag = (node.tagName || '').toUpperCase();
      if (tag === 'SVG' || tag === 'I' || tag === 'STYLE' || tag === 'SCRIPT') continue;
      if (node.getAttribute && node.getAttribute('aria-hidden') === 'true') continue;
      const cls = typeof node.className === 'string' ? node.className : '';
      if (cls && /(?:google-symbols|material-icons|material-symbols)/i.test(cls)) continue;
      const children = node.childNodes;
      for (let i = children.length - 1; i >= 0; i--) {
        stack.push(children[i]);
      }
    }
    let cleaned = out.replace(/\s+/g, ' ').trim().toLowerCase();
    cleaned = cleaned
      .replace(
        /^(?:image|imagesmode|photo|photo_library|crop_original|panorama|videocam|video_library|movie|smart_display|video_camera_back|cinematic_blur)\s*(?=(?:image|images|video|videos)$)/i,
        ''
      )
      .trim();
    return cleaned;
  }

  function getTabKind(el) {
    const label = extractVisibleLabel(el);
    if (label === 'image' || label === 'images') return 'image';
    if (label === 'video' || label === 'videos') return 'video';
    return '';
  }

  function isVideoActionControl(el) {
    if (!el) return false;
    const label = extractVisibleLabel(el);
    return VIDEO_ACTION_RE.test(label);
  }

  function isTabActive(btn) {
    if (!btn) return false;
    if (btn.getAttribute('aria-selected') === 'true') return true;
    if (btn.getAttribute('aria-checked') === 'true') return true;
    if (btn.getAttribute('aria-pressed') === 'true') return true;
    const ds = (btn.getAttribute('data-state') || '').toLowerCase();
    if (ds === 'active' || ds === 'on' || ds === 'checked') return true;
    if (btn.classList && (btn.classList.contains('mdc-tab--active') || btn.classList.contains('mat-button-toggle-checked'))) {
      return true;
    }
    const parentToggle = btn.closest && btn.closest('mat-button-toggle');
    if (parentToggle && parentToggle.classList.contains('mat-button-toggle-checked')) {
      return true;
    }
    return false;
  }

  function findCounterpartImageButton(videoBtn, allImageBtns) {
    let anc = videoBtn.parentElement;
    for (let depth = 0; depth < 3 && anc && anc.tagName !== 'BODY'; depth++) {
      for (let i = 0; i < allImageBtns.length; i++) {
        if (anc.contains(allImageBtns[i])) {
          return allImageBtns[i];
        }
      }
      anc = anc.parentElement;
    }
    return null;
  }

  function findSiblingActiveNonVideoTab(videoBtn) {
    const group =
      (videoBtn.closest && videoBtn.closest('[role="tablist"], [role="radiogroup"], [role="group"], mat-button-toggle-group')) ||
      videoBtn.parentElement;
    if (!group) return false;
    const items = group.querySelectorAll('button, [role="tab"], [role="radio"]');
    for (let i = 0; i < items.length; i++) {
      const item = items[i];
      if (item === videoBtn) continue;
      if (getTabKind(item) === 'video') continue;
      if (isTabActive(item)) return true;
    }
    return false;
  }

  function collectModeControls() {
    const nodes = document.querySelectorAll('button, [role="tab"], [role="radio"], [role="menuitem"], [role="option"]');
    const imageBtns = [];
    const videoCands = [];
    const extraVideoActions = [];

    for (let i = 0; i < nodes.length; i++) {
      const el = nodes[i];
      const role = (el.getAttribute('role') || '').toLowerCase();
      if (isVideoActionControl(el)) {
        extraVideoActions.push(el);
        continue;
      }
      if (role === 'menuitem' || role === 'option') {
        continue;
      }
      const kind = getTabKind(el);
      if (kind === 'image') imageBtns.push(el);
      else if (kind === 'video') videoCands.push(el);
    }

    const confirmedVideoTabs = [];
    for (let i = 0; i < videoCands.length; i++) {
      const vBtn = videoCands[i];
      const pairedImage = findCounterpartImageButton(vBtn, imageBtns);
      const role = (vBtn.getAttribute('role') || '').toLowerCase();
      const inPopoverOrTabGroup =
        role === 'tab' ||
        role === 'radio' ||
        !!(vBtn.closest && vBtn.closest('[role="tablist"], [role="radiogroup"], mat-button-toggle, [data-radix-popper-content-wrapper], [role="dialog"]'));
      if (pairedImage || inPopoverOrTabGroup) {
        confirmedVideoTabs.push({ videoBtn: vBtn, imageBtn: pairedImage });
      }
    }

    return { imageBtns, confirmedVideoTabs, extraVideoActions };
  }

  function simulateRealClick(el) {
    if (!el) return;
    try {
      el.focus();
    } catch {}
    const init = { bubbles: true, cancelable: true, view: window, button: 0, buttons: 1 };
    const types = ['pointerover', 'pointerenter', 'pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click'];
    for (let i = 0; i < types.length; i++) {
      const type = types[i];
      try {
        const Ctor = type.indexOf('pointer') === 0 && typeof PointerEvent === 'function' ? PointerEvent : MouseEvent;
        el.dispatchEvent(new Ctor(type, init));
      } catch {
        try {
          el.dispatchEvent(new MouseEvent(type, init));
        } catch {}
      }
    }
  }

  function dispatchEscape() {
    try {
      document.dispatchEvent(
        new KeyboardEvent('keydown', { key: 'Escape', code: 'Escape', keyCode: 27, which: 27, bubbles: true, cancelable: true })
      );
    } catch {}
  }

  function disableElement(el) {
    // Keep native el.disabled = false so Chromium fires pointerdown/click events reliably (blocked in capture phase + shows toast)
    if ('disabled' in el && el.disabled === true && el.hasAttribute(DISABLED_ATTR)) {
      el.disabled = false;
    }
    setAttrIfChanged(el, DISABLED_ATTR, 'true');
    setAttrIfChanged(el, 'aria-disabled', 'true');
    setAttrIfChanged(el, 'title', LOCKED_TITLE);
    if (el.tabIndex !== -1) {
      el.tabIndex = -1;
    }
  }

  function restoreDisabledElements() {
    hideVideoLockedToast();
    const html = document.documentElement;
    if (html) removeAttrIfPresent(html, MODE_ATTR);
    const locked = document.querySelectorAll('[' + DISABLED_ATTR + '="true"]');
    for (let i = 0; i < locked.length; i++) {
      const el = locked[i];
      removeAttrIfPresent(el, DISABLED_ATTR);
      removeAttrIfPresent(el, 'aria-disabled');
      const curTitle = el.getAttribute('title');
      if (curTitle === LOCKED_TITLE || curTitle === 'Video option is disabled in Nano Banana Only mode') {
        el.removeAttribute('title');
      }
      if ('disabled' in el && el.disabled === true) {
        el.disabled = false;
      }
      if (el.getAttribute('tabindex') === '-1') {
        el.removeAttribute('tabindex');
      }
    }
  }

  function isCompactPromptPill(b) {
    if (!b || b.disabled || b.hasAttribute(DISABLED_ATTR)) return false;
    const role = (b.getAttribute('role') || '').toLowerCase();
    if (role === 'menuitem' || role === 'tab' || role === 'radio' || role === 'option') return false;
    if (b.closest && b.closest('.force-hide-radix, [role="menu"], [role="listbox"], [role="dialog"], [role="tablist"], [role="radiogroup"]')) {
      return false;
    }
    const txt = (b.textContent || '').replace(/\s+/g, ' ').trim();
    if (!txt || txt.length > 70) return false;
    if (typeof b.getBoundingClientRect === 'function') {
      const r = b.getBoundingClientRect();
      if (r.width < 20 || r.width > 380 || r.height < 14 || r.height > 72) return false;
    }
    return true;
  }

  function hasCompactImagePill() {
    const buttons = document.querySelectorAll('button');
    for (let i = 0; i < buttons.length; i++) {
      const b = buttons[i];
      if (!isCompactPromptPill(b)) continue;
      const txt = b.textContent || '';
      if (/\b(Nano|Imagen)\b/i.test(txt)) {
        return true;
      }
    }
    return false;
  }

  function findClosedVideoModePill() {
    const buttons = document.querySelectorAll('button');
    for (let i = 0; i < buttons.length; i++) {
      const b = buttons[i];
      if (!isCompactPromptPill(b)) continue;
      const txt = (b.textContent || '').replace(/\s+/g, ' ').trim();
      if (/\b(Nano|Imagen)\b/i.test(txt)) continue;
      const looksLikeVideoModel =
        /\b(Veo|Omni)\b/i.test(txt) ||
        txt.indexOf('Lower Priority') !== -1 ||
        (/\bVideo\b/i.test(txt) && /\b(4s|6s|8s|10s|16:9|9:16|4:3|3:4|1:1|x1|x2|x3|x4|1x|2x|3x|4x)\b/i.test(txt));
      if (looksLikeVideoModel) return b;
    }
    return null;
  }

  function isOnFlowStudio() {
    const host = location.hostname || '';
    if (!host.includes('flow.google.com') && !host.includes('labs.google')) return false;
    if (/^\/about(?:\/|$)/i.test(location.pathname || '')) return false;
    return true;
  }

  function enforceNanoImageOnly() {
    if (!isNanoMode) {
      restoreDisabledElements();
      return;
    }
    if (!isOnFlowStudio()) return;

    const html = document.documentElement;
    if (html) setAttrIfChanged(html, MODE_ATTR, 'nano_banana');
    ensureStyleInjected();

    if (autoSwitchPath !== location.pathname) {
      autoSwitchPath = location.pathname;
      autoSwitchAttempts = 0;
      autoOpenedPopover = false;
    }

    const now = Date.now();
    const { confirmedVideoTabs, extraVideoActions } = collectModeControls();

    if (toastFromPopover && confirmedVideoTabs.length === 0) {
      hideVideoLockedToast();
    }

    // 1. Switch away from Video tab BEFORE locking it if Video is currently active (or no tab in the pair is active)
    for (let i = 0; i < confirmedVideoTabs.length; i++) {
      const { videoBtn, imageBtn } = confirmedVideoTabs[i];
      if (imageBtn && !isTabActive(imageBtn)) {
        const videoActive = isTabActive(videoBtn);
        const otherActive = findSiblingActiveNonVideoTab(videoBtn);
        if ((videoActive || !otherActive) && now - lastImageClickTs > 220) {
          lastImageClickTs = now;
          simulateRealClick(imageBtn);
        }
      }
      disableElement(videoBtn);
    }

    for (let i = 0; i < extraVideoActions.length; i++) {
      disableElement(extraVideoActions[i]);
    }

    const imageNowActive =
      confirmedVideoTabs.some(p => p.imageBtn && isTabActive(p.imageBtn)) ||
      (hasCompactImagePill() && !findClosedVideoModePill());

    if (imageNowActive) {
      autoSwitchAttempts = 0;
    }

    // 2. If we auto-opened the popover to escape Video mode and Image is now active, close the popover cleanly
    if (autoOpenedPopover && confirmedVideoTabs.length > 0 && imageNowActive) {
      autoOpenedPopover = false;
      setTimeout(() => {
        if (isNanoMode) dispatchEscape();
      }, 120);
      return;
    }

    // 3. If popover is closed and the prompt bar is in Video mode (Veo/Omni/Video pill), auto-open popover to switch to Image
    if (confirmedVideoTabs.length === 0 && !hasCompactImagePill()) {
      const videoPill = findClosedVideoModePill();
      if (videoPill && autoSwitchAttempts < 5 && now - lastAutoOpenTs > 1200) {
        autoSwitchAttempts++;
        lastAutoOpenTs = now;
        autoOpenedPopover = true;
        simulateRealClick(videoPill);
      }
    }
  }

  function scheduleEnforce() {
    if (rafScheduled) return;
    rafScheduled = true;
    const run = () => {
      rafScheduled = false;
      enforceNanoImageOnly();
    };
    if (typeof requestAnimationFrame === 'function') {
      requestAnimationFrame(run);
    } else {
      setTimeout(run, 16);
    }
  }

  function findTargetBlockedVideoEl(e) {
    const t = e.target;
    if (!t || typeof t.closest !== 'function') return null;
    const direct = t.closest('[' + DISABLED_ATTR + '="true"]');
    if (direct) return direct;

    const btn = t.closest('button, [role="tab"], [role="radio"], [role="menuitem"], [role="option"]');
    if (btn) {
      if (isVideoActionControl(btn)) return btn;
      if (getTabKind(btn) === 'video') {
        const { confirmedVideoTabs } = collectModeControls();
        for (let i = 0; i < confirmedVideoTabs.length; i++) {
          if (confirmedVideoTabs[i].videoBtn === btn) return btn;
        }
      }
    }

    // Coordinate fallback in case a parent container intercepts hit-testing
    if (typeof e.clientX === 'number' && typeof e.clientY === 'number' && (e.clientX !== 0 || e.clientY !== 0)) {
      const group = t.closest('[role="tablist"], [role="radiogroup"], [data-radix-popper-content-wrapper], [role="dialog"]');
      if (group) {
        const disabledInside = group.querySelectorAll('[' + DISABLED_ATTR + '="true"]');
        for (let i = 0; i < disabledInside.length; i++) {
          const el = disabledInside[i];
          if (typeof el.getBoundingClientRect !== 'function') continue;
          const r = el.getBoundingClientRect();
          if (r.width > 0 && r.height > 0 && e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom) {
            return el;
          }
        }
      }
    }
    return null;
  }

  function isCurrentlyStuckInVideoMode() {
    const { imageBtns, confirmedVideoTabs } = collectModeControls();
    for (let i = 0; i < confirmedVideoTabs.length; i++) {
      if (isTabActive(confirmedVideoTabs[i].videoBtn)) return true;
    }
    for (let i = 0; i < imageBtns.length; i++) {
      if (isTabActive(imageBtns[i])) return false;
    }
    if (findClosedVideoModePill()) return true;
    return false;
  }

  function blockEvent(e) {
    e.preventDefault();
    e.stopPropagation();
    if (typeof e.stopImmediatePropagation === 'function') {
      e.stopImmediatePropagation();
    }
  }

  // Capture-phase pointer/mouse/click/touch blockers (runs before Radix UI & React)
  const POINTER_EVENTS = ['pointerdown', 'mousedown', 'pointerup', 'mouseup', 'click', 'dblclick', 'auxclick', 'touchstart', 'touchend', 'contextmenu'];
  for (let i = 0; i < POINTER_EVENTS.length; i++) {
    window.addEventListener(
      POINTER_EVENTS[i],
      (e) => {
        if (!isNanoMode || !isOnFlowStudio()) return;
        const blockedVideo = findTargetBlockedVideoEl(e);
        if (blockedVideo) {
          blockEvent(e);
          if (e.type === 'pointerdown' || e.type === 'mousedown' || e.type === 'click' || e.type === 'touchstart') {
            showVideoLockedToast(blockedVideo);
          }
          scheduleEnforce();
          return;
        }
        // Guard submit button if still transitioning out of Video mode
        if (e.type === 'click' || e.type === 'pointerdown' || e.type === 'mousedown') {
          const btn = e.target && e.target.closest && e.target.closest('button');
          if (btn && (btn.textContent || '').indexOf('arrow_forward') !== -1 && isCurrentlyStuckInVideoMode()) {
            blockEvent(e);
            scheduleEnforce();
          }
        }
      },
      true
    );
  }

  // Capture-phase keyboard blocker (prevents ArrowLeft/ArrowRight roving focus onto Video tab & Enter submit in Video mode)
  window.addEventListener(
    'keydown',
    (e) => {
      if (!isNanoMode || !isOnFlowStudio()) return;
      const key = e.key || '';
      if (key === 'Escape') {
        hideVideoLockedToast();
      }
      const active = (e.target && e.target.nodeType === 1 ? e.target : null) || document.activeElement;
      if (active && typeof active.closest === 'function') {
        const directLocked = active.closest('[' + DISABLED_ATTR + '="true"]');
        if (directLocked) {
          if (key !== 'Escape' && key !== 'Tab') {
            blockEvent(e);
            showVideoLockedToast(directLocked);
            scheduleEnforce();
            return;
          }
        }
        if (key === 'ArrowRight' || key === 'ArrowLeft' || key === 'ArrowUp' || key === 'ArrowDown' || key === 'End' || key === 'Home') {
          const group = active.closest('[role="tablist"], [role="radiogroup"], mat-button-toggle-group');
          if (group) {
            const lockedVid = group.querySelector('[' + DISABLED_ATTR + '="true"]');
            if (lockedVid) {
              blockEvent(e);
              showVideoLockedToast(lockedVid);
              return;
            }
          }
        }
      }
      if ((key === 'Enter' || e.keyCode === 13) && isCurrentlyStuckInVideoMode()) {
        blockEvent(e);
        scheduleEnforce();
      }
    },
    true
  );

  try {
    chrome.storage.local.get(['launchMode'], (st) => {
      void chrome.runtime?.lastError;
      isNanoMode = st?.launchMode === 'nano_banana';
      scheduleEnforce();
      if (isNanoMode) {
        setTimeout(scheduleEnforce, 400);
        setTimeout(scheduleEnforce, 1200);
        setTimeout(scheduleEnforce, 2500);
      }
    });

    chrome.storage.onChanged.addListener((changes, area) => {
      if (area === 'local' && changes.launchMode) {
        isNanoMode = changes.launchMode.newValue === 'nano_banana';
        autoSwitchPath = '';
        autoSwitchAttempts = 0;
        autoOpenedPopover = false;
        scheduleEnforce();
      }
    });

    observer = new MutationObserver(() => {
      if (isNanoMode) {
        scheduleEnforce();
      }
    });
    resumeObserver();

    // Lightweight fallback poll while in Nano mode (catches non-DOM-tree state changes)
    setInterval(() => {
      if (isNanoMode && isOnFlowStudio()) {
        enforceNanoImageOnly();
      }
    }, 600);
  } catch {}
})();
