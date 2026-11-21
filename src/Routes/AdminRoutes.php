<?php
declare(strict_types=1);

namespace App\Routes;

use App\Db;
use App\Env;
use App\ExpiryNotifier;
use App\Helpers;
use App\Http;
use App\Mailer;

final class AdminRoutes
{
    public static function dispatch(string $method, string $path): bool
    {
        if ($method === 'GET' && $path === '/admin/verify') {
            $admin = Helpers::requireAdmin();
            Http::json(['success' => true, 'authenticated' => true, 'ok' => true, 'admin' => $admin]);
        }
        if ($method === 'GET' && $path === '/admin/stats') {
            Helpers::requireAdmin();
            self::adminStats();
            return true;
        }
        if ($method === 'GET' && $path === '/admin/users') {
            Helpers::requireAdmin();
            self::adminUsersList();
            return true;
        }
        if ($method === 'POST' && $path === '/admin/users') {
            Helpers::requireAdmin();
            self::adminCreateUser();
            return true;
        }
        if ($method === 'POST' && $path === '/admin/users/reset-limit-by-email') {
            Helpers::requireAdmin();
            self::adminResetLimitByEmail();
            return true;
        }
        if ($method === 'PUT' && preg_match('#^/admin/users/(\d+)$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminUpdateUser((int)$m[1]);
            return true;
        }
        if ($method === 'POST' && preg_match('#^/admin/users/(\d+)/reset-device-lock$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminResetDeviceLock((int)$m[1]);
            return true;
        }
        if ($method === 'DELETE' && preg_match('#^/admin/users/(\d+)$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminDeleteUser((int)$m[1]);
            return true;
        }
        if ($method === 'POST' && preg_match('#^/admin/users/(\d+)/reset-limit$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminResetLimit((int)$m[1]);
            return true;
        }
        if ($method === 'GET' && $path === '/admin/pending-approvals') {
            Helpers::requireAdmin();
            $users = Db::readUsers();
            $pending = [];
            foreach ($users as $u) {
                $st = $u['status'] ?? '';
                if ($st === 'pending' || $st === 'pending_approval') {
                    $pending[] = [
                        'id' => $u['id'],
                        'name' => $u['name'] ?? '',
                        'username' => $u['username'] ?? '',
                        'email' => $u['email'] ?? '',
                        'plainPasswordHint' => $u['plainPasswordHint'] ?? null,
                        'plan' => $u['plan'] ?? 'pro',
                        'requestedDays' => (int)($u['requestedDays'] ?? $u['daysRemaining'] ?? (($u['plan'] ?? '') === 'max' ? 30 : 20)),
                        'resellerId' => $u['resellerId'] ?? null,
                        'resellerName' => $u['resellerName'] ?? null,
                        'resellerEmail' => $u['resellerEmail'] ?? null,
                        'createdAt' => $u['createdAt'] ?? null,
                        'status' => $st,
                    ];
                }
            }
            Http::json(['success' => true, 'pending' => $pending, 'total' => count($pending)]);
        }
        if ($method === 'PUT' && preg_match('#^/admin/users/(\d+)/approve$#', $path, $m)) {
            Helpers::requireAdmin();
            $userId = (int)$m[1];
            $users = Db::readUsers();
            foreach ($users as $i => $u) {
                if ((int)($u['id'] ?? 0) === $userId) {
                    $days = (int)($u['requestedDays'] ?? $u['daysRemaining'] ?? (($u['plan'] ?? '') === 'max' ? 30 : 20));
                    $users[$i]['status'] = 'active';
                    $users[$i]['planExpiresAt'] = Db::isoNow($days * 86400);
                    $users[$i]['daysRemaining'] = $days;
                    $users[$i]['approvedAt'] = Db::isoNow();
                    Db::writeUsers($users, ['immediate' => true]);
                    $safe = $users[$i];
                    unset($safe['passwordHash']);
                    $planUpper = strtoupper((string)($safe['plan'] ?? 'pro'));
                    Http::json([
                        'success' => true,
                        'message' => "User {$safe['email']} ($planUpper) has been APPROVED and is now ACTIVE for $days days!",
                        'user' => $safe,
                    ]);
                }
            }
            Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
        }
        if ($method === 'PUT' && preg_match('#^/admin/users/(\d+)/reject$#', $path, $m)) {
            Helpers::requireAdmin();
            $userId = (int)$m[1];
            $users = Db::readUsers();
            foreach ($users as $i => $u) {
                if ((int)($u['id'] ?? 0) === $userId) {
                    if (!empty($u['resellerId'])) {
                        $resellers = Db::readResellers();
                        foreach ($resellers as $ri => $r) {
                            if (($r['id'] ?? null) === $u['resellerId'] && ((int)($r['usersCreated'] ?? 0)) > 0) {
                                $resellers[$ri]['usersCreated'] = max(0, (int)$r['usersCreated'] - 1);
                                Db::writeResellers($resellers);
                                break;
                            }
                        }
                    }
                    Db::markUserDeleted($u['id'], (string)($u['email'] ?? ''));
                    array_splice($users, $i, 1);
                    Db::writeUsers($users, ['immediate' => true]);
                    Http::json(['success' => true, 'message' => "User registration request for {$u['email']} has been rejected."]);
                }
            }
            Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
        }

        // Settings & Public Settings
        if ($method === 'GET' && $path === '/settings/public') {
            self::publicSettings();
            return true;
        }
        if ($method === 'GET' && $path === '/admin/settings') {
            Helpers::requireAdmin();
            Http::json(['success' => true, 'settings' => Db::readSettings()]);
        }
        if ($method === 'GET' && $path === '/admin/settings/tools-visibility') {
            Helpers::requireAdmin();
            $settings = Db::readSettings();
            $defaults = Db::defaultSettings()['toolsVisibility'];
            Http::json(['success' => true, 'toolsVisibility' => array_merge($defaults, $settings['toolsVisibility'] ?? [])]);
        }
        if ($method === 'PUT' && $path === '/admin/settings/tools-visibility') {
            Helpers::requireAdmin();
            $body = Http::body();
            $settings = Db::readSettings();
            $defaults = Db::defaultSettings()['toolsVisibility'];
            $settings['toolsVisibility'] = array_merge(
                $defaults,
                $settings['toolsVisibility'] ?? [],
                is_array($body['toolsVisibility'] ?? null) ? $body['toolsVisibility'] : []
            );
            Db::writeSettings($settings);
            Http::json(['success' => true, 'message' => 'Tools visibility settings saved successfully', 'toolsVisibility' => $settings['toolsVisibility']]);
        }
        if ($method === 'PUT' && $path === '/admin/settings') {
            Helpers::requireAdmin();
            $current = Db::readSettings();
            $update = Http::body();
            $sanitized = $update;
            if (is_array($sanitized['surfsharkCodes'] ?? null)) {
                $sanitized['surfsharkCodes'] = array_values(array_filter($sanitized['surfsharkCodes'], function ($item) {
                    $p = strtolower((string)($item['plan'] ?? 'basic'));
                    return !in_array($p, ['basic', 'free', 'trial', 'vibe', 'starter'], true);
                }));
            }
            $merged = array_merge($current, $sanitized);
            foreach (['windowsApp', 'broadcast', 'plans', 'maintenance', 'toolsVisibility', 'extensions'] as $k) {
                if (is_array($current[$k] ?? null) || is_array($update[$k] ?? null)) {
                    $merged[$k] = array_merge($current[$k] ?? [], is_array($update[$k] ?? null) ? $update[$k] : []);
                }
            }
            $merged['allowTrialExtensionAccess'] = array_key_exists('allowTrialExtensionAccess', $update)
                ? (bool)$update['allowTrialExtensionAccess']
                : (($current['allowTrialExtensionAccess'] ?? true) !== false);
            Db::writeSettings($merged);
            Http::json(['success' => true, 'message' => 'Settings saved successfully', 'settings' => $merged]);
        }
        if ($method === 'PUT' && $path === '/admin/settings/trial-extension-access') {
            Helpers::requireAdmin();
            $body = Http::body();
            $settings = Db::readSettings();
            $settings['allowTrialExtensionAccess'] = !empty($body['allowTrialExtensionAccess']);
            Db::writeSettings($settings);
            Http::json([
                'success' => true,
                'message' => $settings['allowTrialExtensionAccess']
                    ? 'Free Trial Extension Access ENABLED! Free trial users can now download and use the extension.'
                    : 'Free Trial Extension Access DISABLED! Free trial users will be prompted to upgrade.',
                'allowTrialExtensionAccess' => $settings['allowTrialExtensionAccess'],
            ]);
        }
        if ($method === 'POST' && $path === '/admin/maintenance') {
            Helpers::requireAdmin();
            $body = Http::body();
            $current = Db::readSettings();
            $m = $current['maintenance'] ?? [];
            $updated = [
                'enabled' => array_key_exists('enabled', $body) ? (bool)$body['enabled'] : !empty($m['enabled']),
                'title' => array_key_exists('title', $body) ? (string)$body['title'] : ($m['title'] ?? 'Scheduled System Maintenance'),
                'message' => array_key_exists('message', $body) ? (string)$body['message'] : ($m['message'] ?? 'Pak Flow is currently undergoing scheduled platform upgrades to bring you enhanced performance and faster AI rendering. We will be back online shortly.'),
                'customUrl' => array_key_exists('customUrl', $body) ? (string)$body['customUrl'] : ($m['customUrl'] ?? ''),
                'customUrlTitle' => array_key_exists('customUrlTitle', $body) ? (string)$body['customUrlTitle'] : ($m['customUrlTitle'] ?? 'Join WhatsApp Channel for Updates'),
                'redirectToCustomUrl' => array_key_exists('redirectToCustomUrl', $body) ? (bool)$body['redirectToCustomUrl'] : !empty($m['redirectToCustomUrl']),
                'updatedAt' => Db::isoNow(),
            ];
            $current['maintenance'] = $updated;
            Db::writeSettings($current);
            Http::json([
                'success' => true,
                'message' => $updated['enabled'] ? 'Maintenance Mode is now ACTIVE on the website' : 'Maintenance Mode has been DISABLED',
                'maintenance' => $updated,
            ]);
        }
        if ($method === 'POST' && $path === '/admin/broadcast') {
            Helpers::requireAdmin();
            self::adminBroadcast();
        }
        if ($method === 'POST' && $path === '/admin/send-email') {
            Helpers::requireAdmin();
            self::adminSendEmail();
            return true;
        }
        if (($method === 'GET' || $method === 'POST') && $path === '/admin/run-expiry-emails') {
            Helpers::requireAdmin();
            $res = ExpiryNotifier::run(true, 500);
            Http::json(['success' => true, 'result' => $res]);
        }

        // App & Extension Uploads / Downloads
        if ($method === 'POST' && $path === '/admin/upload-app') {
            Helpers::requireAdmin();
            self::uploadApp();
            return true;
        }
        if ($method === 'GET' && in_array($path, ['/app/download', '/download/app', '/download-app'], true)) {
            self::downloadApp();
            return true;
        }
        if ($method === 'GET' && $path === '/admin/extensions') {
            Helpers::requireAdmin();
            $settings = Db::readSettings();
            Http::json(['success' => true, 'extensions' => $settings['extensions'] ?? Db::defaultSettings()['extensions']]);
        }
        if ($method === 'POST' && $path === '/admin/upload-extension') {
            Helpers::requireAdmin();
            self::uploadExtension();
            return true;
        }
        if ($method === 'GET' && in_array($path, ['/extensions/chat-tools/download', '/extensions/chat-tools'], true)) {
            Http::json(['success' => false, 'disabled' => true, 'error' => 'ToolDisabled', 'message' => 'AI Chat Tools extension has been removed. Only Google Flow is active.'], 410);
        }
        if ($method === 'GET' && in_array($path, ['/extensions/flow/download', '/extensions/flow'], true)) {
            self::downloadExtension('flow');
            return true;
        }
        if ($method === 'GET' && preg_match('#^/(?:extensions/([^/]+)/download|extensions/download/([^/]+)|download/extension/([^/]+))$#', $path, $m)) {
            $rawType = strtolower($m[1] ?: ($m[2] ?: $m[3]));
            if (!in_array($rawType, ['flow', 'googleflow', 'video'], true)) {
                Http::json(['success' => false, 'disabled' => true, 'error' => 'ToolDisabled', 'message' => 'Only Google Flow extension is available.'], 410);
            }
            self::downloadExtension('flow');
            return true;
        }
        if ($method === 'GET' && in_array($path, ['/flow-auto-login/download', '/flow-extension/download'], true)) {
            self::downloadExtension('flow');
            return true;
        }

        // Surfshark
        if ($method === 'GET' && in_array($path, ['/surfshark/stream', '/admin/surfshark-codes/stream'], true)) {
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-store, no-cache, must-revalidate, no-transform');
            header('X-Accel-Buffering: no');
            $settings = Db::readSettings();
            $codes = is_array($settings['surfsharkCodes'] ?? null) ? $settings['surfsharkCodes'] : [];
            $pendingCount = count(array_filter($codes, fn($c) => ($c['status'] ?? 'pending') === 'pending'));
            echo "retry: 30000\ndata: " . json_encode([
                'type' => 'init',
                'codes' => $codes,
                'pendingCount' => $pendingCount,
                'timestamp' => (int)round(microtime(true) * 1000),
            ], JSON_UNESCAPED_SLASHES) . "\n\n";
            exit;
        }
        if ($method === 'POST' && in_array($path, ['/surfshark/login-code', '/surfshark/code'], true)) {
            self::surfsharkLoginCode();
            return true;
        }
        if ($method === 'GET' && preg_match('#^/surfshark/code-status/([^/]+)$#', $path, $m)) {
            header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            $settings = Db::readSettings();
            $list = is_array($settings['surfsharkCodes'] ?? null) ? $settings['surfsharkCodes'] : [];
            $id = $m[1];
            foreach ($list as $item) {
                if (($item['id'] ?? '') === $id || ($item['code'] ?? '') === $id) {
                    Http::json([
                        'success' => true,
                        'id' => $item['id'],
                        'code' => $item['code'],
                        'status' => $item['status'] ?? 'pending',
                        'completedAt' => $item['completedAt'] ?? null,
                    ]);
                }
            }
            Http::json(['success' => false, 'error' => 'NotFound'], 404);
        }
        if ($method === 'GET' && in_array($path, ['/admin/surfshark-codes', '/surfshark/codes'], true)) {
            if ($path === '/admin/surfshark-codes') {
                Helpers::requireAdmin();
            }
            header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            $settings = Db::readSettings();
            $codes = is_array($settings['surfsharkCodes'] ?? null) ? $settings['surfsharkCodes'] : [];
            $pendingCount = count(array_filter($codes, fn($c) => ($c['status'] ?? 'pending') === 'pending'));
            Http::json(['success' => true, 'codes' => $codes, 'pendingCount' => $pendingCount]);
        }
        if ($method === 'PUT' && preg_match('#^/admin/surfshark-codes/([^/]+)/status$#', $path, $m)) {
            Helpers::requireAdmin();
            header('Cache-Control: no-store, no-cache, must-revalidate');
            $body = Http::body();
            $status = (string)($body['status'] ?? 'done');
            $settings = Db::readSettings();
            $list = is_array($settings['surfsharkCodes'] ?? null) ? $settings['surfsharkCodes'] : [];
            $id = $m[1];
            foreach ($list as $i => $item) {
                if (($item['id'] ?? '') === $id || ($item['code'] ?? '') === $id) {
                    $list[$i]['status'] = $status ?: 'done';
                    $list[$i]['completedAt'] = $list[$i]['status'] === 'done' ? Db::isoNow() : null;
                    $settings['surfsharkCodes'] = $list;
                    Db::writeSettings($settings);
                    $upper = strtoupper((string)$list[$i]['status']);
                    Http::json([
                        'success' => true,
                        'message' => "Code {$list[$i]['code']} marked as $upper!",
                        'entry' => $list[$i],
                        'codes' => $list,
                    ]);
                }
            }
            Http::json(['success' => false, 'message' => 'Surfshark code entry not found'], 404);
        }
        if ($method === 'DELETE' && preg_match('#^/admin/surfshark-codes/([^/]+)$#', $path, $m)) {
            Helpers::requireAdmin();
            header('Cache-Control: no-store, no-cache, must-revalidate');
            $id = $m[1];
            $settings = Db::readSettings();
            $list = is_array($settings['surfsharkCodes'] ?? null) ? $settings['surfsharkCodes'] : [];
            $settings['surfsharkCodes'] = array_values(array_filter($list, fn($x) => ($x['id'] ?? '') !== $id && ($x['code'] ?? '') !== $id));
            Db::writeSettings($settings);
            Http::json(['success' => true, 'message' => 'Code removed', 'codes' => $settings['surfsharkCodes']]);
        }
        if ($method === 'DELETE' && $path === '/admin/surfshark-codes') {
            Helpers::requireAdmin();
            header('Cache-Control: no-store, no-cache, must-revalidate');
            $mode = (string)Http::query('mode', 'completed');
            $settings = Db::readSettings();
            $list = is_array($settings['surfsharkCodes'] ?? null) ? $settings['surfsharkCodes'] : [];
            $settings['surfsharkCodes'] = $mode === 'all'
                ? []
                : array_values(array_filter($list, fn($x) => ($x['status'] ?? 'pending') === 'pending'));
            Db::writeSettings($settings);
            Http::json([
                'success' => true,
                'message' => $mode === 'all' ? 'All Surfshark codes cleared!' : 'Completed codes cleared!',
                'codes' => $settings['surfsharkCodes'],
            ]);
        }

        return false;
    }

    private static function adminStats(): void
    {
        $now = time();
        $activeCount = 0;
        $expiredCount = 0;
        $suspendedCount = 0;
        $planBreakdown = ['vibe' => 0, 'plus' => 0, 'pro' => 0, 'max' => 0, 'ultra' => 0, 'starter' => 0, 'free' => 0];

        if (\App\Database::isEnabled()) {
            $rows = \App\Database::pdo()->query('SELECT plan, status, plan_expires_at FROM users')->fetchAll();
            $totalUsers = count($rows);
            foreach ($rows as $r) {
                $p = strtolower((string)($r['plan'] ?? 'free'));
                $planBreakdown[$p] = ($planBreakdown[$p] ?? 0) + 1;
                if (($r['status'] ?? '') === 'suspended') {
                    $suspendedCount++;
                } else {
                    $expRaw = $r['plan_expires_at'] ?? null;
                    $exp = $expRaw ? (strtotime((string)$expRaw) ?: 0) : 0;
                    if ($exp > $now) {
                        $activeCount++;
                    } else {
                        $expiredCount++;
                    }
                }
            }
            Http::json([
                'totalUsers' => $totalUsers,
                'activeSubscribers' => $activeCount,
                'expiredUsers' => $expiredCount,
                'suspendedUsers' => $suspendedCount,
                'planBreakdown' => $planBreakdown,
            ]);
        }

        $users = Db::readUsers();
        foreach ($users as $u) {
            $p = strtolower((string)($u['plan'] ?? 'free'));
            $planBreakdown[$p] = ($planBreakdown[$p] ?? 0) + 1;
            if (($u['status'] ?? '') === 'suspended') {
                $suspendedCount++;
            } else {
                $exp = !empty($u['planExpiresAt']) ? (strtotime((string)$u['planExpiresAt']) ?: 0) : 0;
                if ($exp > $now) {
                    $activeCount++;
                } else {
                    $expiredCount++;
                }
            }
        }

        Http::json([
            'totalUsers' => count($users),
            'activeSubscribers' => $activeCount,
            'expiredUsers' => $expiredCount,
            'suspendedUsers' => $suspendedCount,
            'planBreakdown' => $planBreakdown,
        ]);
    }

    private static function adminUsersList(): void
    {
        $q = Http::query('q');
        $plan = Http::query('plan');
        $status = Http::query('status');
        $now = time();

        if (\App\Database::isEnabled()) {
            $pdo = \App\Database::pdo();
            $where = [];
            $params = [];
            if ($q !== null && $q !== '') {
                $like = '%' . strtolower(trim((string)$q)) . '%';
                $where[] = '(LOWER(name) LIKE ? OR LOWER(email) LIKE ? OR LOWER(username) LIKE ?)';
                $params[] = $like;
                $params[] = $like;
                $params[] = $like;
            }
            if ($plan !== null && $plan !== '' && $plan !== 'all') {
                $where[] = 'LOWER(plan) = ?';
                $params[] = strtolower((string)$plan);
            }
            if ($status !== null && $status !== '' && $status !== 'all') {
                $where[] = 'LOWER(status) = ?';
                $params[] = strtolower((string)$status);
            }
            $sql = 'SELECT id, name, username, email, plain_password_hint, plan, credits, credits_total, plan_expires_at, status, is_locked, reseller_id, created_at, extra_json FROM users';
            if (!empty($where)) {
                $sql .= ' WHERE ' . implode(' AND ', $where);
            }
            $sql .= ' ORDER BY created_at DESC, id DESC';

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $users = [];
            while ($r = $stmt->fetch()) {
                $extraRaw = (string)($r['extra_json'] ?? '');
                $deviceSwitchCount = 0;
                $resellerName = null;
                $requestedDays = null;
                if ($extraRaw !== '') {
                    $extra = json_decode($extraRaw, true);
                    if (is_array($extra)) {
                        $deviceSwitchCount = (int)($extra['deviceSwitchCount'] ?? 0);
                        $resellerName = $extra['resellerName'] ?? null;
                        $requestedDays = $extra['requestedDays'] ?? null;
                    }
                }
                $expRaw = $r['plan_expires_at'] ?? null;
                $daysRem = 0;
                if ($expRaw) {
                    $expTs = strtotime((string)$expRaw);
                    if ($expTs !== false && $expTs > $now) {
                        $daysRem = max(0, (int)ceil(($expTs - $now) / 86400));
                    }
                }
                $rowOut = [
                    'id' => (int)$r['id'],
                    'name' => (string)($r['name'] ?? ''),
                    'username' => (string)($r['username'] ?? ''),
                    'email' => (string)($r['email'] ?? ''),
                    'plainPasswordHint' => $r['plain_password_hint'],
                    'plan' => (string)($r['plan'] ?? 'basic'),
                    'credits' => (int)($r['credits'] ?? 0),
                    'creditsTotal' => (int)($r['credits_total'] ?? 0),
                    'planExpiresAt' => $expRaw,
                    'daysRemaining' => $daysRem,
                    'status' => (string)($r['status'] ?? 'active'),
                    'isLocked' => !empty($r['is_locked']),
                    'deviceSwitchCount' => $deviceSwitchCount,
                    'createdAt' => $r['created_at'],
                ];
                if ($r['reseller_id'] !== null && $r['reseller_id'] !== '') {
                    $rowOut['resellerId'] = (int)$r['reseller_id'];
                }
                if ($resellerName !== null) {
                    $rowOut['resellerName'] = $resellerName;
                }
                if ($requestedDays !== null) {
                    $rowOut['requestedDays'] = (int)$requestedDays;
                }
                $users[] = $rowOut;
            }
            Http::json(['users' => $users, 'total' => count($users)]);
        }

        $rawUsers = Db::readUsers();
        $withTs = [];
        foreach ($rawUsers as $u) {
            $expRaw = $u['planExpiresAt'] ?? null;
            $daysRem = 0;
            if ($expRaw) {
                $expTs = strtotime((string)$expRaw);
                if ($expTs !== false && $expTs > $now) {
                    $daysRem = max(0, (int)ceil(($expTs - $now) / 86400));
                }
            }
            $ts = !empty($u['createdAt']) ? (strtotime((string)$u['createdAt']) ?: 0) : 0;
            $rowOut = [
                'id' => $u['id'],
                'name' => $u['name'] ?? '',
                'username' => $u['username'] ?? '',
                'email' => $u['email'] ?? '',
                'plainPasswordHint' => $u['plainPasswordHint'] ?? null,
                'plan' => $u['plan'] ?? 'basic',
                'credits' => (int)($u['credits'] ?? 0),
                'creditsTotal' => (int)($u['creditsTotal'] ?? 0),
                'planExpiresAt' => $expRaw,
                'daysRemaining' => $daysRem,
                'status' => $u['status'] ?? 'active',
                'isLocked' => !empty($u['isLocked']),
                'deviceSwitchCount' => (int)($u['deviceSwitchCount'] ?? 0),
                'createdAt' => $u['createdAt'] ?? null,
            ];
            if (isset($u['resellerId'])) $rowOut['resellerId'] = $u['resellerId'];
            if (isset($u['resellerName'])) $rowOut['resellerName'] = $u['resellerName'];
            if (isset($u['requestedDays'])) $rowOut['requestedDays'] = $u['requestedDays'];
            $withTs[] = ['copy' => $rowOut, 'ts' => $ts];
        }
        usort($withTs, fn($a, $b) => $b['ts'] <=> $a['ts']);
        $users = array_map(fn($x) => $x['copy'], $withTs);

        if ($q !== null && $q !== '') {
            $query = strtolower((string)$q);
            $users = array_values(array_filter($users, function ($u) use ($query) {
                return str_contains(strtolower((string)($u['name'] ?? '')), $query)
                    || str_contains(strtolower((string)($u['email'] ?? '')), $query)
                    || str_contains(strtolower((string)($u['username'] ?? '')), $query);
            }));
        }
        if ($plan !== null && $plan !== '' && $plan !== 'all') {
            $planLower = strtolower((string)$plan);
            $users = array_values(array_filter($users, fn($u) => strtolower((string)($u['plan'] ?? '')) === $planLower));
        }
        if ($status !== null && $status !== '' && $status !== 'all') {
            $statusLower = strtolower((string)$status);
            $users = array_values(array_filter($users, fn($u) => strtolower((string)($u['status'] ?? '')) === $statusLower));
        }

        Http::json(['users' => $users, 'total' => count($users)]);
    }

    private static function adminCreateUser(): void
    {
        $body = Http::body();
        $name = $body['name'] ?? '';
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $plan = $body['plan'] ?? 'plus';
        $days = $body['days'] ?? 30;
        $credits = $body['credits'] ?? null;
        $status = $body['status'] ?? 'active';

        if (!$email || !$password) {
            Http::json(['error' => 'ValidationError', 'message' => 'Email and password are required'], 400);
        }

        $cleanEmail = Db::sanitizeEmail($email);
        $cleanPass = Helpers::normalizePasswordForEmail($password, $cleanEmail);
        if (!$cleanEmail || !$cleanPass) {
            Http::json(['error' => 'ValidationError', 'message' => 'Valid email and password are required'], 400);
        }

        Db::unmarkUserDeleted($cleanEmail);
        $users = Db::readUsers();

        $userDays = (int)$days ?: 30;
        $nowIso = Db::isoNow();
        $planExpiresAt = Db::isoNow($userDays * 86400);
        $selectedPlan = strtolower((string)($plan ?: 'plus'));
        $planCreditsDefault = [
            'vibe' => 45000, 'plus' => 45000, 'pro' => 45000, 'max' => 45000,
            'ultra' => 45000, 'starter' => 45000, 'free' => 45000, 'trial' => 45000,
        ];
        $userCredits = ($credits !== null && $credits !== '') ? (int)$credits : ($planCreditsDefault[$selectedPlan] ?? 25000);
        $localPart = explode('@', $cleanEmail)[0] ?? 'user';

        foreach ($users as $idx => $existing) {
            if (Db::sanitizeEmail($existing['email'] ?? '') === $cleanEmail) {
                $existing['name'] = $name ? Db::sanitizeCredential($name) : ($existing['name'] ?: $localPart);
                $existing['username'] = $existing['username'] ?: preg_replace('/[^a-zA-Z0-9_]/', '', $localPart);
                $existing['email'] = $cleanEmail;
                $existing['passwordHash'] = Db::hashPassword($cleanPass);
                $existing['plainPasswordHint'] = $cleanPass;
                $existing['plan'] = $selectedPlan;
                $existing['credits'] = $userCredits;
                $existing['creditsTotal'] = $userCredits;
                $existing['planExpiresAt'] = $planExpiresAt;
                $existing['daysRemaining'] = $userDays;
                $existing['status'] = ($status === 'suspended') ? 'suspended' : 'active';
                $existing['isLocked'] = false;
                $existing['lockReason'] = null;
                $existing['lockedAt'] = null;
                $existing['onboardingAccepted'] = true;
                $existing['emailVerified'] = true;
                $existing['updatedAt'] = $nowIso;

                Db::unmarkUserDeleted($cleanEmail, $existing['id']);
                $users[$idx] = $existing;
                Db::writeUsers($users, ['immediate' => true]);
                $safe = $existing;
                unset($safe['passwordHash']);
                Http::json(['success' => true, 'message' => 'User updated and activated successfully', 'user' => $safe], 200);
            }
        }

        $maxId = 0;
        foreach ($users as $u) {
            $uid = (int)($u['id'] ?? 0);
            if ($uid > $maxId) {
                $maxId = $uid;
            }
        }
        $newUser = [
            'id' => $maxId + 1,
            'name' => $name ? Db::sanitizeCredential($name) : $localPart,
            'username' => preg_replace('/[^a-zA-Z0-9_]/', '', $localPart),
            'email' => $cleanEmail,
            'passwordHash' => Db::hashPassword($cleanPass),
            'plainPasswordHint' => $cleanPass,
            'plan' => $selectedPlan,
            'credits' => $userCredits,
            'creditsTotal' => $userCredits,
            'planExpiresAt' => $planExpiresAt,
            'daysRemaining' => $userDays,
            'status' => ($status === 'suspended') ? 'suspended' : 'active',
            'isLocked' => false,
            'onboardingAccepted' => true,
            'emailVerified' => true,
            'createdAt' => $nowIso,
            'updatedAt' => $nowIso,
        ];

        Db::unmarkUserDeleted($cleanEmail, $newUser['id']);
        $users[] = $newUser;
        Db::writeUsers($users, ['immediate' => true]);
        $safe = $newUser;
        unset($safe['passwordHash']);
        Http::json(['success' => true, 'message' => 'User created successfully', 'user' => $safe], 201);
    }

    private static function adminUpdateUser(int $userId): void
    {
        $body = Http::body();
        $useMysqlFast = \App\Database::isEnabled();
        $users = null;
        $index = -1;
        if ($useMysqlFast) {
            $user = Db::findUserByIdMysql($userId);
            if (!$user) {
                Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
            }
        } else {
            $users = Db::readUsers();
            foreach ($users as $i => $u) {
                if ((int)($u['id'] ?? 0) === $userId) {
                    $index = $i;
                    break;
                }
            }
            if ($index === -1) {
                Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
            }
            $user = $users[$index];
        }
        if (array_key_exists('name', $body)) $user['name'] = Db::sanitizeCredential($body['name']);
        if (array_key_exists('email', $body)) $user['email'] = Db::sanitizeEmail($body['email']);
        if (array_key_exists('plan', $body)) $user['plan'] = strtolower((string)$body['plan']);
        if (array_key_exists('status', $body)) {
            $user['status'] = $body['status'];
            if ($body['status'] === 'active') {
                $user['isLocked'] = false;
                $user['lockReason'] = null;
            }
        }
        if (array_key_exists('credits', $body)) $user['credits'] = (int)$body['credits'];
        if (array_key_exists('isLocked', $body)) {
            $user['isLocked'] = (bool)$body['isLocked'];
            if (!$user['isLocked'] && ($user['status'] ?? '') === 'locked') {
                $user['status'] = 'active';
                $user['lockReason'] = null;
            }
        }
        if (array_key_exists('deviceSwitchCount', $body)) {
            $user['deviceSwitchCount'] = max(0, (int)$body['deviceSwitchCount']);
        }
        if (!empty($body['resetDeviceLock'])) {
            $user['deviceSwitchCount'] = 0;
            $user['isLocked'] = false;
            if (($user['status'] ?? '') === 'locked') $user['status'] = 'active';
            $user['lockReason'] = null;
            $user['lockedAt'] = null;
            $user['activeDeviceId'] = null;
            $user['sessionVersion'] = (int)($user['sessionVersion'] ?? 1) + 1;
        }
        if (!empty($body['password'])) {
            $cleanPass = Helpers::normalizePasswordForEmail($body['password'], (string)($user['email'] ?? ''));
            if ($cleanPass !== '') {
                $user['passwordHash'] = Db::hashPassword($cleanPass);
                $user['plainPasswordHint'] = $cleanPass;
            }
        }
        if (array_key_exists('days', $body)) {
            $numDays = max(0, (int)$body['days']);
            $user['planExpiresAt'] = Db::isoNow($numDays * 86400);
            $user['daysRemaining'] = $numDays;
        } elseif (array_key_exists('addDays', $body)) {
            $additional = (int)$body['addDays'];
            $currentExp = !empty($user['planExpiresAt']) ? (strtotime((string)$user['planExpiresAt']) ?: time()) : time();
            $baseTime = $currentExp > time() ? $currentExp : time();
            $newExp = gmdate('Y-m-d\TH:i:s', $baseTime + $additional * 86400) . '.000Z';
            $user['planExpiresAt'] = $newExp;
            $user['daysRemaining'] = Db::calculateDaysRemaining($newExp);
        }

        $user['updatedAt'] = Db::isoNow();
        Db::unmarkUserDeleted((string)($user['email'] ?? ''), $user['id']);
        if ($useMysqlFast) {
            Db::upsertSingleUser($user);
        } else {
            $users[$index] = $user;
            Db::writeUsers($users, ['immediate' => true]);
        }

        $safe = $user;
        unset($safe['passwordHash']);
        $safe['daysRemaining'] = Db::calculateDaysRemaining($user['planExpiresAt'] ?? null);
        Http::json(['success' => true, 'message' => 'User updated successfully', 'user' => $safe]);
    }

    private static function adminResetDeviceLock(int $userId): void
    {
        if (\App\Database::isEnabled()) {
            $user = Db::findUserByIdMysql($userId);
            if (!$user) {
                Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
            }
            $user['deviceSwitchCount'] = 0;
            $user['isLocked'] = false;
            if (($user['status'] ?? '') === 'locked') $user['status'] = 'active';
            $user['lockReason'] = null;
            $user['lockedAt'] = null;
            $user['activeDeviceId'] = null;
            $user['sessionVersion'] = (int)($user['sessionVersion'] ?? 1) + 1;
            $user['updatedAt'] = Db::isoNow();
            Db::upsertSingleUser($user);
            $safe = $user;
            unset($safe['passwordHash']);
            $safe['daysRemaining'] = Db::calculateDaysRemaining($user['planExpiresAt'] ?? null);
            Http::json([
                'success' => true,
                'message' => "Device switch counter reset to 0 and account unlocked for {$user['email']}!",
                'user' => $safe,
            ]);
        }
        $users = Db::readUsers();
        foreach ($users as $i => $user) {
            if ((int)($user['id'] ?? 0) === $userId) {
                $user['deviceSwitchCount'] = 0;
                $user['isLocked'] = false;
                if (($user['status'] ?? '') === 'locked') $user['status'] = 'active';
                $user['lockReason'] = null;
                $user['lockedAt'] = null;
                $user['activeDeviceId'] = null;
                $user['sessionVersion'] = (int)($user['sessionVersion'] ?? 1) + 1;
                $user['updatedAt'] = Db::isoNow();
                $users[$i] = $user;
                Db::writeUsers($users, ['immediate' => true]);
                $safe = $user;
                unset($safe['passwordHash']);
                $safe['daysRemaining'] = Db::calculateDaysRemaining($user['planExpiresAt'] ?? null);
                Http::json([
                    'success' => true,
                    'message' => "Device switch counter reset to 0 and account unlocked for {$user['email']}!",
                    'user' => $safe,
                ]);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
    }

    private static function adminDeleteUser(int $userId): void
    {
        if (\App\Database::isEnabled()) {
            $target = Db::findUserByIdMysql($userId);
            if (!$target) {
                Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
            }
            Db::markUserDeleted($userId, (string)($target['email'] ?? ''));
            Db::deleteSingleUserMysql($userId);
            Http::json(['success' => true, 'message' => 'User deleted successfully']);
        }
        $users = Db::readUsers();
        $target = null;
        $filtered = [];
        foreach ($users as $u) {
            if ((int)($u['id'] ?? 0) === $userId) {
                $target = $u;
            } else {
                $filtered[] = $u;
            }
        }
        if (!$target) {
            Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
        }
        Db::markUserDeleted($userId, (string)($target['email'] ?? ''));
        Db::writeUsers($filtered, ['immediate' => true]);
        Http::json(['success' => true, 'message' => 'User deleted successfully']);
    }

    private static function applyResetLimit(array &$users, int $index, $credits, $days): array
    {
        $user = $users[$index];
        $planCreditsDefault = [
            'vibe' => 45000, 'plus' => 45000, 'pro' => 45000, 'max' => 45000,
            'ultra' => 45000, 'starter' => 45000, 'free' => 45000, 'trial' => 45000,
        ];
        $planKey = strtolower((string)($user['plan'] ?? 'plus'));
        $defaultCredits = $planCreditsDefault[$planKey] ?? 25000;
        $targetCredits = $credits !== null ? (int)$credits : $defaultCredits;
        $targetDays = $days !== null ? (int)$days : 30;

        $user['credits'] = $targetCredits;
        $user['creditsTotal'] = $targetCredits;

        $now = time();
        $expTime = !empty($user['planExpiresAt']) ? (strtotime((string)$user['planExpiresAt']) ?: 0) : 0;
        if (!$expTime || $expTime <= $now) {
            $user['planExpiresAt'] = Db::isoNow($targetDays * 86400);
            $user['daysRemaining'] = $targetDays;
        } else {
            $user['daysRemaining'] = Db::calculateDaysRemaining($user['planExpiresAt']);
        }

        $user['creditsUsed'] = 0;
        $user['used'] = 0;
        $user['quotaExhausted'] = false;
        $user['generationsCount'] = 0;
        $user['isLocked'] = false;
        $user['lockedAt'] = null;
        $user['lockReason'] = null;
        $user['activeDeviceId'] = null;
        $user['secondDeviceId'] = null;
        $user['tokens'] = [];
        $user['activeToken'] = null;
        $user['deviceSwitchCount'] = 0;
        $user['sessionVersion'] = (int)($user['sessionVersion'] ?? 1) + 1;
        $user['status'] = 'active';
        $user['updatedAt'] = Db::isoNow();

        $users[$index] = $user;
        Db::writeUsers($users, ['immediate' => true]);

        $safe = $user;
        unset($safe['passwordHash']);
        $safe['daysRemaining'] = Db::calculateDaysRemaining($user['planExpiresAt'] ?? null);
        return [$safe, $targetCredits];
    }

    private static function adminResetLimit(int $userId): void
    {
        $body = Http::body();
        $users = Db::readUsers();
        foreach ($users as $i => $u) {
            if ((int)($u['id'] ?? 0) === $userId) {
                [$safe, $targetCredits] = self::applyResetLimit($users, $i, $body['credits'] ?? null, $body['days'] ?? null);
                $fmt = number_format($targetCredits);
                Http::json([
                    'success' => true,
                    'message' => "Limit reset successfully for {$safe['email']}! Restored $fmt credits, reset device switches to 0, and unlocked account.",
                    'user' => $safe,
                ]);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
    }

    private static function adminBroadcast(): void
    {
        $body = Http::body();
        $current = Db::readSettings();
        $current['broadcast'] = [
            'title' => $body['title'] ?? ($current['broadcast']['title'] ?? 'Announcement'),
            'message' => $body['message'] ?? ($current['broadcast']['message'] ?? ''),
            'enabled' => array_key_exists('enabled', $body) ? (bool)$body['enabled'] : true,
            'type' => $body['type'] ?? 'info',
            'updatedAt' => Db::isoNow(),
        ];
        Db::writeSettings($current);
        Http::json(['success' => true, 'message' => 'Broadcast updated', 'broadcast' => $current['broadcast']]);
    }

    private static function adminSendEmail(): void
    {
        $body = Http::body();
        $to = trim((string)($body['to'] ?? ''));
        $target = strtolower(trim((string)($body['target'] ?? 'custom')));
        $subject = trim((string)($body['subject'] ?? ''));
        $message = (string)($body['message'] ?? '');
        $format = strtolower(trim((string)($body['format'] ?? 'html'))) === 'text' ? 'text' : 'html';

        if ($subject === '' || trim($message) === '') {
            Http::json(['success' => false, 'message' => 'Subject and message are required'], 400);
        }

        $recipients = [];
        if ($target === 'all' || $target === 'active') {
            $users = Db::readUsers();
            foreach ($users as $u) {
                $email = Db::sanitizeEmail($u['email'] ?? '');
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                if ($target === 'active') {
                    if (Helpers::isPlanBasicOrLocked($u['plan'] ?? 'basic') || Helpers::isUserPlanExpired($u)) {
                        continue;
                    }
                }
                $recipients[$email] = true;
            }
        } else {
            $parts = preg_split('/[\s,;]+/', $to) ?: [];
            foreach ($parts as $p) {
                $email = Db::sanitizeEmail($p);
                if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $recipients[$email] = true;
                }
            }
        }

        $emailList = array_keys($recipients);
        if (empty($emailList)) {
            Http::json(['success' => false, 'message' => 'No valid recipient email addresses found'], 400);
        }

        if ($format === 'text') {
            $safeText = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));
            $htmlBody = '<!DOCTYPE html><html><body style="margin:0;padding:24px;background:#f3f4f6;font-family:Inter,Arial,sans-serif;">'
                . '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e5e7eb;">'
                . '<div style="background:linear-gradient(135deg,#6d28d9,#7c3aed);padding:24px;text-align:center;color:#ffffff;">'
                . '<div style="font-size:22px;font-weight:800;">PakFlow</div>'
                . '<div style="font-size:12px;opacity:0.9;margin-top:4px;">AI Image &amp; Video Generation</div>'
                . '</div>'
                . '<div style="padding:26px 28px;color:#374151;font-size:14.5px;line-height:1.65;">' . $safeText . '</div>'
                . '<div style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:14px 24px;text-align:center;font-size:12px;color:#6b7280;">'
                . '🌐 <a href="https://flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">flowbypak.com</a> &nbsp;&nbsp; ✉️ <a href="mailto:support@flowbypak.com" style="color:#6d28d9;text-decoration:none;font-weight:600;">support@flowbypak.com</a>'
                . '</div></div></body></html>';
        } else {
            $htmlBody = $message;
        }

        $sentCount = 0;
        $failedCount = 0;
        foreach ($emailList as $recipient) {
            if (Mailer::send($recipient, $subject, $htmlBody)) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        if ($sentCount === 0) {
            Http::json(['success' => false, 'sentCount' => 0, 'failedCount' => $failedCount, 'message' => 'Failed to send emails. Check SMTP settings or connectivity.'], 502);
        }

        Http::json([
            'success' => true,
            'sentCount' => $sentCount,
            'failedCount' => $failedCount,
            'total' => count($emailList),
            'message' => "Sent $sentCount email(s)" . ($failedCount > 0 ? " ($failedCount failed)" : '') . '!',
        ]);
    }

    private static function adminResetLimitByEmail(): void
    {
        $body = Http::body();
        $email = $body['email'] ?? '';
        if (!$email) {
            Http::json(['error' => 'ValidationError', 'message' => 'Email is required'], 400);
        }
        $cleanEmail = Db::sanitizeEmail($email);
        $users = Db::readUsers();
        $index = Helpers::findUserIndexByLoginIdentifier($users, $cleanEmail);
        if ($index === -1) {
            Http::json(['error' => 'NotFound', 'message' => 'User not found'], 404);
        }
        [$safe, $targetCredits] = self::applyResetLimit($users, $index, $body['credits'] ?? null, $body['days'] ?? null);
        $fmt = number_format($targetCredits);
        Http::json([
            'success' => true,
            'message' => "Limit reset successfully for {$safe['email']}! Restored $fmt credits and unlocked account.",
            'user' => $safe,
        ]);
    }

    private static function publicSettings(): void
    {
        $settings = Db::readSettings();
        $defaults = Db::defaultSettings();
        $rev = Db::getDbRevision();
        $etag = "W/\"pubset-$rev\"";
        header('Cache-Control: no-cache');
        header("ETag: $etag");
        if (Http::header('if-none-match') === $etag) {
            http_response_code(304);
            exit;
        }
        $flowExt = $settings['extensions']['flow'] ?? ($defaults['extensions']['flow'] ?? []);
        Http::json([
            'success' => true,
            'brandName' => $settings['brandName'] ?? $defaults['brandName'],
            'supportEmail' => $settings['supportEmail'] ?? $defaults['supportEmail'],
            'whatsappChannel' => $settings['whatsappChannel'] ?? $defaults['whatsappChannel'],
            'whatsappPhone' => $settings['whatsappPhone'] ?? $defaults['whatsappPhone'],
            'allowPublicRegistration' => ($settings['allowPublicRegistration'] ?? true) !== false,
            'allowTrialExtensionAccess' => false,
            'broadcast' => !empty($settings['broadcast']['enabled']) ? $settings['broadcast'] : null,
            'windowsApp' => $settings['windowsApp'] ?? $defaults['windowsApp'],
            'plans' => $settings['plans'] ?? $defaults['plans'],
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
            'extensions' => ['flow' => $flowExt],
            'maintenance' => $settings['maintenance'] ?? $defaults['maintenance'],
        ]);
    }

    private static function uploadApp(): void
    {
        $body = Http::body();
        $fileName = $body['fileName'] ?? '';
        $fileData = $body['fileData'] ?? '';
        $downloadUrl = $body['downloadUrl'] ?? '';
        $version = $body['version'] ?? '';
        $instructions = $body['instructions'] ?? '';

        $current = Db::readSettings();
        $root = dirname(__DIR__, 2);

        if ($fileData) {
            $cleanBase64 = preg_replace('/^data:.*?,/', '', (string)$fileData) ?? '';
            $buffer = base64_decode($cleanBase64, true);
            if ($buffer === false) {
                Http::json(['error' => 'UploadFailed', 'message' => 'Invalid base64 data'], 500);
            }
            $targetName = ($fileName && str_ends_with(strtolower((string)$fileName), '.exe')) ? basename((string)$fileName) : 'PakFlow-Setup.exe';
            $publicDestDir = $root . '/public/downloads';
            $distDestDir = $root . '/dist/downloads';
            if (!is_dir($publicDestDir)) @mkdir($publicDestDir, 0775, true);
            if (!is_dir($distDestDir)) @mkdir($distDestDir, 0775, true);
            file_put_contents("$publicDestDir/$targetName", $buffer);
            if (is_dir($root . '/dist')) {
                file_put_contents("$distDestDir/$targetName", $buffer);
            }
            $sizeMB = number_format(strlen($buffer) / (1024 * 1024), 1) . ' MB';
            $current['windowsApp'] = array_merge($current['windowsApp'] ?? [], [
                'fileName' => $targetName,
                'fileSize' => $sizeMB,
                'version' => $version ?: ($current['windowsApp']['version'] ?? '1.2.0'),
                'downloadUrl' => "/downloads/$targetName",
                'updatedAt' => Db::isoNow(),
                'instructions' => $instructions ?: ($current['windowsApp']['instructions'] ?? ''),
            ]);
            Db::writeSettings($current);
            Http::json(['success' => true, 'message' => "App uploaded successfully ($sizeMB)", 'windowsApp' => $current['windowsApp']]);
        }

        if ($downloadUrl) {
            $current['windowsApp'] = array_merge($current['windowsApp'] ?? [], [
                'downloadUrl' => trim((string)$downloadUrl),
                'version' => $version ?: ($current['windowsApp']['version'] ?? '1.2.0'),
                'updatedAt' => Db::isoNow(),
                'instructions' => $instructions ?: ($current['windowsApp']['instructions'] ?? ''),
            ]);
            Db::writeSettings($current);
            Http::json(['success' => true, 'message' => 'Extension link updated successfully', 'windowsApp' => $current['windowsApp']]);
        }

        Http::json(['error' => 'MissingData', 'message' => 'Please provide a file or download URL'], 400);
    }

    private static function downloadApp(): void
    {
        $settings = Db::readSettings();
        $appInfo = $settings['windowsApp'] ?? [];
        $downloadUrl = (string)($appInfo['downloadUrl'] ?? '/downloads/PakFlow-Setup.exe');
        if (str_starts_with($downloadUrl, 'http://') || str_starts_with($downloadUrl, 'https://')) {
            header("Location: $downloadUrl", true, 302);
            exit;
        }
        $fileName = (string)($appInfo['fileName'] ?? 'PakFlow-Setup.exe');
        $filePath = dirname(__DIR__, 2) . '/public/downloads/' . $fileName;
        if (is_file($filePath)) {
            Http::sendFile($filePath, 'application/vnd.microsoft.portable-executable', $fileName);
        }
        header("Location: /downloads/$fileName", true, 302);
        exit;
    }

    private static function uploadExtension(): void
    {
        $body = Http::body();
        $type = $body['type'] ?? '';
        $fileName = $body['fileName'] ?? '';
        $fileData = $body['fileData'] ?? '';
        $downloadUrl = $body['downloadUrl'] ?? '';
        $version = $body['version'] ?? '';
        $instructions = $body['instructions'] ?? '';

        $current = Db::readSettings();
        $extType = ($type === 'flow' || $type === 'googleFlow') ? 'flow' : 'chatTools';
        if (empty($current['extensions'])) {
            $current['extensions'] = Db::defaultSettings()['extensions'];
        }
        $existing = $current['extensions'][$extType] ?? [];
        $root = dirname(__DIR__, 2);

        if ($fileData) {
            $cleanBase64 = preg_replace('/^data:.*?,/', '', (string)$fileData) ?? '';
            $buffer = base64_decode($cleanBase64, true);
            if ($buffer === false) {
                Http::json(['error' => 'UploadFailed', 'message' => 'Invalid base64 data'], 500);
            }
            $defaultName = $extType === 'flow' ? 'PakFlow_v1.7.zip' : 'Social_Pak_Extension.zip';
            $safeInputName = $fileName ? basename(trim((string)$fileName)) : '';
            $targetName = ($safeInputName && str_ends_with(strtolower($safeInputName), '.zip')) ? $safeInputName : $defaultName;

            $publicDestDir = $root . '/public/downloads';
            $distDestDir = $root . '/dist/downloads';
            if (!is_dir($publicDestDir)) @mkdir($publicDestDir, 0775, true);
            if (!is_dir($distDestDir)) @mkdir($distDestDir, 0775, true);

            $mirrors = $extType === 'flow'
                ? array_unique([$targetName, 'PakFlow_v1.7.zip', 'Pakflow1.7.zip', 'PakFlow_v1.6.zip', 'PakFlow_v1.5.zip', 'Pakflow1.5.zip', 'PakFlow_v1.4.zip', 'Pakflow1.4.zip', 'PakFlow_v1.3.zip', 'Pakflow1.3.zip', 'PakFlow_v1.2.zip', 'PakFlow_v1.1.zip'])
                : array_unique([$targetName, 'Social_Pak_Extension.zip', 'PakFlow_AI_Tools_Extension.zip', 'PakFlow_Extension_v1.0.2.zip']);

            foreach ($mirrors as $name) {
                file_put_contents("$publicDestDir/$name", $buffer);
                if (is_dir($root . '/dist')) {
                    file_put_contents("$distDestDir/$name", $buffer);
                }
            }

            $len = strlen($buffer);
            $sizeKB = $len > 1024 * 1024 ? number_format($len / (1024 * 1024), 1) . ' MB' : round($len / 1024) . ' KB';
            $current['extensions'][$extType] = array_merge($existing, [
                'fileName' => $targetName,
                'fileSize' => $sizeKB,
                'version' => $version ?: ($existing['version'] ?? ($extType === 'flow' ? '1.7' : '1.1')),
                'downloadUrl' => "/downloads/$targetName",
                'updatedAt' => Db::isoNow(),
                'instructions' => $instructions ?: ($existing['instructions'] ?? ''),
            ]);
            Db::writeSettings($current);
            $label = $extType === 'flow' ? 'Google Flow' : 'AI Chatbots';
            Http::json([
                'success' => true,
                'message' => "$label Extension uploaded successfully ($sizeKB)",
                'extension' => $current['extensions'][$extType],
                'extensions' => $current['extensions'],
            ]);
        }

        if ($downloadUrl) {
            $resolvedUrl = trim((string)$downloadUrl);
            $resolvedFileName = (string)($existing['fileName'] ?? ($extType === 'flow' ? 'PakFlow_v1.7.zip' : 'Social_Pak_Extension.zip'));
            if (str_starts_with($resolvedUrl, '/downloads/') && str_ends_with(strtolower($resolvedUrl), '.zip')) {
                $resolvedFileName = basename($resolvedUrl);
            }
            $current['extensions'][$extType] = array_merge($existing, [
                'fileName' => $resolvedFileName,
                'downloadUrl' => $resolvedUrl,
                'version' => $version ?: ($existing['version'] ?? ($extType === 'flow' ? '1.7' : '1.1')),
                'updatedAt' => Db::isoNow(),
                'instructions' => $instructions ?: ($existing['instructions'] ?? ''),
            ]);
            Db::writeSettings($current);
            $label = $extType === 'flow' ? 'Google Flow' : 'AI Chatbots';
            Http::json([
                'success' => true,
                'message' => "$label extension link updated successfully",
                'extension' => $current['extensions'][$extType],
                'extensions' => $current['extensions'],
            ]);
        }

        Http::json(['error' => 'MissingData', 'message' => 'Please provide a zip file or download URL'], 400);
    }

    private static function downloadExtension(string $extKey): void
    {
        $settings = Db::readSettings();
        $extInfo = $settings['extensions'][$extKey] ?? [];
        $downloadUrl = trim((string)($extInfo['downloadUrl'] ?? ''));
        if (str_starts_with($downloadUrl, 'http://') || str_starts_with($downloadUrl, 'https://')) {
            header("Location: $downloadUrl", true, 302);
            exit;
        }
        $defaultName = $extKey === 'flow' ? 'PakFlow_v1.7.zip' : 'Social_Pak_Extension.zip';
        $configuredName = (string)($extInfo['fileName'] ?? ($downloadUrl ? basename($downloadUrl) : $defaultName));
        $candidates = $extKey === 'flow'
            ? array_unique([$configuredName, 'PakFlow_v1.7.zip', 'Pakflow1.7.zip', 'PakFlow_v1.6.zip', 'PakFlow_v1.5.zip', 'Pakflow1.5.zip', 'PakFlow_v1.4.zip', 'Pakflow1.4.zip', 'PakFlow_v1.3.zip', 'Pakflow1.3.zip', 'PakFlow_v1.2.zip', 'PakFlow_v1.1.zip', 'pakflowv1.1.zip', 'FlowByPak_Extension_Final.zip'])
            : array_unique([$configuredName, 'Social_Pak_Extension.zip', 'PakFlow_AI_Tools_Extension.zip', 'PakFlow_Extension_v1.0.2.zip']);

        $root = dirname(__DIR__, 2);
        foreach ($candidates as $name) {
            foreach (["$root/public/downloads/$name", "$root/dist/downloads/$name"] as $f) {
                if (is_file($f)) {
                    header('Pragma: no-cache');
                    header('Expires: 0');
                    Http::sendFile($f, 'application/zip', $configuredName, 'no-store, no-cache, must-revalidate');
                }
            }
        }
        Http::json(['success' => false, 'error' => ($extKey === 'flow' ? 'Google Flow' : 'AI Chatbots') . ' Extension package not found.'], 404);
    }

    private static function surfsharkLoginCode(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $body = Http::body();
        $rawCode = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim((string)($body['code'] ?? '')))) ?? '';
        if (strlen($rawCode) !== 6) {
            Http::json(['success' => false, 'error' => 'InvalidCode', 'message' => 'Please enter a valid 6-character Surfshark login code.'], 400);
        }

        if (Helpers::isRequestDeviceRevoked()) {
            Http::json([
                'success' => false,
                'error' => 'device_session_revoked',
                'loggedOutOtherDevice' => true,
                'message' => 'Your account was logged in on another device. Only 1 active device is allowed at a time.',
            ], 401);
        }

        $matchedUser = null;
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Db::verifyToken($token);
            if ($payload && (!empty($payload['userId']) || !empty($payload['email']))) {
                $matchedUser = Helpers::findUserByTokenPayload(Db::readUsers(), $payload);
            }
        }

        $userPlanLower = strtolower((string)($matchedUser['plan'] ?? 'basic'));
        if (
            !$matchedUser ||
            !empty($matchedUser['flowLocked']) ||
            !empty($matchedUser['extensionLocked']) ||
            Helpers::isRequestFlowLocked() ||
            in_array($userPlanLower, ['basic', 'free', 'trial', 'vibe', 'starter'], true)
        ) {
            Http::json([
                'success' => false,
                'error' => 'PlanLocked',
                'planLocked' => true,
                'message' => 'Surfshark VPN login is locked on the Basic Plan. Please upgrade your plan to unlock Surfshark VPN.',
            ], 403);
        }

        $entry = [
            'id' => 'ss_' . (int)round(microtime(true) * 1000) . '_' . bin2hex(random_bytes(3)),
            'code' => $rawCode,
            'userId' => $matchedUser['id'] ?? null,
            'username' => $matchedUser['username'] ?? ($matchedUser['name'] ?? ($body['username'] ?? 'Guest User')),
            'email' => $matchedUser['email'] ?? ($body['email'] ?? ''),
            'plan' => $matchedUser['plan'] ?? 'basic',
            'submittedAt' => Db::isoNow(),
            'completedAt' => null,
            'status' => 'pending',
        ];

        $settings = Db::readSettings();
        $list = is_array($settings['surfsharkCodes'] ?? null) ? $settings['surfsharkCodes'] : [];
        array_unshift($list, $entry);
        $settings['surfsharkCodes'] = array_slice($list, 0, 200);
        Db::writeSettings($settings);

        $surfsharkToken = Env::get('SURFSHARK_TOKEN', (string)($settings['surfsharkToken'] ?? ''));
        if ($surfsharkToken !== '' && function_exists('curl_init')) {
            $ch = curl_init('https://api.surfshark.com/v1/auth/code');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Authorization: Bearer $surfsharkToken"],
                CURLOPT_POSTFIELDS => json_encode(['code' => $rawCode]),
            ]);
            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code >= 200 && $code < 300) {
                $entry['status'] = 'done';
                $entry['completedAt'] = Db::isoNow();
                $settings['surfsharkCodes'][0] = $entry;
                Db::writeSettings($settings);
            }
        }

        Http::json([
            'success' => true,
            'id' => $entry['id'],
            'status' => $entry['status'],
            'code' => $rawCode,
            'entry' => $entry,
            'message' => $entry['status'] === 'done'
                ? 'Done! Your Surfshark extension will log in automatically within seconds â€” connect to United States!'
                : 'Code submitted! Waiting for Admin approval â€” once Done appears, your extension logs in automatically!',
        ]);
    }
}
