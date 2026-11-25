<?php
declare(strict_types=1);

namespace App;

final class Helpers
{
public const ADMIN_EMAIL = 'pakflow@gmail.com';
public const ADMIN_PASSWORD = 'admin@123';
    public const EXCLUSIVE_HEAVY_EMAIL = 'pak@gmail.com';
    public const MIN_PAKFLOW_EXT_VERSION = 1.5;

    private static ?array $sessionsIpCache = null;

    private static function sessionsIpFile(): string
    {
        return Db::dataDir() . DIRECTORY_SEPARATOR . 'sessions_by_ip.json';
    }

    private static function readSessionsByIp(): array
    {
        if (self::$sessionsIpCache !== null) {
            return self::$sessionsIpCache;
        }
        $file = self::sessionsIpFile();
        if (!is_file($file)) {
            self::$sessionsIpCache = [];
            return self::$sessionsIpCache;
        }
        $raw = @file_get_contents($file);
        if (!$raw) {
            self::$sessionsIpCache = [];
            return self::$sessionsIpCache;
        }
        $decoded = json_decode($raw, true);
        self::$sessionsIpCache = is_array($decoded) ? $decoded : [];
        return self::$sessionsIpCache;
    }

    private static function writeSessionsByIp(array $map): void
    {
        // Keep file small by pruning entries older than 24h
        $cutoff = (int)(microtime(true) * 1000) - 24 * 3600 * 1000;
        $pruned = [];
        foreach ($map as $k => $v) {
            if (is_array($v) && ((int)($v['updatedAt'] ?? 0)) >= $cutoff) {
                $pruned[$k] = $v;
            }
        }
        if (count($pruned) > 2000) {
            $pruned = array_slice($pruned, -2000, null, true);
        }
        self::$sessionsIpCache = $pruned;
        @file_put_contents(self::sessionsIpFile(), json_encode($pruned, JSON_UNESCAPED_SLASHES), LOCK_EX);
    }

    public static function getClientIp(): string
    {
        $cf = Http::header('cf-connecting-ip') ?: Http::header('true-client-ip');
        if ($cf !== '') {
            $parts = explode(',', $cf);
            return trim($parts[0]);
        }
        $xff = Http::header('x-forwarded-for');
        if ($xff !== '') {
            $parts = explode(',', $xff);
            return trim($parts[0]);
        }
        $raw = (string)($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');
        return ($raw === '::1' || $raw === '::ffff:127.0.0.1') ? '127.0.0.1' : $raw;
    }

    public static function getClientUaKey(): string
    {
        $ip = self::getClientIp();
        $ua = substr(trim(Http::header('user-agent')), 0, 140);
        return "$ip|$ua";
    }

    public static function isPlanBasicOrLocked(?string $plan): bool
    {
        $p = strtolower(trim((string)($plan ?? 'basic')));
        return $p === '' || in_array($p, ['basic', 'free', 'trial', 'vibe', 'starter'], true);
    }

    public static function getPakFlowExtVersionString(): string
    {
        $raw = Http::query('ext_v');
        if ($raw === null || trim((string)$raw) === '') {
            $raw = Http::header('x-pakflow-extension-version');
        }
        if ($raw === '') {
            $raw = Http::header('x-pakflow-version');
        }
        if ($raw === '') {
            $raw = Http::header('x-ext-version');
        }
        if ($raw === '' && Http::method() !== 'GET' && Http::method() !== 'HEAD') {
            $body = Http::body();
            $raw = (string)($body['extVersion'] ?? '');
        }
        $str = ltrim(trim((string)$raw), 'vV');
        if ($str === '') {
            return '0.0';
        }
        if (preg_match('/(\d+(?:\.\d+){0,3})/', $str, $m)) {
            return $m[1];
        }
        return '0.0';
    }

    public static function normalizeExtVersionString($raw, string $fallback = '1.5'): string
    {
        $str = ltrim(trim((string)$raw), 'vV');
        if ($str !== '' && preg_match('/^(\d+(?:\.\d+){0,3})/', $str, $m)) {
            return $m[1];
        }
        return $fallback;
    }

    public static function isExtVersionOutdated(?string $clientVer, $minVer): bool
    {
        $c = self::normalizeExtVersionString($clientVer ?? self::getPakFlowExtVersionString(), '0.0');
        $m = self::normalizeExtVersionString($minVer, (string)self::MIN_PAKFLOW_EXT_VERSION);
        return version_compare($c, $m, '<');
    }

    public static function getPakFlowExtVersion(): float
    {
        $str = self::getPakFlowExtVersionString();
        $num = (float)$str;
        return is_finite($num) ? $num : 0.0;
    }

    public static function recordActiveClientSession(array $user): void
    {
        $ip = self::getClientIp();
        $uaKey = self::getClientUaKey();
        $locked = self::isPlanBasicOrLocked($user['plan'] ?? 'basic');
        $nowMs = (int)round(microtime(true) * 1000);
        $uid = $user['id'] ?? null;
        $plan = (string)($user['plan'] ?? 'basic');

        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT session_key, user_id, plan, locked, updated_at_ms FROM sessions_by_ip WHERE session_key IN (?, ?)');
            $stmt->execute([$uaKey, $ip]);
            $rows = [];
            foreach ($stmt->fetchAll() as $r) {
                $rows[$r['session_key']] = $r;
            }
            $existingUa = $rows[$uaKey] ?? null;
            $existingIp = $rows[$ip] ?? null;
            $hasRecentPaidOnIp = is_array($existingIp)
                && empty($existingIp['locked'])
                && ($nowMs - (int)($existingIp['updated_at_ms'] ?? 0)) < 6 * 3600 * 1000;
            $shouldWriteIp = !$locked || !$hasRecentPaidOnIp;

            $uaFresh = is_array($existingUa)
                && (string)($existingUa['user_id'] ?? '') === (string)($uid ?? '')
                && (bool)($existingUa['locked'] ?? false) === $locked
                && (string)($existingUa['plan'] ?? 'basic') === $plan
                && ($nowMs - (int)($existingUa['updated_at_ms'] ?? 0)) < 300000;
            $ipFresh = !$shouldWriteIp || (
                is_array($existingIp)
                && (string)($existingIp['user_id'] ?? '') === (string)($uid ?? '')
                && (bool)($existingIp['locked'] ?? false) === $locked
                && (string)($existingIp['plan'] ?? 'basic') === $plan
                && ($nowMs - (int)($existingIp['updated_at_ms'] ?? 0)) < 300000
            );
            if ($uaFresh && $ipFresh) {
                return;
            }
            $up = $pdo->prepare('INSERT INTO sessions_by_ip (session_key, user_id, email, plan, locked, updated_at_ms) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE user_id=VALUES(user_id), email=VALUES(email), plan=VALUES(plan), locked=VALUES(locked), updated_at_ms=VALUES(updated_at_ms)');
            $uidInt = ($uid !== null && $uid !== '' && is_numeric($uid)) ? (int)$uid : null;
            $emailStr = isset($user['email']) ? (string)$user['email'] : null;
            $up->execute([$uaKey, $uidInt, $emailStr, $plan, $locked ? 1 : 0, $nowMs]);
            if ($shouldWriteIp && $ip !== $uaKey) {
                $up->execute([$ip, $uidInt, $emailStr, $plan, $locked ? 1 : 0, $nowMs]);
            }
            if (mt_rand(1, 500) === 1) {
                $pdo->prepare('DELETE FROM sessions_by_ip WHERE updated_at_ms < ? LIMIT 250')->execute([$nowMs - 24 * 3600 * 1000]);
            }
            return;
        }

        $entry = [
            'userId' => $uid,
            'email' => $user['email'] ?? null,
            'plan' => $plan,
            'locked' => $locked,
            'updatedAt' => $nowMs,
        ];
        $map = self::readSessionsByIp();
        $existingUa = $map[$uaKey] ?? null;
        $existingIp = $map[$ip] ?? null;
        $hasRecentPaidOnIp = is_array($existingIp)
            && empty($existingIp['locked'])
            && ($nowMs - (int)($existingIp['updatedAt'] ?? 0)) < 6 * 3600 * 1000;
        $shouldWriteIp = !$locked || !$hasRecentPaidOnIp;

        // Avoid redundant LOCK_EX disk writes on hot polling paths when unchanged within 60s
        $uaFresh = is_array($existingUa)
            && ($existingUa['userId'] ?? null) === $uid
            && (bool)($existingUa['locked'] ?? false) === $locked
            && ($existingUa['plan'] ?? 'basic') === $plan
            && ($nowMs - (int)($existingUa['updatedAt'] ?? 0)) < 60000;
        $ipFresh = !$shouldWriteIp || (
            is_array($existingIp)
            && ($existingIp['userId'] ?? null) === $uid
            && (bool)($existingIp['locked'] ?? false) === $locked
            && ($existingIp['plan'] ?? 'basic') === $plan
            && ($nowMs - (int)($existingIp['updatedAt'] ?? 0)) < 60000
        );
        if ($uaFresh && $ipFresh) {
            return;
        }

        $map[$uaKey] = $entry;
        if ($shouldWriteIp) {
            $map[$ip] = $entry;
        }
        self::writeSessionsByIp($map);
    }

