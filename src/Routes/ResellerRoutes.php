<?php
declare(strict_types=1);

namespace App\Routes;

use App\Db;
use App\Helpers;
use App\Http;

final class ResellerRoutes
{
    public static function dispatch(string $method, string $path): bool
    {
        if ($method === 'POST' && $path === '/reseller/login') {
            self::resellerLogin();
            return true;
        }
        if ($method === 'GET' && $path === '/reseller/me') {
            $r = Helpers::requireReseller();
            self::resellerMe($r);
            return true;
        }
        if ($method === 'POST' && $path === '/reseller/change-password') {
            $r = Helpers::requireReseller();
            self::changePassword($r);
            return true;
        }
        if ($method === 'POST' && $path === '/reseller/payments') {
            $r = Helpers::requireReseller();
            self::submitPayment($r);
            return true;
        }
        if ($method === 'GET' && $path === '/reseller/payments') {
            $r = Helpers::requireReseller();
            $payments = Db::readResellerPayments();
            $mine = array_values(array_filter($payments, fn($p) => ($p['resellerId'] ?? null) === $r['id']));
            Http::json(['success' => true, 'payments' => $mine]);
        }
        if ($method === 'GET' && $path === '/reseller/users') {
            $r = Helpers::requireReseller();
            self::resellerUsersList($r);
            return true;
        }
        if ($method === 'POST' && $path === '/reseller/users') {
            $r = Helpers::requireReseller();
            self::resellerCreateUser($r);
            return true;
        }
        if ($method === 'PUT' && preg_match('#^/reseller/users/(\d+)$#', $path, $m)) {
            $r = Helpers::requireReseller();
            self::resellerUpdateUser($r, (int)$m[1]);
            return true;
        }
        if ($method === 'DELETE' && preg_match('#^/reseller/users/(\d+)$#', $path, $m)) {
            $r = Helpers::requireReseller();
            self::resellerDeleteUser($r, (int)$m[1]);
            return true;
        }

        // Admin Reseller Management
        if ($method === 'GET' && $path === '/admin/resellers/analytics') {
            Helpers::requireAdmin();
            self::adminResellerAnalytics();
            return true;
        }
        if ($method === 'POST' && preg_match('#^/admin/resellers/(\d+)/clear-dues$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminClearDues((int)$m[1]);
            return true;
        }
        if ($method === 'POST' && preg_match('#^/admin/resellers/(\d+)/record-payment$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminRecordPayment((int)$m[1]);
            return true;
        }
        if ($method === 'GET' && $path === '/admin/resellers') {
            Helpers::requireAdmin();
            self::adminResellersList();
            return true;
        }
        if ($method === 'POST' && $path === '/admin/resellers') {
            Helpers::requireAdmin();
            self::adminCreateReseller();
            return true;
        }
        if ($method === 'PUT' && preg_match('#^/admin/resellers/(\d+)$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminUpdateReseller((int)$m[1]);
            return true;
        }
        if ($method === 'DELETE' && preg_match('#^/admin/resellers/(\d+)$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminDeleteReseller((int)$m[1]);
            return true;
        }
        if ($method === 'GET' && $path === '/admin/reseller-payments') {
            Helpers::requireAdmin();
            Http::json(['success' => true, 'payments' => Db::readResellerPayments()]);
        }
        if ($method === 'PUT' && preg_match('#^/admin/reseller-payments/(\d+)/approve$#', $path, $m)) {
            $admin = Helpers::requireAdmin();
            self::adminApprovePayment((int)$m[1], $admin);
            return true;
        }
        if ($method === 'PUT' && preg_match('#^/admin/reseller-payments/(\d+)/reject$#', $path, $m)) {
            $admin = Helpers::requireAdmin();
            self::adminRejectPayment((int)$m[1], $admin);
            return true;
        }
        if ($method === 'GET' && $path === '/admin/settings/reseller-config') {
            Helpers::requireAdmin();
            $settings = Db::readSettings();
            $defaults = Db::defaultSettings();
            Http::json([
                'success' => true,
                'bankDetails' => $settings['resellerBankDetails'] ?? $defaults['resellerBankDetails'],
                'broadcast' => $settings['resellerBroadcast'] ?? $defaults['resellerBroadcast'],
                'rates' => $settings['resellerRates'] ?? $defaults['resellerRates'],
            ]);
        }
        if ($method === 'PUT' && $path === '/admin/settings/reseller-bank') {
            Helpers::requireAdmin();
            $body = Http::body();
            $settings = Db::readSettings();
            $cur = $settings['resellerBankDetails'] ?? [];
            $settings['resellerBankDetails'] = [
                'bankName' => array_key_exists('bankName', $body) ? trim((string)$body['bankName']) : ($cur['bankName'] ?? 'Raqami Islamic Digital Bank'),
                'accountTitle' => array_key_exists('accountTitle', $body) ? trim((string)$body['accountTitle']) : ($cur['accountTitle'] ?? 'Muhammad Usman'),
                'accountNumber' => array_key_exists('accountNumber', $body) ? trim((string)$body['accountNumber']) : ($cur['accountNumber'] ?? '024798897813'),
                'iban' => array_key_exists('iban', $body) ? trim((string)$body['iban']) : ($cur['iban'] ?? 'PK04ROMI0000024798897813'),
                'instructions' => array_key_exists('instructions', $body) ? trim((string)$body['instructions']) : ($cur['instructions'] ?? ''),
                'updatedAt' => Db::isoNow(),
            ];
            Db::writeSettings($settings);
            Http::json(['success' => true, 'message' => 'Reseller bank payment details updated successfully', 'bankDetails' => $settings['resellerBankDetails']]);
        }
        if ($method === 'PUT' && $path === '/admin/settings/reseller-broadcast') {
            Helpers::requireAdmin();
            $body = Http::body();
            $settings = Db::readSettings();
            $settings['resellerBroadcast'] = [
                'enabled' => array_key_exists('enabled', $body) ? (bool)$body['enabled'] : true,
                'title' => array_key_exists('title', $body) ? trim((string)$body['title']) : 'Hello dear resellers,',
                'message' => array_key_exists('message', $body) ? trim((string)$body['message']) : '',
                'phone1' => array_key_exists('phone1', $body) ? trim((string)$body['phone1']) : '+92 331 0356428',
                'phone2' => array_key_exists('phone2', $body) ? trim((string)$body['phone2']) : '+92 319 8273690',
                'updatedAt' => Db::isoNow(),
            ];
            Db::writeSettings($settings);
            Http::json(['success' => true, 'message' => 'Reseller broadcast announcement updated successfully', 'broadcast' => $settings['resellerBroadcast']]);
        }
        if ($method === 'PUT' && $path === '/admin/settings/reseller-rates') {
            Helpers::requireAdmin();
            $body = Http::body();
            $settings = Db::readSettings();
            $settings['resellerRates'] = [
                'basic' => array_key_exists('basic', $body) ? (float)$body['basic'] : 0,
                'pro' => array_key_exists('pro', $body) ? (float)$body['pro'] : 500,
                'ultra' => array_key_exists('ultra', $body) ? (float)$body['ultra'] : 800,
                'plus' => array_key_exists('plus', $body) ? (float)$body['plus'] : 500,
                'max' => array_key_exists('max', $body) ? (float)$body['max'] : 800,
                'vibe' => 0,
            ];
            Db::writeSettings($settings);
            Http::json(['success' => true, 'message' => 'Reseller plan rates updated successfully', 'rates' => $settings['resellerRates']]);
        }

        return false;
    }

    private static function resellerLogin(): void
    {
        $body = Http::body();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        if (!$email || !$password) {
            Http::json(['error' => 'ValidationError', 'message' => 'Email and password are required'], 400);
        }
        $cleanEmail = Db::sanitizeEmail($email);
        $resellers = Db::readResellers();
        $reseller = null;
        foreach ($resellers as $r) {
            if (Db::sanitizeEmail($r['email'] ?? '') === $cleanEmail) {
                $reseller = $r;
                break;
            }
        }
        if (!$reseller || !Db::verifyPassword($password, $reseller['passwordHash'] ?? '', (string)($reseller['plainPasswordHint'] ?? ''), (string)($reseller['email'] ?? ''))) {
            Http::json(['error' => 'InvalidCredentials', 'message' => 'Invalid reseller email or password'], 401);
        }
        if (($reseller['status'] ?? '') === 'suspended') {
            Http::json(['error' => 'Suspended', 'message' => 'Your reseller account has been suspended'], 403);
        }
        $token = Db::generateToken([
            'resellerId' => $reseller['id'],
            'email' => $reseller['email'],
            'role' => 'reseller',
            'name' => $reseller['name'],
        ]);
        Http::json([
            'success' => true,
            'token' => $token,
            'reseller' => [
                'id' => $reseller['id'],
                'name' => $reseller['name'],
                'email' => $reseller['email'],
                'allowedUsers' => $reseller['allowedUsers'] ?? 50,
                'usersCreated' => $reseller['usersCreated'] ?? 0,
                'creditsQuota' => $reseller['creditsQuota'] ?? 1000000,
                'whatsapp' => $reseller['whatsapp'] ?? '',
                'status' => $reseller['status'],
            ],
        ]);
    }

    private static function resellerMe(array $r): void
    {
        $useMysqlFast = \App\Database::isEnabled();
        $users = $useMysqlFast ? Db::readUsersByResellerMysql((int)$r['id']) : Db::readUsers();
        $settings = Db::readSettings();
        $defaults = Db::defaultSettings();
        $myUsers = $useMysqlFast ? $users : (Db::getUserIndexes($users)['byResellerId'][(string)$r['id']] ?? []);
        $now = time();

        $planCounts = ['basic' => 0, 'plus' => 0, 'max' => 0, 'pro' => 0, 'ultra' => 0, 'vibe' => 0];
        foreach ($myUsers as $u) {
            $p = strtolower((string)($u['plan'] ?? 'pro'));
            $planCounts[$p] = ($planCounts[$p] ?? 0) + 1;
        }

        if (count($myUsers) === 0 && (((float)($r['deletedCharges'] ?? 0)) > 0 || ((int)($r['usersCreated'] ?? 0)) !== 0)) {
            $r['deletedCharges'] = 0;
            $r['usersCreated'] = 0;
            $allResellers = Db::readResellers();
            foreach ($allResellers as $ri => $curr) {
                if (($curr['id'] ?? null) === $r['id']) {
                    $allResellers[$ri]['deletedCharges'] = 0;
                    $allResellers[$ri]['usersCreated'] = 0;
                    Db::writeResellers($allResellers);
                    break;
                }
            }
        }

        $billingInfo = Helpers::getResellerBillingInfo($r, $users, $settings);
        $billedMap = [];
        foreach ($billingInfo['myUsers'] as $bu) {
            $billedMap[$bu['id']] = $bu;
        }

        $activeUsers = 0;
        $expiredUsers = 0;
        foreach ($myUsers as $u) {
            $exp = !empty($u['planExpiresAt']) ? (strtotime((string)$u['planExpiresAt']) ?: 0) : 0;
            $bu = $billedMap[$u['id']] ?? null;
            if ($exp <= $now) {
                $expiredUsers++;
            } elseif (!$bu || empty($bu['isUnpaidOverdue'])) {
                $activeUsers++;
            }
        }

        $allowed = (int)($r['allowedUsers'] ?? 50);
        $rem = max(0, $allowed - count($myUsers));

        Http::json([
            'success' => true,
            'reseller' => [
                'id' => $r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'allowedUsers' => $allowed,
                'usersCreated' => count($myUsers),
                'remainingUsers' => $rem,
                'creditsQuota' => $r['creditsQuota'] ?? 1000000,
                'whatsapp' => $r['whatsapp'] ?? '',
                'status' => $billingInfo['isSuspended'] ? 'suspended' : ($r['status'] ?? 'active'),
                'rawStatus' => $r['status'] ?? 'active',
                'isOverdue' => $billingInfo['isOverdue'],
                'discount' => $billingInfo['discountAmount'],
                'discountPercent' => $billingInfo['discountPercent'],
            ],
            'metrics' => [
                'totalUsers' => count($myUsers),
                'activeUsers' => $activeUsers,
                'pendingUsers' => 0,
                'expiredUsers' => $expiredUsers,
                'graceUsersCount' => $billingInfo['graceUsersCount'],
                'overdueUsersCount' => $billingInfo['overdueUsersCount'],
                'usersToday' => $billingInfo['usersToday'],
                'usersThisWeek' => $billingInfo['usersThisWeek'],
                'isOverdue' => $billingInfo['isOverdue'],
                'proPlans' => ($planCounts['pro'] ?? 0) + ($planCounts['plus'] ?? 0),
                'maxPlans' => ($planCounts['max'] ?? 0) + ($planCounts['ultra'] ?? 0),
                'basicPlans' => $planCounts['basic'] ?? 0,
                'plusPlans' => $planCounts['plus'] ?? 0,
                'quotaLeft' => "$rem / $allowed",
            ],
            'billing' => [
                'rates' => $billingInfo['rates'],
                'usersCharges' => $billingInfo['usersCharges'],
                'deletedCharges' => $billingInfo['deletedCharges'],
                'rawTotalCharges' => $billingInfo['rawTotalCharges'],
                'discount' => $billingInfo['totalDiscount'],
                'discountAmount' => $billingInfo['discountAmount'],
                'discountPercent' => $billingInfo['discountPercent'],
                'totalCharges' => $billingInfo['totalCharges'],
                'totalPaid' => $billingInfo['totalPaid'],
                'balanceDue' => $billingInfo['balanceDue'],
                'isOverdue' => $billingInfo['isOverdue'],
                'currency' => 'PKR',
                'usdtRate' => 280,
            ],
            'bankDetails' => $settings['resellerBankDetails'] ?? $defaults['resellerBankDetails'],
            'broadcast' => $settings['resellerBroadcast'] ?? $defaults['resellerBroadcast'],
        ]);
    }

    private static function changePassword(array $reqReseller): void
    {
        $body = Http::body();
        $currentPassword = $body['currentPassword'] ?? '';
        $newPassword = (string)($body['newPassword'] ?? '');
        if (strlen($newPassword) < 4) {
            Http::json(['error' => 'ValidationError', 'message' => 'New password must be at least 4 characters'], 400);
        }
        $resellers = Db::readResellers();
        foreach ($resellers as $i => $r) {
            if (($r['id'] ?? null) === $reqReseller['id']) {
                if ($currentPassword && !Db::verifyPassword($currentPassword, $r['passwordHash'] ?? '')) {
                    Http::json(['error' => 'InvalidPassword', 'message' => 'Current password does not match'], 400);
                }
                $resellers[$i]['passwordHash'] = Db::hashPassword($newPassword);
                $resellers[$i]['plainPasswordHint'] = $newPassword;
                Db::writeResellers($resellers);
                Http::json(['success' => true, 'message' => 'Password updated successfully']);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'Reseller not found'], 404);
    }

    private static function submitPayment(array $reqReseller): void
    {
        $body = Http::body();
        $paidAmount = (float)($body['amount'] ?? 0);
        if ($paidAmount <= 0) {
            Http::json(['error' => 'ValidationError', 'message' => 'Valid payment amount is required'], 400);
        }
        $payments = Db::readResellerPayments();
        $maxId = 0;
        foreach ($payments as $p) {
            $pid = (int)($p['id'] ?? 0);
            if ($pid > $maxId) {
                $maxId = $pid;
            }
        }
        $nextId = $maxId + 1;

        $proof = $body['proof'] ?? null;
        $proofUrl = null;
        if (is_string($proof) && str_starts_with($proof, 'data:image/')) {
            $validProof = Helpers::decodeValidBase64Image($proof);
            if (!$validProof) {
                Http::json(['error' => 'InvalidImage', 'message' => 'Payment proof must be a valid PNG, JPG, WEBP, or GIF image.'], 400);
            }
            $fileName = "proof-$nextId-" . (int)round(microtime(true) * 1000) . ".{$validProof['ext']}";
            $root = dirname(__DIR__, 2);
            $pubDir = "$root/public/uploads/payments";
            $distDir = "$root/dist/uploads/payments";
            if (!is_dir($pubDir)) @mkdir($pubDir, 0775, true);
            if (!is_dir($distDir)) @mkdir($distDir, 0775, true);
            file_put_contents("$pubDir/$fileName", $validProof['buffer']);
            if (is_dir($distDir)) {
                file_put_contents("$distDir/$fileName", $validProof['buffer']);
            }
            $proofUrl = "/uploads/payments/$fileName";
        } elseif (is_string($proof) && str_starts_with($proof, '/uploads/payments/')) {
            $proofUrl = $proof;
        }

        $newPayment = [
            'id' => $nextId,
            'resellerId' => $reqReseller['id'],
            'resellerName' => $reqReseller['name'],
            'resellerEmail' => $reqReseller['email'],
            'amount' => $paidAmount,
            'note' => !empty($body['note']) ? trim((string)$body['note']) : '',
            'proof' => $proofUrl,
            'status' => 'pending',
            'createdAt' => Db::isoNow(),
        ];

        array_unshift($payments, $newPayment);
        Db::writeResellerPayments($payments);
        Http::json([
            'success' => true,
            'message' => 'Payment submitted successfully. Admin will verify and update your balance.',
            'payment' => $newPayment,
        ], 201);
    }

    private static function resellerUsersList(array $reqReseller): void
    {
        $useMysqlFast = \App\Database::isEnabled();
        $users = $useMysqlFast ? Db::readUsersByResellerMysql((int)$reqReseller['id']) : Db::readUsers();
        $settings = Db::readSettings();
        $currentR = $useMysqlFast ? Db::findResellerByIdMysql((int)$reqReseller['id']) : null;
        if (!$currentR && !$useMysqlFast) {
            $resellers = Db::readResellers();
            foreach ($resellers as $r) {
                if (($r['id'] ?? null) === $reqReseller['id']) {
                    $currentR = $r;
                    break;
                }
            }
        }
        $billing = $currentR ? Helpers::getResellerBillingInfo($currentR, $users, $settings) : null;
        $billedMap = [];
        if ($billing) {
            foreach ($billing['myUsers'] as $bu) {
                $billedMap[$bu['id']] = $bu;
            }
        }
        $myUsers = $useMysqlFast ? $users : (Db::getUserIndexes($users)['byResellerId'][(string)$reqReseller['id']] ?? []);
        $nowMs = (int)round(microtime(true) * 1000);
        $graceMs = 3 * 24 * 60 * 60 * 1000;

        $formatted = [];
        foreach ($myUsers as $u) {
            $days = Db::calculateDaysRemaining($u['planExpiresAt'] ?? null);
            $createdMs = !empty($u['createdAt']) ? ((strtotime((string)$u['createdAt']) ?: time()) * 1000) : $nowMs;
            $ageMs = max(0, $nowMs - $createdMs);
            $rawPastGrace = $ageMs > $graceMs;
            $rawHoursLeft = max(0, (int)ceil(($graceMs - $ageMs) / (60 * 60 * 1000)));

            $bu = $billedMap[$u['id']] ?? null;
            $isPaid = $bu ? (bool)$bu['isPaid'] : false;
            $inGracePeriod = $bu ? (bool)$bu['inGracePeriod'] : !$rawPastGrace;
            $isPastGrace = $bu ? (bool)$bu['isPastGrace'] : $rawPastGrace;
            $hoursLeftInGrace = $isPaid ? 0 : ($bu ? $bu['hoursLeftInGrace'] : $rawHoursLeft);
            $effectiveStatus = (!$isPaid && $isPastGrace) ? 'unpaid_suspended' : ($u['status'] ?? 'active');

            $formatted[] = [
                'id' => $u['id'],
                'name' => $u['name'] ?? '',
                'email' => $u['email'] ?? '',
                'plainPasswordHint' => $u['plainPasswordHint'] ?? null,
                'plan' => $u['plan'] ?? 'pro',
                'credits' => $u['credits'] ?? 0,
                'planExpiresAt' => $u['planExpiresAt'] ?? null,
                'daysRemaining' => $days,
                'status' => $effectiveStatus,
                'rawStatus' => $u['status'] ?? 'active',
                'createdAt' => $u['createdAt'] ?? null,
                'requestedDays' => $u['requestedDays'] ?? null,
                'isPaid' => $isPaid,
                'amountDue' => $bu ? $bu['amountDue'] : 0,
                'inGracePeriod' => $inGracePeriod,
                'isPastGrace' => $isPastGrace,
                'hoursLeftInGrace' => $hoursLeftInGrace,
                'daysSinceCreation' => (int)floor($ageMs / 86400000),
            ];
        }

        $allowed = (int)($reqReseller['allowedUsers'] ?? 50);
        Http::json([
            'success' => true,
            'users' => $formatted,
            'total' => count($formatted),
            'allowedUsers' => $allowed,
            'remainingUsers' => max(0, $allowed - count($formatted)),
            'isOverdue' => (bool)($billing && $billing['isOverdue']),
            'balanceDue' => $billing ? $billing['balanceDue'] : 0,
        ]);
    }

    private static function resellerCreateUser(array $reqReseller): void
    {
        $body = Http::body();
        $name = $body['name'] ?? '';
        $username = $body['username'] ?? '';
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $plan = $body['plan'] ?? 'pro';

        if (!$email || !$password) {
            Http::json(['error' => 'ValidationError', 'message' => 'Email and password are required'], 400);
        }

        $users = Db::readUsers();
        $cleanEmail = Db::sanitizeEmail($email);
        $cleanPass = Helpers::normalizePasswordForEmail($password, $cleanEmail);

        $existingIdx = -1;
        foreach ($users as $i => $u) {
            if (Db::sanitizeEmail($u['email'] ?? '') === $cleanEmail) {
                $existingIdx = $i;
                break;
            }
        }
        if ($existingIdx !== -1) {
            $existingUser = $users[$existingIdx];
            $existingPlan = strtolower((string)($existingUser['plan'] ?? 'basic'));
            $isFreeOrBasic = in_array($existingPlan, ['basic', 'free', 'trial', 'starter', 'vibe'], true);
            if (!empty($existingUser['resellerId']) && $existingUser['resellerId'] !== $reqReseller['id'] && !$isFreeOrBasic) {
                Http::json(['error' => 'Conflict', 'message' => 'User with this email already exists'], 409);
            }
        }

        $myUsersCount = count(array_filter($users, fn($u) => ($u['resellerId'] ?? null) === $reqReseller['id']));
        $allowed = (int)($reqReseller['allowedUsers'] ?? 50);
        if ($myUsersCount >= $allowed && ($existingIdx === -1 || ($users[$existingIdx]['resellerId'] ?? null) !== $reqReseller['id'])) {
            Http::json([
                'error' => 'QuotaExceeded',
                'message' => "Your account creation quota ($allowed users) is full. Please contact Flow by Pak admin to increase your quota.",
            ], 403);
        }

        $chosenPlan = strtolower((string)$plan) === 'max' ? 'max' : 'pro';
        $durationDays = $chosenPlan === 'pro' ? 20 : 30;
        $assignedCredits = $chosenPlan === 'max' ? 50000 : 35000;
        $localPart = explode('@', $cleanEmail)[0] ?? 'user';
        $cleanUsername = $username
            ? Db::sanitizeCredential($username)
            : ($name ? Db::sanitizeCredential($name) : preg_replace('/[^a-zA-Z0-9_]/', '', $localPart));

        $resellers = Db::readResellers();
        foreach ($resellers as $r) {
            if (($r['id'] ?? null) === $reqReseller['id'] && ($r['status'] ?? '') === 'suspended') {
                Http::json(['error' => 'ResellerSuspended', 'message' => 'Your reseller account has been suspended by administrator.'], 403);
            }
        }

        $nowIso = Db::isoNow();
        $expiresAt = Db::isoNow($durationDays * 86400);
        Db::unmarkUserDeleted($cleanEmail);

        if ($existingIdx !== -1) {
            $existing = $users[$existingIdx];
            $wasSameReseller = ($existing['resellerId'] ?? null) === $reqReseller['id'];
            $existing['resellerId'] = $reqReseller['id'];
            $existing['resellerName'] = $reqReseller['name'];
            $existing['resellerEmail'] = $reqReseller['email'];
            $existing['name'] = $name ? Db::sanitizeCredential($name) : ($existing['name'] ?: $cleanUsername);
            $existing['username'] = $existing['username'] ?: $cleanUsername;
            $existing['email'] = $cleanEmail;
            $existing['passwordHash'] = Db::hashPassword($cleanPass);
            $existing['plainPasswordHint'] = $cleanPass;
            $existing['plan'] = $chosenPlan;
            $existing['credits'] = $assignedCredits;
            $existing['creditsTotal'] = $assignedCredits;
            $existing['planExpiresAt'] = $expiresAt;
            $existing['requestedDays'] = $durationDays;
            $existing['daysRemaining'] = $durationDays;
            $existing['onboardingAccepted'] = true;
            $existing['emailVerified'] = true;
            $existing['isLocked'] = false;
            $existing['lockReason'] = null;
            $existing['lockedAt'] = null;
            $existing['status'] = 'active';
            $existing['updatedAt'] = $nowIso;

            Db::unmarkUserDeleted($cleanEmail, $existing['id']);
            $users[$existingIdx] = $existing;
            Db::writeUsers($users, ['immediate' => true]);

            if (!$wasSameReseller) {
                foreach ($resellers as $ri => $r) {
                    if (($r['id'] ?? null) === $reqReseller['id']) {
                        $resellers[$ri]['usersCreated'] = (int)($r['usersCreated'] ?? 0) + 1;
                        Db::writeResellers($resellers);
                        break;
                    }
                }
            }
            $safe = $existing;
            unset($safe['passwordHash']);
            Http::json([
                'success' => true,
                'message' => 'User account updated and activated immediately! (3-day grace period for payment is active)',
                'user' => $safe,
            ], 200);
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
            'resellerId' => $reqReseller['id'],
            'resellerName' => $reqReseller['name'],
            'resellerEmail' => $reqReseller['email'],
            'name' => $name ? Db::sanitizeCredential($name) : $cleanUsername,
            'username' => $cleanUsername,
            'email' => $cleanEmail,
            'passwordHash' => Db::hashPassword($cleanPass),
            'plainPasswordHint' => $cleanPass,
            'plan' => $chosenPlan,
            'credits' => $assignedCredits,
            'creditsTotal' => $assignedCredits,
            'planExpiresAt' => $expiresAt,
            'requestedDays' => $durationDays,
            'daysRemaining' => $durationDays,
            'onboardingAccepted' => true,
            'emailVerified' => true,
            'isLocked' => false,
            'status' => 'active',
            'createdAt' => $nowIso,
            'updatedAt' => $nowIso,
        ];

        Db::unmarkUserDeleted($cleanEmail, $newUser['id']);
        $users[] = $newUser;
        Db::writeUsers($users, ['immediate' => true]);

        foreach ($resellers as $ri => $r) {
            if (($r['id'] ?? null) === $reqReseller['id']) {
                $resellers[$ri]['usersCreated'] = (int)($r['usersCreated'] ?? 0) + 1;
                Db::writeResellers($resellers);
                break;
            }
        }

        $safe = $newUser;
        unset($safe['passwordHash']);
        Http::json([
            'success' => true,
            'message' => 'User account created and activated immediately! (3-day grace period for payment is active)',
            'user' => $safe,
        ], 201);
    }

    private static function resellerUpdateUser(array $reqReseller, int $userId): void
    {
        $useMysqlFast = \App\Database::isEnabled();
        $users = null;
        $idx = -1;
        if ($useMysqlFast) {
            $user = Db::findUserByIdMysql($userId);
            if (!$user || (int)($user['resellerId'] ?? 0) !== (int)$reqReseller['id']) {
                Http::json(['error' => 'NotFound', 'message' => 'User not found or not owned by you'], 404);
            }
        } else {
            $users = Db::readUsers();
            foreach ($users as $i => $u) {
                if ((int)($u['id'] ?? 0) === $userId && ($u['resellerId'] ?? null) === $reqReseller['id']) {
                    $idx = $i;
                    break;
                }
            }
            if ($idx === -1) {
                Http::json(['error' => 'NotFound', 'message' => 'User not found or not owned by you'], 404);
            }
            $user = $users[$idx];
        }
        $body = Http::body();
        if (!empty($body['addDays']) || array_key_exists('days', $body)) {
            Http::json(['error' => 'Forbidden', 'message' => 'Resellers are not allowed to add or extend days. Only administrator can extend validity.'], 403);
        }
        if (!empty($body['name'])) $user['name'] = Db::sanitizeCredential($body['name']);
        if (!empty($body['plan'])) $user['plan'] = strtolower((string)$body['plan']) === 'max' ? 'max' : 'pro';
        if (!empty($body['status'])) $user['status'] = $body['status'];
        if (!empty($body['password']) && Db::sanitizeCredential($body['password']) !== '') {
            $cleanPass = Helpers::normalizePasswordForEmail($body['password'], (string)($user['email'] ?? ''));
            $user['passwordHash'] = Db::hashPassword($cleanPass);
            $user['plainPasswordHint'] = $cleanPass;
        }
        $user['updatedAt'] = Db::isoNow();
        Db::unmarkUserDeleted((string)($user['email'] ?? ''), $user['id']);
        if ($useMysqlFast) {
            Db::upsertSingleUser($user);
        } else {
            $users[$idx] = $user;
            Db::writeUsers($users, ['immediate' => true]);
        }
        $safe = $user;
        unset($safe['passwordHash']);
        Http::json(['success' => true, 'message' => 'User updated successfully', 'user' => $safe]);
    }

    private static function resellerDeleteUser(array $reqReseller, int $userId): void
    {
        $useMysqlFast = \App\Database::isEnabled();
        $users = null;
        $index = -1;
        if ($useMysqlFast) {
            $targetUser = Db::findUserByIdMysql($userId);
            if (!$targetUser || (int)($targetUser['resellerId'] ?? 0) !== (int)$reqReseller['id']) {
                Http::json(['error' => 'NotFound', 'message' => 'User not found or not owned by you'], 404);
            }
        } else {
            $users = Db::readUsers();
            foreach ($users as $i => $u) {
                if ((int)($u['id'] ?? 0) === $userId && ($u['resellerId'] ?? null) === $reqReseller['id']) {
                    $index = $i;
                    break;
                }
            }
            if ($index === -1) {
                Http::json(['error' => 'NotFound', 'message' => 'User not found or not owned by you'], 404);
            }
            $targetUser = $users[$index];
        }
        if (!empty($targetUser['createdAt'])) {
            $createdTime = strtotime((string)$targetUser['createdAt']) ?: time();
            $diffHours = (time() - $createdTime) / 3600;
            if ($diffHours > 72) {
                Http::json([
                    'error' => 'DeletionPeriodExpired',
                    'message' => 'Users can only be deleted within 3 days (72 hours) of creation. Since 3 days have passed, this account is locked. Please contact admin if you need to remove it.',
                ], 403);
            }
        }
        Db::markUserDeleted($targetUser['id'], (string)($targetUser['email'] ?? ''));
        if ($useMysqlFast) {
            Db::deleteSingleUserMysql($userId);
            $remainingCount = count(Db::readUsersByResellerMysql((int)$reqReseller['id']));
        } else {
            array_splice($users, $index, 1);
            Db::writeUsers($users, ['immediate' => true]);
            $remainingCount = count(array_filter($users, fn($u) => ($u['resellerId'] ?? null) === $reqReseller['id']));
        }

        $resellers = Db::readResellers();
        foreach ($resellers as $ri => $r) {
            if (($r['id'] ?? null) === $reqReseller['id']) {
                $resellers[$ri]['usersCreated'] = $remainingCount;
                if ($remainingCount === 0) {
                    $resellers[$ri]['deletedCharges'] = 0;
                }
                Db::writeResellers($resellers);
                break;
            }
        }
        Http::json(['success' => true, 'message' => 'User deleted and access revoked successfully']);
    }

    private static function adminResellerAnalytics(): void
    {
        $resellers = Db::readResellers();
        $users = Db::readUsers();
        $settings = Db::readSettings();

        $totalResellerUsers = 0;
        $totalUsersToday = 0;
        $totalUsersThisWeek = 0;
        $totalOutstandingDues = 0;
        $totalPaidOverall = 0;
        $totalOverdueResellers = 0;
        $resellerAnalytics = [];

        foreach ($resellers as $r) {
            $info = Helpers::getResellerBillingInfo($r, $users, $settings);
            $totalResellerUsers += $info['usersCount'];
            $totalUsersToday += $info['usersToday'];
            $totalUsersThisWeek += $info['usersThisWeek'];
            $totalOutstandingDues += $info['balanceDue'];
            $totalPaidOverall += $info['totalPaid'];
            if ($info['isOverdue']) {
                $totalOverdueResellers++;
            }
            $resellerAnalytics[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'whatsapp' => $r['whatsapp'] ?? '',
                'status' => $info['isSuspended'] ? 'suspended' : ($r['status'] ?? 'active'),
                'rawStatus' => $r['status'] ?? 'active',
                'isOverdue' => $info['isOverdue'],
                'allowedUsers' => $r['allowedUsers'] ?? 50,
                'usersCreated' => $info['usersCount'],
                'usersToday' => $info['usersToday'],
                'usersThisWeek' => $info['usersThisWeek'],
                'graceUsersCount' => $info['graceUsersCount'],
                'overdueUsersCount' => $info['overdueUsersCount'],
                'rates' => $info['rates'],
                'proRate' => $r['proRate'] ?? null,
                'maxRate' => $r['maxRate'] ?? null,
                'discount' => $info['discountAmount'],
                'discountPercent' => $info['discountPercent'],
                'totalDiscount' => $info['totalDiscount'],
                'totalCharges' => $info['totalCharges'],
                'totalPaid' => $info['totalPaid'],
                'balanceDue' => $info['balanceDue'],
                'createdAt' => $r['createdAt'] ?? null,
                'users' => $info['myUsers'],
            ];
        }

        Http::json([
            'success' => true,
            'summary' => [
                'totalResellers' => count($resellers),
                'totalResellerUsers' => $totalResellerUsers,
                'totalUsersToday' => $totalUsersToday,
                'totalUsersThisWeek' => $totalUsersThisWeek,
                'totalOutstandingDues' => $totalOutstandingDues,
                'totalPaidOverall' => $totalPaidOverall,
                'totalOverdueResellers' => $totalOverdueResellers,
                'date' => Db::isoNow(),
            ],
            'resellers' => $resellerAnalytics,
        ]);
    }

    private static function adminClearDues(int $id): void
    {
        $resellers = Db::readResellers();
        foreach ($resellers as $i => $r) {
            if ((int)($r['id'] ?? 0) === $id) {
                $billing = Helpers::getResellerBillingInfo($r, Db::readUsers(), Db::readSettings());
                $resellers[$i]['totalPaid'] = $billing['totalCharges'];
                if (($resellers[$i]['status'] ?? '') === 'suspended') {
                    $resellers[$i]['status'] = 'active';
                }
                Db::writeResellers($resellers);
                $label = $r['name'] ?: $r['email'];
                Http::json([
                    'success' => true,
                    'message' => "All dues cleared for $label! Reseller and all users are now 100% ACTIVE.",
                    'reseller' => $resellers[$i],
                ]);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'Reseller not found'], 404);
    }

    private static function adminRecordPayment(int $id): void
    {
        $body = Http::body();
        $payAmount = (float)($body['amount'] ?? 0);
        if ($payAmount <= 0) {
            Http::json(['error' => 'ValidationError', 'message' => 'Valid payment amount is required'], 400);
        }
        $resellers = Db::readResellers();
        foreach ($resellers as $i => $r) {
            if ((int)($r['id'] ?? 0) === $id) {
                $resellers[$i]['totalPaid'] = (float)($r['totalPaid'] ?? 0) + $payAmount;
                if (($resellers[$i]['status'] ?? '') === 'suspended') {
                    $resellers[$i]['status'] = 'active';
                }
                Db::writeResellers($resellers);
                $label = $r['name'] ?: $r['email'];
                $fmt = number_format($payAmount);
                Http::json([
                    'success' => true,
                    'message' => "Payment of Rs. $fmt recorded for $label!",
                    'reseller' => $resellers[$i],
                ]);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'Reseller not found'], 404);
    }

    private static function adminResellersList(): void
    {
        $resellers = Db::readResellers();
        $users = Db::readUsers();
        $settings = Db::readSettings();
        $baseRates = $settings['resellerRates'] ?? ['pro' => 500, 'max' => 800, 'basic' => 0, 'ultra' => 800, 'plus' => 500];
        $byReseller = Db::getUserIndexes($users)['byResellerId'];

        $formatted = [];
        foreach ($resellers as $r) {
            $myUsers = $byReseller[(string)$r['id']] ?? [];
            $rates = array_merge($baseRates, is_array($r['customRates'] ?? null) ? $r['customRates'] : []);
            if (isset($r['proRate']) && $r['proRate'] !== null) $rates['pro'] = (float)$r['proRate'];
            if (isset($r['maxRate']) && $r['maxRate'] !== null) $rates['max'] = (float)$r['maxRate'];

            $usersCharges = 0;
            foreach ($myUsers as $u) {
                if (($u['status'] ?? '') === 'active') {
                    $p = strtolower((string)($u['plan'] ?? 'pro'));
                    $usersCharges += ($rates[$p] ?? 500);
                }
            }
            $deletedCharges = count($myUsers) === 0 ? 0 : (float)($r['deletedCharges'] ?? 0);
            $rawTotalCharges = $usersCharges + $deletedCharges;
            $discount = (float)($r['discount'] ?? 0);
            $discountPercent = (float)($r['discountPercent'] ?? 0);
            $totalDiscount = $discount;
            if ($discountPercent > 0) {
                $totalDiscount += round(($rawTotalCharges * $discountPercent) / 100);
            }
            $totalDiscount = min($rawTotalCharges, $totalDiscount);
            $totalCharges = max(0, $rawTotalCharges - $totalDiscount);
            $totalPaid = (float)($r['totalPaid'] ?? 0);
            $balanceDue = max(0, $totalCharges - $totalPaid);
            $allowed = (int)($r['allowedUsers'] ?? 50);

            $formatted[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'plainPasswordHint' => $r['plainPasswordHint'] ?? null,
                'allowedUsers' => $allowed,
                'usersCreated' => count($myUsers),
                'remainingUsers' => max(0, $allowed - count($myUsers)),
                'creditsQuota' => $r['creditsQuota'] ?? 1000000,
                'whatsapp' => $r['whatsapp'] ?? '',
                'status' => $r['status'] ?? 'active',
                'createdAt' => $r['createdAt'] ?? null,
                'discount' => $discount,
                'discountPercent' => $discountPercent,
                'proRate' => $r['proRate'] ?? null,
                'maxRate' => $r['maxRate'] ?? null,
                'rawTotalCharges' => $rawTotalCharges,
                'totalDiscount' => $totalDiscount,
                'totalCharges' => $totalCharges,
                'totalPaid' => $totalPaid,
                'balanceDue' => $balanceDue,
            ];
        }
        Http::json(['success' => true, 'resellers' => $formatted]);
    }

    private static function adminCreateReseller(): void
    {
        $body = Http::body();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        if (!$email || !$password) {
            Http::json(['error' => 'ValidationError', 'message' => 'Email and password are required'], 400);
        }
        $cleanEmail = strtolower(trim((string)$email));
        $resellers = Db::readResellers();
        foreach ($resellers as $r) {
            if (strtolower((string)($r['email'] ?? '')) === $cleanEmail) {
                Http::json(['error' => 'Conflict', 'message' => 'Reseller with this email already exists'], 409);
            }
        }
        $maxId = 0;
        foreach ($resellers as $r) {
            $rid = (int)($r['id'] ?? 0);
            if ($rid > $maxId) {
                $maxId = $rid;
            }
        }
        $newReseller = [
            'id' => $maxId + 1,
            'name' => !empty($body['name']) ? trim((string)$body['name']) : (explode('@', $cleanEmail)[0] ?? 'reseller'),
            'email' => $cleanEmail,
            'passwordHash' => Db::hashPassword((string)$password),
            'plainPasswordHint' => (string)$password,
            'allowedUsers' => (int)($body['allowedUsers'] ?? 50) ?: 50,
            'creditsQuota' => (int)($body['creditsQuota'] ?? 1000000) ?: 1000000,
            'usersCreated' => 0,
            'totalPaid' => 0,
            'deletedCharges' => 0,
            'discount' => max(0, (float)($body['discount'] ?? 0)),
            'discountPercent' => max(0, min(100, (float)($body['discountPercent'] ?? 0))),
            'proRate' => (isset($body['proRate']) && $body['proRate'] !== '' && $body['proRate'] !== null) ? (float)$body['proRate'] : null,
            'maxRate' => (isset($body['maxRate']) && $body['maxRate'] !== '' && $body['maxRate'] !== null) ? (float)$body['maxRate'] : null,
            'whatsapp' => !empty($body['whatsapp']) ? trim((string)$body['whatsapp']) : '',
            'status' => $body['status'] ?? 'active',
            'createdAt' => Db::isoNow(),
        ];
        $resellers[] = $newReseller;
        Db::writeResellers($resellers);
        $safe = $newReseller;
        unset($safe['passwordHash']);
        Http::json(['success' => true, 'message' => 'Reseller account created successfully', 'reseller' => $safe], 201);
    }

    private static function adminUpdateReseller(int $id): void
    {
        $resellers = Db::readResellers();
        foreach ($resellers as $i => $r) {
            if ((int)($r['id'] ?? 0) === $id) {
                $body = Http::body();
                if (!empty($body['name'])) $r['name'] = trim((string)$body['name']);
                if (!empty($body['email'])) $r['email'] = strtolower(trim((string)$body['email']));
                if (!empty($body['password']) && trim((string)$body['password']) !== '') {
                    $r['passwordHash'] = Db::hashPassword((string)$body['password']);
                    $r['plainPasswordHint'] = (string)$body['password'];
                }
                if (!empty($body['addAllowedUsers']) && (int)$body['addAllowedUsers'] > 0) {
                    $r['allowedUsers'] = (int)($r['allowedUsers'] ?? 0) + (int)$body['addAllowedUsers'];
                } elseif (array_key_exists('allowedUsers', $body)) {
                    $r['allowedUsers'] = (int)$body['allowedUsers'];
                }
                if (array_key_exists('creditsQuota', $body)) $r['creditsQuota'] = (int)$body['creditsQuota'];
                if (array_key_exists('whatsapp', $body)) $r['whatsapp'] = trim((string)$body['whatsapp']);
                if (!empty($body['status'])) $r['status'] = $body['status'];
                if (array_key_exists('discount', $body)) $r['discount'] = max(0, (float)$body['discount']);
                if (array_key_exists('discountPercent', $body)) $r['discountPercent'] = max(0, min(100, (float)$body['discountPercent']));
                if (array_key_exists('proRate', $body)) $r['proRate'] = ($body['proRate'] === null || $body['proRate'] === '') ? null : (float)$body['proRate'];
                if (array_key_exists('maxRate', $body)) $r['maxRate'] = ($body['maxRate'] === null || $body['maxRate'] === '') ? null : (float)$body['maxRate'];
                if (array_key_exists('totalPaid', $body)) $r['totalPaid'] = max(0, (float)$body['totalPaid']);
                if (array_key_exists('deletedCharges', $body)) $r['deletedCharges'] = max(0, (float)$body['deletedCharges']);

                $resellers[$i] = $r;
                Db::writeResellers($resellers);
                $safe = $r;
                unset($safe['passwordHash']);
                Http::json(['success' => true, 'message' => 'Reseller updated successfully', 'reseller' => $safe]);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'Reseller not found'], 404);
    }

    private static function adminDeleteReseller(int $id): void
    {
        $resellers = Db::readResellers();
        foreach ($resellers as $i => $r) {
            if ((int)($r['id'] ?? 0) === $id) {
                array_splice($resellers, $i, 1);
                Db::writeResellers($resellers);
                Http::json(['success' => true, 'message' => 'Reseller deleted permanently']);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'Reseller not found'], 404);
    }

    private static function adminApprovePayment(int $id, array $admin): void
    {
        $payments = Db::readResellerPayments();
        foreach ($payments as $i => $payment) {
            if ((int)($payment['id'] ?? 0) === $id) {
                if (($payment['status'] ?? '') === 'approved') {
                    Http::json(['error' => 'AlreadyApproved', 'message' => 'Payment has already been approved'], 400);
                }
                $payments[$i]['status'] = 'approved';
                $payments[$i]['approvedAt'] = Db::isoNow();
                $payments[$i]['reviewedBy'] = $admin['email'];
                Db::writeResellerPayments($payments);

                $resellers = Db::readResellers();
                foreach ($resellers as $ri => $reseller) {
                    if (($reseller['id'] ?? null) === ($payment['resellerId'] ?? null)) {
                        $resellers[$ri]['totalPaid'] = (float)($reseller['totalPaid'] ?? 0) + (float)$payment['amount'];
                        Db::writeResellers($resellers);
                        break;
                    }
                }
                $rName = $payment['resellerName'] ?? 'reseller';
                Http::json([
                    'success' => true,
                    'message' => "Payment of Rs. {$payment['amount']} approved and credited to $rName!",
                    'payment' => $payments[$i],
                ]);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'Payment request not found'], 404);
    }

    private static function adminRejectPayment(int $id, array $admin): void
    {
        $body = Http::body();
        $payments = Db::readResellerPayments();
        foreach ($payments as $i => $payment) {
            if ((int)($payment['id'] ?? 0) === $id) {
                $payments[$i]['status'] = 'rejected';
                $payments[$i]['rejectedReason'] = $body['reason'] ?? 'Payment proof verification failed';
                $payments[$i]['rejectedAt'] = Db::isoNow();
                $payments[$i]['reviewedBy'] = $admin['email'];
                Db::writeResellerPayments($payments);
                Http::json(['success' => true, 'message' => 'Payment request rejected', 'payment' => $payments[$i]]);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'Payment request not found'], 404);
    }
}
