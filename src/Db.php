<?php
declare(strict_types=1);

namespace App;

require_once __DIR__ . '/Database.php';

final class Db
{
    private static ?array $usersCache = null;
    private static ?array $usersCompactCache = null;
    private static ?array $usersIndexesCache = null;
    private static array $mysqlUserSnapshots = [];
    private static string $usersCacheSig = '';
    private static ?array $settingsCache = null;
    private static ?array $resellersCache = null;
    private static ?array $resellerPaymentsCache = null;
    private static ?array $promptsCache = null;
    private static ?array $toolCookiesCache = null;
    private static ?array $slotsCache = null;
    private static ?array $deletedUsersCache = null;
    private static int $dbRevision = 1;
    private static int $promptsRevision = 1;

    public static function dataDir(): string
    {
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'data';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    public static function getDbRevision(): int
    {
        return self::$dbRevision;
    }

    public static function getPromptsRevision(): int
    {
        return self::$promptsRevision;
    }

    public static function clearCaches(): void
    {
        self::$usersCache = null;
        self::$usersCompactCache = null;
        self::$usersIndexesCache = null;
        self::$mysqlUserSnapshots = [];
        self::$usersCacheSig = '';
        self::$settingsCache = null;
        self::$resellersCache = null;
        self::$resellerPaymentsCache = null;
        self::$promptsCache = null;
        self::$toolCookiesCache = null;
        self::$slotsCache = null;
        self::$deletedUsersCache = null;
        self::$dbRevision++;
    }

    public static function invalidateRuntimeCaches(bool $includeSettings = true, bool $includeVersion = true): void
    {
        $dir = self::dataDir();
        if ($includeVersion) {
            @unlink($dir . DIRECTORY_SEPARATOR . 'version_cache.json');
        }
        if ($includeSettings) {
            @unlink($dir . DIRECTORY_SEPARATOR . 'settings_cache.json');
        }
    }

    public static function isoNow(?int $offsetSeconds = null): string
    {
        $ts = time() + ($offsetSeconds ?? 0);
        return gmdate('Y-m-d\TH:i:s', $ts) . '.000Z';
    }

    private static function readJsonFile(string $filePath, $default = [])
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            return $default;
        }
        $raw = @file_get_contents($filePath);
        if ($raw === false || $raw === '') {
            return $default;
        }
        $decoded = json_decode($raw, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
    }

