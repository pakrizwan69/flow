<?php
declare(strict_types=1);

namespace App\Routes;

use App\Db;
use App\Helpers;
use App\Http;
use App\Mailer;

final class AuthRoutes
{
    public static function dispatch(string $method, string $path): bool
    {
        if ($method === 'POST' && $path === '/admin/login') {
            self::adminLogin();
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/auth/login', '/user/login'], true)) {
            self::userLogin($path);
            return true;
        }
        if ($method === 'GET' && in_array($path, ['/auth/me', '/user/me'], true)) {
            self::userMe($path);
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/user/change-password', '/auth/change-password'], true)) {
            self::userChangePassword($path);
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/user/change-email', '/auth/change-email'], true)) {
            self::userChangeEmail($path);
            return true;
        }
        if (($method === 'DELETE' && $path === '/user/account') || ($method === 'POST' && $path === '/user/delete-account')) {
            self::userDeleteAccount($path);
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/user/deduct-credits', '/credits/deduct'], true)) {
            self::deductCredits($path);
            return true;
        }
        if ($method === 'GET' && in_array($path, ['/maintenance', '/system/maintenance'], true)) {
            header('Cache-Control: public, max-age=45');
            $settings = Db::readSettings();
            $m = $settings['maintenance'] ?? ['enabled' => false];
            Http::json([
                'success' => true,
                'enabled' => !empty($m['enabled']),
                'message' => $m['message'] ?? 'Extension is unavailable for some hours due to maintenance.',
            ]);
        }
        if ($method === 'POST' && in_array($path, ['/auth/register', '/user/register'], true)) {
            self::register();
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/auth/verify-signup', '/user/verify-signup'], true)) {
            self::verifySignup();
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/auth/resend-signup-code', '/user/resend-signup-code'], true)) {
            self::resendSignupCode();
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/auth/forgot-password', '/user/forgot-password'], true)) {
            self::forgotPassword();
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/auth/reset-password', '/user/reset-password'], true)) {
            self::resetPassword();
            return true;
        }
        if ($method === 'POST' && in_array($path, ['/auth/logout', '/user/logout'], true)) {
            self::revokeTokenSession();
            Helpers::clearActiveClientSession();
            Http::json(['success' => true, 'message' => 'Logged out']);
        }
        if ($method === 'GET' && in_array($path, ['/stats/public', '/public-stats'], true)) {
            header('Cache-Control: public, max-age=60, stale-while-revalidate=300');
            $baseEpoch = 1772323200; // 1772323200000 ms
            $hoursPassed = max(0, (int)floor((time() - $baseEpoch) / 3600));
            $totalUsers = 14250 + $hoursPassed * 120;
            $totalVideos = 185400 + $hoursPassed * 520;
            $totalImages = 342800 + $hoursPassed * 980;
            Http::json([
                'totalUsers' => $totalUsers,
                'totalVideos' => $totalVideos,
                'totalImages' => $totalImages,
                'totalGenerations' => $totalVideos + $totalImages,
            ]);
        }
        if (in_array($path, ['/user/accept-onboarding', '/accept-onboarding'], true)) {
            $token = Helpers::getBearerToken();
            if ($token) {
                $payload = Db::verifyToken($token);
                if ($payload) {
                    if (\App\Database::isEnabled()) {
                        $u = Db::findUserByTokenMysql(
                            isset($payload['userId']) ? (string)$payload['userId'] : null,
                            Db::sanitizeEmail($payload['email'] ?? '')
                        );
                        if ($u && empty($u['onboardingAccepted'])) {
                            $u['onboardingAccepted'] = true;
                            $u['updatedAt'] = Db::isoNow();
                            Db::upsertSingleUser($u);
                        }
                    } else {
                        $users = Db::readUsers();
                        $u = Helpers::findUserByTokenPayload($users, $payload);
                        if ($u && empty($u['onboardingAccepted'])) {
                            foreach ($users as $i => $item) {
                                if (($item['id'] ?? null) === ($u['id'] ?? null)) {
                                    $users[$i]['onboardingAccepted'] = true;
                                    Db::writeUsers($users);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
            Http::json(['success' => true, 'onboardingAccepted' => true]);
        }
        if (in_array($path, ['/user/dashboard-heartbeat', '/dashboard-heartbeat'], true)) {
            Http::json(['ok' => true, 'timestamp' => (int)round(microtime(true) * 1000)]);
        }
        if ($method === 'GET' && in_array($path, ['/user/subscription-state', '/subscription-state'], true)) {
            self::subscriptionState();
            return true;
        }
        if ($method === 'GET' && in_array($path, ['/user/free-quota', '/free-quota'], true)) {
            if (Helpers::isRequestFlowLocked()) {
                Http::json([
                    'enforced' => true, 'exhausted' => true, 'used' => 5, 'limit' => 5,
                    'remaining' => 0, 'windowHours' => 6, 'resetAt' => null,
                    'creditsUsed' => 0, 'creditsLimit' => 0, 'creditsRemaining' => 0,
                    'remainingCredits' => 0, 'totalCredits' => 0, 'isPro' => false,
                ]);
            }
            Http::json(['remainingCredits' => 25000, 'totalCredits' => 25000, 'isPro' => true]);
        }
        if ($path === '/user/consume-flow-credit') {
            if (Helpers::isRequestFlowLocked()) {
                Http::json([
                    'ok' => false, 'quotaExhausted' => true, 'creditsRemaining' => 0,
                    'error' => 'BasicPlanLocked', 'message' => 'AI Tools are locked on Basic Plan. Please upgrade your plan.',
                ], 403);
            }
            Http::json(['success' => true, 'remainingCredits' => 24900]);
        }
        if ($path === '/user/orders') {
            Http::json(['success' => true, 'orders' => []]);
        }
        if ($path === '/user/card-waitlist') {
            Http::json(['success' => true, 'message' => 'Added to waitlist']);
        }
        if ($path === '/user/refund-requests') {
            Http::json(['success' => true, 'message' => 'Refund request received']);
        }
        if ($path === '/payments/checkout-disabled') {
            Http::json(['disabled' => false]);
        }
        if ($path === '/support/tickets') {
            Http::json(['success' => true, 'message' => 'Ticket received']);
        }
        if ($path === '/flow-browser/downloads') {
            Http::json(['success' => true, 'downloads' => []]);
        }
        if ($path === '/flow-browser/upload') {
            Http::json(['success' => true, 'url' => '/placeholder.png']);
        }
        if ($path === '/flow-auto-login/pairing') {
            if (Helpers::isRequestFlowLocked()) {
                Http::json(['success' => false, 'paired' => false, 'error' => 'not_available', 'message' => 'Flow Auto Login requires an active paid plan.'], 403);
            }
            Http::json(['success' => true, 'paired' => true, 'token' => 'demo-pair-token']);
        }
        if ($path === '/teams/mine') {
            Http::json(['success' => true, 'team' => null]);
        }
        if ($path === '/teams/invite/email') {
            Http::json(['success' => true]);
        }
        if (in_array($path, ['/auth/verify-email', '/auth/verify-existing-email'], true)) {
            Http::json(['success' => true, 'verified' => true]);
        }
        if (in_array($path, ['/auth/resend-verification', '/auth/resend-verification-auth'], true)) {
            Http::json(['success' => true, 'message' => 'Verification code sent']);
        }
        if (in_array($path, ['/auth/verify-email-change', '/auth/update-profile'], true)) {
            Http::json(['success' => true]);
        }
        if ($path === '/auth/signout') {
            self::revokeTokenSession();
            Helpers::clearActiveClientSession();
            Http::json(['success' => true]);
        }
        if ($method === 'GET' && $path === '/user/notifications') {
            header('Cache-Control: private, max-age=180');
            Http::json(['success' => true, 'notifications' => [], 'unread' => 0]);
        }
        if ($path === '/user/notifications/read') {
            Http::json(['success' => true, 'unread' => 0]);
        }
        if ($method === 'GET' && $path === '/announcement') {
            header('Cache-Control: public, max-age=60');
            $settings = Db::readSettings();
            $enabled = !empty($settings['broadcast']['enabled']);
            Http::json([
                'enabled' => $enabled,
                'text' => $enabled ? (string)($settings['broadcast']['message'] ?? '') : '',
                'announcement' => $enabled ? ($settings['broadcast'] ?? null) : null,
            ]);
        }

        return false;
    }

    private static function revokeTokenSession(): void
    {
        $token = Helpers::getBearerToken();
        if (!$token) {
            return;
        }
        $payload = Db::verifyToken($token);
        if (!$payload) {
            return;
        }
        if (\App\Database::isEnabled()) {
            $target = Db::findUserByTokenMysql(
                isset($payload['userId']) ? (string)$payload['userId'] : null,
                Db::sanitizeEmail($payload['email'] ?? '')
            );
            if ($target) {
                $target['sessionVersion'] = (int)($target['sessionVersion'] ?? 1) + 1;
                $target['singleDeviceEnforced'] = true;
                $target['sessionLoggedOut'] = true;
                $target['updatedAt'] = Db::isoNow();
                Db::upsertSingleUser($target);
            }
            return;
        }
        $users = Db::readUsers(true);
        $target = Helpers::findUserByTokenPayload($users, $payload);
        if (!$target) {
            return;
        }
        foreach ($users as $i => $u) {
            if (($u['id'] ?? null) === ($target['id'] ?? null) && Db::sanitizeEmail($u['email'] ?? '') === Db::sanitizeEmail($target['email'] ?? '')) {
                $users[$i]['sessionVersion'] = (int)($u['sessionVersion'] ?? 1) + 1;
                $users[$i]['singleDeviceEnforced'] = true;
                $users[$i]['sessionLoggedOut'] = true;
                $users[$i]['updatedAt'] = Db::isoNow();
                Db::writeUsers($users, ['immediate' => true]);
                break;
            }
        }
    }

    private static function adminLogin(): void
    {
        $body = Http::body();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        if (!$email || !$password) {
            Http::json(['error' => 'ValidationError', 'message' => 'Email and password are required'], 400);
        }
        $cleanEmail = strtolower(trim((string)$email));
        $cleanPassword = trim((string)$password);
        $admin = Db::verifyAdminCredentials($cleanEmail, $cleanPassword);
        if ($admin) {
            $token = Db::generateToken(['email' => $admin['email'], 'role' => 'admin', 'name' => $admin['name'] ?? 'Flow by Pak Administrator']);
            Http::json([
                'success' => true,
                'token' => $token,
                'admin' => $admin,
            ]);
        }
        Http::json(['error' => 'InvalidCredentials', 'message' => 'Invalid admin email or password'], 401);
    }

    private static function userLogin(string $path): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate');
        $settings = Db::readSettings();
        if (!empty($settings['maintenance']['enabled'])) {
            Http::json([
                'success' => false,
                'maintenance' => true,
                'error' => $settings['maintenance']['message'] ?? 'Platform is currently undergoing scheduled maintenance.',
            ], 503);
        }

        $body = Http::body();
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $deviceId = $body['deviceId'] ?? '';
        $fingerprint = $body['fingerprint'] ?? '';

        $origin = Http::header('origin');
        $isExtLoginRequest = ($path === '/user/login')
            || str_starts_with($origin, 'chrome-extension://')
            || !empty($body['clientApp']);

        $loginClientApp = strtolower(trim((string)($body['clientApp'] ?? '')));
        if ($isExtLoginRequest && $loginClientApp !== 'social_pak_ext' && Helpers::getPakFlowExtVersion() < Helpers::MIN_PAKFLOW_EXT_VERSION) {
            Http::json([
                'success' => false,
                'outdatedExtension' => true,
                'forceLogout' => true,
                'error' => 'Extension Outdated',
                'message' => 'Download latest extension (v1.5) from your dashboard.',
            ], 403);
        }

        if (!$email || !$password) {
            Http::json(['error' => 'ValidationError', 'message' => 'Email and password are required'], 400);
        }

        $useMysqlFast = \App\Database::isEnabled();
        $users = null;
        $index = -1;
        if ($useMysqlFast) {
            $user = Db::findUserByLoginIdentifierMysql($email);
            if (!$user) {
                Http::json(['error' => 'InvalidCredentials', 'message' => 'Invalid email or password'], 401);
            }
        } else {
            $users = Db::readUsers(true);
            $index = Helpers::findUserIndexByLoginIdentifier($users, $email);
            if ($index === -1) {
                Http::json(['error' => 'InvalidCredentials', 'message' => 'Invalid email or password'], 401);
            }
            $user = $users[$index];
        }
        $passwordValid = Db::verifyPassword(
            $password,
            $user['passwordHash'] ?? '',
            (string)($user['plainPasswordHint'] ?? ''),
            (string)($user['email'] ?? '')
        );
        if (!$passwordValid) {
            Http::json(['error' => 'InvalidCredentials', 'message' => 'Invalid email or password'], 401);
        }

        if ($isExtLoginRequest && Helpers::isUserPlanExpired($user)) {
            Http::json([
                'success' => false,
                'expired' => true,
                'forceLogout' => true,
                'error' => 'Plan Expired',
                'message' => 'Your access plan has expired. Please renew your plan.',
            ], 403);
        }

        if ($isExtLoginRequest && Helpers::isPlanBasicOrLocked($user['plan'] ?? 'basic')) {
            Helpers::recordActiveClientSession($user);
            Http::json([
                'success' => false,
                'flowLocked' => true,
                'error' => 'Basic Plan Locked',
                'message' => 'Basic Plan par extension nahi chalegi. Please upgrade your plan to Plus or Max.',
            ], 403);
        }

        if (!empty($user['isLocked']) || ($user['status'] ?? '') === 'locked') {
            $user['isLocked'] = false;
            if (($user['status'] ?? '') === 'locked') {
                $user['status'] = 'active';
            }
            $user['lockReason'] = null;
            $user['lockedAt'] = null;
        }

        if (($user['status'] ?? '') === 'suspended') {
            Http::json([
                'error' => 'AccountSuspended',
                'message' => 'Your account has been suspended. Please contact Flow by Pak support.',
            ], 403);
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
                $scopedUsers = $useMysqlFast ? Db::readUsersByResellerMysql($rid) : $users;
                $billing = Helpers::getResellerBillingInfo($r, $scopedUsers, $settings);
                $billedUser = null;
                foreach ($billing['myUsers'] as $bu) {
                    if (($bu['id'] ?? null) === ($user['id'] ?? null)) {
                        $billedUser = $bu;
                        break;
                    }
                }
                if ($billedUser && !empty($billedUser['isUnpaidOverdue'])) {
                    Http::json([
                        'error' => 'ResellerUnpaid',
                        'message' => 'your reseller has not paid',
                        'detailedMessage' => 'Your account is temporarily suspended because your reseller has an unpaid balance after the 3-day grace period. Please contact your reseller.',
                    ], 403);
                }
                if (($r['status'] ?? '') === 'suspended' && (!$billedUser || empty($billedUser['isPaid']))) {
                    Http::json([
                        'error' => 'ResellerSuspended',
                        'message' => 'your reseller has not paid',
                        'detailedMessage' => 'Your account is temporarily suspended because your reseller account is inactive. Please contact your reseller.',
                    ], 403);
                }
            }
        }

        $currentDeviceId = trim((string)($deviceId ?: $fingerprint ?: Http::header('x-device-id') ?: Http::header('user-agent') ?: 'device_unknown'));
        $isSocialExtLogin = $isExtLoginRequest && $loginClientApp === 'social_pak_ext';
        $isFlowExtLogin = $isExtLoginRequest && !$isSocialExtLogin;
        $currentIp = Helpers::getClientIp();
        $previousIp = trim((string)($user['lastLoginIp'] ?? ''));
        $isSameIp = ($previousIp !== '' && $currentIp !== '' && $previousIp === $currentIp);

        $user['deviceSwitchCount'] = (int)($user['deviceSwitchCount'] ?? 0);
        $user['sessionVersion'] = (int)($user['sessionVersion'] ?? 1);

        $flowExtId = $user['activeFlowExtDeviceId'] ?? ($user['activeExtDeviceId'] ?? null);
        $socialExtId = $user['activeSocialExtDeviceId'] ?? null;
        $webId = $user['activeWebDeviceId'] ?? null;

        // If web portal login detects an active session on another device, ask user to 1-click logout existing session
        $forceLogoutExisting = !empty($body['forceLogoutExisting']);
        $shouldPromptExisting = (!$isExtLoginRequest || !empty($body['promptExistingSession'])) && !$forceLogoutExisting;
        if ($shouldPromptExisting && empty($user['sessionLoggedOut']) && !empty($user['lastLoginAt'])) {
            $lastTs = strtotime((string)$user['lastLoginAt']) ?: 0;
            $recentSession = ($lastTs > 0 && (time() - $lastTs) < 86400);
            $otherDeviceActive = ($webId && $webId !== $currentDeviceId)
                || ($flowExtId && $flowExtId !== $currentDeviceId && !$isSameIp);
            if ($recentSession && $otherDeviceActive) {
                Http::json([
                    'success' => false,
                    'existingSession' => true,
                    'error' => 'ExistingSessionActive',
                    'message' => 'Your account is already signed in on another device. Click below to logout the existing session and sign in here.',
                ], 409);
            }
        }

        if ($forceLogoutExisting) {
            $user['sessionVersion'] += 1;
            $user['deviceSwitchCount'] += 1;
            $user['activeWebDeviceId'] = $currentDeviceId;
            $user['activeFlowExtDeviceId'] = null;
            $user['activeExtDeviceId'] = null;
            $user['activeDeviceId'] = $currentDeviceId;
        } elseif ($isSocialExtLogin) {
            if ($socialExtId && $socialExtId !== $currentDeviceId && !$isSameIp) {
                $user['sessionVersion'] += 1;
                $user['deviceSwitchCount'] += 1;
            }
            $user['activeSocialExtDeviceId'] = $currentDeviceId;
            $user['activeDeviceId'] = $currentDeviceId;
        } elseif ($isFlowExtLogin) {
            if ($flowExtId && $flowExtId !== $currentDeviceId) {
                $user['sessionVersion'] += 1;
                $user['deviceSwitchCount'] += 1;
            }
            $user['activeFlowExtDeviceId'] = $currentDeviceId;
            $user['activeExtDeviceId'] = $currentDeviceId;
            $user['activeDeviceId'] = $currentDeviceId;
        } else {
            // Same-IP web portal login updates web device without kicking out the user's Flow extension on the same PC
            if ($webId && $webId !== $currentDeviceId && !$isSameIp) {
                $user['sessionVersion'] += 1;
                $user['deviceSwitchCount'] += 1;
            }
            $user['activeWebDeviceId'] = $currentDeviceId;
            $user['activeDeviceId'] = $currentDeviceId;
        }

        $user['singleDeviceEnforced'] = true;
        $user['sessionLoggedOut'] = false;
        $user['lastLoginAt'] = Db::isoNow();
        $user['lastLoginIp'] = $currentIp;

        if ($useMysqlFast) {
            Db::upsertSingleUser($user);
        } else {
            $users[$index] = $user;
            Db::writeUsers($users, ['immediate' => true]);
        }
        Helpers::recordActiveClientSession($user);

        $safeUser = Helpers::formatSafeUser($user);
        $supportWa = Helpers::resolveUserSupportWhatsapp($user, $settings);
        $safeUser['supportWhatsapp'] = $supportWa;
        $accessPolicy = array_merge(
            Db::defaultSettings()['accessPolicy'],
            is_array($settings['accessPolicy'] ?? null) ? $settings['accessPolicy'] : []
        );
        $assignedServer = Helpers::computeUserAssignedSlot($user, Db::readSlotsByRange(1, 10), $settings);

        $token = Db::generateToken([
            'userId' => $user['id'],
            'email' => $user['email'],
            'plan' => $safeUser['plan'],
            'sessionVersion' => $user['sessionVersion'],
        ]);

        Http::json([
            'success' => true,
            'token' => $token,
            'user' => $safeUser,
            'supportWhatsapp' => $supportWa,
            'accessPolicy' => $accessPolicy,
            'assignedServer' => $assignedServer,
            'daysLeft' => $safeUser['daysLeft'],
            'deviceSwitchCount' => $user['deviceSwitchCount'],
            'deviceSwitchesRemaining' => 999999,
        ]);
    }

    private static function userMe(string $path): void
    {
        $user = Helpers::requireUser($path);
        Helpers::recordActiveClientSession($user);

        $origin = Http::header('origin');
        $isExtMeRequest = ($path === '/user/me') || str_starts_with($origin, 'chrome-extension://');
        if ($isExtMeRequest && Helpers::isPlanBasicOrLocked($user['plan'] ?? 'basic')) {
            Http::json([
                'success' => false,
                'authenticated' => false,
                'locked' => true,
                'flowLocked' => true,
                'error' => 'Basic Plan par extension nahi chalegi. Please upgrade your plan.',
                'message' => 'Basic Plan par extension nahi chalegi. Please upgrade your plan.',
            ], 403);
        }

        $settings = Db::readSettings();
        $safeUser = Helpers::formatSafeUser($user);
        $supportWa = Helpers::resolveUserSupportWhatsapp($user, $settings);
        $safeUser['supportWhatsapp'] = $supportWa;
        $accessPolicy = array_merge(
            Db::defaultSettings()['accessPolicy'],
            is_array($settings['accessPolicy'] ?? null) ? $settings['accessPolicy'] : []
        );
        $assignedServer = Helpers::computeUserAssignedSlot($user, Db::readSlotsByRange(1, 10), $settings);

        $refreshedToken = Db::generateToken([
            'userId' => $user['id'],
            'email' => $user['email'],
            'plan' => $safeUser['plan'],
            'sessionVersion' => (int)($user['sessionVersion'] ?? 1),
        ]);

        Http::json(array_merge([
            'success' => true,
            'authenticated' => true,
            'token' => $refreshedToken,
            'user' => $safeUser,
            'supportWhatsapp' => $supportWa,
            'accessPolicy' => $accessPolicy,
            'assignedServer' => $assignedServer,
        ], $safeUser));
    }

    private static function deductCredits(string $path): void
    {
        $reqUser = Helpers::requireUser($path);
        $body = Http::body();
        $rawAmount = (float)($body['amount'] ?? 60);
        $amount = ($rawAmount > 0 && is_finite($rawAmount)) ? min(5000, (int)round($rawAmount)) : 60;

        if (\App\Database::isEnabled()) {
            $user = Db::findUserByTokenMysql((string)($reqUser['id'] ?? ''), Db::sanitizeEmail($reqUser['email'] ?? ''));
            if (!$user) {
                Http::json(['success' => false, 'error' => 'UserNotFound'], 404);
            }
            $current = isset($user['credits']) ? (int)$user['credits'] : 45000;
            $total = (isset($user['creditsTotal']) && (int)$user['creditsTotal'] > 0) ? (int)$user['creditsTotal'] : 45000;
            if ($current <= 0) {
                Http::json([
                    'success' => false, 'error' => 'InsufficientCredits',
                    'message' => 'You have 0 credits remaining.', 'credits' => 0, 'creditsTotal' => $total,
                ], 403);
            }
            $nextCredits = max(0, $current - $amount);
            $user['credits'] = $nextCredits;
            $user['creditsTotal'] = $total;
            $user['updatedAt'] = Db::isoNow();
            Db::upsertSingleUser($user);
            Http::json([
                'success' => true, 'deducted' => min($current, $amount),
                'credits' => $nextCredits, 'creditsTotal' => $total,
            ]);
        }

        $users = Db::readUsers();
        $idx = -1;
        foreach ($users as $i => $u) {
            if (($u['id'] ?? null) === ($reqUser['id'] ?? null) || Db::sanitizeEmail($u['email'] ?? '') === Db::sanitizeEmail($reqUser['email'] ?? '')) {
                $idx = $i;
                break;
            }
        }
        if ($idx === -1) {
            Http::json(['success' => false, 'error' => 'UserNotFound'], 404);
        }

        $current = isset($users[$idx]['credits']) ? (int)$users[$idx]['credits'] : 45000;
        $total = (isset($users[$idx]['creditsTotal']) && (int)$users[$idx]['creditsTotal'] > 0) ? (int)$users[$idx]['creditsTotal'] : 45000;
        if ($current <= 0) {
            Http::json([
                'success' => false,
                'error' => 'InsufficientCredits',
                'message' => 'You have 0 credits remaining.',
                'credits' => 0,
                'creditsTotal' => $total,
            ], 403);
        }

        $nextCredits = max(0, $current - $amount);
        $users[$idx]['credits'] = $nextCredits;
        $users[$idx]['creditsTotal'] = $total;
        $users[$idx]['updatedAt'] = Db::isoNow();
        Db::writeUsers($users);

        Http::json([
            'success' => true,
            'deducted' => min($current, $amount),
            'credits' => $nextCredits,
            'creditsTotal' => $total,
        ]);
    }

    private static function register(): void
    {
        $settings = Db::readSettings();
        if (($settings['allowPublicRegistration'] ?? true) === false) {
            Http::json([
                'error' => 'RegistrationDisabled',
                'message' => 'Public registration is currently disabled by administrator. Please contact an admin or authorized reseller.',
            ], 403);
        }

        $body = Http::body();
        $name = $body['name'] ?? '';
        $username = $body['username'] ?? '';
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';
        $deviceId = $body['deviceId'] ?? '';
        $fingerprint = $body['fingerprint'] ?? '';

        if (!$email || !$password) {
            Http::json(['error' => 'ValidationError', 'message' => 'Email and password are required'], 400);
        }

        $cleanEmail = Db::sanitizeEmail($email);
        if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            Http::json(['error' => 'ValidationError', 'message' => 'Please enter a valid email address'], 400);
        }
        $cleanPass = Helpers::normalizePasswordForEmail($password, $cleanEmail);
        $users = Db::readUsers();

        foreach ($users as $u) {
            if (Db::sanitizeEmail($u['email'] ?? '') === $cleanEmail) {
                Http::json(['error' => 'Conflict', 'message' => 'An account with this email already exists'], 409);
            }
        }

        $localPart = explode('@', $cleanEmail)[0] ?? 'user';
        $rawUsername = trim((string)($username ?: $name ?: $localPart));
        $cleanUsername = preg_replace('/[^a-zA-Z0-9_.-]/', '', $rawUsername) ?: (preg_replace('/[^a-zA-Z0-9_]/', '', $localPart) ?: 'user');
        $initialDeviceId = trim((string)($deviceId ?: $fingerprint ?: Http::header('x-device-id') ?: Http::header('user-agent') ?: 'device_unknown'));

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = time();
        Db::savePendingSignup($cleanEmail, [
            'name' => $name ? Db::sanitizeCredential($name) : $rawUsername,
            'username' => $cleanUsername,
            'email' => $cleanEmail,
            'passwordHash' => Db::hashPassword($cleanPass),
            'plainPasswordHint' => $cleanPass,
            'deviceId' => $initialDeviceId,
            'code' => $code,
            'expiresAt' => $now + 900,
        ]);

        $sent = Mailer::sendSignupVerificationCode($cleanEmail, $code);
        if (!$sent) {
            Http::json([
                'success' => false,
                'error' => 'EmailFailed',
                'message' => 'Could not send verification email. Please check your email address or try again later.',
            ], 502);
        }

        Http::json([
            'success' => true,
            'requiresVerification' => true,
            'email' => $cleanEmail,
            'message' => "Verification code sent to $cleanEmail. Please enter the 6-digit code to create your account.",
        ]);
    }

    private static function pendingSignupsFile(): string
    {
        return Db::dataDir() . DIRECTORY_SEPARATOR . 'pending_signups.json';
    }

    private static function verifySignup(): void
    {
        $body = Http::body();
        $cleanEmail = Db::sanitizeEmail($body['email'] ?? '');
        $code = trim((string)($body['code'] ?? ''));

        if ($cleanEmail === '' || $code === '') {
            Http::json(['success' => false, 'message' => 'Email and 6-digit verification code are required'], 400);
        }

        $entry = Db::getPendingSignup($cleanEmail);

        if (!$entry || ($entry['expiresAt'] ?? 0) < time() || !hash_equals((string)($entry['code'] ?? ''), $code)) {
            Http::json(['success' => false, 'message' => 'Invalid or expired verification code'], 400);
        }

        $users = Db::readUsers(true);
        foreach ($users as $u) {
            if (Db::sanitizeEmail($u['email'] ?? '') === $cleanEmail) {
                Db::deletePendingSignup($cleanEmail);
                Http::json(['success' => false, 'message' => 'An account with this email already exists'], 409);
            }
        }

        Db::unmarkUserDeleted($cleanEmail);
        $trialDays = 30;
        $nowIso = Db::isoNow();
        $planExpiresAt = Db::isoNow($trialDays * 86400);

        $maxId = 0;
        foreach ($users as $u) {
            $uid = (int)($u['id'] ?? 0);
            if ($uid > $maxId) {
                $maxId = $uid;
            }
        }

        $newUser = [
            'id' => $maxId + 1,
            'name' => $entry['name'],
            'username' => $entry['username'],
            'email' => $cleanEmail,
            'passwordHash' => $entry['passwordHash'],
            'plainPasswordHint' => $entry['plainPasswordHint'],
            'plan' => 'basic',
            'rawPlan' => 'basic',
            'planLabel' => 'Basic Plan',
            'credits' => 0,
            'creditsTotal' => 0,
            'planExpiresAt' => $planExpiresAt,
            'daysRemaining' => $trialDays,
            'status' => 'active',
            'onboardingAccepted' => true,
            'emailVerified' => true,
            'sessionVersion' => 1,
            'singleDeviceEnforced' => true,
            'deviceSwitchCount' => 0,
            'activeDeviceId' => $entry['deviceId'],
            'activeWebDeviceId' => $entry['deviceId'],
            'createdAt' => $nowIso,
            'updatedAt' => $nowIso,
        ];

        Db::unmarkUserDeleted($cleanEmail, $newUser['id']);
        $users[] = $newUser;
        Db::writeUsers($users, ['immediate' => true]);
        Helpers::recordActiveClientSession($newUser);

        Db::deletePendingSignup($cleanEmail);

        @Mailer::sendWelcomeEmail($cleanEmail, (string)$newUser['name']);

        $token = Db::generateToken(['userId' => $newUser['id'], 'email' => $newUser['email'], 'plan' => 'basic', 'sessionVersion' => 1]);
        $safeUser = Helpers::formatSafeUser($newUser);

        Http::json([
            'success' => true,
            'message' => 'Email verified and account created successfully!',
            'token' => $token,
            'userId' => $newUser['id'],
            'email' => $newUser['email'],
            'user' => $safeUser,
        ], 201);
    }

    private static function resendSignupCode(): void
    {
        $body = Http::body();
        $cleanEmail = Db::sanitizeEmail($body['email'] ?? '');
        if ($cleanEmail === '') {
            Http::json(['success' => false, 'message' => 'Email is required'], 400);
        }

        $entry = Db::getPendingSignup($cleanEmail);
        if (!$entry) {
            Http::json(['success' => false, 'message' => 'No pending signup found for this email. Please fill the signup form again.'], 404);
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $entry['code'] = $code;
        $entry['expiresAt'] = time() + 900;
        Db::savePendingSignup($cleanEmail, $entry);

        $sent = Mailer::sendSignupVerificationCode($cleanEmail, $code);
        if (!$sent) {
            Http::json(['success' => false, 'message' => 'Could not resend verification email.'], 502);
        }

        Http::json(['success' => true, 'message' => "New 6-digit verification code sent to $cleanEmail"]);
    }

    private static function subscriptionState(): void
    {
        $token = Helpers::getBearerToken();
        if ($token) {
            $payload = Db::verifyToken($token);
            if ($payload) {
                $u = \App\Database::isEnabled()
                    ? Db::findUserByTokenMysql(
                        isset($payload['userId']) ? (string)$payload['userId'] : null,
                        Db::sanitizeEmail($payload['email'] ?? '')
                    )
                    : Helpers::findUserByTokenPayload(Db::readUsers(), $payload);
                if ($u) {
                    $currentPlan = Helpers::normalizeClientPlan($u['plan'] ?? 'basic', (string)($u['email'] ?? ''));
                    $isHeavyUser = ($currentPlan === 'heavy');
                    $days = Db::calculateDaysRemaining($u['planExpiresAt'] ?? null);
                    $planExpiresAt = $u['planExpiresAt'] ?? Db::isoNow(max(1, $days ?: 30) * 86400);
                    $expTs = !empty($u['planExpiresAt']) ? strtotime((string)$u['planExpiresAt']) : false;
                    $isExpired = ($expTs !== false && $expTs <= time());
                    Http::json([
                        'planActive' => !$isExpired && ($u['status'] ?? 'active') !== 'suspended',
                        'status' => $isExpired ? 'expired' : ($u['status'] ?? 'active'),
                        'currentPlan' => $currentPlan,
                        'rawPlan' => $isHeavyUser ? 'heavy' : ($u['plan'] ?? 'basic'),
                        'planLabel' => Helpers::formatClientPlanLabel($currentPlan),
                        'isHeavy' => $isHeavyUser,
                        'heavyCredits' => $isHeavyUser ? ((int)($u['heavyCredits'] ?? 6200) ?: 6200) : 0,
                        'ttsCredits' => (int)($u['ttsCredits'] ?? 45000) ?: 45000,
                        'planExpiresAt' => $planExpiresAt,
                        'daysRemaining' => $days,
                        'pendingOrder' => null,
                    ]);
                }
            }
        }
        Http::json(['planActive' => false, 'status' => 'inactive', 'currentPlan' => null, 'planExpiresAt' => null, 'pendingOrder' => null]);
    }

    private static function userChangePassword(string $path): void
    {
        $reqUser = Helpers::requireUser($path);
        $body = Http::body();
        $currentPassword = (string)($body['currentPassword'] ?? '');
        $newPassword = (string)($body['newPassword'] ?? '');
        if (strlen(trim($newPassword)) < 4) {
            Http::json(['success' => false, 'message' => 'New password must be at least 4 characters'], 400);
        }

        if (\App\Database::isEnabled()) {
            $user = Db::findUserByTokenMysql((string)($reqUser['id'] ?? ''), Db::sanitizeEmail($reqUser['email'] ?? ''));
            if (!$user) {
                Http::json(['success' => false, 'message' => 'User not found'], 404);
            }
            if ($currentPassword !== '' && !Db::verifyPassword($currentPassword, $user['passwordHash'] ?? '', (string)($user['plainPasswordHint'] ?? ''), (string)($user['email'] ?? ''))) {
                Http::json(['success' => false, 'message' => 'Current password is incorrect'], 400);
            }
            $cleanPass = Helpers::normalizePasswordForEmail($newPassword, (string)($user['email'] ?? ''));
            $user['passwordHash'] = Db::hashPassword($cleanPass);
            $user['plainPasswordHint'] = $cleanPass;
            $user['updatedAt'] = Db::isoNow();
            Db::upsertSingleUser($user);
            Http::json(['success' => true, 'message' => 'Password updated successfully']);
        }

        $users = Db::readUsers(true);
        $idx = -1;
        foreach ($users as $i => $u) {
            if (($u['id'] ?? null) === ($reqUser['id'] ?? null) || Db::sanitizeEmail($u['email'] ?? '') === Db::sanitizeEmail($reqUser['email'] ?? '')) {
                $idx = $i;
                break;
            }
        }
        if ($idx === -1) {
            Http::json(['success' => false, 'message' => 'User not found'], 404);
        }

        $user = $users[$idx];
        if ($currentPassword !== '' && !Db::verifyPassword($currentPassword, $user['passwordHash'] ?? '', (string)($user['plainPasswordHint'] ?? ''), (string)($user['email'] ?? ''))) {
            Http::json(['success' => false, 'message' => 'Current password is incorrect'], 400);
        }

        $cleanPass = Helpers::normalizePasswordForEmail($newPassword, (string)($user['email'] ?? ''));
        $users[$idx]['passwordHash'] = Db::hashPassword($cleanPass);
        $users[$idx]['plainPasswordHint'] = $cleanPass;
        $users[$idx]['updatedAt'] = Db::isoNow();
        Db::writeUsers($users, ['immediate' => true]);

        Http::json(['success' => true, 'message' => 'Password updated successfully']);
    }

    private static function userChangeEmail(string $path): void
    {
        $reqUser = Helpers::requireUser($path);
        $body = Http::body();
        $newEmail = Db::sanitizeEmail($body['newEmail'] ?? ($body['email'] ?? ''));
        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            Http::json(['success' => false, 'message' => 'Please enter a valid email address'], 400);
        }

        if (\App\Database::isEnabled()) {
            $user = Db::findUserByIdMysql((int)($reqUser['id'] ?? 0));
            if (!$user) {
                Http::json(['success' => false, 'message' => 'User not found'], 404);
            }
            $conflict = Db::findUserByTokenMysql(null, $newEmail);
            if ($conflict && (int)($conflict['id'] ?? 0) !== (int)$user['id']) {
                Http::json(['success' => false, 'message' => 'This email is already in use by another account'], 409);
            }
            Db::unmarkUserDeleted($newEmail, $user['id']);
            $user['email'] = $newEmail;
            $user['updatedAt'] = Db::isoNow();
            Db::upsertSingleUser($user);
            $safeUser = Helpers::formatSafeUser($user);
            $newToken = Db::generateToken([
                'userId' => $user['id'],
                'email' => $newEmail,
                'plan' => $safeUser['plan'],
                'sessionVersion' => (int)($user['sessionVersion'] ?? 1),
            ]);
            Http::json([
                'success' => true,
                'message' => 'Email updated successfully',
                'token' => $newToken,
                'user' => $safeUser,
            ]);
        }

        $users = Db::readUsers(true);
        $idx = -1;
        foreach ($users as $i => $u) {
            if (($u['id'] ?? null) === ($reqUser['id'] ?? null)) {
                $idx = $i;
                continue;
            }
            if (Db::sanitizeEmail($u['email'] ?? '') === $newEmail) {
                Http::json(['success' => false, 'message' => 'This email is already in use by another account'], 409);
            }
        }
        if ($idx === -1) {
            Http::json(['success' => false, 'message' => 'User not found'], 404);
        }

        Db::unmarkUserDeleted($newEmail, $users[$idx]['id'] ?? null);
        $users[$idx]['email'] = $newEmail;
        $users[$idx]['updatedAt'] = Db::isoNow();
        Db::writeUsers($users, ['immediate' => true]);

        $safeUser = Helpers::formatSafeUser($users[$idx]);
        $newToken = Db::generateToken([
            'userId' => $users[$idx]['id'],
            'email' => $newEmail,
            'plan' => $safeUser['plan'],
            'sessionVersion' => (int)($users[$idx]['sessionVersion'] ?? 1),
        ]);

        Http::json([
            'success' => true,
            'message' => 'Email updated successfully',
            'token' => $newToken,
            'user' => $safeUser,
        ]);
    }

    private static function userDeleteAccount(string $path): void
    {
        $reqUser = Helpers::requireUser($path);
        if (\App\Database::isEnabled()) {
            $user = Db::findUserByTokenMysql((string)($reqUser['id'] ?? ''), Db::sanitizeEmail($reqUser['email'] ?? ''));
            if (!$user) {
                Http::json(['success' => false, 'message' => 'User not found'], 404);
            }
            Db::markUserDeleted($user['id'] ?? null, (string)($user['email'] ?? ''));
            Db::deleteSingleUserMysql((int)$user['id']);
            Helpers::clearActiveClientSession();
            Http::json(['success' => true, 'message' => 'Your account has been deleted']);
        }
        $users = Db::readUsers(true);
        $found = false;
        $filtered = [];
        foreach ($users as $u) {
            if (($u['id'] ?? null) === ($reqUser['id'] ?? null) && Db::sanitizeEmail($u['email'] ?? '') === Db::sanitizeEmail($reqUser['email'] ?? '')) {
                $found = true;
                Db::markUserDeleted($u['id'] ?? null, (string)($u['email'] ?? ''));
                continue;
            }
            $filtered[] = $u;
        }
        if (!$found) {
            Http::json(['success' => false, 'message' => 'User not found'], 404);
        }
        Db::writeUsers($filtered, ['immediate' => true]);
        Helpers::clearActiveClientSession();
        Http::json(['success' => true, 'message' => 'Your account has been deleted']);
    }

    private static function resetsFile(): string
    {
        return Db::dataDir() . DIRECTORY_SEPARATOR . 'password_resets.json';
    }

    private static function forgotPassword(): void
    {
        $body = Http::body();
        $email = Db::sanitizeEmail($body['email'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Http::json(['success' => false, 'message' => 'Please enter a valid email address'], 400);
        }

        if (\App\Database::isEnabled()) {
            $matchedUser = Db::findUserByLoginIdentifierMysql($email);
            if (!$matchedUser) {
                Http::json(['success' => false, 'message' => 'No account found with this email address'], 404);
            }
            $targetEmail = Db::sanitizeEmail($matchedUser['email'] ?? $email);
        } else {
            $users = Db::readUsers(true);
            $index = Helpers::findUserIndexByLoginIdentifier($users, $email);
            if ($index === -1) {
                Http::json(['success' => false, 'message' => 'No account found with this email address'], 404);
            }
            $targetEmail = Db::sanitizeEmail($users[$index]['email'] ?? $email);
        }
        $token = bin2hex(random_bytes(24));

        Db::savePasswordReset($token, $targetEmail, time() + 1800);

        $host = $_SERVER['HTTP_HOST'] ?? 'flowbypak.com';
        $isLocal = str_starts_with($host, '127.0.0.1') || str_starts_with($host, 'localhost');
        $baseUrl = $isLocal ? ('http://' . $host) : 'https://flowbypak.com';
        $resetUrl = $baseUrl . '/reset-password?token=' . $token;

        $sent = Mailer::sendPasswordResetLink($targetEmail, $resetUrl);
        if (!$sent) {
            Http::json(['success' => false, 'message' => 'Could not send reset email. Please check SMTP connectivity or try again later.'], 502);
        }

        Http::json(['success' => true, 'message' => "A password reset link has been sent to $targetEmail"]);
    }

    private static function resetPassword(): void
    {
        $body = Http::body();
        $token = trim((string)($body['token'] ?? ''));
        $newPassword = (string)($body['newPassword'] ?? '');

        if ($token === '' || strlen(trim($newPassword)) < 4) {
            Http::json(['success' => false, 'message' => 'Valid reset token and new password (min 4 chars) are required'], 400);
        }

        $entry = Db::getPasswordReset($token);
        if (!$entry || ($entry['expiresAt'] ?? 0) < time() || empty($entry['email'])) {
            Http::json(['success' => false, 'message' => 'Invalid or expired reset link. Please request a new one.'], 400);
        }

        $email = Db::sanitizeEmail($entry['email']);
        if (\App\Database::isEnabled()) {
            $user = Db::findUserByLoginIdentifierMysql($email);
            if (!$user) {
                Http::json(['success' => false, 'message' => 'Account not found'], 404);
            }
            $cleanPass = Helpers::normalizePasswordForEmail($newPassword, (string)($user['email'] ?? ''));
            $user['passwordHash'] = Db::hashPassword($cleanPass);
            $user['plainPasswordHint'] = $cleanPass;
            $user['sessionVersion'] = (int)($user['sessionVersion'] ?? 1) + 1;
            $user['updatedAt'] = Db::isoNow();
            Db::upsertSingleUser($user);
            Db::deletePasswordReset($token);
            Http::json(['success' => true, 'message' => 'Password reset successfully! You can now sign in.']);
        }
        $users = Db::readUsers(true);
        $index = Helpers::findUserIndexByLoginIdentifier($users, $email);
        if ($index === -1) {
            Http::json(['success' => false, 'message' => 'Account not found'], 404);
        }

        $cleanPass = Helpers::normalizePasswordForEmail($newPassword, (string)($users[$index]['email'] ?? ''));
        $users[$index]['passwordHash'] = Db::hashPassword($cleanPass);
        $users[$index]['plainPasswordHint'] = $cleanPass;
        $users[$index]['sessionVersion'] = (int)($users[$index]['sessionVersion'] ?? 1) + 1;
        $users[$index]['updatedAt'] = Db::isoNow();
        Db::writeUsers($users, ['immediate' => true]);

        Db::deletePasswordReset($token);

        Http::json(['success' => true, 'message' => 'Password reset successfully! You can now sign in.']);
    }
}
