<?php
declare(strict_types=1);

namespace App;

/**
 * Idempotent JSON-to-MySQL schema creator and data importer.
 * Run via CLI (`php database/migrate.php`) or guarded HTTP endpoint `/api/admin/migrate-mysql`.
 */
function runMysqlMigration(?string $rootDir = null): array
{
    $root = $rootDir ?? dirname(__DIR__);
    require_once $root . '/src/Env.php';
    require_once $root . '/src/Http.php';
    require_once $root . '/src/Database.php';
    require_once $root . '/src/Db.php';
    require_once $root . '/src/Helpers.php';

    Env::load($root);

    if (!Database::isEnabled()) {
        throw new \RuntimeException('MySQL is not configured. Please set DB_HOST, DB_NAME, DB_USER, and DB_PASS in .env');
    }

    $pdo = Database::pdo();

    // 1. Apply schema.sql safely statement by statement
    $schemaPath = $root . '/database/schema.sql';
    if (!is_file($schemaPath)) {
        throw new \RuntimeException('schema.sql not found at database/schema.sql');
    }
    $sqlRaw = (string)file_get_contents($schemaPath);
    $sqlNoComments = preg_replace('/^\s*--.*$/m', '', $sqlRaw) ?? $sqlRaw;
    $statements = array_filter(array_map('trim', explode(';', $sqlNoComments)));
    foreach ($statements as $stmtSql) {
        if ($stmtSql !== '') {
            $pdo->exec($stmtSql);
        }
    }

    $dataDir = $root . '/data';
    $readJson = static function (string $name, $default = []) use ($dataDir) {
        $p = $dataDir . '/' . $name;
        if (!is_file($p) || !is_readable($p)) {
            return $default;
        }
        $raw = @file_get_contents($p);
        if ($raw === false || $raw === '') {
            return $default;
        }
        $d = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $d : $default;
    };

    $counts = [];

    // 2. Seed default admin account into admins table
    $nowIso = Db::isoNow();
    $adminEmail = strtolower(Helpers::ADMIN_EMAIL);
    $adminStmt = $pdo->prepare(
        'INSERT IGNORE INTO admins (name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $adminStmt->execute([
        'Flow by Pak Administrator',
        $adminEmail,
        Db::hashPassword(Helpers::ADMIN_PASSWORD),
        'admin',
        $nowIso,
        $nowIso,
    ]);
    $counts['admins'] = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();

    // 3. Migrate users.json
    $users = $readJson('users.json', []);
    if (is_array($users) && !empty($users)) {
        // Preload any existing DB rows so removed users in the updated JSON snapshot are synced cleanly
        Db::readUsers(true);
        Db::writeUsers($users);
        $maxUid = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM users')->fetchColumn();
        if ($maxUid > 0) {
            $pdo->exec('ALTER TABLE users AUTO_INCREMENT = ' . ($maxUid + 1));
        }
    }
    $counts['users'] = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    // 4. Migrate resellers.json
    $resellers = $readJson('resellers.json', []);
    if (is_array($resellers) && !empty($resellers)) {
        Db::writeResellers($resellers);
        $maxRid = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM resellers')->fetchColumn();
        if ($maxRid > 0) {
            $pdo->exec('ALTER TABLE resellers AUTO_INCREMENT = ' . ($maxRid + 1));
        }
    }
    $counts['resellers'] = (int)$pdo->query('SELECT COUNT(*) FROM resellers')->fetchColumn();

    // 5. Migrate reseller_payments.json
    $payments = $readJson('reseller_payments.json', []);
    if (is_array($payments) && !empty($payments)) {
        Db::writeResellerPayments($payments);
        $maxPid = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM reseller_payments')->fetchColumn();
        if ($maxPid > 0) {
            $pdo->exec('ALTER TABLE reseller_payments AUTO_INCREMENT = ' . ($maxPid + 1));
        }
    }
    $counts['reseller_payments'] = (int)$pdo->query('SELECT COUNT(*) FROM reseller_payments')->fetchColumn();

    // 6. Migrate slots.json (cookie slots)
    $slots = $readJson('slots.json', []);
    if (is_array($slots) && !empty($slots)) {
        Db::writeSlots($slots);
    } else {
        Db::readSlots();
    }
    $counts['cookie_slots'] = (int)$pdo->query('SELECT COUNT(*) FROM cookie_slots')->fetchColumn();

    // 7. Migrate settings.json + metadata
    $settings = $readJson('settings.json', null);
    if (is_array($settings)) {
        Db::writeSettings(array_replace(Db::defaultSettings(), $settings));
    } else {
        Db::readSettings();
    }
    $rrFile = $dataDir . '/nano_rr_counter.txt';
    if (is_file($rrFile)) {
        Db::setMetaValue('nano_rr_counter', (int)@file_get_contents($rrFile));
    }
    $expRunFile = $dataDir . '/expiry_last_run.txt';
    if (is_file($expRunFile)) {
        Db::setMetaValue('expiry_last_run', (int)@file_get_contents($expRunFile));
    }
    $expLog = $readJson('expiry_email_log.json', null);
    if (is_array($expLog)) {
        Db::setMetaValue('expiry_email_log', $expLog);
    }
    $counts['app_settings'] = (int)$pdo->query('SELECT COUNT(*) FROM app_settings')->fetchColumn();

    // 8. Migrate deleted_users.json
    $deleted = $readJson('deleted_users.json', ['ids' => [], 'emails' => []]);
    $delStmt = $pdo->prepare('INSERT IGNORE INTO deleted_users (entry_type, entry_val) VALUES (?, ?)');
    foreach (($deleted['ids'] ?? []) as $did => $_ts) {
        $delStmt->execute(['id', (string)$did]);
    }
    foreach (($deleted['emails'] ?? []) as $demail => $_ts) {
        $cleanE = Db::sanitizeEmail((string)$demail);
        if ($cleanE !== '') {
            $delStmt->execute(['email', $cleanE]);
        }
    }
    $counts['deleted_users'] = (int)$pdo->query('SELECT COUNT(*) FROM deleted_users')->fetchColumn();

    // 9. Migrate sessions_by_ip.json
    $sessions = $readJson('sessions_by_ip.json', []);
    if (is_array($sessions) && !empty($sessions)) {
        $sesStmt = $pdo->prepare('INSERT INTO sessions_by_ip (session_key, user_id, email, plan, locked, updated_at_ms) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), email=VALUES(email), plan=VALUES(plan), locked=VALUES(locked), updated_at_ms=VALUES(updated_at_ms)');
        foreach ($sessions as $key => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $uid = isset($entry['userId']) && is_numeric($entry['userId']) ? (int)$entry['userId'] : null;
            $sesStmt->execute([
                substr((string)$key, 0, 255),
                $uid,
                isset($entry['email']) ? (string)$entry['email'] : null,
                isset($entry['plan']) ? (string)$entry['plan'] : 'basic',
                !empty($entry['locked']) ? 1 : 0,
                (int)($entry['updatedAt'] ?? round(microtime(true) * 1000)),
            ]);
        }
    }
    $counts['sessions_by_ip'] = (int)$pdo->query('SELECT COUNT(*) FROM sessions_by_ip')->fetchColumn();

    // 10. Migrate pending_signups.json & password_resets.json
    $pending = $readJson('pending_signups.json', []);
    if (is_array($pending)) {
        foreach ($pending as $em => $row) {
            if (is_array($row) && ($row['expiresAt'] ?? 0) >= time()) {
                Db::savePendingSignup(Db::sanitizeEmail((string)$em), $row);
            }
        }
    }
    $resets = $readJson('password_resets.json', []);
    if (is_array($resets)) {
        foreach ($resets as $tok => $row) {
            if (is_array($row) && ($row['expiresAt'] ?? 0) >= time() && !empty($row['email'])) {
                Db::savePasswordReset((string)$tok, Db::sanitizeEmail((string)$row['email']), (int)$row['expiresAt']);
            }
        }
    }

    return [
        'success' => true,
        'message' => 'All JSON data migrated into MySQL successfully.',
        'counts' => $counts,
    ];
}

