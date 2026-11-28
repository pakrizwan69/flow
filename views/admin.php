<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PakFlow Admin Console</title>
  <link rel="icon" href="/flowbypak-logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0b1120;
      --bg-elevated: #0f172a;
      --card: #111827;
      --card-hover: #162033;
      --muted-bg: #1e293b;
      --border: rgba(148, 163, 184, 0.14);
      --border-strong: rgba(148, 163, 184, 0.26);
      --text: #f8fafc;
      --text-secondary: #cbd5e1;
      --text-muted: #94a3b8;
      --primary: #2563eb;
      --primary-hover: #3b82f6;
      --emerald: #10b981;
      --amber: #f59e0b;
      --rose: #ef4444;
      --sidebar-w: 248px;
      --radius: 12px;
      --radius-sm: 8px;
      --font-sans: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    @media (max-width: 1360px) and (min-width: 1121px) {
      :root { --sidebar-w: 228px; }
    }
    html, body {
      min-height: 100%;
      background: var(--bg);
      color: var(--text);
      font-family: var(--font-sans);
      font-size: 14px;
      line-height: 1.5;
      -webkit-font-smoothing: antialiased;
      overflow-x: hidden;
    }
    /* Custom slim dark scrollbars everywhere (no thick white Windows bars) */
    * {
      scrollbar-width: thin;
      scrollbar-color: rgba(148, 163, 184, 0.28) transparent;
    }
    *::-webkit-scrollbar { width: 6px; height: 6px; }
    *::-webkit-scrollbar-track { background: transparent; }
    *::-webkit-scrollbar-thumb { background: rgba(148, 163, 184, 0.28); border-radius: 999px; }
    *::-webkit-scrollbar-thumb:hover { background: rgba(148, 163, 184, 0.45); }

    .hidden { display: none !important; }
    button, input, select, textarea { font-family: inherit; }
    button:focus-visible, a:focus-visible, input:focus-visible, select:focus-visible, textarea:focus-visible {
      outline: 2px solid var(--primary-hover);
      outline-offset: 2px;
    }
    @media (prefers-reduced-motion: reduce) {
      *, *::before, *::after { transition: none !important; animation: none !important; }
    }

    /* ── Login Screen ── */
    .login-shell {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      background:
        radial-gradient(ellipse 65% 50% at 50% 0%, rgba(37, 99, 235, 0.16), transparent 65%),
        var(--bg);
    }
    .login-card {
      width: 100%;
      max-width: 420px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 32px 28px;
      box-shadow: 0 24px 64px rgba(0, 0, 0, 0.45);
    }
    .login-brand { display: flex; align-items: center; gap: 12px; margin-bottom: 24px; }
    .login-brand img {
      width: 40px; height: 40px; border-radius: 10px;
      border: 1px solid var(--border-strong); object-fit: cover;
    }
    .login-brand h1 { font-size: 18px; font-weight: 800; letter-spacing: -0.02em; }
    .login-brand p { font-size: 12px; color: var(--text-muted); }

    /* ── App Shell (Desktop Sidebar + Collapsible Drawer <= 1120px) ── */
    .shell { display: flex; min-height: 100vh; width: 100%; }
    .sidebar-backdrop {
      position: fixed; inset: 0; z-index: 40;
      background: rgba(2, 6, 23, 0.72); backdrop-filter: blur(4px);
    }
    .sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: var(--sidebar-w); z-index: 50;
      background: var(--bg-elevated);
      border-right: 1px solid var(--border);
      display: flex; flex-direction: column;
      transition: transform 0.2s ease;
    }
    @media (max-width: 1120px) {
      .sidebar { transform: translateX(-100%); width: 260px; }
      .sidebar.is-open { transform: translateX(0); }
    }
    .sidebar-head {
      height: 62px; padding: 0 16px;
      display: flex; align-items: center; justify-content: space-between;
      border-bottom: 1px solid var(--border); flex-shrink: 0;
    }
    .brand-link { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--text); min-width: 0; }
    .brand-link img {
      width: 30px; height: 30px; border-radius: 8px;
      border: 1px solid var(--border-strong); object-fit: cover; flex-shrink: 0;
    }
    .brand-title { font-size: 15px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; }
    .brand-sub { font-size: 11px; color: var(--text-muted); font-weight: 500; }
    .sidebar-nav {
      flex: 1; overflow-y: auto; padding: 10px 10px 14px;
      display: flex; flex-direction: column; gap: 2px;
    }
    .nav-group-label {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: 0.07em; color: #64748b; padding: 11px 10px 5px;
    }
    .nav-group-label:first-child { padding-top: 2px; }
    .nav-btn {
      width: 100%; display: flex; align-items: center; gap: 9px;
      padding: 8px 10px; border-radius: var(--radius-sm);
      border: 1px solid transparent; background: transparent;
      color: var(--text-muted); font-size: 12.75px; font-weight: 600;
      cursor: pointer; text-align: left; text-decoration: none;
      transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .nav-btn > span:first-of-type { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .nav-btn svg { width: 16px; height: 16px; flex-shrink: 0; opacity: 0.85; }
    .nav-btn:hover { background: rgba(255, 255, 255, 0.04); color: var(--text); }
    .nav-btn.active {
      background: rgba(37, 99, 235, 0.14);
      border-color: rgba(59, 130, 246, 0.28);
      color: #ffffff;
    }
    .nav-btn.active svg { color: #60a5fa; opacity: 1; }
    .nav-badge {
      margin-left: auto; font-family: var(--font-mono); font-size: 10.5px; font-weight: 600;
      padding: 1px 6px; border-radius: 999px; background: var(--muted-bg); color: var(--text-secondary);
      flex-shrink: 0;
    }
    .nav-badge.warn { background: rgba(245, 158, 11, 0.18); color: #fcd34d; }
    .sidebar-foot {
      padding: 10px; border-top: 1px solid var(--border);
      display: flex; flex-direction: column; gap: 3px; flex-shrink: 0;
    }

    /* ── Main Column ── */
    .main-col { flex: 1; min-width: 0; display: flex; flex-direction: column; }
    @media (min-width: 1121px) {
      .main-col { padding-left: var(--sidebar-w); }
    }
    .topbar {
      position: sticky; top: 0; z-index: 30;
      min-height: 62px; padding: 10px 22px;
      background: rgba(11, 17, 32, 0.9);
      backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      gap: 10px; flex-wrap: wrap;
    }
    .topbar-left { display: flex; align-items: center; gap: 11px; min-width: 0; flex: 1; }
    .menu-toggle {
      display: inline-flex; align-items: center; justify-content: center;
      width: 36px; height: 36px; border-radius: var(--radius-sm);
      border: 1px solid var(--border); background: var(--card); color: var(--text);
      cursor: pointer; flex-shrink: 0;
    }
    @media (min-width: 1121px) { .menu-toggle { display: none; } }
    .page-title { font-size: 16.5px; font-weight: 800; letter-spacing: -0.02em; color: var(--text); line-height: 1.2; }
    .page-sub { font-size: 12px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .topbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    .countdown-pill {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 6px 11px; border-radius: var(--radius-sm);
      background: var(--card); border: 1px solid var(--border-strong);
      color: var(--text-secondary); font-family: var(--font-mono);
      font-size: 11.5px; font-weight: 600; cursor: pointer;
    }
    .countdown-pill:hover { border-color: #60a5fa; background: var(--card-hover); color: #fff; }
    .status-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--emerald); flex-shrink: 0; }
    .maint-pill {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 5px 10px; border-radius: 999px;
      background: rgba(239, 68, 68, 0.16); border: 1px solid rgba(239, 68, 68, 0.38);
      color: #fca5a5; font-size: 11.5px; font-weight: 700; cursor: pointer;
    }

    .workspace {
      width: 100%; max-width: 1480px; margin: 0 auto;
      padding: 20px 22px; display: flex; flex-direction: column; gap: 18px; min-width: 0;
    }
    @media (max-width: 768px) {
      .topbar { padding: 10px 14px; }
      .workspace { padding: 14px; gap: 14px; }
      .page-sub { display: none; }
    }

    /* ── Buttons ── */
    .btn {
      display: inline-flex; align-items: center; justify-content: center; gap: 6px;
      padding: 8px 13px; border-radius: var(--radius-sm);
      border: 1px solid transparent; font-size: 12.75px; font-weight: 600;
      cursor: pointer; text-decoration: none; white-space: nowrap;
      transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease, opacity 0.15s ease;
    }
    .btn svg { width: 15px; height: 15px; flex-shrink: 0; }
    .btn:disabled { opacity: 0.55; cursor: not-allowed; }
    .btn-primary { background: var(--primary); color: #ffffff; }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-emerald { background: var(--emerald); color: #052e16; font-weight: 700; }
    .btn-emerald:hover { background: #34d399; }
    .btn-amber { background: var(--amber); color: #1c1917; font-weight: 700; }
    .btn-amber:hover { background: #fbbf24; }
    .btn-danger { background: rgba(239, 68, 68, 0.14); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; }
    .btn-danger:hover { background: rgba(239, 68, 68, 0.24); color: #fff; }
    .btn-ghost { background: var(--card); border-color: var(--border); color: var(--text-secondary); }
    .btn-ghost:hover { background: var(--muted-bg); border-color: var(--border-strong); color: #fff; }
    .btn-sm { padding: 5px 9px; font-size: 11.75px; }
    .btn-xs { padding: 3px 7px; font-size: 11px; border-radius: 6px; }
    .btn-icon { padding: 7px; }

    /* ── Cards, Subtabs & Grids ── */
    .card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 18px; min-width: 0;
    }
    @media (max-width: 640px) { .card { padding: 14px; } }
    .card-head {
      display: flex; align-items: flex-start; justify-content: space-between;
      gap: 12px; flex-wrap: wrap; margin-bottom: 14px;
    }
    .card-title { font-size: 15px; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
    .card-desc { font-size: 12.25px; color: var(--text-muted); margin-top: 2px; }

    .subtabs {
      display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
      padding: 5px; border-radius: 10px;
      background: var(--bg-elevated); border: 1px solid var(--border);
      margin-bottom: 16px;
    }
    .subtab-btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 13px; border-radius: 7px; border: 1px solid transparent;
      background: transparent; color: var(--text-muted);
      font-size: 12.5px; font-weight: 600; cursor: pointer;
      transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .subtab-btn:hover { color: var(--text); background: rgba(255, 255, 255, 0.04); }
    .subtab-btn.active {
      background: rgba(37, 99, 235, 0.16);
      border-color: rgba(59, 130, 246, 0.34);
      color: #ffffff;
    }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
    }
    .kpi-grid.kpi-grid-5 {
      grid-template-columns: repeat(6, minmax(0, 1fr));
    }
    @media (max-width: 1380px) {
      .kpi-grid.kpi-grid-5 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 1279px) {
      .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .kpi-grid.kpi-grid-5 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
    }
    @media (max-width: 820px) {
      .kpi-grid.kpi-grid-5 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 560px) {
      .kpi-grid, .kpi-grid.kpi-grid-5 { grid-template-columns: 1fr; }
    }

    .kpi-card {
      background: var(--card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 15px 16px;
      display: flex; flex-direction: column; gap: 7px;
      min-width: 0; overflow: hidden;
    }
    .kpi-top {
      display: flex; align-items: center; justify-content: space-between; gap: 8px;
      color: var(--text-muted); font-size: 12px; font-weight: 600;
    }
    .kpi-icon {
      width: 30px; height: 30px; border-radius: 8px; background: var(--muted-bg);
      display: flex; align-items: center; justify-content: center; color: #60a5fa; flex-shrink: 0;
    }
    .kpi-icon.emerald { color: #34d399; background: rgba(16, 185, 129, 0.12); }
    .kpi-icon.amber { color: #fbbf24; background: rgba(245, 158, 11, 0.12); }
    .kpi-icon.rose { color: #f87171; background: rgba(239, 68, 68, 0.12); }
    .kpi-val {
      font-family: var(--font-mono);
      font-size: clamp(18px, 1.7vw, 24px);
      font-weight: 700; letter-spacing: -0.02em;
      color: var(--text); line-height: 1.15;
      overflow-wrap: anywhere; word-break: break-word;
    }
    .kpi-val.is-compact { font-size: clamp(16px, 1.35vw, 20px); }
    .kpi-sub { font-size: 11.75px; color: var(--text-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }

    .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    @media (max-width: 1279px) { .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 820px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    /* ── Toolbar & Filters ── */
    .toolbar {
      display: flex; align-items: center; justify-content: space-between;
      gap: 10px; flex-wrap: wrap; margin-bottom: 14px;
    }
    .search-box { position: relative; flex: 1; min-width: 200px; max-width: 380px; }
    @media (max-width: 640px) { .search-box { max-width: 100%; width: 100%; } }
    .search-box svg {
      position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
      width: 15px; height: 15px; color: var(--text-muted); pointer-events: none;
    }
    .search-box input { padding-left: 34px; }
    .filter-group { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    /* ── Form Controls ── */
    .field { display: flex; flex-direction: column; gap: 5px; margin-bottom: 13px; min-width: 0; }
    .field:last-child { margin-bottom: 0; }
    .field label { font-size: 12px; font-weight: 600; color: var(--text-secondary); }
    .input, .select, .textarea {
      width: 100%; min-width: 0; padding: 8px 11px; border-radius: var(--radius-sm);
      border: 1px solid var(--border-strong); background: var(--bg); color: var(--text);
      font-size: 13px; transition: border-color 0.15s ease;
    }
    .textarea { min-height: 120px; resize: vertical; line-height: 1.55; font-family: var(--font-sans); }
    .textarea.mono { font-family: var(--font-mono); font-size: 12px; }
    .input:focus, .select:focus, .textarea:focus { border-color: var(--primary-hover); }

    /* ── Tables ── */
    .table-wrap {
      width: 100%; max-width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch;
      border: 1px solid var(--border); border-radius: 10px; background: var(--bg-elevated);
    }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    thead th {
      padding: 10px 11px; font-size: 10.75px; font-weight: 700;
      text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted);
      background: rgba(15, 23, 42, 0.92); border-bottom: 1px solid var(--border); white-space: nowrap;
    }
    tbody td {
      padding: 10px 11px; border-bottom: 1px solid var(--border);
      font-size: 12.75px; color: var(--text-secondary); vertical-align: middle;
    }
    tbody tr:last-child td { border-bottom: 0; }
    tbody tr:hover { background: rgba(255, 255, 255, 0.025); }
    .user-cell-name { font-weight: 700; color: var(--text); font-size: 13px; line-height: 1.3; }
    .user-cell-email { font-size: 11.75px; color: var(--text-muted); word-break: break-word; }
    .actions-cell { display: flex; align-items: center; gap: 5px; flex-wrap: wrap; }
    .quick-ext-row { display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap; }

    /* ── Badges ── */
    .badge {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 2.5px 8px; border-radius: 999px; font-size: 11px; font-weight: 700;
      white-space: nowrap; border: 1px solid transparent;
    }
    .badge-emerald { background: rgba(16, 185, 129, 0.14); border-color: rgba(16, 185, 129, 0.3); color: #34d399; }
    .badge-blue { background: rgba(37, 99, 235, 0.16); border-color: rgba(59, 130, 246, 0.32); color: #93c5fd; }
    .badge-amber { background: rgba(245, 158, 11, 0.14); border-color: rgba(245, 158, 11, 0.3); color: #fcd34d; }
    .badge-rose { background: rgba(239, 68, 68, 0.14); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; }
    .badge-muted { background: var(--muted-bg); border-color: var(--border); color: var(--text-muted); }

    /* ── Progress Bars (Analytics) ── */
    .bar-row { display: flex; flex-direction: column; gap: 5px; margin-bottom: 12px; }
    .bar-row:last-child { margin-bottom: 0; }
    .bar-meta { display: flex; justify-content: space-between; font-size: 12.5px; font-weight: 600; }
    .bar-track { height: 8px; border-radius: 999px; background: var(--muted-bg); overflow: hidden; }
    .bar-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #2563eb, #38bdf8); transition: width 0.35s ease; }

    /* ── Session Shuffle Hero & Slot Cards ── */
    .shuffle-hero {
      display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: 14px; align-items: center;
      padding: 16px 18px; border-radius: var(--radius);
      background: var(--bg-elevated); border: 1px solid var(--border-strong); margin-bottom: 14px;
    }
    @media (max-width: 700px) { .shuffle-hero { grid-template-columns: 1fr; } }
    .countdown-clock {
      font-family: var(--font-mono); font-size: clamp(24px, 3vw, 32px); font-weight: 700;
      color: var(--text); letter-spacing: -0.02em; line-height: 1.1; margin: 4px 0;
    }
    .countdown-clock.is-off { font-size: 18px; color: var(--text-muted); }

    .session-card {
      background: var(--bg-elevated); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 15px; display: flex; flex-direction: column; gap: 11px;
      min-width: 0; transition: border-color 0.15s ease;
    }
    .session-card:hover { border-color: var(--border-strong); }
    .session-card.is-active { border-color: rgba(16, 185, 129, 0.34); }
    .session-card-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
    .session-name { font-size: 13.5px; font-weight: 700; color: var(--text); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .session-meta {
      display: flex; align-items: center; justify-content: space-between; gap: 8px;
      font-size: 11.5px; color: var(--text-muted); font-family: var(--font-mono); flex-wrap: wrap;
    }
    .session-pool-check {
      display: inline-flex; align-items: center; gap: 7px;
      font-size: 12px; font-weight: 600; color: var(--text-secondary); cursor: pointer;
    }
    .session-pool-check input { width: 15px; height: 15px; accent-color: var(--emerald); cursor: pointer; }
    .session-actions { display: flex; gap: 6px; flex-wrap: wrap; }

    /* ── Toggle Row & Chips ── */
    .toggle-row {
      display: flex; align-items: center; justify-content: space-between; gap: 14px;
      padding: 12px 14px; border-radius: 10px;
      background: var(--bg-elevated); border: 1px solid var(--border); margin-bottom: 10px;
    }
    .toggle-row strong { display: block; font-size: 13px; color: var(--text); }
    .toggle-row span { display: block; font-size: 11.75px; color: var(--text-muted); margin-top: 2px; }
    .switch {
      width: 42px; height: 24px; appearance: none;
      background: var(--muted-bg); border: 1px solid var(--border-strong);
      border-radius: 999px; position: relative; cursor: pointer; flex-shrink: 0;
      transition: background 0.16s ease, border-color 0.16s ease;
    }
    .switch::after {
      content: ""; position: absolute; top: 2px; left: 2px;
      width: 18px; height: 18px; border-radius: 50%; background: #cbd5e1;
      transition: transform 0.16s ease, background 0.16s ease;
    }
    .switch:checked { background: var(--emerald); border-color: var(--emerald); }
    .switch:checked::after { transform: translateX(18px); background: #ffffff; }

    .chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 6px 0 12px; }
    .chip {
      padding: 5px 10px; border-radius: 6px; border: 1px solid var(--border);
      background: var(--bg-elevated); color: var(--text-secondary);
      font-size: 12px; font-weight: 600; cursor: pointer;
    }
    .chip:hover { border-color: var(--primary-hover); color: #fff; background: var(--muted-bg); }

    .preview-box {
      background: var(--bg); border: 1px solid var(--border); border-radius: 10px;
      padding: 14px; font-size: 13px; line-height: 1.6; color: var(--text-secondary);
      white-space: pre-wrap; word-break: break-word; min-height: 180px;
    }

    /* ── Plan Config Cards ── */
    .plan-card {
      background: var(--bg-elevated); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 15px; display: flex; flex-direction: column; gap: 10px;
    }
    .plan-card-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; }

    /* ── Modals ── */
    .modal-backdrop {
      position: fixed; inset: 0; z-index: 80;
      background: rgba(2, 6, 23, 0.78); backdrop-filter: blur(6px);
      display: flex; align-items: center; justify-content: center;
      padding: 14px; overflow-y: auto;
    }
    .modal {
      width: 100%; max-width: min(94vw, 540px); max-height: calc(100dvh - 28px);
      background: var(--card); border: 1px solid var(--border-strong);
      border-radius: 14px; display: flex; flex-direction: column; overflow: hidden;
      margin: auto; box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6);
    }
    .modal > form {
      display: flex;
      flex-direction: column;
      flex: 1;
      min-height: 0;
      overflow: hidden;
    }
    .modal.modal-lg { max-width: min(96vw, 940px); }
    .modal-head {
      padding: 14px 18px; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between; gap: 10px;
      background: var(--bg-elevated); flex-shrink: 0;
    }
    .modal-head h3 { font-size: 15px; font-weight: 700; }
    .modal-body { padding: 16px 18px; overflow-y: auto; -webkit-overflow-scrolling: touch; flex: 1; min-height: 0; }
    .modal-foot {
      padding: 12px 18px; border-top: 1px solid var(--border);
      display: flex; justify-content: flex-end; align-items: center; gap: 8px;
      flex-wrap: wrap; background: var(--bg-elevated); flex-shrink: 0;
    }
    @media (max-width: 640px) {
      .modal-backdrop { padding: 10px; align-items: flex-end; }
      .modal { max-width: 100%; max-height: 92dvh; border-radius: 16px 16px 12px 12px; }
      .modal-head { padding: 12px 14px; }
      .modal-body { padding: 14px; }
      .modal .grid-2, .modal .grid-3 { grid-template-columns: 1fr !important; gap: 10px !important; }
      .modal-foot { padding: 10px 14px; }
      .modal-foot .btn { flex: 1 1 120px; justify-content: center; }
    }

    /* ── Toast & Alerts ── */
    .toast {
      position: fixed; right: 16px; bottom: 16px; z-index: 120;
      max-width: min(92vw, 390px); padding: 11px 15px; border-radius: 10px;
      background: var(--card); border: 1px solid var(--border-strong); color: var(--text);
      font-size: 13px; font-weight: 600; box-shadow: 0 18px 40px rgba(0, 0, 0, 0.55);
      display: flex; align-items: center; gap: 10px;
    }
    .toast.ok { border-color: rgba(16, 185, 129, 0.45); }
    .toast.err { border-color: rgba(239, 68, 68, 0.45); color: #fecaca; }

    .alert-box {
      padding: 10px 13px; border-radius: var(--radius-sm); font-size: 13px;
      font-weight: 500; margin-bottom: 14px;
    }
    .alert-box.err { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; }
    .alert-box.warn { background: rgba(245, 158, 11, 0.12); border: 1px solid rgba(245, 158, 11, 0.34); color: #fde68a; }
    .empty-state { text-align: center; padding: 32px 14px; color: var(--text-muted); font-size: 13px; }
    .proof-thumb {
      width: 44px; height: 34px; border-radius: 6px; object-fit: cover;
      border: 1px solid var(--border-strong); cursor: pointer; vertical-align: middle;
    }
  </style>
</head>
<body>
  <!-- ADMIN LOGIN VIEW -->
  <section id="loginView" class="login-shell hidden">
    <div class="login-card">
      <div class="login-brand">
        <img src="/flowbypak-logo.png" alt="PakFlow" />
        <div>
          <h1>PakFlow Admin</h1>
          <p>Sign in to manage users, resellers, sessions &amp; releases</p>
        </div>
      </div>
      <div id="loginError" class="alert-box err hidden"></div>
      <form id="adminLoginForm">
        <div class="field">
          <label for="adminEmail">Admin Email</label>
          <input id="adminEmail" class="input" type="email" placeholder="admin@flowbypak.com" required autocomplete="username" />
        </div>
        <div class="field">
          <label for="adminPassword">Password</label>
          <input id="adminPassword" class="input" type="password" placeholder="Enter password" required autocomplete="current-password" />
        </div>
        <button id="adminLoginBtn" type="submit" class="btn btn-primary" style="width:100%;padding:11px;margin-top:6px;">Sign In to Console</button>
      </form>
    </div>
  </section>

  <!-- MAIN ADMIN CONSOLE -->
  <div id="appShell" class="shell hidden">
    <div id="sidebarBackdrop" class="sidebar-backdrop hidden"></div>
    <aside id="sidebar" class="sidebar" aria-label="Admin Navigation">
      <div class="sidebar-head">
        <a href="/admin" class="brand-link">
          <img src="/flowbypak-logo.png" alt="PakFlow" />
          <div>
            <div class="brand-title">PakFlow</div>
            <div class="brand-sub">Admin Console</div>
          </div>
        </a>
        <button id="closeSidebarBtn" type="button" class="menu-toggle" aria-label="Close Menu">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
        </button>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-group-label">Core Management</div>
        <button type="button" class="nav-btn active" data-view="users">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>Users &amp; Approvals</span>
          <span class="nav-badge" id="navUsersCount">0</span>
        </button>
        <button type="button" class="nav-btn" data-view="sessions">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="7" rx="2"/><rect x="2" y="14" width="20" height="7" rx="2"/><path d="M6 6.5h.01M6 17.5h.01"/></svg>
          <span>Flow Sessions (1–15)</span>
          <span class="nav-badge" id="navSessionsCount">0</span>
        </button>
        <button type="button" class="nav-btn" data-view="resellers">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9h.01M9 12h.01M9 15h.01"/></svg>
          <span>Resellers &amp; Payouts</span>
          <span class="nav-badge" id="navResellersCount">0</span>
        </button>
        <button type="button" class="nav-btn" data-view="analytics">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
          <span>Analytics &amp; Revenue</span>
        </button>
        <button type="button" class="nav-btn" data-view="plans">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
          <span>Plans &amp; Pricing</span>
        </button>

        <div class="nav-group-label">Distribution &amp; Access</div>
        <button type="button" class="nav-btn" data-view="extension">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="M3.27 6.96 12 12.01l8.73-5.05M12 22.08V12"/></svg>
          <span>Extension Builder</span>
          <span class="nav-badge" id="navExtVer">v1.7</span>
        </button>
        <button type="button" class="nav-btn" data-view="security">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
          <span>Access Lock &amp; Policy</span>
        </button>
        <button type="button" class="nav-btn" data-view="template">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
          <span>Welcome Template</span>
        </button>

        <div class="nav-group-label">Operations</div>
        <button type="button" class="nav-btn" data-view="settings">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
          <span>Settings &amp; Broadcast</span>
        </button>
        <a href="/admin/email" class="nav-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          <span>Email Studio</span>
        </a>
      </nav>

      <div class="sidebar-foot">
        <a href="/" target="_blank" rel="noopener noreferrer" class="nav-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          <span>Open User Portal</span>
        </a>
        <button id="sidebarLogoutBtn" type="button" class="nav-btn" style="color:#fca5a5;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          <span>Sign Out</span>
        </button>
      </div>
    </aside>

    <div class="main-col">
      <header class="topbar">
        <div class="topbar-left">
          <button id="openSidebarBtn" type="button" class="menu-toggle" aria-label="Open Navigation">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          </button>
          <div style="min-width:0;">
            <h2 id="topbarTitle" class="page-title">Users &amp; Approvals</h2>
            <p id="topbarSub" class="page-sub">Manage accounts, quick extend, reset limits &amp; device locks</p>
          </div>
        </div>
        <div class="topbar-right">
          <button id="topMaintPill" type="button" class="maint-pill hidden" title="Maintenance Mode is Active">Maintenance ON</button>
          <button id="topCountdownPill" type="button" class="countdown-pill hidden" title="Auto Session Shuffle Countdown">
            <span class="status-dot"></span>
            <span id="topCountdownText">Shuffle 00:00:00</span>
          </button>
          <button id="topAddUserBtn" type="button" class="btn btn-primary">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
            <span>Add User</span>
          </button>
          <button id="topRefreshBtn" type="button" class="btn btn-ghost btn-icon" title="Refresh Data" aria-label="Refresh">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>
          </button>
        </div>
      </header>

      <main class="workspace">
        <!-- VIEW 1: USERS & APPROVALS -->
        <section id="view-users" class="view-pane">
          <div class="kpi-grid kpi-grid-5" style="margin-bottom:16px;">
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Total Users</span>
                <span class="kpi-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiTotal">0</div>
              <div class="kpi-sub">Registered accounts</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Active Plans</span>
                <span class="kpi-icon emerald"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiActive" style="color:#34d399;">0</div>
              <div class="kpi-sub">Paid Flow access</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Expired</span>
                <span class="kpi-icon amber"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiExpired" style="color:#fbbf24;">0</div>
              <div class="kpi-sub">Pending renewal</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Suspended / Locked</span>
                <span class="kpi-icon rose"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiSuspended" style="color:#f87171;">0</div>
              <div class="kpi-sub">Restricted accounts</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Site Free Trial</span>
                <span class="kpi-icon emerald"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiSiteTrial" style="color:#38bdf8;">0</div>
              <div class="kpi-sub" id="kpiSiteTrialSub">0 total · Auto Slot</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Reseller Demo</span>
                <span class="kpi-icon amber"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiResellerDemo" style="color:#a78bfa;">0</div>
              <div class="kpi-sub" id="kpiResellerDemoSub">0 total reseller demos</div>
            </div>
          </div>

          <!-- Pending Approvals Card (Auto-visible when pending > 0) -->
          <div id="pendingApprovalsCard" class="card hidden" style="margin-bottom:16px;border-color:rgba(245,158,11,0.38);">
            <div class="card-head">
              <div>
                <h3 class="card-title" style="color:#fcd34d;">Pending User Approvals (<span id="pendingCountNum">0</span>)</h3>
                <p class="card-desc">Approve or reject new account requests waiting for activation.</p>
              </div>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Requested Plan</th>
                    <th>Reseller</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="pendingTableBody"></tbody>
              </table>
            </div>
          </div>

          <div class="card">
            <div class="toolbar">
              <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="userSearchInput" class="input" type="search" placeholder="Search name, email or username..." />
              </div>
              <div class="filter-group">
                <select id="userPlanFilter" class="select" style="width:auto;min-width:124px;">
                  <option value="all">All Plans</option>
                  <option value="pro">Pro</option>
                  <option value="max">Max</option>
                  <option value="demo">Demo</option>
                  <option value="basic">Basic (Free)</option>
                </select>
                <select id="userStatusFilter" class="select" style="width:auto;min-width:136px;">
                  <option value="all">All Statuses</option>
                  <option value="active">Active</option>
                  <option value="expiring">Expiring (&le; 7d)</option>
                  <option value="expired">Expired</option>
                  <option value="suspended">Suspended / Locked</option>
                </select>
                <select id="userAddedByFilter" class="select" style="width:auto;min-width:136px;">
                  <option value="all">All Added By</option>
                  <option value="Admin">Admin</option>
                </select>
              </div>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Plan &amp; Credits</th>
                    <th>Days &amp; Quick Extend</th>
                    <th>Status</th>
                    <th>Devices</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="usersTableBody">
                  <tr><td colspan="6" class="empty-state">Loading users...</td></tr>
                </tbody>
              </table>
            </div>
            <div id="usersPaginationBar" style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:12px;padding-top:12px;border-top:1px solid var(--border);font-size:12.5px;color:var(--text-muted);">
              <span id="usersPageInfo">Showing 0 users</span>
              <div style="display:flex;align-items:center;gap:8px;">
                <button id="usersPrevBtn" type="button" class="btn btn-ghost btn-sm" disabled>Previous</button>
                <span id="usersPageIndicator" style="font-family:var(--font-mono);color:var(--text-secondary);font-weight:600;">Page 1 / 1</span>
                <button id="usersNextBtn" type="button" class="btn btn-ghost btn-sm" disabled>Next</button>
              </div>
            </div>
          </div>
        </section>

        <!-- VIEW 2: GOOGLE FLOW SESSIONS (1-10) & NANO BANANA (11-15) -->
        <section id="view-sessions" class="view-pane hidden">
          <div class="card" style="margin-bottom:16px;">
            <div class="shuffle-hero">
              <div>
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);">Next Automatic Session Shuffle</div>
                <div id="liveCountdownClock" class="countdown-clock is-off">Timer Off</div>
                <div id="liveCountdownMeta" style="font-size:12.25px;color:var(--text-muted);">Epoch #1 · Enable timer to automatically rotate users across active sessions</div>
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button id="reshuffleNowBtn" type="button" class="btn btn-amber">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/><line x1="4" y1="4" x2="9" y2="9"/></svg>
                  <span>Reshuffle All Users Now</span>
                </button>
              </div>
            </div>

            <div class="toggle-row">
              <div>
                <strong>Automatic Session Rotation Timer</strong>
                <span>Distributes users across selected ACTIVE sessions and rotates everyone when the countdown reaches 00:00:00.</span>
              </div>
              <input id="shuffleEnabledToggle" type="checkbox" class="switch" />
            </div>

            <div class="grid-2" style="margin-top:12px;">
              <div class="field">
                <label for="shuffleIntervalInput">Shuffle Interval (Minutes)</label>
                <input id="shuffleIntervalInput" class="input" type="number" min="5" max="43200" value="60" />
                <div class="chips" style="margin-top:6px;margin-bottom:0;">
                  <button type="button" class="chip" data-mins="5">5m</button>
                  <button type="button" class="chip" data-mins="15">15m</button>
                  <button type="button" class="chip" data-mins="30">30m</button>
                  <button type="button" class="chip" data-mins="60">1h</button>
                  <button type="button" class="chip" data-mins="120">2h</button>
                  <button type="button" class="chip" data-mins="360">6h</button>
                  <button type="button" class="chip" data-mins="720">12h</button>
                  <button type="button" class="chip" data-mins="1440">24h</button>
                </div>
              </div>
              <div class="field">
                <label for="defaultSlotSelect">Default Fallback Session</label>
                <select id="defaultSlotSelect" class="select">
                  <option value="1">Session 1</option>
                  <option value="2">Session 2</option>
                  <option value="3">Session 3</option>
                  <option value="4">Session 4</option>
                  <option value="5">Session 5</option>
                  <option value="6">Session 6</option>
                  <option value="7">Session 7</option>
                  <option value="8">Session 8</option>
                  <option value="9">Session 9</option>
                  <option value="10">Session 10</option>
                </select>
                <div style="margin-top:8px;">
                  <button id="saveShuffleBtn" type="button" class="btn btn-primary">Save Shuffle &amp; Pool Settings</button>
                </div>
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-head">
              <div>
                <h3 class="card-title">Cookie Sessions Manager (Slots 1 – 15)</h3>
                <p class="card-desc">Paste JSON/Netscape cookies or upload .json/.txt files. Bulk enable/disable loaded servers in 1 click.</p>
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button id="bulkEnableSlotsBtn" type="button" class="btn btn-emerald btn-sm">Enable All Loaded</button>
                <button id="bulkStandbySlotsBtn" type="button" class="btn btn-ghost btn-sm">Standby All</button>
              </div>
            </div>
            <div class="subtabs" id="slotGroupTabs">
              <button type="button" class="subtab-btn active" data-slot-tab="flow">Google Flow Servers (1–10)</button>
              <button type="button" class="subtab-btn" data-slot-tab="nano">Nano Banana Pro (11–15)</button>
            </div>
            <div id="sessionsGrid" class="grid-3">
              <div class="empty-state">Loading sessions...</div>
            </div>
          </div>
        </section>

        <!-- VIEW 3: RESELLERS & PAYOUTS (COMPLETE 4-SUBTAB SUITE) -->
        <section id="view-resellers" class="view-pane hidden">
          <div class="kpi-grid" style="margin-bottom:16px;">
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Total Resellers</span>
                <span class="kpi-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiResellersTotal">0</div>
              <div class="kpi-sub">Authorized partners</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Reseller Users</span>
                <span class="kpi-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiResellerUsersTotal" style="color:#60a5fa;">0</div>
              <div class="kpi-sub">Total accounts created</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Outstanding Dues</span>
                <span class="kpi-icon amber"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiResellersDue" style="color:#fbbf24;">Rs. 0</div>
              <div class="kpi-sub" id="kpiResellersDueSub">Pending collection</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Total Collected</span>
                <span class="kpi-icon emerald"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiResellersPaid" style="color:#34d399;">Rs. 0</div>
              <div class="kpi-sub" id="kpiResellersPaidSub">Cleared payouts</div>
            </div>
          </div>

          <div class="subtabs" id="resellerSubtabs">
            <button type="button" class="subtab-btn active" data-rtab="partners">Reseller Partners</button>
            <button type="button" class="subtab-btn" data-rtab="analytics">Analytics (Aaj &amp; Signups)</button>
            <button type="button" class="subtab-btn" data-rtab="payments">Payment Receipts <span class="nav-badge warn" id="pendingPayBadge" style="display:none;">0</span></button>
            <button type="button" class="subtab-btn" data-rtab="config">Rates, Bank &amp; Notice</button>
          </div>

          <!-- Sub-tab 1: Reseller Partners -->
          <div id="rtab-partners" class="rtab-pane">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Authorized Resellers</h3>
                  <p class="card-desc">Search resellers, inspect every reseller's users, manage dues or edit rates &amp; discounts.</p>
                </div>
                <button id="openAddResellerBtn" type="button" class="btn btn-primary">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                  <span>Add Reseller</span>
                </button>
              </div>
              <div class="toolbar">
                <div class="search-box">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                  <input id="resellerSearchInput" class="input" type="search" placeholder="Search reseller by name, email or WhatsApp..." />
                </div>
                <div class="filter-group">
                  <select id="resellerDueFilter" class="select" style="width:auto;min-width:155px;">
                    <option value="all">All Resellers</option>
                    <option value="due">Has Balance Due</option>
                    <option value="overdue">Overdue (&gt; 3d)</option>
                    <option value="cleared">Cleared / Paid</option>
                    <option value="suspended">Suspended</option>
                  </select>
                </div>
              </div>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Reseller</th>
                      <th>WhatsApp</th>
                      <th>Users &amp; Quota</th>
                      <th>Billing &amp; Dues</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="resellersTableBody">
                    <tr><td colspan="6" class="empty-state">Loading resellers...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Sub-tab 2: Resellers Analytics (Aaj & Signups) -->
          <div id="rtab-analytics" class="rtab-pane hidden">
            <div class="kpi-grid" style="margin-bottom:14px;">
              <div class="kpi-card">
                <div class="kpi-top"><span>Added Today (Aaj)</span></div>
                <div class="kpi-val" id="raTodayVal" style="color:#60a5fa;">0</div>
                <div class="kpi-sub">Signups in last 24h</div>
              </div>
              <div class="kpi-card">
                <div class="kpi-top"><span>Added This Week</span></div>
                <div class="kpi-val" id="raWeekVal" style="color:#34d399;">0</div>
                <div class="kpi-sub">Last 7 days total</div>
              </div>
              <div class="kpi-card">
                <div class="kpi-top"><span>Overdue Resellers</span></div>
                <div class="kpi-val" id="raOverdueVal" style="color:#f87171;">0</div>
                <div class="kpi-sub">Past 3-day grace period</div>
              </div>
              <div class="kpi-card">
                <div class="kpi-top"><span>Active Network Users</span></div>
                <div class="kpi-val" id="raTotalUsersVal">0</div>
                <div class="kpi-sub">Across all resellers</div>
              </div>
            </div>
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Resellers Real-Time Performance &amp; Daily Signups</h3>
                  <p class="card-desc">Track today's signups, weekly growth, 3-day grace status, and clear dues in 1 click.</p>
                </div>
                <button id="refreshResellerAnalyticsBtn" type="button" class="btn btn-ghost btn-sm">Refresh Analytics</button>
              </div>
              <div class="toolbar">
                <div class="search-box">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                  <input id="raSearchInput" class="input" type="search" placeholder="Filter reseller analytics..." />
                </div>
              </div>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Reseller Partner</th>
                      <th>Today (Aaj)</th>
                      <th>This Week</th>
                      <th>Total Users</th>
                      <th>Balance Due</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="raTableBody">
                    <tr><td colspan="7" class="empty-state">Loading analytics...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Sub-tab 3: Payment Receipts & Approvals -->
          <div id="rtab-payments" class="rtab-pane hidden">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Reseller Balance Top-up Approvals</h3>
                  <p class="card-desc">Inspect payment screenshots, Trx IDs, and approve or reject reseller payment submissions.</p>
                </div>
              </div>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Reseller</th>
                      <th>Amount Paid</th>
                      <th>Proof Screenshot</th>
                      <th>Note / Trx ID</th>
                      <th>Date &amp; Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="paymentsTableBody">
                    <tr><td colspan="6" class="empty-state">Loading payments...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Sub-tab 4: Rates, Bank Details & Reseller Notice Board -->
          <div id="rtab-config" class="rtab-pane hidden">
            <div class="grid-2" style="margin-bottom:16px;">
              <div class="card">
                <div class="card-head">
                  <div>
                    <h3 class="card-title">Partner Bank Account &amp; Billing Details</h3>
                    <p class="card-desc">Displayed to resellers on their payment submission page.</p>
                  </div>
                </div>
                <div class="field">
                  <label for="rbBankName">Bank Name</label>
                  <input id="rbBankName" class="input" type="text" placeholder="Raqami Islamic Digital Bank" />
                </div>
                <div class="field">
                  <label for="rbAccountTitle">Account Title (Name)</label>
                  <input id="rbAccountTitle" class="input" type="text" placeholder="Account Holder Name" />
                </div>
                <div class="grid-2">
                  <div class="field">
                    <label for="rbAccountNumber">Account Number</label>
                    <input id="rbAccountNumber" class="input" type="text" />
                  </div>
                  <div class="field">
                    <label for="rbIban">IBAN</label>
                    <input id="rbIban" class="input" type="text" />
                  </div>
                </div>
                <div class="field">
                  <label for="rbInstructions">Instructions / Note for Resellers</label>
                  <textarea id="rbInstructions" class="textarea" style="min-height:80px;"></textarea>
                </div>
                <button id="saveResellerBankBtn" type="button" class="btn btn-primary">Save Bank Details</button>
              </div>

              <div class="card">
                <div class="card-head">
                  <div>
                    <h3 class="card-title">Default Reseller Rates &amp; Plan Credits</h3>
                    <p class="card-desc">Wholesale PKR price &amp; default AI credits granted per user created by resellers (editable per partner too).</p>
                  </div>
                </div>
                <div class="grid-2">
                  <div class="field">
                    <label for="rrPro">PRO Plan Rate (Rs.)</label>
                    <input id="rrPro" class="input" type="number" min="0" value="500" />
                  </div>
                  <div class="field">
                    <label for="rrMax">MAX Plan Rate (Rs.)</label>
                    <input id="rrMax" class="input" type="number" min="0" value="800" />
                  </div>
                  <div class="field">
                    <label for="rcPro">PRO User Credits (Default 25k)</label>
                    <input id="rcPro" class="input" type="number" min="100" step="100" value="25000" />
                  </div>
                  <div class="field">
                    <label for="rcMax">MAX User Credits (Default 45k)</label>
                    <input id="rcMax" class="input" type="number" min="100" step="100" value="45000" />
                  </div>
                  <div class="field">
                    <label for="rdPro">PRO Plan Duration (Days · Default 20)</label>
                    <input id="rdPro" class="input" type="number" min="1" max="3650" value="20" />
                  </div>
                  <div class="field">
                    <label for="rdMax">MAX Plan Duration (Days · Default 30)</label>
                    <input id="rdMax" class="input" type="number" min="1" max="3650" value="30" />
                  </div>
                </div>
                <button id="saveResellerRatesBtn" type="button" class="btn btn-primary" style="margin-bottom:18px;">Save Rates, Credits &amp; Plan Days</button>

                <div style="border-top:1px solid var(--border);padding-top:16px;">
                  <div class="card-title" style="margin-bottom:8px;">Reseller Partner Notice Board</div>
                  <div class="toggle-row">
                    <div>
                      <strong>Show Announcement to Resellers</strong>
                      <span>Displays a notice banner inside the Reseller Portal.</span>
                    </div>
                    <input id="rbcEnabled" type="checkbox" class="switch" />
                  </div>
                  <div class="field">
                    <label for="rbcTitle">Announcement Heading</label>
                    <input id="rbcTitle" class="input" type="text" placeholder="Hello dear resellers," />
                  </div>
                  <div class="field">
                    <label for="rbcMessage">Announcement Message Body</label>
                    <textarea id="rbcMessage" class="textarea" style="min-height:85px;"></textarea>
                  </div>
                  <div class="grid-2">
                    <div class="field">
                      <label for="rbcPhone1">Contact Phone 1</label>
                      <input id="rbcPhone1" class="input" type="text" placeholder="+92 331 0356428" />
                    </div>
                    <div class="field">
                      <label for="rbcPhone2">Contact Phone 2</label>
                      <input id="rbcPhone2" class="input" type="text" placeholder="+92 319 8273690" />
                    </div>
                  </div>
                  <button id="saveResellerBroadcastBtn" type="button" class="btn btn-emerald">Save Reseller Notice</button>
                </div>
              </div>
            </div>
          </div>
        </section>

        <!-- VIEW 4: ANALYTICS & REVENUE (RESTORED FROM OLD ADMIN PANEL) -->
        <section id="view-analytics" class="view-pane hidden">
          <div class="kpi-grid" style="margin-bottom:16px;">
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Estimated MRR</span>
                <span class="kpi-icon emerald"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></span>
              </div>
              <div class="kpi-val" id="anMrrVal" style="color:#34d399;">Rs. 0</div>
              <div class="kpi-sub">Based on active subscriptions</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Active Retention Rate</span>
                <span class="kpi-icon"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></span>
              </div>
              <div class="kpi-val" id="anRetentionVal" style="color:#60a5fa;">0%</div>
              <div class="kpi-sub">Active vs total accounts</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Expiring In 7 Days</span>
                <span class="kpi-icon amber"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
              </div>
              <div class="kpi-val" id="anExpiringVal" style="color:#fbbf24;">0</div>
              <div class="kpi-sub">Ready for renewal reminder</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Active Paid Users</span>
                <span class="kpi-icon emerald"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
              </div>
              <div class="kpi-val" id="anPaidActiveVal">0</div>
              <div class="kpi-sub">Currently valid plans</div>
            </div>
          </div>

          <div class="grid-2">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Plan Subscriptions Distribution</h3>
                  <p class="card-desc">Live account breakdown across all subscription tiers.</p>
                </div>
              </div>
              <div id="planDistContainer">
                <div class="empty-state">Loading distribution...</div>
              </div>
            </div>

            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Accounts Expiring Soon (&le; 7 Days)</h3>
                  <p class="card-desc">Send renewal reminders or extend plans in 1 click.</p>
                </div>
                <button id="runExpiryEmailsBtn" type="button" class="btn btn-amber btn-sm">Send Expiry Emails</button>
              </div>
              <div class="table-wrap" style="max-height:360px;overflow-y:auto;">
                <table>
                  <thead>
                    <tr>
                      <th>User</th>
                      <th>Plan</th>
                      <th>Days Left</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody id="expiringUsersBody">
                    <tr><td colspan="4" class="empty-state">Loading expiring accounts...</td></tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- VIEW 5: SUBSCRIPTION PLANS & PRICING (RESTORED FROM OLD ADMIN PANEL) -->
        <section id="view-plans" class="view-pane hidden">
          <!-- Public Self-Service Free Trial Control Card (Separate from Reseller Demo) -->
          <div class="card" style="margin-bottom:16px;border-color:rgba(139,92,246,0.32);">
            <div class="card-head">
              <div>
                <h3 class="card-title">Public Site Free Trial Control <span id="demoStatusBadge" class="badge badge-muted" style="margin-left:8px;">OFF</span></h3>
                <p class="card-desc">Controls the Free Trial on your main Pricing/Home page (completely separate from Reseller 2h Demo). Set any duration in Hours or Days &amp; lock all Site Trial users to a specific Cookie Slot.</p>
              </div>
              <button id="saveDemoConfigBtn" type="button" class="btn btn-emerald">Save Trial Settings</button>
            </div>
            <div class="toggle-row" style="margin-bottom:14px;">
              <div>
                <strong>Enable Free Trial on Site (Home &amp; Pricing Self-Activation)</strong>
                <span id="demoToggleSub">When OFF, Free Trial card is hidden from Home &amp; Pricing pages and self-activation is blocked.</span>
              </div>
              <input id="demoEnabledToggle" type="checkbox" class="switch" />
            </div>
            <div class="grid-2" style="margin-bottom:8px;">
              <div class="field">
                <label for="demoDurationVal">Trial Duration (Any Hours or Days)</label>
                <div style="display:flex;gap:8px;">
                  <input id="demoDurationVal" class="input" type="number" min="0.5" max="365" step="any" value="2" style="flex:1;" />
                  <select id="demoDurationUnit" class="select" style="width:130px;">
                    <option value="hours">Hours</option>
                    <option value="days">Days</option>
                  </select>
                </div>
              </div>
              <div class="field">
                <label for="demoCreditsVal">Site Trial AI Credits</label>
                <input id="demoCreditsVal" class="input" type="number" min="50" step="100" value="2000" />
              </div>
            </div>
            <div class="field" style="margin-bottom:12px;">
              <label for="demoAssignedSlot">Dedicated Cookie Session Slot (Site Trial Users ONLY)</label>
              <select id="demoAssignedSlot" class="select">
                <option value="0">Auto / Normal Server Pool (No Lock)</option>
                <option value="1">Lock Site Trial Users to Slot 1 ONLY</option>
                <option value="2">Lock Site Trial Users to Slot 2 ONLY</option>
                <option value="3">Lock Site Trial Users to Slot 3 ONLY</option>
                <option value="4">Lock Site Trial Users to Slot 4 ONLY</option>
                <option value="5">Lock Site Trial Users to Slot 5 ONLY</option>
                <option value="6">Lock Site Trial Users to Slot 6 ONLY</option>
                <option value="7">Lock Site Trial Users to Slot 7 ONLY</option>
                <option value="8">Lock Site Trial Users to Slot 8 ONLY</option>
                <option value="9">Lock Site Trial Users to Slot 9 ONLY</option>
                <option value="10">Lock Site Trial Users to Slot 10 ONLY</option>
              </select>
            </div>
            <div class="grid-2" style="margin-top:4px;">
              <div class="toggle-row" style="margin-bottom:0;">
                <div>
                  <strong>1 IP = 1 Free Trial</strong>
                  <span>Blocks repeat trial claims from the same IP address.</span>
                </div>
                <input id="demoOnePerIp" type="checkbox" class="switch" checked />
              </div>
              <div class="toggle-row" style="margin-bottom:0;">
                <div>
                  <strong>1 Device ID = 1 Free Trial</strong>
                  <span>Tracks browser/device ID to block multiple trials on same PC/phone.</span>
                </div>
                <input id="demoOnePerDevice" type="checkbox" class="switch" checked />
              </div>
            </div>
          </div>

          <div class="card">
            <div class="card-head">
              <div>
                <h3 class="card-title">Plan Manager &amp; Pricing Configuration</h3>
                <p class="card-desc">Configure display names, PKR pricing, credits, visibility, and plan perks shown across the portal.</p>
              </div>
              <button id="savePlansBtn" type="button" class="btn btn-primary">Save All Plans</button>
            </div>
            <div id="plansEditorGrid" class="grid-3">
              <div class="empty-state">Loading plans...</div>
            </div>
          </div>
        </section>

        <!-- VIEW 6: EXTENSION BUILDER & RELEASES -->
        <section id="view-extension" class="view-pane hidden">
          <div class="card" style="margin-bottom:16px;">
            <div class="card-head" style="margin-bottom:0;">
              <div>
                <h3 class="card-title">Current Live Extension Release</h3>
                <p id="extLiveMeta" class="card-desc">Checking active release...</p>
              </div>
              <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span id="extLivePill" class="badge badge-emerald">v1.7</span>
                <a id="extLiveDownloadBtn" href="/downloads/latest" class="btn btn-ghost btn-sm" download>Download Current ZIP</a>
              </div>
            </div>
          </div>

          <div class="grid-2" style="margin-bottom:16px;">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Regenerate Extension ZIP (From Repo)</h3>
                  <p class="card-desc">Packages Chrome-ready .zip directly from <code>extension-src/</code>, updates version in manifest + popup + scripts.</p>
                </div>
              </div>
              <div class="grid-2">
                <div class="field">
                  <label for="extNewVer">New Version (e.g. 1.9)</label>
                  <input id="extNewVer" class="input" type="text" placeholder="1.9" />
                </div>
                <div class="field">
                  <label for="extMinVer">Min Required Version</label>
                  <input id="extMinVer" class="input" type="text" placeholder="1.7" />
                </div>
              </div>
              <div class="field">
                <label for="extReleaseNote">Dashboard Release Banner Text (Optional)</label>
                <input id="extReleaseNote" class="input" type="text" placeholder="Faster session sync &amp; fixes" />
              </div>
              <div class="field">
                <label for="extPortalUrl">Custom Portal URL (Optional — leave blank for dynamic origin)</label>
                <input id="extPortalUrl" class="input" type="url" placeholder="https://flowbypak.com" />
              </div>
              <button id="regenExtBtn" type="button" class="btn btn-primary">Regenerate &amp; Publish ZIP</button>
            </div>

            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Upload Custom Extension ZIP</h3>
                  <p class="card-desc">Upload your own pre-built .zip package to publish immediately for all users.</p>
                </div>
              </div>
              <div class="field">
                <label for="uploadZipInput">Select .zip File</label>
                <input id="uploadZipInput" class="input" type="file" accept=".zip" />
              </div>
              <div class="field">
                <label for="uploadZipVer">Version Label (e.g. 1.9)</label>
                <input id="uploadZipVer" class="input" type="text" placeholder="1.9" />
              </div>
              <div class="field">
                <label for="uploadZipNote">Release Note (Optional)</label>
                <input id="uploadZipNote" class="input" type="text" placeholder="Custom build release" />
              </div>
              <button id="uploadExtBtn" type="button" class="btn btn-emerald">Upload &amp; Publish ZIP</button>
            </div>
          </div>

          <div class="card" style="margin-bottom:16px;border-color:rgba(139,92,246,0.35);background:linear-gradient(180deg,rgba(139,92,246,0.08),rgba(15,23,42,0.55));">
            <div class="card-head">
              <div>
                <h3 class="card-title">Chrome Web Store Package (1-Click Install &amp; Silent Auto-Update)</h3>
                <p class="card-desc">Builds 100% Chrome Web Store policy-compliant <code>PakFlow_WebStore_vX.Y.zip</code> from <code>extension-webstore/</code> (includes auto-disabler for old unpacked copies, zero risk to live ZIP).</p>
              </div>
              <span class="badge badge-purple">Unlisted Store Ready</span>
            </div>
            <div class="grid-2">
              <div class="field">
                <label for="wsExtVer">Web Store Version (must be higher than previous store upload, e.g. 1.8 → 1.9)</label>
                <input id="wsExtVer" class="input" type="text" value="1.8" placeholder="1.8" />
              </div>
              <div class="field">
                <label for="wsPortalUrl">Custom Portal URL (Optional)</label>
                <input id="wsPortalUrl" class="input" type="url" placeholder="https://flowbypak.com" />
              </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
              <button id="buildWebStoreBtn" type="button" class="btn btn-primary">Build &amp; Download Web Store ZIP</button>
              <a id="downloadWebStoreLink" href="/downloads/PakFlow_WebStore_v1.8.zip" class="btn btn-ghost btn-sm" download="PakFlow_WebStore_v1.8.zip">Download PakFlow_WebStore_v1.8.zip</a>
              <a href="/privacy-policy" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">Open Privacy Policy URL &rarr;</a>
            </div>
          </div>

          <div class="card">
            <div class="card-head">
              <div>
                <h3 class="card-title">Extension Download Settings &amp; Trial Access</h3>
                <p class="card-desc">Control Chrome Web Store 1-Click link, Free Trial extension access, direct download URL, and installation guide.</p>
              </div>
            </div>
            <div class="toggle-row">
              <div>
                <strong>Allow Free Trial Extension Access</strong>
                <span>When enabled, Free / Basic trial users can download and use the Google Flow extension.</span>
              </div>
              <input id="trialExtAccessToggle" type="checkbox" class="switch" />
            </div>
            <div class="field" style="margin-top:12px;">
              <label for="extWebStoreUrl">Chrome Web Store URL (Leave blank until Google approves — paste Unlisted link here to enable 1-Click "Add to Chrome" on User Portal)</label>
              <input id="extWebStoreUrl" class="input" type="url" placeholder="https://chromewebstore.google.com/detail/pakflow/..." />
            </div>
            <div class="grid-2" style="margin-top:12px;">
              <div class="field">
                <label for="extDirectUrl">Direct ZIP Download Link (Fallback / Mobile ZIP)</label>
                <input id="extDirectUrl" class="input" type="text" placeholder="/downloads/PakFlow_v1.7.zip" />
              </div>
              <div class="field">
                <label for="extInstructions">Installation Instructions</label>
                <textarea id="extInstructions" class="textarea" style="min-height:95px;"></textarea>
              </div>
            </div>
            <button id="saveExtMetaBtn" type="button" class="btn btn-primary">Save Extension Settings</button>
          </div>
        </section>

        <!-- VIEW 7: ACCESS LOCK & SECURITY -->
        <section id="view-security" class="view-pane hidden">
          <div class="grid-2">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Google Flow Access Lock Policy</h3>
                  <p class="card-desc">Enforced directly inside the PakFlow Chrome Extension.</p>
                </div>
              </div>
              <div class="toggle-row">
                <div>
                  <strong>Require PakFlow Portal Tab Open</strong>
                  <span>Locks Google Flow if the user closes their PakFlow portal tab beyond the grace period.</span>
                </div>
                <input id="polPortalOpen" type="checkbox" class="switch" />
              </div>
              <div class="toggle-row">
                <div>
                  <strong>Block Flow on Logout</strong>
                  <span>Wipes Flow cookies and locks Google Flow immediately when a user signs out.</span>
                </div>
                <input id="polLogout" type="checkbox" class="switch" checked />
              </div>
              <div class="toggle-row">
                <div>
                  <strong>Block Flow on Expired / Basic Plan</strong>
                  <span>Wipes Flow cookies and blocks access as soon as a subscription expires.</span>
                </div>
                <input id="polExpired" type="checkbox" class="switch" checked />
              </div>
              <div class="field" style="max-width:240px;margin-top:10px;">
                <label for="polGraceSec">Portal Close Grace Period (Seconds)</label>
                <input id="polGraceSec" class="input" type="number" min="5" max="120" value="15" />
              </div>
              <button id="savePolicyBtn" type="button" class="btn btn-primary" style="margin-bottom:18px;">Save Access Policy</button>

              <div style="border-top:1px solid var(--border);padding-top:14px;">
                <div class="card-title" style="margin-bottom:8px;">Public Account Registration Control</div>
                <div class="toggle-row">
                  <div>
                    <strong id="pubRegStatusLabel">Public Registration is ENABLED</strong>
                    <span>Allow visitors to self-register trial accounts from the public portal.</span>
                  </div>
                  <input id="pubRegToggle" type="checkbox" class="switch" />
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Platform Maintenance Mode &amp; Custom URL</h3>
                  <p class="card-desc">Pause normal user logins during upgrades or redirect users to your WhatsApp/update page.</p>
                </div>
              </div>
              <div class="toggle-row">
                <div>
                  <strong>Enable Maintenance Mode</strong>
                  <span>Blocks normal user login and displays your maintenance notice.</span>
                </div>
                <input id="maintEnabled" type="checkbox" class="switch" />
              </div>
              <div class="field">
                <label for="maintTitle">Maintenance Headline / Title</label>
                <input id="maintTitle" class="input" type="text" placeholder="Scheduled System Maintenance" />
              </div>
              <div class="field">
                <label for="maintMessage">Maintenance Notice Message</label>
                <textarea id="maintMessage" class="textarea" style="min-height:85px;"></textarea>
              </div>
              <div class="grid-2">
                <div class="field">
                  <label for="maintCustomUrl">Custom Redirect / Support URL</label>
                  <input id="maintCustomUrl" class="input" type="url" placeholder="https://whatsapp.com/channel/..." />
                </div>
                <div class="field">
                  <label for="maintCustomUrlTitle">Custom URL Button Label</label>
                  <input id="maintCustomUrlTitle" class="input" type="text" placeholder="Join WhatsApp Channel for Updates" />
                </div>
              </div>
              <div class="toggle-row">
                <div>
                  <strong>Direct Auto-Redirect to Custom URL</strong>
                  <span>Automatically forwards visitors to your custom URL when maintenance is active.</span>
                </div>
                <input id="maintRedirect" type="checkbox" class="switch" />
              </div>
              <button id="saveMaintBtn" type="button" class="btn btn-primary">Save Maintenance Settings</button>
            </div>
          </div>
        </section>

        <!-- VIEW 8: WELCOME MESSAGE TEMPLATE -->
        <section id="view-template" class="view-pane hidden">
          <div class="grid-2">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Welcome Message Template</h3>
                  <p class="card-desc">Auto-opens with 1-click Copy whenever you create a user or click Share Login. Click any chip to insert.</p>
                </div>
              </div>
              <div id="tplChips" class="chips"></div>
              <div class="field">
                <label for="tplTextarea">Template Body</label>
                <textarea id="tplTextarea" class="textarea" style="min-height:220px;"></textarea>
              </div>
              <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <button id="saveTplBtn" type="button" class="btn btn-primary">Save Template</button>
                <button id="resetTplBtn" type="button" class="btn btn-ghost">Reset Default</button>
              </div>
            </div>

            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Live Message Preview</h3>
                  <p class="card-desc">Preview with sample user credentials and current portal origin.</p>
                </div>
              </div>
              <div id="tplPreviewBox" class="preview-box"></div>
            </div>
          </div>
        </section>

        <!-- VIEW 9: SETTINGS & BROADCAST -->
        <section id="view-settings" class="view-pane hidden">
          <div class="grid-2">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Platform &amp; Communication Channels</h3>
                  <p class="card-desc">Brand settings, fallback WhatsApp support for direct users, and official community channel link.</p>
                </div>
              </div>
              <div class="field">
                <label for="setBrandName">Brand Name</label>
                <input id="setBrandName" class="input" type="text" placeholder="Flow by Pak" />
              </div>
              <div class="field">
                <label for="setWhatsapp">Direct Support WhatsApp Number or URL</label>
                <input id="setWhatsapp" class="input" type="text" placeholder="+923001234567 or https://wa.me/..." />
              </div>
              <div class="field">
                <label for="setChannel">Community Channel Link (WhatsApp / Telegram)</label>
                <input id="setChannel" class="input" type="url" placeholder="https://whatsapp.com/channel/..." />
              </div>
              <div class="field">
                <label for="setSupportEmail">Support Email</label>
                <input id="setSupportEmail" class="input" type="email" placeholder="flowbypak.site@gmail.com" />
              </div>
              <button id="savePlatformSettingsBtn" type="button" class="btn btn-primary">Save Platform Settings</button>
            </div>

            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Global Broadcast Announcement</h3>
                  <p class="card-desc">Publish a live banner alert across the user dashboard.</p>
                </div>
              </div>
              <div class="toggle-row">
                <div>
                  <strong>Show Broadcast Banner</strong>
                  <span>Toggle announcement visibility on user portal</span>
                </div>
                <input id="bcEnabled" type="checkbox" class="switch" />
              </div>
              <div class="grid-2">
                <div class="field">
                  <label for="bcTitle">Banner Title</label>
                  <input id="bcTitle" class="input" type="text" placeholder="Platform Update" />
                </div>
                <div class="field">
                  <label for="bcType">Alert Style</label>
                  <select id="bcType" class="select">
                    <option value="info">Info (Blue)</option>
                    <option value="success">Success (Green)</option>
                    <option value="warning">Warning (Amber)</option>
                    <option value="danger">Alert (Red)</option>
                  </select>
                </div>
              </div>
              <div class="field">
                <label for="bcMessage">Message Content</label>
                <textarea id="bcMessage" class="textarea" style="min-height:105px;"></textarea>
              </div>
              <button id="saveBroadcastBtn" type="button" class="btn btn-primary">Publish Broadcast</button>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- MODAL: ADD / EDIT USER -->
  <div id="userModal" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <h3 id="userModalTitle">Add New User</h3>
        <button type="button" class="btn btn-ghost btn-icon" data-close-modal="userModal" aria-label="Close">&times;</button>
      </div>
      <form id="userForm">
        <div class="modal-body">
          <input type="hidden" id="uEditId" />
          <div class="grid-2">
            <div class="field">
              <label for="uName">Full Name</label>
              <input id="uName" class="input" type="text" placeholder="Ali Khan" />
            </div>
            <div class="field">
              <label for="uEmail">Email Address</label>
              <input id="uEmail" class="input" type="email" placeholder="user@example.com" required />
            </div>
          </div>
          <div class="field">
            <label for="uPassword">Password <span id="uPassHint" style="font-weight:400;color:var(--text-muted);"></span></label>
            <div style="display:flex;gap:8px;">
              <input id="uPassword" class="input" type="text" placeholder="Min 4 characters" />
              <button id="genPassBtn" type="button" class="btn btn-ghost">Generate</button>
            </div>
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="uPlan">Subscription Plan</label>
              <select id="uPlan" class="select">
                <option value="pro">Pro (20 Days · 25k Credits)</option>
                <option value="max">Max (30 Days · 45k Credits)</option>
                <option value="demo">Demo (Timed Auto-Lock · 2h Default)</option>
                <option value="basic">Basic (Free)</option>
              </select>
            </div>
            <div class="field" id="uDaysFieldWrap">
              <label for="uDays">Validity (Days)</label>
              <input id="uDays" class="input" type="number" min="0" max="3650" value="20" />
            </div>
            <div class="field hidden" id="uDemoHoursFieldWrap">
              <label for="uDemoHours">Demo Duration (Hours — Auto-Closes)</label>
              <input id="uDemoHours" class="input" type="number" min="0.05" max="720" step="any" value="2" placeholder="2" />
              <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:6px;">
                <button type="button" class="btn btn-ghost btn-xs" data-demo-hrs="0.5">30m</button>
                <button type="button" class="btn btn-ghost btn-xs" data-demo-hrs="1">1h</button>
                <button type="button" class="btn btn-ghost btn-xs" data-demo-hrs="2">2h</button>
                <button type="button" class="btn btn-ghost btn-xs" data-demo-hrs="6">6h</button>
                <button type="button" class="btn btn-ghost btn-xs" data-demo-hrs="12">12h</button>
                <button type="button" class="btn btn-ghost btn-xs" data-demo-hrs="24">24h</button>
              </div>
            </div>
          </div>
          <div class="grid-3">
            <div class="field">
              <label for="uStatus">Account Status</label>
              <select id="uStatus" class="select">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
            <div class="field">
              <label for="uCredits">AI Credits</label>
              <input id="uCredits" class="input" type="number" min="0" max="10000000" value="45000" />
            </div>
            <div class="field">
              <label for="uMaxDevices">Allowed Devices</label>
              <input id="uMaxDevices" class="input" type="number" min="1" max="50" value="1" />
            </div>
          </div>
          <div class="field">
            <label for="uAddedBy">Added By (Admin Name)</label>
            <input id="uAddedBy" class="input" type="text" maxlength="60" placeholder="Admin" value="Admin" />
          </div>
          <!-- Edit-only Quick Actions -->
          <div id="uEditQuickTools" class="hidden" style="margin-top:10px;padding:12px;border-radius:10px;background:var(--bg-elevated);border:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">
            <div>
              <div style="font-size:12.5px;font-weight:700;">Instant Limit Reset &amp; Unlock</div>
              <div style="font-size:11.5px;color:var(--text-muted);">Restores plan credits, resets quota &amp; clears device locks.</div>
            </div>
            <button id="uModalResetLimitBtn" type="button" class="btn btn-amber btn-sm">Reset Limit Now</button>
          </div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" data-close-modal="userModal">Cancel</button>
          <button id="userFormSubmitBtn" type="submit" class="btn btn-primary">Create User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: COPY / SHARE WELCOME MESSAGE -->
  <div id="welcomeCopyModal" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <div>
          <h3 id="welcomeModalHeading">Share Client Login</h3>
          <p id="welcomeCopySub" style="font-size:12px;color:var(--text-muted);margin-top:2px;"></p>
        </div>
        <button type="button" class="btn btn-ghost btn-icon" data-close-modal="welcomeCopyModal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <div class="field">
          <label for="welcomeCopyText">Ready-to-Send Message</label>
          <textarea id="welcomeCopyText" class="textarea" style="min-height:200px;"></textarea>
        </div>
      </div>
      <div class="modal-foot">
        <button id="welcomeEditTplJumpBtn" type="button" class="btn btn-ghost">Edit Template</button>
        <a id="welcomeWaLink" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-ghost">Share via WhatsApp</a>
        <button id="welcomeCopyBtn" type="button" class="btn btn-emerald">Copy Message</button>
      </div>
    </div>
  </div>

  <!-- MODAL: EDIT SESSION COOKIES (PASTE JSON/NETSCAPE OR UPLOAD FILE) -->
  <div id="slotModal" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <h3 id="slotModalTitle">Update Session Cookies</h3>
        <button type="button" class="btn btn-ghost btn-icon" data-close-modal="slotModal" aria-label="Close">&times;</button>
      </div>
      <form id="slotForm">
        <div class="modal-body">
          <input type="hidden" id="slotNumInput" />
          <div class="field">
            <label for="slotNameInput">Session Display Name</label>
            <input id="slotNameInput" class="input" type="text" placeholder="Session 1" />
          </div>
          <div class="field">
            <label for="slotFileInput">Upload Cookie File (.json / .txt — optional)</label>
            <input id="slotFileInput" class="input" type="file" accept=".json,.txt" />
          </div>
          <div class="field">
            <div style="display:flex;justify-content:space-between;align-items:center;">
              <label for="slotCookiesInput">Cookies Content (JSON Array or Netscape Text)</label>
              <button id="slotClearInputBtn" type="button" class="btn btn-ghost btn-xs">Clear Input</button>
            </div>
            <textarea id="slotCookiesInput" class="textarea mono" placeholder='[{"name":"SID","value":"...","domain":".google.com"}]'></textarea>
          </div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" data-close-modal="slotModal">Cancel</button>
          <button id="slotSaveBtn" type="submit" class="btn btn-primary">Save &amp; Sync Cookies</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: ADD / EDIT RESELLER -->
  <div id="resellerModal" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <h3 id="resellerModalTitle">Add Reseller</h3>
        <button type="button" class="btn btn-ghost btn-icon" data-close-modal="resellerModal" aria-label="Close">&times;</button>
      </div>
      <form id="resellerForm">
        <div class="modal-body">
          <input type="hidden" id="rEditId" />
          <div class="grid-2">
            <div class="field">
              <label for="rName">Reseller Name</label>
              <input id="rName" class="input" type="text" placeholder="Partner Name" required />
            </div>
            <div class="field">
              <label for="rEmail">Email</label>
              <input id="rEmail" class="input" type="email" placeholder="partner@example.com" required />
            </div>
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="rPassword">Password</label>
              <input id="rPassword" class="input" type="text" placeholder="Leave blank on edit to keep" />
            </div>
            <div class="field">
              <label for="rWhatsapp">WhatsApp Number / Link</label>
              <input id="rWhatsapp" class="input" type="text" placeholder="03001234567 or https://wa.me/..." />
            </div>
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="rAllowedUsers">Allowed Users Quota</label>
              <input id="rAllowedUsers" class="input" type="number" min="1" max="10000" value="50" />
            </div>
            <div class="field">
              <label for="rStatus">Account Status</label>
              <select id="rStatus" class="select">
                <option value="active">Active</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="rProRate">Assigned Pro Price (Rs. PKR)</label>
              <input id="rProRate" class="input" type="number" min="0" placeholder="Default (500)" />
            </div>
            <div class="field">
              <label for="rMaxRate">Assigned Max Price (Rs. PKR)</label>
              <input id="rMaxRate" class="input" type="number" min="0" placeholder="Default (800)" />
            </div>
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="rProCredits">Pro Plan Credits (Optional Override)</label>
              <input id="rProCredits" class="input" type="number" min="100" step="100" placeholder="Default (25,000)" />
            </div>
            <div class="field">
              <label for="rMaxCredits">Max Plan Credits (Optional Override)</label>
              <input id="rMaxCredits" class="input" type="number" min="100" step="100" placeholder="Default (45,000)" />
            </div>
          </div>
          <div class="grid-2">
            <div class="field">
              <label for="rDiscount">Flat Discount (Rs.)</label>
              <input id="rDiscount" class="input" type="number" min="0" value="0" />
            </div>
            <div class="field">
              <label for="rDiscountPct">Discount (%)</label>
              <input id="rDiscountPct" class="input" type="number" min="0" max="100" value="0" />
            </div>
          </div>
          <div class="field">
            <label for="rAddedBy">Added By (Admin Name)</label>
            <input id="rAddedBy" class="input" type="text" maxlength="60" placeholder="Admin" value="Admin" />
          </div>
          <div class="field" id="rTotalPaidWrap">
            <label for="rTotalPaid">Recorded Paid Balance (Rs. PKR) — Adjust directly if needed</label>
            <input id="rTotalPaid" class="input" type="number" min="0" step="1" placeholder="0" />
          </div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" data-close-modal="resellerModal">Cancel</button>
          <button id="resellerSubmitBtn" type="submit" class="btn btn-primary">Save Reseller</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: MANAGE RESELLER DUES & RECORD PAYMENT -->
  <div id="resellerDuesModal" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <div>
          <h3 id="duesModalTitle">Manage Reseller Dues</h3>
          <div id="duesModalSub" style="font-size:12px;color:var(--text-muted);margin-top:2px;"></div>
        </div>
        <button type="button" class="btn btn-ghost btn-icon" data-close-modal="resellerDuesModal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="duesResellerId" />
        <div class="grid-3" style="margin-bottom:14px;gap:10px;">
          <div class="kpi-card" style="padding:11px;">
            <div class="kpi-top"><span>Total Charges</span></div>
            <div class="kpi-val is-compact" id="duesTotalCharges">Rs. 0</div>
          </div>
          <div class="kpi-card" style="padding:11px;">
            <div class="kpi-top"><span>Total Paid</span></div>
            <div class="kpi-val is-compact" id="duesTotalPaid" style="color:#34d399;">Rs. 0</div>
          </div>
          <div class="kpi-card" style="padding:11px;">
            <div class="kpi-top"><span>Balance Due</span></div>
            <div class="kpi-val is-compact" id="duesBalanceVal" style="color:#fbbf24;">Rs. 0</div>
          </div>
        </div>

        <div id="duesUsersSummary" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px;"></div>

        <div style="padding:13px;border-radius:10px;background:var(--bg-elevated);border:1px solid var(--border);margin-bottom:12px;">
          <div style="font-size:13px;font-weight:700;margin-bottom:4px;">Option 1 · Full Clearance (1-Click)</div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:11px;">Sets reseller balance due to <strong>Rs. 0</strong>, marks all reseller users as <strong>PAID &amp; ACTIVE</strong>, and approves pending payment receipts immediately on the reseller portal.</div>
          <button id="duesClearAllBtn" type="button" class="btn btn-emerald" style="width:100%;">Clear All Dues (Set Rs. 0)</button>
        </div>

        <form id="duesPartialForm" style="padding:13px;border-radius:10px;background:var(--bg-elevated);border:1px solid var(--border);">
          <div style="font-size:13px;font-weight:700;margin-bottom:4px;">Option 2 · Record Partial / Custom Payment</div>
          <div style="font-size:12px;color:var(--text-muted);margin-bottom:10px;">Add a specific PKR amount received from this reseller without clearing remaining balance.</div>
          <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <input id="duesPartialAmount" class="input" type="number" min="1" step="1" placeholder="Enter amount in Rs. (e.g. 2500)" style="flex:1;min-width:150px;" required />
            <button id="duesRecordPartialBtn" type="submit" class="btn btn-primary">Record Payment</button>
          </div>
        </form>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-ghost" data-close-modal="resellerDuesModal">Close</button>
      </div>
    </div>
  </div>

  <!-- MODAL: VIEW RESELLER'S USERS -->
  <div id="resellerUsersModal" class="modal-backdrop hidden">
    <div class="modal modal-lg" role="dialog" aria-modal="true">
      <div class="modal-head">
        <div style="min-width:0;">
          <h3 id="rUsersModalTitle">Reseller Users</h3>
          <div id="rUsersModalSub" style="font-size:12px;color:var(--text-muted);margin-top:2px;overflow:hidden;text-overflow:ellipsis;"></div>
        </div>
        <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
          <button id="rUsersOpenDuesBtn" type="button" class="btn btn-amber btn-sm">Manage Dues</button>
          <button type="button" class="btn btn-ghost btn-icon" data-close-modal="resellerUsersModal" aria-label="Close">&times;</button>
        </div>
      </div>
      <div class="modal-body">
        <div id="rUsersStatsRow" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:12px;"></div>
        <div class="toolbar" style="margin-bottom:12px;">
          <div class="search-box">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input id="rUsersSearchInput" class="input" type="search" placeholder="Search this reseller's users by name or email..." />
          </div>
          <div class="filter-group">
            <select id="rUsersPayFilter" class="select" style="width:auto;min-width:140px;">
              <option value="all">All Users</option>
              <option value="paid">Paid</option>
              <option value="unpaid">Unpaid / Due</option>
              <option value="overdue">Overdue</option>
              <option value="expired">Expired</option>
            </select>
          </div>
        </div>
        <div class="table-wrap" style="max-height:55vh;overflow-y:auto;">
          <table>
            <thead>
              <tr>
                <th>User</th>
                <th>Plan &amp; Credits</th>
                <th>Days Left</th>
                <th>Payment</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="rUsersTableBody">
              <tr><td colspan="6" class="empty-state">Loading users...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-foot">
        <span id="rUsersFooterCount" style="margin-right:auto;font-size:12px;color:var(--text-muted);"></span>
        <button type="button" class="btn btn-ghost" data-close-modal="resellerUsersModal">Close</button>
      </div>
    </div>
  </div>

  <!-- MODAL: PAYMENT PROOF LIGHTBOX -->
  <div id="proofModal" class="modal-backdrop hidden">
    <div class="modal modal-lg" role="dialog" aria-modal="true" style="max-width:min(94vw,680px);">
      <div class="modal-head">
        <h3 id="proofModalTitle">Payment Receipt Proof</h3>
        <button type="button" class="btn btn-ghost btn-icon" data-close-modal="proofModal" aria-label="Close">&times;</button>
      </div>
      <div class="modal-body" style="text-align:center;">
        <img id="proofModalImg" src="" alt="Payment Proof" style="max-width:100%;max-height:70vh;border-radius:10px;border:1px solid var(--border-strong);" />
        <p id="proofModalMeta" style="margin-top:10px;font-size:12.5px;color:var(--text-muted);"></p>
      </div>
      <div class="modal-foot">
        <a id="proofModalOpenRaw" href="#" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-sm">Open Full Size</a>
        <button type="button" class="btn btn-primary btn-sm" data-close-modal="proofModal">Close</button>
      </div>
    </div>
  </div>

  <div id="toast" class="toast hidden" role="status" aria-live="polite"></div>

  <script>
  (() => {
    'use strict';

    const TOKEN_KEY = 'flowbypak_admin_token';
    const DEFAULT_TEMPLATE =
      'Welcome to PakFlow!\n\n' +
      'Portal Link: {{site_link}}\n' +
      'Name: {{name}}\n' +
      'Email: {{email}}\n' +
      'Password: {{password}}\n' +
      'Plan: {{plan}} ({{days}} days)\n\n' +
      'Sign in on the portal, download the latest Chrome extension from the top bar, and launch Google Flow in 1 click.\n' +
      'Support: {{whatsapp}}';
    const PLACEHOLDERS = ['{{site_link}}', '{{email}}', '{{password}}', '{{name}}', '{{plan}}', '{{days}}', '{{whatsapp}}'];

    const VIEW_META = {
      users: ['Users & Approvals', 'Manage accounts, quick extend, reset limits & device locks'],
      sessions: ['Flow Sessions (1–15)', 'Google Flow (1–10), Nano Banana Pro (11–15) & live auto-shuffle'],
      resellers: ['Resellers & Payouts', 'Partners, daily analytics, payment receipts, bank & rates'],
      analytics: ['Analytics & Revenue', 'Estimated MRR, plan distribution & expiring account alerts'],
      plans: ['Plans & Pricing', 'Manage subscription tiers, PKR pricing, credits & visibility'],
      extension: ['Extension Builder', 'Regenerate Chrome .zip from repo, upload builds & trial access'],
      security: ['Access Lock & Policy', 'Flow locks, public registration & maintenance mode'],
      template: ['Welcome Template', 'Customize the message copied when creating or sharing accounts'],
      settings: ['Settings & Broadcast', 'Platform channels, support contacts & global announcements'],
    };

    const PLAN_KEYS = ['basic', 'pro', 'max', 'demo'];

    let state = {
      users: [],
      usersPage: 1,
      usersLimit: 50,
      usersTotalFiltered: 0,
      usersTotalPages: 1,
      usersStats: { total: 0, active: 0, activeDemo: 0, activeDemoSite: 0, activeDemoReseller: 0, totalDemo: 0, expired: 0, suspended: 0 },
      pendingApprovals: [],
      stats: null,
      expiringUsers: [],
      slots: [],
      activeSlotGroup: 'flow',
      resellers: [],
      resellerAnalytics: null,
      resellerConfig: null,
      payments: [],
      controls: null,
      settings: null,
      activeView: 'users',
      activeResellerTab: 'partners',
      countdown: { enabled: false, targetMs: 0, epoch: 1, intervalMinutes: 60, lastShuffledAt: null },
      autoRefreshedTarget: 0,
    };

    const $ = (id) => document.getElementById(id);
    let toastTimer = null;

    function showToast(msg, isErr) {
      const el = $('toast');
      el.textContent = msg;
      el.className = 'toast ' + (isErr ? 'err' : 'ok');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => el.classList.add('hidden'), 3600);
    }

    function esc(s) {
      return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Overflow-proof PKR formatter for KPI cards (exact value preserved in tooltip)
    function formatPKRCard(amount) {
      const n = Math.round(Number(amount || 0));
      const exact = 'Rs. ' + n.toLocaleString();
      if (Math.abs(n) >= 1_000_000_000) {
        return { text: 'Rs. ' + (n / 1_000_000_000).toFixed(2) + 'B', exact, compact: true };
      }
      if (Math.abs(n) >= 10_000_000) {
        return { text: 'Rs. ' + (n / 1_000_000).toFixed(2) + 'M', exact, compact: true };
      }
      return { text: exact, exact, compact: exact.length >= 11 };
    }

    function setPkrKpi(elId, subId, amount, fallbackSub) {
      const el = $(elId);
      if (!el) return;
      const f = formatPKRCard(amount);
      el.textContent = f.text;
      el.title = f.exact;
      el.classList.toggle('is-compact', f.compact);
      if (subId && $(subId)) {
        $(subId).textContent = (f.text !== f.exact) ? ('Exact: ' + f.exact) : fallbackSub;
        $(subId).title = f.exact;
      }
    }

    function getToken() {
      try { return localStorage.getItem(TOKEN_KEY) || ''; } catch { return ''; }
    }

    async function api(path, opts = {}) {
      const headers = Object.assign({ Accept: 'application/json' }, opts.headers || {});
      const tok = getToken();
      if (tok) headers.Authorization = 'Bearer ' + tok;
      if (opts.body && typeof opts.body === 'string' && !headers['Content-Type']) {
        headers['Content-Type'] = 'application/json';
      }
      const res = await fetch(path, Object.assign({}, opts, { headers, cache: 'no-store' }));
      const data = await res.json().catch(() => ({}));
      if (res.status === 401 || res.status === 403) {
        if (path !== '/api/admin/login') {
          localStorage.removeItem(TOKEN_KEY);
          showLogin();
        }
        throw new Error(data.message || data.error || 'Session expired');
      }
      if (!res.ok) throw new Error(data.message || data.error || ('HTTP ' + res.status));
      return data;
    }

    function formatClock(sec) {
      const s = Math.max(0, Math.floor(sec));
      const h = Math.floor(s / 3600);
      const m = Math.floor((s % 3600) / 60);
      const r = s % 60;
      const p = (n) => (n < 10 ? '0' + n : String(n));
      return p(h) + ':' + p(m) + ':' + p(r);
    }

    function formatWa(raw) {
      const s = String(raw || '').trim();
      if (!s) return 'Contact support';
      if (/^https?:\/\//i.test(s)) return s;
      const d = s.replace(/\D/g, '');
      if (d.length >= 9) return 'https://wa.me/' + (d.startsWith('0') && d.length === 11 ? '92' + d.slice(1) : d);
      return s;
    }

    function fillTemplate(tpl, vars) {
      let out = String(tpl || DEFAULT_TEMPLATE);
      const map = {
        '{{site_link}}': vars.site_link || window.location.origin,
        '{{email}}': vars.email || '',
        '{{password}}': vars.password || '(Existing Password)',
        '{{name}}': vars.name || vars.email || 'User',
        '{{plan}}': String(vars.plan || 'Pro').toUpperCase(),
        '{{days}}': String(vars.days ?? 30),
        '{{whatsapp}}': formatWa(vars.whatsapp),
      };
      for (const [k, v] of Object.entries(map)) out = out.split(k).join(String(v));
      return out;
    }

    function openShareLoginModal(user, headingText) {
      const tpl = (state.controls && state.controls.messageTemplate) || DEFAULT_TEMPLATE;
      const wa = (state.settings && (state.settings.whatsappSupport || state.settings.whatsappNumber || state.settings.whatsappPhone)) || '';
      const isDemo = String(user.plan || '').toLowerCase() === 'demo';
      const demoHrsVal = Number(user.demoHours || 2);
      const durationLabel = isDemo
        ? ((demoHrsVal < 1 ? (Math.round(demoHrsVal * 60) + ' Mins') : (demoHrsVal + ' Hours')) + ' Demo')
        : String(user.days ?? user.daysRemaining ?? 30);
      let msg = fillTemplate(tpl, {
        site_link: window.location.origin,
        name: user.name || user.username || (user.email || '').split('@')[0],
        email: user.email || '',
        password: user.password || user.plainPasswordHint || '(Use your saved password)',
        plan: user.plan || 'pro',
        days: durationLabel,
        whatsapp: wa,
      });
      if (isDemo) {
        msg = msg.replace(/\(\s*([0-9.]+\s*(?:Hours|Mins)\s*Demo)\s*days\s*\)/gi, '($1)');
      }
      $('welcomeModalHeading').textContent = headingText || 'Share Client Login';
      $('welcomeCopySub').textContent = (user.email || '') + ' · ' + String(user.plan || 'pro').toUpperCase() + ' (' + (isDemo ? durationLabel : ((user.days ?? user.daysRemaining ?? 0) + ' days')) + ')';
      $('welcomeCopyText').value = msg;
      $('welcomeWaLink').href = 'https://wa.me/?text=' + encodeURIComponent(msg);
      $('welcomeCopyModal').classList.remove('hidden');
    }

    // ── Countdown Engine ──
    function syncCountdown(sh) {
      if (!sh) return;
      const enabled = Boolean(sh.enabled);
      let targetMs = 0;
      if (enabled) {
        if (typeof sh.remainingSeconds === 'number') targetMs = Date.now() + Math.max(0, sh.remainingSeconds) * 1000;
        else if (sh.nextShuffleTs) targetMs = Number(sh.nextShuffleTs) * 1000;
        else if (sh.nextShuffleAt) targetMs = Date.parse(sh.nextShuffleAt) || 0;
      }
      state.countdown = {
        enabled,
        targetMs,
        epoch: Number(sh.shuffleEpoch || 1),
        intervalMinutes: Number(sh.intervalMinutes || 60),
        lastShuffledAt: sh.lastShuffledAt || null,
      };
      tickCountdown();
    }

    function tickCountdown() {
      const c = state.countdown;
      const topPill = $('topCountdownPill');
      const topTxt = $('topCountdownText');
      const clock = $('liveCountdownClock');
      const meta = $('liveCountdownMeta');

      if (!c.enabled || !c.targetMs) {
        topPill.classList.add('hidden');
        clock.textContent = 'Timer Off';
        clock.classList.add('is-off');
        meta.textContent = 'Epoch #' + c.epoch + (c.lastShuffledAt ? (' · Last reshuffled ' + new Date(c.lastShuffledAt).toLocaleTimeString()) : ' · Enable timer below to auto-rotate sessions');
        return;
      }
      const left = Math.max(0, Math.ceil((c.targetMs - Date.now()) / 1000));
      const fmt = formatClock(left);
      topPill.classList.remove('hidden');
      topTxt.textContent = 'Shuffle ' + fmt;
      clock.textContent = fmt;
      clock.classList.remove('is-off');
      meta.textContent = 'Epoch #' + c.epoch + ' · Every ' + c.intervalMinutes + ' min' + (c.lastShuffledAt ? (' · Last reshuffle ' + new Date(c.lastShuffledAt).toLocaleTimeString()) : '');

      if (left <= 0 && state.autoRefreshedTarget !== c.targetMs) {
        state.autoRefreshedTarget = c.targetMs;
        setTimeout(() => loadControlsOnly(), 1200);
      }
    }
    setInterval(tickCountdown, 1000);

    // ── View Navigation & Mobile Drawer ──
    function setView(viewName) {
      state.activeView = viewName;
      document.querySelectorAll('.view-pane').forEach((el) => el.classList.toggle('hidden', el.id !== 'view-' + viewName));
      document.querySelectorAll('.nav-btn[data-view]').forEach((b) => b.classList.toggle('active', b.dataset.view === viewName));
      const meta = VIEW_META[viewName] || ['Admin Console', ''];
      $('topbarTitle').textContent = meta[0];
      $('topbarSub').textContent = meta[1];
      closeDrawer();
      if (viewName === 'analytics') loadAnalyticsView();
      if (viewName === 'resellers' && state.activeResellerTab === 'analytics') loadResellerAnalytics();
      if (viewName === 'resellers' && state.activeResellerTab === 'config') loadResellerConfig();
    }

    function openDrawer() {
      $('sidebar').classList.add('is-open');
      $('sidebarBackdrop').classList.remove('hidden');
    }
    function closeDrawer() {
      $('sidebar').classList.remove('is-open');
      $('sidebarBackdrop').classList.add('hidden');
    }

    document.querySelectorAll('.nav-btn[data-view]').forEach((btn) => {
      btn.addEventListener('click', () => setView(btn.dataset.view));
    });
    $('openSidebarBtn').addEventListener('click', openDrawer);
    $('closeSidebarBtn').addEventListener('click', closeDrawer);
    $('sidebarBackdrop').addEventListener('click', closeDrawer);
    $('topCountdownPill').addEventListener('click', () => setView('sessions'));
    $('topMaintPill').addEventListener('click', () => setView('security'));

    // Reseller subtabs
    document.querySelectorAll('#resellerSubtabs .subtab-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        const tab = btn.dataset.rtab;
        state.activeResellerTab = tab;
        document.querySelectorAll('#resellerSubtabs .subtab-btn').forEach((b) => b.classList.toggle('active', b === btn));
        document.querySelectorAll('.rtab-pane').forEach((p) => p.classList.toggle('hidden', p.id !== 'rtab-' + tab));
        if (tab === 'analytics') loadResellerAnalytics();
        if (tab === 'config') loadResellerConfig();
      });
    });

    // Slot group subtabs (Flow 1-10 vs Nano Banana 11-15)
    document.querySelectorAll('#slotGroupTabs .subtab-btn').forEach((btn) => {
      btn.addEventListener('click', () => {
        state.activeSlotGroup = btn.dataset.slotTab;
        document.querySelectorAll('#slotGroupTabs .subtab-btn').forEach((b) => b.classList.toggle('active', b === btn));
        renderSessions();
      });
    });

    // Modal close helpers
    document.querySelectorAll('[data-close-modal]').forEach((b) => {
      b.addEventListener('click', () => $(b.dataset.closeModal).classList.add('hidden'));
    });
    document.querySelectorAll('.modal-backdrop').forEach((bd) => {
      bd.addEventListener('click', (e) => { if (e.target === bd) bd.classList.add('hidden'); });
    });

    // ── Data Loading ──
    async function loadControlsOnly() {
      try {
        const cfg = await api('/api/admin/controls/config');
        state.controls = cfg;
        renderControls(cfg);
      } catch {}
    }

    function buildUsersUrl(page) {
      const params = new URLSearchParams();
      params.set('page', String(Math.max(1, page || state.usersPage || 1)));
      params.set('limit', String(state.usersLimit || 50));
      const q = $('userSearchInput').value.trim();
      const planF = $('userPlanFilter').value;
      const statusF = $('userStatusFilter').value;
      const addedByF = $('userAddedByFilter') ? $('userAddedByFilter').value : 'all';
      if (q) params.set('q', q);
      if (planF && planF !== 'all') params.set('plan', planF);
      if (statusF && statusF !== 'all') params.set('status', statusF);
      if (addedByF && addedByF !== 'all') params.set('addedBy', addedByF);
      return '/api/admin/users?' + params.toString();
    }

    function syncAddedByFilterOptions(opts) {
      const sel = $('userAddedByFilter');
      if (!sel || !Array.isArray(opts)) return;
      const curVal = sel.value || 'all';
      const seen = new Set(['all']);
      const list = ['Admin'];
      opts.forEach((v) => {
        const clean = String(v || '').trim();
        if (clean && !list.some((x) => x.toLowerCase() === clean.toLowerCase())) list.push(clean);
      });
      sel.innerHTML = '<option value="all">All Added By</option>' + list.map((name) => {
        seen.add(name);
        return '<option value="' + esc(name) + '">' + esc(name) + '</option>';
      }).join('');
      sel.value = seen.has(curVal) ? curVal : 'all';
    }

    function applyUsersResponse(data) {
      state.users = Array.isArray(data.users) ? data.users : [];
      state.usersPage = Math.max(1, Number(data.page || 1));
      state.usersLimit = Math.max(10, Number(data.limit || state.usersLimit || 50));
      state.usersTotalFiltered = Number(data.totalFiltered ?? data.total ?? state.users.length);
      state.usersTotalPages = Math.max(1, Number(data.totalPages || 1));
      if (Array.isArray(data.addedByOptions)) syncAddedByFilterOptions(data.addedByOptions);
      if (data.stats && typeof data.stats === 'object') {
        state.usersStats = {
          total: Number(data.stats.total || 0),
          active: Number(data.stats.active || 0),
          activeDemo: Number(data.stats.activeDemo || 0),
          activeDemoSite: Number(data.stats.activeDemoSite || 0),
          activeDemoReseller: Number(data.stats.activeDemoReseller || 0),
          totalDemoSite: Number(data.stats.totalDemoSite || 0),
          totalDemoReseller: Number(data.stats.totalDemoReseller || 0),
          totalDemo: Number(data.stats.totalDemo || 0),
          expired: Number(data.stats.expired || 0),
          suspended: Number(data.stats.suspended || 0),
        };
      }
      renderUsers();
    }

    async function loadUsersPage(page) {
      const targetPage = Math.max(1, Number(page || 1));
      try {
        const data = await api(buildUsersUrl(targetPage));
        applyUsersResponse(data);
      } catch (err) {
        showToast(err.message, true);
      }
    }

    async function loadAnalyticsView() {
      const [statsRes, expRes] = await Promise.allSettled([
        api('/api/admin/stats'),
        api('/api/admin/users?status=expiring&limit=100'),
      ]);
      if (statsRes.status === 'fulfilled') state.stats = statsRes.value;
      if (expRes.status === 'fulfilled') state.expiringUsers = Array.isArray(expRes.value.users) ? expRes.value.users : [];
      renderAnalytics();
    }

    async function loadResellerAnalytics() {
      try {
        const res = await api('/api/admin/resellers/analytics');
        state.resellerAnalytics = res;
        renderResellerAnalytics();
      } catch (err) {
        showToast(err.message, true);
      }
    }

    async function loadResellerConfig() {
      try {
        const res = await api('/api/admin/settings/reseller-config');
        state.resellerConfig = res;
        renderResellerConfig(res);
      } catch (err) {
        showToast(err.message, true);
      }
    }

    async function loadAll() {
      const [usersRes, pendingRes, slotsRes, cfgRes, resellersRes, paymentsRes, settingsRes] = await Promise.allSettled([
        api(buildUsersUrl(state.usersPage)),
        api('/api/admin/pending-approvals'),
        api('/api/admin/slots'),
        api('/api/admin/controls/config'),
        api('/api/admin/resellers'),
        api('/api/admin/reseller-payments'),
        api('/api/admin/settings'),
      ]);

      if (usersRes.status === 'fulfilled') applyUsersResponse(usersRes.value);
      if (pendingRes.status === 'fulfilled') {
        state.pendingApprovals = Array.isArray(pendingRes.value.pending) ? pendingRes.value.pending : [];
        renderPendingApprovals();
      }
      if (slotsRes.status === 'fulfilled') {
        state.slots = Array.isArray(slotsRes.value.slots) ? slotsRes.value.slots : [];
      }
      if (cfgRes.status === 'fulfilled') {
        state.controls = cfgRes.value;
        renderControls(cfgRes.value);
      }
      renderSessions();

      if (resellersRes.status === 'fulfilled') {
        state.resellers = Array.isArray(resellersRes.value.resellers) ? resellersRes.value.resellers : [];
        renderResellers();
      }
      if (paymentsRes.status === 'fulfilled') {
        state.payments = Array.isArray(paymentsRes.value.payments) ? paymentsRes.value.payments : [];
        renderPayments();
      }
      if (settingsRes.status === 'fulfilled') {
        state.settings = settingsRes.value.settings || {};
        renderSettings(state.settings);
        renderPlansEditor(state.settings);
      }
      if (state.activeView === 'analytics') loadAnalyticsView();
      if (state.activeView === 'resellers' && state.activeResellerTab === 'analytics') loadResellerAnalytics();
      if (state.activeView === 'resellers' && state.activeResellerTab === 'config') loadResellerConfig();
    }

    // ── Render: Pending Approvals ──
    function renderPendingApprovals() {
      const list = state.pendingApprovals || [];
      const card = $('pendingApprovalsCard');
      $('pendingCountNum').textContent = String(list.length);
      if (!list.length) {
        card.classList.add('hidden');
        return;
      }
      card.classList.remove('hidden');
      $('pendingTableBody').innerHTML = list.map((u) => (
        '<tr>' +
          '<td><div class="user-cell-name">' + esc(u.name || u.username || 'User') + '</div><div class="user-cell-email">' + esc(u.email) + '</div></td>' +
          '<td><span class="badge badge-blue">' + esc(String(u.plan || 'pro').toUpperCase()) + '</span> <span style="font-size:11.5px;color:var(--text-muted);">(' + Number(u.requestedDays || 20) + 'd)</span></td>' +
          '<td>' + esc(u.resellerName || u.resellerEmail || 'Direct') + '</td>' +
          '<td><div class="actions-cell">' +
            '<button type="button" class="btn btn-emerald btn-sm" data-pend-act="approve" data-id="' + u.id + '">Approve</button>' +
            '<button type="button" class="btn btn-danger btn-sm" data-pend-act="reject" data-id="' + u.id + '">Reject</button>' +
          '</div></td>' +
        '</tr>'
      )).join('');
    }

    $('pendingTableBody').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-pend-act]');
      if (!btn) return;
      const id = Number(btn.dataset.id);
      const act = btn.dataset.pendAct;
      btn.disabled = true;
      try {
        const res = await api('/api/admin/users/' + id + '/' + act, { method: 'PUT', body: '{}' });
        showToast(res.message || ('Request ' + act + 'd'));
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // ── Render: Users ──
    function renderUsers() {
      const stStats = state.usersStats || { total: 0, active: 0, activeDemo: 0, activeDemoSite: 0, activeDemoReseller: 0, totalDemoSite: 0, totalDemoReseller: 0, totalDemo: 0, expired: 0, suspended: 0 };
      const activeDemoSite = Number(stStats.activeDemoSite ?? 0);
      const activeDemoRes = Number(stStats.activeDemoReseller ?? 0);
      const totalDemoSite = Number(stStats.totalDemoSite ?? 0);
      const totalDemoRes = Number(stStats.totalDemoReseller ?? 0);
      const assignedTrialSlot = Number((state.settings && state.settings.demoConfig && state.settings.demoConfig.assignedSlot) || 0);
      $('kpiTotal').textContent = stStats.total.toLocaleString();
      $('kpiActive').textContent = stStats.active.toLocaleString();
      $('kpiExpired').textContent = stStats.expired.toLocaleString();
      $('kpiSuspended').textContent = stStats.suspended.toLocaleString();
      if ($('kpiSiteTrial')) $('kpiSiteTrial').textContent = activeDemoSite.toLocaleString();
      if ($('kpiSiteTrialSub')) {
        $('kpiSiteTrialSub').textContent = totalDemoSite.toLocaleString() + ' total · ' + (assignedTrialSlot >= 1 ? ('Slot ' + assignedTrialSlot) : 'Auto Slot');
      }
      if ($('kpiResellerDemo')) $('kpiResellerDemo').textContent = activeDemoRes.toLocaleString();
      if ($('kpiResellerDemoSub')) {
        $('kpiResellerDemoSub').textContent = totalDemoRes.toLocaleString() + ' total reseller demos';
      }
      $('navUsersCount').textContent = stStats.total.toLocaleString();

      const rows = state.users;
      const totalF = state.usersTotalFiltered;
      const startIdx = totalF > 0 ? ((state.usersPage - 1) * state.usersLimit + 1) : 0;
      const endIdx = totalF > 0 ? Math.min(totalF, startIdx + rows.length - 1) : 0;
      $('usersPageInfo').textContent = totalF > 0
        ? ('Showing ' + startIdx.toLocaleString() + '–' + endIdx.toLocaleString() + ' of ' + totalF.toLocaleString() + ' users')
        : 'Showing 0 users';
      $('usersPageIndicator').textContent = 'Page ' + state.usersPage + ' / ' + state.usersTotalPages;
      $('usersPrevBtn').disabled = state.usersPage <= 1;
      $('usersNextBtn').disabled = state.usersPage >= state.usersTotalPages;

      const tbody = $('usersTableBody');
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No users match your filter.</td></tr>';
        return;
      }

      tbody.innerHTML = rows.map((u) => {
        const plan = String(u.plan || 'basic').toUpperCase();
        const isDemo = plan === 'DEMO' || Boolean(u.isDemo);
        const days = Number(u.daysRemaining || 0);
        const expMs = u.planExpiresAt ? Date.parse(String(u.planExpiresAt)) : 0;
        const remSec = expMs > Date.now() ? Math.floor((expMs - Date.now()) / 1000) : Number(u.remainingSeconds || 0);
        const isDemoActive = isDemo && remSec > 0;
        const isSusp = String(u.status || '').toLowerCase() === 'suspended' || u.isLocked;
        const statusBadge = isSusp
          ? '<span class="badge badge-rose">Suspended</span>'
          : ((isDemo ? isDemoActive : (days > 0 || plan === 'BASIC'))
            ? '<span class="badge badge-emerald">' + (isDemo ? 'Demo Active' : 'Active') + '</span>'
            : '<span class="badge badge-amber">Expired</span>');
        const planBadge = plan === 'BASIC' || plan === 'FREE'
          ? '<span class="badge badge-muted">' + esc(plan) + '</span>'
          : (isDemo
            ? '<span class="badge badge-amber">DEMO</span>'
            : '<span class="badge badge-blue">' + esc(plan) + '</span>');
        let validityLabel = days + 'd left';
        if (isDemo) {
          if (remSec <= 0) {
            validityLabel = '0m (Ended)';
          } else if (remSec < 3600) {
            validityLabel = Math.max(1, Math.ceil(remSec / 60)) + 'm left';
          } else {
            const dh = Math.floor(remSec / 3600);
            const dm = Math.floor((remSec % 3600) / 60);
            validityLabel = dm > 0 ? (dh + 'h ' + dm + 'm left') : (dh + 'h left');
          }
        }
        const resellerTag = u.resellerName
          ? (' <span class="badge badge-muted" style="margin-left:4px;font-size:10px;">' + esc(u.resellerName) + '</span>')
          : (' <span class="badge badge-muted" style="margin-left:4px;font-size:10px;opacity:0.85;">by ' + esc(u.addedBy || 'Admin') + '</span>');
        const credNum = Number(u.credits ?? 0);
        return (
          '<tr>' +
            '<td><div class="user-cell-name">' + esc(u.name || u.username || 'User') + resellerTag + '</div><div class="user-cell-email">' + esc(u.email) + '</div></td>' +
            '<td>' + planBadge + '<div style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);margin-top:3px;">' + credNum.toLocaleString() + ' cr</div></td>' +
            '<td>' +
              '<div style="font-family:var(--font-mono);font-weight:700;margin-bottom:4px;">' + validityLabel + '</div>' +
              '<div class="quick-ext-row">' +
                '<button type="button" class="btn btn-ghost btn-xs" data-act="ext-days" data-days="7" data-id="' + u.id + '">+7d</button>' +
                '<button type="button" class="btn btn-ghost btn-xs" data-act="ext-days" data-days="15" data-id="' + u.id + '">+15d</button>' +
                '<button type="button" class="btn btn-ghost btn-xs" data-act="ext-days" data-days="30" data-id="' + u.id + '">+30d</button>' +
              '</div>' +
            '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td style="font-family:var(--font-mono);"><span class="badge badge-muted">' + Math.max(1, Number(u.maxDevices || 1)) + ' Dev' + (Number(u.maxDevices || 1) > 1 ? 's' : '') + '</span> <span style="font-size:11px;color:var(--text-muted);">(' + Number(u.deviceSwitchCount || 0) + ' sw)</span></td>' +
            '<td><div class="actions-cell">' +
              '<button type="button" class="btn btn-ghost btn-xs" data-act="share-user" data-id="' + u.id + '" title="Copy / Share Login">Share</button>' +
              '<button type="button" class="btn btn-ghost btn-xs" data-act="edit-user" data-id="' + u.id + '">Edit</button>' +
              '<button type="button" class="btn btn-ghost btn-xs" data-act="reset-limit" data-id="' + u.id + '" title="Restore credits, quota &amp; unlock">Reset</button>' +
              '<button type="button" class="btn btn-ghost btn-xs" data-act="reset-dev" data-id="' + u.id + '" title="Reset Device Lock">Unlock</button>' +
              '<button type="button" class="btn btn-danger btn-xs" data-act="del-user" data-id="' + u.id + '">Delete</button>' +
            '</div></td>' +
          '</tr>'
        );
      }).join('');
    }

    let userSearchDebounce = null;
    $('userSearchInput').addEventListener('input', () => {
      clearTimeout(userSearchDebounce);
      userSearchDebounce = setTimeout(() => loadUsersPage(1), 280);
    });
    $('userPlanFilter').addEventListener('change', () => loadUsersPage(1));
    $('userStatusFilter').addEventListener('change', () => loadUsersPage(1));
    if ($('userAddedByFilter')) $('userAddedByFilter').addEventListener('change', () => loadUsersPage(1));
    $('usersPrevBtn').addEventListener('click', () => { if (state.usersPage > 1) loadUsersPage(state.usersPage - 1); });
    $('usersNextBtn').addEventListener('click', () => { if (state.usersPage < state.usersTotalPages) loadUsersPage(state.usersPage + 1); });

    $('usersTableBody').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-act]');
      if (!btn) return;
      const id = Number(btn.dataset.id);
      const act = btn.dataset.act;
      const user = state.users.find((u) => Number(u.id) === id);
      if (!user) return;

      if (act === 'edit-user') {
        openUserModal(user);
      } else if (act === 'share-user') {
        openShareLoginModal(user, 'Share Client Login · ' + (user.name || user.email));
      } else if (act === 'ext-days') {
        const addDays = Number(btn.dataset.days || 30);
        btn.disabled = true;
        try {
          await api('/api/admin/users/' + id, { method: 'PUT', body: JSON.stringify({ addDays }) });
          showToast('Extended ' + user.email + ' by +' + addDays + ' days');
          await loadUsersPage(state.usersPage);
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      } else if (act === 'reset-limit') {
        btn.disabled = true;
        try {
          const res = await api('/api/admin/users/' + id + '/reset-limit', { method: 'POST', body: '{}' });
          showToast(res.message || 'Limit & credits reset');
          await loadUsersPage(state.usersPage);
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      } else if (act === 'reset-dev') {
        btn.disabled = true;
        try {
          const res = await api('/api/admin/users/' + id + '/reset-device-lock', { method: 'POST', body: '{}' });
          showToast(res.message || 'Device lock reset');
          await loadUsersPage(state.usersPage);
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      } else if (act === 'del-user') {
        if (!confirm('Delete user ' + user.email + '?')) return;
        btn.disabled = true;
        try {
          await api('/api/admin/users/' + id, { method: 'DELETE' });
          showToast('User deleted');
          await loadAll();
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      }
    });

    // ── Add / Edit User Modal ──
    function getDefaultCreditsForPlan(planKey) {
      const p = String(planKey || 'pro').toLowerCase();
      const rc = (state.resellerConfig && state.resellerConfig.planCredits) || (state.settings && state.settings.resellerPlanCredits) || {};
      if (p === 'demo') return Number(rc.demo || 2000);
      if (p === 'pro' || p === 'plus') return Number(rc.pro || 25000);
      if (p === 'max' || p === 'ultra') return Number(rc.max || 45000);
      if (p === 'vibe') return 10000;
      if (p === 'basic' || p === 'free') return 0;
      return 25000;
    }

    function getDefaultDaysForPlan(plan) {
      const p = String(plan || 'pro').toLowerCase();
      const pd = (state.resellerConfig && state.resellerConfig.planDurations)
        || (state.settings && state.settings.planDurations)
        || {};
      const pl = (state.settings && state.settings.plans) || {};
      if (p === 'pro' || p === 'plus') return Math.max(1, Number(pd.pro || (pl.pro && pl.pro.days) || 20));
      if (p === 'max' || p === 'ultra') return Math.max(1, Number(pd.max || (pl.max && pl.max.days) || 30));
      if (p === 'basic' || p === 'free' || p === 'demo') return 0;
      return Math.max(1, Number(pd.pro || 20));
    }

    function syncUserPlanOptionLabels() {
      const sel = $('uPlan');
      if (!sel) return;
      const proDays = getDefaultDaysForPlan('pro');
      const maxDays = getDefaultDaysForPlan('max');
      const proCr = Math.round(getDefaultCreditsForPlan('pro') / 1000);
      const maxCr = Math.round(getDefaultCreditsForPlan('max') / 1000);
      const optPro = sel.querySelector('option[value="pro"]');
      const optMax = sel.querySelector('option[value="max"]');
      if (optPro) optPro.textContent = 'Pro (' + proDays + ' Days · ' + proCr + 'k Credits)';
      if (optMax) optMax.textContent = 'Max (' + maxDays + ' Days · ' + maxCr + 'k Credits)';
    }

    function syncUserPlanModeUI(applyDefaults) {
      const p = String($('uPlan').value || 'pro').toLowerCase();
      const isDemo = (p === 'demo');
      $('uDaysFieldWrap').classList.toggle('hidden', isDemo);
      $('uDemoHoursFieldWrap').classList.toggle('hidden', !isDemo);
      if (applyDefaults) {
        if (isDemo) {
          $('uDemoHours').value = 2;
        } else {
          $('uDays').value = getDefaultDaysForPlan(p);
        }
        $('uCredits').value = getDefaultCreditsForPlan(p);
      }
    }

    $('uPlan').addEventListener('change', () => {
      const isCreate = !$('uEditId').value.trim();
      syncUserPlanModeUI(isCreate || $('uPlan').value === 'demo');
    });

    document.querySelectorAll('[data-demo-hrs]').forEach((chip) => {
      chip.addEventListener('click', () => {
        $('uDemoHours').value = chip.dataset.demoHrs;
      });
    });

    function openUserModal(user) {
      $('userForm').reset();
      syncUserPlanOptionLabels();
      if (user) {
        const rawPlanLow = String(user.plan || 'pro').toLowerCase();
        const uPlanLow = rawPlanLow === 'plus' ? 'pro' : (rawPlanLow === 'ultra' ? 'max' : (['basic', 'pro', 'max', 'demo'].includes(rawPlanLow) ? rawPlanLow : 'pro'));
        $('userModalTitle').textContent = 'Edit User';
        $('uEditId').value = user.id;
        $('uName').value = user.name || '';
        $('uEmail').value = user.email || '';
        $('uPassword').value = '';
        $('uPassword').required = false;
        $('uPassHint').textContent = '(leave blank to keep current)';
        $('uPlan').value = uPlanLow;
        syncUserPlanModeUI(false);
        if (uPlanLow === 'demo') {
          const remHrs = user.planExpiresAt
            ? Math.max(0.1, Math.round(((Date.parse(user.planExpiresAt) - Date.now()) / 3600000) * 10) / 10)
            : (user.demoHours || 2);
          $('uDemoHours').value = Number.isFinite(remHrs) && remHrs > 0 ? remHrs : (user.demoHours || 2);
        } else {
          $('uDays').value = user.daysRemaining ?? getDefaultDaysForPlan(uPlanLow);
        }
        $('uStatus').value = String(user.status || 'active').toLowerCase() === 'suspended' ? 'suspended' : 'active';
        $('uCredits').value = user.credits ?? getDefaultCreditsForPlan(uPlanLow);
        $('uMaxDevices').value = Math.max(1, Number(user.maxDevices || 1));
        if ($('uAddedBy')) $('uAddedBy').value = user.addedBy || 'Admin';
        $('uEditQuickTools').classList.remove('hidden');
        $('userFormSubmitBtn').textContent = 'Save Changes';
      } else {
        $('userModalTitle').textContent = 'Add New User';
        $('uEditId').value = '';
        $('uPassword').required = true;
        $('uPassHint').textContent = '';
        $('uPlan').value = 'pro';
        $('uStatus').value = 'active';
        $('uMaxDevices').value = 1;
        if ($('uAddedBy')) {
          let lastAb = 'Admin';
          try { lastAb = localStorage.getItem('pakflow_last_added_by') || 'Admin'; } catch (_) {}
          $('uAddedBy').value = lastAb;
        }
        syncUserPlanModeUI(true);
        $('uEditQuickTools').classList.add('hidden');
        $('userFormSubmitBtn').textContent = 'Create User';
      }
      $('userModal').classList.remove('hidden');
    }

    $('uModalResetLimitBtn').addEventListener('click', async () => {
      const id = Number($('uEditId').value);
      if (!id) return;
      const btn = $('uModalResetLimitBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/users/' + id + '/reset-limit', { method: 'POST', body: '{}' });
        showToast(res.message || 'Limit reset & unlocked');
        $('userModal').classList.add('hidden');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('topAddUserBtn').addEventListener('click', () => openUserModal(null));
    $('genPassBtn').addEventListener('click', () => {
      const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#';
      let out = '';
      for (let i = 0; i < 10; i++) out += chars[Math.floor(Math.random() * chars.length)];
      $('uPassword').value = out;
    });

    $('userForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const editId = $('uEditId').value.trim();
      const chosenPlan = $('uPlan').value;
      const isDemo = String(chosenPlan || '').toLowerCase() === 'demo';
      const rawCredits = parseInt($('uCredits').value, 10);
      const demoHrsVal = parseFloat($('uDemoHours').value);
      const demoHours = Number.isFinite(demoHrsVal) && demoHrsVal > 0 ? demoHrsVal : 2;
      const rawMaxDevs = parseInt($('uMaxDevices').value, 10);
      const maxDevices = Number.isFinite(rawMaxDevs) && rawMaxDevs >= 1 ? Math.min(50, rawMaxDevs) : 1;
      const addedByVal = ($('uAddedBy') && $('uAddedBy').value.trim()) ? $('uAddedBy').value.trim() : 'Admin';
      try { localStorage.setItem('pakflow_last_added_by', addedByVal); } catch (_) {}
      const payload = {
        name: $('uName').value.trim(),
        email: $('uEmail').value.trim(),
        plan: chosenPlan,
        status: $('uStatus').value,
        credits: Number.isFinite(rawCredits) && rawCredits >= 0 ? rawCredits : getDefaultCreditsForPlan(chosenPlan),
        maxDevices,
        addedBy: addedByVal,
      };
      if (isDemo) {
        payload.demoHours = demoHours;
      } else {
        payload.days = parseInt($('uDays').value, 10) || 0;
      }
      const pass = $('uPassword').value.trim();
      if (pass) payload.password = pass;

      const btn = $('userFormSubmitBtn');
      btn.disabled = true;
      try {
        if (editId) {
          await api('/api/admin/users/' + editId, { method: 'PUT', body: JSON.stringify(payload) });
          $('userModal').classList.add('hidden');
          showToast('User updated');
        } else {
          await api('/api/admin/users', { method: 'POST', body: JSON.stringify(payload) });
          $('userModal').classList.add('hidden');
          showToast(isDemo ? ('Demo user created (' + demoHours + 'h auto-lock)') : 'User created');
          openShareLoginModal({
            name: payload.name,
            email: payload.email,
            password: pass,
            plan: payload.plan,
            days: payload.days,
            demoHours: isDemo ? demoHours : null,
          }, isDemo ? 'Demo Account Created — Copy Login' : 'User Created — Copy Welcome Message');
        }
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('welcomeCopyText').addEventListener('input', () => {
      $('welcomeWaLink').href = 'https://wa.me/?text=' + encodeURIComponent($('welcomeCopyText').value);
    });
    $('welcomeCopyBtn').addEventListener('click', async () => {
      const txt = $('welcomeCopyText').value;
      try { await navigator.clipboard.writeText(txt); }
      catch { $('welcomeCopyText').select(); document.execCommand('copy'); }
      showToast('Welcome message copied to clipboard');
    });
    $('welcomeEditTplJumpBtn').addEventListener('click', () => {
      $('welcomeCopyModal').classList.add('hidden');
      setView('template');
    });

    // ── Render: Sessions (Flow 1-10 & Nano Banana 11-15) ──
    function renderSessions() {
      const grid = $('sessionsGrid');
      const byNum = {};
      state.slots.forEach((s) => { byNum[Number(s.slotNumber)] = s; });

      let flowActiveCount = 0;
      for (let n = 1; n <= 10; n++) {
        const s = byNum[n];
        if (s && String(s.status || '').toUpperCase() === 'ACTIVE' && Number(s.cookieCount || 0) > 0) flowActiveCount++;
      }
      $('navSessionsCount').textContent = flowActiveCount + '/10';

      const pool = (state.controls && state.controls.serverShuffle && Array.isArray(state.controls.serverShuffle.poolSlots))
        ? state.controls.serverShuffle.poolSlots.map(Number)
        : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

      const isNano = state.activeSlotGroup === 'nano';
      const startSlot = isNano ? 11 : 1;
      const endSlot = isNano ? 15 : 10;
      const cards = [];

      for (let n = startSlot; n <= endSlot; n++) {
        const defaultName = isNano ? ('Nano Banana ' + (n - 10)) : ('Session ' + n);
        const s = byNum[n] || { slotNumber: n, name: defaultName, status: 'EMPTY', cookieCount: 0 };
        const isActive = String(s.status || '').toUpperCase() === 'ACTIVE' && Number(s.cookieCount || 0) > 0;
        const displayName = String(s.name || defaultName).replace(/^Server\s*/i, isNano ? 'Nano Slot ' : 'Session ');
        const updated = s.updatedAt ? new Date(s.updatedAt).toLocaleString() : 'Not uploaded';
        const poolHtml = isNano ? '' : (
          '<label class="session-pool-check">' +
            '<input type="checkbox" name="sessionPoolCb" value="' + n + '" ' + (pool.includes(n) ? 'checked' : '') + ' />' +
            '<span>Include in Auto-Shuffle Pool</span>' +
          '</label>'
        );
        cards.push(
          '<div class="session-card ' + (isActive ? 'is-active' : '') + '">' +
            '<div class="session-card-head">' +
              '<div class="session-name">' + esc(displayName) + '</div>' +
              (isActive ? '<span class="badge badge-emerald">ACTIVE</span>' : (Number(s.cookieCount || 0) > 0 ? '<span class="badge badge-amber">STANDBY</span>' : '<span class="badge badge-muted">EMPTY</span>')) +
            '</div>' +
            '<div class="session-meta">' +
              '<span>' + Number(s.cookieCount || 0) + ' cookies</span>' +
              '<span style="font-family:var(--font-sans);font-size:11px;">' + esc(updated) + '</span>' +
            '</div>' +
            poolHtml +
            '<div class="session-actions">' +
              '<button type="button" class="btn btn-ghost btn-sm" data-slot-act="edit" data-num="' + n + '">Cookies</button>' +
              '<button type="button" class="btn btn-ghost btn-sm" data-slot-act="toggle" data-num="' + n + '">' + (isActive ? 'Standby' : 'Activate') + '</button>' +
              '<button type="button" class="btn btn-danger btn-sm" data-slot-act="clear" data-num="' + n + '">Clear</button>' +
            '</div>' +
          '</div>'
        );
      }
      grid.innerHTML = cards.join('');
    }

    $('bulkEnableSlotsBtn').addEventListener('click', async () => {
      const btn = $('bulkEnableSlotsBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/slots/bulk', { method: 'POST', body: JSON.stringify({ action: 'enable_all' }) });
        showToast(res.message || 'All loaded slots enabled');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('bulkStandbySlotsBtn').addEventListener('click', async () => {
      if (!confirm('Set all active slots to STANDBY?')) return;
      const btn = $('bulkStandbySlotsBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/slots/bulk', { method: 'POST', body: JSON.stringify({ action: 'disable_all' }) });
        showToast(res.message || 'All slots set to STANDBY');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('sessionsGrid').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-slot-act]');
      if (!btn) return;
      const num = Number(btn.dataset.num);
      const act = btn.dataset.slotAct;
      const slot = state.slots.find((s) => Number(s.slotNumber) === num) || { slotNumber: num, name: 'Session ' + num, cookies: [] };

      if (act === 'edit') {
        $('slotModalTitle').textContent = 'Update Slot ' + num + ' Cookies';
        $('slotNumInput').value = num;
        $('slotNameInput').value = String(slot.name || ('Session ' + num));
        $('slotFileInput').value = '';
        $('slotCookiesInput').value = Array.isArray(slot.cookies) && slot.cookies.length ? JSON.stringify(slot.cookies, null, 2) : '';
        $('slotModal').classList.remove('hidden');
      } else if (act === 'toggle') {
        btn.disabled = true;
        try {
          const nextStatus = String(slot.status || '').toUpperCase() === 'ACTIVE' ? 'STANDBY' : 'ACTIVE';
          await api('/api/admin/slots/' + num + '/status', { method: 'POST', body: JSON.stringify({ status: nextStatus }) });
          showToast('Slot ' + num + ' set to ' + nextStatus);
          await loadAll();
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      } else if (act === 'clear') {
        if (!confirm('Clear all cookies from Slot ' + num + '?')) return;
        btn.disabled = true;
        try {
          await api('/api/admin/slots/' + num, { method: 'DELETE' });
          showToast('Slot ' + num + ' cleared');
          await loadAll();
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      }
    });

    $('slotFileInput').addEventListener('change', (e) => {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = () => { $('slotCookiesInput').value = String(reader.result || ''); showToast('Loaded ' + file.name); };
      reader.readAsText(file);
    });
    $('slotClearInputBtn').addEventListener('click', () => { $('slotCookiesInput').value = ''; $('slotFileInput').value = ''; });

    $('slotForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const num = Number($('slotNumInput').value);
      const name = $('slotNameInput').value.trim() || ('Session ' + num);
      const raw = $('slotCookiesInput').value.trim();
      if (!raw) return showToast('Paste cookies JSON/Netscape or upload a file', true);
      let payloadCookies = raw;
      if (raw.startsWith('[') || raw.startsWith('{')) {
        try { payloadCookies = JSON.parse(raw); } catch { payloadCookies = raw; }
      }
      const btn = $('slotSaveBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/slots/' + num, { method: 'POST', body: JSON.stringify({ name, cookies: payloadCookies }) });
        $('slotModal').classList.add('hidden');
        showToast(res.message || ('Slot ' + num + ' saved'));
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    document.querySelectorAll('[data-mins]').forEach((chip) => {
      chip.addEventListener('click', () => { $('shuffleIntervalInput').value = chip.dataset.mins; });
    });

    $('saveShuffleBtn').addEventListener('click', async () => {
      const btn = $('saveShuffleBtn');
      const checked = Array.from(document.querySelectorAll('input[name="sessionPoolCb"]:checked')).map((i) => Number(i.value));
      const poolSlots = checked.length
        ? checked
        : ((state.controls && state.controls.serverShuffle && state.controls.serverShuffle.poolSlots) || []);
      btn.disabled = true;
      try {
        const res = await api('/api/admin/controls/server-shuffle', {
          method: 'PUT',
          body: JSON.stringify({
            enabled: $('shuffleEnabledToggle').checked,
            intervalMinutes: parseInt($('shuffleIntervalInput').value, 10) || 60,
            defaultSlot: parseInt($('defaultSlotSelect').value, 10) || 1,
            poolSlots,
            resetTimer: true,
          }),
        });
        if (res.serverShuffle) syncCountdown(res.serverShuffle);
        showToast(res.message || 'Session shuffle settings saved');
        await loadControlsOnly();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('reshuffleNowBtn').addEventListener('click', async () => {
      const btn = $('reshuffleNowBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/controls/reshuffle-now', { method: 'POST', body: '{}' });
        if (res.serverShuffle) syncCountdown(res.serverShuffle);
        showToast(res.message || 'All user sessions reshuffled');
        await loadControlsOnly();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // ── Render: Controls ──
    function renderControls(cfg) {
      if (!cfg) return;
      const sh = cfg.serverShuffle || {};
      $('shuffleEnabledToggle').checked = Boolean(sh.enabled);
      $('shuffleIntervalInput').value = sh.intervalMinutes || 60;
      $('defaultSlotSelect').value = String(sh.defaultSlot || 1);
      syncCountdown(sh);

      const ext = cfg.extension || {};
      const ver = String(ext.version || '1.7');
      const fname = String(ext.fileName || ('PakFlow_v' + ver + '.zip'));
      $('navExtVer').textContent = 'v' + ver;
      $('extLivePill').textContent = 'v' + ver + (ext.fileSize ? (' · ' + ext.fileSize) : '');
      $('extLiveMeta').textContent = fname + (ext.updatedAt ? (' · Updated ' + new Date(ext.updatedAt).toLocaleString()) : '') + (cfg.extensionSourceReady ? ' · Repo source ready' : '');
      $('extLiveDownloadBtn').href = ext.downloadUrl || '/downloads/latest';
      $('extLiveDownloadBtn').setAttribute('download', fname);

      const parts = ver.split('.');
      const suggested = parts.length >= 2 && !isNaN(parseInt(parts[1], 10)) ? (parts[0] + '.' + (parseInt(parts[1], 10) + 1)) : ver;
      if (!$('extNewVer').value) $('extNewVer').value = suggested;
      $('extMinVer').value = cfg.minExtVersion || ver;
      $('extReleaseNote').value = ext.releaseNote || '';
      if ($('extWebStoreUrl')) $('extWebStoreUrl').value = ext.webStoreUrl || '';

      const ap = cfg.accessPolicy || {};
      $('polPortalOpen').checked = Boolean(ap.requirePortalOpen);
      $('polLogout').checked = ap.blockOnLogout !== false;
      $('polExpired').checked = ap.blockOnExpired !== false;
      $('polGraceSec').value = ap.portalGraceSeconds || 15;

      const tpl = cfg.messageTemplate || DEFAULT_TEMPLATE;
      $('tplTextarea').value = tpl;
      updateTplPreview();
    }

    // Extension Regenerate, Upload & Settings
    $('regenExtBtn').addEventListener('click', async () => {
      const btn = $('regenExtBtn');
      const version = $('extNewVer').value.trim();
      if (!version) return showToast('Enter a version number (e.g. 1.9)', true);
      btn.disabled = true;
      btn.textContent = 'Building ZIP...';
      try {
        const res = await api('/api/admin/extension/regenerate', {
          method: 'POST',
          body: JSON.stringify({
            version,
            minExtVersion: $('extMinVer').value.trim(),
            releaseNote: $('extReleaseNote').value.trim(),
            portalUrl: $('extPortalUrl').value.trim(),
          }),
        });
        showToast(res.message || 'Extension regenerated');
        $('extNewVer').value = '';
        await loadControlsOnly();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; btn.textContent = 'Regenerate & Publish ZIP'; }
    });

    $('uploadExtBtn').addEventListener('click', async () => {
      const btn = $('uploadExtBtn');
      const file = $('uploadZipInput').files && $('uploadZipInput').files[0];
      if (!file) return showToast('Select a .zip file first', true);
      btn.disabled = true;
      btn.textContent = 'Uploading...';
      try {
        const b64 = await new Promise((resolve, reject) => {
          const r = new FileReader();
          r.onload = () => resolve(String(r.result || ''));
          r.onerror = () => reject(new Error('Failed to read file'));
          r.readAsDataURL(file);
        });
        const res = await api('/api/admin/upload-extension', {
          method: 'POST',
          body: JSON.stringify({
            type: 'flow',
            fileName: file.name,
            fileData: b64,
            version: $('uploadZipVer').value.trim(),
            releaseNote: $('uploadZipNote').value.trim(),
          }),
        });
        showToast(res.message || 'Extension uploaded');
        $('uploadZipInput').value = '';
        await loadControlsOnly();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; btn.textContent = 'Upload & Publish ZIP'; }
    });

    $('trialExtAccessToggle').addEventListener('change', async () => {
      try {
        const res = await api('/api/admin/settings/trial-extension-access', {
          method: 'PUT',
          body: JSON.stringify({ allowTrialExtensionAccess: $('trialExtAccessToggle').checked }),
        });
        showToast(res.message || 'Trial extension access updated');
      } catch (err) { showToast(err.message, true); }
    });

    if ($('buildWebStoreBtn')) {
      $('buildWebStoreBtn').addEventListener('click', async () => {
        const btn = $('buildWebStoreBtn');
        const version = ($('wsExtVer').value || '1.8').trim();
        if (!version) return showToast('Enter a Web Store version (e.g. 1.8)', true);
        btn.disabled = true;
        btn.textContent = 'Building Web Store ZIP...';
        try {
          const res = await api('/api/admin/extension/build-webstore', {
            method: 'POST',
            body: JSON.stringify({
              version,
              portalUrl: ($('wsPortalUrl') && $('wsPortalUrl').value.trim()) || '',
            }),
          });
          const pkg = res.package || {};
          if (pkg.downloadUrl && $('downloadWebStoreLink')) {
            $('downloadWebStoreLink').href = pkg.downloadUrl;
            $('downloadWebStoreLink').setAttribute('download', pkg.fileName || ('PakFlow_WebStore_v' + version + '.zip'));
            $('downloadWebStoreLink').textContent = 'Download ' + (pkg.fileName || ('PakFlow_WebStore_v' + version + '.zip'));
            $('downloadWebStoreLink').click();
          }
          showToast(res.message || 'Web Store ZIP ready');
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; btn.textContent = 'Build & Download Web Store ZIP'; }
      });
    }

    $('saveExtMetaBtn').addEventListener('click', async () => {
      const btn = $('saveExtMetaBtn');
      btn.disabled = true;
      try {
        const curFlow = (state.settings && state.settings.extensions && state.settings.extensions.flow) || {};
        const wsUrl = $('extWebStoreUrl') ? $('extWebStoreUrl').value.trim() : (curFlow.webStoreUrl || '');
        const updatedFlow = Object.assign({}, curFlow, {
          webStoreUrl: wsUrl,
          downloadUrl: $('extDirectUrl').value.trim() || curFlow.downloadUrl || '/downloads/latest',
          instructions: $('extInstructions').value,
        });
        await api('/api/admin/settings', {
          method: 'PUT',
          body: JSON.stringify({
            extensions: {
              flow: updatedFlow,
            },
          }),
        });
        if (state.settings && state.settings.extensions) {
          state.settings.extensions.flow = updatedFlow;
        }
        showToast(wsUrl ? 'Chrome Web Store 1-Click URL saved & live on User Portal!' : 'Extension settings saved (Standard ZIP mode active)');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // Policy, Public Registration & Maintenance
    $('savePolicyBtn').addEventListener('click', async () => {
      const btn = $('savePolicyBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/controls/access-policy', {
          method: 'PUT',
          body: JSON.stringify({
            requirePortalOpen: $('polPortalOpen').checked,
            blockOnLogout: $('polLogout').checked,
            blockOnExpired: $('polExpired').checked,
            portalGraceSeconds: parseInt($('polGraceSec').value, 10) || 15,
          }),
        });
        showToast(res.message || 'Access lock policy saved');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('pubRegToggle').addEventListener('change', async () => {
      const enabled = $('pubRegToggle').checked;
      $('pubRegStatusLabel').textContent = enabled ? 'Public Registration is ENABLED (Open)' : 'Public Registration is DISABLED (Restricted)';
      try {
        await api('/api/admin/settings', { method: 'PUT', body: JSON.stringify({ allowPublicRegistration: enabled }) });
        showToast(enabled ? 'Public registration enabled' : 'Public registration disabled');
      } catch (err) { showToast(err.message, true); }
    });

    $('saveMaintBtn').addEventListener('click', async () => {
      const btn = $('saveMaintBtn');
      btn.disabled = true;
      try {
        const enabled = $('maintEnabled').checked;
        const res = await api('/api/admin/maintenance', {
          method: 'POST',
          body: JSON.stringify({
            enabled,
            title: $('maintTitle').value.trim(),
            message: $('maintMessage').value.trim(),
            customUrl: $('maintCustomUrl').value.trim(),
            customUrlTitle: $('maintCustomUrlTitle').value.trim(),
            redirectToCustomUrl: $('maintRedirect').checked,
          }),
        });
        $('topMaintPill').classList.toggle('hidden', !enabled);
        showToast(res.message || 'Maintenance settings updated');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // Template Editor
    function updateTplPreview() {
      $('tplPreviewBox').textContent = fillTemplate($('tplTextarea').value, {
        site_link: window.location.origin,
        name: 'Ali Khan',
        email: 'ali@example.com',
        password: 'Pass@1234',
        plan: 'PRO',
        days: 30,
        whatsapp: (state.settings && (state.settings.whatsappSupport || state.settings.whatsappNumber)) || '+923001234567',
      });
    }
    PLACEHOLDERS.forEach((ph) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'chip';
      b.textContent = ph;
      b.addEventListener('click', () => {
        const ta = $('tplTextarea');
        const st = ta.selectionStart ?? ta.value.length;
        const en = ta.selectionEnd ?? ta.value.length;
        ta.value = ta.value.slice(0, st) + ph + ta.value.slice(en);
        ta.focus();
        ta.setSelectionRange(st + ph.length, st + ph.length);
        updateTplPreview();
      });
      $('tplChips').appendChild(b);
    });
    $('tplTextarea').addEventListener('input', updateTplPreview);
    $('resetTplBtn').addEventListener('click', () => { $('tplTextarea').value = DEFAULT_TEMPLATE; updateTplPreview(); });
    $('saveTplBtn').addEventListener('click', async () => {
      const btn = $('saveTplBtn');
      const tpl = $('tplTextarea').value.trim();
      if (!tpl) return showToast('Template cannot be empty', true);
      btn.disabled = true;
      try {
        await api('/api/admin/controls/message-template', { method: 'PUT', body: JSON.stringify({ template: tpl }) });
        if (state.controls) state.controls.messageTemplate = tpl;
        showToast('Welcome message template saved');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // ── Resellers, Analytics, Receipts & Config ──
    let activeResellerUsersState = { resellerId: 0, reseller: null, billing: null, users: [] };

    function renderResellers() {
      const all = state.resellers || [];
      $('navResellersCount').textContent = all.length.toLocaleString();

      let totalUsers = 0, totalDue = 0, totalPaid = 0;
      all.forEach((r) => {
        totalUsers += Number(r.usersCreated || 0);
        totalDue += Number(r.balanceDue || 0);
        totalPaid += Number(r.totalPaid || 0);
      });
      $('kpiResellersTotal').textContent = all.length.toLocaleString();
      $('kpiResellerUsersTotal').textContent = totalUsers.toLocaleString();
      setPkrKpi('kpiResellersDue', 'kpiResellersDueSub', totalDue, 'Pending collection');
      setPkrKpi('kpiResellersPaid', 'kpiResellersPaidSub', totalPaid, 'Cleared payouts');

      const q = ($('resellerSearchInput')?.value || '').trim().toLowerCase();
      const dueF = $('resellerDueFilter')?.value || 'all';
      const filtered = all.filter((r) => {
        if (q) {
          const hay = ((r.name || '') + ' ' + (r.email || '') + ' ' + (r.whatsapp || '')).toLowerCase();
          if (!hay.includes(q)) return false;
        }
        const due = Number(r.balanceDue || 0);
        const st = String(r.status || 'active').toLowerCase();
        if (dueF === 'due' && due <= 0) return false;
        if (dueF === 'overdue' && !r.isOverdue) return false;
        if (dueF === 'cleared' && due > 0) return false;
        if (dueF === 'suspended' && st !== 'suspended') return false;
        return true;
      });

      const tb = $('resellersTableBody');
      if (!filtered.length) {
        tb.innerHTML = '<tr><td colspan="6" class="empty-state">' + (all.length ? 'No resellers match your search or filter.' : 'No resellers added yet.') + '</td></tr>';
        return;
      }
      tb.innerHTML = filtered.map((r) => {
        const wa = r.whatsapp ? '<span class="badge badge-emerald">' + esc(r.whatsapp) + '</span>' : '<span class="badge badge-muted">Not set</span>';
        const due = Number(r.balanceDue || 0);
        const paid = Number(r.totalPaid || 0);
        const charges = Number(r.totalCharges || 0);
        const paidDisp = formatPKRCard(paid);
        const uCount = Number(r.usersCreated || 0);
        const quota = Number(r.allowedUsers || 50);
        const st = String(r.status || 'active').toLowerCase();
        const dueColor = due > 0 ? (r.isOverdue ? '#f87171' : '#fbbf24') : '#34d399';
        const statusBadge = st === 'suspended'
          ? '<span class="badge badge-rose">Suspended</span>'
          : (r.isOverdue
            ? '<span class="badge badge-rose">Overdue (' + Number(r.overdueUsersCount || 0) + ')</span>'
            : (due > 0
              ? '<span class="badge badge-amber">Due</span>'
              : '<span class="badge badge-emerald">Cleared</span>'));
        const effProRate = Math.round(Number((r.rates && r.rates.pro) ?? r.proRate ?? 500));
        const effMaxRate = Math.round(Number((r.rates && r.rates.max) ?? r.maxRate ?? 800));
        const hasCustomRate = r.proRate != null || r.maxRate != null;
        return (
          '<tr>' +
            '<td><div class="user-cell-name">' + esc(r.name || 'Reseller') + '</div><div class="user-cell-email">' + esc(r.email) + '</div></td>' +
            '<td>' + wa + '</td>' +
            '<td>' +
              '<button type="button" class="btn btn-ghost btn-xs" data-r-act="users" data-id="' + r.id + '" title="View all users of this reseller">' +
                'Users (' + uCount + '/' + quota + ')' +
              '</button>' +
            '</td>' +
            '<td>' +
              '<div style="font-family:var(--font-mono);font-weight:700;color:' + dueColor + ';">Due: Rs. ' + Math.round(due).toLocaleString() + '</div>' +
              '<div style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);" title="Paid ' + esc(paidDisp.exact) + ' / Total Rs. ' + Math.round(charges).toLocaleString() + '">Paid ' + esc(paidDisp.text) + ' / Rs. ' + Math.round(charges).toLocaleString() + '</div>' +
              '<div style="font-size:10.5px;color:' + (hasCustomRate ? '#c084fc' : 'var(--text-muted)') + ';font-family:var(--font-mono);margin-top:2px;">Pro: Rs. ' + effProRate.toLocaleString() + ' · Max: Rs. ' + effMaxRate.toLocaleString() + (hasCustomRate ? ' (Custom)' : '') + '</div>' +
            '</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td><div class="actions-cell">' +
              '<button type="button" class="btn btn-ghost btn-xs" data-r-act="users" data-id="' + r.id + '">Users</button>' +
              '<button type="button" class="btn ' + (due > 0 ? 'btn-amber' : 'btn-ghost') + ' btn-xs" data-r-act="dues" data-id="' + r.id + '">' + (due > 0 ? 'Clear Dues' : 'Dues') + '</button>' +
              '<button type="button" class="btn btn-ghost btn-xs" data-r-act="edit" data-id="' + r.id + '">Edit</button>' +
              '<button type="button" class="btn btn-danger btn-xs" data-r-act="del" data-id="' + r.id + '">Delete</button>' +
            '</div></td>' +
          '</tr>'
        );
      }).join('');
    }

    $('resellerSearchInput').addEventListener('input', renderResellers);
    $('resellerDueFilter').addEventListener('change', renderResellers);

    function renderResellerAnalytics() {
      const data = state.resellerAnalytics;
      if (!data) return;
      const sum = data.summary || {};
      $('raTodayVal').textContent = Number(sum.totalUsersToday || 0).toLocaleString();
      $('raWeekVal').textContent = Number(sum.totalUsersThisWeek || 0).toLocaleString();
      $('raOverdueVal').textContent = Number(sum.totalOverdueResellers || 0).toLocaleString();
      $('raTotalUsersVal').textContent = Number(sum.totalResellerUsers || 0).toLocaleString();

      const q = ($('raSearchInput')?.value || '').trim().toLowerCase();
      const rows = (Array.isArray(data.resellers) ? data.resellers : []).filter((r) => {
        if (!q) return true;
        return ((r.name || '') + ' ' + (r.email || '') + ' ' + (r.whatsapp || '')).toLowerCase().includes(q);
      });
      const tb = $('raTableBody');
      if (!rows.length) {
        tb.innerHTML = '<tr><td colspan="7" class="empty-state">No reseller analytics match your filter.</td></tr>';
        return;
      }
      tb.innerHTML = rows.map((r) => {
        const due = Math.round(Number(r.balanceDue || 0));
        const stBadge = r.isOverdue
          ? '<span class="badge badge-rose">Overdue (' + Number(r.overdueUsersCount || 0) + ')</span>'
          : (due > 0 ? '<span class="badge badge-amber">Due</span>' : '<span class="badge badge-emerald">Cleared</span>');
        return (
          '<tr>' +
            '<td><div class="user-cell-name">' + esc(r.name) + '</div><div class="user-cell-email">' + esc(r.email) + '</div></td>' +
            '<td style="font-family:var(--font-mono);font-weight:700;color:#60a5fa;">' + Number(r.usersToday || 0) + '</td>' +
            '<td style="font-family:var(--font-mono);font-weight:600;color:#34d399;">' + Number(r.usersThisWeek || 0) + '</td>' +
            '<td style="font-family:var(--font-mono);">' + Number(r.usersCreated || 0) + ' / ' + Number(r.allowedUsers || 50) + '</td>' +
            '<td style="font-family:var(--font-mono);font-weight:700;color:' + (due > 0 ? '#fbbf24' : '#34d399') + ';">Rs. ' + due.toLocaleString() + '</td>' +
            '<td>' + stBadge + '</td>' +
            '<td><div class="actions-cell">' +
              '<button type="button" class="btn btn-ghost btn-xs" data-ra-act="users" data-id="' + r.id + '">Users</button>' +
              '<button type="button" class="btn ' + (due > 0 ? 'btn-amber' : 'btn-ghost') + ' btn-xs" data-ra-act="dues" data-id="' + r.id + '">' + (due > 0 ? 'Clear (0)' : 'Dues') + '</button>' +
            '</div></td>' +
          '</tr>'
        );
      }).join('');
    }

    $('raSearchInput').addEventListener('input', renderResellerAnalytics);
    $('refreshResellerAnalyticsBtn').addEventListener('click', loadResellerAnalytics);
    $('raTableBody').addEventListener('click', (e) => {
      const btn = e.target.closest('button[data-ra-act]');
      if (!btn) return;
      const id = Number(btn.dataset.id);
      if (btn.dataset.raAct === 'users') openResellerUsersModal(id);
      else if (btn.dataset.raAct === 'dues') {
        const r = state.resellers.find((x) => Number(x.id) === id) || (state.resellerAnalytics?.resellers || []).find((x) => Number(x.id) === id);
        if (r) openResellerDuesModal(r);
      }
    });

    function renderResellerConfig(cfg) {
      if (!cfg) return;
      const b = cfg.bankDetails || {};
      $('rbBankName').value = b.bankName || '';
      $('rbAccountTitle').value = b.accountTitle || '';
      $('rbAccountNumber').value = b.accountNumber || '';
      $('rbIban').value = b.iban || '';
      $('rbInstructions').value = b.instructions || '';

      const rates = cfg.rates || {};
      $('rrPro').value = rates.pro ?? 500;
      $('rrMax').value = rates.max ?? 800;

      const pc = cfg.planCredits || {};
      if ($('rcPro')) $('rcPro').value = pc.pro ?? 25000;
      if ($('rcMax')) $('rcMax').value = pc.max ?? 45000;

      const pd = cfg.planDurations || (state.settings && state.settings.planDurations) || {};
      if ($('rdPro')) $('rdPro').value = pd.pro ?? 20;
      if ($('rdMax')) $('rdMax').value = pd.max ?? 30;
      syncUserPlanOptionLabels();

      const bc = cfg.broadcast || {};
      $('rbcEnabled').checked = Boolean(bc.enabled);
      $('rbcTitle').value = bc.title || '';
      $('rbcMessage').value = bc.message || '';
      $('rbcPhone1').value = bc.phone1 || '';
      $('rbcPhone2').value = bc.phone2 || '';
    }

    $('saveResellerBankBtn').addEventListener('click', async () => {
      const btn = $('saveResellerBankBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/settings/reseller-bank', {
          method: 'PUT',
          body: JSON.stringify({
            bankName: $('rbBankName').value.trim(),
            accountTitle: $('rbAccountTitle').value.trim(),
            accountNumber: $('rbAccountNumber').value.trim(),
            iban: $('rbIban').value.trim(),
            instructions: $('rbInstructions').value.trim(),
          }),
        });
        showToast(res.message || 'Reseller bank details saved');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('saveResellerRatesBtn').addEventListener('click', async () => {
      const btn = $('saveResellerRatesBtn');
      btn.disabled = true;
      try {
        const proRateVal = parseFloat($('rrPro').value) || 0;
        const maxRateVal = parseFloat($('rrMax').value) || 0;
        const proDaysVal = Math.max(1, parseInt($('rdPro')?.value || '20', 10) || 20);
        const maxDaysVal = Math.max(1, parseInt($('rdMax')?.value || '30', 10) || 30);
        const res = await api('/api/admin/settings/reseller-rates', {
          method: 'PUT',
          body: JSON.stringify({
            pro: proRateVal,
            max: maxRateVal,
            plus: proRateVal,
            ultra: maxRateVal,
            basic: 0,
            proCredits: parseInt($('rcPro')?.value || '25000', 10) || 25000,
            maxCredits: parseInt($('rcMax')?.value || '45000', 10) || 45000,
            proDays: proDaysVal,
            maxDays: maxDaysVal,
          }),
        });
        if (state.resellerConfig && res.planCredits) state.resellerConfig.planCredits = res.planCredits;
        if (state.resellerConfig && res.planDurations) state.resellerConfig.planDurations = res.planDurations;
        if (state.settings && res.planDurations) state.settings.planDurations = res.planDurations;
        syncUserPlanOptionLabels();
        showToast(res.message || 'Reseller rates, credits & plan durations updated');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('saveResellerBroadcastBtn').addEventListener('click', async () => {
      const btn = $('saveResellerBroadcastBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/settings/reseller-broadcast', {
          method: 'PUT',
          body: JSON.stringify({
            enabled: $('rbcEnabled').checked,
            title: $('rbcTitle').value.trim(),
            message: $('rbcMessage').value.trim(),
            phone1: $('rbcPhone1').value.trim(),
            phone2: $('rbcPhone2').value.trim(),
          }),
        });
        showToast(res.message || 'Reseller notice board updated');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    function openResellerDuesModal(r) {
      if (!r) return;
      $('duesResellerId').value = r.id;
      $('duesModalTitle').textContent = 'Manage Dues · ' + (r.name || 'Reseller');
      $('duesModalSub').textContent = (r.email || '') + (r.whatsapp ? (' · ' + r.whatsapp) : '');
      const due = Math.round(Number(r.balanceDue || 0));
      const paid = Math.round(Number(r.totalPaid || 0));
      const charges = Math.round(Number(r.totalCharges || 0));
      const pFmt = formatPKRCard(paid);
      $('duesTotalCharges').textContent = 'Rs. ' + charges.toLocaleString();
      $('duesTotalPaid').textContent = pFmt.text;
      $('duesTotalPaid').title = pFmt.exact;
      $('duesBalanceVal').textContent = 'Rs. ' + due.toLocaleString();
      $('duesBalanceVal').style.color = due > 0 ? (r.isOverdue ? '#f87171' : '#fbbf24') : '#34d399';
      $('duesUsersSummary').innerHTML =
        '<span class="badge badge-blue">Total Users: ' + Number(r.usersCreated || 0) + '</span>' +
        '<span class="badge badge-emerald">Paid: ' + Number(r.paidUsersCount || 0) + '</span>' +
        '<span class="badge badge-amber">In Grace: ' + Number(r.graceUsersCount || 0) + '</span>' +
        '<span class="badge badge-rose">Overdue: ' + Number(r.overdueUsersCount || 0) + '</span>';
      $('duesClearAllBtn').textContent = due > 0
        ? ('Clear All Dues · Rs. ' + due.toLocaleString() + ' → Rs. 0')
        : 'Confirm All Dues Cleared (Rs. 0)';
      $('duesPartialAmount').value = due > 0 ? String(due) : '';
      $('resellerDuesModal').classList.remove('hidden');
    }

    function renderResellerUsersModalTable() {
      const rows = activeResellerUsersState.users || [];
      const q = ($('rUsersSearchInput')?.value || '').trim().toLowerCase();
      const pf = $('rUsersPayFilter')?.value || 'all';
      const filtered = rows.filter((u) => {
        if (q) {
          const hay = ((u.name || '') + ' ' + (u.email || '') + ' ' + (u.username || '')).toLowerCase();
          if (!hay.includes(q)) return false;
        }
        if (pf === 'paid' && !u.isPaid) return false;
        if (pf === 'unpaid' && u.isPaid) return false;
        if (pf === 'overdue' && !u.isUnpaidOverdue) return false;
        if (pf === 'expired' && Number(u.daysRemaining || 0) > 0) return false;
        return true;
      });
      $('rUsersFooterCount').textContent = 'Showing ' + filtered.length + ' of ' + rows.length + ' user(s)';
      const tb = $('rUsersTableBody');
      if (!filtered.length) {
        tb.innerHTML = '<tr><td colspan="6" class="empty-state">' + (rows.length ? 'No users match your filter.' : 'This reseller has not created any users yet.') + '</td></tr>';
        return;
      }
      tb.innerHTML = filtered.map((u) => {
        const plan = String(u.plan || 'pro').toUpperCase();
        const days = Number(u.daysRemaining || 0);
        const cred = Number(u.credits || 0);
        const payBadge = u.isPaid
          ? '<span class="badge badge-emerald">Paid</span>'
          : (u.isUnpaidOverdue
            ? '<span class="badge badge-rose">Overdue · Rs. ' + Math.round(Number(u.amountDue || 0)).toLocaleString() + '</span>'
            : '<span class="badge badge-amber">Due · Rs. ' + Math.round(Number(u.amountDue || 0)).toLocaleString() + '</span>');
        const stLower = String(u.status || 'active').toLowerCase();
        const stBadge = (stLower === 'suspended' || stLower === 'unpaid_suspended' || u.isLocked)
          ? '<span class="badge badge-rose">' + (stLower === 'unpaid_suspended' ? 'Unpaid Lock' : 'Suspended') + '</span>'
          : (days > 0 ? '<span class="badge badge-emerald">Active</span>' : '<span class="badge badge-amber">Expired</span>');
        return (
          '<tr>' +
            '<td><div class="user-cell-name">' + esc(u.name || u.username || 'User') + '</div><div class="user-cell-email">' + esc(u.email) + '</div></td>' +
            '<td><span class="badge badge-blue">' + esc(plan) + '</span><div style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);margin-top:3px;">' + cred.toLocaleString() + ' cr</div></td>' +
            '<td style="font-family:var(--font-mono);font-weight:600;">' + days + 'd</td>' +
            '<td>' + payBadge + '</td>' +
            '<td>' + stBadge + '</td>' +
            '<td><div class="actions-cell">' +
              '<button type="button" class="btn btn-ghost btn-xs" data-ru-act="edit" data-id="' + u.id + '">Edit</button>' +
              '<button type="button" class="btn btn-ghost btn-xs" data-ru-act="unlock" data-id="' + u.id + '">Unlock</button>' +
              '<button type="button" class="btn btn-danger btn-xs" data-ru-act="del" data-id="' + u.id + '">Delete</button>' +
            '</div></td>' +
          '</tr>'
        );
      }).join('');
    }

    async function openResellerUsersModal(resellerId) {
      activeResellerUsersState = { resellerId, reseller: null, billing: null, users: [] };
      $('rUsersModalTitle').textContent = 'Loading reseller users...';
      $('rUsersModalSub').textContent = '';
      $('rUsersStatsRow').innerHTML = '';
      $('rUsersSearchInput').value = '';
      $('rUsersPayFilter').value = 'all';
      $('rUsersTableBody').innerHTML = '<tr><td colspan="6" class="empty-state">Loading users...</td></tr>';
      $('resellerUsersModal').classList.remove('hidden');
      try {
        const data = await api('/api/admin/resellers/' + resellerId + '/users');
        activeResellerUsersState = {
          resellerId,
          reseller: data.reseller || null,
          billing: data.billing || null,
          users: Array.isArray(data.users) ? data.users : [],
        };
        const r = activeResellerUsersState.reseller || {};
        const b = activeResellerUsersState.billing || {};
        $('rUsersModalTitle').textContent = (r.name || 'Reseller') + ' · Users (' + activeResellerUsersState.users.length + ')';
        $('rUsersModalSub').textContent = (r.email || '') + (r.whatsapp ? (' · WhatsApp: ' + r.whatsapp) : '');
        const due = Math.round(Number(b.balanceDue || 0));
        $('rUsersStatsRow').innerHTML =
          '<span class="badge badge-blue">Quota: ' + activeResellerUsersState.users.length + ' / ' + Number(r.allowedUsers || 50) + '</span>' +
          '<span class="badge ' + (due > 0 ? 'badge-amber' : 'badge-emerald') + '">Balance Due: Rs. ' + due.toLocaleString() + '</span>' +
          '<span class="badge badge-emerald">Paid Users: ' + Number(b.paidUsersCount || 0) + '</span>' +
          '<span class="badge badge-amber">Grace: ' + Number(b.graceUsersCount || 0) + '</span>' +
          (Number(b.overdueUsersCount || 0) > 0 ? '<span class="badge badge-rose">Overdue: ' + Number(b.overdueUsersCount || 0) + '</span>' : '');
        renderResellerUsersModalTable();
      } catch (err) {
        $('rUsersTableBody').innerHTML = '<tr><td colspan="6" class="empty-state">' + esc(err.message) + '</td></tr>';
        showToast(err.message, true);
      }
    }

    $('rUsersSearchInput').addEventListener('input', renderResellerUsersModalTable);
    $('rUsersPayFilter').addEventListener('change', renderResellerUsersModalTable);
    $('rUsersOpenDuesBtn').addEventListener('click', () => {
      const r = state.resellers.find((x) => Number(x.id) === Number(activeResellerUsersState.resellerId));
      if (r) {
        $('resellerUsersModal').classList.add('hidden');
        openResellerDuesModal(r);
      }
    });
    $('rUsersTableBody').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-ru-act]');
      if (!btn) return;
      const uid = Number(btn.dataset.id);
      const act = btn.dataset.ruAct;
      const u = (activeResellerUsersState.users || []).find((x) => Number(x.id) === uid);
      if (!u) return;
      if (act === 'edit') {
        $('resellerUsersModal').classList.add('hidden');
        openUserModal(u);
      } else if (act === 'unlock') {
        btn.disabled = true;
        try {
          const res = await api('/api/admin/users/' + uid + '/reset-device-lock', { method: 'POST', body: '{}' });
          showToast(res.message || 'Device lock reset');
          await openResellerUsersModal(activeResellerUsersState.resellerId);
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      } else if (act === 'del') {
        if (!confirm('Delete user ' + u.email + '?')) return;
        btn.disabled = true;
        try {
          await api('/api/admin/users/' + uid, { method: 'DELETE' });
          showToast('User deleted');
          await loadAll();
          await openResellerUsersModal(activeResellerUsersState.resellerId);
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      }
    });

    $('duesClearAllBtn').addEventListener('click', async () => {
      const id = Number($('duesResellerId').value);
      if (!id) return;
      const btn = $('duesClearAllBtn');
      btn.disabled = true;
      btn.textContent = 'Clearing dues...';
      try {
        const res = await api('/api/admin/resellers/' + id + '/clear-dues', { method: 'POST', body: '{}' });
        showToast(res.message || 'Reseller dues cleared to Rs. 0');
        $('resellerDuesModal').classList.add('hidden');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('duesPartialForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const id = Number($('duesResellerId').value);
      const amount = parseFloat($('duesPartialAmount').value);
      if (!id || !amount || amount <= 0) return showToast('Enter a valid amount in Rs.', true);
      const btn = $('duesRecordPartialBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/resellers/' + id + '/record-payment', {
          method: 'POST',
          body: JSON.stringify({ amount }),
        });
        showToast(res.message || 'Payment recorded');
        $('resellerDuesModal').classList.add('hidden');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    function renderPayments() {
      const list = state.payments || [];
      const pendingCnt = list.filter((p) => String(p.status || 'pending').toLowerCase() === 'pending').length;
      const badgeEl = $('pendingPayBadge');
      if (badgeEl) {
        badgeEl.textContent = String(pendingCnt);
        badgeEl.style.display = pendingCnt > 0 ? 'inline-flex' : 'none';
      }
      const tb = $('paymentsTableBody');
      if (!list.length) {
        tb.innerHTML = '<tr><td colspan="6" class="empty-state">No reseller payment requests submitted yet.</td></tr>';
        return;
      }
      tb.innerHTML = list.slice(0, 120).map((p) => {
        const st = String(p.status || 'pending').toLowerCase();
        const badge = st === 'approved' ? '<span class="badge badge-emerald">Approved</span>'
          : (st === 'rejected' ? '<span class="badge badge-rose">Rejected</span>' : '<span class="badge badge-amber">Pending</span>');
        const proofCell = p.proof
          ? '<div style="display:flex;align-items:center;gap:6px;"><img src="' + esc(p.proof) + '" class="proof-thumb" data-p-proof="' + p.id + '" alt="Proof" /><button type="button" class="btn btn-ghost btn-xs" data-p-proof="' + p.id + '">View</button></div>'
          : '<span style="font-size:11.5px;color:var(--text-muted);">No screenshot</span>';
        const dt = p.createdAt ? new Date(p.createdAt).toLocaleString() : '';
        const actions = st === 'pending'
          ? '<button type="button" class="btn btn-emerald btn-xs" data-p-act="approve" data-id="' + p.id + '">Approve</button>' +
            '<button type="button" class="btn btn-danger btn-xs" data-p-act="reject" data-id="' + p.id + '">Reject</button>'
          : '<span style="font-size:11.5px;color:var(--text-muted);">Resolved</span>';
        return (
          '<tr>' +
            '<td><div class="user-cell-name">' + esc(p.resellerName || ('#' + p.resellerId)) + '</div><div class="user-cell-email">' + esc(p.resellerEmail || '') + '</div></td>' +
            '<td style="font-family:var(--font-mono);font-weight:700;">Rs. ' + Number(p.amount || 0).toLocaleString() + '</td>' +
            '<td>' + proofCell + '</td>' +
            '<td>' + esc(p.note || p.method || p.reference || '-') + '</td>' +
            '<td>' + badge + '<div style="font-size:10.75px;color:var(--text-muted);margin-top:3px;">' + esc(dt) + '</div></td>' +
            '<td><div class="actions-cell">' + actions + '</div></td>' +
          '</tr>'
        );
      }).join('');
    }

    $('paymentsTableBody').addEventListener('click', async (e) => {
      const proofEl = e.target.closest('[data-p-proof]');
      if (proofEl) {
        const pid = Number(proofEl.dataset.pProof);
        const p = (state.payments || []).find((x) => Number(x.id) === pid);
        if (p && p.proof) {
          $('proofModalTitle').textContent = 'Payment Proof · ' + (p.resellerName || p.resellerEmail || ('#' + p.resellerId));
          $('proofModalImg').src = p.proof;
          $('proofModalOpenRaw').href = p.proof;
          $('proofModalMeta').textContent = 'Amount: Rs. ' + Number(p.amount || 0).toLocaleString() + (p.note ? (' · Note: ' + p.note) : '');
          $('proofModal').classList.remove('hidden');
        }
        return;
      }
      const btn = e.target.closest('button[data-p-act]');
      if (!btn) return;
      const id = Number(btn.dataset.id);
      const act = btn.dataset.pAct;
      let body = '{}';
      if (act === 'reject') {
        const reason = prompt('Optional rejection reason:', 'Invalid or unclear proof');
        if (reason === null) return;
        body = JSON.stringify({ reason });
      }
      btn.disabled = true;
      try {
        await api('/api/admin/reseller-payments/' + id + '/' + act, { method: 'PUT', body });
        showToast('Payment ' + act + 'd');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('openAddResellerBtn').addEventListener('click', () => {
      $('resellerForm').reset();
      $('rEditId').value = '';
      $('resellerModalTitle').textContent = 'Add Reseller';
      $('rPassword').required = true;
      $('rAllowedUsers').value = 50;
      $('rStatus').value = 'active';
      $('rProRate').value = '';
      $('rMaxRate').value = '';
      if ($('rProCredits')) $('rProCredits').value = '';
      if ($('rMaxCredits')) $('rMaxCredits').value = '';
      $('rDiscount').value = 0;
      $('rDiscountPct').value = 0;
      if ($('rAddedBy')) {
        let lastAb = 'Admin';
        try { lastAb = localStorage.getItem('pakflow_last_added_by') || 'Admin'; } catch (_) {}
        $('rAddedBy').value = lastAb;
      }
      $('rTotalPaidWrap').classList.add('hidden');
      $('resellerModal').classList.remove('hidden');
    });

    $('resellersTableBody').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-r-act]');
      if (!btn) return;
      const id = Number(btn.dataset.id);
      const act = btn.dataset.rAct;
      const r = state.resellers.find((x) => Number(x.id) === id);
      if (!r) return;

      if (act === 'users') {
        openResellerUsersModal(id);
      } else if (act === 'dues') {
        openResellerDuesModal(r);
      } else if (act === 'edit') {
        $('resellerModalTitle').textContent = 'Edit Reseller';
        $('rEditId').value = r.id;
        $('rName').value = r.name || '';
        $('rEmail').value = r.email || '';
        $('rPassword').value = '';
        $('rPassword').required = false;
        $('rWhatsapp').value = r.whatsapp || '';
        $('rAllowedUsers').value = r.allowedUsers ?? 50;
        $('rStatus').value = String(r.rawStatus || r.status || 'active').toLowerCase() === 'suspended' ? 'suspended' : 'active';
        $('rProRate').value = r.proRate != null ? r.proRate : '';
        $('rMaxRate').value = r.maxRate != null ? r.maxRate : '';
        if ($('rProCredits')) $('rProCredits').value = r.proCredits != null ? r.proCredits : '';
        if ($('rMaxCredits')) $('rMaxCredits').value = r.maxCredits != null ? r.maxCredits : '';
        $('rDiscount').value = r.discount ?? 0;
        $('rDiscountPct').value = r.discountPercent ?? 0;
        if ($('rAddedBy')) $('rAddedBy').value = r.addedBy || 'Admin';
        $('rTotalPaid').value = Math.round(Number(r.totalPaid || 0));
        $('rTotalPaidWrap').classList.remove('hidden');
        $('resellerModal').classList.remove('hidden');
      } else if (act === 'del') {
        if (!confirm('Delete reseller ' + r.email + '?')) return;
        try {
          await api('/api/admin/resellers/' + id, { method: 'DELETE' });
          showToast('Reseller deleted');
          await loadAll();
        } catch (err) { showToast(err.message, true); }
      }
    });

    $('resellerForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const editId = $('rEditId').value.trim();
      const payload = {
        name: $('rName').value.trim(),
        email: $('rEmail').value.trim(),
        whatsapp: $('rWhatsapp').value.trim(),
        allowedUsers: parseInt($('rAllowedUsers').value, 10) || 50,
        status: $('rStatus').value,
        proRate: $('rProRate').value.trim() === '' ? null : parseFloat($('rProRate').value),
        maxRate: $('rMaxRate').value.trim() === '' ? null : parseFloat($('rMaxRate').value),
        proCredits: ($('rProCredits') && $('rProCredits').value.trim() !== '') ? parseInt($('rProCredits').value, 10) : null,
        maxCredits: ($('rMaxCredits') && $('rMaxCredits').value.trim() !== '') ? parseInt($('rMaxCredits').value, 10) : null,
        discount: Math.max(0, parseFloat($('rDiscount').value) || 0),
        discountPercent: Math.max(0, Math.min(100, parseFloat($('rDiscountPct').value) || 0)),
        addedBy: ($('rAddedBy') && $('rAddedBy').value.trim()) ? $('rAddedBy').value.trim() : 'Admin',
      };
      try { localStorage.setItem('pakflow_last_added_by', payload.addedBy); } catch (_) {}
      if (editId && $('rTotalPaid').value.trim() !== '') {
        payload.totalPaid = Math.max(0, parseFloat($('rTotalPaid').value) || 0);
      }
      if ($('rPassword').value.trim()) payload.password = $('rPassword').value.trim();
      const btn = $('resellerSubmitBtn');
      btn.disabled = true;
      try {
        if (editId) {
          await api('/api/admin/resellers/' + editId, { method: 'PUT', body: JSON.stringify(payload) });
          showToast('Reseller updated');
        } else {
          await api('/api/admin/resellers', { method: 'POST', body: JSON.stringify(payload) });
          showToast('Reseller created');
        }
        $('resellerModal').classList.add('hidden');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // ── Render: Analytics & Revenue ──
    function renderAnalytics() {
      const st = state.stats || {};
      const plansCfg = (state.settings && state.settings.plans) || {};
      const bd = st.planBreakdown || {};
      const total = Number(st.totalUsers || state.usersStats.total || 0);
      const active = Number(st.activeSubscribers || state.usersStats.active || 0);

      let estMrr = 0;
      const defaultPrices = { vibe: 1500, plus: 2500, pro: 3500, max: 5000, ultra: 8000, heavy: 5999 };
      Object.entries(bd).forEach(([k, count]) => {
        const key = k.toLowerCase();
        if (key === 'basic' || key === 'free' || key === 'starter') return;
        const price = Number((plansCfg[key] && plansCfg[key].price) ?? defaultPrices[key] ?? 0);
        estMrr += price * Number(count || 0);
      });
      // Scale MRR proportionally by active ratio
      const paidTotal = Object.entries(bd).reduce((acc, [k, c]) => (['basic', 'free', 'starter'].includes(k) ? acc : acc + Number(c || 0)), 0);
      const activeRatio = paidTotal > 0 ? Math.min(1, active / paidTotal) : 0;
      const mrrVal = Math.round(estMrr * activeRatio);
      const retention = total > 0 ? Math.round((active / total) * 100) : 0;

      setPkrKpi('anMrrVal', null, mrrVal, 'Based on active subscriptions');
      $('anRetentionVal').textContent = retention + '%';
      $('anExpiringVal').textContent = String((state.expiringUsers || []).length);
      $('anPaidActiveVal').textContent = active.toLocaleString();

      // Distribution Bars
      const distRows = ['pro', 'max', 'demo', 'basic', 'plus', 'ultra', 'vibe', 'free']
        .map((k) => ({ key: k, count: Number(bd[k] || 0) }))
        .filter((r) => r.count > 0 || ['pro', 'max', 'demo', 'basic'].includes(r.key));
      const maxC = Math.max(1, ...distRows.map((r) => r.count));
      $('planDistContainer').innerHTML = distRows.map((r) => {
        const pct = Math.round((r.count / Math.max(1, total)) * 100);
        const w = Math.round((r.count / maxC) * 100);
        return (
          '<div class="bar-row">' +
            '<div class="bar-meta"><span>' + esc(r.key.toUpperCase()) + '</span><span style="font-family:var(--font-mono);color:var(--text-secondary);">' + r.count.toLocaleString() + ' (' + pct + '%)</span></div>' +
            '<div class="bar-track"><div class="bar-fill" style="width:' + w + '%;"></div></div>' +
          '</div>'
        );
      }).join('');

      // Expiring Soon Table
      const exp = state.expiringUsers || [];
      const tb = $('expiringUsersBody');
      if (!exp.length) {
        tb.innerHTML = '<tr><td colspan="4" class="empty-state">No paid accounts expiring in the next 7 days.</td></tr>';
        return;
      }
      tb.innerHTML = exp.map((u) => {
        const days = Number(u.daysRemaining || 0);
        const msg = 'Hi ' + (u.name || u.email) + ', your PakFlow ' + String(u.plan || 'Pro').toUpperCase() + ' plan expires in ' + days + ' day(s). Reply here to renew smoothly!';
        return (
          '<tr>' +
            '<td><div class="user-cell-name">' + esc(u.name || 'User') + '</div><div class="user-cell-email">' + esc(u.email) + '</div></td>' +
            '<td><span class="badge badge-blue">' + esc(String(u.plan || 'pro').toUpperCase()) + '</span></td>' +
            '<td><span class="badge badge-amber">' + days + 'd left</span></td>' +
            '<td><div class="actions-cell">' +
              '<button type="button" class="btn btn-emerald btn-xs" data-exp-ext="' + u.id + '">+30d</button>' +
              '<a href="https://wa.me/?text=' + encodeURIComponent(msg) + '" target="_blank" rel="noopener noreferrer" class="btn btn-ghost btn-xs">Remind</a>' +
            '</div></td>' +
          '</tr>'
        );
      }).join('');
    }

    $('expiringUsersBody').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-exp-ext]');
      if (!btn) return;
      const uid = Number(btn.dataset.expExt);
      btn.disabled = true;
      try {
        await api('/api/admin/users/' + uid, { method: 'PUT', body: JSON.stringify({ addDays: 30 }) });
        showToast('Extended user by +30 days');
        await loadAnalyticsView();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('runExpiryEmailsBtn').addEventListener('click', async () => {
      const btn = $('runExpiryEmailsBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/run-expiry-emails', { method: 'POST', body: '{}' });
        showToast('Expiry reminder run complete');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // ── Render & Save: Subscription Plans & Pricing ──
    function syncDemoStatusBadge() {
      const enabled = Boolean($('demoEnabledToggle')?.checked);
      const badge = $('demoStatusBadge');
      const sub = $('demoToggleSub');
      if (badge) {
        badge.textContent = enabled ? 'ON · LIVE ON SITE' : 'OFF · HIDDEN';
        badge.className = enabled ? 'badge badge-emerald' : 'badge badge-muted';
      }
      if (sub) {
        sub.textContent = enabled
          ? 'Free Demo card is VISIBLE on Home & Pricing pages. Users can self-activate (1 per IP & Device).'
          : 'When OFF, Free Demo card is hidden from Home & Pricing pages and self-activation is blocked.';
      }
    }

    if ($('demoEnabledToggle')) {
      $('demoEnabledToggle').addEventListener('change', syncDemoStatusBadge);
    }

    if ($('saveDemoConfigBtn')) {
      $('saveDemoConfigBtn').addEventListener('click', async () => {
        const btn = $('saveDemoConfigBtn');
        const unit = ($('demoDurationUnit')?.value === 'days') ? 'days' : 'hours';
        const rawVal = parseFloat($('demoDurationVal')?.value || '2');
        const val = unit === 'days'
          ? Math.max(1, Math.min(365, Math.round(Number.isFinite(rawVal) ? rawVal : 1)))
          : Math.max(0.5, Math.min(720, Math.round((Number.isFinite(rawVal) ? rawVal : 2) * 10) / 10));
        const credits = Math.max(50, parseInt($('demoCreditsVal')?.value || '2000', 10) || 2000);
        const assignedSlot = Math.max(0, Math.min(10, parseInt($('demoAssignedSlot')?.value || '0', 10) || 0));
        const demoConfig = {
          enabled: Boolean($('demoEnabledToggle')?.checked),
          durationValue: val,
          durationUnit: unit,
          credits,
          assignedSlot,
          onePerIp: Boolean($('demoOnePerIp')?.checked),
          onePerDevice: Boolean($('demoOnePerDevice')?.checked),
        };
        btn.disabled = true;
        try {
          const res = await api('/api/admin/settings', { method: 'PUT', body: JSON.stringify({ demoConfig }) });
          if (state.settings) {
            state.settings.demoConfig = (res.settings && res.settings.demoConfig) || demoConfig;
            if (res.settings && res.settings.plans) state.settings.plans = res.settings.plans;
          }
          syncDemoStatusBadge();
          renderPlansEditor(state.settings || { demoConfig });
          renderUsers();
          showToast('Site Trial settings saved (' + (demoConfig.enabled ? 'ON' : 'OFF') + ' · ' + val + ' ' + unit + (assignedSlot ? ' · Slot ' + assignedSlot : '') + ')');
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      });
    }

    function renderPlansEditor(st) {
      const plans = (st && st.plans) || {};
      const pd = (st && st.planDurations) || {};
      const dc = (st && st.demoConfig) || {};
      if ($('demoEnabledToggle')) $('demoEnabledToggle').checked = Boolean(dc.enabled);
      if ($('demoDurationVal')) $('demoDurationVal').value = dc.durationValue ?? 2;
      if ($('demoDurationUnit')) $('demoDurationUnit').value = dc.durationUnit === 'days' ? 'days' : 'hours';
      if ($('demoCreditsVal')) $('demoCreditsVal').value = dc.credits ?? (plans.demo && plans.demo.credits) ?? 2000;
      if ($('demoAssignedSlot')) $('demoAssignedSlot').value = String(dc.assignedSlot ?? 0);
      if ($('demoOnePerIp')) $('demoOnePerIp').checked = dc.onePerIp !== false;
      if ($('demoOnePerDevice')) $('demoOnePerDevice').checked = dc.onePerDevice !== false;
      syncDemoStatusBadge();
      const defaultMeta = {
        basic: { name: 'Basic', price: 0, credits: 0, days: 0, description: 'Free starter account' },
        pro: { name: 'Pro', price: 3500, credits: 25000, days: Number(pd.pro || 20), description: 'High-speed Google Flow rendering & 25,000 credits' },
        max: { name: 'Max', price: 5000, credits: 45000, days: Number(pd.max || 30), description: 'Heavy creators: 45,000 credits & VIP priority queue' },
        demo: { name: 'Demo', price: 0, credits: 2000, days: 0, description: 'Timed 2-hour trial account (auto-locks on expiry)' },
      };
      const grid = $('plansEditorGrid');
      grid.innerHTML = PLAN_KEYS.map((key) => {
        const def = defaultMeta[key] || { name: key.toUpperCase(), price: 0, credits: 0, days: 0, description: '' };
        const p = Object.assign({}, def, plans[key] || {});
        const daysVal = key === 'pro' ? Number(pd.pro ?? p.days ?? 20) : (key === 'max' ? Number(pd.max ?? p.days ?? 30) : Number(p.days ?? 0));
        return (
          '<div class="plan-card" data-plan-key="' + key + '">' +
            '<div class="plan-card-head">' +
              '<span class="badge badge-blue">' + esc(key.toUpperCase()) + '</span>' +
              '<label style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--text-secondary);cursor:pointer;">' +
                '<span>Visible</span>' +
                '<input type="checkbox" class="switch" data-pf="visible" ' + (p.visible !== false ? 'checked' : '') + ' />' +
              '</label>' +
            '</div>' +
            '<div class="field"><label>Plan Display Name</label><input class="input" type="text" data-pf="name" value="' + esc(p.name || def.name) + '" /></div>' +
            '<div class="grid-3">' +
              '<div class="field"><label>Price (PKR)</label><input class="input" type="number" min="0" data-pf="price" value="' + Number(p.price || 0) + '" /></div>' +
              '<div class="field"><label>Credits</label><input class="input" type="number" min="0" data-pf="credits" value="' + Number(p.credits || 0) + '" /></div>' +
              '<div class="field"><label>Duration (Days)</label><input class="input" type="number" min="0" max="3650" data-pf="days" value="' + daysVal + '"' + (key === 'demo' ? ' title="Demo uses hours (2h default)"' : '') + ' /></div>' +
            '</div>' +
            '<div class="field"><label>Description / Perks</label><textarea class="textarea" style="min-height:68px;" data-pf="description">' + esc(p.description || '') + '</textarea></div>' +
          '</div>'
        );
      }).join('');
    }

    $('savePlansBtn').addEventListener('click', async () => {
      const btn = $('savePlansBtn');
      const existingPlans = (state.settings && state.settings.plans) || {};
      const updated = {};
      const nextDurations = { basic: 0, pro: 20, max: 30, demo: 0 };
      document.querySelectorAll('#plansEditorGrid .plan-card').forEach((card) => {
        const key = card.dataset.planKey;
        const daysNum = Math.max(0, Math.min(3650, parseInt(card.querySelector('[data-pf="days"]')?.value || '0', 10) || 0));
        if (key === 'pro') nextDurations.pro = Math.max(1, daysNum || 20);
        if (key === 'max') nextDurations.max = Math.max(1, daysNum || 30);
        if (key === 'basic') nextDurations.basic = daysNum;
        updated[key] = Object.assign({}, existingPlans[key] || {}, {
          visible: card.querySelector('[data-pf="visible"]').checked,
          name: card.querySelector('[data-pf="name"]').value.trim() || key.toUpperCase(),
          price: parseInt(card.querySelector('[data-pf="price"]').value, 10) || 0,
          credits: parseInt(card.querySelector('[data-pf="credits"]').value, 10) || 0,
          days: key === 'pro' ? nextDurations.pro : (key === 'max' ? nextDurations.max : daysNum),
          description: card.querySelector('[data-pf="description"]').value.trim(),
        });
      });
      btn.disabled = true;
      try {
        await api('/api/admin/settings', { method: 'PUT', body: JSON.stringify({ plans: updated, planDurations: nextDurations }) });
        if (state.settings) {
          state.settings.plans = updated;
          state.settings.planDurations = nextDurations;
        }
        if (state.resellerConfig) state.resellerConfig.planDurations = nextDurations;
        if ($('rdPro')) $('rdPro').value = nextDurations.pro;
        if ($('rdMax')) $('rdMax').value = nextDurations.max;
        syncUserPlanOptionLabels();
        showToast('All subscription plans & durations saved');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // ── Platform Settings & Broadcast ──
    function renderSettings(st) {
      $('setBrandName').value = st.brandName || 'Flow by Pak';
      $('setWhatsapp').value = st.whatsappSupport || st.whatsappNumber || st.whatsappPhone || '';
      $('setChannel').value = st.whatsappChannel || '';
      $('setSupportEmail').value = st.supportEmail || 'flowbypak.site@gmail.com';

      const m = st.maintenance || {};
      $('maintEnabled').checked = Boolean(m.enabled);
      $('maintTitle').value = m.title || 'Scheduled System Maintenance';
      $('maintMessage').value = m.message || '';
      $('maintCustomUrl').value = m.customUrl || '';
      $('maintCustomUrlTitle').value = m.customUrlTitle || 'Join WhatsApp Channel for Updates';
      $('maintRedirect').checked = Boolean(m.redirectToCustomUrl);
      $('topMaintPill').classList.toggle('hidden', !m.enabled);

      const pubReg = st.allowPublicRegistration !== false;
      $('pubRegToggle').checked = pubReg;
      $('pubRegStatusLabel').textContent = pubReg ? 'Public Registration is ENABLED (Open)' : 'Public Registration is DISABLED (Restricted)';

      $('trialExtAccessToggle').checked = Boolean(st.allowTrialExtensionAccess);
      const flowExt = (st.extensions && st.extensions.flow) || {};
      if ($('extWebStoreUrl')) $('extWebStoreUrl').value = flowExt.webStoreUrl || '';
      $('extDirectUrl').value = flowExt.downloadUrl || '';
      $('extInstructions').value = flowExt.instructions || '';

      const bc = st.broadcast || {};
      $('bcEnabled').checked = Boolean(bc.enabled);
      $('bcTitle').value = bc.title || '';
      $('bcType').value = bc.type || 'info';
      $('bcMessage').value = bc.message || '';
    }

    $('savePlatformSettingsBtn').addEventListener('click', async () => {
      const btn = $('savePlatformSettingsBtn');
      btn.disabled = true;
      try {
        const wa = $('setWhatsapp').value.trim();
        await api('/api/admin/settings', {
          method: 'PUT',
          body: JSON.stringify({
            brandName: $('setBrandName').value.trim() || 'Flow by Pak',
            whatsappSupport: wa,
            whatsappNumber: wa,
            whatsappPhone: wa,
            whatsappChannel: $('setChannel').value.trim(),
            supportEmail: $('setSupportEmail').value.trim(),
          }),
        });
        showToast('Platform settings saved');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    $('saveBroadcastBtn').addEventListener('click', async () => {
      const btn = $('saveBroadcastBtn');
      btn.disabled = true;
      try {
        await api('/api/admin/broadcast', {
          method: 'POST',
          body: JSON.stringify({
            enabled: $('bcEnabled').checked,
            title: $('bcTitle').value.trim(),
            type: $('bcType').value || 'info',
            message: $('bcMessage').value.trim(),
          }),
        });
        showToast('Broadcast announcement updated');
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // ── Auth Boot ──
    function showLogin() {
      $('appShell').classList.add('hidden');
      $('loginView').classList.remove('hidden');
    }
    function showConsole() {
      $('loginView').classList.add('hidden');
      $('appShell').classList.remove('hidden');
    }

    $('adminLoginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      $('loginError').classList.add('hidden');
      const btn = $('adminLoginBtn');
      btn.disabled = true;
      btn.textContent = 'Signing in...';
      try {
        const res = await api('/api/admin/login', {
          method: 'POST',
          body: JSON.stringify({
            email: $('adminEmail').value.trim(),
            password: $('adminPassword').value,
          }),
        });
        if (res.token) {
          localStorage.setItem(TOKEN_KEY, res.token);
          showConsole();
          await loadAll();
        }
      } catch (err) {
        $('loginError').textContent = err.message || 'Invalid credentials';
        $('loginError').classList.remove('hidden');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Sign In to Console';
      }
    });

    $('sidebarLogoutBtn').addEventListener('click', () => {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem('flowbypak_admin_user');
      showLogin();
    });

    $('topRefreshBtn').addEventListener('click', async () => {
      await loadAll();
      showToast('Data refreshed');
    });

    async function boot() {
      const tok = getToken();
      if (!tok) return showLogin();
      try {
        await api('/api/admin/verify');
        showConsole();
        await loadAll();
      } catch {
        showLogin();
      }
    }

    boot();
  })();
  </script>
</body>
</html>
