/* Unified project history resilient to IP/VPN changes and server shuffles.
 * Automatically merges all legacy myProjectsByIp[*] buckets without deleting old projects. */
(() => {
  if (globalThis.__pfErrorShieldInstalled) return;
  globalThis.__pfErrorShieldInstalled = true;
  try {
    const noop = () => {};
    const dbg = typeof console !== 'undefined' && typeof console.debug === 'function' ? console.debug.bind(console) : noop;
    if (typeof console !== 'undefined') {
      console.error = (...args) => { try { dbg(...args); } catch {} };
      console.warn = (...args) => { try { dbg(...args); } catch {} };
    }
  } catch {}
  try {
    if (typeof globalThis.addEventListener === 'function') {
      globalThis.addEventListener('error', (e) => { try { e.preventDefault(); } catch {} });
      globalThis.addEventListener('unhandledrejection', (e) => { try { e.preventDefault(); } catch {} });
    }
  } catch {}
})();
globalThis.PakFlowProjects = (() => {
  let pending = Promise.resolve();
  const normalize = value => typeof value === 'string' && value.trim() && value !== 'active' ? value.trim() : 'slot_1';
  const selected = state => {
    // Prefer lastInjectedSlot (actual cookies active in Chrome) over stale UI activeSlot so projects NEVER save under the wrong server
    const actual = typeof state.lastInjectedSlot === 'string' && state.lastInjectedSlot.trim() && state.lastInjectedSlot !== 'active'
      ? state.lastInjectedSlot
      : state.activeSlot;
    return typeof actual === 'string' && actual.trim() && actual !== 'active'
      ? normalize(actual)
      : 'slot_' + ((Number.isInteger(state.sessionLoopIndex) && state.sessionLoopIndex >= 0 ? state.sessionLoopIndex : 0) + 1);
  };
  const sorted = list => [...list].sort((a, b) => (b.updatedAt || b.createdAt || 0) - (a.updatedAt || a.createdAt || 0));
  function run(action) {
    const result = pending.then(action);
    pending = result.catch(() => {});
    return result;
  }

  function mergeAllProjects(unifiedList, byIpObj, ownerEmail = '') {
    const cleanOwner = String(ownerEmail || '').trim().toLowerCase();
    const map = new Map();
    const ingest = (arr) => {
      if (!Array.isArray(arr)) return;
      for (const raw of arr) {
        if (!raw || typeof raw !== 'object') continue;
        const rawOwner = String(raw.ownerEmail || '').trim().toLowerCase();
        // Never allow another PakFlow account's projects to mix into this user's project folder
        if (cleanOwner && rawOwner && rawOwner !== cleanOwner) continue;
        const id = String(raw.id || '').trim().toLowerCase();
        if (!id) continue;
        const item = {
          ...raw,
          id,
          ownerEmail: rawOwner || cleanOwner || undefined,
          slot: normalize(raw.slot),
          url: raw.url || ('https://flow.google.com/project/' + id),
          createdAt: Number(raw.createdAt || raw.updatedAt || Date.now()),
          updatedAt: Number(raw.updatedAt || raw.createdAt || Date.now())
        };
        const prev = map.get(id);
        if (!prev) {
          map.set(id, item);
        } else {
          // Prefer customTitle and latest updatedAt
          const newer = (item.updatedAt || 0) >= (prev.updatedAt || 0) ? item : prev;
          const older = newer === item ? prev : item;
          map.set(id, {
            ...older,
            ...newer,
            ownerEmail: cleanOwner || newer.ownerEmail || older.ownerEmail || undefined,
            accountHash: newer.accountHash || older.accountHash || undefined,
            thumbnail: newer.thumbnail || older.thumbnail || newer.thumbnailUrl || older.thumbnailUrl || '',
            title: prev.customTitle ? prev.title : (item.customTitle ? item.title : (newer.title || older.title)),
            customTitle: Boolean(prev.customTitle || item.customTitle),
            createdAt: Math.min(prev.createdAt || Date.now(), item.createdAt || Date.now()),
            updatedAt: Math.max(prev.updatedAt || 0, item.updatedAt || 0)
          });
        }
      }
    };
    if (byIpObj && typeof byIpObj === 'object') {
      for (const bucket of Object.values(byIpObj)) ingest(bucket);
    }
    ingest(unifiedList);
    return sorted(Array.from(map.values()));
  }

  async function context(resolveIp) {
    let ip = 'default';
    try { ip = (await resolveIp()) || 'default'; } catch { ip = 'default'; }
    const state = await chrome.storage.local.get([
      'myProjectsUnified',
      'myProjectsByIp',
      'myProjectServerOwnersByIp',
      'activeSlot',
      'lastInjectedSlot',
      'pf_active_account_hash',
      'sessionLoopIndex',
      'userEmail',
      'user',
      'pakflow_user',
      'pf_projects_owner_email'
    ]);
    const ownerEmail = String(state.userEmail || state.user?.email || state.pakflow_user?.email || state.pf_projects_owner_email || '').trim().toLowerCase();
    const accountHash = String(state.pf_active_account_hash || '').trim();
    const byIp = state.myProjectsByIp && typeof state.myProjectsByIp === 'object' ? { ...state.myProjectsByIp } : {};
    const all = mergeAllProjects(state.myProjectsUnified, byIp, ownerEmail);
    const ownersByIp = state.myProjectServerOwnersByIp && typeof state.myProjectServerOwnersByIp === 'object'
      ? { ...state.myProjectServerOwnersByIp } : {};
    const owners = { ...(ownersByIp[ip] || {}) };
    for (const project of all) {
      const id = String(project.id || '').toLowerCase();
      if (id && !Object.hasOwn(owners, id)) owners[id] = normalize(project.slot);
    }
    ownersByIp[ip] = owners;
    return { ip, byIp, all, ownersByIp, owners, ownerEmail, accountHash, slot: selected(state) };
  }

  function response(ctx) {
    const ordered = sorted(ctx.all);
    // 3-Key Lock: match user (ownerEmail) + active Google Account hash (or active slot if hash not yet recorded)
    const activeSlotList = [];
    const seen = new Set();
    for (const p of ordered) {
      const id = String(p.id || '').toLowerCase();
      if (!id || seen.has(id)) continue;
      seen.add(id);
      if (ctx.accountHash && p.accountHash) {
        if (p.accountHash === ctx.accountHash) activeSlotList.push(p);
      } else if (normalize(p.slot) === ctx.slot) {
        activeSlotList.push(p);
      }
    }
    return {
      ip: ctx.ip,
      activeSlot: ctx.slot,
      projects: activeSlotList,
      allProjects: ordered,
      activeSlotCount: activeSlotList.length,
      ownedIds: activeSlotList.map(p => p.id)
    };
  }

  let cloudPushTimer = null;
  function scheduleCloudBackup(projects, replaceAll) {
    try {
      if (cloudPushTimer) clearTimeout(cloudPushTimer);
      cloudPushTimer = setTimeout(() => {
        cloudPushTimer = null;
        if (typeof globalThis.__pakflowPushProjectsToCloud === 'function') {
          globalThis.__pakflowPushProjectsToCloud(projects, Boolean(replaceAll));
        }
      }, 1500);
    } catch {}
  }

  async function persist(ctx, opts = {}) {
    ctx.byIp[ctx.ip] = ctx.all;
    const latest = await chrome.storage.local.get(['activeSlot', 'sessionLoopIndex']);
    const cacheSlot = selected(latest);
    await chrome.storage.local.set({
      myProjectsUnified: ctx.all,
      myProjectsByIp: ctx.byIp,
      myProjectServerOwnersByIp: ctx.ownersByIp,
      myProjectsCurrentIp: ctx.ip,
      myProjectsOwnedIds: response({ ...ctx, slot: cacheSlot }).ownedIds
    });
    if (!opts.skipCloud) {
      scheduleCloudBackup(ctx.all, Boolean(opts.replaceCloud));
    }
    return response(ctx);
  }

  return {
    list(resolveIp) {
      return run(async () => {
        const ctx = await context(resolveIp);
        await chrome.storage.local.set({
          myProjectsUnified: ctx.all,
          myProjectServerOwnersByIp: ctx.ownersByIp,
          myProjectsOwnedIds: response(ctx).ownedIds
        });
        return response(ctx);
      });
    },
    save(resolveIp, input = {}) {
      return run(async () => {
        const ctx = await context(resolveIp);
        const match = String(input.url || '').match(/\/project\/([^/?#]+)/i);
        const id = String(input.id || match?.[1] || '').toLowerCase();
        if (!id) return response(ctx);
        const index = ctx.all.findIndex(p => String(p.id || '').toLowerCase() === id);
        const previous = index >= 0 ? ctx.all[index] : null;
        // Preserve original server attribution if already known; otherwise use requested or active slot
        const target = previous?.slot
          ? normalize(previous.slot)
          : (input.slot ? normalize(input.slot) : ctx.slot);
        ctx.owners[id] = target;
        const title = String(input.title || '').trim();
        const prompt = String(input.prompt || '').trim().slice(0, 180);
        const isGenericTitle = (t) => !t
          || /^(?:flow|google\s*flow|new\s*project)$/i.test(t)
          || /^flow\s*[-–—]\s*google/i.test(t)
          || /^google\s*flow\s*[-–—]\s*ai\s*creative\s*studio/i.test(t)
          || /page\s*not\s*found/i.test(t);
        const generic = isGenericTitle(title);
        const number = ctx.all.length + 1;
        const now = Date.now();
        const project = {
          ...previous,
          id,
          ownerEmail: ctx.ownerEmail || previous?.ownerEmail || undefined,
          accountHash: ctx.accountHash || previous?.accountHash || undefined,
          url: 'https://flow.google.com/project/' + id,
          slot: ctx.slot || target,
          ip: ctx.ip,
          createdAt: previous?.createdAt || now,
          updatedAt: now
        };
        if (input.customTitle && title) {
          project.title = title;
          project.customTitle = true;
        } else if (!previous?.customTitle && !generic) {
          project.title = title;
        }
        if (!project.title || isGenericTitle(project.title)) {
          project.title = prompt ? prompt.slice(0, 60) : 'Project #' + number;
        }
        if (input.thumbnail && /^(?:https:\/\/|data:image\/)/i.test(String(input.thumbnail))) {
          project.thumbnail = String(input.thumbnail);
        } else if (previous?.thumbnail) {
          project.thumbnail = previous.thumbnail;
        }
        if (prompt) project.prompt = prompt;
        if (project.customTitle === undefined) project.customTitle = false;
        if (index >= 0) ctx.all[index] = project;
        else ctx.all.unshift(project);
        return persist(ctx);
      });
    },
    rename(resolveIp, id, title) {
      return run(async () => {
        const ctx = await context(resolveIp);
        id = String(id || '').toLowerCase();
        title = String(title || '').trim();
        const index = ctx.all.findIndex(p => String(p.id || '').toLowerCase() === id);
        if (index < 0 || !title) return response(ctx);
        ctx.all[index] = { ...ctx.all[index], title, customTitle: true, updatedAt: Date.now() };
        return persist(ctx);
      });
    },
    remove(resolveIp, id) {
      return run(async () => {
        const ctx = await context(resolveIp);
        id = String(id || '').toLowerCase();
        ctx.all = ctx.all.filter(p => String(p.id || '').toLowerCase() !== id);
        for (const k of Object.keys(ctx.byIp)) {
          if (Array.isArray(ctx.byIp[k])) {
            ctx.byIp[k] = ctx.byIp[k].filter(p => String(p?.id || '').toLowerCase() !== id);
          }
        }
        delete ctx.owners[id];
        return persist(ctx, { replaceCloud: true });
      });
    },
    mergeCloud(resolveIp, cloudProjects = [], ownerEmail = '') {
      return run(async () => {
        if (!Array.isArray(cloudProjects)) return null;
        const ctx = await context(resolveIp);
        const effOwner = String(ownerEmail || ctx.ownerEmail || '').trim().toLowerCase();
        const taggedCloud = cloudProjects.map((p) => (p && typeof p === 'object' ? { ...p, ownerEmail: effOwner || p.ownerEmail } : p));
        ctx.all = mergeAllProjects([...taggedCloud, ...ctx.all], ctx.byIp, effOwner);
        return persist(ctx, { skipCloud: true });
      });
    }
  };
})();
