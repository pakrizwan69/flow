/* PakFlow Admin & Reseller UI Enhancer
 * - Clean, responsive Zinc UI integration for /admin and /reseller
 * - Session Shuffle with Live Countdown Timer + Instant Reshuffle Now + Active Session Statuses
 * - Extension Builder & Upload (updates manifest.json + popup.html + scripts)
 * - Flow Access Lock Policy
 * - Editable Welcome Message Templates + Automatic 1-Click Copy Modal on User Creation
 */
(() => {
  'use strict';
  if (window.__pakflowAdminEnhancerLoaded) return;
  window.__pakflowAdminEnhancerLoaded = true;

  const DEFAULT_TEMPLATE =
    'Welcome to PakFlow!\n\n' +
    'Portal Link: {{site_link}}\n' +
    'Name: {{name}}\n' +
    'Email: {{email}}\n' +
    'Password: {{password}}\n' +
    'Plan: {{plan}} ({{days}} days)\n\n' +
    'Sign in on the portal, download the latest Chrome extension from the top bar, and launch Google Flow in 1 click.\n' +
    'Support: {{whatsapp}}';

  const PLACEHOLDERS = [
    '{{site_link}}',
    '{{email}}',
    '{{password}}',
    '{{name}}',
    '{{plan}}',
    '{{days}}',
    '{{whatsapp}}'
  ];

  let cachedAdminTemplate = null;
  let cachedResellerTemplate = null;
  let cachedConfig = null;
  let activeRole = null; // 'admin' | 'reseller' | null
  let countdownInterval = null;
  let countdownState = { enabled: false, targetMs: 0, epoch: 1, intervalMinutes: 60, lastShuffledAt: null };
  let autoRefreshTriggeredForTarget = 0;

  const rawFetch = window.fetch.bind(window);

  function getAdminToken() {
    try { return localStorage.getItem('flowbypak_admin_token') || ''; } catch { return ''; }
  }
  function getResellerToken() {
    try { return localStorage.getItem('flowbypak_reseller_token') || ''; } catch { return ''; }
  }
  function getResellerData() {
    try {
      const raw = localStorage.getItem('flowbypak_reseller_data');
      return raw ? JSON.parse(raw) : null;
    } catch { return null; }
  }
  function isAdminOrResellerPath() {
    const p = (window.location.pathname || '').toLowerCase();
    return p.startsWith('/admin') || p.startsWith('/reseller');
  }
  function detectPanelRole() {
    const p = (window.location.pathname || '').toLowerCase();
    if (p.startsWith('/admin') && getAdminToken()) return 'admin';
    if (p.startsWith('/reseller') && getResellerToken()) return 'reseller';
    return null;
  }

  function formatCountdown(totalSec) {
    const sec = Math.max(0, Math.floor(totalSec));
    const h = Math.floor(sec / 3600);
    const m = Math.floor((sec % 3600) / 60);
    const s = sec % 60;
    const pad = (n) => (n < 10 ? '0' + n : String(n));
    return pad(h) + ':' + pad(m) + ':' + pad(s);
  }

  function formatWhatsappText(raw) {
    const s = String(raw || '').trim();
    if (!s) return 'Contact your reseller / admin';
    if (/^https?:\/\//i.test(s)) return s;
    const digits = s.replace(/\D/g, '');
    if (digits.length >= 9) return 'https://wa.me/' + digits;
    return s;
  }

  function renderMessageTemplate(tpl, vars) {
    const source = String(tpl || DEFAULT_TEMPLATE);
    const map = {
      '{{site_link}}': vars.site_link || window.location.origin,
      '{{email}}': vars.email || '',
      '{{password}}': vars.password || '',
      '{{name}}': vars.name || vars.email || 'User',
      '{{plan}}': String(vars.plan || 'Pro').toUpperCase(),
      '{{days}}': String(vars.days ?? 30),
      '{{whatsapp}}': formatWhatsappText(vars.whatsapp)
    };
    let out = source;
    for (const [k, v] of Object.entries(map)) {
      out = out.split(k).join(String(v));
    }
    return out;
  }

  async function apiFetch(url, options = {}, role = 'admin') {
    const tok = role === 'reseller' ? getResellerToken() : getAdminToken();
    const headers = Object.assign({ Accept: 'application/json' }, options.headers || {});
    if (tok) headers.Authorization = 'Bearer ' + tok;
    if (options.body && typeof options.body === 'string' && !headers['Content-Type']) {
      headers['Content-Type'] = 'application/json';
    }
    const res = await rawFetch(url, Object.assign({}, options, { headers }));
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
      throw new Error(data.message || data.error || ('Request failed (HTTP ' + res.status + ')'));
    }
    return data;
  }

  async function fetchTemplateForRole(role) {
    try {
      if (role === 'reseller') {
        const d = await apiFetch('/api/reseller/message-template', { method: 'GET' }, 'reseller');
        cachedResellerTemplate = d.template || DEFAULT_TEMPLATE;
        return { template: cachedResellerTemplate, whatsapp: d.whatsapp || (getResellerData()?.whatsapp || '') };
      } else {
        const d = await apiFetch('/api/admin/controls/message-template', { method: 'GET' }, 'admin');
        cachedAdminTemplate = d.template || DEFAULT_TEMPLATE;
        let wa = '';
        try {
          const pub = await rawFetch('/api/settings/public').then(r => r.json()).catch(() => null);
          wa = pub?.whatsappSupport || pub?.whatsappNumber || '';
        } catch {}
        return { template: cachedAdminTemplate, whatsapp: wa };
      }
    } catch {
      return {
        template: (role === 'reseller' ? cachedResellerTemplate : cachedAdminTemplate) || DEFAULT_TEMPLATE,
        whatsapp: role === 'reseller' ? (getResellerData()?.whatsapp || '') : ''
      };
    }
  }

  // ── 1. Live Countdown Engine (Header Pill + Controls Modal Clock) ──
  function applyCountdownFromShuffle(sh) {
    if (!sh || typeof sh !== 'object') return;
    const enabled = Boolean(sh.enabled);
    const epoch = Number(sh.shuffleEpoch || 1);
    const intervalMinutes = Number(sh.intervalMinutes || 60);
    let targetMs = 0;
    if (enabled) {
      if (typeof sh.remainingSeconds === 'number') {
        targetMs = Date.now() + Math.max(0, sh.remainingSeconds) * 1000;
      } else if (sh.nextShuffleTs) {
        targetMs = Number(sh.nextShuffleTs) * 1000;
      } else if (sh.nextShuffleAt) {
        targetMs = Date.parse(sh.nextShuffleAt) || 0;
      }
    }
    countdownState = {
      enabled,
      targetMs,
      epoch,
      intervalMinutes,
      lastShuffledAt: sh.lastShuffledAt || null
    };
    renderCountdownTick();
    if (!countdownInterval) {
      countdownInterval = setInterval(renderCountdownTick, 1000);
    }
  }

  function renderCountdownTick() {
    const hdrPill = document.getElementById('pfHeaderCountdownPill');
    const hdrText = document.getElementById('pfHeaderCountdownText');
    const modalClock = document.getElementById('pfLiveCountdownClock');
    const modalMeta = document.getElementById('pfLiveCountdownMeta');

    if (!countdownState.enabled || !countdownState.targetMs) {
      if (hdrPill) hdrPill.style.display = 'none';
      if (modalClock) {
        modalClock.textContent = 'Timer Off';
        modalClock.classList.add('is-off');
      }
      if (modalMeta) {
        modalMeta.textContent =
          'Epoch #' + countdownState.epoch +
          (countdownState.lastShuffledAt ? (' · Last reshuffled ' + new Date(countdownState.lastShuffledAt).toLocaleTimeString()) : ' · Enable timer below to auto-rotate sessions');
      }
      return;
    }

    const leftSec = Math.max(0, Math.ceil((countdownState.targetMs - Date.now()) / 1000));
    const formatted = formatCountdown(leftSec);

    if (hdrPill && hdrText) {
      hdrPill.style.display = 'inline-flex';
      hdrText.textContent = 'Shuffle ' + formatted;
    }
    if (modalClock) {
      modalClock.textContent = formatted;
      modalClock.classList.remove('is-off');
    }
    if (modalMeta) {
      modalMeta.textContent =
        'Epoch #' + countdownState.epoch +
        ' · Every ' + countdownState.intervalMinutes + ' min' +
        (countdownState.lastShuffledAt ? (' · Last reshuffle ' + new Date(countdownState.lastShuffledAt).toLocaleTimeString()) : '');
    }

    if (leftSec <= 0 && autoRefreshTriggeredForTarget !== countdownState.targetMs) {
      autoRefreshTriggeredForTarget = countdownState.targetMs;
      setTimeout(() => {
        if (detectPanelRole() === 'admin') {
          refreshShuffleStateSilently();
        }
      }, 1200);
    }
  }

  async function refreshShuffleStateSilently() {
    try {
      const cfg = await apiFetch('/api/admin/controls/config', { method: 'GET' }, 'admin');
      cachedConfig = cfg;
      if (cfg && cfg.serverShuffle) applyCountdownFromShuffle(cfg.serverShuffle);
      const modal = document.getElementById('pfControlsModal');
      if (modal && !modal.classList.contains('pf-hidden')) {
        populateAdminControlsFromConfig(cfg);
      }
    } catch {}
  }

  // ── 2. Intercept User Creation (Admin & Reseller) to pop up Copy Welcome Message Modal ──
  window.fetch = async function (input, init) {
    let urlStr = '';
    let method = 'GET';
    let submittedBody = null;
    try {
      urlStr = typeof input === 'string' ? input : (input && input.url ? input.url : String(input));
      method = String((init && init.method) || (typeof input !== 'string' && input && input.method) || 'GET').toUpperCase();
      const isCreateAdminUser = method === 'POST' && /\/api\/admin\/users\/?(?:\?|$)/i.test(urlStr);
      const isCreateResellerUser = method === 'POST' && /\/api\/reseller\/users\/?(?:\?|$)/i.test(urlStr);
      if ((isCreateAdminUser || isCreateResellerUser) && init && typeof init.body === 'string') {
        submittedBody = JSON.parse(init.body);
      }
    } catch {}

    const resp = await rawFetch(input, init);

    try {
      const isCreateAdminUser = method === 'POST' && /\/api\/admin\/users\/?(?:\?|$)/i.test(urlStr);
      const isCreateResellerUser = method === 'POST' && /\/api\/reseller\/users\/?(?:\?|$)/i.test(urlStr);
      if ((isCreateAdminUser || isCreateResellerUser) && resp && resp.ok) {
        const role = isCreateResellerUser ? 'reseller' : 'admin';
        resp.clone().json().then(async (data) => {
          const created = data?.user || data || {};
          const email = created.email || submittedBody?.email || '';
          const password = submittedBody?.password || created.password || '';
          if (!email) return;
          const name = created.name || created.username || submittedBody?.name || submittedBody?.username || email.split('@')[0];
          const plan = created.plan || submittedBody?.plan || 'Pro';
          const days = created.daysRemaining ?? created.days ?? submittedBody?.days ?? submittedBody?.planDays ?? 30;
          const tplInfo = await fetchTemplateForRole(role);
          openWelcomeCopyModal({
            role,
            template: tplInfo.template,
            vars: {
              site_link: window.location.origin,
              email,
              password,
              name,
              plan,
              days,
              whatsapp: tplInfo.whatsapp || ''
            }
          });
        }).catch(() => {});
      }
    } catch {}

    return resp;
  };

  // ── 3. Welcome Message Copy Modal (Admin & Reseller) ──
  function ensureWelcomeCopyModal() {
    let el = document.getElementById('pfWelcomeCopyModal');
    if (el) return el;
    el = document.createElement('div');
    el.id = 'pfWelcomeCopyModal';
    el.className = 'pf-enh-backdrop pf-hidden';
    el.innerHTML =
      '<div class="pf-enh-modal pf-modal-sm" role="dialog" aria-modal="true">' +
        '<div class="pf-enh-head">' +
          '<div>' +
            '<h3>User Created — Copy Welcome Message</h3>' +
            '<p id="pfWelcomeSub">Copy &amp; send credentials to the user</p>' +
          '</div>' +
          '<button type="button" class="pf-enh-close" id="pfWelcomeClose">&times;</button>' +
        '</div>' +
        '<div class="pf-enh-body">' +
          '<div id="pfWelcomeStatus" class="pf-status-banner"></div>' +
          '<div id="pfWelcomeCopyPane">' +
            '<div class="pf-field">' +
              '<label>Ready-to-Send Message</label>' +
              '<textarea id="pfWelcomeRendered" class="pf-textarea" rows="9"></textarea>' +
            '</div>' +
            '<div class="pf-row" style="margin-top:14px;">' +
              '<button type="button" class="pf-btn pf-btn-emerald" id="pfCopyMsgBtn" style="flex:1;">Copy Message</button>' +
              '<a id="pfWaShareBtn" class="pf-btn pf-btn-primary" href="#" target="_blank" rel="noopener noreferrer">WhatsApp</a>' +
              '<button type="button" class="pf-btn pf-btn-ghost" id="pfToggleEditTplBtn">Edit Template</button>' +
            '</div>' +
          '</div>' +
          '<div id="pfWelcomeEditPane" style="display:none;">' +
            '<div class="pf-field">' +
              '<label>Edit Saved Template (Click variable to insert)</label>' +
              '<div class="pf-chip-list" id="pfQuickVarChips"></div>' +
              '<textarea id="pfQuickTplInput" class="pf-textarea" rows="8"></textarea>' +
            '</div>' +
            '<div class="pf-row" style="margin-top:12px;">' +
              '<button type="button" class="pf-btn pf-btn-primary" id="pfSaveQuickTplBtn" style="flex:1;">Save Template &amp; Apply</button>' +
              '<button type="button" class="pf-btn pf-btn-ghost" id="pfCancelQuickTplBtn">Back</button>' +
            '</div>' +
          '</div>' +
        '</div>' +
      '</div>';
    document.body.appendChild(el);

    el.addEventListener('click', (e) => {
      if (e.target === el) el.classList.add('pf-hidden');
    });
    el.querySelector('#pfWelcomeClose').addEventListener('click', () => el.classList.add('pf-hidden'));

    const chipsWrap = el.querySelector('#pfQuickVarChips');
    const quickInput = el.querySelector('#pfQuickTplInput');
    PLACEHOLDERS.forEach((ph) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'pf-chip';
      b.textContent = ph;
      b.addEventListener('click', () => insertAtCursor(quickInput, ph));
      chipsWrap.appendChild(b);
    });

    return el;
  }

  function insertAtCursor(textarea, text) {
    if (!textarea) return;
    const start = textarea.selectionStart ?? textarea.value.length;
    const end = textarea.selectionEnd ?? textarea.value.length;
    textarea.value = textarea.value.slice(0, start) + text + textarea.value.slice(end);
    textarea.focus();
    const pos = start + text.length;
    textarea.setSelectionRange(pos, pos);
    textarea.dispatchEvent(new Event('input'));
  }

  function setBanner(el, msg, isErr) {
    if (!el) return;
    if (!msg) {
      el.className = 'pf-status-banner';
      el.textContent = '';
      return;
    }
    el.className = 'pf-status-banner show ' + (isErr ? 'err' : 'ok');
    el.textContent = msg;
  }

  function openWelcomeCopyModal(ctx) {
    const modal = ensureWelcomeCopyModal();
    const statusEl = modal.querySelector('#pfWelcomeStatus');
    const renderedEl = modal.querySelector('#pfWelcomeRendered');
    const copyPane = modal.querySelector('#pfWelcomeCopyPane');
    const editPane = modal.querySelector('#pfWelcomeEditPane');
    const quickInput = modal.querySelector('#pfQuickTplInput');
    const copyBtn = modal.querySelector('#pfCopyMsgBtn');
    const waBtn = modal.querySelector('#pfWaShareBtn');
    const editToggleBtn = modal.querySelector('#pfToggleEditTplBtn');
    const saveTplBtn = modal.querySelector('#pfSaveQuickTplBtn');
    const cancelTplBtn = modal.querySelector('#pfCancelQuickTplBtn');

    modal.querySelector('h3').textContent = 'User Created — Copy Welcome Message';
    setBanner(statusEl, '', false);
    copyPane.style.display = 'block';
    editPane.style.display = 'none';

    const updateRendered = (tpl) => {
      const msg = renderMessageTemplate(tpl, ctx.vars);
      renderedEl.value = msg;
      waBtn.href = 'https://wa.me/?text=' + encodeURIComponent(msg);
    };

    modal.querySelector('#pfWelcomeSub').textContent = 'Account: ' + ctx.vars.email + ' (' + ctx.vars.plan + ')';
    quickInput.value = ctx.template || DEFAULT_TEMPLATE;
    updateRendered(quickInput.value);

    renderedEl.oninput = () => {
      waBtn.href = 'https://wa.me/?text=' + encodeURIComponent(renderedEl.value);
    };

    copyBtn.onclick = async () => {
      try {
        await navigator.clipboard.writeText(renderedEl.value);
        copyBtn.textContent = 'Copied';
        setBanner(statusEl, 'Welcome message copied to clipboard.', false);
        setTimeout(() => { copyBtn.textContent = 'Copy Message'; }, 2000);
      } catch {
        renderedEl.select();
        document.execCommand('copy');
        copyBtn.textContent = 'Copied';
        setTimeout(() => { copyBtn.textContent = 'Copy Message'; }, 2000);
      }
    };

    editToggleBtn.onclick = () => {
      setBanner(statusEl, '', false);
      copyPane.style.display = 'none';
      editPane.style.display = 'block';
    };

    cancelTplBtn.onclick = () => {
      copyPane.style.display = 'block';
      editPane.style.display = 'none';
    };

    saveTplBtn.onclick = async () => {
      const nextTpl = quickInput.value.trim();
      if (!nextTpl) return setBanner(statusEl, 'Template cannot be empty.', true);
      saveTplBtn.disabled = true;
      saveTplBtn.textContent = 'Saving...';
      try {
        const endpoint = ctx.role === 'reseller' ? '/api/reseller/message-template' : '/api/admin/controls/message-template';
        await apiFetch(endpoint, { method: 'PUT', body: JSON.stringify({ template: nextTpl }) }, ctx.role);
        if (ctx.role === 'reseller') cachedResellerTemplate = nextTpl;
        else cachedAdminTemplate = nextTpl;
        ctx.template = nextTpl;
        updateRendered(nextTpl);
        copyPane.style.display = 'block';
        editPane.style.display = 'none';
        setBanner(statusEl, 'Template saved & applied.', false);
      } catch (err) {
        setBanner(statusEl, err.message || 'Failed to save template', true);
      } finally {
        saveTplBtn.disabled = false;
        saveTplBtn.textContent = 'Save Template & Apply';
      }
    };

    modal.classList.remove('pf-hidden');
  }

  async function openResellerTemplateModal() {
    const tplInfo = await fetchTemplateForRole('reseller');
    openWelcomeCopyModal({
      role: 'reseller',
      template: tplInfo.template,
      vars: {
        site_link: window.location.origin,
        email: 'customer@example.com',
        password: 'SamplePassword123',
        name: 'Sample Customer',
        plan: 'PRO',
        days: 30,
        whatsapp: tplInfo.whatsapp || ''
      }
    });
    const modal = document.getElementById('pfWelcomeCopyModal');
    if (modal) {
      modal.querySelector('h3').textContent = 'Reseller Welcome Message Template';
      modal.querySelector('#pfWelcomeSub').textContent = 'Customize the message copied automatically whenever you add a user';
      modal.querySelector('#pfWelcomeCopyPane').style.display = 'none';
      modal.querySelector('#pfWelcomeEditPane').style.display = 'block';
    }
  }

  // ── 4. Admin Controls Modal (Extension Builder, Session Shuffle + Countdown, Access Lock, Welcome Template) ──
  function ensureAdminControlsModal() {
    let el = document.getElementById('pfControlsModal');
    if (el) return el;

    const poolItemsHtml = Array.from({ length: 10 }, (_, idx) => {
      const n = idx + 1;
      return (
        `<label class="pf-pool-item" data-session-row="${n}">` +
          `<span class="pf-pool-left"><input type="checkbox" name="pfShufflePool" value="${n}" /><span data-session-label="${n}">Session ${n}</span></span>` +
          `<span class="pf-pool-badge empty" data-session-badge="${n}">Checking...</span>` +
        `</label>`
      );
    }).join('');

    const defaultSlotOptions = Array.from({ length: 10 }, (_, idx) => {
      const n = idx + 1;
      return `<option value="${n}">Session ${n}</option>`;
    }).join('');

    el = document.createElement('div');
    el.id = 'pfControlsModal';
    el.className = 'pf-enh-backdrop pf-hidden';
    el.innerHTML =
      '<div class="pf-enh-modal" role="dialog" aria-modal="true">' +
        '<div class="pf-enh-head">' +
          '<div>' +
            '<h3>PakFlow Controls</h3>' +
            '<p>Extension Release · Session Shuffle &amp; Timer · Flow Access Lock · Welcome Template</p>' +
          '</div>' +
          '<button type="button" class="pf-enh-close" id="pfCtrlClose">&times;</button>' +
        '</div>' +
        '<div class="pf-enh-tabs">' +
          '<button type="button" class="pf-enh-tab active" data-tab="shuffle">Session Shuffle</button>' +
          '<button type="button" class="pf-enh-tab" data-tab="ext">Extension Builder</button>' +
          '<button type="button" class="pf-enh-tab" data-tab="lock">Access Lock</button>' +
          '<button type="button" class="pf-enh-tab" data-tab="tpl">Welcome Template</button>' +
        '</div>' +
        '<div class="pf-enh-body">' +
          '<div id="pfCtrlStatus" class="pf-status-banner"></div>' +

          /* TAB 1: SESSION SHUFFLE & DEFAULT SESSION */
          '<div class="pf-tab-pane" data-pane="shuffle">' +
            '<div class="pf-countdown-card">' +
              '<div>' +
                '<div class="pf-countdown-label">Next Automatic Session Shuffle</div>' +
                '<div class="pf-countdown-clock is-off" id="pfLiveCountdownClock">Timer Off</div>' +
                '<div class="pf-countdown-meta" id="pfLiveCountdownMeta">Epoch #1</div>' +
              '</div>' +
              '<button type="button" class="pf-btn pf-btn-amber" id="pfReshuffleNowBtn">Reshuffle Sessions Now</button>' +
            '</div>' +

            '<div class="pf-enh-section">' +
              '<h4>Auto Session Rotation &amp; Default Session</h4>' +
              '<p class="pf-enh-hint">Rotates users across active Google Flow cookie sessions (Session 1–10) every interval. Users can still manually switch sessions inside the extension popup.</p>' +
              '<div class="pf-toggle-row">' +
                '<div><strong>Enable Auto Session Shuffle Timer</strong><span>Automatically rotates every user to a balanced active session when countdown hits 00:00:00</span></div>' +
                '<input type="checkbox" id="pfShuffleEnabled" />' +
              '</div>' +
              '<div class="pf-enh-grid">' +
                '<div class="pf-field">' +
                  '<label for="pfShuffleInterval">Shuffle Interval (Minutes)</label>' +
                  '<input id="pfShuffleInterval" class="pf-input" type="number" min="5" max="43200" value="60" />' +
                '</div>' +
                '<div class="pf-field">' +
                  '<label for="pfDefaultSlot">Default Fallback Session</label>' +
                  '<select id="pfDefaultSlot" class="pf-select">' + defaultSlotOptions + '</select>' +
                '</div>' +
              '</div>' +
              '<div class="pf-chip-list">' +
                '<button type="button" class="pf-chip" data-mins="5">5 min</button>' +
                '<button type="button" class="pf-chip" data-mins="15">15 min</button>' +
                '<button type="button" class="pf-chip" data-mins="30">30 min</button>' +
                '<button type="button" class="pf-chip" data-mins="60">1 hour</button>' +
                '<button type="button" class="pf-chip" data-mins="120">2 hours</button>' +
                '<button type="button" class="pf-chip" data-mins="360">6 hours</button>' +
                '<button type="button" class="pf-chip" data-mins="720">12 hours</button>' +
                '<button type="button" class="pf-chip" data-mins="1440">24 hours</button>' +
              '</div>' +
              '<div class="pf-field">' +
                '<label>Eligible Sessions Pool (Only ACTIVE sessions with cookies rotate)</label>' +
                '<div class="pf-pool-grid">' + poolItemsHtml + '</div>' +
              '</div>' +
              '<div class="pf-row" style="margin-top:14px;">' +
                '<button type="button" class="pf-btn pf-btn-primary" id="pfSaveShuffleBtn">Save Shuffle Settings</button>' +
              '</div>' +
            '</div>' +
          '</div>' +

          /* TAB 2: EXTENSION BUILDER & UPLOAD */
          '<div class="pf-tab-pane" data-pane="ext" style="display:none;">' +
            '<div class="pf-enh-section">' +
              '<div class="pf-row" style="justify-content:space-between;margin-bottom:8px;">' +
                '<h4 style="margin:0;">Current Live Extension</h4>' +
                '<span class="pf-meta-pill" id="pfExtCurrentPill">Loading...</span>' +
              '</div>' +
              '<p class="pf-enh-hint" id="pfExtCurrentInfo">Checking latest release...</p>' +
              '<a id="pfExtCurrentDownload" href="/downloads/latest" class="pf-btn pf-btn-ghost" download>Download Current ZIP</a>' +
            '</div>' +

            '<div class="pf-enh-section">' +
              '<h4>Regenerate Extension ZIP (From Repo Source)</h4>' +
              '<p class="pf-enh-hint">Builds a Chrome-ready .zip directly from <code>extension-src/</code>, updates the version in manifest + popup header + scripts, and updates the user dashboard download banner immediately.</p>' +
              '<div class="pf-enh-grid">' +
                '<div class="pf-field">' +
                  '<label for="pfExtVersion">New Version (e.g. 1.8)</label>' +
                  '<input id="pfExtVersion" class="pf-input" type="text" placeholder="1.8" />' +
                '</div>' +
                '<div class="pf-field">' +
                  '<label for="pfExtMinVersion">Min Required Version</label>' +
                  '<input id="pfExtMinVersion" class="pf-input" type="text" placeholder="1.7" />' +
                '</div>' +
              '</div>' +
              '<div class="pf-enh-grid">' +
                '<div class="pf-field">' +
                  '<label for="pfExtReleaseNote">Release Banner Text (Optional)</label>' +
                  '<input id="pfExtReleaseNote" class="pf-input" type="text" placeholder="New update live now" />' +
                '</div>' +
                '<div class="pf-field">' +
                  '<label for="pfExtPortalUrl">Custom Portal URL (Optional)</label>' +
                  '<input id="pfExtPortalUrl" class="pf-input" type="url" placeholder="Leave empty for dynamic origin" />' +
                '</div>' +
              '</div>' +
              '<button type="button" class="pf-btn pf-btn-primary" id="pfRegenerateExtBtn">Regenerate &amp; Publish ZIP</button>' +
            '</div>' +

            '<div class="pf-enh-section">' +
              '<h4>Upload Custom Extension ZIP</h4>' +
              '<p class="pf-enh-hint">Upload your own pre-built .zip to publish it to all users.</p>' +
              '<div class="pf-enh-grid">' +
                '<div class="pf-field">' +
                  '<label for="pfUploadZipFile">Extension .zip File</label>' +
                  '<input id="pfUploadZipFile" class="pf-input" type="file" accept=".zip" />' +
                '</div>' +
                '<div class="pf-field">' +
                  '<label for="pfUploadZipVer">Version Label (e.g. 1.8)</label>' +
                  '<input id="pfUploadZipVer" class="pf-input" type="text" placeholder="1.8" />' +
                '</div>' +
              '</div>' +
              '<button type="button" class="pf-btn pf-btn-emerald" id="pfUploadZipBtn">Upload &amp; Publish ZIP</button>' +
            '</div>' +
          '</div>' +

          /* TAB 3: FLOW ACCESS LOCK */
          '<div class="pf-tab-pane" data-pane="lock" style="display:none;">' +
            '<div class="pf-enh-section">' +
              '<h4>Strict Google Flow Access Lock</h4>' +
              '<p class="pf-enh-hint">Control when Google Flow must lock and clear session cookies inside the user extension.</p>' +
              '<div class="pf-toggle-row">' +
                '<div><strong>Require PakFlow Portal Tab Open</strong><span>If user closes their PakFlow dashboard tab, Google Flow locks until they reopen it</span></div>' +
                '<input type="checkbox" id="pfRequirePortalOpen" />' +
              '</div>' +
              '<div class="pf-toggle-row">' +
                '<div><strong>Block Flow on Logout</strong><span>Immediately wipes Flow cookies and locks Google Flow when user signs out</span></div>' +
                '<input type="checkbox" id="pfBlockOnLogout" checked />' +
              '</div>' +
              '<div class="pf-toggle-row">' +
                '<div><strong>Block Flow on Expired / Basic Plan</strong><span>Blocks Google Flow access and wipes cookies as soon as subscription ends</span></div>' +
                '<input type="checkbox" id="pfBlockOnExpired" checked />' +
              '</div>' +
              '<div class="pf-field" style="max-width:260px;">' +
                '<label for="pfPortalGraceSec">Portal Close Grace Period (Seconds)</label>' +
                '<input id="pfPortalGraceSec" class="pf-input" type="number" min="5" max="120" value="15" />' +
              '</div>' +
              '<button type="button" class="pf-btn pf-btn-primary" id="pfSaveLockBtn">Save Access Lock Policy</button>' +
            '</div>' +
          '</div>' +

          /* TAB 4: WELCOME MESSAGE TEMPLATE */
          '<div class="pf-tab-pane" data-pane="tpl" style="display:none;">' +
            '<div class="pf-enh-section">' +
              '<h4>Default User Welcome Message Template</h4>' +
              '<p class="pf-enh-hint">Whenever you add a user, a 1-click Copy popup opens with this template auto-filled with their site link, email, password, plan, and days.</p>' +
              '<div class="pf-chip-list" id="pfAdminTplChips"></div>' +
              '<div class="pf-field">' +
                '<label for="pfAdminTplTextarea">Template Text</label>' +
                '<textarea id="pfAdminTplTextarea" class="pf-textarea" rows="8"></textarea>' +
              '</div>' +
              '<div class="pf-row" style="margin-bottom:14px;">' +
                '<button type="button" class="pf-btn pf-btn-primary" id="pfSaveAdminTplBtn">Save Template</button>' +
                '<button type="button" class="pf-btn pf-btn-ghost" id="pfResetAdminTplBtn">Reset Default</button>' +
              '</div>' +
              '<div class="pf-field">' +
                '<label>Live Preview</label>' +
                '<div id="pfAdminTplPreview" class="pf-preview-box"></div>' +
              '</div>' +
            '</div>' +
          '</div>' +

        '</div>' +
      '</div>';

    document.body.appendChild(el);

    const statusEl = el.querySelector('#pfCtrlStatus');

    el.addEventListener('click', (e) => {
      if (e.target === el) el.classList.add('pf-hidden');
    });
    el.querySelector('#pfCtrlClose').addEventListener('click', () => el.classList.add('pf-hidden'));

    // Tab switching
    el.querySelectorAll('.pf-enh-tab').forEach((tabBtn) => {
      tabBtn.addEventListener('click', () => {
        setBanner(statusEl, '', false);
        const target = tabBtn.getAttribute('data-tab');
        el.querySelectorAll('.pf-enh-tab').forEach(b => b.classList.toggle('active', b === tabBtn));
        el.querySelectorAll('.pf-tab-pane').forEach(p => {
          p.style.display = p.getAttribute('data-pane') === target ? 'block' : 'none';
        });
      });
    });

    // Interval chips
    el.querySelectorAll('[data-mins]').forEach((chip) => {
      chip.addEventListener('click', () => {
        el.querySelector('#pfShuffleInterval').value = chip.getAttribute('data-mins');
      });
    });

    // Admin template chips + live preview
    const tplTextarea = el.querySelector('#pfAdminTplTextarea');
    const tplPreview = el.querySelector('#pfAdminTplPreview');
    const updatePreview = () => {
      tplPreview.textContent = renderMessageTemplate(tplTextarea.value, {
        site_link: window.location.origin,
        name: 'Ali Khan',
        email: 'ali@example.com',
        password: 'Pass@1234',
        plan: 'PRO',
        days: 30,
        whatsapp: '+923001234567'
      });
    };
    tplTextarea.addEventListener('input', updatePreview);

    const chipsBox = el.querySelector('#pfAdminTplChips');
    PLACEHOLDERS.forEach((ph) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'pf-chip';
      b.textContent = ph;
      b.addEventListener('click', () => insertAtCursor(tplTextarea, ph));
      chipsBox.appendChild(b);
    });

    // Save Session Shuffle
    el.querySelector('#pfSaveShuffleBtn').addEventListener('click', async () => {
      const btn = el.querySelector('#pfSaveShuffleBtn');
      const enabled = el.querySelector('#pfShuffleEnabled').checked;
      const intervalMinutes = parseInt(el.querySelector('#pfShuffleInterval').value, 10) || 60;
      const defaultSlot = parseInt(el.querySelector('#pfDefaultSlot').value, 10) || 1;
      const poolSlots = Array.from(el.querySelectorAll('input[name="pfShufflePool"]:checked')).map(i => parseInt(i.value, 10));
      btn.disabled = true;
      btn.textContent = 'Saving...';
      try {
        const res = await apiFetch('/api/admin/controls/server-shuffle', {
          method: 'PUT',
          body: JSON.stringify({ enabled, intervalMinutes, defaultSlot, poolSlots, resetTimer: true })
        }, 'admin');
        if (res.serverShuffle) applyCountdownFromShuffle(res.serverShuffle);
        setBanner(statusEl, res.message || 'Session shuffle settings saved!', false);
        await hydrateAdminControls();
      } catch (err) {
        setBanner(statusEl, err.message || 'Failed to save shuffle settings', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Save Shuffle Settings';
      }
    });

    // Reshuffle Sessions Now
    el.querySelector('#pfReshuffleNowBtn').addEventListener('click', async () => {
      const btn = el.querySelector('#pfReshuffleNowBtn');
      btn.disabled = true;
      btn.textContent = 'Reshuffling...';
      try {
        const res = await apiFetch('/api/admin/controls/reshuffle-now', { method: 'POST', body: '{}' }, 'admin');
        if (res.serverShuffle) applyCountdownFromShuffle(res.serverShuffle);
        setBanner(statusEl, res.message || 'All user sessions reshuffled!', false);
        await hydrateAdminControls();
      } catch (err) {
        setBanner(statusEl, err.message || 'Failed to reshuffle sessions', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Reshuffle Sessions Now';
      }
    });

    // Regenerate Extension ZIP
    el.querySelector('#pfRegenerateExtBtn').addEventListener('click', async () => {
      const btn = el.querySelector('#pfRegenerateExtBtn');
      const version = el.querySelector('#pfExtVersion').value.trim();
      const minExtVersion = el.querySelector('#pfExtMinVersion').value.trim();
      const releaseNote = el.querySelector('#pfExtReleaseNote').value.trim();
      const portalUrl = el.querySelector('#pfExtPortalUrl').value.trim();
      if (!version) return setBanner(statusEl, 'Please enter a version number (e.g. 1.8).', true);
      btn.disabled = true;
      btn.textContent = 'Building ZIP...';
      setBanner(statusEl, '', false);
      try {
        const res = await apiFetch('/api/admin/extension/regenerate', {
          method: 'POST',
          body: JSON.stringify({ version, minExtVersion, releaseNote, portalUrl })
        }, 'admin');
        setBanner(statusEl, res.message || 'Extension regenerated successfully!', false);
        await hydrateAdminControls();
      } catch (err) {
        setBanner(statusEl, err.message || 'Failed to regenerate extension', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Regenerate & Publish ZIP';
      }
    });

    // Upload custom ZIP
    el.querySelector('#pfUploadZipBtn').addEventListener('click', async () => {
      const btn = el.querySelector('#pfUploadZipBtn');
      const fileInput = el.querySelector('#pfUploadZipFile');
      const ver = el.querySelector('#pfUploadZipVer').value.trim();
      const file = fileInput.files && fileInput.files[0];
      if (!file) return setBanner(statusEl, 'Please select a .zip file first.', true);
      btn.disabled = true;
      btn.textContent = 'Uploading...';
      setBanner(statusEl, '', false);
      try {
        const b64 = await new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = () => resolve(String(reader.result || ''));
          reader.onerror = () => reject(new Error('Failed to read ZIP file'));
          reader.readAsDataURL(file);
        });
        const res = await apiFetch('/api/admin/upload-extension', {
          method: 'POST',
          body: JSON.stringify({
            type: 'flow',
            fileName: file.name,
            fileData: b64,
            version: ver || ''
          })
        }, 'admin');
        setBanner(statusEl, res.message || 'Extension ZIP uploaded!', false);
        fileInput.value = '';
        await hydrateAdminControls();
      } catch (err) {
        setBanner(statusEl, err.message || 'Failed to upload ZIP', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Upload & Publish ZIP';
      }
    });

    // Save Access Policy
    el.querySelector('#pfSaveLockBtn').addEventListener('click', async () => {
      const btn = el.querySelector('#pfSaveLockBtn');
      const payload = {
        requirePortalOpen: el.querySelector('#pfRequirePortalOpen').checked,
        blockOnLogout: el.querySelector('#pfBlockOnLogout').checked,
        blockOnExpired: el.querySelector('#pfBlockOnExpired').checked,
        portalGraceSeconds: parseInt(el.querySelector('#pfPortalGraceSec').value, 10) || 15
      };
      btn.disabled = true;
      btn.textContent = 'Saving...';
      try {
        const res = await apiFetch('/api/admin/controls/access-policy', {
          method: 'PUT',
          body: JSON.stringify(payload)
        }, 'admin');
        setBanner(statusEl, res.message || 'Access lock policy saved!', false);
      } catch (err) {
        setBanner(statusEl, err.message || 'Failed to save policy', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Save Access Lock Policy';
      }
    });

    // Save Admin Welcome Template
    el.querySelector('#pfSaveAdminTplBtn').addEventListener('click', async () => {
      const btn = el.querySelector('#pfSaveAdminTplBtn');
      const template = tplTextarea.value.trim();
      if (!template) return setBanner(statusEl, 'Template cannot be empty.', true);
      btn.disabled = true;
      btn.textContent = 'Saving...';
      try {
        const res = await apiFetch('/api/admin/controls/message-template', {
          method: 'PUT',
          body: JSON.stringify({ template })
        }, 'admin');
        cachedAdminTemplate = template;
        setBanner(statusEl, res.message || 'Welcome template saved!', false);
      } catch (err) {
        setBanner(statusEl, err.message || 'Failed to save template', true);
      } finally {
        btn.disabled = false;
        btn.textContent = 'Save Template';
      }
    });

    el.querySelector('#pfResetAdminTplBtn').addEventListener('click', () => {
      tplTextarea.value = DEFAULT_TEMPLATE;
      updatePreview();
    });

    return el;
  }

  function populateAdminControlsFromConfig(cfg) {
    const modal = ensureAdminControlsModal();
    if (!cfg || typeof cfg !== 'object') return;

    const ext = cfg.extension || {};
    const ver = String(ext.version || '1.7');
    const fileName = String(ext.fileName || ('PakFlow_v' + ver + '.zip'));
    const dlUrl = String(ext.downloadUrl || '/downloads/latest');
    modal.querySelector('#pfExtCurrentPill').textContent = 'v' + ver + (ext.fileSize ? (' · ' + ext.fileSize) : '');
    modal.querySelector('#pfExtCurrentInfo').textContent =
      fileName + (ext.updatedAt ? (' · Updated ' + new Date(ext.updatedAt).toLocaleString()) : '') +
      ((cfg.extensionSourceReady || cfg.hasExtensionSource) ? ' · Source ready (extension-src/)' : ' · Source folder missing');
    const dlEl = modal.querySelector('#pfExtCurrentDownload');
    dlEl.href = dlUrl;
    dlEl.setAttribute('download', fileName);

    const parts = ver.split('.');
    let nextVer = ver;
    if (parts.length >= 2 && !isNaN(parseInt(parts[1], 10))) {
      nextVer = parts[0] + '.' + (parseInt(parts[1], 10) + 1);
    }
    if (!modal.querySelector('#pfExtVersion').value) {
      modal.querySelector('#pfExtVersion').value = nextVer;
    }
    modal.querySelector('#pfExtMinVersion').value = cfg.minExtVersion || ver;
    modal.querySelector('#pfExtReleaseNote').value = ext.releaseNote || '';

    // Session Shuffle + Live Slot Statuses
    const sh = cfg.serverShuffle || {};
    modal.querySelector('#pfShuffleEnabled').checked = Boolean(sh.enabled);
    modal.querySelector('#pfShuffleInterval').value = sh.intervalMinutes || 60;
    modal.querySelector('#pfDefaultSlot').value = String(sh.defaultSlot || 1);
    const pool = Array.isArray(sh.poolSlots) && sh.poolSlots.length ? sh.poolSlots.map(Number) : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
    modal.querySelectorAll('input[name="pfShufflePool"]').forEach((cb) => {
      cb.checked = pool.includes(parseInt(cb.value, 10));
    });

    const slotsByNum = {};
    if (Array.isArray(cfg.slots)) {
      cfg.slots.forEach((s) => {
        if (s && s.slotNumber) slotsByNum[Number(s.slotNumber)] = s;
      });
    }
    for (let n = 1; n <= 10; n++) {
      const slotInfo = slotsByNum[n];
      const badge = modal.querySelector('[data-session-badge="' + n + '"]');
      const lbl = modal.querySelector('[data-session-label="' + n + '"]');
      if (lbl && slotInfo && slotInfo.name) {
        const cleanName = String(slotInfo.name).replace(/^Server\s*/i, 'Session ');
        lbl.textContent = cleanName || ('Session ' + n);
      }
      if (badge) {
        const isActive = slotInfo && String(slotInfo.status || '').toUpperCase() === 'ACTIVE' && Number(slotInfo.cookieCount || 0) > 0;
        badge.className = 'pf-pool-badge ' + (isActive ? 'active' : 'empty');
        badge.textContent = isActive ? ('ACTIVE · ' + slotInfo.cookieCount) : 'EMPTY';
      }
    }

    applyCountdownFromShuffle(sh);

    // Access Lock
    const ap = cfg.accessPolicy || {};
    modal.querySelector('#pfRequirePortalOpen').checked = Boolean(ap.requirePortalOpen);
    modal.querySelector('#pfBlockOnLogout').checked = ap.blockOnLogout !== false;
    modal.querySelector('#pfBlockOnExpired').checked = ap.blockOnExpired !== false;
    modal.querySelector('#pfPortalGraceSec').value = ap.portalGraceSeconds || 15;

    // Welcome Template
    const tpl = cfg.messageTemplate || cfg.welcomeMessageTemplate || DEFAULT_TEMPLATE;
    cachedAdminTemplate = tpl;
    const ta = modal.querySelector('#pfAdminTplTextarea');
    ta.value = tpl;
    ta.dispatchEvent(new Event('input'));
  }

  async function hydrateAdminControls() {
    const modal = ensureAdminControlsModal();
    const statusEl = modal.querySelector('#pfCtrlStatus');
    try {
      const cfg = await apiFetch('/api/admin/controls/config', { method: 'GET' }, 'admin');
      cachedConfig = cfg;
      populateAdminControlsFromConfig(cfg);
    } catch (err) {
      setBanner(statusEl, err.message || 'Could not load controls config', true);
    }
  }

  function openAdminControlsModal(defaultTab) {
    const modal = ensureAdminControlsModal();
    setBanner(modal.querySelector('#pfCtrlStatus'), '', false);
    if (defaultTab) {
      modal.querySelectorAll('.pf-enh-tab').forEach(b => b.classList.toggle('active', b.getAttribute('data-tab') === defaultTab));
      modal.querySelectorAll('.pf-tab-pane').forEach(p => {
        p.style.display = p.getAttribute('data-pane') === defaultTab ? 'block' : 'none';
      });
    }
    modal.classList.remove('pf-hidden');
    hydrateAdminControls();
  }

  // ── 5. Mount Integrated Header Tools on /admin & Clean Dock on /reseller ──
  function mountAdminHeaderControls() {
    const header = document.querySelector('header.sticky');
    const rightGroup = header && header.children.length >= 2 ? header.children[header.children.length - 1] : null;
    if (!rightGroup) return false;

    if (!document.getElementById('pfAdminHeaderTools')) {
      const wrap = document.createElement('div');
      wrap.id = 'pfAdminHeaderTools';
      wrap.className = 'pf-header-tools';

      const pill = document.createElement('button');
      pill.type = 'button';
      pill.id = 'pfHeaderCountdownPill';
      pill.className = 'pf-hdr-countdown';
      pill.style.display = 'none';
      pill.title = 'Live Session Auto-Shuffle Countdown — Click to manage';
      pill.innerHTML = '<span class="pf-hdr-dot"></span><span id="pfHeaderCountdownText">Shuffle 00:00:00</span>';
      pill.addEventListener('click', () => openAdminControlsModal('shuffle'));

      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'pf-hdr-btn';
      btn.textContent = 'Controls';
      btn.title = 'Session Shuffle, Extension Builder, Access Lock & Welcome Template';
      btn.addEventListener('click', () => openAdminControlsModal());

      wrap.append(pill, btn);
      rightGroup.prepend(wrap);
      renderCountdownTick();
    }

    const fallbackDock = document.getElementById('pfEnhancerDock');
    if (fallbackDock) fallbackDock.remove();
    return true;
  }

  function syncShellAndControls() {
    if (document.body) {
      document.body.classList.toggle('pf-admin-shell', isAdminOrResellerPath());
    }

    const role = detectPanelRole();
    const roleChanged = role !== activeRole;
    activeRole = role;

    if (!role) {
      const existingDock = document.getElementById('pfEnhancerDock');
      if (existingDock) existingDock.remove();
      const existingHdr = document.getElementById('pfAdminHeaderTools');
      if (existingHdr) existingHdr.remove();
      return;
    }

    if (role === 'admin') {
      const mountedInHeader = mountAdminHeaderControls();
      if (!mountedInHeader && !document.getElementById('pfEnhancerDock')) {
        const dock = document.createElement('div');
        dock.id = 'pfEnhancerDock';
        dock.className = 'pf-enh-dock';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pf-enh-fab';
        btn.textContent = 'Controls';
        btn.addEventListener('click', () => openAdminControlsModal());
        dock.appendChild(btn);
        document.body.appendChild(dock);
      }
      if (roleChanged || !cachedConfig) {
        refreshShuffleStateSilently();
      }
    } else if (role === 'reseller') {
      if (!document.getElementById('pfEnhancerDock')) {
        const dock = document.createElement('div');
        dock.id = 'pfEnhancerDock';
        dock.className = 'pf-enh-dock';
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'pf-enh-fab';
        btn.textContent = 'Welcome Template';
        btn.addEventListener('click', openResellerTemplateModal);
        dock.appendChild(btn);
        document.body.appendChild(dock);
      }
    }
  }

  function init() {
    syncShellAndControls();
    setInterval(syncShellAndControls, 1200);
    window.addEventListener('popstate', syncShellAndControls);
    window.addEventListener('storage', syncShellAndControls);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