    public static function clearActiveClientSession(): void
    {
        $uaKey = self::getClientUaKey();
        $nowMs = (int)round(microtime(true) * 1000);
        if (Database::isEnabled()) {
            Database::pdo()->prepare('INSERT INTO sessions_by_ip (session_key, user_id, email, plan, locked, updated_at_ms) VALUES (?, NULL, NULL, ?, 1, ?) ON DUPLICATE KEY UPDATE user_id=NULL, email=NULL, plan=VALUES(plan), locked=1, updated_at_ms=VALUES(updated_at_ms)')
                ->execute([$uaKey, 'basic', $nowMs]);
            return;
        }
        $map = self::readSessionsByIp();
        $map[$uaKey] = [
            'userId' => null,
            'email' => null,
            'plan' => 'basic',
            'locked' => true,
            'updatedAt' => $nowMs,
        ];
        self::writeSessionsByIp($map);
    }

    public static function purgeActiveClientSessionsForUser($userId, string $email = ''): void
    {
        $uidInt = ($userId !== null && $userId !== '' && is_numeric($userId)) ? (int)$userId : null;
        $cleanE = Db::sanitizeEmail($email);
        if ($uidInt === null && $cleanE === '') {
            return;
        }
        if (Database::isEnabled()) {
            try {
                $pdo = Database::pdo();
                if ($uidInt !== null && $cleanE !== '') {
                    $pdo->prepare('DELETE FROM sessions_by_ip WHERE user_id = ? OR LOWER(email) = ?')->execute([$uidInt, $cleanE]);
                } elseif ($uidInt !== null) {
                    $pdo->prepare('DELETE FROM sessions_by_ip WHERE user_id = ?')->execute([$uidInt]);
                } else {
                    $pdo->prepare('DELETE FROM sessions_by_ip WHERE LOWER(email) = ?')->execute([$cleanE]);
                }
            } catch (\Throwable $e) {
            }
            return;
        }
        $map = self::readSessionsByIp();
        $changed = false;
        foreach ($map as $k => $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $entryUid = isset($entry['userId']) && is_numeric($entry['userId']) ? (int)$entry['userId'] : null;
            $entryEmail = Db::sanitizeEmail($entry['email'] ?? '');
            if (($uidInt !== null && $entryUid === $uidInt) || ($cleanE !== '' && $entryEmail === $cleanE)) {
                unset($map[$k]);
                $changed = true;
            }
        }
        if ($changed) {
            self::writeSessionsByIp($map);
        }
    }

    public static function isDemoUser(array $user): bool
    {
        $p = strtolower(trim((string)($user['plan'] ?? '')));
        return $p === 'demo' || !empty($user['isDemo']);
    }

    public static function getUserMaxDevices(array $user): int
    {
        return max(1, min(50, (int)($user['maxDevices'] ?? 1)));
    }

