<?php
$__pfExtVer = '1.7';
try {
    $__pfRoot = dirname(__DIR__);
    $__pfVerCache = $__pfRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'version_cache.json';
    $__pfResolved = '';
    if (is_file($__pfVerCache) && is_readable($__pfVerCache)) {
        $__pfVc = json_decode((string)@file_get_contents($__pfVerCache), true);
        if (is_array($__pfVc) && !empty($__pfVc['latestExtVersion'])) {
            $__pfResolved = trim((string)$__pfVc['latestExtVersion']);
        }
    }
    if ($__pfResolved === '') {
        require_once $__pfRoot . '/src/Env.php';
        require_once $__pfRoot . '/src/Database.php';
        require_once $__pfRoot . '/src/Db.php';
        \App\Env::load($__pfRoot);
        $__pfSettings = \App\Db::readSettings();
        $__pfResolved = trim((string)($__pfSettings['latestExtVersion'] ?? ($__pfSettings['extensions']['flow']['version'] ?? '1.7')));
    }
    $__pfClean = ltrim($__pfResolved, 'vV');
    if ($__pfClean !== '' && preg_match('/^\d+(?:\.\d+){0,3}$/', $__pfClean)) {
        $__pfExtVer = $__pfClean;
    }
} catch (\Throwable $_) {
    $__pfExtVer = '1.7';
}
$__pfExtSafeVer = htmlspecialchars($__pfExtVer, ENT_QUOTES, 'UTF-8');
$__pfExtZipName = 'PakFlow_v' . $__pfExtSafeVer . '.zip';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="google-site-verification" content="wR4H4lO2yGQYZdAm1085Thn0qJfRDfga3RLeJjZ1zOc" />
  <title>PakFlow — Google Flow AI</title>
  <link rel="icon" href="/flowbypak-logo.png" />
  <link rel="preload" as="image" href="/flowbypak-logo.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0b1120;
      --bg-elevated: #0f172a;
      --card: #111827;
      --card-border: rgba(148, 163, 184, 0.14);
      --border-strong: rgba(148, 163, 184, 0.26);
      --text: #f8fafc;
      --muted: #94a3b8;
      --accent: #2563eb;
      --accent-2: #3b82f6;
      --emerald: #10b981;
      --danger: #ef4444;
      --font-mono: "JetBrains Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
    }
    html, body {
      height: 100%;
      background: var(--bg);
      color: var(--text);
      font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      -webkit-font-smoothing: antialiased;
      scroll-behavior: smooth;
    }
    .hidden { display: none !important; }

    /* ── Cinematic Landing Page (flow.google.com style) ── */
    .landing-wrap {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      position: relative;
      overflow: hidden;
      background:
        radial-gradient(ellipse 80% 55% at 50% -10%, rgba(139,92,246,0.30), transparent 65%),
        radial-gradient(ellipse 55% 45% at 85% 85%, rgba(59,130,246,0.18), transparent 60%),
        radial-gradient(ellipse 45% 40% at 15% 75%, rgba(236,72,153,0.12), transparent 60%),
        #040406;
    }
    .landing-wrap::before {
      content: "";
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 56px 56px;
      mask-image: radial-gradient(ellipse 75% 65% at 50% 35%, #000 30%, transparent 80%);
      pointer-events: none;
    }
    .landing-nav-outer {
      position: sticky;
      top: 0;
      z-index: 40;
      background: rgba(4,4,6,0.72);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .landing-nav {
      position: relative;
      z-index: 5;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 32px;
      max-width: 1240px;
      width: 100%;
      margin: 0 auto;
    }
    .landing-links {
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 5px 7px;
      border-radius: 999px;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.10);
      backdrop-filter: blur(12px);
    }
    .landing-links a,
    .landing-links button {
      background: transparent;
      border: 0;
      color: #d4d4d8;
      text-decoration: none;
      font-size: 13px;
      font-weight: 600;
      padding: 7px 16px;
      border-radius: 999px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: background .15s ease, color .15s ease;
    }
    .landing-links a:hover,
    .landing-links button:hover {
      color: #ffffff;
      background: rgba(255,255,255,0.06);
    }
    .landing-links a.active,
    .landing-links button.active {
      background: #ffffff;
      color: #09090b;
    }
    .landing-links a.earn {
      color: #a855f7;
    }
    .landing-links a.earn:hover {
      color: #c084fc;
      background: rgba(168,85,247,0.12);
    }
    @media (max-width: 820px) {
      .landing-links { display: none; }
    }
    .landing-stats {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 12px;
      width: 100%;
      max-width: 980px;
      margin: 0 auto 44px;
      padding: 18px 24px;
      background: rgba(255,255,255,0.025);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 16px;
      backdrop-filter: blur(10px);
    }
    @media (max-width: 720px) {
      .landing-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
    }
    .l-stat { text-align: center; }
    .l-stat strong {
      display: block;
      font-size: 20px;
      font-weight: 800;
      color: #ffffff;
      letter-spacing: -0.02em;
    }
    .l-stat span {
      font-size: 12px;
      color: #71717a;
      font-weight: 500;
    }
    .landing-footer {
      position: relative;
      z-index: 5;
      border-top: 1px solid rgba(255,255,255,0.07);
      padding: 28px 32px;
      max-width: 1240px;
      width: 100%;
      margin: 0 auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 16px;
      font-size: 13px;
      color: #71717a;
    }
    .landing-footer a {
      color: #a1a1aa;
      text-decoration: none;
      margin-left: 18px;
    }
    .landing-footer a:hover { color: #fff; }
    .landing-logo {
      display: flex;
      align-items: center;
      gap: 11px;
      font-size: 18px;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #fff;
      text-decoration: none;
    }
    .landing-logo img {
      width: 34px;
      height: 34px;
      border-radius: 9px;
      object-fit: cover;
      border: 1px solid rgba(255,255,255,0.15);
    }
    .landing-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-ghost {
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.14);
      color: #e5e7eb;
      padding: 9px 18px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      backdrop-filter: blur(8px);
      transition: background .15s ease, border-color .15s ease;
    }
    .btn-ghost:hover {
      background: rgba(255,255,255,0.10);
      border-color: rgba(255,255,255,0.32);
    }
    .btn-pill-sm {
      background: #ffffff;
      color: #09090b;
      border: 0;
      padding: 9px 20px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: transform .12s ease, opacity .15s ease;
    }
    .btn-pill-sm:hover { opacity: 0.92; transform: translateY(-1px); }
    .landing-hero {
      position: relative;
      z-index: 5;
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 36px 24px 64px;
      max-width: 980px;
      margin: 0 auto;
      width: 100%;
    }
    .landing-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 7px 16px;
      border-radius: 999px;
      background: rgba(168,85,247,0.12);
      border: 1px solid rgba(168,85,247,0.32);
      color: #e9d5ff;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 24px;
      backdrop-filter: blur(8px);
    }
    .landing-hero h1 {
      font-size: clamp(44px, 7vw, 80px);
      font-weight: 800;
      letter-spacing: -0.035em;
      line-height: 1.04;
      min-height: 3.15em;
      margin-bottom: 20px;
      background: linear-gradient(180deg, #ffffff 15%, #c4b5fd 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .tw-cursor {
      display: inline-block;
      width: 4px;
      height: 0.85em;
      margin-left: 4px;
      vertical-align: -0.06em;
      background: #c4b5fd;
      -webkit-text-fill-color: initial;
      animation: twBlink .75s step-end infinite;
    }
    @keyframes twBlink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0; }
    }
    .landing-hero p {
      font-size: clamp(16px, 2vw, 20px);
      line-height: 1.6;
      color: #a1a1aa;
      max-width: 600px;
      margin-bottom: 34px;
    }
    .landing-cta-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 52px;
    }
    .btn-flow-cta {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 16px 36px;
      border-radius: 999px;
      border: 0;
      background: #ffffff;
      color: #09090b;
      font-size: 16px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 0 40px rgba(168,85,247,0.35), 0 12px 32px rgba(255,255,255,0.16);
      transition: transform .15s ease, box-shadow .15s ease;
    }
    .btn-flow-cta:hover {
      transform: translateY(-2px);
      box-shadow: 0 0 55px rgba(168,85,247,0.5), 0 16px 40px rgba(255,255,255,0.24);
    }
    .btn-flow-sec {
      padding: 15px 28px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.16);
      background: rgba(255,255,255,0.04);
      color: #f4f4f5;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s ease, border-color .15s ease;
    }
    .btn-flow-sec:hover {
      background: rgba(255,255,255,0.09);
      border-color: rgba(255,255,255,0.3);
    }
    .landing-cards {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 16px;
      width: 100%;
      text-align: left;
    }
    @media (max-width: 760px) {
      .landing-cards { grid-template-columns: 1fr; }
    }
    .l-card {
      background: rgba(17,17,22,0.72);
      border: 1px solid rgba(255,255,255,0.09);
      border-radius: 16px;
      padding: 20px 22px;
      backdrop-filter: blur(12px);
      transition: border-color .18s ease, transform .18s ease;
    }
    .l-card:hover {
      border-color: rgba(168,85,247,0.45);
      transform: translateY(-3px);
    }
    .l-card-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: rgba(168,85,247,0.16);
      border: 1px solid rgba(168,85,247,0.3);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      margin-bottom: 12px;
    }
    .l-card h3 {
      font-size: 15px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 6px;
    }
    .l-card p {
      font-size: 13px;
      line-height: 1.5;
      color: #9ca3af;
      margin: 0;
    }

    /* ── About PakFlow Section ── */
    .about-section {
      position: relative;
      z-index: 5;
      max-width: 820px;
      width: 100%;
      margin: 16px auto 64px;
      padding: 44px 28px;
      text-align: center;
      border-radius: 24px;
      background: rgba(255,255,255,0.02);
      border: 1px solid rgba(255,255,255,0.08);
      backdrop-filter: blur(12px);
    }
    .about-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 6px 14px;
      border-radius: 999px;
      background: rgba(139,92,246,0.14);
      border: 1px solid rgba(139,92,246,0.32);
      color: #c4b5fd;
      font-size: 11.5px;
      font-weight: 700;
      letter-spacing: 0.05em;
      text-transform: uppercase;
      margin-bottom: 18px;
    }
    .about-section h2 {
      font-size: clamp(32px, 5vw, 48px);
      font-weight: 800;
      letter-spacing: -0.03em;
      color: #ffffff;
      margin-bottom: 16px;
    }
    .about-section h2 span { color: #8b5cf6; }
    .about-section p {
      font-size: 15.5px;
      line-height: 1.65;
      color: #a1a1aa;
      max-width: 660px;
      margin: 0 auto 28px;
    }
    .about-section p strong { color: #f4f4f5; }
    .about-btns {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    .about-dev-box {
      margin: 26px auto 0;
      padding: 14px 20px;
      max-width: 520px;
      border-radius: 16px;
      background: rgba(139, 92, 246, 0.08);
      border: 1px solid rgba(139, 92, 246, 0.24);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
      text-align: left;
    }
    .about-dev-info {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }
    .about-dev-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: #a78bfa;
    }
    .about-dev-name {
      font-size: 15px;
      font-weight: 800;
      color: #ffffff;
      letter-spacing: -0.01em;
    }
    .about-dev-wa {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 8px 14px;
      border-radius: 999px;
      background: rgba(34, 197, 94, 0.14);
      border: 1px solid rgba(34, 197, 94, 0.35);
      color: #86efac;
      font-size: 12.5px;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.2s ease;
      white-space: nowrap;
    }
    .about-dev-wa:hover {
      background: rgba(34, 197, 94, 0.24);
      border-color: rgba(74, 222, 128, 0.65);
      color: #ffffff;
      transform: translateY(-1px);
    }
    @media (max-width: 560px) {
      .about-dev-box { justify-content: center; text-align: center; }
    }

    /* ── Home Sections: Ready in 4 steps, Clients, Questions Answered ── */
    .home-sec {
      position: relative;
      z-index: 5;
      max-width: 1120px;
      width: 100%;
      margin: 0 auto 88px;
      padding: 0 24px;
    }
    .steps4-head {
      margin-bottom: 30px;
    }
    .steps4-head h2 {
      font-size: clamp(32px, 4.5vw, 48px);
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.08;
      color: #ffffff;
      margin-bottom: 12px;
    }
    .steps4-head h2 span { color: #9333ea; display: block; }
    .steps4-head p {
      font-size: 15px;
      color: #a1a1aa;
      max-width: 470px;
      line-height: 1.55;
    }
    .steps4-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
    }
    .step4-card {
      display: flex;
      align-items: flex-start;
      gap: 18px;
      background: rgba(18, 12, 34, 0.72);
      border: 1px solid rgba(147, 51, 234, 0.22);
      border-radius: 14px;
      padding: 22px 24px;
      transition: border-color .18s ease, transform .18s ease;
    }
    .step4-card:hover {
      border-color: rgba(168, 85, 247, 0.5);
      transform: translateY(-2px);
    }
    .step4-num {
      font-size: 28px;
      font-weight: 800;
      color: rgba(147, 51, 234, 0.38);
      line-height: 1;
      flex-shrink: 0;
      min-width: 38px;
    }
    .step4-body h3 {
      font-size: 15.5px;
      font-weight: 700;
      color: #ffffff;
      margin-bottom: 6px;
    }
    .step4-body p {
      font-size: 13px;
      line-height: 1.55;
      color: #9ca3af;
    }

    /* Clients Testimonials */
    .clients-head {
      text-align: center;
      margin-bottom: 34px;
    }
    .clients-head h2 {
      font-size: clamp(28px, 4vw, 40px);
      font-weight: 800;
      letter-spacing: -0.025em;
      color: #ffffff;
      margin-bottom: 8px;
    }
    .clients-head h2 span { color: #a855f7; }
    .clients-head p {
      font-size: 14px;
      color: #9ca3af;
    }
    .clients-head p span { color: #a855f7; }
    .clients-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 14px;
    }
    .client-card {
      background: #0a0a0f;
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 12px;
      padding: 18px 20px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 14px;
    }
    .client-card h4 {
      font-size: 13px;
      font-weight: 700;
      color: #f4f4f5;
      margin-bottom: 5px;
    }
    .client-card p {
      font-size: 12.5px;
      line-height: 1.55;
      color: #9ca3af;
    }
    .client-foot {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .client-user {
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .client-avatar {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: #7c3aed;
      color: #ffffff;
      font-size: 11px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .client-name {
      font-size: 12.5px;
      font-weight: 600;
      color: #e4e4e7;
    }
    .client-stars {
      color: #9333ea;
      font-size: 12px;
      letter-spacing: 1px;
    }
    .clients-marquee-wrap {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 16px;
      height: 410px;
      overflow: hidden;
      mask-image: linear-gradient(to bottom, transparent 0%, #000 14%, #000 86%, transparent 100%);
      -webkit-mask-image: linear-gradient(to bottom, transparent 0%, #000 14%, #000 86%, transparent 100%);
    }
    .clients-col {
      overflow: hidden;
      position: relative;
    }
    .clients-track {
      display: flex;
      flex-direction: column;
      gap: 14px;
      will-change: transform;
      animation: clientsScrollUp 28s linear infinite;
    }
    .clients-col.col-slow .clients-track {
      animation-duration: 34s;
    }
    .clients-marquee-wrap:hover .clients-track {
      animation-play-state: paused;
    }
    @keyframes clientsScrollUp {
      0% { transform: translateY(0); }
      100% { transform: translateY(-50%); }
    }
    @media (max-width: 780px) {
      .clients-marquee-wrap { grid-template-columns: 1fr; height: 360px; }
      .clients-col.col-slow { display: none; }
    }

    /* White Questions? Answered. Accordion Section */
    .qa-band {
      position: relative;
      z-index: 5;
      width: 100%;
      background: #ffffff;
      color: #09090b;
      padding: 76px 24px 88px;
    }
    .qa-inner {
      max-width: 720px;
      margin: 0 auto;
    }
    .qa-head {
      text-align: center;
      margin-bottom: 34px;
    }
    .qa-head h2 {
      font-size: clamp(32px, 4.5vw, 46px);
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.08;
      color: #09090b;
    }
    .qa-head h2 span {
      display: block;
      color: #7c3aed;
    }
    .qa-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .qa-item {
      background: #ffffff;
      border: 1px solid #e4e4e7;
      border-radius: 12px;
      overflow: hidden;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    .qa-item[open] {
      border-color: #c4b5fd;
      box-shadow: 0 6px 20px rgba(124, 58, 237, 0.08);
    }
    .qa-item summary {
      list-style: none;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 18px 22px;
      font-size: 14.5px;
      font-weight: 700;
      color: #09090b;
      cursor: pointer;
    }
    .qa-item summary::-webkit-details-marker { display: none; }
    .qa-chevron {
      color: #7c3aed;
      transition: transform .2s ease;
      flex-shrink: 0;
    }
    .qa-item[open] .qa-chevron { transform: rotate(180deg); }
    .qa-ans {
      padding: 0 22px 18px;
      font-size: 13.5px;
      line-height: 1.6;
      color: #52525b;
    }
    .purple-cta-band {
      position: relative;
      z-index: 5;
      width: 100%;
      background: linear-gradient(135deg, #6d28d9 0%, #7c3aed 55%, #5b21b6 100%);
      padding: 64px 24px;
    }
    .purple-cta-inner {
      max-width: 960px;
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 32px;
      flex-wrap: wrap;
    }
    .purple-cta-text h2 {
      font-size: clamp(30px, 4.2vw, 44px);
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.08;
      color: #ffffff;
      margin-bottom: 12px;
    }
    .purple-cta-text p {
      font-size: 14.5px;
      color: rgba(255,255,255,0.86);
      max-width: 390px;
      line-height: 1.55;
    }
    .purple-cta-action {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 16px;
    }
    .purple-cta-action img {
      width: 68px;
      height: 68px;
      border-radius: 50%;
      object-fit: cover;
      background: #050507;
      border: 2px solid rgba(255,255,255,0.22);
      box-shadow: 0 12px 28px rgba(0,0,0,0.35);
    }
    .btn-purple-white {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #ffffff;
      color: #6d28d9;
      font-size: 14px;
      font-weight: 800;
      padding: 13px 28px;
      border-radius: 10px;
      text-decoration: none;
      box-shadow: 0 8px 24px rgba(0,0,0,0.22);
      transition: transform .15s ease, box-shadow .15s ease;
    }
    .btn-purple-white:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 30px rgba(0,0,0,0.3);
    }
    .landing-footer.centered-footer {
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 10px;
      background: #000000;
      padding: 28px 20px 32px;
      text-align: center;
      border-top: 1px solid rgba(255,255,255,0.06);
    }
    .landing-footer.centered-footer div {
      display: flex;
      gap: 20px;
      flex-wrap: wrap;
      justify-content: center;
    }
    .landing-footer.centered-footer a {
      color: #a1a1aa;
      font-size: 12.5px;
      text-decoration: none;
    }
    .landing-footer.centered-footer a:hover { color: #ffffff; }
    .landing-footer.centered-footer span {
      font-size: 11.5px;
      color: #52525b;
    }
    .landing-footer.centered-footer a.footer-dev-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #c4b5fd;
      font-size: 12px;
      font-weight: 600;
      padding: 4px 12px;
      border-radius: 999px;
      background: rgba(139, 92, 246, 0.10);
      border: 1px solid rgba(139, 92, 246, 0.24);
      transition: all 0.2s ease;
      margin-top: 2px;
    }
    .landing-footer.centered-footer a.footer-dev-link:hover {
      color: #ffffff;
      background: rgba(34, 197, 94, 0.16);
      border-color: rgba(74, 222, 128, 0.48);
    }
    .landing-footer.centered-footer a.footer-dev-link svg {
      color: #4ade80;
      flex-shrink: 0;
    }
    @media (max-width: 780px) {
      .steps4-grid, .clients-grid { grid-template-columns: 1fr; }
      .purple-cta-inner { flex-direction: column; text-align: center; }
      .purple-cta-text p { margin: 0 auto; }
    }

    /* ── Made with Veo Showcase Section ── */
    .veo-section {
      position: relative;
      z-index: 5;
      max-width: 1120px;
      width: 100%;
      margin: 10px auto 80px;
      padding: 0 24px;
    }
    .veo-badge {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 6px 13px;
      border-radius: 999px;
      background: rgba(139,92,246,0.14);
      border: 1px solid rgba(139,92,246,0.32);
      color: #c4b5fd;
      font-size: 12px;
      font-weight: 600;
      margin-bottom: 16px;
    }
    .veo-head {
      display: flex;
      justify-content: space-between;
      align-items: flex-end;
      gap: 24px;
      flex-wrap: wrap;
      margin-bottom: 34px;
    }
    .veo-head h2 {
      font-size: clamp(32px, 4.5vw, 50px);
      font-weight: 800;
      line-height: 1.05;
      letter-spacing: -0.03em;
      color: #fff;
    }
    .veo-head h2 span {
      display: block;
      color: #8b5cf6;
    }
    .veo-head p {
      max-width: 340px;
      font-size: 14px;
      line-height: 1.55;
      color: #9ca3af;
      text-align: right;
    }
    @media (max-width: 760px) {
      .veo-head p { text-align: left; }
    }
    .veo-grid {
      display: grid;
      grid-template-columns: repeat(5, minmax(0, 1fr));
      gap: 14px;
      align-items: center;
      margin-bottom: 36px;
    }
    @media (max-width: 900px) {
      .veo-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    .veo-card {
      position: relative;
      border-radius: 16px;
      overflow: hidden;
      aspect-ratio: 9 / 15;
      border: 1px solid rgba(255,255,255,0.10);
      background: #111116;
      box-shadow: 0 20px 50px rgba(0,0,0,0.65);
      transition: transform .2s ease, border-color .2s ease;
    }
    .veo-card:nth-child(2), .veo-card:nth-child(4) { transform: translateY(10px); }
    .veo-card:nth-child(3) { transform: translateY(-10px); }
    .veo-card:hover {
      transform: translateY(-6px) scale(1.02);
      border-color: rgba(139,92,246,0.5);
    }
    .veo-card img,
    .veo-card video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .veo-card::after {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent 55%, rgba(0,0,0,0.72) 100%);
      pointer-events: none;
    }
    .veo-label {
      position: absolute;
      left: 10px;
      bottom: 12px;
      z-index: 2;
      padding: 5px 11px;
      border-radius: 999px;
      background: rgba(17,17,22,0.72);
      border: 1px solid rgba(255,255,255,0.18);
      backdrop-filter: blur(8px);
      color: #f4f4f5;
      font-size: 11.5px;
      font-weight: 600;
    }
    .veo-cta-wrap {
      display: flex;
      justify-content: center;
    }
    .btn-veo-start {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 14px 28px;
      border-radius: 12px;
      border: 0;
      background: linear-gradient(135deg, #7c3aed, #6d28d9);
      color: #ffffff;
      font-size: 14.5px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 12px 32px rgba(124,58,237,0.40);
      transition: transform .15s ease, box-shadow .15s ease;
    }
    .btn-veo-start:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 40px rgba(124,58,237,0.55);
    }
    /* ── Auth (Direct Login / Signup) ── */
    .auth-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      position: relative;
      overflow: hidden;
      background:
        radial-gradient(ellipse 75% 55% at 50% -10%, rgba(139,92,246,0.26), transparent 65%),
        radial-gradient(ellipse 50% 45% at 85% 90%, rgba(59,130,246,0.16), transparent 60%),
        #040406;
    }
    .auth-wrap::before {
      content: "";
      position: absolute;
      inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
      background-size: 48px 48px;
      mask-image: radial-gradient(ellipse 70% 60% at 50% 50%, #000 30%, transparent 80%);
      pointer-events: none;
    }
    .auth-marquee-bg {
      position: absolute;
      inset: -4% -2%;
      display: flex;
      flex-direction: column;
      justify-content: space-around;
      gap: 18px;
      pointer-events: none;
      user-select: none;
      overflow: hidden;
      z-index: 1;
      mask-image: radial-gradient(ellipse 90% 85% at 50% 50%, #000 45%, transparent 100%);
      -webkit-mask-image: radial-gradient(ellipse 90% 85% at 50% 50%, #000 45%, transparent 100%);
    }
    .auth-marquee-row {
      display: flex;
      width: 100%;
      overflow: hidden;
      white-space: nowrap;
    }
    .auth-marquee-track {
      display: flex;
      flex-shrink: 0;
      align-items: center;
      font-size: clamp(20px, 2.2vw, 30px);
      font-weight: 800;
      letter-spacing: 0.04em;
      color: rgba(255, 255, 255, 0.045);
      line-height: 1.1;
      will-change: transform;
      animation: authMarqueeLeft 36s linear infinite;
    }
    .auth-marquee-row:nth-child(even) .auth-marquee-track {
      animation: authMarqueeRight 40s linear infinite;
    }
    .auth-marquee-row:nth-child(3n) .auth-marquee-track {
      animation-duration: 46s;
      color: rgba(167, 139, 250, 0.045);
    }
    .auth-marquee-track span {
      display: inline-block;
      padding-right: 28px;
    }
    @keyframes authMarqueeLeft {
      0% { transform: translateX(0); }
      100% { transform: translateX(-50%); }
    }
    @keyframes authMarqueeRight {
      0% { transform: translateX(-50%); }
      100% { transform: translateX(0); }
    }
    .auth-center-col {
      position: relative;
      z-index: 5;
      width: 100%;
      max-width: 860px;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 18px;
    }
    @keyframes authCardIn {
      from { opacity: 0; transform: translateY(12px) scale(0.985); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .auth-card {
      position: relative;
      width: 100%;
      display: grid;
      grid-template-columns: 1fr 1.08fr;
      background: #0c0c12;
      color: #f4f4f5;
      border: 1px solid rgba(139,92,246,0.28);
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 32px 90px rgba(0,0,0,0.85), 0 0 70px rgba(139,92,246,0.14);
      animation: authCardIn .28s ease-out;
    }
    .auth-split-left {
      background: radial-gradient(circle at 20% 15%, rgba(139,92,246,0.24), transparent 55%),
                  radial-gradient(circle at 80% 85%, rgba(16,185,129,0.12), transparent 55%),
                  #100d1c;
      border-right: 1px solid rgba(255,255,255,0.08);
      padding: 36px 32px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      gap: 28px;
    }
    .auth-split-brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .auth-split-brand img {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      object-fit: cover;
      border: 1px solid rgba(255,255,255,0.16);
    }
    .auth-split-brand span {
      font-size: 16px;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #ffffff;
    }
    .auth-split-copy h2 {
      font-size: 28px;
      font-weight: 800;
      line-height: 1.15;
      letter-spacing: -0.025em;
      color: #ffffff;
      margin-bottom: 12px;
    }
    .auth-split-copy h2 em {
      font-style: normal;
      background: linear-gradient(135deg, #c4b5fd, #818cf8);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .auth-split-copy p {
      font-size: 13.5px;
      line-height: 1.6;
      color: #a1a1aa;
      margin-bottom: 22px;
    }
    .auth-split-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .auth-split-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 13px;
      color: #e4e4e7;
    }
    .auth-split-dot {
      width: 26px;
      height: 26px;
      border-radius: 8px;
      background: rgba(139,92,246,0.16);
      border: 1px solid rgba(139,92,246,0.32);
      color: #c4b5fd;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 13px;
      flex-shrink: 0;
    }
    .auth-split-foot {
      font-size: 12px;
      color: #71717a;
      border-top: 1px solid rgba(255,255,255,0.07);
      padding-top: 16px;
    }
    .auth-split-right {
      padding: 36px 34px 32px;
      background: #0c0c12;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    @media (max-width: 820px) {
      .auth-center-col { max-width: 430px; }
      .auth-card { grid-template-columns: 1fr; }
      .auth-split-left { display: none; }
      .auth-split-right { padding: 30px 26px; }
    }
    .auth-card.is-forgot {
      border-color: rgba(168,85,247,0.45);
      box-shadow: 0 32px 90px rgba(0,0,0,0.85), 0 0 80px rgba(168,85,247,0.18);
    }
    .auth-forgot-icon {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      background: rgba(139,92,246,0.14);
      border: 1px solid rgba(168,85,247,0.35);
      color: #c4b5fd;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 14px;
    }
    .auth-brand {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      text-align: left;
      margin-bottom: 22px;
    }
    .auth-brand img {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      object-fit: cover;
      background: #050507;
      border: 1px solid rgba(139,92,246,0.35);
      margin-bottom: 12px;
    }
    .auth-brand h1 {
      font-size: 24px;
      font-weight: 800;
      color: #ffffff;
      letter-spacing: -0.02em;
    }
    .auth-brand span {
      display: block;
      font-size: 13.5px;
      color: #9ca3af;
      margin-top: 4px;
    }
    .auth-perks {
      display: flex;
      justify-content: flex-start;
      gap: 12px;
      flex-wrap: wrap;
      font-size: 11.5px;
      color: #a1a1aa;
      margin-top: 10px;
    }
    .auth-perks span b { color: #a78bfa; margin-right: 3px; }
    .auth-mode-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 11px;
      border-radius: 999px;
      background: rgba(139,92,246,0.14);
      border: 1px solid rgba(139,92,246,0.30);
      color: #c4b5fd;
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.02em;
      margin-bottom: 10px;
    }
    .auth-back-below {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.12);
      color: #cbd5e1;
      font-size: 12.5px;
      font-weight: 600;
      padding: 7px 18px;
      border-radius: 999px;
      cursor: pointer;
      transition: all .15s ease;
    }
    .auth-back-below:hover { color: #ffffff; background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.25); }
    .auth-trust-note {
      font-size: 11.5px;
      color: #64748b;
      letter-spacing: 0.01em;
    }
    .auth-tabs {
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 13px;
      padding: 4px;
      margin-bottom: 20px;
    }
    .auth-tab {
      border: 0;
      background: transparent;
      color: var(--muted);
      padding: 10px;
      font-size: 13.5px;
      font-weight: 700;
      border-radius: 9px;
      cursor: pointer;
      transition: all .18s ease;
    }
    .auth-tab.active {
      background: #ffffff;
      color: #09090b;
      box-shadow: 0 4px 14px rgba(0,0,0,0.35);
    }
    .field { margin-bottom: 16px; }
    .field label {
      display: block;
      font-size: 12.5px;
      font-weight: 600;
      color: #d4d4d8;
      margin-bottom: 7px;
    }
    .input-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }
    .input-icon {
      position: absolute;
      left: 13px;
      color: #71717a;
      pointer-events: none;
      display: flex;
      align-items: center;
    }
    .field input {
      width: 100%;
      padding: 12px 14px 12px 40px;
      border-radius: 12px;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.04);
      color: #fff;
      font-size: 14px;
      outline: none;
      transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
    }
    .field input.has-toggle { padding-right: 44px; }
    .field input:focus {
      border-color: var(--accent);
      background: rgba(255,255,255,0.06);
      box-shadow: 0 0 0 3px rgba(168,85,247,0.16);
    }
    .pass-toggle {
      position: absolute;
      right: 10px;
      background: transparent;
      border: 0;
      color: #9ca3af;
      cursor: pointer;
      padding: 4px 6px;
      font-size: 14px;
      border-radius: 6px;
    }
    .pass-toggle:hover { color: #fff; background: rgba(255,255,255,0.08); }
    .btn-primary {
      width: 100%;
      margin-top: 8px;
      padding: 13px 18px;
      border: 0;
      border-radius: 12px;
      background: #ffffff;
      color: #09090b;
      font-size: 14.5px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 10px 28px rgba(255,255,255,0.12);
      transition: transform .12s ease, opacity .15s ease, box-shadow .15s ease;
    }
    .btn-primary:hover { opacity: 0.94; transform: translateY(-1px); box-shadow: 0 14px 34px rgba(255,255,255,0.18); }
    .btn-primary:active { transform: scale(0.99); }
    .btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }
    .auth-footer-switch {
      margin-top: 18px;
      text-align: center;
      font-size: 13px;
      color: var(--muted);
    }
    .auth-footer-switch button {
      background: transparent;
      border: 0;
      color: #c4b5fd;
      font-weight: 600;
      cursor: pointer;
      margin-left: 4px;
    }
    .auth-footer-switch button:hover { text-decoration: underline; color: #fff; }
    .auth-card .field label { color: #d4d4d8; font-size: 12.5px; font-weight: 600; margin-bottom: 6px; }
    .auth-card .field input {
      padding: 12px 14px;
      border-radius: 11px;
      border: 1px solid rgba(255,255,255,0.12);
      background: #14141e;
      color: #f8fafc;
      font-size: 14px;
      transition: border-color .15s ease, box-shadow .15s ease;
    }
    .auth-card .field input:-webkit-autofill,
    .auth-card .field input:-webkit-autofill:hover,
    .auth-card .field input:-webkit-autofill:focus {
      -webkit-box-shadow: 0 0 0 1000px #14141e inset;
      -webkit-text-fill-color: #f8fafc;
      transition: background-color 5000s ease-in-out 0s;
    }
    .auth-card .field input::placeholder { color: #71717a; }
    .auth-card .field input.has-toggle { padding-right: 44px; }
    .auth-card .field input:focus {
      border-color: #8b5cf6;
      background: #181826;
      box-shadow: 0 0 0 3px rgba(139,92,246,0.18);
    }
    .auth-card .pass-toggle { color: #9ca3af; }
    .auth-card .pass-toggle:hover { color: #ffffff; background: rgba(255,255,255,0.08); }
    .auth-card .btn-primary {
      background: #2563eb;
      color: #ffffff;
      border-radius: 10px;
      padding: 13px 18px;
      font-size: 14.5px;
      font-weight: 700;
      box-shadow: 0 8px 22px rgba(37, 99, 235, 0.34);
      margin-top: 6px;
    }
    .auth-card .btn-primary:hover {
      background: #3b82f6;
      transform: translateY(-1px);
      box-shadow: 0 12px 28px rgba(37, 99, 235, 0.44);
    }
    .auth-card .auth-footer-switch {
      color: #9ca3af;
      margin-top: 16px;
      font-size: 13px;
    }
    .auth-card .auth-footer-switch button {
      color: #c4b5fd;
      font-weight: 700;
    }
    .auth-card .auth-footer-switch button:hover { color: #ffffff; text-decoration: underline; }
    .auth-card .auth-alert.err {
      background: rgba(239,68,68,0.14);
      border: 1px solid rgba(239,68,68,0.35);
      color: #fca5a5;
    }
    .auth-card .auth-alert.ok {
      background: rgba(34,197,94,0.14);
      border: 1px solid rgba(34,197,94,0.35);
      color: #86efac;
    }
    .auth-alert {
      margin-bottom: 16px;
      padding: 11px 13px;
      border-radius: 10px;
      font-size: 13px;
      line-height: 1.4;
    }
    .auth-alert.err {
      background: rgba(239,68,68,0.14);
      border: 1px solid rgba(239,68,68,0.35);
      color: #fca5a5;
    }
    .auth-alert.ok {
      background: rgba(34,197,94,0.14);
      border: 1px solid rgba(34,197,94,0.35);
      color: #86efac;
    }

    /* ── User Dashboard (UI/UX Pro Max — Executive Midnight Slate Workspace) ── */
    .dash-wrap {
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      background:
        radial-gradient(ellipse 75% 45% at 50% -10%, rgba(37, 99, 235, 0.14), transparent 65%),
        var(--bg);
    }
    .topbar {
      position: sticky;
      top: 0;
      z-index: 35;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 12px 28px;
      background: rgba(11, 17, 32, 0.88);
      backdrop-filter: blur(14px);
      -webkit-backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--card-border);
      flex-wrap: wrap;
    }
    .topbar-brand {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      text-decoration: none;
      color: var(--text);
      font-weight: 800;
      font-size: 16px;
      letter-spacing: -0.02em;
    }
    .topbar-brand img {
      width: 30px;
      height: 30px;
      border-radius: 8px;
      border: 1px solid var(--border-strong);
      object-fit: cover;
    }
    .topbar-right {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }
    .topbar-notice {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 6px 13px;
      border-radius: 8px;
      background: rgba(16, 185, 129, 0.10);
      border: 1px solid rgba(16, 185, 129, 0.28);
      color: #ecfdf5;
      font-size: 12px;
      font-weight: 600;
      text-decoration: none;
      transition: border-color 0.15s ease, background 0.15s ease;
    }
    .topbar-notice:hover {
      border-color: rgba(16, 185, 129, 0.55);
      background: rgba(16, 185, 129, 0.18);
    }
    .topbar-notice-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #10b981;
      flex-shrink: 0;
    }
    .topbar-notice-cta {
      color: #34d399;
      font-weight: 700;
      margin-left: 2px;
    }
    .boot-loader {
      position: fixed;
      inset: 0;
      z-index: 9999;
      background: var(--bg);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 14px;
      transition: opacity 0.18s ease, visibility 0.18s ease;
    }
    .boot-loader.is-done {
      opacity: 0;
      visibility: hidden;
      pointer-events: none;
    }
    .boot-spinner {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      border: 3px solid rgba(148, 163, 184, 0.18);
      border-top-color: #2563eb;
      animation: bootSpin 0.7s linear infinite;
    }
    .boot-label {
      color: var(--muted);
      font-size: 13px;
      font-weight: 600;
    }
    @keyframes bootSpin { to { transform: rotate(360deg); } }
    .plan-pill {
      background: rgba(37, 99, 235, 0.15);
      color: #93c5fd;
      border: 1px solid rgba(59, 130, 246, 0.32);
      padding: 6px 12px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.01em;
    }
    .avatar-btn {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      border: 1px solid rgba(59, 130, 246, 0.35);
      background: #2563eb;
      color: #fff;
      font-weight: 800;
      font-size: 13.5px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: background 0.15s ease;
    }
    .avatar-btn:hover { background: #3b82f6; }
    .dropdown {
      position: absolute;
      top: 62px;
      right: 28px;
      width: 280px;
      background: var(--card);
      border: 1px solid var(--border-strong);
      border-radius: 12px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.75);
      overflow: hidden;
      z-index: 50;
    }
    .dd-head {
      padding: 14px 16px;
      background: var(--bg-elevated);
      border-bottom: 1px solid var(--card-border);
    }
    .dd-name {
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }
    .dd-email {
      font-size: 12px;
      color: var(--muted);
      margin-top: 2px;
      word-break: break-all;
    }
    .dd-credits {
      padding: 14px 16px;
      border-bottom: 1px solid var(--card-border);
    }
    .dd-credits-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 12px;
      margin-bottom: 8px;
    }
    .dd-credits-label { color: #cbd5e1; display: flex; align-items: center; gap: 5px; font-weight: 600; }
    .dd-credits-val { font-weight: 700; color: #fff; font-family: var(--font-mono); }
    .dd-credits-val span { color: var(--muted); font-weight: 500; }
    .progress-track {
      width: 100%;
      height: 6px;
      border-radius: 999px;
      background: rgba(148, 163, 184, 0.16);
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: #2563eb;
      border-radius: 999px;
      width: 100%;
      transition: width .3s ease;
    }
    .dd-days {
      margin-top: 7px;
      font-size: 11.5px;
      color: var(--muted);
    }
    .dd-menu { padding: 6px; }
    .dd-item {
      width: 100%;
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 9px 11px;
      border: 0;
      border-radius: 8px;
      background: transparent;
      color: #cbd5e1;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
      text-align: left;
      transition: background .14s ease, color .14s ease;
    }
    .dd-item svg { width: 15px; height: 15px; color: #94a3b8; flex-shrink: 0; }
    .dd-item:hover { background: rgba(255, 255, 255, 0.06); color: #fff; }
    .dd-item:hover svg { color: #fff; }
    .dd-item.signout {
      border-top: 1px solid var(--card-border);
      margin-top: 4px;
      padding-top: 11px;
      border-radius: 0 0 8px 8px;
      color: #fca5a5;
    }
    .dd-item.signout svg { color: #fca5a5; }
    .dd-item.signout:hover { background: rgba(239, 68, 68, 0.12); color: #fff; }

    .dash-main {
      flex: 1;
      width: 100%;
      max-width: 1200px;
      margin: 0 auto;
      padding: 28px 24px 84px;
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
    .dash-stats-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }
    @media (max-width: 820px) {
      .dash-stats-grid { grid-template-columns: 1fr; }
    }
    .dash-stat-card {
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: 14px;
      padding: 18px 20px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .dash-stat-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12px;
      font-weight: 600;
      color: var(--muted);
    }
    .dash-stat-val {
      font-family: var(--font-mono);
      font-size: 24px;
      font-weight: 700;
      color: var(--text);
      letter-spacing: -0.02em;
    }
    .dash-stat-sub { font-size: 12px; color: var(--muted); }
    .hero-banner {
      position: relative;
      width: 100%;
      min-height: 360px;
      border-radius: 18px;
      overflow: hidden;
      border: 1px solid var(--border-strong);
      background: #0b1120 url('/google-flow-banner.jpg') center center / cover no-repeat;
      box-shadow: 0 24px 64px rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: flex-end;
      padding: 36px;
    }
    .hero-banner::before {
      content: "";
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(11, 17, 32, 0.25) 0%, rgba(11, 17, 32, 0.72) 55%, rgba(11, 17, 32, 0.96) 100%);
    }
    .hero-banner-content {
      position: relative;
      z-index: 2;
      max-width: 620px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      width: fit-content;
      padding: 5px 11px;
      border-radius: 999px;
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.35);
      color: #6ee7b7;
      font-size: 11.5px;
      font-weight: 700;
    }
    .hero-banner-content h2 {
      font-size: clamp(24px, 3.2vw, 34px);
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.15;
      color: #ffffff;
    }
    .hero-banner-content p {
      font-size: 14px;
      line-height: 1.6;
      color: #cbd5e1;
    }
    .cta-wrap {
      margin-top: 6px;
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    .btn-generate {
      display: inline-flex;
      align-items: center;
      gap: 9px;
      padding: 13px 26px;
      border-radius: 10px;
      border: 0;
      background: #2563eb;
      color: #ffffff;
      font-size: 14.5px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      box-shadow: 0 10px 26px rgba(37, 99, 235, 0.36);
      transition: background .15s ease, transform .12s ease;
    }
    .btn-generate:hover {
      background: #3b82f6;
      transform: translateY(-1px);
    }
    .btn-ext-sec {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 20px;
      border-radius: 10px;
      border: 1px solid rgba(148, 163, 184, 0.3);
      background: rgba(15, 23, 42, 0.8);
      color: #f8fafc;
      font-size: 13.5px;
      font-weight: 600;
      text-decoration: none;
      transition: background .15s ease, border-color .15s ease;
    }
    .btn-ext-sec:hover { background: rgba(30, 41, 59, 0.95); border-color: rgba(148, 163, 184, 0.5); }
    .dash-steps-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
    }
    @media (max-width: 820px) {
      .dash-steps-grid { grid-template-columns: 1fr; }
      .dash-main { padding: 16px 14px 80px; }
      .hero-banner { padding: 24px 20px; min-height: 300px; }
      .topbar { padding: 10px 14px; }
    }
    .dash-step {
      background: var(--card);
      border: 1px solid var(--card-border);
      border-radius: 12px;
      padding: 16px 18px;
      display: flex;
      align-items: flex-start;
      gap: 12px;
    }
    .dash-step-num {
      width: 28px;
      height: 28px;
      border-radius: 8px;
      background: rgba(37, 99, 235, 0.14);
      border: 1px solid rgba(59, 130, 246, 0.28);
      color: #60a5fa;
      font-family: var(--font-mono);
      font-size: 12px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }
    .dash-step h4 { font-size: 13.5px; font-weight: 700; color: var(--text); margin-bottom: 3px; }
    .dash-step p { font-size: 12.5px; color: var(--muted); line-height: 1.5; }

    /* Simple Modal */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.75);
      backdrop-filter: blur(6px);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      z-index: 80;
    }
    .modal-card {
      width: 100%;
      max-width: 460px;
      background: #141418;
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 16px;
      padding: 24px;
    }
    .modal-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }
    .modal-head h3 { font-size: 17px; font-weight: 700; }
    .modal-close {
      background: transparent;
      border: 0;
      color: var(--muted);
      font-size: 20px;
      cursor: pointer;
    }
    .modal-body { font-size: 14px; color: #d1d5db; line-height: 1.6; }
    .info-row {
      display: flex;
      justify-content: space-between;
      padding: 10px 0;
      border-bottom: 1px solid rgba(255,255,255,0.07);
    }
    .info-row:last-child { border-bottom: 0; }

    /* ── Settings Page ── */
    .settings-wrap {
      min-height: 100vh;
      background:
        radial-gradient(ellipse 70% 40% at 50% 0%, rgba(37, 99, 235, 0.14), transparent 65%),
        var(--bg);
      padding: 36px 24px 72px;
    }
    .settings-container {
      max-width: 820px;
      margin: 0 auto;
    }
    .settings-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding-bottom: 20px;
      margin-bottom: 24px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .settings-header h2 {
      font-size: 24px;
      font-weight: 800;
      letter-spacing: -0.02em;
    }
    .settings-header-sub {
      font-size: 13px;
      color: var(--muted);
      margin-top: 4px;
    }
    .settings-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
      margin-bottom: 18px;
    }
    @media (max-width: 720px) {
      .settings-grid { grid-template-columns: 1fr; }
    }
    .settings-card {
      background: linear-gradient(180deg, rgba(24,24,32,0.92), rgba(15,15,21,0.98));
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 20px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      box-shadow: 0 12px 32px rgba(0,0,0,0.45);
      transition: border-color 0.2s ease, transform 0.15s ease;
    }
    .settings-card:hover {
      border-color: rgba(168,85,247,0.35);
    }
    .settings-card.email-card:hover {
      border-color: rgba(16,185,129,0.38);
    }
    .settings-card-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      width: fit-content;
      padding: 4px 11px;
      border-radius: 999px;
      background: rgba(168,85,247,0.16);
      border: 1px solid rgba(168,85,247,0.35);
      color: #e9d5ff;
      font-size: 11px;
      font-weight: 700;
      margin-bottom: 12px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .settings-card-badge.emerald {
      background: rgba(16,185,129,0.15);
      border-color: rgba(52,211,153,0.35);
      color: #6ee7b7;
    }
    .settings-card h3 {
      font-size: 17px;
      font-weight: 700;
      margin-bottom: 6px;
    }
    .settings-card p.sub {
      font-size: 13px;
      color: var(--muted);
      margin-bottom: 18px;
      line-height: 1.45;
    }
    .settings-card .field {
      margin-bottom: 14px;
    }
    .settings-card .field label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: #d4d4d8;
      margin-bottom: 6px;
    }
    .settings-card .field input {
      width: 100%;
      border-radius: 12px !important;
      padding: 11px 14px !important;
      background: #08080c;
      border: 1px solid rgba(255,255,255,0.12);
      color: #f5f5f7;
      font-size: 13.5px;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .settings-card .field input:focus {
      outline: none;
      border-color: #a855f7;
      box-shadow: 0 0 0 3px rgba(168,85,247,0.18);
    }
    .settings-submit-btn {
      margin-top: 4px;
      padding: 11px 22px;
      border: 0;
      border-radius: 10px;
      background: #2563eb;
      color: #ffffff;
      font-size: 13.5px;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 6px 18px rgba(37, 99, 235, 0.30);
      transition: background 0.15s ease, transform 0.12s ease;
    }
    .settings-submit-btn:hover { background: #3b82f6; }
    .settings-submit-btn.emerald {
      background: #10b981;
      color: #052e16;
      box-shadow: 0 6px 18px rgba(16, 185, 129, 0.25);
    }
    .settings-submit-btn.emerald:hover { background: #34d399; }
    .settings-submit-btn:active { transform: scale(0.98); }
    .settings-card.danger {
      border-color: rgba(239,68,68,0.32);
      background: linear-gradient(135deg, rgba(239,68,68,0.08), rgba(17,17,22,0.95));
      flex-direction: row;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      flex-wrap: wrap;
    }
    .settings-card.danger p.sub { margin-bottom: 0; }
    .btn-danger {
      padding: 11px 20px;
      border: 0;
      border-radius: 12px;
      background: #ef4444;
      color: #fff;
      font-size: 13.5px;
      font-weight: 700;
      cursor: pointer;
      flex-shrink: 0;
      transition: background 0.15s ease, transform 0.12s ease;
    }
    .btn-danger:hover { background: #dc2626; }
    .btn-danger:active { transform: scale(0.98); }
    .btn-danger:disabled { opacity: 0.55; cursor: not-allowed; }

    /* ── Plan & Pricing Page (Pro 20d & Max 30d, Google Flow Only) ── */
    .pricing-wrap {
      min-height: 100vh;
      background:
        radial-gradient(ellipse 70% 40% at 50% 0%, rgba(37, 99, 235, 0.14), transparent 65%),
        var(--bg);
      display: flex;
      flex-direction: column;
    }
    .pricing-body {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 40px 20px 64px;
    }
    .pricing-shell {
      width: 100%;
      max-width: 820px;
      margin: 0 auto;
    }
    .pricing-top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 16px;
    }
    .pricing-top h3 { font-size: 20px; font-weight: 700; }
    .pricing-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 18px;
    }
    @media (max-width: 720px) {
      .pricing-grid { grid-template-columns: 1fr; }
    }
    .p-card {
      border-radius: 18px;
      padding: 22px;
      border: 1px solid rgba(255,255,255,0.12);
      background: #0d0e12;
      display: flex;
      flex-direction: column;
    }
    .p-card.pro {
      border-color: rgba(212,245,42,0.28);
      background: linear-gradient(180deg, rgba(212,245,42,0.06) 0%, #0b0c0f 45%);
    }
    .p-card.max {
      border-color: rgba(59,130,246,0.38);
      background: linear-gradient(180deg, rgba(59,130,246,0.10) 0%, #0a0d16 45%);
    }
    .p-title-row {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
      margin-bottom: 4px;
    }
    .p-title-row h4 {
      font-size: 24px;
      font-weight: 800;
      letter-spacing: 0.01em;
    }
    .p-tag {
      font-size: 11px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 999px;
      background: #2563eb;
      color: #fff;
    }
    .p-tag.pink { background: #db2777; }
    .p-sub { font-size: 13px; color: var(--muted); margin-bottom: 14px; }
    .p-box {
      background: rgba(255,255,255,0.03);
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 12px;
      padding: 14px;
      margin-bottom: 14px;
    }
    .p-credits { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
    .p-card.pro .p-credits { color: #d4f52a; }
    .p-card.max .p-credits { color: #60a5fa; }
    .p-duration { font-size: 12px; color: #9ca3af; margin-bottom: 12px; }
    .p-tools-label {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.06em;
      color: #6b7280;
      margin-bottom: 8px;
    }
    .p-tool-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 13px;
      font-weight: 600;
      color: #f3f4f6;
    }
    .p-tool-row span:last-child { font-size: 12px; color: #9ca3af; font-weight: 500; }
    .p-btn {
      width: 100%;
      padding: 13px 16px;
      border: 0;
      border-radius: 11px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      margin-bottom: 10px;
      transition: opacity .15s ease;
    }
    .p-btn:hover { opacity: 0.92; }
    .p-btn.pro { background: #d4f52a; color: #09090b; }
    .p-btn.max { background: #3b82f6; color: #ffffff; }
    .p-reseller-note {
      text-align: center;
      font-size: 11.5px;
      color: #d1d5db;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.07);
      border-radius: 8px;
      padding: 8px 10px;
      margin-bottom: 14px;
    }
    .p-noads {
      display: inline-block;
      font-size: 11px;
      font-weight: 600;
      color: #4ade80;
      background: rgba(34,197,94,0.12);
      border: 1px solid rgba(34,197,94,0.28);
      border-radius: 999px;
      padding: 3px 10px;
      margin-bottom: 12px;
      width: fit-content;
    }
    .p-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
      font-size: 13px;
      color: #d1d5db;
    }
    .p-card.pro .p-list li::before { content: "✓ "; color: #d4f52a; font-weight: 700; }
    .p-card.max .p-list li::before { content: "✓ "; color: #60a5fa; font-weight: 700; }

    /* Bottom-right corner toast */
    .corner-toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 999;
      max-width: 340px;
      background: #141418;
      border: 1px solid rgba(255,255,255,0.16);
      border-radius: 14px;
      padding: 14px 16px;
      box-shadow: 0 20px 50px rgba(0,0,0,0.8);
      display: flex;
      align-items: flex-start;
      gap: 12px;
      animation: toastIn .18s ease-out;
    }
    @keyframes toastIn {
      from { opacity: 0; transform: translateY(12px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .corner-toast-body { flex: 1; }
    .corner-toast-title { font-size: 14px; font-weight: 700; color: #fff; margin-bottom: 3px; }
    .corner-toast-msg { font-size: 12.5px; color: #d1d5db; line-height: 1.45; }
    .corner-toast-close {
      background: transparent;
      border: 0;
      color: #9ca3af;
      font-size: 18px;
      line-height: 1;
      cursor: pointer;
      padding: 0 2px;
    }
    .corner-toast-close:hover { color: #fff; }
    /* Floating WhatsApp Support Button (Reseller / Platform) */
    .wa-float-btn {
      position: fixed;
      right: 22px;
      bottom: 22px;
      z-index: 75;
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 12px 18px 12px 14px;
      border-radius: 999px;
      background: linear-gradient(135deg, #25d366 0%, #128c7e 100%);
      color: #ffffff;
      text-decoration: none;
      font-size: 13.5px;
      font-weight: 700;
      box-shadow: 0 12px 32px rgba(37, 211, 102, 0.36), 0 4px 12px rgba(0, 0, 0, 0.45);
      border: 1px solid rgba(255, 255, 255, 0.22);
      transition: transform 0.16s ease, box-shadow 0.16s ease;
    }
    .wa-float-btn:hover {
      transform: translateY(-2px) scale(1.02);
      box-shadow: 0 16px 38px rgba(37, 211, 102, 0.48), 0 6px 16px rgba(0, 0, 0, 0.55);
    }
    .wa-float-btn svg { width: 22px; height: 22px; flex-shrink: 0; }
    @media (max-width: 640px) {
      .wa-float-btn { right: 16px; bottom: 16px; padding: 13px; }
      .wa-float-btn .wa-float-label { display: none; }
    }

    /* Existing Session Confirmation Modal */
    .session-modal-card {
      max-width: 400px;
      text-align: center;
      border: 1px solid rgba(168, 85, 247, 0.35);
      box-shadow: 0 24px 70px rgba(0, 0, 0, 0.8), 0 0 45px rgba(168, 85, 247, 0.16);
    }
    .session-modal-icon {
      width: 52px;
      height: 52px;
      margin: 0 auto 14px;
      border-radius: 14px;
      background: rgba(245, 158, 11, 0.14);
      border: 1px solid rgba(245, 158, 11, 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
    }
    .session-modal-title { font-size: 18px; font-weight: 800; color: #fff; margin-bottom: 8px; }
    .session-modal-desc { font-size: 13.5px; color: #a1a1aa; line-height: 1.55; margin-bottom: 20px; }
    .session-modal-actions { display: flex; flex-direction: column; gap: 10px; }
    .btn-kick-signin {
      width: 100%;
      padding: 12px 18px;
      border: 0;
      border-radius: 10px;
      background: #2563eb;
      color: #ffffff;
      font-size: 13.5px;
      font-weight: 700;
      cursor: pointer;
      transition: background 0.15s ease, transform 0.12s ease;
    }
    .btn-kick-signin:hover { background: #3b82f6; }
    .btn-kick-signin:disabled { opacity: 0.55; cursor: not-allowed; }
    .btn-kick-cancel {
      width: 100%;
      padding: 10px 16px;
      border-radius: 11px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(255, 255, 255, 0.04);
      color: #d4d4d8;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
    }
    .btn-kick-cancel:hover { background: rgba(255, 255, 255, 0.09); color: #fff; }

    @media (max-width: 640px) {
      .hero-banner { padding: 32px 24px; min-height: 320px; }
      .topbar { padding: 14px 16px; }
      .dropdown { right: 16px; }
    }

    /* ── Option B Theme: Electric Purple 3D Hero + Alternating Light/Dark SaaS + Responsive Hamburger ── */
    .landing-nav-outer {
      background: rgba(8, 7, 14, 0.90) !important;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
    }
    .landing-nav { padding: 13px 24px !important; }
    .btn-pill-sm {
      background: linear-gradient(135deg, #8b5cf6, #6d28d9) !important;
      color: #ffffff !important;
      box-shadow: 0 6px 20px rgba(124, 58, 237, 0.35);
    }
    .mob-menu-btn {
      display: none;
      width: 40px;
      height: 40px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.14);
      background: rgba(255, 255, 255, 0.05);
      color: #ffffff;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      flex-shrink: 0;
      transition: background 0.15s ease, border-color 0.15s ease;
    }
    .mob-menu-btn:hover { background: rgba(139, 92, 246, 0.22); border-color: rgba(167, 139, 250, 0.45); }
    @media (max-width: 860px) {
      .landing-links,
      .landing-actions { display: none !important; }
      .mob-menu-btn { display: inline-flex; }
    }
    .mob-drawer-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(4, 3, 10, 0.72);
      backdrop-filter: blur(6px);
      z-index: 9998;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s ease;
    }
    .mob-drawer-backdrop.open { opacity: 1; pointer-events: auto; }
    .mob-drawer {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: min(320px, 86vw);
      background: #0b0914;
      border-left: 1px solid rgba(139, 92, 246, 0.28);
      box-shadow: -20px 0 60px rgba(0, 0, 0, 0.75);
      z-index: 9999;
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 18px;
      transform: translateX(102%);
      transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1);
    }
    .mob-drawer.open { transform: translateX(0); }
    .mob-drawer-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 14px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .mob-drawer-close {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.12);
      background: rgba(255, 255, 255, 0.05);
      color: #e4e4e7;
      font-size: 20px;
      line-height: 1;
      cursor: pointer;
    }
    .mob-drawer-links {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .mob-drawer-links a {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 14px;
      border-radius: 12px;
      color: #e4e4e7;
      text-decoration: none;
      font-size: 14.5px;
      font-weight: 600;
      border: 1px solid transparent;
      transition: background 0.15s ease, border-color 0.15s ease;
    }
    .mob-drawer-links a:hover,
    .mob-drawer-links a.active {
      background: rgba(139, 92, 246, 0.16);
      border-color: rgba(167, 139, 250, 0.32);
      color: #ffffff;
    }
    .mob-drawer-actions {
      margin-top: auto;
      display: flex;
      flex-direction: column;
      gap: 10px;
      padding-top: 14px;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
    }
    .mob-drawer-actions button,
    .mob-drawer-actions a {
      width: 100%;
      text-align: center;
      text-decoration: none;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 700;
    }

    /* Electric Purple 3D Hero (Matched to Reference Composition) */
    .pf-hero-band {
      position: relative;
      width: 100%;
      overflow: hidden;
      background:
        radial-gradient(circle at 74% 52%, rgba(168, 85, 247, 0.78) 0%, rgba(124, 58, 237, 0.52) 26%, rgba(46, 16, 101, 0.85) 58%, #150734 100%),
        linear-gradient(105deg, #12062d 0%, #240c59 48%, #4c1d95 100%);
      padding: clamp(36px, 5.5vw, 68px) 24px clamp(38px, 5.5vw, 64px);
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .pf-hero-inner {
      max-width: 1180px;
      margin: 0 auto;
      display: grid;
      grid-template-columns: minmax(0, 1.08fr) minmax(0, 0.92fr);
      align-items: center;
      gap: clamp(20px, 3vw, 40px);
    }
    .pf-hero-eyebrow {
      display: inline-block;
      color: rgba(233, 213, 255, 0.78);
      font-size: 12px;
      font-weight: 600;
      letter-spacing: 0.14em;
      text-transform: uppercase;
      margin-bottom: 16px;
    }
    .pf-hero-left h1 {
      font-size: clamp(40px, 5.6vw, 74px);
      font-weight: 800;
      line-height: 0.98;
      letter-spacing: -0.035em;
      color: #ffffff;
      text-transform: uppercase;
      margin-bottom: 20px;
      min-height: 3.02em;
    }
    .pf-hero-sub {
      font-size: clamp(15px, 1.6vw, 18px);
      line-height: 1.52;
      color: rgba(243, 232, 255, 0.90);
      max-width: 440px;
      margin-bottom: 26px;
      font-weight: 500;
    }
    .pf-hero-cta-row {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
      margin-bottom: 30px;
    }
    .pf-hero-white-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 13px 24px;
      border-radius: 12px;
      border: 0;
      background: #ffffff;
      color: #140730;
      font-size: 13.5px;
      font-weight: 800;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      cursor: pointer;
      text-decoration: none;
      box-shadow: 0 10px 28px rgba(0, 0, 0, 0.25);
      transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .pf-hero-white-btn:hover { transform: translateY(-2px); box-shadow: 0 14px 34px rgba(0, 0, 0, 0.34); }
    .pf-hero-outline-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 12px 22px;
      border-radius: 12px;
      border: 1.5px solid rgba(255, 255, 255, 0.42);
      background: rgba(255, 255, 255, 0.06);
      color: #ffffff;
      font-size: 13px;
      font-weight: 700;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      text-decoration: none;
      backdrop-filter: blur(8px);
      transition: background 0.15s ease, border-color 0.15s ease;
    }
    .pf-hero-outline-btn:hover { background: rgba(255, 255, 255, 0.14); border-color: rgba(255, 255, 255, 0.72); }
    .pf-hero-stats-inline {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: 10px 16px;
      font-size: clamp(13.5px, 1.35vw, 16px);
      font-weight: 700;
      color: #ffffff;
      letter-spacing: -0.01em;
    }
    .pf-hero-stats-inline .sep {
      color: rgba(255, 255, 255, 0.34);
      font-weight: 400;
    }
    .pf-hero-right {
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .pf-hero-robot-wrap {
      position: relative;
      width: 100%;
      max-width: 500px;
      border: 0;
      box-shadow: none;
      background: transparent;
    }
    .pf-hero-robot-wrap img {
      width: 100%;
      height: auto;
      display: block;
      object-fit: contain;
      filter: drop-shadow(0 20px 44px rgba(15, 4, 38, 0.50));
    }
    @media (max-width: 920px) {
      .pf-hero-band {
        background:
          radial-gradient(circle at 50% 78%, rgba(168, 85, 247, 0.68) 0%, rgba(109, 40, 217, 0.42) 34%, #150734 88%);
      }
      .pf-hero-inner { grid-template-columns: 1fr; text-align: center; }
      .pf-hero-sub { margin-left: auto; margin-right: auto; }
      .pf-hero-cta-row { justify-content: center; }
      .pf-hero-stats-inline { justify-content: center; }
      .pf-hero-robot-wrap { max-width: 340px; margin: 0 auto; }
    }

    /* Crisp White Live Preview Band */
    .pf-white-band {
      width: 100%;
      background: #ffffff;
      color: #0f172a;
      padding: clamp(48px, 6vw, 76px) 24px;
    }
    .pf-lilac-band {
      width: 100%;
      background: #f5f3ff;
      color: #0f172a;
      padding: clamp(48px, 6vw, 76px) 24px;
    }
    .pf-dark-band {
      width: 100%;
      background: #08070f;
      color: #ffffff;
      padding: clamp(52px, 6vw, 84px) 24px;
    }
    .pf-container { max-width: 1120px; margin: 0 auto; width: 100%; }
    .pf-preview-label {
      text-align: center;
      font-size: 11.5px;
      font-weight: 800;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #7c3aed;
      margin-bottom: 18px;
    }
    .pf-browser-frame {
      max-width: 960px;
      margin: 0 auto;
      border-radius: 18px;
      overflow: hidden;
      background: #0d0a1a;
      border: 1px solid rgba(124, 58, 237, 0.25);
      box-shadow: 0 28px 70px rgba(30, 8, 66, 0.20);
    }
    .pf-browser-bar {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 11px 16px;
      background: #141026;
      border-bottom: 1px solid rgba(255, 255, 255, 0.07);
    }
    .pf-b-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    .pf-b-url {
      margin: 0 auto;
      padding: 4px 18px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.06);
      color: #a1a1aa;
      font-size: 11.5px;
      font-family: 'JetBrains Mono', monospace;
    }
    .pf-browser-body {
      display: grid;
      grid-template-columns: 200px minmax(0, 1fr);
      min-height: 310px;
      color: #f4f4f5;
    }
    .pf-mock-side {
      background: #0a0814;
      border-right: 1px solid rgba(255, 255, 255, 0.07);
      padding: 18px 14px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .pf-mock-brand { display: flex; align-items: center; gap: 8px; font-weight: 800; font-size: 14px; margin-bottom: 10px; }
    .pf-mock-brand img { width: 24px; height: 24px; border-radius: 6px; }
    .pf-mock-navitem { padding: 8px 10px; border-radius: 8px; font-size: 12px; color: #9ca3af; font-weight: 600; }
    .pf-mock-navitem.active { background: rgba(139, 92, 246, 0.20); color: #e9d5ff; }
    .pf-mock-main { padding: 22px 24px; display: flex; flex-direction: column; gap: 18px; }
    .pf-mock-top { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .pf-mock-pills { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .pf-mpill { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
    .pf-mpill.green { background: rgba(16, 185, 129, 0.16); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); }
    .pf-mpill.purple { background: rgba(139, 92, 246, 0.20); color: #d8b4fe; border: 1px solid rgba(139, 92, 246, 0.35); }
    .pf-mock-hero {
      padding: 20px;
      border-radius: 14px;
      background: linear-gradient(135deg, rgba(124, 58, 237, 0.24), rgba(15, 23, 42, 0.72));
      border: 1px solid rgba(139, 92, 246, 0.28);
    }
    .pf-mock-hero h4 { font-size: 18px; font-weight: 800; margin-bottom: 6px; }
    .pf-mock-hero p { font-size: 12.5px; color: #cbd5e1; margin-bottom: 14px; }
    .pf-mock-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
    .pf-mcard {
      padding: 14px;
      border-radius: 12px;
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255, 255, 255, 0.08);
    }
    .pf-mcard small { display: block; font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em; color: #94a3b8; margin-bottom: 4px; }
    .pf-mcard strong { font-size: 16px; font-weight: 800; color: #ffffff; }
    @media (max-width: 760px) {
      .pf-browser-body { grid-template-columns: 1fr; }
      .pf-mock-side { display: none; }
      .pf-mock-cards { grid-template-columns: 1fr; }
    }

    /* Editorial Light Sections */
    .pf-sec-head { text-align: center; margin-bottom: 36px; }
    .pf-sec-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 14px;
      border-radius: 999px;
      background: #ede9fe;
      color: #6d28d9;
      font-size: 11.5px;
      font-weight: 700;
      margin-bottom: 12px;
    }
    .pf-sec-head h2 {
      font-size: clamp(28px, 4.2vw, 44px);
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.08;
      color: #0f172a;
      margin-bottom: 10px;
    }
    .pf-sec-head h2 span { color: #7c3aed; }
    .pf-sec-head p { font-size: 15px; color: #64748b; max-width: 560px; margin: 0 auto; line-height: 1.6; }

    .pf-trust-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 18px;
      max-width: 880px;
      margin: 0 auto 28px;
      text-align: center;
    }
    .pf-trust-num strong { display: block; font-size: clamp(24px, 3vw, 34px); font-weight: 800; color: #6d28d9; letter-spacing: -0.02em; }
    .pf-trust-num span { font-size: 12.5px; color: #64748b; font-weight: 600; }
    .pf-trust-pills {
      display: flex;
      justify-content: center;
      flex-wrap: wrap;
      gap: 14px 24px;
      font-size: 12.5px;
      font-weight: 600;
      color: #475569;
      padding-top: 20px;
      border-top: 1px solid #e2e8f0;
    }
    @media (max-width: 680px) { .pf-trust-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }

    .pf-bento-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 18px;
    }
    @media (max-width: 900px) { .pf-bento-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 600px) { .pf-bento-grid { grid-template-columns: 1fr; } }
    .pf-bento-card {
      background: #ffffff;
      border: 1px solid #e9e5ff;
      border-radius: 18px;
      padding: 24px;
      box-shadow: 0 8px 24px rgba(109, 40, 217, 0.05);
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }
    .pf-bento-card:hover { transform: translateY(-3px); box-shadow: 0 16px 36px rgba(109, 40, 217, 0.10); }
    .pf-bento-ico {
      width: 40px;
      height: 40px;
      border-radius: 11px;
      background: #f3e8ff;
      color: #7c3aed;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 18px;
      margin-bottom: 14px;
    }
    .pf-bento-card h3 { font-size: 16px; font-weight: 800; color: #0f172a; margin-bottom: 6px; }
    .pf-bento-card p { font-size: 13.5px; line-height: 1.55; color: #64748b; }

    /* Skip the shoot comparison */
    .pf-compare-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 20px;
      max-width: 880px;
      margin: 0 auto;
    }
    @media (max-width: 720px) { .pf-compare-grid { grid-template-columns: 1fr; } }
    .pf-cmp-card {
      border-radius: 22px;
      padding: 28px 26px;
    }
    .pf-cmp-card.old {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      color: #475569;
    }
    .pf-cmp-card.new {
      background: linear-gradient(145deg, #2e1065 0%, #5b21b6 60%, #7c3aed 100%);
      color: #ffffff;
      box-shadow: 0 22px 50px rgba(109, 40, 217, 0.28);
    }
    .pf-cmp-tag {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
      margin-bottom: 18px;
    }
    .pf-cmp-card.old .pf-cmp-tag { background: #e2e8f0; color: #475569; }
    .pf-cmp-card.new .pf-cmp-tag { background: rgba(255, 255, 255, 0.16); color: #ffffff; }
    .pf-cmp-list { list-style: none; display: flex; flex-direction: column; gap: 12px; font-size: 14px; font-weight: 600; }
    .pf-cmp-list li { display: flex; align-items: center; gap: 10px; }

    /* Home Plans Band (uses exact PRO & MAX pricing content) */
    .pf-home-plans-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 22px;
      max-width: 880px;
      margin: 0 auto;
    }
    @media (max-width: 780px) {
      .pf-home-plans-grid { grid-template-columns: 1fr; }
      .steps4-grid { grid-template-columns: 1fr !important; }
      .clients-marquee-wrap { grid-template-columns: 1fr !important; height: 340px !important; }
      .clients-col.col-slow { display: none; }
    }

    /* Subpage Electric Purple Hero (About & Pricing) */
    .pf-subhero {
      width: 100%;
      background:
        radial-gradient(ellipse 60% 80% at 50% 0%, rgba(168, 85, 247, 0.40), transparent 65%),
        linear-gradient(135deg, #1e0842 0%, #3b0f7a 55%, #5b21b6 100%);
      padding: clamp(42px, 5vw, 68px) 24px;
      text-align: center;
      color: #ffffff;
      border-bottom: 1px solid rgba(255, 255, 255, 0.10);
    }
    .pf-subhero h1,
    .pf-subhero h2 {
      font-size: clamp(30px, 4.8vw, 52px);
      font-weight: 800;
      letter-spacing: -0.03em;
      line-height: 1.08;
      margin-bottom: 12px;
      color: #ffffff;
    }
    .pf-subhero p {
      font-size: clamp(14.5px, 1.5vw, 17px);
      color: rgba(243, 232, 255, 0.86);
      max-width: 640px;
      margin: 0 auto;
      line-height: 1.6;
    }
    .auth-split-left {
      background: radial-gradient(circle at 20% 15%, rgba(168, 85, 247, 0.42), transparent 55%),
                  linear-gradient(145deg, #1e0842 0%, #4c1d95 58%, #2e1065 100%) !important;
    }

    /* High-Contrast Option B Pricing Cards (Crisp on BOTH White & Dark Backgrounds) */
    .p-card.pro,
    .p-card.max {
      background: linear-gradient(165deg, #160c2e 0%, #0f0a1f 55%, #090714 100%) !important;
      border: 1px solid rgba(139, 92, 246, 0.34) !important;
      border-radius: 22px !important;
      color: #f8fafc !important;
      box-shadow: 0 22px 55px rgba(30, 8, 66, 0.18) !important;
    }
    .p-card.max {
      background: linear-gradient(160deg, #2b105e 0%, #170c33 52%, #0a0717 100%) !important;
      border: 1.5px solid rgba(168, 85, 247, 0.62) !important;
      box-shadow: 0 26px 65px rgba(109, 40, 217, 0.28) !important;
    }
    .p-card .p-title-row h4 { color: #ffffff !important; font-size: 24px !important; font-weight: 800 !important; }
    .p-card .p-sub { color: #cbd5e1 !important; font-size: 13.5px !important; }
    .p-card .p-box {
      background: rgba(255, 255, 255, 0.06) !important;
      border: 1px solid rgba(255, 255, 255, 0.12) !important;
      border-radius: 14px !important;
    }
    .p-card.pro .p-credits { color: #fde047 !important; font-weight: 800 !important; }
    .p-card.max .p-credits { color: #c4b5fd !important; font-weight: 800 !important; }
    .p-card .p-duration { color: #e2e8f0 !important; }
    .p-card .p-tools-label { color: #a78bfa !important; }
    .p-card .p-tool-row { color: #ffffff !important; }
    .p-card .p-btn.pro {
      background: linear-gradient(135deg, #7c3aed, #6d28d9) !important;
      color: #ffffff !important;
      border: 0 !important;
      box-shadow: 0 10px 26px rgba(124, 58, 237, 0.38) !important;
    }
    .p-card .p-btn.max {
      background: linear-gradient(135deg, #a855f7, #7c3aed) !important;
      color: #ffffff !important;
      border: 0 !important;
      box-shadow: 0 12px 30px rgba(168, 85, 247, 0.45) !important;
    }
    .p-card .p-reseller-note {
      background: rgba(255, 255, 255, 0.05) !important;
      border: 1px solid rgba(255, 255, 255, 0.10) !important;
      color: #cbd5e1 !important;
    }
    .p-card .p-list li { color: #e2e8f0 !important; }

    /* About Card High-Contrast Developer Link */
    .about-section { color: #e4e4e7 !important; }
    .about-dev-box { background: rgba(139, 92, 246, 0.14) !important; border-color: rgba(167, 139, 250, 0.34) !important; }
    .about-dev-label { color: #c4b5fd !important; }
    .about-dev-name {
      color: #ffffff !important;
      font-size: 16px !important;
      font-weight: 800 !important;
      border-bottom: 1px dashed rgba(196, 181, 253, 0.65);
      padding-bottom: 1px;
      transition: color 0.15s ease, border-color 0.15s ease;
    }
    .about-dev-name:hover { color: #ddd6fe !important; border-color: #ffffff; }

    /* Professional Multi-Column SaaS Footer */
    .pf-site-footer {
      width: 100%;
      background: #07060d;
      color: #94a3b8;
      border-top: 1px solid rgba(255, 255, 255, 0.08);
      padding: 52px 24px 26px;
    }
    .pf-footer-inner { max-width: 1160px; margin: 0 auto; width: 100%; }
    .pf-footer-grid {
      display: grid;
      grid-template-columns: 1.6fr 1fr 1fr 1.1fr;
      gap: 32px;
      padding-bottom: 36px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }
    .pf-footer-brand p {
      margin-top: 12px;
      font-size: 13.5px;
      line-height: 1.6;
      color: #94a3b8;
      max-width: 300px;
    }
    .pf-footer-col h4 {
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #ffffff;
      margin-bottom: 14px;
    }
    .pf-footer-col ul { list-style: none; display: flex; flex-direction: column; gap: 10px; }
    .pf-footer-col a {
      color: #94a3b8;
      text-decoration: none;
      font-size: 13.5px;
      font-weight: 500;
      transition: color 0.15s ease;
    }
    .pf-footer-col a:hover { color: #c4b5fd; }
    .pf-footer-bottom {
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      padding-top: 22px;
      font-size: 13px;
      color: #71717a;
    }
    .pf-footer-dev {
      color: #94a3b8;
      font-size: 13px;
    }
    .pf-footer-dev a {
      color: #c4b5fd;
      font-weight: 700;
      text-decoration: none;
      border-bottom: 1px dashed rgba(196, 181, 253, 0.5);
      transition: color 0.15s ease;
    }
    .pf-footer-dev a:hover { color: #ffffff; }
    @media (max-width: 820px) {
      .pf-footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 26px; }
    }
    @media (max-width: 520px) {
      .pf-footer-grid { grid-template-columns: 1fr; gap: 24px; }
      .pf-footer-bottom { flex-direction: column; text-align: center; }
    }
  </style>
</head>
<body>
  <div id="bootLoader" class="boot-loader" role="status" aria-live="polite">
    <div class="boot-spinner" aria-hidden="true"></div>
    <div class="boot-label">Loading PakFlow...</div>
  </div>

  <!-- Shared Responsive Mobile Hamburger Drawer (Home / About / Pricing) -->
  <div id="mobileNavBackdrop" class="mob-drawer-backdrop" aria-hidden="true"></div>
  <aside id="mobileNavDrawer" class="mob-drawer" aria-label="Mobile Navigation" aria-hidden="true">
    <div class="mob-drawer-head">
      <a href="/" class="landing-logo">
        <img src="/flowbypak-logo.png" alt="PakFlow" />
        <span>PakFlow</span>
      </a>
      <button type="button" id="mobileNavCloseBtn" class="mob-drawer-close" aria-label="Close menu">&times;</button>
    </div>
    <nav class="mob-drawer-links">
      <a href="/" data-mob-link="/"><span>Home</span><span>&rarr;</span></a>
      <a href="/about" data-mob-link="/about"><span>About</span><span>&rarr;</span></a>
      <a href="/pricing" data-mob-link="/pricing"><span>Pricing</span><span>&rarr;</span></a>
      <a href="/privacy-policy" data-mob-link="/privacy-policy"><span>Privacy Policy</span><span>&rarr;</span></a>
    </nav>
    <div class="mob-drawer-actions">
      <a href="/login" class="btn-ghost" id="mobLoginBtn">Log In</a>
      <a href="/signup" class="btn-pill-sm" id="mobSignupBtn">Get Started Free</a>
    </div>
  </aside>

  <!-- Option B: Electric Purple 3D Hero + Alternating Editorial SaaS Landing Page -->
  <section id="landingView" class="landing-wrap hidden">
    <div class="landing-nav-outer">
      <nav class="landing-nav">
        <a href="/" class="landing-logo">
          <img src="/flowbypak-logo.png" alt="Flow" />
          <span>PakFlow</span>
        </a>
        <div class="landing-links">
          <a href="/" class="active" id="navHomeBtn">Home</a>
          <a href="/about" id="navAboutBtn">About</a>
          <a href="/pricing" id="navPricingBtn">Pricing</a>
          <a href="/privacy-policy">Privacy</a>
        </div>
        <div class="landing-actions">
          <button type="button" class="btn-ghost" id="navLoginBtn">Login</button>
          <button type="button" class="btn-pill-sm" id="navSignupBtn">Get Started</button>
        </div>
        <button type="button" class="mob-menu-btn" data-mob-toggle aria-label="Open navigation menu" aria-expanded="false">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
      </nav>
    </div>

    <!-- Electric Purple 3D Studio Hero -->
    <div class="pf-hero-band">
      <div class="pf-hero-inner">
        <div class="pf-hero-left">
          <div class="pf-hero-eyebrow">World-Class AI Video Platform</div>
          <h1 aria-label="AI VIDEO. UNLIMITED. CREATION."><span id="heroTypedText"></span><span class="tw-cursor"></span></h1>
          <p class="pf-hero-sub">
            Create cinema-grade AI videos powered by Google Flow Veo 3.1.
          </p>
          <div class="pf-hero-cta-row">
            <button type="button" class="pf-hero-white-btn" id="heroCreateBtn">
              Get Started Free <span>&#8599;</span>
            </button>
            <a href="/pricing" class="pf-hero-outline-btn">View Pricing</a>
          </div>
          <div class="pf-hero-stats-inline">
            <span>1-Click Sync</span>
            <span class="sep">|</span>
            <span>Veo 3.1 Ultra</span>
            <span class="sep">|</span>
            <span>45K+ Credits</span>
          </div>
        </div>
        <div class="pf-hero-right">
          <div class="pf-hero-robot-wrap">
            <img src="/assets/pakflow-hero-robot.png?v=3" alt="PakFlow 3D AI Cinema Studio" fetchpriority="high" />
          </div>
        </div>
      </div>
    </div>

    <!-- Live Preview Browser Window (Crisp White Editorial Band) -->
    <div class="pf-white-band">
      <div class="pf-container">
        <div class="pf-preview-label">Live Preview &mdash; No Signup Needed</div>
        <div class="pf-browser-frame">
          <div class="pf-browser-bar">
            <span class="pf-b-dot" style="background:#ef4444;"></span>
            <span class="pf-b-dot" style="background:#f59e0b;"></span>
            <span class="pf-b-dot" style="background:#10b981;"></span>
            <span class="pf-b-url">flowbypak.com/dashboard</span>
          </div>
          <div class="pf-browser-body">
            <div class="pf-mock-side">
              <div class="pf-mock-brand"><img src="/flowbypak-logo.png" alt="PakFlow" /><span>PakFlow</span></div>
              <div class="pf-mock-navitem active">&#9679; Studio Home</div>
              <div class="pf-mock-navitem">&#127916; Google Flow</div>
              <div class="pf-mock-navitem">&#128193; My Projects</div>
              <div class="pf-mock-navitem">&#128268; Extension</div>
              <div class="pf-mock-navitem">&#128179; Plan &amp; Billing</div>
            </div>
            <div class="pf-mock-main">
              <div class="pf-mock-top">
                <span style="font-size:13px;color:#cbd5e1;font-weight:600;">Welcome back, Creator</span>
                <div class="pf-mock-pills">
                  <span class="pf-mpill green">&#9679; Extension Connected</span>
                  <span class="pf-mpill purple">45,000 Credits &middot; Max</span>
                </div>
              </div>
              <div class="pf-mock-hero">
                <h4>Create Cinematic AI Videos in One Click.</h4>
                <p>Your PakFlow Chrome Extension automatically syncs your active session and backs up generated video projects.</p>
                <a href="/signup" class="btn-veo-start" style="text-decoration:none;padding:9px 18px;font-size:12.5px;">Launch Google Flow &rarr;</a>
              </div>
              <div class="pf-mock-cards">
                <div class="pf-mcard"><small>Active Plan</small><strong>Flow Ultra</strong></div>
                <div class="pf-mcard"><small>AI Credits</small><strong>45,000</strong></div>
                <div class="pf-mcard"><small>Cloud Vault</small><strong>Auto-Synced</strong></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Made with Veo Showcase (Deep Obsidian Dark Band) -->
    <div class="pf-dark-band">
    <div id="veoShowcase" class="veo-section" style="margin:0 auto;">
      <div class="veo-badge">🎬 Made with Veo</div>
      <div class="veo-head">
        <h2>One prompt.<span>Any style.</span></h2>
        <p>Every clip below was generated with the same models you get on PakFlow — no camera, no crew, no editing.</p>
      </div>
      <div class="veo-grid">
        <div class="veo-card">
          <img src="/videos/usecases/uc-baby-snack-review.jpg" alt="Snack Reviews" loading="lazy" />
          <span class="veo-label">Snack Reviews</span>
        </div>
        <div class="veo-card">
          <img src="/videos/usecases/uc-baby-podcast.jpg" alt="Baby Podcasts" loading="lazy" />
          <span class="veo-label">Baby Podcasts</span>
        </div>
        <div class="veo-card">
          <img src="/videos/usecases/uc-baby-animal-interview.jpg" alt="Animal Interviews" loading="lazy" />
          <span class="veo-label">Animal Interviews</span>
        </div>
        <div class="veo-card">
          <img src="/videos/usecases/uc-baby-chef.jpg" alt="Chef Skits" loading="lazy" />
          <span class="veo-label">Chef Skits</span>
        </div>
        <div class="veo-card">
          <img src="/videos/usecases/uc-baby-documentary.jpg" alt="Mini Documentaries" loading="lazy" />
          <span class="veo-label">Mini Documentaries</span>
        </div>
      </div>
      <div class="veo-cta-wrap">
        <button type="button" class="btn-veo-start" onclick="showAuth('signup')">
          Start creating free &rarr;
        </button>
      </div>
    </div>
    </div>

    <!-- Trust Stats (Crisp White Band) -->
    <div class="pf-white-band">
      <div class="pf-container">
        <div class="pf-sec-head">
          <div class="pf-sec-badge">&#9733; Trusted by creators worldwide</div>
          <h2>Real people. <span>Real creations.</span></h2>
          <p>Built for daily AI video makers who want instant speed, clean reliability, and zero billing headaches.</p>
        </div>
        <div class="pf-trust-grid">
          <div class="pf-trust-num"><strong>Veo 3.1</strong><span>Latest Video Engine</span></div>
          <div class="pf-trust-num"><strong>45,000</strong><span>Max Plan AI Credits</span></div>
          <div class="pf-trust-num"><strong>1-Click</strong><span>Chrome Auto-Sync</span></div>
          <div class="pf-trust-num"><strong>24/7</strong><span>Reseller Support</span></div>
        </div>
        <div class="pf-trust-pills">
          <span>&#128274; SSL Secured</span>
          <span>&#9889; Instant Access</span>
          <span>&#128179; No Credit Card Required</span>
          <span>&#128172; Real Human Support</span>
        </div>
      </div>
    </div>

    <!-- Built for Creators Bento (Soft Lilac Band) -->
    <div class="pf-lilac-band">
      <div class="pf-container">
        <div class="pf-sec-head" style="text-align:left;margin-bottom:28px;">
          <h2>Built for creators<br><span>at every level.</span></h2>
          <p style="margin:0;">Everything you need to generate AI video content at scale — without the technical friction.</p>
        </div>
        <div class="pf-bento-grid">
          <div class="pf-bento-card">
            <div class="pf-bento-ico">&#127916;</div>
            <h3>Veo 3.1 Video Studio</h3>
            <p>Access Google's latest Veo 3.1 model to generate crisp, cinematic AI videos in seconds.</p>
          </div>
          <div class="pf-bento-card">
            <div class="pf-bento-ico">&#128193;</div>
            <h3>Cloud Project Vault</h3>
            <p>Every generated video project is safely bookmarked in your private My Projects drawer.</p>
          </div>
          <div class="pf-bento-card">
            <div class="pf-bento-ico">&#128268;</div>
            <h3>1-Click Chrome Extension</h3>
            <p>Installs in seconds and bridges your PakFlow portal directly with Google Flow automatically.</p>
          </div>
          <div class="pf-bento-card">
            <div class="pf-bento-ico">&#10024;</div>
            <h3>Nano Banana Pro Ready</h3>
            <p>Unlock high-success video generation, multiple aspect ratios, and faster processing queues.</p>
          </div>
          <div class="pf-bento-card">
            <div class="pf-bento-ico">&#9989;</div>
            <h3>No Waitlist, No Ads</h3>
            <p>Sign up, verify your OTP, and start creating immediately — zero ads, zero complicated setup.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Skip the shoot. Keep the quality. (White Band) -->
    <div class="pf-white-band">
      <div class="pf-container">
        <div class="pf-sec-head">
          <h2>Skip the shoot.<br><span>Keep the quality.</span></h2>
          <p>What used to take a camera crew and a full week now takes a single prompt and a minute.</p>
        </div>
        <div class="pf-compare-grid">
          <div class="pf-cmp-card old">
            <span class="pf-cmp-tag">The old way</span>
            <ul class="pf-cmp-list">
              <li><span>&#10005;</span> Expensive per-video USD billing &amp; cards</li>
              <li><span>&#10005;</span> Hours of manual editing &amp; rendering</li>
              <li><span>&#10005;</span> Complicated VPN &amp; account errors</li>
              <li><span>&#10005;</span> Lost projects when sessions refresh</li>
              <li><span>&#10005;</span> Slow queues &amp; limited aspect ratios</li>
            </ul>
          </div>
          <div class="pf-cmp-card new">
            <span class="pf-cmp-tag">&#10022; The PakFlow way</span>
            <ul class="pf-cmp-list">
              <li><span>&#10003;</span> Type a prompt — get cinematic Veo 3.1 video</li>
              <li><span>&#10003;</span> One flat affordable plan via local resellers</li>
              <li><span>&#10003;</span> 1-Click Chrome Extension auto-sync</li>
              <li><span>&#10003;</span> Private Cloud Vault saves your video projects</li>
              <li><span>&#10003;</span> 16:9, 9:16, 1:1 &amp; parallel generations</li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Ready in 4 steps (Deep Obsidian Dark Band) -->
    <div class="pf-dark-band">
    <div class="home-sec" style="margin-bottom:0;">
      <div class="steps4-head">
        <h2>Ready in<span>4 steps.</span></h2>
        <p>No complicated setup. Install the Chrome Extension, connect your account, and start generating.</p>
      </div>
      <div class="steps4-grid">
        <div class="step4-card">
          <div class="step4-num">01</div>
          <div class="step4-body">
            <h3>Create an Account</h3>
            <p>Sign up with your email and verify with OTP to get instant access to your PakFlow portal.</p>
          </div>
        </div>
        <div class="step4-card">
          <div class="step4-num">02</div>
          <div class="step4-body">
            <h3>Install the Extension</h3>
            <p>Download or Add the PakFlow Extension from your dashboard in one click.</p>
          </div>
        </div>
        <div class="step4-card">
          <div class="step4-num">03</div>
          <div class="step4-body">
            <h3>Open Google Flow</h3>
            <p>Click Launch Google Flow. The Extension syncs your session automatically.</p>
          </div>
        </div>
        <div class="step4-card">
          <div class="step4-num">04</div>
          <div class="step4-body">
            <h3>Generate &amp; Enjoy</h3>
            <p>Type your prompt, hit generate, and create cinematic AI videos in seconds.</p>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Plans Section on Home (Crisp White Band — EXACT Existing Pricing Plans Content!) -->
    <div class="pf-white-band">
      <div class="pf-container">
        <div class="pf-sec-head">
          <div class="pf-sec-badge">&#10024; Authorized Reseller Distribution</div>
          <h2>2 plans. <span>Unlimited power.</span></h2>
          <p>Choose Pro (20 Days) or Max (30 Days) — contact your authorized reseller for instant activation.</p>
        </div>
        <div class="pf-home-plans-grid">
          <div class="p-card pro">
            <div class="p-title-row">
              <h4>PRO</h4>
              <span class="p-tag">43% OFF</span>
            </div>
            <div class="p-sub">For everyday AI creation</div>
            <div class="p-box">
              <div class="p-credits">✨ 25,000 AI Credits</div>
              <div class="p-duration">= Full Google Flow access for 20 days</div>
              <div class="p-tools-label">MAIN TOOLS</div>
              <div class="p-tool-row">
                <span>✓ Google Flow</span>
                <span>Full Access</span>
              </div>
            </div>
            <button type="button" class="p-btn pro" data-get-plan="Pro (20 Days)">Get Pro</button>
            <div class="p-reseller-note">Special discounts available via authorized resellers</div>
            <div class="p-noads">● No Ads</div>
            <ul class="p-list">
              <li>Full Google Flow Access</li>
              <li>Parallel generations: 2x Videos</li>
              <li>Higher Video Generation Success Rate</li>
              <li>1K Image Resolution</li>
              <li>16:9, 9:16 &amp; More Aspect Ratios</li>
              <li>Faster Processing Speed</li>
              <li>1 Device at a Time</li>
              <li>Priority Support</li>
              <li>Ad-Free Experience</li>
            </ul>
          </div>

          <div class="p-card max">
            <div class="p-title-row">
              <h4>MAX</h4>
              <span class="p-tag">50% OFF</span>
              <span class="p-tag pink">✦ BEST VALUE</span>
            </div>
            <div class="p-sub">For ambitious AI projects</div>
            <div class="p-box">
              <div class="p-credits">✨ 45,000 AI Credits</div>
              <div class="p-duration">= Full Google Flow access for 30 days</div>
              <div class="p-tools-label">MAIN TOOLS</div>
              <div class="p-tool-row">
                <span>✓ Google Flow</span>
                <span>Full Access</span>
              </div>
            </div>
            <button type="button" class="p-btn max" data-get-plan="Max (30 Days)">Get Max</button>
            <div class="p-reseller-note">Special discounts available via authorized resellers</div>
            <div class="p-noads">● No Ads</div>
            <ul class="p-list">
              <li>Full Google Flow Access (30 Days)</li>
              <li>Parallel generations: 3x Videos</li>
              <li>Highest Video Generation Success</li>
              <li>All Aspect Ratios (16:9, 4:3, 1:1, 3:4, 9:16)</li>
              <li>Fastest Processing + Premium Queue</li>
              <li>1 Device at a Time</li>
              <li>Early Access to New Features</li>
              <li>Priority Support</li>
              <li>Ad-Free Experience</li>
            </ul>
          </div>
        </div>
        <div style="text-align:center;margin-top:26px;">
          <a href="/pricing" class="btn-veo-start" style="text-decoration:none;">View full pricing details &rarr;</a>
        </div>
      </div>
    </div>

    <!-- Hear it from our clients (Deep Obsidian Dark Band) -->
    <div class="pf-dark-band">
    <div class="home-sec" style="margin-bottom:0;">
      <div class="clients-head">
        <h2>Hear it from our <span>clients</span></h2>
        <p>Real users sharing their <span>PakFlow</span> experience.</p>
      </div>
      <?php
        $leftReviews = [
          ['Recommended', 'Very happy with the tool. Does what it says — simple and smooth. PakFlow connected my session instantly and I generated my first video in under a minute.', 'VK', 'Vamsi K.'],
          ['Game changer for content creators', 'I was skeptical at first but the Extension is incredibly seamless. My workflow went from hours to minutes with PakFlow.', 'LM', 'Layla M.'],
          ['Best AI video tool I\'ve tried', 'I\'ve been using PakFlow for a few weeks. The support is great and I can always generate high-quality videos without any issues.', 'KR', 'Karl R.'],
          ['Smooth & fast rendering', 'On the Pro plan now and the generation flow is super reliable. No headaches, just pure AI video creation.', 'PS', 'Priya S.'],
        ];
        $rightReviews = [
          ['Fast & reliable', 'The generation speed is insane. Veo 3 through PakFlow is significantly better than anything else I\'ve tried. Worth every day.', 'LA', 'Leonie A.'],
          ['Top-notch creator experience', 'The best AI video solution for creators. I\'ve been a PakFlow user for months — the team always delivers updates and the support is top notch.', 'HR', 'Hassan R.'],
          ['Blown away by the quality', 'I showed my clients videos I generated with PakFlow and they thought they were shot with a real drone. Absolutely unreal quality.', 'MT', 'Mia T.'],
          ['Worth every penny', 'One-click extension connection and super responsive reseller activation. Makes daily content production effortless.', 'OF', 'Omar F.'],
        ];
      ?>
      <div class="clients-marquee-wrap">
        <div class="clients-col">
          <div class="clients-track">
            <?php foreach (array_merge($leftReviews, $leftReviews) as $rev): ?>
            <div class="client-card">
              <div>
                <h4><?= htmlspecialchars($rev[0]) ?></h4>
                <p><?= htmlspecialchars($rev[1]) ?></p>
              </div>
              <div class="client-foot">
                <div class="client-user"><span class="client-avatar"><?= htmlspecialchars($rev[2]) ?></span><span class="client-name"><?= htmlspecialchars($rev[3]) ?></span></div>
                <span class="client-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="clients-col col-slow">
          <div class="clients-track">
            <?php foreach (array_merge($rightReviews, $rightReviews) as $rev): ?>
            <div class="client-card">
              <div>
                <h4><?= htmlspecialchars($rev[0]) ?></h4>
                <p><?= htmlspecialchars($rev[1]) ?></p>
              </div>
              <div class="client-foot">
                <div class="client-user"><span class="client-avatar"><?= htmlspecialchars($rev[2]) ?></span><span class="client-name"><?= htmlspecialchars($rev[3]) ?></span></div>
                <span class="client-stars">&#9733;&#9733;&#9733;&#9733;&#9733;</span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
    </div>

    <!-- Questions? Answered. (White Accordion Section) -->
    <div class="qa-band">
      <div class="qa-inner">
        <div class="qa-head">
          <h2>Questions?<span>Answered.</span></h2>
        </div>
        <div class="qa-list">
          <details class="qa-item">
            <summary>
              <span>Do I need any video editing experience?</span>
              <svg class="qa-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="qa-ans">Not at all. Just type a prompt describing your scene in plain English and Google Flow generates cinematic videos automatically.</div>
          </details>
          <details class="qa-item">
            <summary>
              <span>How do I upgrade to Pro or Max?</span>
              <svg class="qa-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="qa-ans">Check our Pro (20 Days) and Max (30 Days) plans on the Pricing page and contact your authorized reseller for instant activation.</div>
          </details>
          <details class="qa-item">
            <summary>
              <span>Which AI models do I get access to?</span>
              <svg class="qa-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="qa-ans">PakFlow gives you direct access to Google Flow powered by Veo 3.1 and Nano Banana Pro with 45,000 AI Credits on Pro and Max plans.</div>
          </details>
          <details class="qa-item">
            <summary>
              <span>Can I use PakFlow on multiple devices at once?</span>
              <svg class="qa-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="qa-ans">Each account supports 1 active device at a time. Signing in on a new device automatically signs out the previous session.</div>
          </details>
          <details class="qa-item">
            <summary>
              <span>How fast can I start after signing up?</span>
              <svg class="qa-chevron" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
            </summary>
            <div class="qa-ans">Immediately. Verify your email OTP, download the Chrome Extension from your dashboard, and launch Google Flow in one click.</div>
          </details>
        </div>
      </div>
    </div>

    <!-- Bottom Purple CTA Banner -->
    <div class="purple-cta-band">
      <div class="purple-cta-inner">
        <div class="purple-cta-text">
          <h2>Start generating<br>AI videos today.</h2>
          <p>No credit card needed. Just install, connect, and create.</p>
        </div>
        <div class="purple-cta-action">
          <img src="/flowbypak-logo.png" alt="PakFlow" />
          <a href="/signup" class="btn-purple-white">Get Started Free</a>
        </div>
      </div>
    </div>

    <footer class="pf-site-footer">
      <div class="pf-footer-inner">
        <div class="pf-footer-grid">
          <div class="pf-footer-brand">
            <a href="/" class="landing-logo">
              <img src="/flowbypak-logo.png" alt="PakFlow" />
              <span>PakFlow</span>
            </a>
            <p>AI Video Creation Platform giving creators direct 1-click access to Google Flow (Veo 3.1) &amp; Cloud Project Vault.</p>
          </div>
          <div class="pf-footer-col">
            <h4>Pages</h4>
            <ul>
              <li><a href="/">Home</a></li>
              <li><a href="/about">About</a></li>
              <li><a href="/pricing">Pricing</a></li>
              <li><a href="/dashboard">Dashboard</a></li>
            </ul>
          </div>
          <div class="pf-footer-col">
            <h4>Account</h4>
            <ul>
              <li><a href="/login">Sign In</a></li>
              <li><a href="/signup">Get Started Free</a></li>
              <li><a href="/privacy-policy">Privacy Policy</a></li>
            </ul>
          </div>
          <div class="pf-footer-col">
            <h4>Support</h4>
            <ul>
              <li><a href="mailto:support@flowbypak.com">support@flowbypak.com</a></li>
              <li><a href="/pricing">Authorized Resellers</a></li>
            </ul>
          </div>
        </div>
        <div class="pf-footer-bottom">
          <span>&copy; 2026 PakFlow Studio. All rights reserved.</span>
          <span class="pf-footer-dev">Developed by <a href="https://wa.me/923430641457?text=I%20want%20a%20website%20for%20my%20business" target="_blank" rel="noopener noreferrer">Aivrasol Softwares</a></span>
        </div>
      </div>
    </footer>
  </section>

  <!-- Dedicated About Page (/about) — Option B Matching Theme -->
  <section id="aboutView" class="landing-wrap hidden">
    <div class="landing-nav-outer">
      <nav class="landing-nav">
        <a href="/" class="landing-logo">
          <img src="/flowbypak-logo.png" alt="Flow" />
          <span>PakFlow</span>
        </a>
        <div class="landing-links">
          <a href="/">Home</a>
          <a href="/about" class="active">About</a>
          <a href="/pricing">Pricing</a>
          <a href="/privacy-policy">Privacy</a>
        </div>
        <div class="landing-actions">
          <a href="/login" class="btn-ghost" style="text-decoration:none;">Login</a>
          <a href="/signup" class="btn-pill-sm" style="text-decoration:none;">Get Started</a>
        </div>
        <button type="button" class="mob-menu-btn" data-mob-toggle aria-label="Open navigation menu" aria-expanded="false">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
      </nav>
    </div>

    <div class="pf-subhero">
      <div class="pf-hero-eyebrow">&#10022; About PakFlow Studio</div>
      <h1>Built for Modern AI Creators.</h1>
      <p>Direct, effortless access to Google Flow (Veo 3.1) &amp; Nano Banana Pro with our 1-Click Chrome Extension and Cloud Project Vault.</p>
    </div>

    <div class="pf-white-band">
      <div class="pf-container" style="max-width:880px;">
        <div id="aboutSection" class="about-section" style="margin:0 auto;background:#0b0916;border-color:rgba(139,92,246,0.28);box-shadow:0 24px 60px rgba(30,8,66,0.16);">
          <div class="about-badge">✦ About PakFlow</div>
          <h2>What is <span>PakFlow?</span></h2>
          <p>
            PakFlow, developed by <a href="https://wa.me/923430641457?text=I%20want%20a%20website%20for%20my%20business" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:none;border-bottom:1px dashed rgba(196,181,253,0.45);"><strong>Aivrasol Softwares</strong></a>, is a Pakistan-based platform that gives creators direct access to <strong>Google Flow (Veo 3.1)</strong> with our <strong>1-Click Extension</strong> — without the complicated billing setup, expensive per-video costs, or technical hassle.
          </p>
          <p style="margin-top:-12px;margin-bottom:26px;font-size:14px;">
            Need help? Contact Support: <a href="mailto:support@flowbypak.com" style="color:#c4b5fd;font-weight:600;text-decoration:none;">support@flowbypak.com</a>
          </p>
          <div class="about-btns">
            <a href="/signup" class="btn-veo-start" style="text-decoration:none;">Try For Free &rarr;</a>
            <a href="/pricing" class="btn-flow-sec" style="text-decoration:none;">View Pricing</a>
          </div>
          <div class="about-dev-box" style="justify-content:center;text-align:center;">
            <div class="about-dev-info" style="align-items:center;">
              <span class="about-dev-label">Designed &amp; Engineered By</span>
              <a class="about-dev-name" href="https://wa.me/923430641457?text=I%20want%20a%20website%20for%20my%20business" target="_blank" rel="noopener noreferrer">Aivrasol Softwares</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom Purple CTA Banner -->
    <div class="purple-cta-band">
      <div class="purple-cta-inner">
        <div class="purple-cta-text">
          <h2>Start generating<br>AI videos today.</h2>
          <p>No credit card needed. Just install, connect, and create.</p>
        </div>
        <div class="purple-cta-action">
          <img src="/flowbypak-logo.png" alt="PakFlow" />
          <a href="/signup" class="btn-purple-white">Get Started Free</a>
        </div>
      </div>
    </div>

    <footer class="pf-site-footer">
      <div class="pf-footer-inner">
        <div class="pf-footer-grid">
          <div class="pf-footer-brand">
            <a href="/" class="landing-logo">
              <img src="/flowbypak-logo.png" alt="PakFlow" />
              <span>PakFlow</span>
            </a>
            <p>AI Video Creation Platform giving creators direct 1-click access to Google Flow (Veo 3.1) &amp; Cloud Project Vault.</p>
          </div>
          <div class="pf-footer-col">
            <h4>Pages</h4>
            <ul>
              <li><a href="/">Home</a></li>
              <li><a href="/about">About</a></li>
              <li><a href="/pricing">Pricing</a></li>
              <li><a href="/dashboard">Dashboard</a></li>
            </ul>
          </div>
          <div class="pf-footer-col">
            <h4>Account</h4>
            <ul>
              <li><a href="/login">Sign In</a></li>
              <li><a href="/signup">Get Started Free</a></li>
              <li><a href="/privacy-policy">Privacy Policy</a></li>
            </ul>
          </div>
          <div class="pf-footer-col">
            <h4>Support</h4>
            <ul>
              <li><a href="mailto:support@flowbypak.com">support@flowbypak.com</a></li>
              <li><a href="/pricing">Authorized Resellers</a></li>
            </ul>
          </div>
        </div>
        <div class="pf-footer-bottom">
          <span>&copy; 2026 PakFlow Studio. All rights reserved.</span>
          <span class="pf-footer-dev">Developed by <a href="https://wa.me/923430641457?text=I%20want%20a%20website%20for%20my%20business" target="_blank" rel="noopener noreferrer">Aivrasol Softwares</a></span>
        </div>
      </div>
    </footer>
  </section>

  <!-- Direct Login / Signup Page -->
  <section id="authView" class="auth-wrap hidden">
    <div class="auth-marquee-bg" aria-hidden="true">
      <?php
        $marqueeLine = str_repeat('FlowByPak &middot; ', 12);
        for ($r = 0; $r < 12; $r++):
      ?>
      <div class="auth-marquee-row">
        <div class="auth-marquee-track">
          <span><?= $marqueeLine ?></span>
          <span><?= $marqueeLine ?></span>
        </div>
      </div>
      <?php endfor; ?>
    </div>
    <div class="auth-center-col">
      <div class="auth-card">
        <div class="auth-split-left">
          <div class="auth-split-brand">
            <img src="/flowbypak-logo.png" alt="PakFlow" />
            <span>PakFlow Studio</span>
          </div>
          <div class="auth-split-copy">
            <h2>Create Cinematic <em>AI Videos</em> in Seconds.</h2>
            <p>Access Google Flow (Veo 3.1) and Nano Banana Pro directly from one clean workspace.</p>
            <div class="auth-split-list">
              <div class="auth-split-item"><span class="auth-split-dot">&#9889;</span><span>45,000 AI Credits on Pro &amp; Max plans</span></div>
              <div class="auth-split-item"><span class="auth-split-dot">&#128268;</span><span>1-Click Chrome Extension session sync</span></div>
              <div class="auth-split-item"><span class="auth-split-dot">&#128737;</span><span>OTP verified &amp; 1-device protected</span></div>
            </div>
          </div>
          <div class="auth-split-foot">Need help? support@flowbypak.com</div>
        </div>
        <div class="auth-split-right">
        <div class="auth-brand">
          <img id="authLogoImg" src="/flowbypak-logo.png" alt="PakFlow" />
          <div id="authForgotIcon" class="auth-forgot-icon hidden" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
          </div>
          <div id="authModePill" class="auth-mode-pill">&#10022; Google Flow Studio</div>
          <h1 id="authHeading">PakFlow</h1>
          <span id="authSubheading">Sign in to your account</span>
          <div id="authPerks" class="auth-perks hidden">
            <span><b>&#10003;</b> Basic Plan</span>
            <span><b>&#10003;</b> No card needed</span>
            <span><b>&#10003;</b> Instant signup</span>
          </div>
        </div>

        <div id="authAlert" class="auth-alert hidden"></div>

        <form id="authForm">
          <div class="field hidden" id="nameField">
            <label for="authName">Full Name</label>
            <input id="authName" name="full_name" type="text" placeholder="Your full name" autocomplete="name" />
          </div>
          <div class="field">
            <label for="authEmail">Email</label>
            <input id="authEmail" name="email" type="text" placeholder="you@example.com" required autocomplete="email" />
          </div>
          <div class="field">
            <label for="authPassword">Password</label>
            <div class="input-wrap">
              <input id="authPassword" class="has-toggle" type="password" placeholder="••••••••" required autocomplete="current-password" />
              <button type="button" class="pass-toggle" id="togglePassBtn" title="Show/Hide Password" aria-label="Show password">&#128065;</button>
            </div>
          </div>
          <button type="submit" class="btn-primary" id="authSubmitBtn">Sign In</button>
          <div id="forgotRow" style="text-align:center;margin-top:16px;">
            <button type="button" id="openForgotBtn" style="background:transparent;border:0;color:#c4b5fd;font-size:13px;font-weight:700;cursor:pointer;">Forgot your password?</button>
          </div>
        </form>

        <!-- Verify Signup Email Flow -->
        <div id="verifySignupBox" class="hidden">
          <form id="verifySignupForm">
            <div class="field">
              <label for="signupVerifyCode">6-Digit Verification Code</label>
              <input id="signupVerifyCode" type="text" maxlength="6" placeholder="123456" style="letter-spacing:6px;font-weight:800;text-align:center;font-size:18px;" required />
            </div>
            <button type="submit" class="btn-primary" id="verifySignupBtn">Verify &amp; Create Account</button>
          </form>
          <div class="auth-footer-switch">
            Didn't receive the code? <button type="button" id="resendSignupCodeBtn">Resend Code</button>
          </div>
        </div>

        <!-- Forgot Password Flow -->
        <div id="forgotBox" class="hidden">
          <form id="forgotStep1Form">
            <div class="field">
              <label for="forgotEmail">Email</label>
              <input id="forgotEmail" type="email" placeholder="you@example.com" required />
            </div>
            <button type="submit" class="btn-primary" id="sendCodeBtn">Send Reset Link</button>
          </form>

          <form id="forgotStep2Form" class="hidden" style="margin-top:16px;">
            <div class="field">
              <label for="resetNewPass">New Password</label>
              <input id="resetNewPass" type="password" placeholder="Min 4 characters" required />
            </div>
            <button type="submit" class="btn-primary" id="resetPassBtn">Reset Password</button>
          </form>

          <div class="auth-footer-switch">
            <button type="button" id="backToLoginBtn">&larr; Back to sign in</button>
          </div>
        </div>

        <div class="auth-footer-switch" id="authFooterSwitch">
          Don't have an account? <button type="button" id="footerSwitchBtn">Sign up free</button>
        </div>
        </div>
      </div>

      <button type="button" class="auth-back-below" id="authBackBtn">&larr; Back to home</button>
      <div class="auth-trust-note">&#128274; Protected by 1-device session sync</div>
    </div>
  </section>

  <!-- Redesigned User Dashboard (UI/UX Pro Max) -->
  <section id="dashView" class="dash-wrap hidden">
    <header class="topbar">
      <a href="/dashboard" class="topbar-brand">
        <img src="/flowbypak-logo.png" alt="PakFlow" />
        <span>PakFlow Studio</span>
      </a>
      <div class="topbar-right">
        <a class="topbar-notice" id="extReleaseLink" href="/downloads/latest" download="<?= $__pfExtZipName ?>">
          <span class="topbar-notice-dot" aria-hidden="true"></span>
          <span id="extReleaseLabel">PakFlow v<?= $__pfExtSafeVer ?> is live now</span>
          <span class="topbar-notice-cta">Download &rarr;</span>
        </a>
        <div class="plan-pill" id="planPill">Flow Ultra</div>
        <button type="button" class="avatar-btn" id="avatarBtn" aria-label="Account menu">P</button>
      </div>

      <div class="dropdown hidden" id="accountDropdown">
        <div class="dd-head">
          <div class="dd-name" id="ddName">PAKTOOLS</div>
          <div class="dd-email" id="ddEmail">user@pakflow.com</div>
        </div>
        <div class="dd-credits">
          <div class="dd-credits-row">
            <span class="dd-credits-label">AI Credits</span>
            <span class="dd-credits-val" id="ddCreditsVal">45,000 <span>/ 45,000</span></span>
          </div>
          <div class="progress-track"><div class="progress-fill" id="ddProgress"></div></div>
          <div class="dd-days" id="ddDays">30 days left in plan</div>
        </div>
        <div class="dd-menu">
          <button type="button" class="dd-item" data-modal="settings">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>Account Settings</span>
          </button>
          <button type="button" class="dd-item" data-modal="plans">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
            <span>Plan &amp; Pricing</span>
          </button>
          <button type="button" class="dd-item" data-modal="devices">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>My Devices</span>
          </button>
          <a class="dd-item" id="ddExtLink" href="/downloads/latest" download="<?= $__pfExtZipName ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <span id="ddExtText">Get Extension (v<?= $__pfExtSafeVer ?>)</span>
          </a>
          <button type="button" class="dd-item signout" id="signOutBtn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
            <span>Sign Out</span>
          </button>
        </div>
      </div>
    </header>

    <main class="dash-main">
      <!-- KPI Summary Strip -->
      <div class="dash-stats-grid">
        <div class="dash-stat-card">
          <div class="dash-stat-top">
            <span>Active Subscription</span>
            <span style="display:inline-flex;align-items:center;gap:5px;color:#34d399;font-size:11.5px;font-weight:700;"><span class="topbar-notice-dot"></span>Connected</span>
          </div>
          <div class="dash-stat-val" id="dashPlanCardVal">Flow Ultra</div>
          <div class="dash-stat-sub" id="dashPlanCardSub">Google Flow (Veo 3.1) Studio Access</div>
        </div>
        <div class="dash-stat-card">
          <div class="dash-stat-top">
            <span>AI Credits Available</span>
            <span style="font-size:11.5px;color:#93c5fd;font-weight:700;">Auto-Sync</span>
          </div>
          <div class="dash-stat-val" id="dashCreditsCardVal">45,000</div>
          <div class="progress-track" style="margin-top:2px;"><div class="progress-fill" id="dashCreditsBar"></div></div>
        </div>
        <div class="dash-stat-card">
          <div class="dash-stat-top">
            <span>Validity Remaining</span>
            <button type="button" data-modal="plans" style="background:transparent;border:0;color:#60a5fa;font-size:11.5px;font-weight:700;cursor:pointer;">Upgrade &rarr;</button>
          </div>
          <div class="dash-stat-val" id="dashDaysCardVal">30 Days</div>
          <div class="dash-stat-sub" id="dashEmailCardSub">1 active device session</div>
        </div>
      </div>

      <!-- Studio Launch Hero Card -->
      <div class="hero-banner" aria-label="Google Flow AI Video Studio">
        <div class="hero-banner-content">
          <div class="hero-eyebrow"><span class="topbar-notice-dot"></span>Google Flow · Veo 3.1 Ready</div>
          <h2>Create Cinematic AI Videos in One Click.</h2>
          <p>Your PakFlow Chrome Extension automatically syncs your active session. Keep this portal open and launch Google Flow directly below.</p>
          <div class="cta-wrap">
            <a href="https://flow.google.com" target="_blank" rel="noopener noreferrer" class="btn-generate" id="generateVideoBtn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><polygon points="6 4 20 12 6 20 6 4"/></svg>
              <span>Launch Google Flow</span>
            </a>
            <a href="/downloads/latest" download="<?= $__pfExtZipName ?>" class="btn-ext-sec" id="heroExtDownloadBtn">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              <span id="heroExtBtnText">Download Extension (v<?= $__pfExtSafeVer ?>)</span>
            </a>
            <a href="/downloads/<?= $__pfExtZipName ?>" download="<?= $__pfExtZipName ?>" id="heroZipFallbackLink" style="display:none;align-items:center;gap:5px;font-size:12px;font-weight:600;color:#94a3b8;text-decoration:none;padding:6px 10px;border-radius:8px;border:1px solid rgba(255,255,255,0.08);background:rgba(255,255,255,0.03);">Manual ZIP (Mobile)</a>
          </div>
        </div>
      </div>

      <!-- 3-Step Quick Guide -->
      <div class="dash-steps-grid">
        <div class="dash-step">
          <div class="dash-step-num">01</div>
          <div>
            <h4 id="dashStep1Title">Install Latest Extension</h4>
            <p id="dashStep1Desc">Download the Chrome extension above and load it once in Chrome Extensions.</p>
          </div>
        </div>
        <div class="dash-step">
          <div class="dash-step-num">02</div>
          <div>
            <h4>Keep Portal Connected</h4>
            <p>Your account syncs automatically with the extension for instant session switching.</p>
          </div>
        </div>
        <div class="dash-step">
          <div class="dash-step-num">03</div>
          <div>
            <h4>Generate in Google Flow</h4>
            <p>Click Launch Google Flow and start creating Veo 3.1 videos right away.</p>
          </div>
        </div>
      </div>
    </main>
  </section>

  <!-- Settings Page (Change Password, Change Email, Delete Account) -->
  <section id="settingsView" class="settings-wrap hidden">
    <div class="settings-container">
      <div class="settings-header">
        <div>
          <h2>Account Settings</h2>
          <p class="settings-header-sub">Manage your password, email address, and account security.</p>
        </div>
        <button type="button" class="btn-ghost" id="settingsBackBtn">&larr; Back to Dashboard</button>
      </div>

      <div id="settingsAlert" class="auth-alert hidden"></div>

      <div class="settings-grid">
        <!-- Change Password -->
        <div class="settings-card">
          <span class="settings-card-badge">Security</span>
          <h3>Change Password</h3>
          <p class="sub">Keep your account protected with a strong password.</p>
          <form id="changePasswordForm">
            <div class="field">
              <label for="curPass">Current Password</label>
              <input id="curPass" type="password" placeholder="Enter current password" />
            </div>
            <div class="field">
              <label for="newPass">New Password</label>
              <input id="newPass" type="password" placeholder="Enter new password (min 4 chars)" required />
            </div>
            <button type="submit" class="settings-submit-btn">Update Password</button>
          </form>
        </div>

        <!-- Change Email -->
        <div class="settings-card email-card">
          <span class="settings-card-badge emerald">Identity</span>
          <h3>Change Email</h3>
          <p class="sub">Current email: <strong id="settingsCurrentEmail">-</strong></p>
          <form id="changeEmailForm">
            <div class="field">
              <label for="newEmailInput">New Email Address</label>
              <input id="newEmailInput" type="email" placeholder="newemail@example.com" required />
            </div>
            <button type="submit" class="settings-submit-btn emerald">Update Email</button>
          </form>
        </div>
      </div>

      <!-- Delete Account -->
      <div class="settings-card danger">
        <div>
          <h3>Danger Zone &mdash; Delete Account</h3>
          <p class="sub">Permanently delete your account and sign out. This action cannot be undone.</p>
        </div>
        <button type="button" class="btn-danger" id="deleteAccountBtn">Delete My Account</button>
      </div>
    </div>
  </section>

  <!-- Plan & Pricing Page (2 Plans: Pro 20 Days & Max 30 Days - Google Flow Only) -->
  <section id="pricingView" class="pricing-wrap hidden">
    <div class="landing-nav-outer">
      <nav class="landing-nav">
        <a href="/" class="landing-logo">
          <img src="/flowbypak-logo.png" alt="Flow" />
          <span>PakFlow</span>
        </a>
        <div class="landing-links">
          <a href="/">Home</a>
          <a href="/about">About</a>
          <a href="/pricing" class="active">Pricing</a>
          <a href="/privacy-policy">Privacy</a>
        </div>
        <div class="landing-actions">
          <a href="/login" class="btn-ghost" style="text-decoration:none;">Login</a>
          <a href="/signup" class="btn-pill-sm" style="text-decoration:none;">Get Started</a>
        </div>
        <button type="button" class="mob-menu-btn" data-mob-toggle aria-label="Open navigation menu" aria-expanded="false">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
      </nav>
    </div>

    <div class="pf-subhero">
      <div class="pf-hero-eyebrow">&#10024; Authorized Reseller Distribution</div>
      <h1>Simple Plans. Unlimited Power.</h1>
      <p>Contact your authorized reseller for instant activation &amp; local pricing on Pro (20 Days) and Max (30 Days) plans.</p>
    </div>

    <div class="pricing-body">
      <div class="pricing-shell">
        <div class="pricing-top">
          <h3>Plan &amp; Pricing</h3>
          <button type="button" class="btn-ghost" id="pricingBackBtn">&larr; Back to Dashboard</button>
        </div>
      <div class="pricing-grid">
        <!-- PRO PLAN -->
        <div class="p-card pro">
          <div class="p-title-row">
            <h4>PRO</h4>
            <span class="p-tag">43% OFF</span>
          </div>
          <div class="p-sub">For everyday AI creation</div>
          <div class="p-box">
            <div class="p-credits">✨ 25,000 AI Credits</div>
            <div class="p-duration">= Full Google Flow access for 20 days</div>
            <div class="p-tools-label">MAIN TOOLS</div>
            <div class="p-tool-row">
              <span>✓ Google Flow</span>
              <span>Full Access</span>
            </div>
          </div>
          <button type="button" class="p-btn pro" data-get-plan="Pro (20 Days)">Get Pro</button>
          <div class="p-reseller-note">Special discounts available via authorized resellers</div>
          <div class="p-noads">● No Ads</div>
          <ul class="p-list">
            <li>Full Google Flow Access</li>
            <li>Parallel generations: 2x Videos</li>
            <li>Higher Video Generation Success Rate</li>
            <li>1K Image Resolution</li>
            <li>16:9, 9:16 &amp; More Aspect Ratios</li>
            <li>Faster Processing Speed</li>
            <li>1 Device at a Time</li>
            <li>Priority Support</li>
            <li>Ad-Free Experience</li>
          </ul>
        </div>

        <!-- MAX PLAN -->
        <div class="p-card max">
          <div class="p-title-row">
            <h4>MAX</h4>
            <span class="p-tag">50% OFF</span>
            <span class="p-tag pink">✦ BEST VALUE</span>
          </div>
          <div class="p-sub">For ambitious AI projects</div>
          <div class="p-box">
            <div class="p-credits">✨ 45,000 AI Credits</div>
            <div class="p-duration">= Full Google Flow access for 30 days</div>
            <div class="p-tools-label">MAIN TOOLS</div>
            <div class="p-tool-row">
              <span>✓ Google Flow</span>
              <span>Full Access</span>
            </div>
          </div>
          <button type="button" class="p-btn max" data-get-plan="Max (30 Days)">Get Max</button>
          <div class="p-reseller-note">Special discounts available via authorized resellers</div>
          <div class="p-noads">● No Ads</div>
          <ul class="p-list">
            <li>Full Google Flow Access (30 Days)</li>
            <li>Parallel generations: 3x Videos</li>
            <li>Highest Video Generation Success</li>
            <li>All Aspect Ratios (16:9, 4:3, 1:1, 3:4, 9:16)</li>
            <li>Fastest Processing + Premium Queue</li>
            <li>1 Device at a Time</li>
            <li>Early Access to New Features</li>
            <li>Priority Support</li>
            <li>Ad-Free Experience</li>
          </ul>
        </div>
      </div>
    </div>
    </div>

    <footer class="pf-site-footer">
      <div class="pf-footer-inner">
        <div class="pf-footer-grid">
          <div class="pf-footer-brand">
            <a href="/" class="landing-logo">
              <img src="/flowbypak-logo.png" alt="PakFlow" />
              <span>PakFlow</span>
            </a>
            <p>AI Video Creation Platform giving creators direct 1-click access to Google Flow (Veo 3.1) &amp; Cloud Project Vault.</p>
          </div>
          <div class="pf-footer-col">
            <h4>Pages</h4>
            <ul>
              <li><a href="/">Home</a></li>
              <li><a href="/about">About</a></li>
              <li><a href="/pricing">Pricing</a></li>
              <li><a href="/dashboard">Dashboard</a></li>
            </ul>
          </div>
          <div class="pf-footer-col">
            <h4>Account</h4>
            <ul>
              <li><a href="/login">Sign In</a></li>
              <li><a href="/signup">Get Started Free</a></li>
              <li><a href="/privacy-policy">Privacy Policy</a></li>
            </ul>
          </div>
          <div class="pf-footer-col">
            <h4>Support</h4>
            <ul>
              <li><a href="mailto:support@flowbypak.com">support@flowbypak.com</a></li>
              <li><a href="/pricing">Authorized Resellers</a></li>
            </ul>
          </div>
        </div>
        <div class="pf-footer-bottom">
          <span>&copy; 2026 PakFlow Studio. All rights reserved.</span>
          <span class="pf-footer-dev">Developed by <a href="https://wa.me/923430641457?text=I%20want%20a%20website%20for%20my%20business" target="_blank" rel="noopener noreferrer">Aivrasol Softwares</a></span>
        </div>
      </div>
    </footer>
  </section>

  <!-- Corner Toast -->
  <div id="cornerToast" class="corner-toast hidden" role="status" aria-live="polite">
    <div class="corner-toast-body">
      <div class="corner-toast-title" id="cornerToastTitle">Contact Your Reseller</div>
      <div class="corner-toast-msg" id="cornerToastMsg"></div>
    </div>
    <button type="button" class="corner-toast-close" id="cornerToastClose" aria-label="Close">&times;</button>
  </div>

  <!-- Floating WhatsApp Support Button (User Dashboard / Reseller-aware) -->
  <a id="waFloatBtn" class="wa-float-btn hidden" href="#" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp Support">
    <svg viewBox="0 0 32 32" fill="currentColor" aria-hidden="true"><path d="M16.004 3C8.834 3 3 8.834 3 16.004c0 2.29.598 4.525 1.734 6.494L3 29l6.678-1.752A12.94 12.94 0 0 0 16.004 29C23.174 29 29 23.166 29 16.004 29 8.834 23.174 3 16.004 3zm7.584 18.374c-.317.893-1.857 1.706-2.556 1.81-.65.098-1.472.14-2.376-.148-.548-.175-1.25-.408-2.148-.797-3.778-1.631-6.246-5.436-6.434-5.687-.188-.251-1.536-2.044-1.536-3.898 0-1.855.972-2.767 1.317-3.144.345-.377.752-.472 1.003-.472.251 0 .502.002.721.013.232.012.543-.088.849.647.314.752 1.066 2.607 1.16 2.796.094.188.157.408.031.659-.125.251-.188.408-.377.628-.188.22-.396.49-.565.659-.188.188-.384.392-.165.768.22.377.977 1.612 2.099 2.612 1.442 1.286 2.658 1.685 3.035 1.873.377.188.596.157.816-.094.22-.251.941-1.098 1.192-1.475.251-.377.502-.314.847-.188.345.125 2.196 1.035 2.573 1.224.377.188.628.282.722.439.094.157.094.91-.223 1.803z"/></svg>
    <span class="wa-float-label" id="waFloatLabel">WhatsApp Support</span>
  </a>

  <!-- Existing Session Modal (Login 1-Click Logout Existing Device) -->
  <div id="existingSessionModal" class="modal-backdrop hidden" role="dialog" aria-modal="true" aria-labelledby="existingSessionTitle">
    <div class="modal-card session-modal-card">
      <div class="session-modal-icon" aria-hidden="true">&#128274;</div>
      <h3 id="existingSessionTitle" class="session-modal-title">Already Logged In</h3>
      <p id="existingSessionMsg" class="session-modal-desc">This account is active on another device. Click below to sign out the existing session and login here.</p>
      <div class="session-modal-actions">
        <button type="button" class="btn-kick-signin" id="kickExistingSessionBtn">Logout Existing Session &amp; Login</button>
        <button type="button" class="btn-kick-cancel" id="cancelExistingSessionBtn">Cancel</button>
      </div>
    </div>
  </div>

  <!-- Info Modal -->
  <div id="infoModal" class="modal-backdrop hidden">
    <div class="modal-card">
      <div class="modal-head">
        <h3 id="modalTitle">Details</h3>
        <button type="button" class="modal-close" id="modalClose">&times;</button>
      </div>
      <div class="modal-body" id="modalBody"></div>
    </div>
  </div>

  <script>
    (function () {
      const landingView = document.getElementById('landingView');
      const aboutView = document.getElementById('aboutView');
      const authView = document.getElementById('authView');
      const dashView = document.getElementById('dashView');
      const settingsView = document.getElementById('settingsView');
      const settingsBackBtn = document.getElementById('settingsBackBtn');
      const settingsAlert = document.getElementById('settingsAlert');
      const changePasswordForm = document.getElementById('changePasswordForm');
      const changeEmailForm = document.getElementById('changeEmailForm');
      const deleteAccountBtn = document.getElementById('deleteAccountBtn');
      const heroCreateBtn = document.getElementById('heroCreateBtn');
      const navLoginBtn = document.getElementById('navLoginBtn');
      const navSignupBtn = document.getElementById('navSignupBtn');
      const authBackBtn = document.getElementById('authBackBtn');
      const tabLogin = document.getElementById('tabLogin');
      const tabSignup = document.getElementById('tabSignup');
      const nameField = document.getElementById('nameField');
      const authForm = document.getElementById('authForm');
      const authSubmitBtn = document.getElementById('authSubmitBtn');
      const authAlert = document.getElementById('authAlert');
      const avatarBtn = document.getElementById('avatarBtn');
      const dropdown = document.getElementById('accountDropdown');
      const signOutBtn = document.getElementById('signOutBtn');
      const infoModal = document.getElementById('infoModal');
      const modalTitle = document.getElementById('modalTitle');
      const modalBody = document.getElementById('modalBody');
      const modalClose = document.getElementById('modalClose');
      const pricingView = document.getElementById('pricingView');
      const pricingBackBtn = document.getElementById('pricingBackBtn');

      let mode = (location.pathname === '/register' || location.pathname === '/signup') ? 'signup' : 'login';
      let currentUser = null;
      let pendingForceLoginCreds = null;

      const waFloatBtn = document.getElementById('waFloatBtn');
      const waFloatLabel = document.getElementById('waFloatLabel');

      // Responsive Mobile Hamburger Drawer Controller (All Public Pages)
      (function initMobileDrawer() {
        const drawer = document.getElementById('mobileNavDrawer');
        const backdrop = document.getElementById('mobileNavBackdrop');
        const closeBtn = document.getElementById('mobileNavCloseBtn');
        const toggles = document.querySelectorAll('[data-mob-toggle]');
        if (!drawer || !backdrop) return;
        const setDrawer = (open) => {
          drawer.classList.toggle('open', open);
          backdrop.classList.toggle('open', open);
          drawer.setAttribute('aria-hidden', String(!open));
          backdrop.setAttribute('aria-hidden', String(!open));
          document.body.style.overflow = open ? 'hidden' : '';
          toggles.forEach((btn) => btn.setAttribute('aria-expanded', String(open)));
          if (open) {
            const p = (location.pathname || '/').replace(/\/+$/, '') || '/';
            drawer.querySelectorAll('[data-mob-link]').forEach((a) => {
              a.classList.toggle('active', a.getAttribute('data-mob-link') === p);
            });
          }
        };
        toggles.forEach((btn) => btn.addEventListener('click', () => setDrawer(!drawer.classList.contains('open'))));
        if (closeBtn) closeBtn.addEventListener('click', () => setDrawer(false));
        backdrop.addEventListener('click', () => setDrawer(false));
        drawer.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setDrawer(false)));
        document.addEventListener('keydown', (ev) => { if (ev.key === 'Escape' && drawer.classList.contains('open')) setDrawer(false); });
      })();
      const existingSessionModal = document.getElementById('existingSessionModal');
      const existingSessionMsg = document.getElementById('existingSessionMsg');
      const kickExistingSessionBtn = document.getElementById('kickExistingSessionBtn');
      const cancelExistingSessionBtn = document.getElementById('cancelExistingSessionBtn');

      async function loadDynamicExtensionRelease() {
        try {
          const res = await fetch('/api/settings/public', { cache: 'no-store' });
          if (!res.ok) return;
          const data = await res.json();
          const flowExt = data && data.extensions && data.extensions.flow ? data.extensions.flow : null;
          if (!flowExt) return;
          const ver = String(flowExt.version || '1.7').replace(/^v/i, '');
          const fileName = String(flowExt.fileName || ('PakFlow_v' + ver + '.zip'));
          const dlUrl = String(flowExt.downloadUrl || ('/downloads/' + fileName));
          const webStoreUrl = String(flowExt.webStoreUrl || '').trim();
          const storeCandidate = webStoreUrl || dlUrl;
          const isWebStore = /^https:\/\/(?:chromewebstore\.google\.com|chrome\.google\.com\/webstore)\//i.test(storeCandidate);
          const zipFallbackHref = '/downloads/' + encodeURIComponent(fileName);
          const releaseText = flowExt.releaseNote
            ? ('PakFlow v' + ver + ' — ' + String(flowExt.releaseNote))
            : (isWebStore ? ('PakFlow 1-Click Chrome Extension is live') : ('PakFlow v' + ver + ' is live now'));
          const topLink = document.getElementById('extReleaseLink');
          const topLabel = document.getElementById('extReleaseLabel');
          const topCta = topLink ? topLink.querySelector('.topbar-notice-cta') : null;
          const ddLink = document.getElementById('ddExtLink');
          const ddText = document.getElementById('ddExtText');
          const heroExtBtn = document.getElementById('heroExtDownloadBtn');
          const heroExtTxt = document.getElementById('heroExtBtnText');
          const heroZipFallback = document.getElementById('heroZipFallbackLink');
          const step1Title = document.getElementById('dashStep1Title');
          const step1Desc = document.getElementById('dashStep1Desc');

          if (isWebStore) {
            if (topLink) {
              topLink.setAttribute('href', storeCandidate);
              topLink.removeAttribute('download');
              topLink.setAttribute('target', '_blank');
              topLink.setAttribute('rel', 'noopener noreferrer');
            }
            if (topCta) topCta.innerHTML = 'Add to Chrome &rarr;';
            if (ddLink) {
              ddLink.setAttribute('href', storeCandidate);
              ddLink.removeAttribute('download');
              ddLink.setAttribute('target', '_blank');
              ddLink.setAttribute('rel', 'noopener noreferrer');
              if (ddText) ddText.textContent = 'Add to Chrome (1-Click)';
              else ddLink.textContent = 'Add to Chrome (1-Click)';
            }
            if (heroExtBtn) {
              heroExtBtn.setAttribute('href', storeCandidate);
              heroExtBtn.removeAttribute('download');
              heroExtBtn.setAttribute('target', '_blank');
              heroExtBtn.setAttribute('rel', 'noopener noreferrer');
            }
            if (heroExtTxt) heroExtTxt.textContent = 'Add to Chrome (1-Click)';
            if (heroZipFallback) {
              heroZipFallback.setAttribute('href', zipFallbackHref);
              heroZipFallback.setAttribute('download', fileName);
              heroZipFallback.style.display = 'inline-flex';
            }
            if (step1Title) step1Title.textContent = 'Add 1-Click Chrome Extension';
            if (step1Desc) step1Desc.textContent = 'Click Add to Chrome above — automatic updates install silently in the background.';
          } else {
            if (topLink) {
              topLink.setAttribute('href', dlUrl);
              topLink.setAttribute('download', fileName);
              topLink.removeAttribute('target');
              topLink.removeAttribute('rel');
            }
            if (topCta) topCta.innerHTML = 'Download &rarr;';
            if (ddLink) {
              ddLink.setAttribute('href', dlUrl);
              ddLink.setAttribute('download', fileName);
              ddLink.removeAttribute('target');
              ddLink.removeAttribute('rel');
              if (ddText) ddText.textContent = 'Get Extension (v' + ver + ')';
              else ddLink.textContent = 'Get Extension (v' + ver + ')';
            }
            if (heroExtBtn) {
              heroExtBtn.setAttribute('href', dlUrl);
              heroExtBtn.setAttribute('download', fileName);
              heroExtBtn.removeAttribute('target');
              heroExtBtn.removeAttribute('rel');
            }
            if (heroExtTxt) heroExtTxt.textContent = 'Download Extension (v' + ver + ')';
            if (heroZipFallback) heroZipFallback.style.display = 'none';
            if (step1Title) step1Title.textContent = 'Install Latest Extension';
            if (step1Desc) step1Desc.textContent = 'Download the Chrome extension above and load it once in Chrome Extensions.';
          }
          if (topLabel) topLabel.textContent = releaseText;
        } catch (_) {}
      }

      function updateWhatsappButton(user, visible) {
        if (!waFloatBtn) return;
        const wa = user && user.supportWhatsapp && user.supportWhatsapp.url ? user.supportWhatsapp : null;
        if (!visible || !wa || !wa.url) {
          waFloatBtn.classList.add('hidden');
          return;
        }
        waFloatBtn.setAttribute('href', wa.url);
        const label = wa.label || (wa.source === 'reseller' ? 'Chat with Reseller' : 'WhatsApp Support');
        waFloatBtn.setAttribute('title', label);
        if (waFloatLabel) waFloatLabel.textContent = label;
        waFloatBtn.classList.remove('hidden');
      }

      function getDeviceId() {
        let id = localStorage.getItem('pakflow_device_id');
        if (!id) {
          id = 'dev_' + Math.random().toString(36).slice(2, 12);
          localStorage.setItem('pakflow_device_id', id);
        }
        return id;
      }

      function syncExtensionBridgeLogin(token, user) {
        if (!token || !user) return;
        const plan = String(user.plan || 'basic').toLowerCase();
        if (plan === 'basic' || plan === 'free' || plan === 'trial' || plan === 'vibe' || plan === 'starter') return;
        const devId = getDeviceId();
        try {
          localStorage.setItem('pakflow_token', token);
          localStorage.setItem('token', token);
          localStorage.setItem('user', JSON.stringify(user));
          localStorage.setItem('pakflow_auth_bridge', JSON.stringify({ token: token, user: user, deviceId: devId }));
          window.dispatchEvent(new CustomEvent('__bf_auth_ready__', {
            detail: {
              token: token,
              deviceId: devId,
              userId: user.id,
              email: user.email,
              username: user.username || user.name,
              plan: user.plan || 'plus',
              planExpiresAt: user.planExpiresAt || null,
              accessPolicy: user.accessPolicy || null,
              assignedServer: user.assignedServer || null,
              daysRemaining: user.daysLeft !== undefined ? user.daysLeft : (user.daysRemaining !== undefined ? user.daysRemaining : 30)
            }
          }));
          window.postMessage({ type: 'PAKFLOW_SITE_LOGIN', token: token, user: user, deviceId: devId }, '*');
        } catch (_) {}
      }

      function syncExtensionBridgeLogout() {
        try {
          localStorage.removeItem('user');
          localStorage.removeItem('pakflow_auth_bridge');
          localStorage.removeItem('__flow_auth__');
          window.dispatchEvent(new CustomEvent('__bf_logout__'));
          window.postMessage({ type: 'PAKFLOW_SITE_LOGOUT' }, '*');
        } catch (_) {}
      }

      function showAlert(msg, isOk) {
        authAlert.textContent = msg;
        authAlert.className = 'auth-alert ' + (isOk ? 'ok' : 'err');
      }

      function clearAlert() {
        authAlert.className = 'auth-alert hidden';
      }

      function setMode(next) {
        mode = next;
        clearAlert();
        const heading = document.getElementById('authHeading');
        const sub = document.getElementById('authSubheading');
        const footerWrap = document.getElementById('authFooterSwitch');
        const forgotRow = document.getElementById('forgotRow');
        const forgotBox = document.getElementById('forgotBox');
        const verifySignupBox = document.getElementById('verifySignupBox');
        const authTabsEl = document.querySelector('.auth-tabs');
        const authCardEl = document.querySelector('.auth-card');
        const authLogoImgEl = document.getElementById('authLogoImg');
        const authForgotIconEl = document.getElementById('authForgotIcon');
        if (authCardEl) authCardEl.classList.remove('is-forgot');
        if (authLogoImgEl) authLogoImgEl.classList.remove('hidden');
        if (authForgotIconEl) authForgotIconEl.classList.add('hidden');
        if (forgotBox) forgotBox.classList.add('hidden');
        if (verifySignupBox) verifySignupBox.classList.add('hidden');
        if (authForm) authForm.classList.remove('hidden');
        if (authTabsEl) authTabsEl.classList.remove('hidden');
        if (footerWrap) footerWrap.classList.remove('hidden');
        const authPerksEl = document.getElementById('authPerks');
        const authModePillEl = document.getElementById('authModePill');
        if (authModePillEl) authModePillEl.classList.remove('hidden');
        if (mode === 'signup') {
          if (tabSignup) tabSignup.classList.add('active');
          if (tabLogin) tabLogin.classList.remove('active');
          nameField.classList.remove('hidden');
          if (authPerksEl) authPerksEl.classList.remove('hidden');
          if (authModePillEl) authModePillEl.textContent = '✦ OTP Verified Signup';
          if (forgotRow) forgotRow.classList.add('hidden');
          authSubmitBtn.textContent = 'Create Account →';
          if (heading) heading.textContent = 'Join PakFlow';
          if (sub) sub.textContent = 'Free Basic Plan — upgrade anytime to unlock tools';
          if (footerWrap) footerWrap.innerHTML = 'Already have an account? <button type="button" id="footerSwitchBtn">Sign in</button>';
        } else {
          if (tabLogin) tabLogin.classList.add('active');
          if (tabSignup) tabSignup.classList.remove('active');
          nameField.classList.add('hidden');
          if (authPerksEl) authPerksEl.classList.add('hidden');
          if (authModePillEl) authModePillEl.textContent = '✦ Google Flow Studio';
          if (forgotRow) forgotRow.classList.remove('hidden');
          authSubmitBtn.textContent = 'Sign In →';
          if (heading) heading.textContent = 'PakFlow';
          if (sub) sub.textContent = 'Sign in to your account';
          if (footerWrap) footerWrap.innerHTML = 'Don\'t have an account? <button type="button" id="footerSwitchBtn">Sign up free</button>';
        }
        const fBtn = document.getElementById('footerSwitchBtn');
        if (fBtn) fBtn.addEventListener('click', () => setMode(mode === 'signup' ? 'login' : 'signup'));
      }

      let pendingSignupEmail = '';
      const verifySignupForm = document.getElementById('verifySignupForm');
      const verifySignupBtn = document.getElementById('verifySignupBtn');
      const resendSignupCodeBtn = document.getElementById('resendSignupCodeBtn');

      if (verifySignupForm) {
        verifySignupForm.addEventListener('submit', async (e) => {
          e.preventDefault();
          clearAlert();
          const code = document.getElementById('signupVerifyCode').value.trim();
          verifySignupBtn.disabled = true;
          verifySignupBtn.textContent = 'Verifying...';
          try {
            const res = await fetch('/api/auth/verify-signup', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ email: pendingSignupEmail, code }),
            });
            const data = await res.json();
            if (res.ok && data.token) {
              const verifiedUser = data.user || { email: pendingSignupEmail };
              localStorage.setItem('pakflow_token', data.token);
              localStorage.setItem('token', data.token);
              try { localStorage.setItem('pakflow_user_cache', JSON.stringify(verifiedUser)); } catch (_) {}
              syncExtensionBridgeLogin(data.token, verifiedUser);
              showDash(verifiedUser);
            } else {
              showAlert(data.message || 'Invalid or expired verification code', false);
            }
          } catch (_) {
            showAlert('Network error while verifying code.', false);
          } finally {
            verifySignupBtn.disabled = false;
            verifySignupBtn.textContent = 'Verify & Create Account';
          }
        });
      }

      if (resendSignupCodeBtn) {
        resendSignupCodeBtn.addEventListener('click', async () => {
          if (!pendingSignupEmail) return;
          clearAlert();
          try {
            const res = await fetch('/api/auth/resend-signup-code', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ email: pendingSignupEmail }),
            });
            const data = await res.json();
            showAlert(data.message || (res.ok ? 'Code resent!' : 'Failed to resend code'), res.ok && data.success);
          } catch (_) {
            showAlert('Network error.', false);
          }
        });
      }

      const openForgotBtn = document.getElementById('openForgotBtn');
      const backToLoginBtn = document.getElementById('backToLoginBtn');
      const forgotBox = document.getElementById('forgotBox');
      const forgotStep1Form = document.getElementById('forgotStep1Form');
      const forgotStep2Form = document.getElementById('forgotStep2Form');
      const sendCodeBtn = document.getElementById('sendCodeBtn');
      const resetPassBtn = document.getElementById('resetPassBtn');

      if (openForgotBtn && forgotBox) {
        openForgotBtn.addEventListener('click', () => {
          clearAlert();
          authForm.classList.add('hidden');
          const tabsEl = document.querySelector('.auth-tabs');
          if (tabsEl) tabsEl.classList.add('hidden');
          document.getElementById('authFooterSwitch').classList.add('hidden');
          const authCardEl = document.querySelector('.auth-card');
          const authLogoImgEl = document.getElementById('authLogoImg');
          const authForgotIconEl = document.getElementById('authForgotIcon');
          const authPerksEl = document.getElementById('authPerks');
          const authModePillEl = document.getElementById('authModePill');
          if (authModePillEl) authModePillEl.classList.add('hidden');
          if (authCardEl) authCardEl.classList.add('is-forgot');
          if (authLogoImgEl) authLogoImgEl.classList.add('hidden');
          if (authPerksEl) authPerksEl.classList.add('hidden');
          if (authForgotIconEl) authForgotIconEl.classList.remove('hidden');
          forgotBox.classList.remove('hidden');
          forgotStep2Form.classList.add('hidden');
          document.getElementById('authHeading').textContent = 'Forgot password?';
          document.getElementById('authSubheading').textContent = "Enter your account email and we'll send you a link to reset your password.";
        });
      }
      if (backToLoginBtn) {
        backToLoginBtn.addEventListener('click', () => setMode('login'));
      }
      if (forgotStep1Form) {
        forgotStep1Form.addEventListener('submit', async (e) => {
          e.preventDefault();
          clearAlert();
          const email = document.getElementById('forgotEmail').value.trim();
          sendCodeBtn.disabled = true;
          sendCodeBtn.textContent = 'Sending link...';
          try {
            const res = await fetch('/api/auth/forgot-password', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ email }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
              showAlert(data.message || 'Reset link sent! Please check your email.', true);
            } else {
              showAlert(data.message || 'Failed to send reset link', false);
            }
          } catch (_) {
            showAlert('Network error. Please try again.', false);
          } finally {
            sendCodeBtn.disabled = false;
            sendCodeBtn.textContent = 'Send Reset Link';
          }
        });
      }
      if (forgotStep2Form) {
        forgotStep2Form.addEventListener('submit', async (e) => {
          e.preventDefault();
          clearAlert();
          const params = new URLSearchParams(location.search);
          const token = params.get('token') || '';
          const newPassword = document.getElementById('resetNewPass').value;
          resetPassBtn.disabled = true;
          resetPassBtn.textContent = 'Updating...';
          try {
            const res = await fetch('/api/auth/reset-password', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ token, newPassword }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
              history.replaceState(null, '', '/login');
              setMode('login');
              showAlert(data.message || 'Password reset successfully! Please sign in.', true);
            } else {
              showAlert(data.message || 'Invalid or expired reset link', false);
            }
          } catch (_) {
            showAlert('Network error. Please try again.', false);
          } finally {
            resetPassBtn.disabled = false;
            resetPassBtn.textContent = 'Reset Password';
          }
        });
      }

      const togglePassBtn = document.getElementById('togglePassBtn');
      const authPassInput = document.getElementById('authPassword');
      if (togglePassBtn && authPassInput) {
        togglePassBtn.addEventListener('click', () => {
          const isPwd = authPassInput.type === 'password';
          authPassInput.type = isPwd ? 'text' : 'password';
          togglePassBtn.textContent = isPwd ? '🙈' : '👁';
        });
      }

      if (tabLogin) tabLogin.addEventListener('click', () => setMode('login'));
      if (tabSignup) tabSignup.addEventListener('click', () => setMode('signup'));

      function formatValidityText(user, shortCard) {
        const plan = String((user && user.plan) || 'basic').toLowerCase();
        const isDemo = plan === 'demo' || Boolean(user && user.isDemo);
        const expMs = user && user.planExpiresAt ? Date.parse(String(user.planExpiresAt)) : NaN;
        const remSec = Number.isFinite(expMs) ? Math.max(0, Math.floor((expMs - Date.now()) / 1000)) : Number((user && user.remainingSeconds) || 0);
        if (isDemo) {
          if (remSec <= 0) return shortCard ? 'Demo Ended' : 'Demo time ended';
          if (remSec < 3600) {
            const mins = Math.max(1, Math.ceil(remSec / 60));
            return shortCard ? (mins + (mins === 1 ? ' Min' : ' Mins')) : (mins + ' min left in demo');
          }
          const hrs = Math.floor(remSec / 3600);
          const mins = Math.floor((remSec % 3600) / 60);
          const hm = mins > 0 ? (hrs + 'h ' + mins + 'm') : (hrs + 'h');
          return shortCard ? hm : (hm + ' left in demo');
        }
        const days = Number((user && (user.daysRemaining ?? user.daysLeft)) ?? 0);
        return shortCard ? (days + (days === 1 ? ' Day' : ' Days')) : (days + ' days left in plan');
      }

      function renderUser(user) {
        currentUser = user;
        const name = (user.name || user.username || user.email || 'User').toString();
        const email = (user.email || '').toString();
        const plan = (user.plan || 'basic').toString().toLowerCase();
        const isDemo = plan === 'demo' || Boolean(user.isDemo);
        const isBasic = ['basic', 'free', 'trial', 'vibe', 'starter'].includes(plan);
        const rawTotal = Number(user.creditsTotal ?? 0);
        const defaultPlanTotal = isDemo ? 2000 : ((plan === 'pro' || plan === 'plus') ? 25000 : 45000);
        const creditsTotal = isBasic ? 0 : (rawTotal > 0 ? rawTotal : defaultPlanTotal);
        const hasLiveCredits = user.credits !== undefined && user.credits !== null && Number.isFinite(Number(user.credits));
        const credits = isBasic ? 0 : Math.max(0, hasLiveCredits ? Math.round(Number(user.credits)) : creditsTotal);
        const pct = creditsTotal > 0 ? Math.max(0, Math.min(100, Math.round((credits / creditsTotal) * 100))) : 0;
        const planBadge = isDemo ? 'Flow Demo'
          : (plan === 'ultra' || plan === 'max') ? 'Flow Ultra'
          : (plan === 'pro' || plan === 'plus') ? 'Flow Plus'
          : plan === 'heavy' ? 'Flow Heavy'
          : 'Flow Basic';

        document.getElementById('planPill').textContent = planBadge;
        avatarBtn.textContent = name.trim().charAt(0).toUpperCase() || 'P';
        document.getElementById('ddName').textContent = name.toUpperCase();
        document.getElementById('ddEmail').textContent = email;
        document.getElementById('ddCreditsVal').innerHTML =
          credits.toLocaleString() + ' <span>/ ' + creditsTotal.toLocaleString() + '</span>';
        document.getElementById('ddProgress').style.width = pct + '%';
        document.getElementById('ddDays').textContent = formatValidityText(user, false);

        const dashPlanEl = document.getElementById('dashPlanCardVal');
        const dashCreditsEl = document.getElementById('dashCreditsCardVal');
        const dashBarEl = document.getElementById('dashCreditsBar');
        const dashDaysEl = document.getElementById('dashDaysCardVal');
        const dashEmailSub = document.getElementById('dashEmailCardSub');
        const maxDevsAllowed = Math.max(1, Number(user.maxDevices || 1));
        const devLabel = maxDevsAllowed + ' active device' + (maxDevsAllowed > 1 ? 's allowed' : ' session');
        if (dashPlanEl) dashPlanEl.textContent = planBadge;
        if (dashCreditsEl) dashCreditsEl.textContent = credits.toLocaleString();
        if (dashBarEl) dashBarEl.style.width = pct + '%';
        if (dashDaysEl) dashDaysEl.textContent = formatValidityText(user, true);
        if (dashEmailSub) dashEmailSub.textContent = email ? (email + ' · ' + maxDevsAllowed + (maxDevsAllowed > 1 ? ' Devices' : ' Device')) : devLabel;
      }

      function hideBootLoader() {
        const bl = document.getElementById('bootLoader');
        if (bl && !bl.classList.contains('is-done')) {
          bl.classList.add('is-done');
        }
      }

      function showLanding() {
        hideBootLoader();
        updateWhatsappButton(null, false);
        aboutView.classList.add('hidden');
        authView.classList.add('hidden');
        dashView.classList.add('hidden');
        settingsView.classList.add('hidden');
        pricingView.classList.add('hidden');
        landingView.classList.remove('hidden');
      }

      function showAbout() {
        hideBootLoader();
        updateWhatsappButton(null, false);
        landingView.classList.add('hidden');
        authView.classList.add('hidden');
        dashView.classList.add('hidden');
        settingsView.classList.add('hidden');
        pricingView.classList.add('hidden');
        aboutView.classList.remove('hidden');
        if (location.pathname !== '/about') {
          history.replaceState(null, '', '/about');
        }
      }

      function showAuth(targetMode) {
        hideBootLoader();
        updateWhatsappButton(null, false);
        if (targetMode) mode = targetMode;
        landingView.classList.add('hidden');
        aboutView.classList.add('hidden');
        dashView.classList.add('hidden');
        settingsView.classList.add('hidden');
        pricingView.classList.add('hidden');
        authView.classList.remove('hidden');
        setMode(mode);
        const targetUrl = mode === 'signup' ? '/signup' : '/login';
        if (location.pathname !== targetUrl && location.pathname !== '/reset-password') {
          history.replaceState(null, '', targetUrl);
        }
      }

      function showDash(user) {
        hideBootLoader();
        renderUser(user);
        updateWhatsappButton(user, true);
        landingView.classList.add('hidden');
        aboutView.classList.add('hidden');
        authView.classList.add('hidden');
        settingsView.classList.add('hidden');
        pricingView.classList.add('hidden');
        dashView.classList.remove('hidden');
        if (location.pathname !== '/dashboard') {
          history.replaceState(null, '', '/dashboard');
        }
      }

      function showSettings() {
        hideBootLoader();
        landingView.classList.add('hidden');
        aboutView.classList.add('hidden');
        authView.classList.add('hidden');
        dashView.classList.add('hidden');
        pricingView.classList.add('hidden');
        settingsView.classList.remove('hidden');
        settingsAlert.className = 'auth-alert hidden';
        document.getElementById('settingsCurrentEmail').textContent = (currentUser && currentUser.email) ? currentUser.email : '-';
        if (location.pathname !== '/settings') {
          history.replaceState(null, '', '/settings');
        }
      }

      function showPricing() {
        hideBootLoader();
        landingView.classList.add('hidden');
        aboutView.classList.add('hidden');
        authView.classList.add('hidden');
        dashView.classList.add('hidden');
        settingsView.classList.add('hidden');
        pricingBackBtn.textContent = currentUser ? '← Back to Dashboard' : '← Back to Home';
        pricingView.classList.remove('hidden');
        if (location.pathname !== '/pricing') {
          history.replaceState(null, '', '/pricing');
        }
      }

      function showSettingsAlert(msg, isOk) {
        settingsAlert.textContent = msg;
        settingsAlert.className = 'auth-alert ' + (isOk ? 'ok' : 'err');
      }

      settingsBackBtn.addEventListener('click', () => {
        if (currentUser) showDash(currentUser);
      });

      changePasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const token = localStorage.getItem('pakflow_token') || localStorage.getItem('token');
        const currentPassword = document.getElementById('curPass').value;
        const newPassword = document.getElementById('newPass').value;
        try {
          const res = await fetch('/api/user/change-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + token },
            body: JSON.stringify({ currentPassword, newPassword }),
          });
          const data = await res.json();
          if (res.ok && data.success) {
            showSettingsAlert(data.message || 'Password updated successfully', true);
            changePasswordForm.reset();
          } else {
            showSettingsAlert(data.message || 'Failed to update password', false);
          }
        } catch (_) {
          showSettingsAlert('Network error while updating password', false);
        }
      });

      changeEmailForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const token = localStorage.getItem('pakflow_token') || localStorage.getItem('token');
        const newEmail = document.getElementById('newEmailInput').value.trim();
        try {
          const res = await fetch('/api/user/change-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Authorization: 'Bearer ' + token },
            body: JSON.stringify({ newEmail }),
          });
          const data = await res.json();
          if (res.ok && data.success) {
            if (data.token) {
              localStorage.setItem('pakflow_token', data.token);
              localStorage.setItem('token', data.token);
            }
            if (data.user) {
              renderUser(data.user);
            } else if (currentUser) {
              currentUser.email = newEmail;
              renderUser(currentUser);
            }
            document.getElementById('settingsCurrentEmail').textContent = newEmail;
            showSettingsAlert(data.message || 'Email updated successfully', true);
            changeEmailForm.reset();
          } else {
            showSettingsAlert(data.message || 'Failed to update email', false);
          }
        } catch (_) {
          showSettingsAlert('Network error while updating email', false);
        }
      });

      deleteAccountBtn.addEventListener('click', async () => {
        if (!confirm('Are you sure you want to permanently delete your account?')) return;
        const token = localStorage.getItem('pakflow_token') || localStorage.getItem('token');
        deleteAccountBtn.disabled = true;
        try {
          const res = await fetch('/api/user/account', {
            method: 'DELETE',
            headers: { Authorization: 'Bearer ' + token },
          });
          const data = await res.json();
          if (res.ok && data.success) {
            localStorage.removeItem('pakflow_token');
            localStorage.removeItem('token');
            localStorage.removeItem('pakflow_user_cache');
            syncExtensionBridgeLogout();
            currentUser = null;
            history.replaceState(null, '', '/');
            showLanding();
          } else {
            showSettingsAlert(data.message || 'Failed to delete account', false);
          }
        } catch (_) {
          showSettingsAlert('Network error while deleting account', false);
        } finally {
          deleteAccountBtn.disabled = false;
        }
      });

      heroCreateBtn.addEventListener('click', () => showAuth('login'));
      navLoginBtn.addEventListener('click', () => showAuth('login'));
      navSignupBtn.addEventListener('click', () => showAuth('signup'));
      authBackBtn.addEventListener('click', () => {
        history.replaceState(null, '', '/');
        showLanding();
      });

      async function performAuthSubmit(forceLogoutExisting) {
        clearAlert();
        const email = (pendingForceLoginCreds && forceLogoutExisting) ? pendingForceLoginCreds.email : document.getElementById('authEmail').value.trim();
        const password = (pendingForceLoginCreds && forceLogoutExisting) ? pendingForceLoginCreds.password : document.getElementById('authPassword').value;
        const name = document.getElementById('authName').value.trim();

        authSubmitBtn.disabled = true;
        authSubmitBtn.textContent = mode === 'signup' ? 'Creating account...' : 'Signing in...';
        if (kickExistingSessionBtn && forceLogoutExisting) {
          kickExistingSessionBtn.disabled = true;
          kickExistingSessionBtn.textContent = 'Logging out existing session...';
        }

        try {
          const endpoint = mode === 'signup' ? '/api/auth/register' : '/api/auth/login';
          const payload = mode === 'signup'
            ? { name, email, password, deviceId: getDeviceId() }
            : { email, password, deviceId: getDeviceId() };

          const res = await fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });
          const data = await res.json();
          if (res.status === 409 && data && data.existingSession) {
            pendingForceLoginCreds = { email, password };
            if (existingSessionMsg) {
              existingSessionMsg.textContent = data.message || 'This account is already logged in on another device. Click below to logout the existing session and login here.';
            }
            if (existingSessionModal) existingSessionModal.classList.remove('hidden');
            return;
          }
          if (existingSessionModal) existingSessionModal.classList.add('hidden');
          pendingForceLoginCreds = null;

          if (res.ok && data.requiresVerification) {
            pendingSignupEmail = data.email || email;
            authForm.classList.add('hidden');
            const _tabs = document.querySelector('.auth-tabs');
            if (_tabs) _tabs.classList.add('hidden');
            const _perks = document.getElementById('authPerks');
            if (_perks) _perks.classList.add('hidden');
            document.getElementById('authFooterSwitch').classList.add('hidden');
            document.getElementById('verifySignupBox').classList.remove('hidden');
            document.getElementById('authHeading').textContent = 'Verify Your Email';
            document.getElementById('authSubheading').textContent = 'Enter the 6-digit code sent to ' + pendingSignupEmail;
            showAlert(data.message || 'Verification code sent to your email.', true);
          } else if (!res.ok || !data.token) {
            showAlert(data.message || data.error || 'Authentication failed', false);
          } else {
            const signedInUser = Object.assign({}, data.user || { email }, {
              accessPolicy: data.accessPolicy || (data.user && data.user.accessPolicy) || null,
              assignedServer: data.assignedServer || (data.user && data.user.assignedServer) || null
            });
            localStorage.setItem('pakflow_token', data.token);
            localStorage.setItem('token', data.token);
            try { localStorage.setItem('pakflow_user_cache', JSON.stringify(signedInUser)); } catch (_) {}
            syncExtensionBridgeLogin(data.token, signedInUser);
            showDash(signedInUser);
          }
        } catch (_err) {
          if (existingSessionModal) existingSessionModal.classList.add('hidden');
          showAlert('Network error. Please try again.', false);
        } finally {
          authSubmitBtn.disabled = false;
          authSubmitBtn.textContent = mode === 'signup' ? 'Create Account' : 'Sign In';
          if (kickExistingSessionBtn) {
            kickExistingSessionBtn.disabled = false;
            kickExistingSessionBtn.textContent = 'Logout Existing Session & Login';
          }
        }
      }

      authForm.addEventListener('submit', (e) => {
        e.preventDefault();
        performAuthSubmit(false);
      });

      if (kickExistingSessionBtn) {
        kickExistingSessionBtn.addEventListener('click', () => {
          performAuthSubmit(true);
        });
      }
      if (cancelExistingSessionBtn) {
        cancelExistingSessionBtn.addEventListener('click', () => {
          pendingForceLoginCreds = null;
          if (existingSessionModal) existingSessionModal.classList.add('hidden');
        });
      }
      if (existingSessionModal) {
        existingSessionModal.addEventListener('click', (e) => {
          if (e.target === existingSessionModal) {
            pendingForceLoginCreds = null;
            existingSessionModal.classList.add('hidden');
          }
        });
      }

      avatarBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        dropdown.classList.toggle('hidden');
      });

      document.addEventListener('click', (e) => {
        if (!dropdown.contains(e.target) && e.target !== avatarBtn) {
          dropdown.classList.add('hidden');
        }
      });

      signOutBtn.addEventListener('click', async () => {
        const token = localStorage.getItem('pakflow_token') || localStorage.getItem('token');
        try {
          await fetch('/api/auth/logout', {
            method: 'POST',
            headers: token ? { Authorization: 'Bearer ' + token } : {},
          });
        } catch (_) {}
        localStorage.removeItem('pakflow_token');
        localStorage.removeItem('token');
        localStorage.removeItem('pakflow_user_cache');
        syncExtensionBridgeLogout();
        dropdown.classList.add('hidden');
        history.replaceState(null, '', '/login');
        mode = 'login';
        showAuth();
      });

      document.querySelectorAll('[data-modal]').forEach((btn) => {
        btn.addEventListener('click', () => {
          dropdown.classList.add('hidden');
          const kind = btn.getAttribute('data-modal');
          const u = currentUser || {};
          if (kind === 'settings') {
            showSettings();
            return;
          } else if (kind === 'plans') {
            showPricing();
            return;
          } else {
            const maxDevsAllowed = Math.max(1, Number(u.maxDevices || 1));
            modalTitle.textContent = 'My Devices';
            modalBody.innerHTML =
              '<div class="info-row"><span>Allowed Devices</span><strong>' + maxDevsAllowed + (maxDevsAllowed > 1 ? ' Devices' : ' Device') + '</strong></div>' +
              '<div class="info-row"><span>Active Browser ID</span><strong>' + getDeviceId() + '</strong></div>' +
              '<div class="info-row"><span>Device Switches</span><strong>' + Number(u.deviceSwitchCount || 0) + '</strong></div>';
          }
          infoModal.classList.remove('hidden');
        });
      });

      const navPricingBtn = document.getElementById('navPricingBtn');
      if (navPricingBtn) {
        navPricingBtn.addEventListener('click', () => {
          pricingBackBtn.textContent = currentUser ? '← Back to Dashboard' : '← Back to Home';
          showPricing();
        });
      }

      pricingBackBtn.addEventListener('click', () => {
        if (currentUser) {
          showDash(currentUser);
        } else {
          history.replaceState(null, '', '/');
          showLanding();
        }
      });

      const cornerToast = document.getElementById('cornerToast');
      const cornerToastTitle = document.getElementById('cornerToastTitle');
      const cornerToastMsg = document.getElementById('cornerToastMsg');
      const cornerToastClose = document.getElementById('cornerToastClose');
      let toastTimer = null;

      function showCornerToast(title, msg) {
        cornerToastTitle.textContent = title;
        cornerToastMsg.textContent = msg;
        cornerToast.classList.remove('hidden');
        if (toastTimer) clearTimeout(toastTimer);
        toastTimer = setTimeout(() => cornerToast.classList.add('hidden'), 4500);
      }

      cornerToastClose.addEventListener('click', () => {
        if (toastTimer) clearTimeout(toastTimer);
        cornerToast.classList.add('hidden');
      });

      document.querySelectorAll('[data-get-plan]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const planName = btn.getAttribute('data-get-plan') || 'Plan';
          showCornerToast('Contact Your Reseller', 'To get the ' + planName + ' plan, please contact your reseller.');
        });
      });

      modalClose.addEventListener('click', () => infoModal.classList.add('hidden'));
      infoModal.addEventListener('click', (e) => {
        if (e.target === infoModal) infoModal.classList.add('hidden');
      });

      async function boot() {
        const token = localStorage.getItem('pakflow_token') || localStorage.getItem('token');
        if (!token) {
          if (location.pathname === '/reset-password' && new URLSearchParams(location.search).get('token')) {
            showAuth('login');
            authForm.classList.add('hidden');
            const _tabs = document.querySelector('.auth-tabs');
            if (_tabs) _tabs.classList.add('hidden');
            const _perks = document.getElementById('authPerks');
            if (_perks) _perks.classList.add('hidden');
            document.getElementById('authFooterSwitch').classList.add('hidden');
            forgotBox.classList.remove('hidden');
            forgotStep1Form.classList.add('hidden');
            forgotStep2Form.classList.remove('hidden');
            document.getElementById('authHeading').textContent = 'Set New Password';
            document.getElementById('authSubheading').textContent = 'Enter your new password below';
          } else if (location.pathname === '/about') {
            showAbout();
          } else if (location.pathname === '/pricing') {
            showPricing();
          } else if (location.pathname === '/login' || location.pathname === '/register' || location.pathname === '/signup') {
            showAuth(mode);
          } else {
            showLanding();
          }
          return;
        }
        // Instant cached render on refresh so there is zero wait/black screen
        let renderedFromCache = false;
        try {
          const cachedRaw = localStorage.getItem('pakflow_user_cache');
          if (cachedRaw) {
            const cachedUser = JSON.parse(cachedRaw);
            if (cachedUser && (cachedUser.email || cachedUser.id)) {
              renderedFromCache = true;
              if (location.pathname === '/settings') {
                renderUser(cachedUser);
                showSettings();
              } else if (location.pathname === '/pricing') {
                renderUser(cachedUser);
                showPricing();
              } else {
                showDash(cachedUser);
              }
            }
          }
        } catch (_) {}

        try {
          const res = await fetch('/api/auth/me', {
            headers: { Authorization: 'Bearer ' + token },
          });
          const data = await res.json();
          if (res.ok && data && data.authenticated !== false && (data.user || data.email)) {
            const freshUser = Object.assign({}, data.user || data, {
              accessPolicy: data.accessPolicy || (data.user && data.user.accessPolicy) || null,
              assignedServer: data.assignedServer || (data.user && data.user.assignedServer) || null
            });
            const activeTok = data.token || token;
            if (data.token) {
              localStorage.setItem('pakflow_token', data.token);
              localStorage.setItem('token', data.token);
            }
            try { localStorage.setItem('pakflow_user_cache', JSON.stringify(freshUser)); } catch (_) {}
            syncExtensionBridgeLogin(activeTok, freshUser);
            if (location.pathname === '/settings') {
              renderUser(freshUser);
              updateWhatsappButton(freshUser, true);
              if (!renderedFromCache) showSettings();
            } else if (location.pathname === '/pricing') {
              renderUser(freshUser);
              updateWhatsappButton(freshUser, true);
              if (!renderedFromCache) showPricing();
            } else {
              if (!renderedFromCache) showDash(freshUser);
              else {
                renderUser(freshUser);
                updateWhatsappButton(freshUser, true);
              }
            }
            return;
          }
        } catch (_) {
          if (renderedFromCache) return;
        }
        localStorage.removeItem('pakflow_token');
        localStorage.removeItem('token');
        localStorage.removeItem('pakflow_user_cache');
        showLanding();
      }

      let lastSessionCheckMs = Date.now();
      let sessionCheckInFlight = false;
      async function verifyActiveSession() {
        const token = localStorage.getItem('pakflow_token') || localStorage.getItem('token');
        if (!token || !currentUser || sessionCheckInFlight) return;
        sessionCheckInFlight = true;
        lastSessionCheckMs = Date.now();
        try {
          const res = await fetch('/api/auth/me', {
            headers: { Authorization: 'Bearer ' + token },
          });
          const data = await res.json();
          if (res.status === 401 || (data && (data.forceLogout || data.loggedOutOtherDevice || data.authenticated === false))) {
            localStorage.removeItem('pakflow_token');
            localStorage.removeItem('token');
            localStorage.removeItem('pakflow_user_cache');
            syncExtensionBridgeLogout();
            currentUser = null;
            showAuth('login');
            showAlert((data && (data.message || data.error)) || 'Logged out: Your account was signed in on another device.', false);
          } else if (res.ok && data && (data.user || data.email)) {
            const freshUser = Object.assign({}, currentUser || {}, data.user || data, {
              accessPolicy: data.accessPolicy || (data.user && data.user.accessPolicy) || (currentUser && currentUser.accessPolicy) || null,
              assignedServer: data.assignedServer || (data.user && data.user.assignedServer) || (currentUser && currentUser.assignedServer) || null
            });
            try { localStorage.setItem('pakflow_user_cache', JSON.stringify(freshUser)); } catch (_) {}
            renderUser(freshUser);
          }
        } catch (_) {} finally {
          sessionCheckInFlight = false;
        }
      }

      const sessionPollMs = 180000 + Math.floor(Math.random() * 30000);
      setInterval(() => {
        if (document.visibilityState === 'visible') {
          verifyActiveSession();
        }
      }, sessionPollMs);
      // Exact-minute Demo timer refresh + immediate auto-logout when Demo timer reaches 0
      setInterval(() => {
        if (!currentUser) return;
        const p = String(currentUser.plan || '').toLowerCase();
        if (p !== 'demo' && !currentUser.isDemo) return;
        const expMs = currentUser.planExpiresAt ? Date.parse(String(currentUser.planExpiresAt)) : NaN;
        if (Number.isFinite(expMs) && expMs <= Date.now()) {
          verifyActiveSession();
        } else {
          renderUser(currentUser);
        }
      }, 15000);
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && (Date.now() - lastSessionCheckMs) >= 15000) {
          verifyActiveSession();
        }
      });

      // Same-Chrome Multi-Tab Sync: if another tab in this Chrome profile signs in or refreshes token, stay signed in seamlessly
      window.addEventListener('storage', (ev) => {
        if (!ev) return;
        if (ev.key === 'pakflow_token' || ev.key === 'token') {
          if (ev.newValue && typeof ev.newValue === 'string') {
            try {
              const cachedRaw = localStorage.getItem('pakflow_user_cache');
              if (cachedRaw) {
                const cachedU = JSON.parse(cachedRaw);
                if (cachedU && (cachedU.email || cachedU.id)) {
                  if (!currentUser) showDash(cachedU);
                  else renderUser(cachedU);
                  return;
                }
              }
            } catch (_) {}
            boot();
          } else if (!localStorage.getItem('pakflow_token') && !localStorage.getItem('token') && currentUser) {
            currentUser = null;
            showAuth('login');
          }
        }
      });

      // Live credit balance push from PakFlow Chrome Extension (instant, zero extra server requests)
      window.addEventListener('message', (event) => {
        if (event.source !== window || !event.data || event.data.type !== 'PAKFLOW_CREDITS_UPDATED') return;
        const nextCred = Number(event.data.credits);
        if (!Number.isFinite(nextCred) || nextCred < 0 || !currentUser) return;
        const nextTotal = Number(event.data.creditsTotal);
        currentUser = Object.assign({}, currentUser, {
          credits: Math.round(nextCred),
          creditsTotal: Number.isFinite(nextTotal) && nextTotal > 0 ? Math.round(nextTotal) : (currentUser.creditsTotal || 45000),
        });
        try { localStorage.setItem('pakflow_user_cache', JSON.stringify(currentUser)); } catch (_) {}
        renderUser(currentUser);
      });

      const heroTypedEl = document.getElementById('heroTypedText');
      (function startTypewriter() {
        if (!heroTypedEl) return;
        const full = 'AI VIDEO.\nUNLIMITED.\nCREATION.';
        let step = 0;
        function tick() {
          heroTypedEl.innerHTML = full.slice(0, step).replace(/\n/g, '<br />');
          if (step < full.length) {
            step++;
            setTimeout(tick, 70);
          }
        }
        tick();
      })();

      const genBtnEl = document.getElementById('generateVideoBtn');
      if (genBtnEl) {
        const signalPrepareFlow = () => {
          const tok = localStorage.getItem('pakflow_token') || localStorage.getItem('token');
          if (tok && currentUser) syncExtensionBridgeLogin(tok, currentUser);
          try { window.postMessage({ type: 'PAKFLOW_PREPARE_FLOW' }, '*'); } catch (_) {}
        };
        genBtnEl.addEventListener('mouseenter', signalPrepareFlow, { passive: true });
        genBtnEl.addEventListener('pointerdown', signalPrepareFlow, { passive: true });
        genBtnEl.addEventListener('click', signalPrepareFlow, { passive: true });
      }

      loadDynamicExtensionRelease();
      boot();
    })();
  </script>
</body>
</html>