    private static function writeFileAtomic(string $filePath, $data, bool $pretty = false): bool
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if ($pretty) {
            $flags |= JSON_PRETTY_PRINT;
        }
        $payload = json_encode($data, $flags);
        if ($payload === false) {
            return false;
        }
        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $tmpPath = $filePath . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $written = @file_put_contents($tmpPath, $payload, LOCK_EX);
        if ($written === false) {
            @unlink($tmpPath);
            return @file_put_contents($filePath, $payload, LOCK_EX) !== false;
        }
        if (!@rename($tmpPath, $filePath)) {
            @unlink($tmpPath);
            return @file_put_contents($filePath, $payload, LOCK_EX) !== false;
        }
        return true;
    }

    public static function sanitizeCredential($val): string
    {
        if ($val === null) {
            return '';
        }
        $str = (string)$val;
        if ($str === '' || !preg_match('/[\x80-\xFF]/', $str)) {
            return trim($str);
        }
        $str = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', '', $str) ?? $str;
        $str = str_replace("\u{00A0}", ' ', $str);
        return trim($str);
    }

    public static function sanitizeEmail($val): string
    {
        if ($val === null) {
            return '';
        }
        $str = (string)$val;
        if ($str === '' || !preg_match('/[\s\x80-\xFF]/', $str)) {
            return strtolower($str);
        }
        $str = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}\x{00A0}\s]/u', '', $str) ?? $str;
        return strtolower($str);
    }

    private static function deletedUsersFile(): string
    {
        return self::dataDir() . DIRECTORY_SEPARATOR . 'deleted_users.json';
    }

    private static function readDeletedUserTracker(): array
    {
        if (self::$deletedUsersCache !== null) {
            return self::$deletedUsersCache;
        }
        if (Database::isEnabled()) {
            $rows = Database::pdo()->query('SELECT entry_type, entry_val FROM deleted_users')->fetchAll();
            $ids = [];
            $emails = [];
            foreach ($rows as $r) {
                if (($r['entry_type'] ?? '') === 'id') {
                    $ids[(string)$r['entry_val']] = 1;
                } elseif (($r['entry_type'] ?? '') === 'email') {
                    $emails[(string)$r['entry_val']] = 1;
                }
            }
            self::$deletedUsersCache = ['ids' => $ids, 'emails' => $emails];
            return self::$deletedUsersCache;
        }
        $file = self::deletedUsersFile();
        if (!is_file($file)) {
            self::$deletedUsersCache = ['ids' => [], 'emails' => []];
            return self::$deletedUsersCache;
        }
        $parsed = self::readJsonFile($file, []);
        self::$deletedUsersCache = [
            'ids' => is_array($parsed['ids'] ?? null) ? $parsed['ids'] : [],
            'emails' => is_array($parsed['emails'] ?? null) ? $parsed['emails'] : [],
        ];
        return self::$deletedUsersCache;
    }

    public static function markUserDeleted($userId, string $email = ''): void
    {
        $cleanE = self::sanitizeEmail($email);
        if (Database::isEnabled()) {
            $stmt = Database::pdo()->prepare('INSERT IGNORE INTO deleted_users (entry_type, entry_val) VALUES (?, ?)');
            if ($userId !== null && $userId !== '') {
                $stmt->execute(['id', (string)$userId]);
            }
            if ($cleanE !== '') {
                $stmt->execute(['email', $cleanE]);
            }
            self::$deletedUsersCache = null;
            return;
        }
        $tracker = self::readDeletedUserTracker();
        $now = (int)round(microtime(true) * 1000);
        if ($userId !== null && $userId !== '') {
            $tracker['ids'][(string)$userId] = $now;
        }
        if ($cleanE !== '') {
            $tracker['emails'][$cleanE] = $now;
        }
        self::$deletedUsersCache = $tracker;
        self::writeFileAtomic(self::deletedUsersFile(), $tracker, false);
    }

    public static function unmarkUserDeleted(string $email = '', $userId = null): void
    {
        $cleanE = self::sanitizeEmail($email);
        if (Database::isEnabled()) {
            $stmt = Database::pdo()->prepare('DELETE FROM deleted_users WHERE entry_type = ? AND entry_val = ?');
            if ($cleanE !== '') {
                $stmt->execute(['email', $cleanE]);
            }
            if ($userId !== null && $userId !== '') {
                $stmt->execute(['id', (string)$userId]);
            }
            self::$deletedUsersCache = null;
            return;
        }
        $tracker = self::readDeletedUserTracker();
        $changed = false;
        if ($cleanE !== '' && isset($tracker['emails'][$cleanE])) {
            unset($tracker['emails'][$cleanE]);
            $changed = true;
        }
        if ($userId !== null && $userId !== '' && isset($tracker['ids'][(string)$userId])) {
            unset($tracker['ids'][(string)$userId]);
            $changed = true;
        }
        if ($changed) {
            self::$deletedUsersCache = $tracker;
            self::writeFileAtomic(self::deletedUsersFile(), $tracker, false);
        }
    }

    public static function hashPassword(string $password): string
    {
        return hash('sha256', $password);
    }

    public static function verifyPassword($password, ?string $storedHash, string $plainPasswordHint = '', string $userEmail = ''): bool
    {
        if ($password === null) {
            return false;
        }
        $raw = (string)$password;
        $clean = self::sanitizeCredential($raw);
        $cleanNoSpaces = preg_replace('/\s+/u', '', $clean) ?? $clean;
        if ($clean === '' && $raw === '') {
            return false;
        }

        $candidates = array_unique([
            $raw,
            $clean,
            $cleanNoSpaces,
            strtolower($clean),
            strtolower($cleanNoSpaces),
            strtoupper($clean),
        ]);
        if (strlen($clean) > 0) {
            $candidates[] = strtoupper($clean[0]) . substr($clean, 1);
            $candidates[] = strtolower($clean[0]) . substr($clean, 1);
            $candidates[] = strtoupper($clean[0]) . strtolower(substr($clean, 1));
            $candidates = array_unique($candidates);
        }

        if ($storedHash !== null && $storedHash !== '') {
            foreach ($candidates as $cand) {
                if ($cand === '') {
                    continue;
                }
                if ($cand === $storedHash || self::hashPassword($cand) === $storedHash) {
                    return true;
                }
            }
        }

        if ($plainPasswordHint !== '') {
            $cleanHint = self::sanitizeCredential($plainPasswordHint);
            $cleanHintNoSpaces = preg_replace('/\s+/u', '', $cleanHint) ?? $cleanHint;
            if ($cleanHint !== '') {
                if (
                    $clean === $cleanHint ||
                    strtolower($clean) === strtolower($cleanHint) ||
                    ($cleanNoSpaces !== '' && strtolower($cleanNoSpaces) === strtolower($cleanHintNoSpaces))
                ) {
                    return true;
                }
            }
        }

        if ($userEmail !== '') {
            $cleanUserEmail = self::sanitizeEmail($userEmail);
            $cleanHintEmail = self::sanitizeEmail($plainPasswordHint);
            $upperFirst = $cleanUserEmail !== '' ? strtoupper($cleanUserEmail[0]) . substr($cleanUserEmail, 1) : '';
            if (
                $cleanUserEmail !== '' &&
                (
                    $cleanHintEmail === $cleanUserEmail ||
                    ($storedHash && ($storedHash === self::hashPassword($cleanUserEmail) || $storedHash === self::hashPassword($upperFirst)))
                )
            ) {
                if (strtolower($cleanNoSpaces) === $cleanUserEmail) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function usersFile(): string
    {
        return self::dataDir() . DIRECTORY_SEPARATOR . 'users.json';
    }

    private static function usersBackupFile(): string
    {
        return self::dataDir() . DIRECTORY_SEPARATOR . 'users.live_backup.json';
    }

    private static function usersCacheFile(): string
    {
        return self::dataDir() . DIRECTORY_SEPARATOR . '.users_cache.ser';
    }

    private static function fileSig(string $filePath): string
    {
        clearstatcache(true, $filePath);
        $mtime = @filemtime($filePath);
        $size = @filesize($filePath);
        return ($mtime === false ? '0' : (string)$mtime) . ':' . ($size === false ? '0' : (string)$size);
    }

    private static function buildCompactIndexes(array $users): array
    {
        $byId = [];
        $byEmail = [];
        foreach ($users as $i => $u) {
            if (!is_array($u)) {
                continue;
            }
            if (isset($u['id']) && $u['id'] !== '') {
                $idKey = (string)$u['id'];
                if (!isset($byId[$idKey])) {
                    $byId[$idKey] = (int)$i;
                }
            }
            $cleanE = self::sanitizeEmail($u['email'] ?? '');
            if ($cleanE !== '' && !isset($byEmail[$cleanE])) {
                $byEmail[$cleanE] = (int)$i;
            }
        }
        return ['byId' => $byId, 'byEmail' => $byEmail];
    }

    private static function writeUsersBinaryCache(string $sig, array $users, array $compact): void
    {
        $cacheFile = self::usersCacheFile();
        $tmpPath = $cacheFile . '.' . getmypid() . '.' . bin2hex(random_bytes(3)) . '.tmp';
        $payload = @serialize([
            'sig' => $sig,
            'users' => $users,
            'compact' => $compact,
        ]);
        if ($payload === false) {
            return;
        }
        if (@file_put_contents($tmpPath, $payload, LOCK_EX) !== false) {
            if (!@rename($tmpPath, $cacheFile)) {
                @unlink($tmpPath);
            }
        }
    }

    public static function getUserCompactIndexes(?array $usersList = null): array
    {
        if ($usersList === null) {
            self::readUsers();
            if (self::$usersCompactCache !== null) {
                return self::$usersCompactCache;
            }
        }
        if ($usersList !== null && self::$usersCompactCache !== null && self::$usersCache !== null && $usersList === self::$usersCache) {
            return self::$usersCompactCache;
        }
        return self::buildCompactIndexes($usersList ?? self::readUsers());
    }

    private static function rowToUser(array $row): array
    {
        $extra = !empty($row['extra_json']) ? json_decode((string)$row['extra_json'], true) : [];
        if (!is_array($extra)) {
            $extra = [];
        }
        $base = [
            'id' => (int)$row['id'],
            'name' => (string)($row['name'] ?? ''),
            'username' => (string)($row['username'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
            'passwordHash' => (string)($row['password_hash'] ?? ''),
            'plainPasswordHint' => $row['plain_password_hint'],
            'plan' => (string)($row['plan'] ?? 'basic'),
            'credits' => (int)($row['credits'] ?? 0),
            'creditsTotal' => (int)($row['credits_total'] ?? 0),
            'planExpiresAt' => $row['plan_expires_at'],
            'daysRemaining' => (int)($row['days_remaining'] ?? 0),
            'status' => (string)($row['status'] ?? 'active'),
            'sessionVersion' => (int)($row['session_version'] ?? 1),
            'activeDeviceId' => $row['active_device_id'],
            'lastLoginAt' => $row['last_login_at'],
            'lastLoginIp' => $row['last_login_ip'],
            'isLocked' => !empty($row['is_locked']),
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
        if (isset($row['reseller_id']) && $row['reseller_id'] !== null && $row['reseller_id'] !== '') {
            $base['resellerId'] = (int)$row['reseller_id'];
        }
        return array_merge($extra, $base);
    }

    private static function userToDbParams(array $u): array
    {
        $coreKeys = [
            'id', 'name', 'username', 'email', 'passwordHash', 'plainPasswordHint',
            'plan', 'credits', 'creditsTotal', 'planExpiresAt', 'daysRemaining', 'status',
            'sessionVersion', 'activeDeviceId', 'lastLoginAt', 'lastLoginIp',
            'isLocked', 'resellerId', 'createdAt', 'updatedAt',
        ];
        $extra = [];
        foreach ($u as $k => $v) {
            if (!in_array($k, $coreKeys, true)) {
                $extra[$k] = $v;
            }
        }
        $rid = isset($u['resellerId']) && $u['resellerId'] !== null && $u['resellerId'] !== '' ? (int)$u['resellerId'] : null;
        return [
            (int)($u['id'] ?? 0),
            (string)($u['name'] ?? ''),
            (string)($u['username'] ?? ''),
            self::sanitizeEmail($u['email'] ?? ''),
            (string)($u['passwordHash'] ?? ''),
            isset($u['plainPasswordHint']) ? (string)$u['plainPasswordHint'] : null,
            (string)($u['plan'] ?? 'basic'),
            (int)($u['credits'] ?? 0),
            (int)($u['creditsTotal'] ?? 0),
            isset($u['planExpiresAt']) ? (string)$u['planExpiresAt'] : null,
            (int)($u['daysRemaining'] ?? 0),
            (string)($u['status'] ?? 'active'),
            (int)($u['sessionVersion'] ?? 1),
            isset($u['activeDeviceId']) ? (string)$u['activeDeviceId'] : null,
            isset($u['lastLoginAt']) ? (string)$u['lastLoginAt'] : null,
            isset($u['lastLoginIp']) ? (string)$u['lastLoginIp'] : null,
            !empty($u['isLocked']) ? 1 : 0,
            $rid,
            isset($u['createdAt']) ? (string)$u['createdAt'] : null,
            isset($u['updatedAt']) ? (string)$u['updatedAt'] : null,
            empty($extra) ? null : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    public static function upsertSingleUser(array $user): bool
    {
        if (!Database::isEnabled()) {
            return false;
        }
        $sql = 'INSERT INTO users (id, name, username, email, password_hash, plain_password_hint, plan, credits, credits_total, plan_expires_at, days_remaining, status, session_version, active_device_id, last_login_at, last_login_ip, is_locked, reseller_id, created_at, updated_at, extra_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  name = VALUES(name), username = VALUES(username), email = VALUES(email),
                  password_hash = VALUES(password_hash), plain_password_hint = VALUES(plain_password_hint),
                  plan = VALUES(plan), credits = VALUES(credits), credits_total = VALUES(credits_total),
                  plan_expires_at = VALUES(plan_expires_at), days_remaining = VALUES(days_remaining),
                  status = VALUES(status), session_version = VALUES(session_version),
                  active_device_id = VALUES(active_device_id), last_login_at = VALUES(last_login_at),
                  last_login_ip = VALUES(last_login_ip), is_locked = VALUES(is_locked),
                  reseller_id = VALUES(reseller_id), created_at = VALUES(created_at),
                  updated_at = VALUES(updated_at), extra_json = VALUES(extra_json)';
        $stmt = Database::pdo()->prepare($sql);
        $params = self::userToDbParams($user);
        $ok = $stmt->execute($params);
        $uid = (int)$params[0];
        if ($uid > 0) {
            self::$mysqlUserSnapshots[$uid] = $user;
            if (is_array(self::$usersCache)) {
                $replaced = false;
                foreach (self::$usersCache as $idx => $cu) {
                    if ((int)($cu['id'] ?? 0) === $uid) {
                        self::$usersCache[$idx] = $user;
                        $replaced = true;
                        break;
                    }
                }
                if (!$replaced) {
                    self::$usersCache[] = $user;
                }
                self::$usersCompactCache = null;
                self::$usersIndexesCache = null;
            }
        }
        self::$dbRevision++;
        return $ok;
    }

    public static function findUserByTokenMysql(?string $userId, string $cleanEmail): ?array
    {
        if (!Database::isEnabled()) {
            return null;
        }
        $pdo = Database::pdo();
        if ($userId !== null && $userId !== '' && ctype_digit((string)$userId)) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$userId]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                $u = self::rowToUser($row);
                if ($cleanEmail === '' || self::sanitizeEmail($u['email'] ?? '') === $cleanEmail) {
                    return $u;
                }
            }
        }
        if ($cleanEmail !== '') {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$cleanEmail]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                return self::rowToUser($row);
            }
        }
        if ($userId !== null && $userId !== '' && ctype_digit((string)$userId)) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([(int)$userId]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                return self::rowToUser($row);
            }
        }
        return null;
    }

    public static function findUserByIdMysql(int $userId): ?array
    {
        if (!Database::isEnabled() || $userId <= 0) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return is_array($row) ? self::rowToUser($row) : null;
    }

    public static function findUserByLoginIdentifierMysql($rawIdentifier): ?array
    {
        if (!Database::isEnabled()) {
            return null;
        }
        $cleanId = self::sanitizeEmail($rawIdentifier);
        if ($cleanId === '') {
            return null;
        }
        $pdo = Database::pdo();
        // 1. Exact email match (uses UNIQUE idx uniq_users_email)
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$cleanId]);
        $row = $stmt->fetch();
        if (is_array($row)) {
            return self::rowToUser($row);
        }
        // 2. Exact username match (uses idx_users_username)
        $stmt2 = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt2->execute([$cleanId]);
        $row = $stmt2->fetch();
        if (is_array($row)) {
            return self::rowToUser($row);
        }
        // 3. Local-part match (no @ provided)
        if (!str_contains($cleanId, '@')) {
            $stmt3 = $pdo->prepare('SELECT * FROM users WHERE email LIKE ? LIMIT 1');
            $stmt3->execute([$cleanId . '@%']);
            $row = $stmt3->fetch();
            if (is_array($row)) {
                return self::rowToUser($row);
            }
            return null;
        }
        // 4. Gmail dot-normalized fallback (e.g. a.b@gmail.com vs ab@gmail.com)
        $comparable = self::normalizeGmailKey($cleanId);
        if (str_ends_with($comparable, '@gmail.com')) {
            $stmt4 = $pdo->prepare("SELECT * FROM users WHERE email LIKE '%@gmail.com' OR email LIKE '%@googlemail.com'");
            $stmt4->execute();
            while ($r = $stmt4->fetch()) {
                $e = self::sanitizeEmail($r['email'] ?? '');
                if ($e !== '' && self::normalizeGmailKey($e) === $comparable) {
                    return self::rowToUser($r);
                }
            }
        }
        return null;
    }

    public static function deleteSingleUserMysql(int $userId): bool
    {
        if (!Database::isEnabled() || $userId <= 0) {
            return false;
        }
        $stmt = Database::pdo()->prepare('DELETE FROM users WHERE id = ?');
        $ok = $stmt->execute([$userId]);
        unset(self::$mysqlUserSnapshots[$userId]);
        if (is_array(self::$usersCache)) {
            self::$usersCache = array_values(array_filter(
                self::$usersCache,
                static fn($u): bool => (int)($u['id'] ?? 0) !== $userId
            ));
            self::$usersCompactCache = null;
            self::$usersIndexesCache = null;
        }
        self::$dbRevision++;
        return $ok;
    }

    public static function readUsersByResellerMysql(int $resellerId): array
    {
        if (!Database::isEnabled() || $resellerId <= 0) {
            return [];
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE reseller_id = ? ORDER BY id ASC');
        $stmt->execute([$resellerId]);
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[] = self::rowToUser($r);
        }
        return $out;
    }

    public static function readAllResellerUsersMysql(): array
    {
        if (!Database::isEnabled()) {
            return [];
        }
        $stmt = Database::pdo()->query('SELECT * FROM users WHERE reseller_id IS NOT NULL ORDER BY id ASC');
        $out = [];
        foreach ($stmt->fetchAll() as $r) {
            $out[] = self::rowToUser($r);
        }
        return $out;
    }

    public static function findResellerByIdMysql(int $resellerId): ?array
    {
        if (!Database::isEnabled() || $resellerId <= 0) {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM resellers WHERE id = ? LIMIT 1');
        $stmt->execute([$resellerId]);
        $r = $stmt->fetch();
        if (!is_array($r)) {
            return null;
        }
        $extra = !empty($r['extra_json']) ? json_decode((string)$r['extra_json'], true) : [];
        if (!is_array($extra)) {
            $extra = [];
        }
        return array_merge($extra, [
            'id' => (int)$r['id'],
            'name' => (string)$r['name'],
            'email' => (string)$r['email'],
            'passwordHash' => (string)$r['password_hash'],
            'plainPasswordHint' => $r['plain_password_hint'],
            'allowedUsers' => (int)$r['allowed_users'],
            'creditsQuota' => (int)$r['credits_quota'],
            'usersCreated' => (int)$r['users_created'],
            'totalPaid' => (int)$r['total_paid'],
            'deletedCharges' => (int)$r['deleted_charges'],
            'whatsapp' => (string)($r['whatsapp'] ?? ''),
            'status' => (string)($r['status'] ?? 'active'),
            'createdAt' => $r['created_at'],
        ]);
    }

    public static function countUsersByResellerMysql(int $resellerId): int
    {
        if (!Database::isEnabled() || $resellerId <= 0) {
            return 0;
        }
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM users WHERE reseller_id = ?');
        $stmt->execute([$resellerId]);
        return (int)$stmt->fetchColumn();
    }

    public static function nextUserIdMysql(): int
    {
        if (!Database::isEnabled()) {
            return 1;
        }
        return ((int)Database::pdo()->query('SELECT COALESCE(MAX(id), 0) FROM users')->fetchColumn()) + 1;
    }

    public static function readUsers(bool $forceRefresh = false): array
    {
        if (Database::isEnabled()) {
            if (!$forceRefresh && self::$usersCache !== null) {
                return self::$usersCache;
            }
            $rows = Database::pdo()->query('SELECT * FROM users ORDER BY id ASC')->fetchAll();
            $out = [];
            $snaps = [];
            foreach ($rows as $r) {
                $u = self::rowToUser($r);
                $out[] = $u;
                $snaps[(int)$u['id']] = $u;
            }
            self::$usersCache = $out;
            self::$mysqlUserSnapshots = $snaps;
            self::$usersCompactCache = null;
            self::$usersIndexesCache = null;
            self::$dbRevision++;
            return self::$usersCache;
        }

        $usersFile = self::usersFile();
        $backupFile = self::usersBackupFile();

        if (!is_file($usersFile)) {
            $initial = [
                [
                    'id' => 1,
                    'name' => 'Demo User',
                    'username' => 'demouser',
                    'email' => 'user@pakflow.com',
                    'passwordHash' => self::hashPassword('user123'),
                    'plan' => 'plus',
                    'credits' => 25000,
                    'creditsTotal' => 25000,
                    'planExpiresAt' => self::isoNow(30 * 86400),
                    'daysRemaining' => 30,
                    'status' => 'active',
                    'createdAt' => self::isoNow(),
                ],
            ];
            if (is_file($backupFile)) {
                $backup = self::readJsonFile($backupFile, null);
                if (is_array($backup) && count($backup) > 0) {
                    $initial = $backup;
                }
            }
            self::writeUsers($initial);
            return self::$usersCache;
        }

        if (!$forceRefresh && self::$usersCache !== null) {
            return self::$usersCache;
        }

        $sig = self::fileSig($usersFile);
        if (self::$usersCache !== null && self::$usersCacheSig === $sig) {
            return self::$usersCache;
        }

        // Fast compact binary cache avoids JSON decode & eager multi-map duplication on every request
        $cacheFile = self::usersCacheFile();
        if ($sig !== '0:0' && is_file($cacheFile) && is_readable($cacheFile)) {
            $rawSer = @file_get_contents($cacheFile);
            if ($rawSer !== false && $rawSer !== '') {
                $cached = @unserialize($rawSer, ['allowed_classes' => false]);
                if (
                    is_array($cached)
                    && ($cached['sig'] ?? '') === $sig
                    && is_array($cached['users'] ?? null)
                    && is_array($cached['compact'] ?? null)
                ) {
                    self::$usersCache = $cached['users'];
                    self::$usersCompactCache = $cached['compact'];
                    self::$usersIndexesCache = null;
                    self::$usersCacheSig = $sig;
                    self::$dbRevision++;
                    return self::$usersCache;
                }
            }
        }

        $parsed = self::readJsonFile($usersFile, null);
        if (!is_array($parsed)) {
            if (is_file($backupFile)) {
                $backup = self::readJsonFile($backupFile, null);
                if (is_array($backup) && count($backup) > 0) {
                    self::$usersCache = $backup;
                    self::$usersCompactCache = self::buildCompactIndexes($backup);
                    self::$usersIndexesCache = null;
                    self::$usersCacheSig = '';
                    return self::$usersCache;
                }
            }
            return self::$usersCache ?? [];
        }

        self::$usersCache = $parsed;
        self::$usersCompactCache = self::buildCompactIndexes($parsed);
        self::$usersIndexesCache = null;
        self::$usersCacheSig = $sig;
        self::$dbRevision++;
        self::writeUsersBinaryCache($sig, self::$usersCache, self::$usersCompactCache);
        return self::$usersCache;
    }

    public static function writeUsers(array $users, array $options = []): bool
    {
        $immediate = !empty($options['immediate']);
        $cleanList = array_values($users);
        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $sql = 'INSERT INTO users (id, name, username, email, password_hash, plain_password_hint, plan, credits, credits_total, plan_expires_at, days_remaining, status, session_version, active_device_id, last_login_at, last_login_ip, is_locked, reseller_id, created_at, updated_at, extra_json)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE
                      name = VALUES(name), username = VALUES(username), email = VALUES(email),
                      password_hash = VALUES(password_hash), plain_password_hint = VALUES(plain_password_hint),
                      plan = VALUES(plan), credits = VALUES(credits), credits_total = VALUES(credits_total),
                      plan_expires_at = VALUES(plan_expires_at), days_remaining = VALUES(days_remaining),
                      status = VALUES(status), session_version = VALUES(session_version),
                      active_device_id = VALUES(active_device_id), last_login_at = VALUES(last_login_at),
                      last_login_ip = VALUES(last_login_ip), is_locked = VALUES(is_locked),
                      reseller_id = VALUES(reseller_id), created_at = VALUES(created_at),
                      updated_at = VALUES(updated_at), extra_json = VALUES(extra_json)';
            $stmt = $pdo->prepare($sql);
            $newSnaps = [];
            $pdo->beginTransaction();
            try {
                foreach ($cleanList as $u) {
                    if (!is_array($u)) {
                        continue;
                    }
                    $uid = (int)($u['id'] ?? 0);
                    $newSnaps[$uid] = $u;
                    if (!isset(self::$mysqlUserSnapshots[$uid]) || self::$mysqlUserSnapshots[$uid] !== $u) {
                        $stmt->execute(self::userToDbParams($u));
                    }
                }
                if (!empty(self::$mysqlUserSnapshots)) {
                    $deletedIds = array_keys(array_diff_key(self::$mysqlUserSnapshots, $newSnaps));
                    if (!empty($deletedIds)) {
                        $placeholders = implode(',', array_fill(0, count($deletedIds), '?'));
                        $delStmt = $pdo->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                        $delStmt->execute(array_values(array_map('intval', $deletedIds)));
                    }
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
            self::$mysqlUserSnapshots = $newSnaps;
            self::$usersCache = $cleanList;
            self::$usersCompactCache = null;
            self::$usersIndexesCache = null;
            self::$dbRevision++;
            return true;
        }

        self::$usersCache = $cleanList;
        self::$usersCompactCache = self::buildCompactIndexes(self::$usersCache);
        self::$usersIndexesCache = null;
        self::$dbRevision++;
        $usersFile = self::usersFile();
        $ok = self::writeFileAtomic($usersFile, self::$usersCache, false);
        $sig = self::fileSig($usersFile);
        self::$usersCacheSig = $sig;
        if ($ok) {
            self::writeUsersBinaryCache($sig, self::$usersCache, self::$usersCompactCache);
        }
        if ($immediate) {
            self::writeFileAtomic(self::usersBackupFile(), self::$usersCache, false);
        }
        return $ok;
    }

    public static function normalizeGmailKey(string $cleanEmail): string
    {
        $clean = str_starts_with($cleanEmail, 'www.') ? substr($cleanEmail, 4) : $cleanEmail;
        $atIdx = strpos($clean, '@');
        if ($atIdx === false) {
            return $clean;
        }
        $local = substr($clean, 0, $atIdx);
        $domain = substr($clean, $atIdx + 1);
        if ($domain === 'gmail.com' || $domain === 'googlemail.com') {
            return str_replace('.', '', $local) . '@gmail.com';
        }
        return $local . '@' . $domain;
    }

    public static function getUserIndexes(?array $usersList = null): array
    {
        if ($usersList === null) {
            self::readUsers();
            if (self::$usersIndexesCache === null && self::$usersCache !== null) {
                self::$usersIndexesCache = self::buildUserIndexes(self::$usersCache);
            }
            return self::$usersIndexesCache ?? [];
        }
        if (self::$usersCache !== null && $usersList === self::$usersCache) {
            if (self::$usersIndexesCache === null) {
                self::$usersIndexesCache = self::buildUserIndexes(self::$usersCache);
            }
            return self::$usersIndexesCache;
        }
        return self::buildUserIndexes($usersList);
    }

    private static function buildUserIndexes(array $users): array
    {
        $byId = [];
        $byEmail = [];
        $byUsername = [];
        $byGmail = [];
        $byLocalPart = [];
        $byResellerId = [];
        $latestByIp = [];

        foreach ($users as $i => $u) {
            if (!is_array($u)) {
                continue;
            }
            $entry = ['user' => $u, 'index' => $i];

            if (isset($u['id']) && $u['id'] !== '') {
                $idKey = (string)$u['id'];
                if (!isset($byId[$idKey])) {
                    $byId[$idKey] = $entry;
                }
            }

            $cleanE = self::sanitizeEmail($u['email'] ?? '');
            if ($cleanE !== '') {
                if (!isset($byEmail[$cleanE])) {
                    $byEmail[$cleanE] = $entry;
                }
                $gmailKey = self::normalizeGmailKey($cleanE);
                if (!isset($byGmail[$gmailKey])) {
                    $byGmail[$gmailKey] = $entry;
                }
                $atIdx = strpos($cleanE, '@');
                $localPart = $atIdx !== false ? substr($cleanE, 0, $atIdx) : $cleanE;
                if ($localPart !== '' && !isset($byLocalPart[$localPart])) {
                    $byLocalPart[$localPart] = $entry;
                }
            }

            if (!empty($u['username'])) {
                $cleanU = strtolower(self::sanitizeCredential($u['username']));
                if ($cleanU !== '' && !isset($byUsername[$cleanU])) {
                    $byUsername[$cleanU] = $entry;
                }
            }

            if (isset($u['resellerId']) && $u['resellerId'] !== null) {
                $rKey = (string)$u['resellerId'];
                $byResellerId[$rKey][] = $u;
            }

            if (!empty($u['lastLoginIp']) && !empty($u['lastLoginAt'])) {
                $ts = strtotime((string)$u['lastLoginAt']) ?: 0;
                $ip = (string)$u['lastLoginIp'];
                if (!isset($latestByIp[$ip]) || $ts > $latestByIp[$ip]['ts']) {
                    $latestByIp[$ip] = ['user' => $u, 'ts' => $ts];
                }
            }
        }

        return [
            'byId' => $byId,
            'byEmail' => $byEmail,
            'byUsername' => $byUsername,
            'byGmail' => $byGmail,
            'byLocalPart' => $byLocalPart,
            'byResellerId' => $byResellerId,
            'latestByIp' => $latestByIp,
        ];
    }

    public static function calculateDaysRemaining(?string $expiresAt): int
    {
        if (!$expiresAt) {
            return 0;
        }
        $exp = strtotime($expiresAt);
        $now = time();
        if ($exp === false || $exp <= $now) {
            return 0;
        }
        return max(0, (int)ceil(($exp - $now) / 86400));
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data)
    {
        $pad = strlen($data) % 4;
        if ($pad > 0) {
            $data .= str_repeat('=', 4 - $pad);
        }
        return base64_decode(strtr($data, '-_', '+/'), true);
    }

    private static function jwtSecret(): string
    {
        return Env::get('JWT_SECRET', 'pakflow_super_secret_production_key_2026');
    }

    public static function generateToken(array $payload): string
    {
        $header = self::base64UrlEncode((string)json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $now = time();
        $bodyArr = array_merge($payload, [
            'iat' => $now,
            'exp' => $now + 3650 * 24 * 3600,
        ]);
        $body = self::base64UrlEncode((string)json_encode($bodyArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $sig = self::base64UrlEncode(hash_hmac('sha256', "$header.$body", self::jwtSecret(), true));
        return "$header.$body.$sig";
    }

    public static function verifyToken(?string $token): ?array
    {
        if (!$token || !is_string($token)) {
            return null;
        }
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$header, $body, $signature] = $parts;
        $expectedSig = self::base64UrlEncode(hash_hmac('sha256', "$header.$body", self::jwtSecret(), true));
        if (!hash_equals($expectedSig, $signature)) {
            return null;
        }
        $rawJson = self::base64UrlDecode($body);
        if ($rawJson === false) {
            return null;
        }
        $decoded = json_decode($rawJson, true);
        return is_array($decoded) ? $decoded : null;
    }

    public static function defaultSettings(): array
    {
        $now = self::isoNow();
        return [
            'brandName' => 'Flow by Pak',
            'supportEmail' => 'flowbypak.site@gmail.com',
            'whatsappChannel' => 'https://whatsapp.com/channel/0029Vb8c59QGehEJnsmNKd23',
            'whatsappPhone' => 'https://whatsapp.com/channel/0029Vb8c59QGehEJnsmNKd23',
            'allowPublicRegistration' => true,
            'broadcast' => [
                'enabled' => true,
                'title' => 'âš¡ Welcome to Flow by Pak!',
                'message' => 'Download the official Chrome Extension for 1-click auto-login and seamless video generation.',
                'type' => 'info',
                'updatedAt' => $now,
            ],
            'windowsApp' => [
                'title' => 'Pak Flow Chrome Extension',
                'fileName' => 'PakFlow_v1.7.zip',
                'version' => '1.7',
                'fileSize' => '829 KB',
                'downloadUrl' => '/downloads/PakFlow_v1.7.zip',
                'updatedAt' => $now,
                'instructions' => "1. Download PakFlow_v1.7.zip\n2. Extract it to a folder on your PC\n3. Go to chrome://extensions and enable Developer mode\n4. Click Load unpacked and select the extracted folder",
            ],
            'plans' => [
                'vibe' => ['name' => 'Vibe', 'price' => 1500, 'credits' => 10000, 'visible' => true, 'description' => 'Budget friendly Veo 3.1 Lite model'],
                'plus' => ['name' => 'Plus', 'price' => 2500, 'credits' => 25000, 'visible' => true, 'description' => 'Most popular: 25,000 credits & single device lock'],
                'pro' => ['name' => 'Pro', 'price' => 3500, 'credits' => 35000, 'visible' => true, 'description' => 'High-speed Google Flow rendering & 35,000 credits'],
                'max' => ['name' => 'Max', 'price' => 5000, 'credits' => 50000, 'visible' => true, 'description' => 'Heavy creators: 50,000 credits & VIP priority queue'],
                'ultra' => ['name' => 'Ultra', 'price' => 8000, 'credits' => 100000, 'visible' => true, 'description' => 'Ultimate studio power: 100,000 credits & personal support'],
                'heavy' => ['name' => 'Heavy', 'price' => 5999, 'credits' => 100000, 'heavyCredits' => 6200, 'visible' => true, 'exclusiveEmail' => 'pak@gmail.com', 'description' => 'Omni Flash 6,200 Credits + Unlimited Veo 3.1 Lite'],
            ],
            'toolsVisibility' => [
                'chatgpt' => false,
                'supergrok' => false,
                'metaAi' => false,
                'museAi' => false,
                'googleFlow' => true,
                'nanoBananaPro' => false,
                'surfshark' => false,
                'whiskStudio' => false,
                'geminiLive' => false,
                'openart' => false,
                'promptLibrary' => false,
                'pakTts' => false,
            ],
            'extensions' => [
                'flow' => [
                    'title' => 'Google Flow (Veo 3.1) Extension',
                    'fileName' => 'PakFlow_v1.7.zip',
                    'version' => '1.7',
                    'fileSize' => '829 KB',
                    'downloadUrl' => '/downloads/PakFlow_v1.7.zip',
                    'updatedAt' => $now,
                    'instructions' => "1. Download Google Flow Extension .zip\n2. Extract it to a folder on your PC\n3. Go to chrome://extensions and enable Developer mode\n4. Click Load unpacked and select the extracted folder\n5. Open Google Flow and enjoy auto-login cookies!",
                ],
            ],
            'resellerBankDetails' => [
                'bankName' => 'Raqami Islamic Digital Bank',
                'accountTitle' => 'Muhammad Usman',
                'accountNumber' => '024798897813',
                'iban' => 'PK04ROMI0000024798897813',
                'instructions' => 'Send your payment to this Raqami Bank account, then submit the form with a screenshot.',
            ],
            'resellerBroadcast' => [
                'enabled' => true,
                'title' => 'Hello dear resellers,',
                'message' => "We have suspended all reseller accounts as we will now be working only with genuine and verified resellers.\n\nIf you need any help or want to discuss your reseller account, please contact us:\n\nðŸ“ž +92 331 0356428\nðŸ“ž +92 319 8273690\n\nThank you for your understanding and cooperation. â¤ï¸",
                'phone1' => '+92 331 0356428',
                'phone2' => '+92 319 8273690',
            ],
            'resellerRates' => [
                'basic' => 0,
                'pro' => 500,
                'ultra' => 800,
                'plus' => 500,
                'max' => 800,
                'vibe' => 0,
            ],
            'maintenance' => [
                'enabled' => false,
                'title' => 'Scheduled System Maintenance',
                'message' => 'Pak Flow is currently undergoing scheduled platform upgrades to bring you enhanced performance and faster AI rendering. We will be back online shortly.',
                'customUrl' => 'https://whatsapp.com/channel/0029Vb8c59QGehEJnsmNKd23',
                'customUrlTitle' => 'Join WhatsApp Channel for Updates',
                'redirectToCustomUrl' => false,
                'updatedAt' => $now,
            ],
            'allowTrialExtensionAccess' => false,
            'minExtVersion' => 1.5,
            'accessPolicy' => [
                'requirePortalOpen' => true,
                'blockOnLogout' => true,
                'blockOnExpired' => true,
                'portalGraceSeconds' => 15,
            ],
            'serverShuffle' => [
                'enabled' => false,
                'intervalMinutes' => 180,
                'shuffleEpoch' => 1,
                'defaultSlot' => 1,
                'poolSlots' => [],
                'lastShuffledAt' => null,
            ],
            'welcomeMessageTemplate' => "🎉 Welcome to PakFlow!\nYour account is ready:\n\n🌐 Website: {{site_link}}\n📧 Email: {{email}}\n🔑 Password: {{password}}\n📦 Plan: {{plan}} ({{days}} Days)\n\nDownload the extension from your dashboard and start creating!",
        ];
    }

    public static function getMetaValue(string $key, $default = null)
    {
        if (Database::isEnabled()) {
            $stmt = Database::pdo()->prepare('SELECT value_json FROM app_settings WHERE setting_key = ? LIMIT 1');
            $stmt->execute([$key]);
            $row = $stmt->fetch();
            if ($row && isset($row['value_json'])) {
                $decoded = json_decode((string)$row['value_json'], true);
                return json_last_error() === JSON_ERROR_NONE ? $decoded : $default;
            }
            return $default;
        }
        return $default;
    }

    public static function setMetaValue(string $key, $value): bool
    {
        if (Database::isEnabled()) {
            $stmt = Database::pdo()->prepare('INSERT INTO app_settings (setting_key, value_json, updated_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value_json = VALUES(value_json), updated_at = VALUES(updated_at)');
            return $stmt->execute([$key, json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), time()]);
        }
        return false;
    }

    public static function findAdminByEmail(string $email): ?array
    {
        if (!Database::isEnabled()) {
            return null;
        }
        $clean = self::sanitizeEmail($email);
        if ($clean === '') {
            return null;
        }
        $stmt = Database::pdo()->prepare('SELECT * FROM admins WHERE email = ? LIMIT 1');
        $stmt->execute([$clean]);
        $row = $stmt->fetch();
        return is_array($row) ? $row : null;
    }

    public static function verifyAdminCredentials(string $email, string $password): ?array
    {
        $cleanEmail = self::sanitizeEmail($email);
        if (!Database::isEnabled()) {
            if ($cleanEmail === strtolower(Helpers::ADMIN_EMAIL) && $password === Helpers::ADMIN_PASSWORD) {
                return ['email' => $cleanEmail, 'name' => 'Flow by Pak Administrator', 'role' => 'admin'];
            }
            return null;
        }
        $row = self::findAdminByEmail($cleanEmail);
        if ($row) {
            if (self::verifyPassword($password, (string)($row['password_hash'] ?? ''))) {
                return [
                    'id' => (int)$row['id'],
                    'email' => (string)$row['email'],
                    'name' => (string)($row['name'] ?? 'Flow by Pak Administrator'),
                    'role' => (string)($row['role'] ?? 'admin'),
                ];
            }
            return null;
        }
        // Auto-seed default admin on fresh DB if matching default admin credentials
        if ($cleanEmail === strtolower(Helpers::ADMIN_EMAIL) && $password === Helpers::ADMIN_PASSWORD) {
            $now = self::isoNow();
            $stmt = Database::pdo()->prepare('INSERT IGNORE INTO admins (name, email, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute(['Flow by Pak Administrator', $cleanEmail, self::hashPassword($password), 'admin', $now, $now]);
            return ['email' => $cleanEmail, 'name' => 'Flow by Pak Administrator', 'role' => 'admin'];
        }
        return null;
    }

    public static function readSettings(): array
    {
        if (self::$settingsCache !== null) {
            return self::$settingsCache;
        }
        $defaults = self::defaultSettings();
        if (Database::isEnabled()) {
            $cacheFile = self::dataDir() . DIRECTORY_SEPARATOR . 'settings_cache.json';
            clearstatcache(true, $cacheFile);
            if (is_file($cacheFile) && is_readable($cacheFile)) {
                $age = time() - (@filemtime($cacheFile) ?: 0);
                if ($age >= 0 && $age <= 8) {
                    $cached = self::readJsonFile($cacheFile, null);
                    if (is_array($cached)) {
                        self::$settingsCache = array_replace($defaults, $cached);
                        self::$dbRevision++;
                        return self::$settingsCache;
                    }
                }
            }
            $parsed = self::getMetaValue('platform_settings', null);
            if (!is_array($parsed)) {
                self::setMetaValue('platform_settings', $defaults);
                self::$settingsCache = $defaults;
                self::writeFileAtomic($cacheFile, $defaults, false);
                return self::$settingsCache;
            }
            self::$settingsCache = array_replace($defaults, $parsed);
            self::writeFileAtomic($cacheFile, $parsed, false);
            self::$dbRevision++;
            return self::$settingsCache;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'settings.json';
        if (!is_file($file)) {
            self::writeFileAtomic($file, $defaults, true);
            self::$settingsCache = $defaults;
            return self::$settingsCache;
        }
        $parsed = self::readJsonFile($file, []);
        self::$settingsCache = is_array($parsed) ? array_replace($defaults, $parsed) : $defaults;
        self::$dbRevision++;
        return self::$settingsCache;
    }

    public static function writeSettings(array $settings): bool
    {
        self::$settingsCache = $settings;
        self::$dbRevision++;
        if (Database::isEnabled()) {
            $ok = self::setMetaValue('platform_settings', $settings);
            self::invalidateRuntimeCaches(true, true);
            return $ok;
        }
        $ok = self::writeFileAtomic(self::dataDir() . DIRECTORY_SEPARATOR . 'settings.json', $settings, true);
        self::invalidateRuntimeCaches(true, true);
        return $ok;
    }

    public static function readResellers(): array
    {
        if (self::$resellersCache !== null) {
            return self::$resellersCache;
        }
        if (Database::isEnabled()) {
            $rows = Database::pdo()->query('SELECT * FROM resellers ORDER BY id ASC')->fetchAll();
            $out = [];
            foreach ($rows as $r) {
                $extra = !empty($r['extra_json']) ? json_decode((string)$r['extra_json'], true) : [];
                if (!is_array($extra)) {
                    $extra = [];
                }
                $out[] = array_merge($extra, [
                    'id' => (int)$r['id'],
                    'name' => (string)$r['name'],
                    'email' => (string)$r['email'],
                    'passwordHash' => (string)$r['password_hash'],
                    'plainPasswordHint' => $r['plain_password_hint'],
                    'allowedUsers' => (int)$r['allowed_users'],
                    'creditsQuota' => (int)$r['credits_quota'],
                    'usersCreated' => (int)$r['users_created'],
                    'totalPaid' => (int)$r['total_paid'],
                    'deletedCharges' => (int)$r['deleted_charges'],
                    'whatsapp' => (string)($r['whatsapp'] ?? ''),
                    'status' => (string)($r['status'] ?? 'active'),
                    'createdAt' => $r['created_at'],
                ]);
            }
            self::$resellersCache = $out;
            self::$dbRevision++;
            return self::$resellersCache;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'resellers.json';
        if (!is_file($file)) {
            $defaults = [
                [
                    'id' => 1,
                    'name' => 'VIP Reseller',
                    'email' => 'reseller@pakflow.com',
                    'passwordHash' => self::hashPassword('reseller123'),
                    'plainPasswordHint' => 'reseller123',
                    'allowedUsers' => 50,
                    'creditsQuota' => 1000000,
                    'usersCreated' => 0,
                    'totalPaid' => 0,
                    'deletedCharges' => 0,
                    'whatsapp' => '+92 303 6903532',
                    'status' => 'active',
                    'createdAt' => self::isoNow(),
                ],
            ];
            self::writeFileAtomic($file, $defaults, true);
            self::$resellersCache = $defaults;
            return self::$resellersCache;
        }
        $parsed = self::readJsonFile($file, []);
        self::$resellersCache = is_array($parsed) ? $parsed : [];
        self::$dbRevision++;
        return self::$resellersCache;
    }

    public static function writeResellers(array $resellers): bool
    {
        $clean = array_values($resellers);
        self::$resellersCache = $clean;
        self::$dbRevision++;
        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $core = ['id', 'name', 'email', 'passwordHash', 'plainPasswordHint', 'allowedUsers', 'creditsQuota', 'usersCreated', 'totalPaid', 'deletedCharges', 'whatsapp', 'status', 'createdAt'];
            $stmt = $pdo->prepare('INSERT INTO resellers (id, name, email, password_hash, plain_password_hint, allowed_users, credits_quota, users_created, total_paid, deleted_charges, whatsapp, status, created_at, extra_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), password_hash=VALUES(password_hash), plain_password_hint=VALUES(plain_password_hint), allowed_users=VALUES(allowed_users), credits_quota=VALUES(credits_quota), users_created=VALUES(users_created), total_paid=VALUES(total_paid), deleted_charges=VALUES(deleted_charges), whatsapp=VALUES(whatsapp), status=VALUES(status), created_at=VALUES(created_at), extra_json=VALUES(extra_json)');
            $pdo->beginTransaction();
            try {
                $ids = [];
                foreach ($clean as $r) {
                    if (!is_array($r)) {
                        continue;
                    }
                    $extra = array_diff_key($r, array_flip($core));
                    $rid = (int)($r['id'] ?? 0);
                    $ids[] = $rid;
                    $stmt->execute([
                        $rid,
                        (string)($r['name'] ?? ''),
                        self::sanitizeEmail($r['email'] ?? ''),
                        (string)($r['passwordHash'] ?? ''),
                        isset($r['plainPasswordHint']) ? (string)$r['plainPasswordHint'] : null,
                        (int)($r['allowedUsers'] ?? 50),
                        (int)($r['creditsQuota'] ?? 1000000),
                        (int)($r['usersCreated'] ?? 0),
                        (int)($r['totalPaid'] ?? 0),
                        (int)($r['deletedCharges'] ?? 0),
                        (string)($r['whatsapp'] ?? ''),
                        (string)($r['status'] ?? 'active'),
                        isset($r['createdAt']) ? (string)$r['createdAt'] : null,
                        empty($extra) ? null : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
                if (empty($ids)) {
                    $pdo->exec('DELETE FROM resellers');
                } else {
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $pdo->prepare("DELETE FROM resellers WHERE id NOT IN ($ph)")->execute($ids);
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
            return true;
        }
        return self::writeFileAtomic(self::dataDir() . DIRECTORY_SEPARATOR . 'resellers.json', self::$resellersCache, true);
    }

    public static function readResellerPayments(): array
    {
        if (self::$resellerPaymentsCache !== null) {
            return self::$resellerPaymentsCache;
        }
        if (Database::isEnabled()) {
            $rows = Database::pdo()->query('SELECT * FROM reseller_payments ORDER BY id DESC')->fetchAll();
            $out = [];
            foreach ($rows as $r) {
                $extra = !empty($r['extra_json']) ? json_decode((string)$r['extra_json'], true) : [];
                if (!is_array($extra)) {
                    $extra = [];
                }
                $item = array_merge($extra, [
                    'id' => (int)$r['id'],
                    'resellerId' => (int)$r['reseller_id'],
                    'resellerName' => (string)$r['reseller_name'],
                    'resellerEmail' => (string)$r['reseller_email'],
                    'amount' => (float)$r['amount'] + 0,
                    'note' => (string)($r['note'] ?? ''),
                    'proof' => $r['proof'],
                    'status' => (string)($r['status'] ?? 'pending'),
                    'createdAt' => $r['created_at'],
                ]);
                if ($r['reviewed_by'] !== null) $item['reviewedBy'] = $r['reviewed_by'];
                if ($r['approved_at'] !== null) $item['approvedAt'] = $r['approved_at'];
                if ($r['rejected_at'] !== null) $item['rejectedAt'] = $r['rejected_at'];
                if ($r['rejected_reason'] !== null) $item['rejectedReason'] = $r['rejected_reason'];
                $out[] = $item;
            }
            self::$resellerPaymentsCache = $out;
            return self::$resellerPaymentsCache;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'reseller_payments.json';
        if (!is_file($file)) {
            self::writeFileAtomic($file, [], true);
            self::$resellerPaymentsCache = [];
            return self::$resellerPaymentsCache;
        }
        $parsed = self::readJsonFile($file, []);
        self::$resellerPaymentsCache = is_array($parsed) ? $parsed : [];
        return self::$resellerPaymentsCache;
    }

    public static function writeResellerPayments(array $payments): bool
    {
        $clean = array_values($payments);
        self::$resellerPaymentsCache = $clean;
        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $core = ['id', 'resellerId', 'resellerName', 'resellerEmail', 'amount', 'note', 'proof', 'status', 'createdAt', 'reviewedBy', 'approvedAt', 'rejectedAt', 'rejectedReason'];
            $stmt = $pdo->prepare('INSERT INTO reseller_payments (id, reseller_id, reseller_name, reseller_email, amount, note, proof, status, created_at, reviewed_by, approved_at, rejected_at, rejected_reason, extra_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE reseller_id=VALUES(reseller_id), reseller_name=VALUES(reseller_name), reseller_email=VALUES(reseller_email), amount=VALUES(amount), note=VALUES(note), proof=VALUES(proof), status=VALUES(status), created_at=VALUES(created_at), reviewed_by=VALUES(reviewed_by), approved_at=VALUES(approved_at), rejected_at=VALUES(rejected_at), rejected_reason=VALUES(rejected_reason), extra_json=VALUES(extra_json)');
            $pdo->beginTransaction();
            try {
                $ids = [];
                foreach ($clean as $p) {
                    if (!is_array($p)) {
                        continue;
                    }
                    $extra = array_diff_key($p, array_flip($core));
                    $pid = (int)($p['id'] ?? 0);
                    $ids[] = $pid;
                    $stmt->execute([
                        $pid,
                        (int)($p['resellerId'] ?? 0),
                        (string)($p['resellerName'] ?? ''),
                        (string)($p['resellerEmail'] ?? ''),
                        (float)($p['amount'] ?? 0),
                        isset($p['note']) ? (string)$p['note'] : null,
                        isset($p['proof']) ? (string)$p['proof'] : null,
                        (string)($p['status'] ?? 'pending'),
                        isset($p['createdAt']) ? (string)$p['createdAt'] : null,
                        isset($p['reviewedBy']) ? (string)$p['reviewedBy'] : null,
                        isset($p['approvedAt']) ? (string)$p['approvedAt'] : null,
                        isset($p['rejectedAt']) ? (string)$p['rejectedAt'] : null,
                        isset($p['rejectedReason']) ? (string)$p['rejectedReason'] : null,
                        empty($extra) ? null : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ]);
                }
                if (empty($ids)) {
                    $pdo->exec('DELETE FROM reseller_payments');
                } else {
                    $ph = implode(',', array_fill(0, count($ids), '?'));
                    $pdo->prepare("DELETE FROM reseller_payments WHERE id NOT IN ($ph)")->execute($ids);
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
            return true;
        }
        return self::writeFileAtomic(self::dataDir() . DIRECTORY_SEPARATOR . 'reseller_payments.json', self::$resellerPaymentsCache, false);
    }

    public static function readPrompts(): array
    {
        if (self::$promptsCache !== null) {
            return self::$promptsCache;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'prompts.json';
        if (!is_file($file)) {
            self::writeFileAtomic($file, [], true);
            self::$promptsCache = [];
            return self::$promptsCache;
        }
        $parsed = self::readJsonFile($file, []);
        self::$promptsCache = is_array($parsed) ? $parsed : [];
        self::$promptsRevision++;
        return self::$promptsCache;
    }

    public static function writePrompts(array $prompts): bool
    {
        self::$promptsCache = array_values($prompts);
        self::$promptsRevision++;
        return self::writeFileAtomic(self::dataDir() . DIRECTORY_SEPARATOR . 'prompts.json', self::$promptsCache, false);
    }

    public static function defaultToolCookies(): array
    {
        return [
            'chatgpt' => [
                'id' => 'chatgpt',
                'name' => 'ChatGPT Plus / Max',
                'domain' => '.chatgpt.com',
                'targetUrl' => 'https://chatgpt.com',
                'cookieCount' => 0,
                'status' => 'EMPTY',
                'updatedAt' => null,
                'updatedBy' => 'Super Admin',
                'cookies' => [],
            ],
            'supergrok' => [
                'id' => 'supergrok',
                'name' => 'Super Grok',
                'domain' => '.grok.com',
                'targetUrl' => 'https://grok.com',
                'cookieCount' => 0,
                'status' => 'EMPTY',
                'updatedAt' => null,
                'updatedBy' => 'Super Admin',
                'cookies' => [],
            ],
            'meta_ai' => [
                'id' => 'meta_ai',
                'name' => 'Meta AI',
                'domain' => '.meta.ai',
                'targetUrl' => 'https://www.meta.ai',
                'cookieCount' => 0,
                'status' => 'EMPTY',
                'updatedAt' => null,
                'updatedBy' => 'Super Admin',
                'cookies' => [],
            ],
            'muse_ai' => [
                'id' => 'muse_ai',
                'name' => 'Muse AI',
                'domain' => '.muse.ai',
                'targetUrl' => 'https://muse.ai',
                'cookieCount' => 0,
                'status' => 'EMPTY',
                'updatedAt' => null,
                'updatedBy' => 'Super Admin',
                'cookies' => [],
            ],
        ];
    }

    public static function decodeWafSafeText($val)
    {
        if (!is_string($val)) {
            return $val;
        }
        if (str_starts_with($val, '__b64__:')) {
            $decoded = base64_decode(substr($val, 8), true);
            return $decoded !== false ? $decoded : $val;
        }
        return $val;
    }

    private static function isList(array $arr): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($arr);
        }
        return $arr === [] || array_keys($arr) === range(0, count($arr) - 1);
    }

    public static function parseCookiesInput($input): array
    {
        if (!$input) {
            return [];
        }
        if (is_array($input)) {
            if (self::isList($input)) {
                return $input;
            }
            if (isset($input['cookies'])) {
                if (is_array($input['cookies'])) {
                    return $input['cookies'];
                }
                if (is_string($input['cookies'])) {
                    return self::parseCookiesInput($input['cookies']);
                }
            }
            if (isset($input['data'])) {
                if (is_array($input['data'])) {
                    return $input['data'];
                }
                if (is_string($input['data'])) {
                    return self::parseCookiesInput($input['data']);
                }
            }
            return [];
        }

        if (is_string($input)) {
            $decoded = (string)self::decodeWafSafeText(trim($input));
            $trimmed = trim($decoded);
            if ($trimmed === '') {
                return [];
            }

            if ((str_starts_with($trimmed, '[') && str_ends_with($trimmed, ']')) || (str_starts_with($trimmed, '{') && str_ends_with($trimmed, '}'))) {
                $parsed = json_decode($trimmed, true);
                if (is_array($parsed)) {
                    if (self::isList($parsed)) {
                        return $parsed;
                    }
                    if (is_array($parsed['cookies'] ?? null)) {
                        return $parsed['cookies'];
                    }
                    if (is_array($parsed['data'] ?? null)) {
                        return $parsed['data'];
                    }
                }
            }

            $lines = preg_split('/\r?\n/', $trimmed) ?: [];
            $cookies = [];
            foreach ($lines as $line) {
                $cleanLine = trim($line);
                if ($cleanLine === '' || str_starts_with($cleanLine, '#')) {
                    continue;
                }
                $parts = preg_split('/\t+/', $cleanLine) ?: [];
                if (count($parts) >= 7) {
                    $exp = is_numeric(trim($parts[4])) ? (float)trim($parts[4]) : null;
                    $cookie = [
                        'domain' => trim($parts[0]),
                        'includeSubDomains' => strtolower(trim($parts[1])) === 'true',
                        'path' => trim($parts[2]),
                        'secure' => strtolower(trim($parts[3])) === 'true',
                        'name' => trim($parts[5]),
                        'value' => trim($parts[6]),
                    ];
                    if ($exp !== null && $exp > 0) {
                        $cookie['expirationDate'] = $exp;
                    }
                    $cookies[] = $cookie;
                } elseif (str_contains($cleanLine, '=')) {
                    $eqIdx = strpos($cleanLine, '=');
                    $name = trim(substr($cleanLine, 0, $eqIdx));
                    $value = trim(substr($cleanLine, $eqIdx + 1));
                    if ($name !== '') {
                        $cookies[] = ['name' => $name, 'value' => $value, 'path' => '/'];
                    }
                }
            }
            return $cookies;
        }

        return [];
    }

    public static function readToolCookies(): array
    {
        if (self::$toolCookiesCache !== null) {
            return self::$toolCookiesCache;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'tool_cookies.json';
        $defaults = self::defaultToolCookies();
        if (!is_file($file)) {
            self::writeFileAtomic($file, $defaults, true);
            self::$toolCookiesCache = $defaults;
            return self::$toolCookiesCache;
        }
        $parsed = self::readJsonFile($file, []);
        self::$toolCookiesCache = is_array($parsed) ? array_replace($defaults, $parsed) : $defaults;
        return self::$toolCookiesCache;
    }

    public static function writeToolCookies(array $toolCookies): bool
    {
        self::$toolCookiesCache = $toolCookies;
        return self::writeFileAtomic(self::dataDir() . DIRECTORY_SEPARATOR . 'tool_cookies.json', $toolCookies, true);
    }

    private static function buildDefaultSlots(): array
    {
        $slots = [];
        for ($slotNum = 1; $slotNum <= 15; $slotNum++) {
            $isVideo = ($slotNum === 1);
            $isNano = ($slotNum >= 11);
            $serverType = $isVideo ? 'Video' : ($isNano ? 'Nano Banana Pro' : 'Images');
            $slots[] = [
                'slotNumber' => $slotNum,
                'name' => "Server $slotNum ($serverType)",
                'serverType' => $serverType,
                'status' => $slotNum === 1 ? 'ACTIVE' : 'EMPTY',
                'cookieCount' => 0,
                'updatedAt' => null,
                'updatedBy' => 'Super Admin',
                'cookies' => [],
            ];
        }
        return $slots;
    }

    private static function ensureAllFifteenSlots($list): array
    {
        $arr = is_array($list) ? $list : [];
        $defaults = self::buildDefaultSlots();
        $out = [];
        foreach ($defaults as $def) {
            $found = null;
            foreach ($arr as $s) {
                if (is_array($s) && (int)($s['slotNumber'] ?? 0) === $def['slotNumber']) {
                    $found = $s;
                    break;
                }
            }
            if ($found !== null) {
                $merged = array_merge($def, $found);
                $merged['slotNumber'] = $def['slotNumber'];
                $out[] = $merged;
            } else {
                $out[] = $def;
            }
        }
        return $out;
    }

    public static function getSlotsVersion(?int $slotNumber = null): string
    {
        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            if ($slotNumber !== null && $slotNumber >= 1 && $slotNumber <= 15) {
                $stmt = $pdo->prepare('SELECT COALESCE(version_ts, 0) FROM cookie_slots WHERE slot_number = ? LIMIT 1');
                $stmt->execute([$slotNumber]);
                $val = $stmt->fetchColumn();
                return $val !== false ? (string)$val : '0';
            }
            $val = $pdo->query('SELECT COALESCE(MAX(version_ts), 0) FROM cookie_slots')->fetchColumn();
            return (string)$val;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'slots.json';
        clearstatcache(true, $file);
        return ((@filemtime($file) ?: 0) . '-' . (@filesize($file) ?: 0));
    }

    public static function computeCookiesSignature(array $cookies, bool $coreAuthOnly = false): string
    {
        if (empty($cookies)) {
            return '';
        }
        $noisyPerClick = ['SIDCC', '__Secure-1PSIDCC', '__Secure-3PSIDCC'];
        $parts = [];
        foreach ($cookies as $c) {
            if (!is_array($c)) {
                continue;
            }
            $dom = strtolower(trim((string)($c['domain'] ?? '')));
            $name = trim((string)($c['name'] ?? ''));
            $val = (string)($c['value'] ?? '');
            if ($name === '') {
                continue;
            }
            // Always ignore analytics cookies (_ga*, _gid, _gcl*) so page clicks never cause version churn
            if (str_starts_with($name, '_ga') || $name === '_gid' || str_starts_with($name, '_gcl')) {
                continue;
            }
            if ($coreAuthOnly && in_array($name, $noisyPerClick, true)) {
                continue;
            }
            $parts[] = $dom . '|' . $name . '=' . $val;
        }
        sort($parts, SORT_STRING);
        return sha1(implode(';', $parts));
    }

    private static function rowToSlot(array $r): array
    {
        $cookies = json_decode((string)($r['cookies_json'] ?? '[]'), true);
        if (!is_array($cookies)) {
            $cookies = [];
        }
        return [
            'slotNumber' => (int)$r['slot_number'],
            'name' => (string)$r['name'],
            'serverType' => (string)$r['server_type'],
            'status' => (string)$r['status'],
            'cookieCount' => (int)$r['cookie_count'],
            'updatedAt' => $r['updated_at'],
            'updatedBy' => (string)($r['updated_by'] ?? 'Super Admin'),
            'cookies' => $cookies,
            'versionTs' => (int)($r['version_ts'] ?? 0),
        ];
    }

    public static function readSlotByNumber(int $slotNum): ?array
    {
        if ($slotNum < 1 || $slotNum > 15) {
            return null;
        }
        if (Database::isEnabled()) {
            $stmt = Database::pdo()->prepare('SELECT * FROM cookie_slots WHERE slot_number = ? LIMIT 1');
            $stmt->execute([$slotNum]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                return self::rowToSlot($row);
            }
            $isVideo = ($slotNum === 1);
            $isNano = ($slotNum >= 11);
            $serverType = $isVideo ? 'Video' : ($isNano ? 'Nano Banana Pro' : 'Images');
            return [
                'slotNumber' => $slotNum,
                'name' => "Server $slotNum ($serverType)",
                'serverType' => $serverType,
                'status' => 'EMPTY',
                'cookieCount' => 0,
                'updatedAt' => null,
                'updatedBy' => 'Super Admin',
                'cookies' => [],
                'versionTs' => 0,
            ];
        }
        foreach (self::readSlots() as $s) {
            if ((int)($s['slotNumber'] ?? 0) === $slotNum) {
                return $s;
            }
        }
        return null;
    }

    public static function readSlotsByRange(int $minSlot, int $maxSlot): array
    {
        if (Database::isEnabled()) {
            $stmt = Database::pdo()->prepare('SELECT * FROM cookie_slots WHERE slot_number BETWEEN ? AND ? ORDER BY slot_number ASC');
            $stmt->execute([$minSlot, $maxSlot]);
            $rows = $stmt->fetchAll();
            if (empty($rows)) {
                $all = self::readSlots();
                return array_values(array_filter(
                    $all,
                    static fn($s): bool => (int)($s['slotNumber'] ?? 0) >= $minSlot && (int)($s['slotNumber'] ?? 0) <= $maxSlot
                ));
            }
            $out = [];
            foreach ($rows as $r) {
                $out[] = self::rowToSlot($r);
            }
            return $out;
        }
        return array_values(array_filter(
            self::readSlots(),
            static fn($s): bool => (int)($s['slotNumber'] ?? 0) >= $minSlot && (int)($s['slotNumber'] ?? 0) <= $maxSlot
        ));
    }

    public static function upsertSingleSlot(array $slot, bool $forceBumpVersion = false): array
    {
        $slotNum = (int)($slot['slotNumber'] ?? 0);
        if ($slotNum < 1 || $slotNum > 15) {
            throw new \InvalidArgumentException("Invalid slot number: $slotNum");
        }
        $cookies = is_array($slot['cookies'] ?? null) ? $slot['cookies'] : [];
        $cookieCount = (int)($slot['cookieCount'] ?? count($cookies));
        $name = (string)($slot['name'] ?? '');
        $serverType = (string)($slot['serverType'] ?? 'Images');
        $status = (string)($slot['status'] ?? 'EMPTY');
        $updatedAt = isset($slot['updatedAt']) ? (string)$slot['updatedAt'] : null;
        $updatedBy = (string)($slot['updatedBy'] ?? 'Super Admin');

        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $existing = self::readSlotByNumber($slotNum);
            if (!$forceBumpVersion && is_array($existing) && ($existing['versionTs'] ?? 0) > 0) {
                $existingCookies = is_array($existing['cookies'] ?? null) ? $existing['cookies'] : [];
                $oldSig = self::computeCookiesSignature($existingCookies, false);
                $newSig = self::computeCookiesSignature($cookies, false);
                $metaSame = ($existing['status'] ?? '') === $status
                    && ($existing['name'] ?? '') === $name
                    && (int)($existing['cookieCount'] ?? 0) === $cookieCount;
                if ($oldSig === $newSig && $metaSame) {
                    return ['slot' => $existing, 'changed' => false, 'versionTs' => (int)($existing['versionTs'] ?? 0)];
                }
                // If ONLY noisy SIDCC cookies changed (core auth identical) within last 180s, throttle version_ts bump
                $oldCore = self::computeCookiesSignature($existingCookies, true);
                $newCore = self::computeCookiesSignature($cookies, true);
                $ageMs = (int)round(microtime(true) * 1000) - (int)($existing['versionTs'] ?? 0);
                if ($oldCore === $newCore && $metaSame && $ageMs >= 0 && $ageMs < 180000) {
                    return ['slot' => $existing, 'changed' => false, 'versionTs' => (int)($existing['versionTs'] ?? 0)];
                }
            }

            $ver = (int)round(microtime(true) * 1000);
            $stmt = $pdo->prepare('INSERT INTO cookie_slots (slot_number, name, server_type, status, cookie_count, updated_at, updated_by, cookies_json, version_ts)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE name=VALUES(name), server_type=VALUES(server_type), status=VALUES(status), cookie_count=VALUES(cookie_count), updated_at=VALUES(updated_at), updated_by=VALUES(updated_by), cookies_json=VALUES(cookies_json), version_ts=VALUES(version_ts)');
            $stmt->execute([
                $slotNum,
                $name,
                $serverType,
                $status,
                $cookieCount,
                $updatedAt,
                $updatedBy,
                json_encode($cookies, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $ver,
            ]);
            $slot['versionTs'] = $ver;
            if (is_array(self::$slotsCache)) {
                $replaced = false;
                foreach (self::$slotsCache as $idx => $cs) {
                    if ((int)($cs['slotNumber'] ?? 0) === $slotNum) {
                        self::$slotsCache[$idx] = $slot;
                        $replaced = true;
                        break;
                    }
                }
                if (!$replaced) {
                    self::$slotsCache[] = $slot;
                }
            }
            self::invalidateRuntimeCaches(false, true);
            return ['slot' => $slot, 'changed' => true, 'versionTs' => $ver];
        }

        $slots = self::readSlots();
        $idx = -1;
        foreach ($slots as $i => $s) {
            if ((int)($s['slotNumber'] ?? 0) === $slotNum) {
                $idx = $i;
                break;
            }
        }
        if ($idx !== -1 && !$forceBumpVersion) {
            $existing = $slots[$idx];
            $oldSig = self::computeCookiesSignature(is_array($existing['cookies'] ?? null) ? $existing['cookies'] : []);
            $newSig = self::computeCookiesSignature($cookies);
            if (
                $oldSig === $newSig
                && ($existing['status'] ?? '') === $status
                && ($existing['name'] ?? '') === $name
                && (int)($existing['cookieCount'] ?? 0) === $cookieCount
            ) {
                return ['slot' => $existing, 'changed' => false, 'versionTs' => 0];
            }
        }
        if ($idx !== -1) {
            $slots[$idx] = $slot;
        } else {
            $slots[] = $slot;
            usort($slots, static fn($a, $b) => ((int)$a['slotNumber']) <=> ((int)$b['slotNumber']));
        }
        self::writeSlots($slots);
        return ['slot' => $slot, 'changed' => true, 'versionTs' => (int)round(microtime(true) * 1000)];
    }

    public static function readSlots(): array
    {
        if (self::$slotsCache !== null && count(self::$slotsCache) >= 15) {
            return self::$slotsCache;
        }
        if (Database::isEnabled()) {
            $rows = Database::pdo()->query('SELECT * FROM cookie_slots ORDER BY slot_number ASC')->fetchAll();
            $out = [];
            foreach ($rows as $r) {
                $cookies = json_decode((string)($r['cookies_json'] ?? '[]'), true);
                if (!is_array($cookies)) {
                    $cookies = [];
                }
                $out[] = [
                    'slotNumber' => (int)$r['slot_number'],
                    'name' => (string)$r['name'],
                    'serverType' => (string)$r['server_type'],
                    'status' => (string)$r['status'],
                    'cookieCount' => (int)$r['cookie_count'],
                    'updatedAt' => $r['updated_at'],
                    'updatedBy' => (string)($r['updated_by'] ?? 'Super Admin'),
                    'cookies' => $cookies,
                ];
            }
            if (empty($out)) {
                $defaults = self::buildDefaultSlots();
                self::writeSlots($defaults);
                return self::$slotsCache;
            }
            self::$slotsCache = self::ensureAllFifteenSlots($out);
            return self::$slotsCache;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'slots.json';
        if (!is_file($file)) {
            $defaults = self::buildDefaultSlots();
            self::writeFileAtomic($file, $defaults, true);
            self::$slotsCache = $defaults;
            return self::$slotsCache;
        }
        $parsed = self::readJsonFile($file, null);
        if (!is_array($parsed)) {
            self::$slotsCache = self::ensureAllFifteenSlots(self::$slotsCache);
            self::writeFileAtomic($file, self::$slotsCache, true);
            return self::$slotsCache;
        }
        self::$slotsCache = self::ensureAllFifteenSlots($parsed);
        return self::$slotsCache;
    }

    public static function writeSlots(array $slots): bool
    {
        $clean = array_values($slots);
        self::$slotsCache = $clean;
        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $ver = (int)round(microtime(true) * 1000);
            $stmt = $pdo->prepare('INSERT INTO cookie_slots (slot_number, name, server_type, status, cookie_count, updated_at, updated_by, cookies_json, version_ts)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE name=VALUES(name), server_type=VALUES(server_type), status=VALUES(status), cookie_count=VALUES(cookie_count), updated_at=VALUES(updated_at), updated_by=VALUES(updated_by), cookies_json=VALUES(cookies_json), version_ts=VALUES(version_ts)');
            $pdo->beginTransaction();
            try {
                foreach ($clean as $s) {
                    if (!is_array($s)) {
                        continue;
                    }
                    $cookies = is_array($s['cookies'] ?? null) ? $s['cookies'] : [];
                    $stmt->execute([
                        (int)($s['slotNumber'] ?? 0),
                        (string)($s['name'] ?? ''),
                        (string)($s['serverType'] ?? 'Images'),
                        (string)($s['status'] ?? 'EMPTY'),
                        (int)($s['cookieCount'] ?? count($cookies)),
                        isset($s['updatedAt']) ? (string)$s['updatedAt'] : null,
                        (string)($s['updatedBy'] ?? 'Super Admin'),
                        json_encode($cookies, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        $ver,
                    ]);
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
            self::invalidateRuntimeCaches(false, true);
            return true;
        }
        $ok = self::writeFileAtomic(self::dataDir() . DIRECTORY_SEPARATOR . 'slots.json', self::$slotsCache, true);
        self::invalidateRuntimeCaches(false, true);
        return $ok;
    }

    public static function getPendingSignup(string $cleanEmail): ?array
    {
        if (Database::isEnabled()) {
            $stmt = Database::pdo()->prepare('SELECT * FROM pending_signups WHERE email = ? LIMIT 1');
            $stmt->execute([$cleanEmail]);
            $r = $stmt->fetch();
            if (!is_array($r)) {
                return null;
            }
            $extra = !empty($r['extra_json']) ? json_decode((string)$r['extra_json'], true) : [];
            return array_merge(is_array($extra) ? $extra : [], [
                'name' => (string)$r['name'],
                'username' => (string)$r['username'],
                'email' => (string)$r['email'],
                'passwordHash' => (string)$r['password_hash'],
                'plainPasswordHint' => (string)($r['plain_password_hint'] ?? ''),
                'deviceId' => (string)($r['device_id'] ?? ''),
                'code' => (string)$r['code'],
                'expiresAt' => (int)$r['expires_at'],
            ]);
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'pending_signups.json';
        $all = is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : [];
        return is_array($all[$cleanEmail] ?? null) ? $all[$cleanEmail] : null;
    }

    public static function savePendingSignup(string $cleanEmail, array $entry): void
    {
        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $pdo->prepare('DELETE FROM pending_signups WHERE expires_at < ?')->execute([time()]);
            $core = ['name', 'username', 'email', 'passwordHash', 'plainPasswordHint', 'deviceId', 'code', 'expiresAt'];
            $extra = array_diff_key($entry, array_flip($core));
            $stmt = $pdo->prepare('INSERT INTO pending_signups (email, name, username, password_hash, plain_password_hint, device_id, code, expires_at, extra_json)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE name=VALUES(name), username=VALUES(username), password_hash=VALUES(password_hash), plain_password_hint=VALUES(plain_password_hint), device_id=VALUES(device_id), code=VALUES(code), expires_at=VALUES(expires_at), extra_json=VALUES(extra_json)');
            $stmt->execute([
                $cleanEmail,
                (string)($entry['name'] ?? ''),
                (string)($entry['username'] ?? ''),
                (string)($entry['passwordHash'] ?? ''),
                isset($entry['plainPasswordHint']) ? (string)$entry['plainPasswordHint'] : null,
                isset($entry['deviceId']) ? (string)$entry['deviceId'] : null,
                (string)($entry['code'] ?? ''),
                (int)($entry['expiresAt'] ?? 0),
                empty($extra) ? null : json_encode($extra, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
            return;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'pending_signups.json';
        $all = is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : [];
        $now = time();
        foreach ($all as $k => $v) {
            if (($v['expiresAt'] ?? 0) < $now) {
                unset($all[$k]);
            }
        }
        $all[$cleanEmail] = $entry;
        @file_put_contents($file, json_encode($all, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public static function deletePendingSignup(string $cleanEmail): void
    {
        if (Database::isEnabled()) {
            Database::pdo()->prepare('DELETE FROM pending_signups WHERE email = ?')->execute([$cleanEmail]);
            return;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'pending_signups.json';
        $all = is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : [];
        if (isset($all[$cleanEmail])) {
            unset($all[$cleanEmail]);
            @file_put_contents($file, json_encode($all, JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
    }

    public static function getPasswordReset(string $token): ?array
    {
        if (Database::isEnabled()) {
            $stmt = Database::pdo()->prepare('SELECT token, email, expires_at FROM password_resets WHERE token = ? LIMIT 1');
            $stmt->execute([$token]);
            $r = $stmt->fetch();
            return is_array($r) ? ['email' => (string)$r['email'], 'expiresAt' => (int)$r['expires_at']] : null;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'password_resets.json';
        $all = is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : [];
        return is_array($all[$token] ?? null) ? $all[$token] : null;
    }

    public static function savePasswordReset(string $token, string $email, int $expiresAt): void
    {
        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $pdo->prepare('DELETE FROM password_resets WHERE expires_at < ?')->execute([time()]);
            $pdo->prepare('INSERT INTO password_resets (token, email, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE email=VALUES(email), expires_at=VALUES(expires_at)')
                ->execute([$token, $email, $expiresAt]);
            return;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'password_resets.json';
        $all = is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : [];
        $now = time();
        foreach ($all as $k => $v) {
            if (($v['expiresAt'] ?? 0) < $now) {
                unset($all[$k]);
            }
        }
        $all[$token] = ['email' => $email, 'expiresAt' => $expiresAt];
        @file_put_contents($file, json_encode($all, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public static function deletePasswordReset(string $token): void
    {
        if (Database::isEnabled()) {
            Database::pdo()->prepare('DELETE FROM password_resets WHERE token = ?')->execute([$token]);
            return;
        }
        $file = self::dataDir() . DIRECTORY_SEPARATOR . 'password_resets.json';
        $all = is_file($file) ? (json_decode((string)@file_get_contents($file), true) ?: []) : [];
        if (isset($all[$token])) {
            unset($all[$token]);
            @file_put_contents($file, json_encode($all, JSON_UNESCAPED_SLASHES), LOCK_EX);
        }
    }
}