    public static function normalizeActiveDeviceSessions(array $user): array
    {
        $raw = $user['activeDeviceSessions'] ?? null;
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $devId = trim((string)($item['deviceId'] ?? ''));
            $sid = trim((string)($item['sid'] ?? ''));
            if ($devId === '' || $sid === '') {
                continue;
            }
            $out[] = [
                'deviceId' => $devId,
                'sid' => $sid,
                'ip' => trim((string)($item['ip'] ?? '')),
                'uaKey' => trim((string)($item['uaKey'] ?? '')),
                'loginAt' => (int)($item['loginAt'] ?? time()),
                'lastSeenAt' => (int)($item['lastSeenAt'] ?? ($item['loginAt'] ?? time())),
            ];
        }
        return $out;
    }

    public static function isTokenSessionRevoked(array $user, array $payload): bool
    {
        $tokenVer = (int)($payload['sessionVersion'] ?? 1);
        $resetFloor = (int)($user['resetSessionFloor'] ?? 0);
        if ($resetFloor > 0 && $tokenVer < $resetFloor) {
            return true;
        }
        $tokenSid = trim((string)($payload['sid'] ?? ''));
        if ($tokenSid !== '') {
            $sessions = self::normalizeActiveDeviceSessions($user);
            foreach ($sessions as $s) {
                if (($s['sid'] ?? '') === $tokenSid) {
                    return false;
                }
            }
            return true;
        }
        if (!empty($user['singleDeviceEnforced'])) {
            $currentVersion = (int)($user['sessionVersion'] ?? 1);
            if ($tokenVer < $currentVersion) {
                return true;
            }
        }
        return false;
    }

    public static function getResellerPlanCredits(?array $reseller = null, ?array $settings = null): array
    {
        $settings = $settings ?? Db::readSettings();
        $cfg = is_array($settings['resellerPlanCredits'] ?? null) ? $settings['resellerPlanCredits'] : [];
        $pro = isset($cfg['pro']) && (int)$cfg['pro'] > 0 ? (int)$cfg['pro'] : 25000;
        $max = isset($cfg['max']) && (int)$cfg['max'] > 0 ? (int)$cfg['max'] : 45000;
        $demo = isset($cfg['demo']) && (int)$cfg['demo'] > 0 ? (int)$cfg['demo'] : 2000;
        if (is_array($reseller)) {
            if (isset($reseller['proCredits']) && is_numeric($reseller['proCredits']) && (int)$reseller['proCredits'] > 0) {
                $pro = (int)$reseller['proCredits'];
            }
            if (isset($reseller['maxCredits']) && is_numeric($reseller['maxCredits']) && (int)$reseller['maxCredits'] > 0) {
                $max = (int)$reseller['maxCredits'];
            }
        }
        return [
            'pro' => $pro,
            'max' => $max,
            'plus' => $pro,
            'ultra' => $max,
            'demo' => $demo,
        ];
    }

    public static function getPlanDurations(?array $settings = null): array
    {
        $settings = $settings ?? Db::readSettings();
        $cfg = is_array($settings['planDurations'] ?? null) ? $settings['planDurations'] : [];
        $plans = is_array($settings['plans'] ?? null) ? $settings['plans'] : [];

        $proRaw = $cfg['pro'] ?? ($plans['pro']['days'] ?? ($plans['pro']['durationDays'] ?? null));
        $maxRaw = $cfg['max'] ?? ($plans['max']['days'] ?? ($plans['max']['durationDays'] ?? null));
        $basicRaw = $cfg['basic'] ?? ($plans['basic']['days'] ?? 0);

        $pro = (is_numeric($proRaw) && (int)$proRaw > 0) ? max(1, min(3650, (int)$proRaw)) : 20;
        $max = (is_numeric($maxRaw) && (int)$maxRaw > 0) ? max(1, min(3650, (int)$maxRaw)) : 30;
        $basic = (is_numeric($basicRaw) && (int)$basicRaw >= 0) ? min(3650, (int)$basicRaw) : 0;

        return [
            'basic' => $basic,
            'pro' => $pro,
            'max' => $max,
            'demo' => 0,
            'plus' => $pro,
            'ultra' => $max,
        ];
    }

    public static function getPlanDurationDays(string $plan, ?array $settings = null): int
    {
        $p = strtolower(trim($plan));
        $durations = self::getPlanDurations($settings);
        if ($p === 'max' || $p === 'ultra') {
            return (int)$durations['max'];
        }
        if ($p === 'pro' || $p === 'plus') {
            return (int)$durations['pro'];
        }
        if ($p === 'basic' || $p === 'free' || $p === 'demo') {
            return (int)($durations[$p] ?? 0);
        }
        return (int)$durations['pro'];
    }

    public static function getDemoConfig(?array $settings = null): array
    {
        $settings = $settings ?? Db::readSettings();
        $cfg = is_array($settings['demoConfig'] ?? null) ? $settings['demoConfig'] : [];
        $demoPlan = is_array($settings['plans']['demo'] ?? null) ? $settings['plans']['demo'] : [];

        $enabled = !empty($cfg['enabled']);
        $unit = strtolower(trim((string)($cfg['durationUnit'] ?? 'hours')));
        if ($unit !== 'days' && $unit !== 'hours') {
            $unit = 'hours';
        }
        $rawVal = $cfg['durationValue'] ?? ($demoPlan['demoHours'] ?? 2);
        if ($unit === 'days') {
            $val = is_numeric($rawVal) ? max(1, min(365, (int)round((float)$rawVal))) : 1;
            $seconds = $val * 86400;
            $label = $val === 1 ? '1 Day' : "{$val} Days";
        } else {
            $val = is_numeric($rawVal) ? max(0.5, min(720, round((float)$rawVal, 1))) : 2;
            if ((float)$val === (float)(int)$val) {
                $val = (int)$val;
            }
            $seconds = max(60, (int)round($val * 3600));
            $label = ((float)$val == 1.0) ? '1 Hour' : "{$val} Hours";
        }
        $rawCr = $cfg['credits'] ?? ($demoPlan['credits'] ?? ($settings['resellerPlanCredits']['demo'] ?? 2000));
        $credits = is_numeric($rawCr) ? max(50, min(1000000, (int)$rawCr)) : 2000;
        $rawSlot = $cfg['assignedSlot'] ?? 0;
        $assignedSlot = (is_numeric($rawSlot) && (int)$rawSlot >= 1 && (int)$rawSlot <= 10) ? (int)$rawSlot : 0;

        return [
            'enabled' => $enabled,
            'durationValue' => $val,
            'durationUnit' => $unit,
            'durationSeconds' => $seconds,
            'durationLabel' => $label,
            'credits' => $credits,
            'assignedSlot' => $assignedSlot,
            'onePerIp' => !array_key_exists('onePerIp', $cfg) || !empty($cfg['onePerIp']),
            'onePerDevice' => !array_key_exists('onePerDevice', $cfg) || !empty($cfg['onePerDevice']),
        ];
    }

    public static function isSiteTrialUser(?array $user): bool
    {
        if (!is_array($user)) {
            return false;
        }
        if (!self::isDemoUser($user)) {
            return false;
        }
        // Reseller demos have resellerId set; public site trials have no resellerId (or explicit demoSource === 'site')
        if (strtolower((string)($user['demoSource'] ?? '')) === 'site') {
            return true;
        }
        return empty($user['resellerId']);
    }

    public static function getSiteTrialAssignedSlot(?array $user, ?array $settings = null): int
    {
        if (!self::isSiteTrialUser($user)) {
            return 0;
        }
        $dc = self::getDemoConfig($settings);
        $slot = (int)($dc['assignedSlot'] ?? 0);
        return ($slot >= 1 && $slot <= 10) ? $slot : 0;
    }

    public static function isUserPlanExpired(array $user): bool
    {
        if (($user['status'] ?? '') === 'expired') {
            return true;
        }
        if (!empty($user['planExpiresAt'])) {
            $expTime = strtotime((string)$user['planExpiresAt']);
            if ($expTime !== false && $expTime <= time()) {
                return true;
            }
        }
        return false;
    }

    public static function normalizePasswordForEmail($rawPassword, string $cleanEmail): string
    {
        $cleanPass = Db::sanitizeCredential($rawPassword);
        $cleanPassNoSpace = preg_replace('/\s+/u', '', $cleanPass) ?? $cleanPass;
        if ($cleanEmail !== '' && strtolower($cleanPassNoSpace) === $cleanEmail) {
            return $cleanEmail;
        }
        return $cleanPass;
    }

    public static function findUserIndexByLoginIdentifier(array $users, $rawIdentifier): int
    {
        $cleanId = Db::sanitizeEmail($rawIdentifier);
        if ($cleanId === '') {
            return -1;
        }
        $idxMap = Db::getUserIndexes($users);

        if (isset($idxMap['byEmail'][$cleanId])) {
            return (int)$idxMap['byEmail'][$cleanId]['index'];
        }
        if (isset($idxMap['byUsername'][$cleanId])) {
            return (int)$idxMap['byUsername'][$cleanId]['index'];
        }
        $comparable = Db::normalizeGmailKey($cleanId);
        if (isset($idxMap['byGmail'][$comparable])) {
            return (int)$idxMap['byGmail'][$comparable]['index'];
        }
        if (!str_contains($cleanId, '@') && isset($idxMap['byLocalPart'][$cleanId])) {
            return (int)$idxMap['byLocalPart'][$cleanId]['index'];
        }
        return -1;
    }

    public static function findUserByTokenPayloadWithIndex(array $users, ?array $payload): ?array
    {
        if (!$payload) {
            return null;
        }
        $compact = Db::getUserCompactIndexes($users);
        $payloadEmail = Db::sanitizeEmail($payload['email'] ?? '');
        if (isset($payload['userId']) && $payload['userId'] !== '') {
            $idKey = (string)$payload['userId'];
            // Never authenticate a deleted user ID, even if another account was later created.
            if (Db::isUserIdDeleted($idKey) || !isset($compact['byId'][$idKey])) {
                return null;
            }
            $i = (int)$compact['byId'][$idKey];
            $u = $users[$i] ?? null;
            if (!is_array($u)) {
                return null;
            }
            // Strict identity match: if token carries an email, it MUST match this exact user row.
            if ($payloadEmail !== '' && Db::sanitizeEmail($u['email'] ?? '') !== $payloadEmail) {
                return null;
            }
            return ['user' => $u, 'index' => $i];
        }
        if ($payloadEmail !== '' && isset($compact['byEmail'][$payloadEmail])) {
            $i = (int)$compact['byEmail'][$payloadEmail];
            $u = $users[$i] ?? null;
            if (is_array($u)) {
                return ['user' => $u, 'index' => $i];
            }
        }
        return null;
    }

    public static function findUserByTokenPayload(array $users, ?array $payload): ?array
    {
        $match = self::findUserByTokenPayloadWithIndex($users, $payload);
        return $match ? $match['user'] : null;
    }

    public static function normalizeClientPlan(?string $rawPlan, string $email = ''): string
    {
        $p = strtolower(trim((string)($rawPlan ?? 'basic')));
        if ($email !== '' && strtolower(trim($email)) === self::EXCLUSIVE_HEAVY_EMAIL) {
            return 'heavy';
        }
        if ($p === 'heavy') {
            return ($email === '' || strtolower(trim($email)) === self::EXCLUSIVE_HEAVY_EMAIL) ? 'heavy' : 'ultra';
        }
        if (in_array($p, ['max', 'ultra', 'ultra4x'], true)) {
            return 'ultra';
        }
        if (in_array($p, ['plus', 'pro'], true)) {
            return 'pro';
        }
        if (in_array($p, ['vibe', 'basic', 'starter'], true)) {
            return 'basic';
        }
        if (in_array($p, ['free', 'trial'], true)) {
            return 'free';
        }
        return $p;
    }

    public static function formatClientPlanLabel(?string $rawPlan): string
    {
        $norm = self::normalizeClientPlan($rawPlan);
        switch ($norm) {
            case 'ultra': return 'Max Plan';
            case 'pro': return 'Plus Plan';
            case 'basic': return 'Basic Plan';
            case 'heavy': return 'Heavy Plan';
            case 'free': return 'Free Plan';
            default: return ucfirst($norm) . ' Plan';
        }
    }

    public static function getBearerToken(): ?string
    {
        $auth = Http::header('authorization');
        if ($auth !== '' && str_starts_with($auth, 'Bearer ')) {
            return trim(substr($auth, 7));
        }
        $qToken = Http::query('token');
        if ($qToken !== null && $qToken !== '') {
            return trim((string)$qToken);
        }
        return null;
    }

    public static function requireAdmin(): array
    {
        $auth = Http::header('authorization');
        $token = '';
        if ($auth !== '' && str_starts_with($auth, 'Bearer ')) {
            $token = trim(substr($auth, 7));
        } else {
            $alt = Http::header('x-admin-token');
            if ($alt !== '') {
                $token = trim($alt);
            }
        }
        if ($token === '') {
            Http::json(['error' => 'Unauthorized', 'message' => 'Admin authorization token required'], 401);
        }
        $payload = Db::verifyToken($token);
        $adminEmail = strtolower(trim((string)($payload['email'] ?? '')));
        $isKnownAdmin = $adminEmail !== '' && ($adminEmail === strtolower(self::ADMIN_EMAIL) || Db::findAdminByEmail($adminEmail) !== null);
        if (
            !$payload ||
            ($payload['role'] ?? '') !== 'admin' ||
            !$isKnownAdmin
        ) {
            Http::json(['error' => 'Forbidden', 'message' => 'Valid admin session required'], 403);
        }
        return $payload;
    }

    public static function requireUser(string $apiPath): array
    {
        $origin = Http::header('origin');
        $isExtMeReq = $apiPath === '/user/me'
            || str_contains($apiPath, '/user/me')
            || str_starts_with($origin, 'chrome-extension://');

        $auth = Http::header('authorization');
        if ($auth === '' || !str_starts_with($auth, 'Bearer ')) {
            Http::json([
                'authenticated' => false,
                'locked' => false,
                'forceLogout' => false,
                'error' => $isExtMeReq ? 'Please sign in to PakFlow.' : 'Unauthorized',
                'message' => 'Authentication token required',
            ], 401);
        }

        $token = trim(substr($auth, 7));
        $payload = Db::verifyToken($token);
        if (!$payload || (empty($payload['userId']) && empty($payload['email']))) {
            Http::json([
                'authenticated' => false,
                'locked' => false,
                'forceLogout' => false,
                'error' => $isExtMeReq ? 'Session expired. Please sign in again.' : 'Unauthorized',
                'message' => 'Invalid or expired session',
            ], 401);
        }

        $useMysqlFast = Database::isEnabled();
        $users = null;
        $user = null;
        $userIdx = -1;
        if ($useMysqlFast) {
            $user = Db::findUserByTokenMysql(
                isset($payload['userId']) ? (string)$payload['userId'] : null,
                Db::sanitizeEmail($payload['email'] ?? '')
            );
        } else {
            $users = Db::readUsers();
            $userMatch = self::findUserByTokenPayloadWithIndex($users, $payload);
            if ($userMatch) {
                $user = $userMatch['user'];
                $userIdx = (int)$userMatch['index'];
            }
        }
        if (!$user) {
            Http::json([
                'authenticated' => false,
                'locked' => true,
                'forceLogout' => true,
                'userDeleted' => true,
                'error' => $isExtMeReq ? 'Your account has been removed by Admin.' : 'UserNotFound',
                'message' => 'User account no longer exists',
            ], 401);
        }

        $isDemo = self::isDemoUser($user);
        $isExpired = self::isUserPlanExpired($user);
        if ($isExpired && ($isExtMeReq || $isDemo)) {
            if ($isDemo && ($user['status'] ?? '') !== 'expired') {
                $user['status'] = 'expired';
                $user['updatedAt'] = Db::isoNow();
                if ($useMysqlFast) {
                    Db::upsertSingleUser($user);
                } elseif ($userIdx >= 0 && is_array($users)) {
                    $users[$userIdx] = $user;
                    Db::writeUsers($users, ['immediate' => true]);
                }
            }
            $expMsg = $isDemo
                ? 'Your demo time has ended. Please upgrade to a Pro or Max plan to continue.'
                : 'Your access plan has expired. Please renew your plan.';
            Http::json([
                'authenticated' => false,
                'locked' => true,
                'forceLogout' => true,
                'expired' => true,
                'demoExpired' => $isDemo,
                'error' => $expMsg,
                'message' => $expMsg,
            ], 401);
        }

        if (($user['status'] ?? '') === 'suspended') {
            Http::json([
                'locked' => true,
                'error' => 'Your account has been suspended',
                'message' => 'Your account has been suspended',
            ], 403);
        }

        $userModified = false;

        if (!empty($user['isLocked']) || ($user['status'] ?? '') === 'locked') {
            $user['isLocked'] = false;
            if (($user['status'] ?? '') === 'locked') {
                $user['status'] = 'active';
            }
            $user['lockReason'] = null;
            $user['lockedAt'] = null;
            $userModified = true;
        }

        $tokenVersion = (int)($payload['sessionVersion'] ?? 1);
        if (empty($user['singleDeviceEnforced']) && empty($payload['sid'])) {
            $user['sessionVersion'] = $tokenVersion;
            $user['singleDeviceEnforced'] = true;
            $userModified = true;
        } elseif (self::isTokenSessionRevoked($user, $payload)) {
            if ($userModified) {
                if ($useMysqlFast) {
                    Db::upsertSingleUser($user);
                } elseif ($userIdx >= 0 && is_array($users)) {
                    $users[$userIdx] = $user;
                    Db::writeUsers($users);
                }
            }
            $maxDevsMsg = self::getUserMaxDevices($user);
            $limitNote = $maxDevsMsg === 1
                ? 'Only 1 active device is allowed at a time.'
                : "Your plan allows up to {$maxDevsMsg} active devices.";
            Http::json([
                'authenticated' => false,
                'locked' => true,
                'forceLogout' => true,
                'loggedOutOtherDevice' => true,
                'error' => 'Logged out: Your account was signed in on another device.',
                'message' => "Logged out: Your account was signed in on another device. {$limitNote}",
            ], 401);
        }

        if ($userModified) {
            if ($useMysqlFast) {
                Db::upsertSingleUser($user);
            } elseif ($userIdx >= 0 && is_array($users)) {
                $users[$userIdx] = $user;
                Db::writeUsers($users);
            }
        }

        if (!empty($user['resellerId'])) {
            $rid = (int)$user['resellerId'];
            $r = null;
            if ($useMysqlFast) {
                $r = Db::findResellerByIdMysql($rid);
            } else {
                $resellers = Db::readResellers();
                foreach ($resellers as $rx) {
                    if ((int)($rx['id'] ?? 0) === $rid) {
                        $r = $rx;
                        break;
                    }
                }
            }
            if ($r) {
                $settings = Db::readSettings();
                $scopedUsers = $useMysqlFast ? Db::readUsersByResellerMysql($rid) : ($users ?? Db::readUsers());
                $billing = self::getResellerBillingInfo($r, $scopedUsers, $settings);
                $billedUser = null;
                foreach ($billing['myUsers'] as $bu) {
                    if (($bu['id'] ?? null) === ($user['id'] ?? null)) {
                        $billedUser = $bu;
                        break;
                    }
                }
                if ($billedUser && !empty($billedUser['isUnpaidOverdue'])) {
                    Http::json([
                        'locked' => true,
                        'error' => 'ResellerUnpaid',
                        'message' => 'your reseller has not paid',
                        'detailedMessage' => 'Your account is temporarily suspended because your reseller has an unpaid balance after the 3-day grace period. Please contact your reseller.',
                    ], 403);
                }
                if (($r['status'] ?? '') === 'suspended' && (!$billedUser || empty($billedUser['isPaid']))) {
                    Http::json([
                        'locked' => true,
                        'error' => 'ResellerSuspended',
                        'message' => 'your reseller has not paid',
                        'detailedMessage' => 'Your account is temporarily suspended because your reseller account is inactive. Please contact your reseller.',
                    ], 403);
                }
            }
        }

        self::recordActiveClientSession($user);
        return $user;
    }

    public static function requireReseller(): array
    {
        $auth = Http::header('authorization');
        if ($auth === '' || !str_starts_with($auth, 'Bearer ')) {
            Http::json(['error' => 'Unauthorized', 'message' => 'Reseller token required'], 401);
        }
        $token = trim(substr($auth, 7));
        $payload = Db::verifyToken($token);
        if (!$payload || ($payload['role'] ?? '') !== 'reseller') {
            Http::json(['error' => 'Forbidden', 'message' => 'Valid reseller session required'], 403);
        }
        $resellers = Db::readResellers();
        $reseller = null;
        foreach ($resellers as $r) {
            if (($r['id'] ?? null) === ($payload['resellerId'] ?? null)) {
                $reseller = $r;
                break;
            }
        }
        if (!$reseller) {
            Http::json(['error' => 'NotFound', 'message' => 'Reseller account not found'], 401);
        }
        if (($reseller['status'] ?? '') === 'suspended') {
            Http::json(['error' => 'Suspended', 'message' => 'Reseller account is suspended'], 403);
        }
        return $reseller;
    }

    public static function getForcedLogoutPayload(): ?array
    {
        $token = self::getBearerToken();
        if (!$token) {
            return null;
        }
        $payload = Db::verifyToken($token);
        if (!$payload || (empty($payload['userId']) && empty($payload['email']))) {
            return null;
        }
        $useMysqlFast = Database::isEnabled();
        $users = null;
        if ($useMysqlFast) {
            $user = Db::findUserByTokenMysql(
                isset($payload['userId']) ? (string)$payload['userId'] : null,
                Db::sanitizeEmail($payload['email'] ?? '')
            );
        } else {
            $users = Db::readUsers();
            $user = self::findUserByTokenPayload($users, $payload);
        }
        if (!$user) {
            return [
                'success' => false,
                'forceLogout' => true,
                'userDeleted' => true,
                'locked' => true,
                'error' => 'Your account has been removed by Admin.',
            ];
        }
        if (self::isUserPlanExpired($user)) {
            return [
                'success' => false,
                'forceLogout' => true,
                'expired' => true,
                'locked' => true,
                'error' => 'Your access plan has expired. Please renew your plan.',
            ];
        }
        $tokenVersion = (int)($payload['sessionVersion'] ?? 1);
        if (empty($user['singleDeviceEnforced']) && empty($payload['sid'])) {
            if ($useMysqlFast) {
                $user['sessionVersion'] = $tokenVersion;
                $user['singleDeviceEnforced'] = true;
                Db::upsertSingleUser($user);
            } else {
                foreach ($users as $i => $u) {
                    if (($u['id'] ?? null) === ($user['id'] ?? null)) {
                        $users[$i]['sessionVersion'] = $tokenVersion;
                        $users[$i]['singleDeviceEnforced'] = true;
                        Db::writeUsers($users);
                        break;
                    }
                }
            }
            return null;
        }
        if (self::isTokenSessionRevoked($user, $payload)) {
            return [
                'success' => false,
                'forceLogout' => true,
                'loggedOutOtherDevice' => true,
                'locked' => true,
                'error' => 'Logged out: Your account was signed in on another device.',
            ];
        }
        return null;
    }

    public static function isRequestDeviceRevoked(): bool
    {
        return self::getForcedLogoutPayload() !== null;
    }

    /**
     * Single-query access check for high-traffic /api/cookies/* & /api/slots endpoints.
     * Avoids duplicate SELECTs on users and avoids writing sessions_by_ip on every cookie poll.
     */
    public static function resolveSlotRequestAccess(bool $checkForcedLogout = true): array
    {
        $token = self::getBearerToken();
        if ($token) {
            $payload = Db::verifyToken($token);
            if (!$payload || (empty($payload['userId']) && empty($payload['email']))) {
                return ['forcedLogout' => null, 'flowLocked' => true, 'user' => null];
            }
            $useMysqlFast = Database::isEnabled();
            $users = null;
            if ($useMysqlFast) {
                $user = Db::findUserByTokenMysql(
                    isset($payload['userId']) ? (string)$payload['userId'] : null,
                    Db::sanitizeEmail($payload['email'] ?? '')
                );
            } else {
                $users = Db::readUsers();
                $user = self::findUserByTokenPayload($users, $payload);
            }
            if (!$user) {
                return [
                    'forcedLogout' => $checkForcedLogout ? [
                        'success' => false,
                        'forceLogout' => true,
                        'userDeleted' => true,
                        'locked' => true,
                        'error' => 'Your account has been removed by Admin.',
                    ] : null,
                    'flowLocked' => true,
                    'user' => null,
                ];
            }
            if ($checkForcedLogout && self::isUserPlanExpired($user)) {
                return [
                    'forcedLogout' => [
                        'success' => false,
                        'forceLogout' => true,
                        'expired' => true,
                        'locked' => true,
                        'error' => 'Your access plan has expired. Please renew your plan.',
                    ],
                    'flowLocked' => true,
                    'user' => $user,
                ];
            }
            if ($checkForcedLogout) {
                $tokenVersion = (int)($payload['sessionVersion'] ?? 1);
                if (empty($user['singleDeviceEnforced']) && empty($payload['sid'])) {
                    $user['sessionVersion'] = $tokenVersion;
                    $user['singleDeviceEnforced'] = true;
                    if ($useMysqlFast) {
                        Db::upsertSingleUser($user);
                    } elseif (is_array($users)) {
                        foreach ($users as $i => $u) {
                            if (($u['id'] ?? null) === ($user['id'] ?? null)) {
                                $users[$i] = $user;
                                Db::writeUsers($users);
                                break;
                            }
                        }
                    }
                } elseif (self::isTokenSessionRevoked($user, $payload)) {
                    return [
                        'forcedLogout' => [
                            'success' => false,
                            'forceLogout' => true,
                            'loggedOutOtherDevice' => true,
                            'locked' => true,
                            'error' => 'Logged out: Your account was signed in on another device.',
                        ],
                        'flowLocked' => true,
                        'user' => $user,
                    ];
                }
            }
            $isBasicPlan = self::isPlanBasicOrLocked($user['plan'] ?? 'basic');
            $isSuspended = (($user['status'] ?? 'active') === 'suspended');
            $creditsExhausted = (!$isBasicPlan && array_key_exists('credits', $user) && (int)$user['credits'] <= 0);
            $locked = $isBasicPlan || $isSuspended || $creditsExhausted;
            return [
                'forcedLogout' => null,
                'flowLocked' => $locked,
                'creditsExhausted' => $creditsExhausted,
                'user' => $user,
            ];
        }

        return ['forcedLogout' => null, 'flowLocked' => self::isRequestFlowLocked(), 'user' => null];
    }

    public static function isRequestFlowLocked(): bool
    {
        $token = self::getBearerToken();
        if ($token) {
            $payload = Db::verifyToken($token);
            if (!$payload || (empty($payload['userId']) && empty($payload['email']))) {
                return true;
            }
            if (Database::isEnabled()) {
                $user = Db::findUserByTokenMysql(
                    isset($payload['userId']) ? (string)$payload['userId'] : null,
                    Db::sanitizeEmail($payload['email'] ?? '')
                );
            } else {
                $users = Db::readUsers();
                $user = self::findUserByTokenPayload($users, $payload);
            }
            if (!$user) {
                return true;
            }
            self::recordActiveClientSession($user);
            return self::isPlanBasicOrLocked($user['plan'] ?? 'basic') || self::isUserPlanExpired($user);
        }

        $uaKey = self::getClientUaKey();
        $ip = self::getClientIp();
        if (Database::isEnabled()) {
            $pdo = Database::pdo();
            $stmt = $pdo->prepare('SELECT session_key, user_id, email, plan, locked FROM sessions_by_ip WHERE session_key IN (?, ?)');
            $stmt->execute([$uaKey, $ip]);
            $rows = [];
            foreach ($stmt->fetchAll() as $r) {
                $rows[$r['session_key']] = $r;
            }
            $recent = $rows[$uaKey] ?? ($rows[$ip] ?? null);
            if (is_array($recent)) {
                if (!empty($recent['user_id']) || !empty($recent['email'])) {
                    $u = Db::findUserByTokenMysql(
                        isset($recent['user_id']) ? (string)$recent['user_id'] : null,
                        Db::sanitizeEmail($recent['email'] ?? '')
                    );
                    if ($u) {
                        return self::isPlanBasicOrLocked($u['plan'] ?? 'basic');
                    }
                }
                return !empty($recent['locked']);
            }
            $uStmt = $pdo->prepare('SELECT * FROM users WHERE last_login_ip = ? ORDER BY last_login_at DESC LIMIT 1');
            $uStmt->execute([$ip]);
            $row = $uStmt->fetch();
            if (is_array($row)) {
                return self::isPlanBasicOrLocked($row['plan'] ?? 'basic');
            }
            return true;
        }
        $map = self::readSessionsByIp();
        $recent = $map[$uaKey] ?? ($map[$ip] ?? null);
        if (is_array($recent)) {
            if (!empty($recent['userId']) || !empty($recent['email'])) {
                $users = Db::readUsers();
                $u = self::findUserByTokenPayload($users, ['userId' => $recent['userId'] ?? null, 'email' => $recent['email'] ?? null]);
                if ($u) {
                    return self::isPlanBasicOrLocked($u['plan'] ?? 'basic');
                }
            }
            return !empty($recent['locked']);
        }

        $users = Db::readUsers();
        $idx = Db::getUserIndexes($users);
        if (isset($idx['latestByIp'][$ip]['user'])) {
            return self::isPlanBasicOrLocked($idx['latestByIp'][$ip]['user']['plan'] ?? 'basic');
        }
        return true;
    }

    public static function getSlotTypeTag(int $slotNumber): string
    {
        if ($slotNumber === 1) {
            return 'Video';
        }
        if ($slotNumber >= 11 && $slotNumber <= 15) {
            return 'Nano Banana Pro';
        }
        return 'Images';
    }

    public static function decodeValidBase64Image($rawImageData): ?array
    {
        if (!is_string($rawImageData)) {
            return null;
        }
        if (!preg_match('/^data:image\/(png|jpe?g|webp|gif);base64,([A-Za-z0-9+\/=\s]+)$/i', $rawImageData, $m)) {
            return null;
        }
        $buf = base64_decode($m[2], true);
        if ($buf === false || strlen($buf) < 12) {
            return null;
        }
        $b0 = ord($buf[0]);
        $b1 = ord($buf[1]);
        $b2 = ord($buf[2]);
        $b3 = ord($buf[3]);
        $isPng = ($b0 === 0x89 && $b1 === 0x50 && $b2 === 0x4E && $b3 === 0x47);
        $isJpg = ($b0 === 0xFF && $b1 === 0xD8 && $b2 === 0xFF);
        $isGif = ($b0 === 0x47 && $b1 === 0x49 && $b2 === 0x46 && $b3 === 0x38);
        $isWebp = ($b0 === 0x52 && $b1 === 0x49 && $b2 === 0x46 && $b3 === 0x46
            && ord($buf[8]) === 0x57 && ord($buf[9]) === 0x45 && ord($buf[10]) === 0x42 && ord($buf[11]) === 0x50);

        if (!$isPng && !$isJpg && !$isGif && !$isWebp) {
            return null;
        }
        $ext = $isPng ? 'png' : ($isJpg ? 'jpg' : ($isWebp ? 'webp' : 'gif'));
        return ['buffer' => $buf, 'ext' => $ext];
    }

    public static function getResellerBillingInfo(array $reseller, array $allUsers, array $settings): array
    {
        $baseRates = $settings['resellerRates'] ?? [
            'pro' => 500,
            'max' => 800,
            'basic' => 0,
            'ultra' => 800,
            'plus' => 500,
            'vibe' => 0,
        ];
        $rIdKey = (string)($reseller['id'] ?? '');
        $myUsers = Db::getUserIndexes($allUsers)['byResellerId'][$rIdKey] ?? [];

        $rates = array_merge($baseRates, is_array($reseller['customRates'] ?? null) ? $reseller['customRates'] : []);
        if (isset($reseller['proRate']) && $reseller['proRate'] !== null && $reseller['proRate'] !== '') {
            $rates['pro'] = (float)$reseller['proRate'];
            $rates['plus'] = (float)$reseller['proRate'];
        }
        if (isset($reseller['maxRate']) && $reseller['maxRate'] !== null && $reseller['maxRate'] !== '') {
            $rates['max'] = (float)$reseller['maxRate'];
            $rates['ultra'] = (float)$reseller['maxRate'];
        }
        $planCredits = self::getResellerPlanCredits($reseller, $settings);

        $now = (int)round(microtime(true) * 1000);
        $graceMs = 3 * 24 * 60 * 60 * 1000;
        $todayStartTime = strtotime('today midnight') * 1000;
        $weekStartTime = $now - (7 * 24 * 60 * 60 * 1000);

        $usersCharges = 0;
        foreach ($myUsers as $u) {
            $isDemoU = self::isDemoUser($u);
            if ($isDemoU) {
                continue;
            }
            $p = strtolower((string)($u['plan'] ?? 'pro'));
            $baseRate = (float)($rates[$p] ?? ($p === 'max' ? 800 : 500));
            $maxDevs = self::getUserMaxDevices($u);
            $billedDevs = max($maxDevs, (int)($u['billedDevices'] ?? 1));
            $usersCharges += ($baseRate * $billedDevs);
        }

        $deletedCharges = count($myUsers) === 0 ? 0 : (float)($reseller['deletedCharges'] ?? 0);
        $rawTotalCharges = $usersCharges + $deletedCharges;
        $discountAmount = (float)($reseller['discount'] ?? 0);
        $discountPercent = (float)($reseller['discountPercent'] ?? 0);
        $totalDiscount = $discountAmount;
        if ($discountPercent > 0) {
            $totalDiscount += round(($rawTotalCharges * $discountPercent) / 100);
        }
        $totalDiscount = min($rawTotalCharges, $totalDiscount);
        $totalCharges = max(0, $rawTotalCharges - $totalDiscount);
        $totalPaid = (float)($reseller['totalPaid'] ?? 0);
        $balanceDue = max(0, $totalCharges - $totalPaid);

        $sortedOldestFirst = $myUsers;
        usort($sortedOldestFirst, function ($a, $b) {
            $ta = !empty($a['createdAt']) ? ((strtotime((string)$a['createdAt']) ?: 0) * 1000) : 0;
            $tb = !empty($b['createdAt']) ? ((strtotime((string)$b['createdAt']) ?: 0) * 1000) : 0;
            if ($ta !== $tb) {
                return $ta <=> $tb;
            }
            return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
        });

        $remainingCredit = max(0, $totalPaid + $totalDiscount - $deletedCharges);
        $paidMap = [];
        foreach ($sortedOldestFirst as $u) {
            $isDemoU = self::isDemoUser($u);
            $p = strtolower((string)($u['plan'] ?? 'pro'));
            $maxDevs = self::getUserMaxDevices($u);
            $billedDevs = $isDemoU ? 1 : max($maxDevs, (int)($u['billedDevices'] ?? 1));
            $baseRate = $isDemoU ? 0.0 : (float)($rates[$p] ?? ($p === 'max' ? 800 : 500));
            $rate = $isDemoU ? 0.0 : ($baseRate * $billedDevs);
            $uid = $u['id'];
            if ($rate <= 0 || $remainingCredit >= $rate) {
                $remainingCredit = max(0, $remainingCredit - $rate);
                $paidMap[$uid] = ['isPaid' => true, 'amountDue' => 0, 'rate' => $rate, 'baseRate' => $baseRate, 'billedDevices' => $billedDevs];
            } else {
                $userDue = $rate - $remainingCredit;
                $remainingCredit = 0;
                $paidMap[$uid] = ['isPaid' => false, 'amountDue' => $userDue, 'rate' => $rate, 'baseRate' => $baseRate, 'billedDevices' => $billedDevs];
            }
        }

        $usersToday = 0;
        $usersThisWeek = 0;
        $graceUsersCount = 0;
        $overdueUsersCount = 0;
        $paidUsersCount = 0;
        $usersFormatted = [];

        foreach ($myUsers as $u) {
            $createdTs = !empty($u['createdAt']) ? (strtotime((string)$u['createdAt']) ?: time()) : time();
            $createdTime = $createdTs * 1000;
            $ageMs = max(0, $now - $createdTime);
            $rawPastGrace = $ageMs > $graceMs;
            $hoursLeftInGrace = max(0, (int)ceil(($graceMs - $ageMs) / (60 * 60 * 1000)));
            $daysSinceCreation = (int)floor($ageMs / (24 * 60 * 60 * 1000));

            if ($createdTime >= $todayStartTime) {
                $usersToday++;
            }
            if ($createdTime >= $weekStartTime) {
                $usersThisWeek++;
            }

            $isDemoU = self::isDemoUser($u);
            $maxDevs = self::getUserMaxDevices($u);
            $payInfo = $paidMap[$u['id']] ?? ['isPaid' => $isDemoU, 'amountDue' => $isDemoU ? 0 : 500, 'rate' => $isDemoU ? 0 : 500, 'baseRate' => $isDemoU ? 0 : 500, 'billedDevices' => $maxDevs];
            $isPaid = (bool)$payInfo['isPaid'];
            $inGracePeriod = !$isPaid && !$rawPastGrace;
            $isPastGrace = !$isPaid && $rawPastGrace;
            $isUnpaidOverdue = $isPastGrace;

            if ($isPaid) {
                $paidUsersCount++;
            } elseif ($inGracePeriod) {
                $graceUsersCount++;
            } else {
                $overdueUsersCount++;
            }

            $expTsU = !empty($u['planExpiresAt']) ? (strtotime((string)$u['planExpiresAt']) ?: 0) : 0;
            $remSecU = $expTsU > time() ? ($expTsU - time()) : 0;
            $rawSt = ($isDemoU && $remSecU <= 0 && ($u['status'] ?? 'active') !== 'suspended') ? 'expired' : ($u['status'] ?? 'active');
            $effectiveStatus = $isUnpaidOverdue ? 'unpaid_suspended' : $rawSt;

            $uPlanLower = strtolower((string)($u['plan'] ?? 'pro'));
            $defaultTotalCr = $isDemoU
                ? (int)($planCredits['demo'] ?? 2000)
                : (($uPlanLower === 'max' || $uPlanLower === 'ultra') ? $planCredits['max'] : $planCredits['pro']);
            $usersFormatted[] = [
                'id' => $u['id'],
                'name' => $u['name'] ?? '',
                'username' => $u['username'] ?? '',
                'email' => $u['email'] ?? '',
                'plainPasswordHint' => $u['plainPasswordHint'] ?? null,
                'plan' => $isDemoU ? 'demo' : ($u['plan'] ?? 'pro'),
                'isDemo' => $isDemoU,
                'demoHours' => isset($u['demoHours']) ? (float)$u['demoHours'] : ($isDemoU ? 2.0 : null),
                'remainingSeconds' => $remSecU,
                'maxDevices' => $maxDevs,
                'billedDevices' => (int)($payInfo['billedDevices'] ?? $maxDevs),
                'credits' => (int)($u['credits'] ?? 0),
                'creditsTotal' => (int)($u['creditsTotal'] ?? 0) ?: $defaultTotalCr,
                'planExpiresAt' => $u['planExpiresAt'] ?? null,
                'daysRemaining' => Db::calculateDaysRemaining($u['planExpiresAt'] ?? null),
                'status' => $effectiveStatus,
                'rawStatus' => $rawSt,
                'createdAt' => $u['createdAt'] ?? null,
                'ageMs' => $ageMs,
                'daysSinceCreation' => $daysSinceCreation,
                'isPaid' => $isPaid,
                'amountDue' => $payInfo['amountDue'],
                'inGracePeriod' => $inGracePeriod,
                'isPastGrace' => $isPastGrace,
                'isUnpaidOverdue' => $isUnpaidOverdue,
                'hoursLeftInGrace' => $isPaid ? 0 : $hoursLeftInGrace,
                'planRate' => $payInfo['rate'],
                'basePlanRate' => $payInfo['baseRate'],
            ];
        }

        $isOverdue = ($balanceDue > 0 && $overdueUsersCount > 0);
        $isSuspended = (($reseller['status'] ?? '') === 'suspended') || $isOverdue;

        return [
            'rates' => $rates,
            'planCredits' => $planCredits,
            'myUsers' => $usersFormatted,
            'usersCount' => count($myUsers),
            'paidUsersCount' => $paidUsersCount,
            'usersToday' => $usersToday,
            'usersThisWeek' => $usersThisWeek,
            'graceUsersCount' => $graceUsersCount,
            'overdueUsersCount' => $overdueUsersCount,
            'usersCharges' => $usersCharges,
            'deletedCharges' => $deletedCharges,
            'rawTotalCharges' => $rawTotalCharges,
            'discountAmount' => $discountAmount,
            'discountPercent' => $discountPercent,
            'totalDiscount' => $totalDiscount,
            'totalCharges' => $totalCharges,
            'totalPaid' => $totalPaid,
            'balanceDue' => $balanceDue,
            'isOverdue' => $isOverdue,
            'isSuspended' => $isSuspended,
        ];
    }

    public static function formatSafeUser(array $user): array
    {
        $expTs = !empty($user['planExpiresAt']) ? (strtotime((string)$user['planExpiresAt']) ?: 0) : 0;
        $remSec = $expTs > time() ? ($expTs - time()) : 0;
        $days = Db::calculateDaysRemaining($user['planExpiresAt'] ?? null);
        $isDemo = self::isDemoUser($user);
        $normalizedPlan = $isDemo ? 'demo' : self::normalizeClientPlan($user['plan'] ?? 'basic', (string)($user['email'] ?? ''));
        $isHeavyUser = ($normalizedPlan === 'heavy');
        if ($isDemo) {
            if ($remSec <= 0) {
                $statusText = 'Demo Expired';
            } elseif ($remSec < 3600) {
                $statusText = max(1, (int)ceil($remSec / 60)) . 'm Left';
            } else {
                $hrs = (int)floor($remSec / 3600);
                $mins = (int)floor(($remSec % 3600) / 60);
                $statusText = $mins > 0 ? "{$hrs}h {$mins}m Left" : "{$hrs}h Left";
            }
        } else {
            $statusText = $days === 1 ? '1 Day Left' : "$days Days Left";
        }
        return [
            'id' => $user['id'],
            'name' => $user['name'] ?? '',
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
            'plan' => $normalizedPlan,
            'rawPlan' => $isHeavyUser ? 'heavy' : ($user['plan'] ?? 'basic'),
            'planLabel' => $isDemo ? 'Demo Plan' : self::formatClientPlanLabel($normalizedPlan),
            'isDemo' => $isDemo,
            'demoHours' => isset($user['demoHours']) ? (float)$user['demoHours'] : ($isDemo ? 2.0 : null),
            'remainingSeconds' => $remSec,
            'isHeavy' => $isHeavyUser,
            'heavyCredits' => $isHeavyUser ? ((int)($user['heavyCredits'] ?? 6200) ?: 6200) : 0,
            'ttsCredits' => (int)($user['ttsCredits'] ?? 45000) ?: 45000,
            'planActive' => ($isDemo ? ($remSec > 0) : ($days > 0)) && !in_array(($user['status'] ?? 'active'), ['suspended', 'expired'], true),
            'credits' => $user['credits'] ?? 0,
            'creditsTotal' => $user['creditsTotal'] ?? ($user['credits'] ?? 0),
            'planExpiresAt' => $user['planExpiresAt'] ?? null,
            'daysRemaining' => $days,
            'daysLeft' => $days,
            'statusText' => $statusText,
            'onboardingAccepted' => $user['onboardingAccepted'] ?? true,
            'emailVerified' => true,
            'status' => ($isDemo && $remSec <= 0) ? 'expired' : ($user['status'] ?? 'active'),
            'createdAt' => $user['createdAt'] ?? null,
            'maxDevices' => self::getUserMaxDevices($user),
            'activeDevicesCount' => max(1, count(self::normalizeActiveDeviceSessions($user))),
            'deviceSwitchCount' => (int)($user['deviceSwitchCount'] ?? 0),
            'isLocked' => !empty($user['isLocked']),
        ];
    }

    public static function normalizeWhatsappLink(?string $raw): array
    {
        $val = trim((string)($raw ?? ''));
        if ($val === '') {
            return ['phone' => '', 'url' => ''];
        }
        if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) {
            return ['phone' => $val, 'url' => $val];
        }
        $digits = preg_replace('/\D+/', '', $val) ?? '';
        if ($digits === '') {
            return ['phone' => $val, 'url' => ''];
        }
        // Convert Pakistani local format 03xxxxxxxxx -> 923xxxxxxxxx
        if (strlen($digits) === 11 && str_starts_with($digits, '03')) {
            $digits = '92' . substr($digits, 1);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '92' . $digits;
        }
        return [
            'phone' => $val,
            'url' => 'https://wa.me/' . $digits,
        ];
    }

    public static function resolveUserSupportWhatsapp(array $user, ?array $settings = null): array
    {
        $settings = $settings ?? Db::readSettings();
        $resellerName = '';
        $resellerPhone = '';

        if (!empty($user['resellerId'])) {
            $rid = (int)$user['resellerId'];
            $reseller = null;
            if (Database::isEnabled()) {
                $reseller = Db::findResellerByIdMysql($rid);
            } else {
                foreach (Db::readResellers() as $rx) {
                    if ((int)($rx['id'] ?? 0) === $rid) {
                        $reseller = $rx;
                        break;
                    }
                }
            }
            if ($reseller) {
                $resellerName = trim((string)($reseller['name'] ?? ''));
                $resellerPhone = trim((string)($reseller['whatsapp'] ?? ''));
            }
        }

        if ($resellerPhone !== '') {
            $norm = self::normalizeWhatsappLink($resellerPhone);
            if ($norm['url'] !== '') {
                return [
                    'url' => $norm['url'],
                    'phone' => $norm['phone'],
                    'label' => $resellerName !== '' ? $resellerName : 'Your Reseller',
                    'source' => 'reseller',
                ];
            }
        }

        $fallbackRaw = trim((string)($settings['whatsappPhone'] ?? ''))
            ?: trim((string)($settings['whatsappChannel'] ?? ''));
        $norm = self::normalizeWhatsappLink($fallbackRaw);
        return [
            'url' => $norm['url'],
            'phone' => $norm['phone'],
            'label' => 'PakFlow Support',
            'source' => 'platform',
        ];
    }

    public static function computeUserAssignedSlot(?array $user, array $slots, ?array $settings = null): array
    {
        $settings = $settings ?? Db::readSettings();
        $defaults = Db::defaultSettings()['serverShuffle'];
        $cfg = array_merge($defaults, is_array($settings['serverShuffle'] ?? null) ? $settings['serverShuffle'] : []);

        $enabled = !empty($cfg['enabled']);
        $intervalMin = max(5, min(43200, (int)($cfg['intervalMinutes'] ?? 180)));
        $epoch = max(1, (int)($cfg['shuffleEpoch'] ?? 1));
        $fallbackDefault = max(1, min(10, (int)($cfg['defaultSlot'] ?? 1)));
        $configuredPool = is_array($cfg['poolSlots'] ?? null)
            ? array_values(array_unique(array_filter(array_map('intval', $cfg['poolSlots']), static fn(int $n): bool => $n >= 1 && $n <= 10)))
            : [];

        $activeNums = [];
        foreach ($slots as $s) {
            $num = (int)($s['slotNumber'] ?? 0);
            $cnt = (int)($s['cookieCount'] ?? count($s['cookies'] ?? []));
            // Require >= 3 cookies so broken 1-cookie slots (e.g. partial paste) are never auto-assigned to users
            if ($num >= 1 && $num <= 10 && ($s['status'] ?? '') === 'ACTIVE' && $cnt >= 3) {
                $activeNums[] = $num;
            }
        }
        if (empty($activeNums)) {
            foreach ($slots as $s) {
                $num = (int)($s['slotNumber'] ?? 0);
                if ($num >= 1 && $num <= 10 && ($s['status'] ?? '') === 'ACTIVE' && (int)($s['cookieCount'] ?? count($s['cookies'] ?? [])) > 0) {
                    $activeNums[] = $num;
                }
            }
        }
        sort($activeNums);

        $pool = !empty($configuredPool)
            ? array_values(array_intersect($configuredPool, $activeNums))
            : $activeNums;
        if (empty($pool)) {
            $pool = !empty($activeNums) ? $activeNums : [$fallbackDefault];
        }

        $now = time();
        $intervalSec = $intervalMin * 60;
        $anchor = (int)($cfg['shuffleAnchorTs'] ?? 0);
        if ($anchor > 0 && $anchor <= $now + 60) {
            $elapsed = max(0, $now - $anchor);
            $bucket = (int)floor($elapsed / $intervalSec);
            $nextShuffleTs = $anchor + (($bucket + 1) * $intervalSec);
        } else {
            $bucket = (int)floor($now / $intervalSec);
            $nextShuffleTs = ($bucket + 1) * $intervalSec;
        }
        $period = $enabled ? ($bucket + ($epoch * 100000)) : $epoch;

        $forcedTrialSlot = self::getSiteTrialAssignedSlot($user, $settings);
        if ($forcedTrialSlot >= 1 && $forcedTrialSlot <= 10) {
            return [
                'defaultSlot' => $forcedTrialSlot,
                'defaultSlotId' => 'slot_' . $forcedTrialSlot,
                'shuffleEnabled' => false,
                'shuffleIntervalMinutes' => $intervalMin,
                'shuffleEpoch' => $epoch,
                'shufflePeriod' => 'trial-' . $forcedTrialSlot,
                'shuffleAnchorTs' => $now,
                'serverTime' => $now,
                'nextShuffleTs' => $nextShuffleTs,
                'remainingSeconds' => 0,
                'nextShuffleAt' => null,
                'lastShuffledAt' => $cfg['lastShuffledAt'] ?? null,
                'poolSlots' => [$forcedTrialSlot],
                'activePoolCount' => 1,
                'lockedToTrialSlot' => true,
            ];
        }

        $assigned = $fallbackDefault;
        if ($enabled && !empty($pool)) {
            $baseKey = $user
                ? ((string)($user['id'] ?? '') . ':' . strtolower(trim((string)($user['email'] ?? ''))))
                : ('anon:' . self::getClientIp());
            $baseOffset = (int)sprintf('%u', crc32($baseKey));
            // Deterministic guaranteed rotation: every Reshuffle Now (epoch+1) or timer tick (bucket+1)
            // rotates EVERY user to a different active session while keeping load evenly balanced.
            $idx = ($baseOffset + $epoch + $bucket) % count($pool);
            $assigned = $pool[$idx];
        } elseif (!in_array($assigned, $activeNums, true) && !empty($activeNums)) {
            $assigned = $activeNums[0];
        }

        return [
            'defaultSlot' => $assigned,
            'defaultSlotId' => 'slot_' . $assigned,
            'shuffleEnabled' => $enabled,
            'shuffleIntervalMinutes' => $intervalMin,
            'shuffleEpoch' => $epoch,
            'shufflePeriod' => (string)$period,
            'shuffleAnchorTs' => $anchor > 0 ? $anchor : ($bucket * $intervalSec),
            'serverTime' => $now,
            'nextShuffleTs' => $nextShuffleTs,
            'remainingSeconds' => max(0, $nextShuffleTs - $now),
            'nextShuffleAt' => gmdate('Y-m-d\TH:i:s', $nextShuffleTs) . '.000Z',
            'lastShuffledAt' => $cfg['lastShuffledAt'] ?? null,
            'poolSlots' => $pool,
            'activePoolCount' => count($pool),
        ];
    }
}
