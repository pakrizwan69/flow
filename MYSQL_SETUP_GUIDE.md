# PakFlow MySQL Setup Guide (`flowbypak.com` — Hostinger)

All users, Google Flow cookies/slots, Admin logins, Resellers, Reseller Payments, Sessions, Signups, Password Resets, and Settings now run on **MySQL (PDO InnoDB)** automatically once `.env` is configured.  
*(If `DB_NAME` is empty on local dev, the app automatically falls back to `data/*.json`.)*

---

## Step 1: Create MySQL Database on Hostinger
1. Login to **Hostinger hPanel** -> open your website **`flowbypak.com`**.
2. Go to **Databases -> Management**.
3. Under **Create a New MySQL Database And Database User**:
   - **Database Name**: e.g. `u123456789_pakflow`
   - **Username**: e.g. `u123456789_pakflow`
   - **Password**: choose a strong password and copy all three values.
4. Click **Create**.

---

## Step 2: Import Tables in phpMyAdmin (Optional — Step 4 also creates them automatically)
1. In Hostinger **Databases -> Management**, click **Enter phpMyAdmin** next to your new database.
2. Open the **Import** tab -> choose `database/schema.sql` from your project -> click **Import**.
   - This creates all 10 tables:
     - `admins` (Admin login credentials)
     - `users` (1,705+ users, single-device `session_version`, plans, expiry)
     - `cookie_slots` (Google Flow Server 1–10 & slots cookies + instant `version_ts`)
     - `resellers` & `reseller_payments`
     - `deleted_users`, `app_settings`, `sessions_by_ip`, `pending_signups`, `password_resets`

---

## Step 3: Create `.env` on Hostinger (`public_html` / `flowbypak.com` root)
Copy `.env.example` to `.env` inside the root folder of `flowbypak.com` (next to `index.php`) and fill in your Hostinger credentials:

```env
APP_ENV=production
APP_URL=https://flowbypak.com
JWT_SECRET=put-any-long-random-secret-string-here

DB_HOST=localhost
DB_PORT=3306
DB_NAME=u123456789_pakflow
DB_USER=u123456789_pakflow
DB_PASS=YourStrongMySQLPasswordHere
DB_CHARSET=utf8mb4

MIGRATE_KEY=my-one-time-setup-secret-9988
```

*(Never share `.env` publicly; `.htaccess` already blocks direct browser access to `.env` and `database/`.)*

---

## Step 4: Migrate All Existing Data (`data/*.json` -> MySQL) in 1 Click
Keep your `data/` folder uploaded along with the code, then run **either**:

- **Option A (Browser — easiest on Hostinger Shared)**:
  Open this URL once in your browser:
  `https://flowbypak.com/api/admin/migrate-mysql?key=my-one-time-setup-secret-9988`

  You will see JSON confirming all imported counts (`admins`, `users`, `resellers`, `reseller_payments`, `cookie_slots`, `app_settings`, etc.).

- **Option B (Hostinger SSH Terminal)**:
  ```bash
  php database/migrate.php
  ```

---

## Step 5: Remove `MIGRATE_KEY` & Verify
1. Edit `.env` on Hostinger and clear `MIGRATE_KEY=` (leave empty) so the public setup key can no longer be called without Admin login.
2. Open **`https://flowbypak.com/admin`** and login (`pakflow@gmail.com` / `admin@123`, stored in MySQL `admins` table — you can update your Admin email/password in phpMyAdmin using SHA-256 hash or via SQL if desired).
3. Test User login, Reseller login, and switching Google Flow servers (`Session 1`, `Session 2`, ...).