/**
 * Self-healing bootstrap: creates missing schema tables on SQLSTATE 42S02
 * and seeds JSON data only if users table is absent or empty.
 */
function ensureSchemaAndSeedIfEmpty(?string $rootDir = null): array
{
    $root = $rootDir ?? dirname(__DIR__);
    require_once $root . '/src/Env.php';
    require_once $root . '/src/Database.php';
    require_once $root . '/src/Db.php';
    require_once $root . '/src/Helpers.php';

    Env::load($root);
    if (!Database::isEnabled()) {
        return ['success' => false, 'message' => 'MySQL disabled'];
    }

    $lockKey = md5(Env::get('DB_NAME', 'default'));
    $lockPath = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'pf_mysql_init_' . $lockKey . '.lock';
    $lockFp = @fopen($lockPath, 'c+');
    if ($lockFp) {
        @flock($lockFp, LOCK_EX);
    }

    try {
        $pdo = Database::pdo();
        $hasUsersTable = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn() !== false;
        $userCount = $hasUsersTable ? (int)$pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn() : 0;

        if (!$hasUsersTable || $userCount === 0) {
            $res = runMysqlMigration($root);
        } else {
            // Existing users present: apply only missing CREATE TABLE IF NOT EXISTS statements without overwriting live users
            $schemaPath = $root . '/database/schema.sql';
            if (is_file($schemaPath)) {
                $sqlRaw = (string)file_get_contents($schemaPath);
                $sqlNoComments = preg_replace('/^\s*--.*$/m', '', $sqlRaw) ?? $sqlRaw;
                $statements = array_filter(array_map('trim', explode(';', $sqlNoComments)));
                foreach ($statements as $stmtSql) {
                    if ($stmtSql !== '') {
                        $pdo->exec($stmtSql);
                    }
                }
            }
            if ((int)$pdo->query('SELECT COUNT(*) FROM `admins`')->fetchColumn() === 0) {
                $nowIso = Db::isoNow();
                $pdo->prepare('INSERT IGNORE INTO admins (name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)')
                    ->execute(['Flow by Pak Administrator', strtolower(Helpers::ADMIN_EMAIL), Db::hashPassword(Helpers::ADMIN_PASSWORD), 'admin', $nowIso, $nowIso]);
            }
            if ((int)$pdo->query('SELECT COUNT(*) FROM `app_settings`')->fetchColumn() === 0) {
                $sPath = $root . '/data/settings.json';
                $sRaw = is_file($sPath) ? json_decode((string)@file_get_contents($sPath), true) : null;
                Db::writeSettings(is_array($sRaw) ? array_replace(Db::defaultSettings(), $sRaw) : Db::defaultSettings());
            }
            if ((int)$pdo->query('SELECT COUNT(*) FROM `cookie_slots`')->fetchColumn() === 0) {
                $slPath = $root . '/data/slots.json';
                $slRaw = is_file($slPath) ? json_decode((string)@file_get_contents($slPath), true) : null;
                if (is_array($slRaw) && !empty($slRaw)) {
                    Db::writeSlots($slRaw);
                } else {
                    Db::readSlots();
                }
            }
            $res = ['success' => true, 'message' => 'Missing schema tables created without modifying existing users.'];
        }
        Db::clearCaches();
        return $res;
    } finally {
        if ($lockFp) {
            @flock($lockFp, LOCK_UN);
            @fclose($lockFp);
        }
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        $result = runMysqlMigration();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    } catch (\Throwable $e) {
        fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . PHP_EOL);
        exit(1);
    }
}
