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
        if ($method === 'GET' && $path === '/reseller/message-template') {
            $r = Helpers::requireReseller();
            $settings = Db::readSettings();
            $defaultTpl = (string)($settings['welcomeMessageTemplate'] ?? Db::defaultSettings()['welcomeMessageTemplate']);
            $customTpl = trim((string)($r['welcomeMessageTemplate'] ?? ''));
            Http::json([
                'success' => true,
                'template' => $customTpl !== '' ? $customTpl : $defaultTpl,
                'isCustom' => $customTpl !== '',
                'whatsapp' => (string)($r['whatsapp'] ?? ''),
            ]);
        }
        if ($method === 'PUT' && $path === '/reseller/message-template') {
            $r = Helpers::requireReseller();
            $body = Http::body();
            $tpl = trim((string)($body['template'] ?? ''));
            $resellers = Db::readResellers();
            foreach ($resellers as $i => $item) {
                if ((int)($item['id'] ?? 0) === (int)$r['id']) {
                    if ($tpl === '') {
                        unset($resellers[$i]['welcomeMessageTemplate']);
                    } else {
                        $resellers[$i]['welcomeMessageTemplate'] = $tpl;
                    }
                    Db::writeResellers($resellers);
                    break;
                }
            }
            $settings = Db::readSettings();
            $effective = $tpl !== '' ? $tpl : (string)($settings['welcomeMessageTemplate'] ?? Db::defaultSettings()['welcomeMessageTemplate']);
            Http::json(['success' => true, 'message' => 'Message template saved!', 'template' => $effective]);
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
        if ($method === 'GET' && preg_match('#^/admin/resellers/(\d+)/users$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminResellerUsers((int)$m[1]);
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
                'planCredits' => Helpers::getResellerPlanCredits(null, $settings),
                'planDurations' => Helpers::getPlanDurations($settings),
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
            $curRates = is_array($settings['resellerRates'] ?? null) ? $settings['resellerRates'] : [];
            $settings['resellerRates'] = [
                'basic' => array_key_exists('basic', $body) ? (float)$body['basic'] : (float)($curRates['basic'] ?? 0),
                'pro' => array_key_exists('pro', $body) ? (float)$body['pro'] : (float)($curRates['pro'] ?? 500),
                'ultra' => array_key_exists('ultra', $body) ? (float)$body['ultra'] : (float)($curRates['ultra'] ?? 800),
                'plus' => array_key_exists('plus', $body) ? (float)$body['plus'] : (float)($curRates['plus'] ?? 500),
                'max' => array_key_exists('max', $body) ? (float)$body['max'] : (float)($curRates['max'] ?? 800),
                'vibe' => 0,
            ];
            $curCredits = Helpers::getResellerPlanCredits(null, $settings);
            $inPlanCr = is_array($body['planCredits'] ?? null) ? $body['planCredits'] : [];
            $nextProCr = isset($body['proCredits']) && is_numeric($body['proCredits'])
                ? max(100, (int)$body['proCredits'])
                : (isset($inPlanCr['pro']) && is_numeric($inPlanCr['pro']) ? max(100, (int)$inPlanCr['pro']) : $curCredits['pro']);
            $nextMaxCr = isset($body['maxCredits']) && is_numeric($body['maxCredits'])
                ? max(100, (int)$body['maxCredits'])
                : (isset($inPlanCr['max']) && is_numeric($inPlanCr['max']) ? max(100, (int)$inPlanCr['max']) : $curCredits['max']);
            $nextDemoCr = isset($body['demoCredits']) && is_numeric($body['demoCredits'])
                ? max(50, (int)$body['demoCredits'])
                : (isset($inPlanCr['demo']) && is_numeric($inPlanCr['demo']) ? max(50, (int)$inPlanCr['demo']) : $curCredits['demo']);
            $settings['resellerPlanCredits'] = [
                'pro' => $nextProCr,
                'max' => $nextMaxCr,
                'plus' => $nextProCr,
                'ultra' => $nextMaxCr,
                'demo' => $nextDemoCr,
            ];
            if (is_array($settings['plans']['pro'] ?? null)) {
                $settings['plans']['pro']['credits'] = $nextProCr;
            }
            if (is_array($settings['plans']['max'] ?? null)) {
                $settings['plans']['max']['credits'] = $nextMaxCr;
            }
            $curDurations = Helpers::getPlanDurations($settings);
            $inDurations = is_array($body['planDurations'] ?? null) ? $body['planDurations'] : [];
            $nextProDays = isset($body['proDays']) && is_numeric($body['proDays'])
                ? max(1, min(3650, (int)$body['proDays']))
                : (isset($inDurations['pro']) && is_numeric($inDurations['pro']) ? max(1, min(3650, (int)$inDurations['pro'])) : (int)$curDurations['pro']);
            $nextMaxDays = isset($body['maxDays']) && is_numeric($body['maxDays'])
                ? max(1, min(3650, (int)$body['maxDays']))
                : (isset($inDurations['max']) && is_numeric($inDurations['max']) ? max(1, min(3650, (int)$inDurations['max'])) : (int)$curDurations['max']);
            $settings['planDurations'] = [
                'basic' => (int)($curDurations['basic'] ?? 0),
                'pro' => $nextProDays,
                'max' => $nextMaxDays,
                'demo' => 0,
            ];
            if (is_array($settings['plans']['pro'] ?? null)) {
                $settings['plans']['pro']['days'] = $nextProDays;
            }
            if (is_array($settings['plans']['max'] ?? null)) {
                $settings['plans']['max']['days'] = $nextMaxDays;
            }
            Db::writeSettings($settings);
            Http::json([
                'success' => true,
                'message' => 'Reseller plan rates, credits & durations updated successfully',
                'rates' => $settings['resellerRates'],
                'planCredits' => $settings['resellerPlanCredits'],
                'planDurations' => $settings['planDurations'],
            ]);
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
        $liveUsersCount = \App\Database::isEnabled()
            ? Db::countUsersByResellerMysql((int)$reseller['id'])
            : count(Db::getUserIndexes(Db::readUsers())['byResellerId'][(string)$reseller['id']] ?? []);
        Http::json([
            'success' => true,
            'token' => $token,
            'reseller' => [
                'id' => $reseller['id'],
                'name' => $reseller['name'],
                'email' => $reseller['email'],
                'allowedUsers' => $reseller['allowedUsers'] ?? 50,
                'usersCreated' => $liveUsersCount,
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
                'proRate' => $r['proRate'] ?? null,
                'maxRate' => $r['maxRate'] ?? null,
                'proCredits' => $r['proCredits'] ?? null,
                'maxCredits' => $r['maxCredits'] ?? null,
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
                'planCredits' => $billingInfo['planCredits'],
                'planDurations' => Helpers::getPlanDurations($settings),
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
        $planCredits = Helpers::getResellerPlanCredits($currentR ?: $reqReseller, $settings);
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
            $uPlan = strtolower((string)($u['plan'] ?? 'pro'));
            $isDemoU = Helpers::isDemoUser($u);
            $maxDevs = Helpers::getUserMaxDevices($u);
            $defTot = $isDemoU
                ? (int)($planCredits['demo'] ?? 2000)
                : (($uPlan === 'max' || $uPlan === 'ultra') ? $planCredits['max'] : $planCredits['pro']);
            $expTsU = !empty($u['planExpiresAt']) ? (strtotime((string)$u['planExpiresAt']) ?: 0) : 0;
            $remSecU = $expTsU > time() ? ($expTsU - time()) : 0;
            if ($isDemoU && $remSecU <= 0 && $effectiveStatus === 'active') {
                $effectiveStatus = 'expired';
            }

            $formatted[] = [
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
                'billedDevices' => $bu ? (int)($bu['billedDevices'] ?? $maxDevs) : max($maxDevs, (int)($u['billedDevices'] ?? 1)),
                'planRate' => $bu ? $bu['planRate'] : 0,
                'basePlanRate' => $bu ? ($bu['basePlanRate'] ?? $bu['planRate']) : 0,
                'credits' => (int)($u['credits'] ?? 0),
                'creditsTotal' => (int)($u['creditsTotal'] ?? 0) ?: $defTot,
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

        // Always show newest users first so newly created users are immediately visible at the top
        usort($formatted, static function ($a, $b) {
            $ta = !empty($a['createdAt']) ? (strtotime((string)$a['createdAt']) ?: 0) : 0;
            $tb = !empty($b['createdAt']) ? (strtotime((string)$b['createdAt']) ?: 0) : 0;
            if ($ta !== $tb) {
                return $tb <=> $ta;
            }
            return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
        });

        $allowed = (int)($reqReseller['allowedUsers'] ?? 50);
        Http::json([
            'success' => true,
            'users' => $formatted,
            'total' => count($formatted),
            'allowedUsers' => $allowed,
            'remainingUsers' => max(0, $allowed - count($formatted)),
            'isOverdue' => (bool)($billing && $billing['isOverdue']),
            'balanceDue' => $billing ? $billing['balanceDue'] : 0,
            'planCredits' => $planCredits,
            'planDurations' => Helpers::getPlanDurations($settings),
            'rates' => $billing ? $billing['rates'] : null,
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

        $cleanEmail = Db::sanitizeEmail($email);
        $cleanPass = Helpers::normalizePasswordForEmail($password, $cleanEmail);
        $useMysqlFast = \App\Database::isEnabled();
        $resellerId = (int)$reqReseller['id'];

        $settings = Db::readSettings();
        $freshResellerMeta = $useMysqlFast ? (Db::findResellerByIdMysql($resellerId) ?: $reqReseller) : $reqReseller;
        $planCredits = Helpers::getResellerPlanCredits($freshResellerMeta, $settings);

        $rawPlanLow = strtolower(trim((string)$plan));
        $isDemoPlan = ($rawPlanLow === 'demo');
        $chosenPlan = $isDemoPlan ? 'demo' : ($rawPlanLow === 'max' ? 'max' : 'pro');
        $demoHours = null;
        $nowIso = Db::isoNow();
        if ($isDemoPlan) {
            $rawDemoHrs = $body['demoHours'] ?? ($body['hours'] ?? 2);
            // Cap reseller demo duration at 2 hours max (default 2h) to prevent abuse
            $demoHours = is_numeric($rawDemoHrs) ? max(0.0005, min(2.0, (float)$rawDemoHrs)) : 2.0;
            $demoSeconds = max(2, (int)round($demoHours * 3600));
            $durationDays = 1;
            $expiresAt = Db::isoNow($demoSeconds);
            $assignedCredits = (int)($planCredits['demo'] ?? 2000);
            $maxDevices = 1;
            $billedDevices = 1;
        } else {
            $durationDays = Helpers::getPlanDurationDays($chosenPlan, $settings);
            $expiresAt = Db::isoNow($durationDays * 86400);
            $assignedCredits = $chosenPlan === 'max' ? (int)$planCredits['max'] : (int)$planCredits['pro'];
            $rawMaxDev = $body['maxDevices'] ?? ($body['devices'] ?? 1);
            $maxDevices = is_numeric($rawMaxDev) ? max(1, min(5, (int)$rawMaxDev)) : 1;
            $billedDevices = $maxDevices;
        }
        $localPart = explode('@', $cleanEmail)[0] ?? 'user';
        $cleanUsername = $username
            ? Db::sanitizeCredential($username)
            : ($name ? Db::sanitizeCredential($name) : preg_replace('/[^a-zA-Z0-9_]/', '', $localPart));

        $allowed = (int)($reqReseller['allowedUsers'] ?? 50);

        if ($useMysqlFast) {
            $freshReseller = Db::findResellerByIdMysql($resellerId);
            if ($freshReseller && ($freshReseller['status'] ?? '') === 'suspended') {
                Http::json(['error' => 'ResellerSuspended', 'message' => 'Your reseller account has been suspended by administrator.'], 403);
            }

            $existing = Db::findUserByLoginIdentifierMysql($cleanEmail);
            if ($existing && Db::sanitizeEmail($existing['email'] ?? '') !== $cleanEmail) {
                $existing = null;
            }
            if ($existing) {
                $existingPlan = strtolower((string)($existing['plan'] ?? 'basic'));
                $isFreeOrBasic = in_array($existingPlan, ['basic', 'free', 'trial', 'starter', 'vibe'], true);
                if (!empty($existing['resellerId']) && (int)$existing['resellerId'] !== $resellerId && !$isFreeOrBasic) {
                    Http::json(['error' => 'Conflict', 'message' => 'User with this email already exists'], 409);
                }
            }

            $myUsersCount = Db::countUsersByResellerMysql($resellerId);
            if ($myUsersCount >= $allowed && (!$existing || (int)$existing['resellerId'] !== $resellerId)) {
                Http::json([
                    'error' => 'QuotaExceeded',
                    'message' => "Your account creation quota ($allowed users) is full. Please contact Flow by Pak admin to increase your quota.",
                ], 403);
            }

            Db::unmarkUserDeleted($cleanEmail);
            if ($existing) {
                $wasSameReseller = (int)($existing['resellerId'] ?? 0) === $resellerId;
                $existing['resellerId'] = $resellerId;
                $existing['resellerName'] = $reqReseller['name'];
                $existing['resellerEmail'] = $reqReseller['email'];
                $existing['name'] = $name ? Db::sanitizeCredential($name) : ($existing['name'] ?: $cleanUsername);
                $existing['username'] = $existing['username'] ?: $cleanUsername;
                $existing['email'] = $cleanEmail;
                $existing['passwordHash'] = Db::hashPassword($cleanPass);
                $existing['plainPasswordHint'] = $cleanPass;
                $existing['plan'] = $chosenPlan;
                $existing['isDemo'] = $isDemoPlan;
                $existing['demoHours'] = $demoHours;
                $existing['maxDevices'] = $maxDevices;
                $existing['billedDevices'] = $wasSameReseller ? max($billedDevices, (int)($existing['billedDevices'] ?? 1)) : $billedDevices;
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
                Db::upsertSingleUser($existing);
                if (!$wasSameReseller) {
                    \App\Database::pdo()->prepare('UPDATE resellers SET users_created = users_created + 1 WHERE id = ?')->execute([$resellerId]);
                }
                $safe = $existing;
                unset($safe['passwordHash']);
                Http::json([
                    'success' => true,
                    'message' => $isDemoPlan ? "Demo account activated for {$demoHours}h (auto-closes on expiry)!" : 'User account updated and activated immediately! (3-day grace period for payment is active)',
                    'user' => $safe,
                ], 200);
            }

            $newUser = [
                'id' => Db::nextUserIdMysql(),
                'resellerId' => $resellerId,
                'resellerName' => $reqReseller['name'],
                'resellerEmail' => $reqReseller['email'],
                'name' => $name ? Db::sanitizeCredential($name) : $cleanUsername,
                'username' => $cleanUsername,
                'email' => $cleanEmail,
                'passwordHash' => Db::hashPassword($cleanPass),
                'plainPasswordHint' => $cleanPass,
                'plan' => $chosenPlan,
                'isDemo' => $isDemoPlan,
                'demoHours' => $demoHours,
                'maxDevices' => $maxDevices,
                'billedDevices' => $billedDevices,
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

            Db::unmarkUserDeleted($cleanEmail);
            Db::upsertSingleUser($newUser);
            \App\Database::pdo()->prepare('UPDATE resellers SET users_created = users_created + 1 WHERE id = ?')->execute([$resellerId]);

            $safe = $newUser;
            unset($safe['passwordHash']);
            Http::json([
                'success' => true,
                'message' => $isDemoPlan ? "Demo account created for {$demoHours}h (auto-closes on expiry)!" : 'User account created and activated immediately! (3-day grace period for payment is active)',
                'user' => $safe,
            ], 201);
        }

        $users = Db::readUsers();
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
        if ($myUsersCount >= $allowed && ($existingIdx === -1 || ($users[$existingIdx]['resellerId'] ?? null) !== $reqReseller['id'])) {
            Http::json([
                'error' => 'QuotaExceeded',
                'message' => "Your account creation quota ($allowed users) is full. Please contact Flow by Pak admin to increase your quota.",
            ], 403);
        }

        $resellers = Db::readResellers();
        foreach ($resellers as $r) {
            if (($r['id'] ?? null) === $reqReseller['id'] && ($r['status'] ?? '') === 'suspended') {
                Http::json(['error' => 'ResellerSuspended', 'message' => 'Your reseller account has been suspended by administrator.'], 403);
            }
        }

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
            $existing['isDemo'] = $isDemoPlan;
            $existing['demoHours'] = $demoHours;
            $existing['maxDevices'] = $maxDevices;
            $existing['billedDevices'] = $wasSameReseller ? max($billedDevices, (int)($existing['billedDevices'] ?? 1)) : $billedDevices;
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
                'message' => $isDemoPlan ? "Demo account activated for {$demoHours}h (auto-closes on expiry)!" : 'User account updated and activated immediately! (3-day grace period for payment is active)',
                'user' => $safe,
            ], 200);
        }

        $newUser = [
            'id' => Db::nextUserId($users),
            'resellerId' => $reqReseller['id'],
            'resellerName' => $reqReseller['name'],
            'resellerEmail' => $reqReseller['email'],
            'name' => $name ? Db::sanitizeCredential($name) : $cleanUsername,
            'username' => $cleanUsername,
            'email' => $cleanEmail,
            'passwordHash' => Db::hashPassword($cleanPass),
            'plainPasswordHint' => $cleanPass,
            'plan' => $chosenPlan,
            'isDemo' => $isDemoPlan,
            'demoHours' => $demoHours,
            'maxDevices' => $maxDevices,
            'billedDevices' => $billedDevices,
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

        Db::unmarkUserDeleted($cleanEmail);
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
            'message' => $isDemoPlan ? "Demo account created for {$demoHours}h (auto-closes on expiry)!" : 'User account created and activated immediately! (3-day grace period for payment is active)',
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
        $wasDemoBefore = Helpers::isDemoUser($user);
        if (!empty($body['plan'])) {
            $reqPlanLow = strtolower(trim((string)$body['plan']));
            if ($reqPlanLow === 'demo' && !$wasDemoBefore) {
                Http::json(['error' => 'Forbidden', 'message' => 'Paid Pro/Max accounts cannot be downgraded to Demo.'], 403);
            }
            $nextPlan = $reqPlanLow === 'demo' ? 'demo' : ($reqPlanLow === 'max' ? 'max' : 'pro');
            $prevPlan = $wasDemoBefore ? 'demo' : (strtolower((string)($user['plan'] ?? 'pro')) === 'max' ? 'max' : 'pro');
            if ($nextPlan !== $prevPlan) {
                $curSettings = Db::readSettings();
                $planCr = Helpers::getResellerPlanCredits($reqReseller, $curSettings);
                $planDur = Helpers::getPlanDurations($curSettings);
                $proCr = (int)$planCr['pro'];
                $maxCr = (int)$planCr['max'];
                $proDays = max(1, (int)($planDur['pro'] ?? 20));
                $maxDays = max(1, (int)($planDur['max'] ?? 30));
                $nowTs = time();
                $curExpTs = !empty($user['planExpiresAt']) ? (strtotime((string)$user['planExpiresAt']) ?: 0) : 0;

                if ($prevPlan === 'demo' && ($nextPlan === 'pro' || $nextPlan === 'max')) {
                    // Upgrading a Demo trial account to full paid Pro/Max plan
                    $upgDays = $nextPlan === 'max' ? $maxDays : $proDays;
                    $upgCr = $nextPlan === 'max' ? $maxCr : $proCr;
                    $user['isDemo'] = false;
                    $user['demoHours'] = null;
                    $user['status'] = 'active';
                    $user['credits'] = $upgCr;
                    $user['creditsTotal'] = $upgCr;
                    $user['planExpiresAt'] = Db::isoNow($upgDays * 86400);
                    $user['daysRemaining'] = $upgDays;
                    $user['requestedDays'] = $upgDays;
                    $user['createdAt'] = Db::isoNow();
                    $wasDemoBefore = false;
                } elseif ($nextPlan === 'max') {
                    // Upgrading Pro -> Max: top up credits AND add difference days (capped strictly at maxDays)
                    $deltaCr = max(0, $maxCr - $proCr);
                    $user['credits'] = min($maxCr, max(0, (int)($user['credits'] ?? 0)) + $deltaCr);
                    $user['creditsTotal'] = $maxCr;

                    $prevDays = (int)($user['requestedDays'] ?? $proDays);
                    if ($prevDays <= 0 || $prevDays > $maxDays) {
                        $prevDays = $proDays;
                    }
                    $deltaDays = max(0, $maxDays - $prevDays);
                    if ($curExpTs > $nowTs) {
                        $newExpTs = min($curExpTs + ($deltaDays * 86400), $nowTs + ($maxDays * 86400));
                    } else {
                        $newExpTs = $nowTs + ($maxDays * 86400);
                        $user['status'] = 'active';
                    }
                    $remDays = max(1, (int)ceil(($newExpTs - $nowTs) / 86400));
                    $user['planExpiresAt'] = Db::isoNow(max(86400, $newExpTs - $nowTs));
                    $user['daysRemaining'] = $remDays;
                    $user['requestedDays'] = $maxDays;
                } elseif ($nextPlan === 'pro') {
                    // Switching Max -> Pro: adjust credits AND validity days to Pro plan duration
                    $user['credits'] = min($proCr, max(0, (int)($user['credits'] ?? 0)));
                    $user['creditsTotal'] = $proCr;

                    $prevDays = (int)($user['requestedDays'] ?? $maxDays);
                    if ($prevDays <= 0) {
                        $prevDays = $maxDays;
                    }
                    $deltaDays = max(0, $prevDays - $proDays);
                    if ($curExpTs > $nowTs) {
                        $newExpTs = max($nowTs + 86400, min($curExpTs - ($deltaDays * 86400), $nowTs + ($proDays * 86400)));
                        $remDays = max(1, (int)ceil(($newExpTs - $nowTs) / 86400));
                        $user['planExpiresAt'] = Db::isoNow(max(86400, $newExpTs - $nowTs));
                        $user['daysRemaining'] = $remDays;
                    }
                    $user['requestedDays'] = $proDays;
                }
            }
            $user['plan'] = $nextPlan;
        }
        if (!$wasDemoBefore && isset($body['maxDevices']) && is_numeric($body['maxDevices'])) {
            $nextMaxDev = max(1, min(5, (int)$body['maxDevices']));
            $user['maxDevices'] = $nextMaxDev;
            $user['billedDevices'] = max($nextMaxDev, (int)($user['billedDevices'] ?? 1));
            $curSess = Helpers::normalizeActiveDeviceSessions($user);
            if (count($curSess) > $nextMaxDev) {
                usort($curSess, static fn(array $a, array $b): int => ((int)($a['lastSeenAt'] ?? 0)) <=> ((int)($b['lastSeenAt'] ?? 0)));
                $user['activeDeviceSessions'] = array_values(array_slice($curSess, -$nextMaxDev));
            }
        }
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
        Helpers::purgeActiveClientSessionsForUser($targetUser['id'], (string)($targetUser['email'] ?? ''));
        if ($useMysqlFast) {
            Db::deleteSingleUserMysql($userId);
            $remainingCount = Db::countUsersByResellerMysql((int)$reqReseller['id']);
            if ($remainingCount === 0) {
                \App\Database::pdo()->prepare('UPDATE resellers SET users_created = 0, deleted_charges = 0 WHERE id = ?')->execute([(int)$reqReseller['id']]);
            } else {
                \App\Database::pdo()->prepare('UPDATE resellers SET users_created = ? WHERE id = ?')->execute([$remainingCount, (int)$reqReseller['id']]);
            }
            Http::json(['success' => true, 'message' => 'User deleted and access revoked successfully']);
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
        $users = \App\Database::isEnabled() ? Db::readAllResellerUsersMysql() : Db::readUsers();
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
                'paidUsersCount' => $info['paidUsersCount'],
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

    private static function adminResellerUsers(int $id): void
    {
        $useMysqlFast = \App\Database::isEnabled();
        $reseller = $useMysqlFast ? Db::findResellerByIdMysql($id) : null;
        if (!$reseller && !$useMysqlFast) {
            foreach (Db::readResellers() as $r) {
                if ((int)($r['id'] ?? 0) === $id) {
                    $reseller = $r;
                    break;
                }
            }
        }
        if (!$reseller) {
            Http::json(['error' => 'NotFound', 'message' => 'Reseller not found'], 404);
        }

        $scopedUsers = $useMysqlFast ? Db::readUsersByResellerMysql($id) : Db::readUsers();
        $settings = Db::readSettings();
        $billing = Helpers::getResellerBillingInfo($reseller, $scopedUsers, $settings);

        // Attach credits & deviceSwitchCount from raw user objects and sort newest-first
        $rawMap = [];
        foreach ($scopedUsers as $su) {
            if ((int)($su['resellerId'] ?? 0) === $id) {
                $rawMap[(int)$su['id']] = $su;
            }
        }
        $usersOut = [];
        foreach ($billing['myUsers'] as $bu) {
            $raw = $rawMap[(int)($bu['id'] ?? 0)] ?? [];
            $bu['credits'] = (int)($raw['credits'] ?? 0);
            $bu['creditsTotal'] = (int)($raw['creditsTotal'] ?? 0);
            $bu['deviceSwitchCount'] = (int)($raw['deviceSwitchCount'] ?? 0);
            $bu['isLocked'] = !empty($raw['isLocked']);
            $usersOut[] = $bu;
        }
        usort($usersOut, static function ($a, $b) {
            $ta = !empty($a['createdAt']) ? (strtotime((string)$a['createdAt']) ?: 0) : 0;
            $tb = !empty($b['createdAt']) ? (strtotime((string)$b['createdAt']) ?: 0) : 0;
            if ($ta !== $tb) return $tb <=> $ta;
            return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
        });

        Http::json([
            'success' => true,
            'reseller' => [
                'id' => (int)$reseller['id'],
                'name' => (string)($reseller['name'] ?? ''),
                'email' => (string)($reseller['email'] ?? ''),
                'whatsapp' => (string)($reseller['whatsapp'] ?? ''),
                'allowedUsers' => (int)($reseller['allowedUsers'] ?? 50),
                'status' => $billing['isSuspended'] ? 'suspended' : ($reseller['status'] ?? 'active'),
                'rawStatus' => (string)($reseller['status'] ?? 'active'),
            ],
            'billing' => [
                'rates' => $billing['rates'],
                'usersCharges' => $billing['usersCharges'],
                'deletedCharges' => $billing['deletedCharges'],
                'rawTotalCharges' => $billing['rawTotalCharges'],
                'totalDiscount' => $billing['totalDiscount'],
                'totalCharges' => $billing['totalCharges'],
                'totalPaid' => $billing['totalPaid'],
                'balanceDue' => $billing['balanceDue'],
                'isOverdue' => $billing['isOverdue'],
                'paidUsersCount' => $billing['paidUsersCount'],
                'graceUsersCount' => $billing['graceUsersCount'],
                'overdueUsersCount' => $billing['overdueUsersCount'],
                'usersCount' => $billing['usersCount'],
            ],
            'users' => $usersOut,
            'total' => count($usersOut),
        ]);
    }

    private static function adminClearDues(int $id): void
    {
        $useMysqlFast = \App\Database::isEnabled();
        $resellers = Db::readResellers();
        foreach ($resellers as $i => $r) {
            if ((int)($r['id'] ?? 0) === $id) {
                $scopedUsers = $useMysqlFast ? Db::readUsersByResellerMysql($id) : Db::readUsers();
                $settings = Db::readSettings();
                $billing = Helpers::getResellerBillingInfo($r, $scopedUsers, $settings);

                // Always ceil & never reduce totalPaid below current paid so balanceDue is strictly 0
                $clearedTotalPaid = (int)ceil(max((float)($r['totalPaid'] ?? 0), (float)$billing['totalCharges']));
                $clearedAmount = max(0, $clearedTotalPaid - (float)($r['totalPaid'] ?? 0));

                $resellers[$i]['totalPaid'] = $clearedTotalPaid;
                if (($resellers[$i]['status'] ?? '') === 'suspended') {
                    $resellers[$i]['status'] = 'active';
                }
                $resellers[$i]['duesClearedAt'] = Db::isoNow();

                if ($useMysqlFast) {
                    $pdo = \App\Database::pdo();
                    $pdo->prepare('UPDATE resellers SET total_paid = ?, status = ? WHERE id = ?')
                        ->execute([$clearedTotalPaid, (string)$resellers[$i]['status'], $id]);
                    // Reactivate any user of this reseller stuck in unpaid_suspended status
                    $pdo->prepare("UPDATE users SET status = 'active' WHERE reseller_id = ? AND status = 'unpaid_suspended'")
                        ->execute([$id]);
                    // Mark any pending payment submissions for this reseller as approved so reseller sees clean history
                    $pdo->prepare("UPDATE reseller_payments SET status = 'approved', approved_at = ?, reviewed_by = 'Admin (Clear Dues)' WHERE reseller_id = ? AND status = 'pending'")
                        ->execute([Db::isoNow(), $id]);
                    Db::clearCaches();
                } else {
                    Db::writeResellers($resellers);
                    $usersChanged = false;
                    foreach ($scopedUsers as $ui => $u) {
                        if ((int)($u['resellerId'] ?? 0) === $id && ($u['status'] ?? '') === 'unpaid_suspended') {
                            $scopedUsers[$ui]['status'] = 'active';
                            $usersChanged = true;
                        }
                    }
                    if ($usersChanged) {
                        Db::writeUsers($scopedUsers, ['immediate' => true]);
                    }
                    $payments = Db::readResellerPayments();
                    $payChanged = false;
                    foreach ($payments as $pi => $p) {
                        if ((int)($p['resellerId'] ?? 0) === $id && ($p['status'] ?? '') === 'pending') {
                            $payments[$pi]['status'] = 'approved';
                            $payments[$pi]['approvedAt'] = Db::isoNow();
                            $payments[$pi]['reviewedBy'] = 'Admin (Clear Dues)';
                            $payChanged = true;
                        }
                    }
                    if ($payChanged) {
                        Db::writeResellerPayments($payments);
                    }
                }

                $updatedBilling = Helpers::getResellerBillingInfo($resellers[$i], $scopedUsers, $settings);
                $label = $r['name'] ?: $r['email'];
                $fmtCleared = number_format($clearedAmount);
                $safeR = $resellers[$i];
                unset($safeR['passwordHash']);
                Http::json([
                    'success' => true,
                    'message' => $clearedAmount > 0
                        ? "All dues (Rs. $fmtCleared) cleared for $label! Balance is now Rs. 0 and all users are PAID & ACTIVE."
                        : "Dues verified & cleared for $label! Balance is Rs. 0 and all users are PAID & ACTIVE.",
                    'clearedAmount' => $clearedAmount,
                    'reseller' => $safeR,
                    'billing' => [
                        'totalCharges' => $updatedBilling['totalCharges'],
                        'totalPaid' => $updatedBilling['totalPaid'],
                        'balanceDue' => $updatedBilling['balanceDue'],
                        'isOverdue' => $updatedBilling['isOverdue'],
                        'paidUsersCount' => $updatedBilling['paidUsersCount'],
                        'overdueUsersCount' => $updatedBilling['overdueUsersCount'],
                    ],
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
        $useMysqlFast = \App\Database::isEnabled();
        $resellers = Db::readResellers();
        foreach ($resellers as $i => $r) {
            if ((int)($r['id'] ?? 0) === $id) {
                $newTotalPaid = (int)round((float)($r['totalPaid'] ?? 0) + $payAmount);
                $resellers[$i]['totalPaid'] = $newTotalPaid;
                if (($resellers[$i]['status'] ?? '') === 'suspended') {
                    $resellers[$i]['status'] = 'active';
                }
                if ($useMysqlFast) {
                    \App\Database::pdo()->prepare('UPDATE resellers SET total_paid = ?, status = ? WHERE id = ?')
                        ->execute([$newTotalPaid, (string)$resellers[$i]['status'], $id]);
                    Db::clearCaches();
                } else {
                    Db::writeResellers($resellers);
                }

                $scopedUsers = $useMysqlFast ? Db::readUsersByResellerMysql($id) : Db::readUsers();
                $updatedBilling = Helpers::getResellerBillingInfo($resellers[$i], $scopedUsers, Db::readSettings());
                $label = $r['name'] ?: $r['email'];
                $fmt = number_format($payAmount);
                $remFmt = number_format($updatedBilling['balanceDue']);
                $safeR = $resellers[$i];
                unset($safeR['passwordHash']);
                Http::json([
                    'success' => true,
                    'message' => "Payment of Rs. $fmt recorded for $label! Remaining balance: Rs. $remFmt.",
                    'reseller' => $safeR,
                    'billing' => [
                        'totalCharges' => $updatedBilling['totalCharges'],
                        'totalPaid' => $updatedBilling['totalPaid'],
                        'balanceDue' => $updatedBilling['balanceDue'],
                        'isOverdue' => $updatedBilling['isOverdue'],
                    ],
                ]);
            }
        }
        Http::json(['error' => 'NotFound', 'message' => 'Reseller not found'], 404);
    }

    private static function adminResellersList(): void
    {
        $resellers = Db::readResellers();
        $users = \App\Database::isEnabled() ? Db::readAllResellerUsersMysql() : Db::readUsers();
        $settings = Db::readSettings();

        $formatted = [];
        foreach ($resellers as $r) {
            // Single source of truth: identical billing formula to /api/reseller/me & adminClearDues
            $info = Helpers::getResellerBillingInfo($r, $users, $settings);
            $allowed = (int)($r['allowedUsers'] ?? 50);

            $formatted[] = [
                'id' => $r['id'],
                'name' => $r['name'],
                'email' => $r['email'],
                'plainPasswordHint' => $r['plainPasswordHint'] ?? null,
                'allowedUsers' => $allowed,
                'usersCreated' => $info['usersCount'],
                'remainingUsers' => max(0, $allowed - $info['usersCount']),
                'paidUsersCount' => $info['paidUsersCount'],
                'graceUsersCount' => $info['graceUsersCount'],
                'overdueUsersCount' => $info['overdueUsersCount'],
                'isOverdue' => $info['isOverdue'],
                'creditsQuota' => $r['creditsQuota'] ?? 1000000,
                'whatsapp' => $r['whatsapp'] ?? '',
                'status' => $info['isSuspended'] ? 'suspended' : ($r['status'] ?? 'active'),
                'rawStatus' => $r['status'] ?? 'active',
                'createdAt' => $r['createdAt'] ?? null,
                'discount' => $info['discountAmount'],
                'discountPercent' => $info['discountPercent'],
                'proRate' => $r['proRate'] ?? null,
                'maxRate' => $r['maxRate'] ?? null,
                'proCredits' => $r['proCredits'] ?? null,
                'maxCredits' => $r['maxCredits'] ?? null,
                'planCredits' => $info['planCredits'],
                'rates' => $info['rates'],
                'rawTotalCharges' => $info['rawTotalCharges'],
                'totalDiscount' => $info['totalDiscount'],
                'totalCharges' => $info['totalCharges'],
                'totalPaid' => $info['totalPaid'],
                'balanceDue' => $info['balanceDue'],
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
            'proCredits' => (isset($body['proCredits']) && $body['proCredits'] !== '' && $body['proCredits'] !== null) ? max(100, (int)$body['proCredits']) : null,
            'maxCredits' => (isset($body['maxCredits']) && $body['maxCredits'] !== '' && $body['maxCredits'] !== null) ? max(100, (int)$body['maxCredits']) : null,
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
                if (array_key_exists('proCredits', $body)) $r['proCredits'] = ($body['proCredits'] === null || $body['proCredits'] === '') ? null : max(100, (int)$body['proCredits']);
                if (array_key_exists('maxCredits', $body)) $r['maxCredits'] = ($body['maxCredits'] === null || $body['maxCredits'] === '') ? null : max(100, (int)$body['maxCredits']);
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
