<?php
declare(strict_types=1);
$__pfUpdated = 'November 25, 2026';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="google-site-verification" content="wR4H4lO2yGQYZdAm1085Thn0qJfRDfga3RLeJjZ1zOc" />
  <title>Privacy Policy — PakFlow Studio &amp; Chrome Extension</title>
  <meta name="description" content="Official Privacy Policy for PakFlow Studio (flowbypak.com) and the PakFlow Chrome Extension." />
  <link rel="icon" type="image/png" href="/flowbypak-logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: #ffffff;
      color: #0f172a;
      line-height: 1.7;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }
    .nav-outer {
      position: sticky;
      top: 0;
      z-index: 50;
      background: rgba(8, 7, 14, 0.92);
      backdrop-filter: blur(18px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .nav-inner {
      max-width: 1200px;
      margin: 0 auto;
      padding: 13px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: #fff;
      font-weight: 800;
      font-size: 18px;
      letter-spacing: -0.02em;
    }
    .brand img { width: 32px; height: 32px; border-radius: 9px; border: 1px solid rgba(255,255,255,0.16); }
    .nav-pill {
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 5px 7px;
      border-radius: 999px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.10);
    }
    .nav-pill a {
      color: #d4d4d8;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      padding: 7px 16px;
      border-radius: 999px;
      transition: background 0.15s ease, color 0.15s ease;
    }
    .nav-pill a:hover { color: #fff; background: rgba(255,255,255,0.08); }
    .nav-pill a.active { background: #fff; color: #09090b; }
    .nav-actions { display: flex; align-items: center; gap: 10px; }
    .btn-ghost {
      padding: 8px 16px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.16);
      background: rgba(255,255,255,0.04);
      color: #e5e7eb;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
    }
    .btn-cta {
      padding: 9px 18px;
      border-radius: 999px;
      background: linear-gradient(135deg, #8b5cf6, #6d28d9);
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      text-decoration: none;
    }
    .mob-btn {
      display: none;
      width: 40px;
      height: 40px;
      border-radius: 10px;
      border: 1px solid rgba(255,255,255,0.16);
      background: rgba(255,255,255,0.06);
      color: #fff;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }
    @media (max-width: 860px) {
      .nav-pill, .nav-actions { display: none; }
      .mob-btn { display: inline-flex; }
    }
    .mob-backdrop {
      position: fixed; inset: 0; background: rgba(4,3,10,0.72); backdrop-filter: blur(6px);
      z-index: 98; opacity: 0; pointer-events: none; transition: opacity 0.2s ease;
    }
    .mob-backdrop.open { opacity: 1; pointer-events: auto; }
    .mob-drawer {
      position: fixed; top: 0; right: 0; bottom: 0; width: min(320px, 86vw);
      background: #0b0914; border-left: 1px solid rgba(139,92,246,0.28);
      z-index: 99; padding: 20px; display: flex; flex-direction: column; gap: 16px;
      transform: translateX(102%); transition: transform 0.24s ease;
    }
    .mob-drawer.open { transform: translateX(0); }
    .mob-drawer a.m-link {
      display: flex; justify-content: space-between; padding: 12px 14px; border-radius: 12px;
      color: #e4e4e7; text-decoration: none; font-size: 14.5px; font-weight: 600;
    }
    .mob-drawer a.m-link.active, .mob-drawer a.m-link:hover { background: rgba(139,92,246,0.18); color: #fff; }
    .hero-band {
      background:
        radial-gradient(ellipse 60% 80% at 50% 0%, rgba(168, 85, 247, 0.42), transparent 65%),
        linear-gradient(135deg, #1e0842 0%, #3b0f7a 55%, #5b21b6 100%);
      color: #ffffff;
      text-align: center;
      padding: clamp(40px, 5vw, 66px) 24px;
    }
    .badge {
      display: inline-block;
      padding: 6px 14px;
      border-radius: 999px;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.22);
      color: #f3e8ff;
      font-size: 11.5px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      margin-bottom: 14px;
    }
    .hero-band h1 { font-size: clamp(28px, 4.5vw, 44px); font-weight: 800; letter-spacing: -0.03em; margin-bottom: 10px; }
    .hero-band p { color: rgba(243,232,255,0.88); font-size: 14.5px; max-width: 640px; margin: 0 auto; }
    .container {
      max-width: 860px;
      margin: 40px auto 72px;
      padding: 0 24px;
    }
    .highlight {
      background: #ecfdf5;
      border: 1px solid #a7f3d0;
      border-radius: 14px;
      padding: 16px 18px;
      color: #065f46;
      font-size: 13.8px;
      margin-bottom: 24px;
    }
    .policy-card {
      background: #ffffff;
      border: 1px solid #e9e5ff;
      border-radius: 22px;
      padding: clamp(24px, 4vw, 38px);
      box-shadow: 0 14px 40px rgba(30, 8, 66, 0.06);
      display: flex;
      flex-direction: column;
      gap: 28px;
    }
    section h2 { font-size: 18px; font-weight: 800; color: #0f172a; margin-bottom: 10px; letter-spacing: -0.015em; }
    section p, section li { color: #475569; font-size: 14.5px; }
    section ul { padding-left: 20px; margin-top: 8px; display: flex; flex-direction: column; gap: 7px; }
    code { background: #f3e8ff; padding: 2px 6px; border-radius: 5px; font-size: 13px; color: #6d28d9; font-weight: 600; }
    a { color: #7c3aed; text-decoration: none; }
    a:hover { text-decoration: underline; }
    footer {
      background: #08070f;
      color: #94a3b8;
      text-align: center;
      font-size: 13px;
      padding: 28px 24px;
      border-top: 1px solid rgba(255,255,255,0.08);
    }
    footer a { color: #cbd5e1; margin: 0 10px; }
  </style>
</head>
<body>
  <header class="nav-outer">
    <div class="nav-inner">
      <a href="/" class="brand">
        <img src="/flowbypak-logo.png" alt="PakFlow" />
        <span>PakFlow</span>
      </a>
      <nav class="nav-pill">
        <a href="/">Home</a>
        <a href="/about">About</a>
        <a href="/pricing">Pricing</a>
        <a href="/privacy-policy" class="active">Privacy</a>
      </nav>
      <div class="nav-actions">
        <a href="/login" class="btn-ghost">Login</a>
        <a href="/signup" class="btn-cta">Get Started</a>
      </div>
      <button type="button" class="mob-btn" id="pMobToggle" aria-label="Open navigation menu" aria-expanded="false">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </header>

  <div id="pMobBackdrop" class="mob-backdrop"></div>
  <aside id="pMobDrawer" class="mob-drawer" aria-label="Mobile Navigation">
    <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,0.08);">
      <span class="brand"><img src="/flowbypak-logo.png" alt="PakFlow" /><span>PakFlow</span></span>
      <button type="button" id="pMobClose" style="width:36px;height:36px;border-radius:10px;border:1px solid rgba(255,255,255,0.14);background:rgba(255,255,255,0.06);color:#fff;font-size:20px;cursor:pointer;">&times;</button>
    </div>
    <a class="m-link" href="/"><span>Home</span><span>&rarr;</span></a>
    <a class="m-link" href="/about"><span>About</span><span>&rarr;</span></a>
    <a class="m-link" href="/pricing"><span>Pricing</span><span>&rarr;</span></a>
    <a class="m-link active" href="/privacy-policy"><span>Privacy Policy</span><span>&rarr;</span></a>
    <div style="margin-top:auto;display:flex;flex-direction:column;gap:10px;">
      <a href="/login" class="btn-ghost" style="text-align:center;padding:12px;">Login</a>
      <a href="/signup" class="btn-cta" style="text-align:center;padding:12px;">Get Started Free</a>
    </div>
  </aside>

  <div class="hero-band">
    <span class="badge">Chrome Web Store &amp; Web Platform</span>
    <h1>Privacy Policy</h1>
    <p>Last Updated: <?= htmlspecialchars($__pfUpdated, ENT_QUOTES, 'UTF-8') ?> &middot; Applies to <strong>flowbypak.com</strong> and the <strong>PakFlow Chrome Extension</strong></p>
  </div>

  <main class="container">
    <div class="highlight">
      <strong>Chrome Web Store Limited Use Disclosure:</strong> The use of information received from Google APIs and Chrome Extension APIs adheres strictly to the Chrome Web Store User Data Policy, including the Limited Use requirements. We never sell user data, never track general browsing history, and never use user data for advertising.
    </div>

    <div class="policy-card">
      <section>
        <h2>1. Overview &amp; Single Purpose</h2>
        <p>
          PakFlow ("we", "our", or "us") provides a subscription web portal (<code>flowbypak.com</code>) and companion Chrome Extension ("PakFlow") with a single purpose: synchronizing authorized studio workspace sessions, credit balances, and user-created video project links between the PakFlow Portal and Google Flow Studio (<code>flow.google.com</code> / <code>labs.google</code>).
        </p>
      </section>

      <section>
        <h2>2. Information We Collect &amp; Process</h2>
        <ul>
          <li><strong>Account &amp; Subscription Identifiers:</strong> When you sign in on the PakFlow web portal, we process your registered email address, account plan tier, credit balance, and a pseudonymous device session identifier solely to authenticate your active subscription and enforce plan device limits.</li>
          <li><strong>Studio Workspace Session State:</strong> The Chrome Extension synchronizes temporary, in-memory session rules required to access your assigned AI video studio workspace on supported studio domains.</li>
          <li><strong>Saved Video Project Metadata:</strong> When you generate a video inside the studio, PakFlow stores the project URL, title, and thumbnail reference in your personal Project Vault so you can reopen your projects across sessions.</li>
        </ul>
      </section>

      <section>
        <h2>3. How Chrome Extension Permissions Are Used</h2>
        <ul>
          <li><code>cookies</code> &amp; <code>declarativeNetRequestWithHostAccess</code>: Used strictly on PakFlow and Google Flow studio domains to attach authorized workspace session headers in memory without persisting raw credentials on disk.</li>
          <li><code>storage</code> &amp; <code>alarms</code>: Used locally in your browser to cache extension UI state, active plan status, project vault entries, and periodic session health checks.</li>
          <li><code>tabs</code>, <code>scripting</code>, &amp; <code>browsingData</code>: Used only on supported PakFlow and Google Flow studio tabs to launch the studio, render the workspace project drawer/credits badge, and clear stale studio cache when switching slots.</li>
          <li><code>management</code>: Used solely upon installation/startup to detect and disable duplicate legacy unpacked developer-mode copies of PakFlow so two copies do not conflict.</li>
        </ul>
      </section>

      <section>
        <h2>4. What We Never Collect or Share</h2>
        <ul>
          <li>We <strong>do not</strong> read, log, or transmit your browsing activity on any third-party websites outside PakFlow and supported Google Flow studio endpoints.</li>
          <li>We <strong>do not</strong> sell, rent, or trade personal information to third parties, data brokers, or advertisers.</li>
          <li>We <strong>do not</strong> use your data for personalized advertisements, creditworthiness checks, or unrelated analytics.</li>
        </ul>
      </section>

      <section>
        <h2>5. Data Security, Retention &amp; Deletion</h2>
        <p>
          All communication between the PakFlow Chrome Extension and our servers is encrypted over HTTPS (TLS). Account and Project Vault records are retained only while your account remains active. You may delete individual saved projects directly inside the extension UI at any time, or request complete account deletion by contacting support.
        </p>
      </section>

      <section>
        <h2>6. Contact Us</h2>
        <p>
          If you have any questions about this Privacy Policy, data access, or account deletion requests, please contact us at:
          <a href="mailto:support@flowbypak.com">support@flowbypak.com</a>.
        </p>
      </section>
    </div>
  </main>

  <footer>
    <div style="margin-bottom:10px;">
      <a href="/">Home</a>
      <a href="/about">About</a>
      <a href="/pricing">Pricing</a>
      <a href="/login">Sign In</a>
      <a href="/signup">Sign Up</a>
    </div>
    <div>&copy; <?= date('Y') ?> PakFlow Studio (<a href="https://wa.me/923430641457?text=I%20want%20a%20website%20for%20my%20business" target="_blank" rel="noopener noreferrer" style="margin:0;color:#c4b5fd;">Aivrasol Softwares</a>). All rights reserved.</div>
  </footer>

  <script>
    (function () {
      const btn = document.getElementById('pMobToggle');
      const closeBtn = document.getElementById('pMobClose');
      const drawer = document.getElementById('pMobDrawer');
      const bd = document.getElementById('pMobBackdrop');
      const toggle = (open) => {
        drawer.classList.toggle('open', open);
        bd.classList.toggle('open', open);
        btn.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
      };
      btn.addEventListener('click', () => toggle(!drawer.classList.contains('open')));
      closeBtn.addEventListener('click', () => toggle(false));
      bd.addEventListener('click', () => toggle(false));
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') toggle(false); });
    })();
  </script>
</body>
</html>
