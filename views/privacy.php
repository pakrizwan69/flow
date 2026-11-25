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
    :root {
      --bg: #07090f;
      --card: rgba(15, 19, 30, 0.86);
      --border: rgba(255, 255, 255, 0.08);
      --text: #f1f5f9;
      --muted: #94a3b8;
      --accent: #8b5cf6;
      --accent-soft: rgba(139, 92, 246, 0.14);
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: radial-gradient(circle at 50% 0%, rgba(124, 58, 237, 0.16), transparent 48%), var(--bg);
      color: var(--text);
      line-height: 1.7;
      min-height: 100vh;
      -webkit-font-smoothing: antialiased;
    }
    .topbar {
      max-width: 960px;
      margin: 0 auto;
      padding: 22px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--border);
    }
    .brand {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: #fff;
      font-weight: 800;
      font-size: 17px;
      letter-spacing: -0.02em;
    }
    .brand img { width: 30px; height: 30px; border-radius: 8px; }
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 15px;
      border-radius: 999px;
      border: 1px solid var(--border);
      background: rgba(255, 255, 255, 0.04);
      color: #e2e8f0;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      transition: background 0.18s ease, border-color 0.18s ease;
    }
    .back-btn:hover { background: rgba(255, 255, 255, 0.09); border-color: rgba(255, 255, 255, 0.18); }
    .container {
      max-width: 860px;
      margin: 40px auto 72px;
      padding: 0 24px;
    }
    .hero-box {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 34px 36px;
      margin-bottom: 24px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.45);
    }
    .badge {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 999px;
      background: var(--accent-soft);
      border: 1px solid rgba(139, 92, 246, 0.32);
      color: #c4b5fd;
      font-size: 11.5px;
      font-weight: 700;
      letter-spacing: 0.04em;
      text-transform: uppercase;
      margin-bottom: 14px;
    }
    h1 { font-size: clamp(26px, 4vw, 34px); font-weight: 800; letter-spacing: -0.025em; margin-bottom: 8px; }
    .updated { color: var(--muted); font-size: 13.5px; }
    .policy-card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 20px;
      padding: 34px 36px;
      display: flex;
      flex-direction: column;
      gap: 28px;
    }
    section h2 {
      font-size: 18px;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 10px;
      letter-spacing: -0.01em;
    }
    section p, section li {
      color: #cbd5e1;
      font-size: 14.5px;
    }
    section ul {
      padding-left: 20px;
      margin-top: 8px;
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    code {
      background: rgba(255, 255, 255, 0.07);
      padding: 2px 6px;
      border-radius: 5px;
      font-size: 13px;
      color: #ddd6fe;
    }
    .highlight {
      background: rgba(16, 185, 129, 0.08);
      border: 1px solid rgba(16, 185, 129, 0.25);
      border-radius: 12px;
      padding: 14px 16px;
      color: #d1fae5;
      font-size: 13.5px;
      margin-top: 10px;
    }
    a { color: #a78bfa; text-decoration: none; }
    a:hover { text-decoration: underline; }
    footer {
      text-align: center;
      color: var(--muted);
      font-size: 13px;
      padding: 24px;
      border-top: 1px solid var(--border);
    }
    @media (max-width: 640px) {
      .hero-box, .policy-card { padding: 24px 20px; }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <a href="/" class="brand">
      <img src="/flowbypak-logo.png" alt="PakFlow" />
      <span>PakFlow Studio</span>
    </a>
    <a href="/dashboard" class="back-btn">&larr; Back to Portal</a>
  </header>

  <main class="container">
    <div class="hero-box">
      <span class="badge">Chrome Web Store &amp; Web Platform</span>
      <h1>Privacy Policy</h1>
      <p class="updated">Last Updated: <?= htmlspecialchars($__pfUpdated, ENT_QUOTES, 'UTF-8') ?> &middot; Applies to <strong>flowbypak.com</strong> and the <strong>PakFlow Chrome Extension</strong></p>
      <div class="highlight">
        <strong>Chrome Web Store Limited Use Disclosure:</strong> The use of information received from Google APIs and Chrome Extension APIs adheres strictly to the Chrome Web Store User Data Policy, including the Limited Use requirements. We never sell user data, never track general browsing history, and never use user data for advertising.
      </div>
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
    &copy; <?= date('Y') ?> PakFlow Studio (Aivrasol Softwares). All rights reserved.
  </footer>
</body>
</html>
