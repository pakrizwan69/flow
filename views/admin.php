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
      --sidebar-w: 256px;
      --radius: 12px;
      --radius-sm: 8px;
      --font-sans: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
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
    .login-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 24px;
    }
    .login-brand img {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      border: 1px solid var(--border-strong);
      object-fit: cover;
    }
    .login-brand h1 { font-size: 18px; font-weight: 800; letter-spacing: -0.02em; }
    .login-brand p { font-size: 12px; color: var(--text-muted); }

    /* ── App Shell (Desktop Sidebar + Mobile Drawer) ── */
    .shell { display: flex; min-height: 100vh; width: 100%; }
    .sidebar-backdrop {
      position: fixed;
      inset: 0;
      z-index: 40;
      background: rgba(2, 6, 23, 0.72);
      backdrop-filter: blur(4px);
    }
    .sidebar {
      position: fixed;
      top: 0;
      left: 0;
      bottom: 0;
      width: var(--sidebar-w);
      z-index: 50;
      background: var(--bg-elevated);
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      transition: transform 0.2s ease;
    }
    @media (max-width: 1023px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.is-open { transform: translateX(0); }
    }
    .sidebar-head {
      height: 64px;
      padding: 0 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .brand-link {
      display: flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: var(--text);
    }
    .brand-link img {
      width: 30px;
      height: 30px;
      border-radius: 8px;
      border: 1px solid var(--border-strong);
      object-fit: cover;
    }
    .brand-title { font-size: 15px; font-weight: 800; letter-spacing: -0.02em; line-height: 1.1; }
    .brand-sub { font-size: 11px; color: var(--text-muted); font-weight: 500; }
    .sidebar-nav {
      flex: 1;
      overflow-y: auto;
      padding: 14px 12px;
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .nav-group-label {
      font-size: 10.5px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #64748b;
      padding: 12px 10px 6px;
    }
    .nav-group-label:first-child { padding-top: 2px; }
    .nav-btn {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 11px;
      border-radius: var(--radius-sm);
      border: 1px solid transparent;
      background: transparent;
      color: var(--text-muted);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-align: left;
      text-decoration: none;
      transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
    }
    .nav-btn svg { width: 17px; height: 17px; flex-shrink: 0; opacity: 0.85; }
    .nav-btn:hover { background: rgba(255, 255, 255, 0.04); color: var(--text); }
    .nav-btn.active {
      background: rgba(37, 99, 235, 0.14);
      border-color: rgba(59, 130, 246, 0.28);
      color: #ffffff;
    }
    .nav-btn.active svg { color: #60a5fa; opacity: 1; }
    .nav-badge {
      margin-left: auto;
      font-family: var(--font-mono);
      font-size: 11px;
      font-weight: 600;
      padding: 1px 7px;
      border-radius: 999px;
      background: var(--muted-bg);
      color: var(--text-secondary);
    }
    .sidebar-foot {
      padding: 12px;
      border-top: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    /* ── Main Column ── */
    .main-col {
      flex: 1;
      min-width: 0;
      display: flex;
      flex-direction: column;
    }
    @media (min-width: 1024px) {
      .main-col { padding-left: var(--sidebar-w); }
    }
    .topbar {
      position: sticky;
      top: 0;
      z-index: 30;
      min-height: 64px;
      padding: 10px 24px;
      background: rgba(11, 17, 32, 0.88);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
    }
    .topbar-left { display: flex; align-items: center; gap: 12px; min-width: 0; }
    .menu-toggle {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border);
      background: var(--card);
      color: var(--text);
      cursor: pointer;
    }
    @media (min-width: 1024px) { .menu-toggle { display: none; } }
    .page-title { font-size: 17px; font-weight: 800; letter-spacing: -0.02em; color: var(--text); }
    .page-sub { font-size: 12px; color: var(--text-muted); }
    .topbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    .countdown-pill {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 6px 12px;
      border-radius: var(--radius-sm);
      background: var(--card);
      border: 1px solid var(--border-strong);
      color: var(--text-secondary);
      font-family: var(--font-mono);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
      transition: border-color 0.15s ease, background 0.15s ease;
    }
    .countdown-pill:hover { border-color: #60a5fa; background: var(--card-hover); color: #fff; }
    .status-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: var(--emerald);
      flex-shrink: 0;
    }
    .status-dot.off { background: #64748b; }

    .workspace {
      width: 100%;
      max-width: 1440px;
      margin: 0 auto;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    @media (max-width: 640px) {
      .topbar { padding: 10px 14px; }
      .workspace { padding: 14px; gap: 14px; }
      .page-sub { display: none; }
    }

    /* ── Buttons ── */
    .btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
      padding: 8px 14px;
      border-radius: var(--radius-sm);
      border: 1px solid transparent;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      white-space: nowrap;
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
    .btn-sm { padding: 5px 10px; font-size: 12px; }
    .btn-icon { padding: 7px; }

    /* ── Cards & Grids ── */
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 20px;
      min-width: 0;
    }
    .card-head {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 16px;
    }
    .card-title { font-size: 15px; font-weight: 700; color: var(--text); letter-spacing: -0.01em; }
    .card-desc { font-size: 12.5px; color: var(--text-muted); margin-top: 2px; }

    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 14px;
    }
    @media (max-width: 1024px) { .kpi-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 480px) { .kpi-grid { grid-template-columns: 1fr; } }

    .kpi-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px 18px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .kpi-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      color: var(--text-muted);
      font-size: 12px;
      font-weight: 600;
    }
    .kpi-icon {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      background: var(--muted-bg);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #60a5fa;
    }
    .kpi-icon.emerald { color: #34d399; background: rgba(16, 185, 129, 0.12); }
    .kpi-icon.amber { color: #fbbf24; background: rgba(245, 158, 11, 0.12); }
    .kpi-icon.rose { color: #f87171; background: rgba(239, 68, 68, 0.12); }
    .kpi-val {
      font-family: var(--font-mono);
      font-size: 26px;
      font-weight: 700;
      letter-spacing: -0.02em;
      color: var(--text);
      line-height: 1.1;
    }
    .kpi-sub { font-size: 12px; color: var(--text-muted); }

    .grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
    @media (max-width: 1100px) { .grid-3 { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 768px) { .grid-2, .grid-3 { grid-template-columns: 1fr; } }

    /* ── Toolbar & Filters ── */
    .toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }
    .search-box {
      position: relative;
      flex: 1;
      min-width: 220px;
      max-width: 380px;
    }
    .search-box svg {
      position: absolute;
      left: 11px;
      top: 50%;
      transform: translateY(-50%);
      width: 15px;
      height: 15px;
      color: var(--text-muted);
      pointer-events: none;
    }
    .search-box input { padding-left: 34px; }
    .filter-group { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }

    /* ── Form Controls ── */
    .field { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
    .field:last-child { margin-bottom: 0; }
    .field label {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-secondary);
    }
    .input, .select, .textarea {
      width: 100%;
      padding: 9px 12px;
      border-radius: var(--radius-sm);
      border: 1px solid var(--border-strong);
      background: var(--bg);
      color: var(--text);
      font-size: 13.5px;
      transition: border-color 0.15s ease;
    }
    .textarea { min-height: 150px; resize: vertical; line-height: 1.55; font-family: var(--font-sans); }
    .textarea.mono { font-family: var(--font-mono); font-size: 12px; }
    .input:focus, .select:focus, .textarea:focus { border-color: var(--primary-hover); }

    /* ── Tables ── */
    .table-wrap {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
      border: 1px solid var(--border);
      border-radius: 10px;
      background: var(--bg-elevated);
    }
    table { width: 100%; border-collapse: collapse; text-align: left; }
    thead th {
      padding: 11px 14px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      background: rgba(15, 23, 42, 0.9);
      border-bottom: 1px solid var(--border);
      white-space: nowrap;
    }
    tbody td {
      padding: 12px 14px;
      border-bottom: 1px solid var(--border);
      font-size: 13px;
      color: var(--text-secondary);
      vertical-align: middle;
    }
    tbody tr:last-child td { border-bottom: 0; }
    tbody tr:hover { background: rgba(255, 255, 255, 0.025); }
    .user-cell-name { font-weight: 700; color: var(--text); font-size: 13.5px; }
    .user-cell-email { font-size: 12px; color: var(--text-muted); }
    .actions-cell { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; }

    /* ── Badges ── */
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 3px 9px;
      border-radius: 999px;
      font-size: 11.5px;
      font-weight: 700;
      white-space: nowrap;
      border: 1px solid transparent;
    }
    .badge-emerald { background: rgba(16, 185, 129, 0.14); border-color: rgba(16, 185, 129, 0.3); color: #34d399; }
    .badge-blue { background: rgba(37, 99, 235, 0.16); border-color: rgba(59, 130, 246, 0.32); color: #93c5fd; }
    .badge-amber { background: rgba(245, 158, 11, 0.14); border-color: rgba(245, 158, 11, 0.3); color: #fcd34d; }
    .badge-rose { background: rgba(239, 68, 68, 0.14); border-color: rgba(239, 68, 68, 0.3); color: #fca5a5; }
    .badge-muted { background: var(--muted-bg); border-color: var(--border); color: var(--text-muted); }

    /* ── Session Shuffle Hero & Slot Cards ── */
    .shuffle-hero {
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto;
      gap: 16px;
      align-items: center;
      padding: 18px 20px;
      border-radius: var(--radius);
      background: var(--bg-elevated);
      border: 1px solid var(--border-strong);
      margin-bottom: 16px;
    }
    @media (max-width: 700px) { .shuffle-hero { grid-template-columns: 1fr; } }
    .countdown-clock {
      font-family: var(--font-mono);
      font-size: 32px;
      font-weight: 700;
      color: var(--text);
      letter-spacing: -0.02em;
      line-height: 1.1;
      margin: 4px 0;
    }
    .countdown-clock.is-off { font-size: 18px; color: var(--text-muted); }

    .session-card {
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      transition: border-color 0.15s ease;
    }
    .session-card:hover { border-color: var(--border-strong); }
    .session-card.is-active { border-color: rgba(16, 185, 129, 0.32); }
    .session-card-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 8px;
    }
    .session-name { font-size: 14px; font-weight: 700; color: var(--text); }
    .session-meta {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12px;
      color: var(--text-muted);
      font-family: var(--font-mono);
    }
    .session-pool-check {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      font-size: 12px;
      font-weight: 600;
      color: var(--text-secondary);
      cursor: pointer;
    }
    .session-pool-check input { width: 15px; height: 15px; accent-color: var(--emerald); cursor: pointer; }
    .session-actions { display: flex; gap: 6px; flex-wrap: wrap; }

    /* ── Toggle Row & Chips ── */
    .toggle-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 13px 15px;
      border-radius: 10px;
      background: var(--bg-elevated);
      border: 1px solid var(--border);
      margin-bottom: 10px;
    }
    .toggle-row strong { display: block; font-size: 13.5px; color: var(--text); }
    .toggle-row span { display: block; font-size: 12px; color: var(--text-muted); margin-top: 2px; }
    .switch {
      width: 42px;
      height: 24px;
      appearance: none;
      background: var(--muted-bg);
      border: 1px solid var(--border-strong);
      border-radius: 999px;
      position: relative;
      cursor: pointer;
      flex-shrink: 0;
      transition: background 0.16s ease, border-color 0.16s ease;
    }
    .switch::after {
      content: "";
      position: absolute;
      top: 2px;
      left: 2px;
      width: 18px;
      height: 18px;
      border-radius: 50%;
      background: #cbd5e1;
      transition: transform 0.16s ease, background 0.16s ease;
    }
    .switch:checked { background: var(--emerald); border-color: var(--emerald); }
    .switch:checked::after { transform: translateX(18px); background: #ffffff; }

    .chips { display: flex; flex-wrap: wrap; gap: 6px; margin: 6px 0 12px; }
    .chip {
      padding: 5px 10px;
      border-radius: 6px;
      border: 1px solid var(--border);
      background: var(--bg-elevated);
      color: var(--text-secondary);
      font-size: 12px;
      font-weight: 600;
      cursor: pointer;
    }
    .chip:hover { border-color: var(--primary-hover); color: #fff; background: var(--muted-bg); }

    .preview-box {
      background: var(--bg);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 14px;
      font-size: 13px;
      line-height: 1.6;
      color: var(--text-secondary);
      white-space: pre-wrap;
      word-break: break-word;
      min-height: 180px;
    }

    /* ── Modals ── */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      z-index: 80;
      background: rgba(2, 6, 23, 0.78);
      backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 16px;
      overflow-y: auto;
    }
    .modal {
      width: 100%;
      max-width: 520px;
      max-height: calc(100dvh - 32px);
      background: var(--card);
      border: 1px solid var(--border-strong);
      border-radius: 14px;
      display: flex;
      flex-direction: column;
      overflow: hidden;
      margin: auto;
      box-shadow: 0 24px 64px rgba(0, 0, 0, 0.6);
    }
    .modal-head {
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      background: var(--bg-elevated);
    }
    .modal-head h3 { font-size: 15.5px; font-weight: 700; }
    .modal-body { padding: 18px 20px; overflow-y: auto; flex: 1; }
    .modal-foot {
      padding: 14px 20px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: flex-end;
      gap: 8px;
      flex-wrap: wrap;
      background: var(--bg-elevated);
    }

    /* ── Toast Notification ── */
    .toast {
      position: fixed;
      right: 20px;
      bottom: 20px;
      z-index: 120;
      max-width: 380px;
      padding: 12px 16px;
      border-radius: 10px;
      background: var(--card);
      border: 1px solid var(--border-strong);
      color: var(--text);
      font-size: 13px;
      font-weight: 600;
      box-shadow: 0 18px 40px rgba(0, 0, 0, 0.55);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .toast.ok { border-color: rgba(16, 185, 129, 0.45); }
    .toast.err { border-color: rgba(239, 68, 68, 0.45); color: #fecaca; }

    .alert-box {
      padding: 10px 13px;
      border-radius: var(--radius-sm);
      font-size: 13px;
      font-weight: 500;
      margin-bottom: 14px;
    }
    .alert-box.err { background: rgba(239, 68, 68, 0.14); border: 1px solid rgba(239, 68, 68, 0.3); color: #fca5a5; }
    .alert-box.ok { background: rgba(16, 185, 129, 0.14); border: 1px solid rgba(16, 185, 129, 0.3); color: #6ee7b7; }
    .empty-state {
      text-align: center;
      padding: 36px 16px;
      color: var(--text-muted);
      font-size: 13px;
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
          <p>Sign in to manage users, sessions &amp; releases</p>
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
        <div class="nav-group-label">Core</div>
        <button type="button" class="nav-btn active" data-view="users">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          <span>Users &amp; Overview</span>
          <span class="nav-badge" id="navUsersCount">0</span>
        </button>
        <button type="button" class="nav-btn" data-view="sessions">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="3" width="20" height="7" rx="2"/><rect x="2" y="14" width="20" height="7" rx="2"/><path d="M6 6.5h.01M6 17.5h.01"/></svg>
          <span>Flow Sessions (1–10)</span>
          <span class="nav-badge" id="navSessionsCount">0</span>
        </button>
        <button type="button" class="nav-btn" data-view="resellers">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18M5 21V7l8-4v18M19 21V11l-6-4"/><path d="M9 9h.01M9 12h.01M9 15h.01"/></svg>
          <span>Resellers &amp; Payouts</span>
          <span class="nav-badge" id="navResellersCount">0</span>
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
          <div>
            <h2 id="topbarTitle" class="page-title">Users &amp; Overview</h2>
            <p id="topbarSub" class="page-sub">Manage accounts, subscriptions and device access</p>
          </div>
        </div>
        <div class="topbar-right">
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
        <!-- VIEW 1: USERS & OVERVIEW -->
        <section id="view-users" class="view-pane">
          <div class="kpi-grid" style="margin-bottom:18px;">
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Total Users</span>
                <span class="kpi-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiTotal">0</div>
              <div class="kpi-sub">Registered accounts</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Active Plans</span>
                <span class="kpi-icon emerald"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiActive" style="color:#34d399;">0</div>
              <div class="kpi-sub">Paid Flow access</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Expired</span>
                <span class="kpi-icon amber"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiExpired" style="color:#fbbf24;">0</div>
              <div class="kpi-sub">Pending renewal</div>
            </div>
            <div class="kpi-card">
              <div class="kpi-top">
                <span>Suspended / Locked</span>
                <span class="kpi-icon rose"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg></span>
              </div>
              <div class="kpi-val" id="kpiSuspended" style="color:#f87171;">0</div>
              <div class="kpi-sub">Restricted accounts</div>
            </div>
          </div>

          <div class="card">
            <div class="toolbar">
              <div class="search-box">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="userSearchInput" class="input" type="search" placeholder="Search name, email or username..." />
              </div>
              <div class="filter-group">
                <select id="userPlanFilter" class="select" style="width:auto;min-width:130px;">
                  <option value="all">All Plans</option>
                  <option value="pro">Pro</option>
                  <option value="max">Max</option>
                  <option value="plus">Plus</option>
                  <option value="ultra">Ultra</option>
                  <option value="basic">Basic</option>
                </select>
                <select id="userStatusFilter" class="select" style="width:auto;min-width:130px;">
                  <option value="all">All Statuses</option>
                  <option value="active">Active</option>
                  <option value="expired">Expired</option>
                  <option value="suspended">Suspended</option>
                </select>
              </div>
            </div>

            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Plan</th>
                    <th>Days Left</th>
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
            <div id="usersPaginationBar" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px;padding-top:12px;border-top:1px solid var(--border);font-size:12.5px;color:var(--text-muted);">
              <span id="usersPageInfo">Showing 0 users</span>
              <div style="display:flex;align-items:center;gap:8px;">
                <button id="usersPrevBtn" type="button" class="btn btn-ghost btn-sm" disabled>Previous</button>
                <span id="usersPageIndicator" style="font-family:var(--font-mono);color:var(--text-secondary);font-weight:600;">Page 1 / 1</span>
                <button id="usersNextBtn" type="button" class="btn btn-ghost btn-sm" disabled>Next</button>
              </div>
            </div>
          </div>
        </section>

        <!-- VIEW 2: GOOGLE FLOW SESSIONS (1-10) & AUTO-SHUFFLE -->
        <section id="view-sessions" class="view-pane hidden">
          <div class="card" style="margin-bottom:16px;">
            <div class="shuffle-hero">
              <div>
                <div style="font-size:11.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-muted);">Next Automatic Session Shuffle</div>
                <div id="liveCountdownClock" class="countdown-clock is-off">Timer Off</div>
                <div id="liveCountdownMeta" style="font-size:12.5px;color:var(--text-muted);">Epoch #1 · Enable timer to automatically rotate users across active sessions</div>
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
                <span>Distributes users across selected ACTIVE sessions and rotates everyone when the countdown reaches 00:00:00. Users can still manually switch sessions in the extension.</span>
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
                <h3 class="card-title">Google Flow Cookie Sessions (Session 1 – 10)</h3>
                <p class="card-desc">Manage session cookies, toggle active status, and select which sessions participate in auto-shuffle.</p>
              </div>
            </div>
            <div id="sessionsGrid" class="grid-3">
              <div class="empty-state">Loading sessions...</div>
            </div>
          </div>
        </section>

        <!-- VIEW 3: RESELLERS & PAYOUTS -->
        <section id="view-resellers" class="view-pane hidden">
          <div class="card" style="margin-bottom:16px;">
            <div class="card-head">
              <div>
                <h3 class="card-title">Authorized Resellers</h3>
                <p class="card-desc">Users added by a reseller automatically get that reseller's WhatsApp button on their dashboard.</p>
              </div>
              <button id="openAddResellerBtn" type="button" class="btn btn-primary">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                <span>Add Reseller</span>
              </button>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Reseller</th>
                    <th>WhatsApp</th>
                    <th>Users</th>
                    <th>Balance Due</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="resellersTableBody">
                  <tr><td colspan="5" class="empty-state">Loading resellers...</td></tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="card">
            <div class="card-head">
              <div>
                <h3 class="card-title">Reseller Payment Submissions</h3>
                <p class="card-desc">Review and approve or reject reseller payment proofs.</p>
              </div>
            </div>
            <div class="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Reseller</th>
                    <th>Amount</th>
                    <th>Method / Ref</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="paymentsTableBody">
                  <tr><td colspan="5" class="empty-state">Loading payments...</td></tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- VIEW 4: EXTENSION BUILDER & RELEASES -->
        <section id="view-extension" class="view-pane hidden">
          <div class="card" style="margin-bottom:16px;">
            <div class="card-head" style="margin-bottom:0;">
              <div>
                <h3 class="card-title">Current Live Extension Release</h3>
                <p id="extLiveMeta" class="card-desc">Checking active release...</p>
              </div>
              <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span id="extLivePill" class="badge badge-emerald">v1.7</span>
                <a id="extLiveDownloadBtn" href="/downloads/latest" class="btn btn-ghost" download>Download Current ZIP</a>
              </div>
            </div>
          </div>

          <div class="grid-2">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Regenerate Extension ZIP (From Repo Source)</h3>
                  <p class="card-desc">Packages Chrome-ready .zip directly from <code>extension-src/</code>, updates version in manifest + popup + scripts, and updates the user dashboard top bar.</p>
                </div>
              </div>
              <div class="grid-2">
                <div class="field">
                  <label for="extNewVer">New Version (e.g. 1.8)</label>
                  <input id="extNewVer" class="input" type="text" placeholder="1.8" />
                </div>
                <div class="field">
                  <label for="extMinVer">Min Required Version</label>
                  <input id="extMinVer" class="input" type="text" placeholder="1.7" />
                </div>
              </div>
              <div class="field">
                <label for="extReleaseNote">Dashboard Release Banner Text (Optional)</label>
                <input id="extReleaseNote" class="input" type="text" placeholder="Faster session sync & fixes" />
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
                <label for="uploadZipVer">Version Label (e.g. 1.8)</label>
                <input id="uploadZipVer" class="input" type="text" placeholder="1.8" />
              </div>
              <div class="field">
                <label for="uploadZipNote">Release Note (Optional)</label>
                <input id="uploadZipNote" class="input" type="text" placeholder="Custom build release" />
              </div>
              <button id="uploadExtBtn" type="button" class="btn btn-emerald">Upload &amp; Publish ZIP</button>
            </div>
          </div>
        </section>

        <!-- VIEW 5: ACCESS LOCK & SECURITY -->
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
              <div class="field" style="max-width:240px;margin-top:12px;">
                <label for="polGraceSec">Portal Close Grace Period (Seconds)</label>
                <input id="polGraceSec" class="input" type="number" min="5" max="120" value="15" />
              </div>
              <button id="savePolicyBtn" type="button" class="btn btn-primary">Save Access Policy</button>
            </div>

            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Platform Maintenance Mode</h3>
                  <p class="card-desc">Temporarily pause user logins during scheduled updates.</p>
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
                <label for="maintTitle">Notice Title</label>
                <input id="maintTitle" class="input" type="text" placeholder="Scheduled System Maintenance" />
              </div>
              <div class="field">
                <label for="maintMessage">Notice Message</label>
                <textarea id="maintMessage" class="textarea" style="min-height:90px;"></textarea>
              </div>
              <button id="saveMaintBtn" type="button" class="btn btn-primary">Save Maintenance Settings</button>
            </div>
          </div>
        </section>

        <!-- VIEW 6: WELCOME MESSAGE TEMPLATE -->
        <section id="view-template" class="view-pane hidden">
          <div class="grid-2">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Welcome Message Template</h3>
                  <p class="card-desc">Auto-opens with 1-click Copy whenever you create a new user. Click any placeholder chip to insert it.</p>
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

        <!-- VIEW 7: SETTINGS & BROADCAST -->
        <section id="view-settings" class="view-pane hidden">
          <div class="grid-2">
            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Platform Support &amp; WhatsApp</h3>
                  <p class="card-desc">Fallback WhatsApp button shown to direct users who were not added by a reseller.</p>
                </div>
              </div>
              <div class="field">
                <label for="setWhatsapp">Support WhatsApp Number or URL</label>
                <input id="setWhatsapp" class="input" type="text" placeholder="+923001234567 or https://wa.me/..." />
              </div>
              <div class="field">
                <label for="setSupportEmail">Support Email</label>
                <input id="setSupportEmail" class="input" type="email" placeholder="support@flowbypak.com" />
              </div>
              <button id="savePlatformSettingsBtn" type="button" class="btn btn-primary">Save Platform Settings</button>
            </div>

            <div class="card">
              <div class="card-head">
                <div>
                  <h3 class="card-title">Broadcast Announcement</h3>
                  <p class="card-desc">Publish a notice across the platform.</p>
                </div>
              </div>
              <div class="toggle-row">
                <div>
                  <strong>Show Broadcast Banner</strong>
                  <span>Toggle announcement visibility</span>
                </div>
                <input id="bcEnabled" type="checkbox" class="switch" />
              </div>
              <div class="field">
                <label for="bcTitle">Headline</label>
                <input id="bcTitle" class="input" type="text" placeholder="Platform Update" />
              </div>
              <div class="field">
                <label for="bcMessage">Message</label>
                <textarea id="bcMessage" class="textarea" style="min-height:90px;"></textarea>
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
                <option value="pro">Pro (20 Days)</option>
                <option value="max">Max (30 Days)</option>
                <option value="plus">Plus</option>
                <option value="ultra">Ultra</option>
                <option value="basic">Basic (Free)</option>
              </select>
            </div>
            <div class="field">
              <label for="uDays">Validity (Days)</label>
              <input id="uDays" class="input" type="number" min="0" max="3650" value="30" />
            </div>
          </div>
          <div class="grid-2">
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
          </div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" data-close-modal="userModal">Cancel</button>
          <button id="userFormSubmitBtn" type="submit" class="btn btn-primary">Create User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- MODAL: COPY WELCOME MESSAGE AFTER USER CREATED -->
  <div id="welcomeCopyModal" class="modal-backdrop hidden">
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-head">
        <div>
          <h3>User Created — Copy Welcome Message</h3>
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

  <!-- MODAL: EDIT SESSION COOKIES -->
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
            <label for="slotCookiesInput">Cookies JSON Array</label>
            <textarea id="slotCookiesInput" class="textarea mono" placeholder='[{"name":"SID","value":"...","domain":".google.com"}]'></textarea>
          </div>
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" data-close-modal="slotModal">Cancel</button>
          <button id="slotSaveBtn" type="submit" class="btn btn-primary">Save Session Cookies</button>
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
        </div>
        <div class="modal-foot">
          <button type="button" class="btn btn-ghost" data-close-modal="resellerModal">Cancel</button>
          <button id="resellerSubmitBtn" type="submit" class="btn btn-primary">Save Reseller</button>
        </div>
      </form>
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
      users: ['Users & Overview', 'Manage accounts, subscriptions and device access'],
      sessions: ['Flow Sessions (1–10)', 'Manage cookie sessions, default session & live auto-shuffle timer'],
      resellers: ['Resellers & Payouts', 'Partner network, WhatsApp routing & payment approvals'],
      extension: ['Extension Builder', 'Regenerate Chrome-ready .zip from repo or upload custom releases'],
      security: ['Access Lock & Policy', 'Enforce portal-open, logout and subscription locks on Google Flow'],
      template: ['Welcome Template', 'Customize the message copied automatically when adding users'],
      settings: ['Settings & Broadcast', 'Platform support contact and live user announcements'],
    };

    let state = {
      users: [],
      usersPage: 1,
      usersLimit: 50,
      usersTotalFiltered: 0,
      usersTotalPages: 1,
      usersStats: { total: 0, active: 0, expired: 0, suspended: 0 },
      slots: [],
      resellers: [],
      payments: [],
      controls: null,
      settings: null,
      activeView: 'users',
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
        '{{password}}': vars.password || '',
        '{{name}}': vars.name || vars.email || 'User',
        '{{plan}}': String(vars.plan || 'Pro').toUpperCase(),
        '{{days}}': String(vars.days ?? 30),
        '{{whatsapp}}': formatWa(vars.whatsapp),
      };
      for (const [k, v] of Object.entries(map)) out = out.split(k).join(String(v));
      return out;
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
      if (q) params.set('q', q);
      if (planF && planF !== 'all') params.set('plan', planF);
      if (statusF && statusF !== 'all') params.set('status', statusF);
      return '/api/admin/users?' + params.toString();
    }

    function applyUsersResponse(data) {
      state.users = Array.isArray(data.users) ? data.users : [];
      state.usersPage = Math.max(1, Number(data.page || 1));
      state.usersLimit = Math.max(10, Number(data.limit || state.usersLimit || 50));
      state.usersTotalFiltered = Number(data.totalFiltered ?? data.total ?? state.users.length);
      state.usersTotalPages = Math.max(1, Number(data.totalPages || 1));
      if (data.stats && typeof data.stats === 'object') {
        state.usersStats = {
          total: Number(data.stats.total || 0),
          active: Number(data.stats.active || 0),
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

    async function loadAll() {
      const [usersRes, slotsRes, cfgRes, resellersRes, paymentsRes, settingsRes] = await Promise.allSettled([
        api(buildUsersUrl(state.usersPage)),
        api('/api/admin/slots'),
        api('/api/admin/controls/config'),
        api('/api/admin/resellers'),
        api('/api/admin/reseller-payments'),
        api('/api/admin/settings'),
      ]);

      if (usersRes.status === 'fulfilled') {
        applyUsersResponse(usersRes.value);
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
      }
    }

    // ── Render: Users ──
    function renderUsers() {
      const stStats = state.usersStats || { total: 0, active: 0, expired: 0, suspended: 0 };
      $('kpiTotal').textContent = stStats.total.toLocaleString();
      $('kpiActive').textContent = stStats.active.toLocaleString();
      $('kpiExpired').textContent = stStats.expired.toLocaleString();
      $('kpiSuspended').textContent = stStats.suspended.toLocaleString();
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
        const days = Number(u.daysRemaining || 0);
        const isSusp = String(u.status || '').toLowerCase() === 'suspended' || u.isLocked;
        const statusBadge = isSusp
          ? '<span class="badge badge-rose">Suspended</span>'
          : (days > 0 || plan === 'BASIC'
            ? '<span class="badge badge-emerald">Active</span>'
            : '<span class="badge badge-amber">Expired</span>');
        const planBadge = plan === 'BASIC' || plan === 'FREE'
          ? '<span class="badge badge-muted">' + esc(plan) + '</span>'
          : '<span class="badge badge-blue">' + esc(plan) + '</span>';
        const resellerTag = u.resellerName ? (' <span class="badge badge-muted" style="margin-left:4px;font-size:10px;">' + esc(u.resellerName) + '</span>') : '';
        const credNum = Number(u.credits ?? 0);
        return (
          '<tr>' +
            '<td><div class="user-cell-name">' + esc(u.name || u.username || 'User') + resellerTag + '</div><div class="user-cell-email">' + esc(u.email) + '</div></td>' +
            '<td>' + planBadge + '<div style="font-size:11px;color:var(--text-muted);font-family:var(--font-mono);margin-top:3px;">' + credNum.toLocaleString() + ' cr</div></td>' +
            '<td style="font-family:var(--font-mono);font-weight:600;">' + days + 'd</td>' +
            '<td>' + statusBadge + '</td>' +
            '<td style="font-family:var(--font-mono);">' + Number(u.deviceSwitchCount || 0) + '</td>' +
            '<td><div class="actions-cell">' +
              '<button type="button" class="btn btn-ghost btn-sm" data-act="edit-user" data-id="' + u.id + '">Edit</button>' +
              '<button type="button" class="btn btn-ghost btn-sm" data-act="reset-dev" data-id="' + u.id + '" title="Reset Device Lock">Unlock</button>' +
              '<button type="button" class="btn btn-danger btn-sm" data-act="del-user" data-id="' + u.id + '">Delete</button>' +
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
    $('usersPrevBtn').addEventListener('click', () => {
      if (state.usersPage > 1) loadUsersPage(state.usersPage - 1);
    });
    $('usersNextBtn').addEventListener('click', () => {
      if (state.usersPage < state.usersTotalPages) loadUsersPage(state.usersPage + 1);
    });

    $('usersTableBody').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-act]');
      if (!btn) return;
      const id = Number(btn.dataset.id);
      const act = btn.dataset.act;
      const user = state.users.find((u) => Number(u.id) === id);
      if (!user) return;

      if (act === 'edit-user') {
        openUserModal(user);
      } else if (act === 'reset-dev') {
        btn.disabled = true;
        try {
          const res = await api('/api/admin/users/' + id + '/reset-device-lock', { method: 'POST', body: '{}' });
          showToast(res.message || 'Device lock reset');
          await loadAll();
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

    // ── Add / Edit User Modal + Automatic Copy Welcome Modal ──
    function openUserModal(user) {
      $('userForm').reset();
      if (user) {
        $('userModalTitle').textContent = 'Edit User';
        $('uEditId').value = user.id;
        $('uName').value = user.name || '';
        $('uEmail').value = user.email || '';
        $('uPassword').value = '';
        $('uPassword').required = false;
        $('uPassHint').textContent = '(leave blank to keep current)';
        $('uPlan').value = String(user.plan || 'pro').toLowerCase();
        $('uDays').value = user.daysRemaining ?? 30;
        $('uStatus').value = String(user.status || 'active').toLowerCase() === 'suspended' ? 'suspended' : 'active';
        $('uCredits').value = user.credits ?? 45000;
        $('userFormSubmitBtn').textContent = 'Save Changes';
      } else {
        $('userModalTitle').textContent = 'Add New User';
        $('uEditId').value = '';
        $('uPassword').required = true;
        $('uPassHint').textContent = '';
        $('uPlan').value = 'pro';
        $('uDays').value = 30;
        $('uStatus').value = 'active';
        $('uCredits').value = 45000;
        $('userFormSubmitBtn').textContent = 'Create User';
      }
      $('userModal').classList.remove('hidden');
    }

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
      const rawCredits = parseInt($('uCredits').value, 10);
      const payload = {
        name: $('uName').value.trim(),
        email: $('uEmail').value.trim(),
        plan: $('uPlan').value,
        days: parseInt($('uDays').value, 10) || 0,
        status: $('uStatus').value,
        credits: Number.isFinite(rawCredits) && rawCredits >= 0 ? rawCredits : 45000,
      };
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
          const res = await api('/api/admin/users', { method: 'POST', body: JSON.stringify(payload) });
          $('userModal').classList.add('hidden');
          showToast('User created');
          const tpl = (state.controls && state.controls.messageTemplate) || DEFAULT_TEMPLATE;
          const wa = (state.settings && (state.settings.whatsappSupport || state.settings.whatsappNumber)) || '';
          const msg = fillTemplate(tpl, {
            site_link: window.location.origin,
            name: payload.name || payload.email.split('@')[0],
            email: payload.email,
            password: pass,
            plan: payload.plan,
            days: payload.days,
            whatsapp: wa,
          });
          $('welcomeCopySub').textContent = payload.email + ' · ' + payload.plan.toUpperCase() + ' (' + payload.days + ' days)';
          $('welcomeCopyText').value = msg;
          $('welcomeWaLink').href = 'https://wa.me/?text=' + encodeURIComponent(msg);
          $('welcomeCopyModal').classList.remove('hidden');
        }
        await loadAll();
      } catch (err) {
        showToast(err.message, true);
      } finally {
        btn.disabled = false;
      }
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

    // ── Render: Sessions (1-10) & Auto-Shuffle ──
    function renderSessions() {
      const grid = $('sessionsGrid');
      const flowSlots = [];
      const byNum = {};
      state.slots.forEach((s) => { byNum[Number(s.slotNumber)] = s; });
      let activeCount = 0;
      const pool = (state.controls && state.controls.serverShuffle && Array.isArray(state.controls.serverShuffle.poolSlots))
        ? state.controls.serverShuffle.poolSlots.map(Number)
        : [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];

      for (let n = 1; n <= 10; n++) {
        const s = byNum[n] || { slotNumber: n, name: 'Session ' + n, status: 'EMPTY', cookieCount: 0 };
        const isActive = String(s.status || '').toUpperCase() === 'ACTIVE' && Number(s.cookieCount || 0) > 0;
        if (isActive) activeCount++;
        flowSlots.push({ num: n, data: s, isActive, inPool: pool.includes(n) });
      }
      $('navSessionsCount').textContent = activeCount + '/10';

      grid.innerHTML = flowSlots.map(({ num, data, isActive, inPool }) => {
        const displayName = String(data.name || ('Session ' + num)).replace(/^Server\s*/i, 'Session ');
        const updated = data.updatedAt ? new Date(data.updatedAt).toLocaleString() : 'Not uploaded';
        return (
          '<div class="session-card ' + (isActive ? 'is-active' : '') + '">' +
            '<div class="session-card-head">' +
              '<div class="session-name">' + esc(displayName) + '</div>' +
              (isActive ? '<span class="badge badge-emerald">ACTIVE</span>' : '<span class="badge badge-muted">EMPTY</span>') +
            '</div>' +
            '<div class="session-meta">' +
              '<span>' + Number(data.cookieCount || 0) + ' cookies</span>' +
              '<span style="font-family:var(--font-sans);font-size:11.5px;">' + esc(updated) + '</span>' +
            '</div>' +
            '<label class="session-pool-check">' +
              '<input type="checkbox" name="sessionPoolCb" value="' + num + '" ' + (inPool ? 'checked' : '') + ' />' +
              '<span>Include in Auto-Shuffle Pool</span>' +
            '</label>' +
            '<div class="session-actions">' +
              '<button type="button" class="btn btn-ghost btn-sm" data-slot-act="edit" data-num="' + num + '">Cookies</button>' +
              '<button type="button" class="btn btn-ghost btn-sm" data-slot-act="toggle" data-num="' + num + '">' + (isActive ? 'Disable' : 'Activate') + '</button>' +
              '<button type="button" class="btn btn-danger btn-sm" data-slot-act="clear" data-num="' + num + '">Clear</button>' +
            '</div>' +
          '</div>'
        );
      }).join('');
    }

    $('sessionsGrid').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-slot-act]');
      if (!btn) return;
      const num = Number(btn.dataset.num);
      const act = btn.dataset.slotAct;
      const slot = state.slots.find((s) => Number(s.slotNumber) === num) || { slotNumber: num, name: 'Session ' + num, cookies: [] };

      if (act === 'edit') {
        $('slotModalTitle').textContent = 'Update Session ' + num + ' Cookies';
        $('slotNumInput').value = num;
        $('slotNameInput').value = String(slot.name || ('Session ' + num)).replace(/^Server\s*/i, 'Session ');
        $('slotCookiesInput').value = Array.isArray(slot.cookies) && slot.cookies.length ? JSON.stringify(slot.cookies, null, 2) : '';
        $('slotModal').classList.remove('hidden');
      } else if (act === 'toggle') {
        btn.disabled = true;
        try {
          const nextStatus = String(slot.status || '').toUpperCase() === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
          await api('/api/admin/slots/' + num + '/status', { method: 'POST', body: JSON.stringify({ status: nextStatus }) });
          showToast('Session ' + num + ' set to ' + nextStatus);
          await loadAll();
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      } else if (act === 'clear') {
        if (!confirm('Clear all cookies from Session ' + num + '?')) return;
        btn.disabled = true;
        try {
          await api('/api/admin/slots/' + num, { method: 'DELETE' });
          showToast('Session ' + num + ' cleared');
          await loadAll();
        } catch (err) { showToast(err.message, true); }
        finally { btn.disabled = false; }
      }
    });

    $('slotForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      const num = Number($('slotNumInput').value);
      const name = $('slotNameInput').value.trim() || ('Session ' + num);
      const raw = $('slotCookiesInput').value.trim();
      let cookies = [];
      if (raw) {
        try { cookies = JSON.parse(raw); }
        catch { return showToast('Invalid JSON format for cookies', true); }
      }
      const btn = $('slotSaveBtn');
      btn.disabled = true;
      try {
        await api('/api/admin/slots/' + num, { method: 'POST', body: JSON.stringify({ name, cookies }) });
        $('slotModal').classList.add('hidden');
        showToast('Session ' + num + ' saved');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    document.querySelectorAll('[data-mins]').forEach((chip) => {
      chip.addEventListener('click', () => { $('shuffleIntervalInput').value = chip.dataset.mins; });
    });

    $('saveShuffleBtn').addEventListener('click', async () => {
      const btn = $('saveShuffleBtn');
      const poolSlots = Array.from(document.querySelectorAll('input[name="sessionPoolCb"]:checked')).map((i) => Number(i.value));
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

    // ── Render: Controls (Extension + Policy + Template + Shuffle) ──
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

      const ap = cfg.accessPolicy || {};
      $('polPortalOpen').checked = Boolean(ap.requirePortalOpen);
      $('polLogout').checked = ap.blockOnLogout !== false;
      $('polExpired').checked = ap.blockOnExpired !== false;
      $('polGraceSec').value = ap.portalGraceSeconds || 15;

      const tpl = cfg.messageTemplate || DEFAULT_TEMPLATE;
      $('tplTextarea').value = tpl;
      updateTplPreview();
    }

    // Extension Regenerate & Upload
    $('regenExtBtn').addEventListener('click', async () => {
      const btn = $('regenExtBtn');
      const version = $('extNewVer').value.trim();
      if (!version) return showToast('Enter a version number (e.g. 1.8)', true);
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

    // Policy & Maintenance
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

    $('saveMaintBtn').addEventListener('click', async () => {
      const btn = $('saveMaintBtn');
      btn.disabled = true;
      try {
        const res = await api('/api/admin/maintenance', {
          method: 'POST',
          body: JSON.stringify({
            enabled: $('maintEnabled').checked,
            title: $('maintTitle').value.trim(),
            message: $('maintMessage').value.trim(),
          }),
        });
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

    // Resellers & Payments
    function renderResellers() {
      $('navResellersCount').textContent = state.resellers.length;
      const tb = $('resellersTableBody');
      if (!state.resellers.length) {
        tb.innerHTML = '<tr><td colspan="5" class="empty-state">No resellers added yet.</td></tr>';
        return;
      }
      tb.innerHTML = state.resellers.map((r) => {
        const wa = r.whatsapp ? '<span class="badge badge-emerald">' + esc(r.whatsapp) + '</span>' : '<span class="badge badge-muted">Not set</span>';
        return (
          '<tr>' +
            '<td><div class="user-cell-name">' + esc(r.name || 'Reseller') + '</div><div class="user-cell-email">' + esc(r.email) + '</div></td>' +
            '<td>' + wa + '</td>' +
            '<td style="font-family:var(--font-mono);">' + Number(r.usersCreated || 0) + '</td>' +
            '<td style="font-family:var(--font-mono);">' + Number(r.balanceDue || 0).toLocaleString() + '</td>' +
            '<td><div class="actions-cell">' +
              '<button type="button" class="btn btn-ghost btn-sm" data-r-act="edit" data-id="' + r.id + '">Edit</button>' +
              '<button type="button" class="btn btn-ghost btn-sm" data-r-act="clear" data-id="' + r.id + '">Clear Dues</button>' +
              '<button type="button" class="btn btn-danger btn-sm" data-r-act="del" data-id="' + r.id + '">Delete</button>' +
            '</div></td>' +
          '</tr>'
        );
      }).join('');
    }

    function renderPayments() {
      const tb = $('paymentsTableBody');
      if (!state.payments.length) {
        tb.innerHTML = '<tr><td colspan="5" class="empty-state">No payment submissions yet.</td></tr>';
        return;
      }
      tb.innerHTML = state.payments.slice(0, 100).map((p) => {
        const st = String(p.status || 'pending').toLowerCase();
        const badge = st === 'approved' ? '<span class="badge badge-emerald">Approved</span>'
          : (st === 'rejected' ? '<span class="badge badge-rose">Rejected</span>' : '<span class="badge badge-amber">Pending</span>');
        const actions = st === 'pending'
          ? '<button type="button" class="btn btn-emerald btn-sm" data-p-act="approve" data-id="' + p.id + '">Approve</button>' +
            '<button type="button" class="btn btn-danger btn-sm" data-p-act="reject" data-id="' + p.id + '">Reject</button>'
          : '<span style="font-size:12px;color:var(--text-muted);">Processed</span>';
        return (
          '<tr>' +
            '<td>' + esc(p.resellerName || p.resellerEmail || ('#' + p.resellerId)) + '</td>' +
            '<td style="font-family:var(--font-mono);font-weight:700;">' + Number(p.amount || 0).toLocaleString() + '</td>' +
            '<td>' + esc(p.method || p.reference || '-') + '</td>' +
            '<td>' + badge + '</td>' +
            '<td><div class="actions-cell">' + actions + '</div></td>' +
          '</tr>'
        );
      }).join('');
    }

    $('openAddResellerBtn').addEventListener('click', () => {
      $('resellerForm').reset();
      $('rEditId').value = '';
      $('resellerModalTitle').textContent = 'Add Reseller';
      $('rPassword').required = true;
      $('resellerModal').classList.remove('hidden');
    });

    $('resellersTableBody').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-r-act]');
      if (!btn) return;
      const id = Number(btn.dataset.id);
      const act = btn.dataset.rAct;
      const r = state.resellers.find((x) => Number(x.id) === id);
      if (!r) return;

      if (act === 'edit') {
        $('resellerModalTitle').textContent = 'Edit Reseller';
        $('rEditId').value = r.id;
        $('rName').value = r.name || '';
        $('rEmail').value = r.email || '';
        $('rPassword').value = '';
        $('rPassword').required = false;
        $('rWhatsapp').value = r.whatsapp || '';
        $('resellerModal').classList.remove('hidden');
      } else if (act === 'clear') {
        if (!confirm('Mark dues cleared for ' + r.name + '?')) return;
        try {
          await api('/api/admin/resellers/' + id + '/clear-dues', { method: 'POST', body: '{}' });
          showToast('Reseller dues cleared');
          await loadAll();
        } catch (err) { showToast(err.message, true); }
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
      };
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

    $('paymentsTableBody').addEventListener('click', async (e) => {
      const btn = e.target.closest('button[data-p-act]');
      if (!btn) return;
      const id = Number(btn.dataset.id);
      const act = btn.dataset.pAct;
      btn.disabled = true;
      try {
        await api('/api/admin/reseller-payments/' + id + '/' + act, { method: 'PUT', body: '{}' });
        showToast('Payment ' + act + 'd');
        await loadAll();
      } catch (err) { showToast(err.message, true); }
      finally { btn.disabled = false; }
    });

    // Platform Settings & Broadcast
    function renderSettings(st) {
      $('setWhatsapp').value = st.whatsappSupport || st.whatsappNumber || '';
      $('setSupportEmail').value = st.supportEmail || 'support@flowbypak.com';
      const m = st.maintenance || {};
      $('maintEnabled').checked = Boolean(m.enabled);
      $('maintTitle').value = m.title || 'Scheduled System Maintenance';
      $('maintMessage').value = m.message || '';
      const bc = st.broadcast || {};
      $('bcEnabled').checked = Boolean(bc.enabled);
      $('bcTitle').value = bc.title || '';
      $('bcMessage').value = bc.message || '';
    }

    $('savePlatformSettingsBtn').addEventListener('click', async () => {
      const btn = $('savePlatformSettingsBtn');
      btn.disabled = true;
      try {
        await api('/api/admin/settings', {
          method: 'PUT',
          body: JSON.stringify({
            whatsappSupport: $('setWhatsapp').value.trim(),
            whatsappNumber: $('setWhatsapp').value.trim(),
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
