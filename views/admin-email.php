<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>PakFlow Admin — Custom Email Sender</title>
  <link rel="icon" href="/flowbypak-logo.png" />
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      min-height: 100vh;
      background: #050507;
      color: #f4f4f5;
      font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      padding: 28px 20px 60px;
    }
    .hidden { display: none !important; }
    .wrap { max-width: 780px; margin: 0 auto; }
    .top {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 24px;
      flex-wrap: wrap;
      gap: 12px;
    }
    .top h1 { font-size: 22px; font-weight: 800; }
    .btn-ghost {
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.14);
      color: #e4e4e7;
      padding: 8px 16px;
      border-radius: 999px;
      font-size: 13px;
      font-weight: 600;
      text-decoration: none;
      cursor: pointer;
    }
    .card {
      background: #111116;
      border: 1px solid rgba(255,255,255,0.10);
      border-radius: 18px;
      padding: 24px 26px;
      margin-bottom: 18px;
    }
    .field { margin-bottom: 16px; }
    .field label {
      display: block;
      font-size: 13px;
      font-weight: 600;
      color: #d4d4d8;
      margin-bottom: 6px;
    }
    input, select, textarea {
      width: 100%;
      padding: 11px 14px;
      border-radius: 11px;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.04);
      color: #ffffff;
      font-size: 14px;
      font-family: inherit;
      outline: none;
    }
    select option { background: #141418; color: #fff; }
    textarea { min-height: 200px; resize: vertical; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 13px; }
    input:focus, select:focus, textarea:focus { border-color: #8b5cf6; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    @media (max-width: 640px) { .row { grid-template-columns: 1fr; } }
    .btn-primary {
      padding: 12px 24px;
      border: 0;
      border-radius: 11px;
      background: linear-gradient(135deg, #7c3aed, #6d28d9);
      color: #fff;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-primary:disabled { opacity: 0.55; cursor: not-allowed; }
    .alert {
      padding: 12px 14px;
      border-radius: 10px;
      font-size: 13.5px;
      margin-bottom: 16px;
    }
    .alert.ok { background: rgba(34,197,94,0.14); border: 1px solid rgba(34,197,94,0.35); color: #86efac; }
    .alert.err { background: rgba(239,68,68,0.14); border: 1px solid rgba(239,68,68,0.35); color: #fca5a5; }
    .preview-box {
      background: #f3f4f6;
      border-radius: 12px;
      padding: 16px;
      margin-top: 12px;
      color: #111827;
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <h1>PakFlow Admin — Custom Email Sender</h1>
      <div style="display:flex;gap:10px;">
        <a href="/admin" class="btn-ghost">&larr; Admin Panel</a>
        <button type="button" id="adminLogoutBtn" class="btn-ghost hidden">Logout</button>
      </div>
    </div>

    <div id="alertBox" class="alert hidden"></div>

    <!-- Admin Login Card -->
    <div id="adminLoginCard" class="card hidden">
      <h2 style="font-size:17px;margin-bottom:14px;">Admin Sign In</h2>
      <form id="adminLoginForm">
        <div class="field">
          <label for="admEmail">Admin Email</label>
          <input id="admEmail" type="email" placeholder="paktools@gmail.com" required />
        </div>
        <div class="field">
          <label for="admPass">Admin Password</label>
          <input id="admPass" type="password" placeholder="Enter admin password" required />
        </div>
        <button type="submit" class="btn-primary">Sign In</button>
      </form>
    </div>

    <!-- Email Composer Card -->
    <div id="composerCard" class="card hidden">
      <form id="sendEmailForm">
        <div class="row">
          <div class="field">
            <label for="targetSelect">Send To</label>
            <select id="targetSelect">
              <option value="custom">Custom Email(s)</option>
              <option value="active">All Active Paid Users</option>
              <option value="all">All Registered Users</option>
            </select>
          </div>
          <div class="field">
            <label for="formatSelect">Format</label>
            <select id="formatSelect">
              <option value="html">HTML (Custom Design)</option>
              <option value="text">Plain Text (PakFlow Card)</option>
            </select>
          </div>
        </div>

        <div class="field" id="customToField">
          <label for="toInput">Recipient Email(s) (comma-separated)</label>
          <input id="toInput" type="text" placeholder="user1@gmail.com, user2@gmail.com" />
        </div>

        <div class="field">
          <label for="subjectInput">Subject</label>
          <input id="subjectInput" type="text" placeholder="Important Update from PakFlow" required />
        </div>

        <div class="field">
          <label for="messageInput">Message (HTML or Text)</label>
          <textarea id="messageInput" placeholder="Write your HTML or plain text message here..." required></textarea>
        </div>

        <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
          <button type="submit" class="btn-primary" id="sendBtn">Send Email(s)</button>
          <button type="button" class="btn-ghost" id="previewBtn">Toggle HTML Preview</button>
        </div>
      </form>

      <div id="previewBox" class="preview-box hidden"></div>
    </div>
  </div>

  <script>
    (function () {
      const loginCard = document.getElementById('adminLoginCard');
      const composerCard = document.getElementById('composerCard');
      const alertBox = document.getElementById('alertBox');
      const logoutBtn = document.getElementById('adminLogoutBtn');
      const targetSelect = document.getElementById('targetSelect');
      const customToField = document.getElementById('customToField');
      const previewBtn = document.getElementById('previewBtn');
      const previewBox = document.getElementById('previewBox');

      function getToken() {
        return localStorage.getItem('pakflow_admin_token') || localStorage.getItem('adminToken') || '';
      }

      function showAlert(msg, ok) {
        alertBox.textContent = msg;
        alertBox.className = 'alert ' + (ok ? 'ok' : 'err');
      }

      function showComposer() {
        loginCard.classList.add('hidden');
        composerCard.classList.remove('hidden');
        logoutBtn.classList.remove('hidden');
      }

      function showLogin() {
        composerCard.classList.add('hidden');
        logoutBtn.classList.add('hidden');
        loginCard.classList.remove('hidden');
      }

      targetSelect.addEventListener('change', () => {
        if (targetSelect.value === 'custom') {
          customToField.classList.remove('hidden');
        } else {
          customToField.classList.add('hidden');
        }
      });

      previewBtn.addEventListener('click', () => {
        previewBox.classList.toggle('hidden');
        if (!previewBox.classList.contains('hidden')) {
          const msg = document.getElementById('messageInput').value;
          previewBox.innerHTML = document.getElementById('formatSelect').value === 'html'
            ? msg
            : msg.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/\n/g, '<br>');
        }
      });

      document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        alertBox.className = 'alert hidden';
        const email = document.getElementById('admEmail').value.trim();
        const password = document.getElementById('admPass').value;
        const res = await fetch('/api/admin/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email, password }),
        });
        const data = await res.json();
        if (res.ok && data.token) {
          localStorage.setItem('pakflow_admin_token', data.token);
          showComposer();
        } else {
          showAlert(data.message || 'Invalid admin credentials', false);
        }
      });

      logoutBtn.addEventListener('click', () => {
        localStorage.removeItem('pakflow_admin_token');
        showLogin();
      });

      document.getElementById('sendEmailForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        alertBox.className = 'alert hidden';
        const sendBtn = document.getElementById('sendBtn');
        sendBtn.disabled = true;
        sendBtn.textContent = 'Sending...';
        try {
          const res = await fetch('/api/admin/send-email', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer ' + getToken(),
            },
            body: JSON.stringify({
              target: targetSelect.value,
              to: document.getElementById('toInput').value.trim(),
              format: document.getElementById('formatSelect').value,
              subject: document.getElementById('subjectInput').value.trim(),
              message: document.getElementById('messageInput').value,
            }),
          });
          const data = await res.json();
          if (res.status === 401 || res.status === 403) {
            showLogin();
            showAlert('Admin session expired. Please sign in again.', false);
          } else if (res.ok && data.success) {
            showAlert(data.message || 'Emails sent!', true);
          } else {
            showAlert(data.message || 'Failed to send emails', false);
          }
        } catch (_) {
          showAlert('Network error while sending emails.', false);
        } finally {
          sendBtn.disabled = false;
          sendBtn.textContent = 'Send Email(s)';
        }
      });

      if (getToken()) {
        showComposer();
      } else {
        showLogin();
      }
    })();
  </script>
</body>
</html>
