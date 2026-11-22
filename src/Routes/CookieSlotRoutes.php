<?php
declare(strict_types=1);

namespace App\Routes;

use App\Db;
use App\Helpers;
use App\Http;
use App\PakTts;

final class CookieSlotRoutes
{
    private const TOOL_KEY_MAP = [
        'chatgpt' => 'chatgpt',
        'grok' => 'supergrok',
        'supergrok' => 'supergrok',
        'meta' => 'meta_ai',
        'meta-ai' => 'meta_ai',
        'meta_ai' => 'meta_ai',
        'muse' => 'muse_ai',
        'muse-ai' => 'muse_ai',
        'muse_ai' => 'muse_ai',
    ];

    public static function dispatch(string $method, string $path): bool
    {
        if ($method === 'GET' && $path === '/admin/tool-cookies') {
            Helpers::requireAdmin();
            Http::json(['success' => true, 'disabled' => true, 'toolCookies' => (object)[]]);
        }
        if ($method === 'POST' && preg_match('#^/admin/tool-cookies/([^/]+)$#', $path, $m)) {
            $admin = Helpers::requireAdmin();
            self::saveToolCookies($m[1], $admin);
            return true;
        }
        if ($method === 'DELETE' && preg_match('#^/admin/tool-cookies/([^/]+)$#', $path, $m)) {
            Helpers::requireAdmin();
            $rawKey = strtolower(trim($m[1]));
            $toolKey = self::TOOL_KEY_MAP[$rawKey] ?? $rawKey;
            $toolCookies = Db::readToolCookies();
            if (isset($toolCookies[$toolKey])) {
                $toolCookies[$toolKey]['cookies'] = [];
                $toolCookies[$toolKey]['cookieCount'] = 0;
                $toolCookies[$toolKey]['status'] = 'EMPTY';
                $toolCookies[$toolKey]['updatedAt'] = null;
                Db::writeToolCookies($toolCookies);
            }
            Http::json(['success' => true, 'message' => "Cleared cookies for $toolKey", 'toolCookies' => $toolCookies]);
        }

        if ($method === 'GET' && $path === '/admin/slots') {
            Helpers::requireAdmin();
            Http::json(['success' => true, 'slots' => Db::readSlots()]);
        }
        if ($method === 'POST' && $path === '/admin/slots/bulk') {
            Helpers::requireAdmin();
            self::bulkSlots();
            return true;
        }
        if ($method === 'POST' && preg_match('#^/admin/slots/(\d+)/status$#', $path, $m)) {
            Helpers::requireAdmin();
            self::toggleSlotStatus((int)$m[1]);
            return true;
        }
        if ($method === 'POST' && preg_match('#^/admin/slots/(\d+)/activate$#', $path, $m)) {
            Helpers::requireAdmin();
            self::activateSlot((int)$m[1]);
            return true;
        }
        if ($method === 'POST' && preg_match('#^/admin/slots/(\d+)/rename$#', $path, $m)) {
            Helpers::requireAdmin();
            self::renameSlot((int)$m[1]);
            return true;
        }
        if ($method === 'POST' && preg_match('#^/admin/slots/([^/]+)$#', $path, $m)) {
            $admin = Helpers::requireAdmin();
            self::saveSlot($m[1], $admin);
            return true;
        }
        if ($method === 'DELETE' && preg_match('#^/admin/slots/(\d+)$#', $path, $m)) {
            Helpers::requireAdmin();
            self::clearSlot((int)$m[1]);
            return true;
        }

        if ($method === 'GET' && in_array($path, ['/cookies', '/slots', '/api/slots'], true)) {
            self::publicSlotsList();
            return true;
        }
        if ($method === 'GET' && $path === '/cookies/active') {
            self::publicActiveCookie();
            return true;
        }
        if ($method === 'GET' && in_array($path, ['/cookies/all-tools', '/cookies/tools'], true)) {
            self::publicAllTools();
            return true;
        }
        if ($method === 'GET' && preg_match('#^/cookies/tool/([^/]+)$#', $path, $m)) {
            self::publicToolCookie($m[1]);
            return true;
        }
        if ($method === 'GET' && preg_match('#^/cookies/([^/]+)$#', $path, $m)) {
            self::publicCookieById($m[1]);
            return true;
        }

        if ($method === 'GET' && $path === '/prompts') {
            Http::json(['success' => true, 'disabled' => true, 'prompts' => [], 'total' => 0]);
        }
        if ($method === 'GET' && $path === '/admin/prompts') {
            Helpers::requireAdmin();
            Http::json(['success' => true, 'disabled' => true, 'prompts' => []]);
        }
        if ($method === 'POST' && $path === '/admin/prompts') {
            Helpers::requireAdmin();
            self::adminCreatePrompt();
            return true;
        }
        if ($method === 'DELETE' && preg_match('#^/admin/prompts/([^/]+)$#', $path, $m)) {
            Helpers::requireAdmin();
            self::adminDeletePrompt($m[1]);
            return true;
        }

        if (in_array($path, ['/assistant/chat', '/assistant'], true)) {
            Http::json(['reply' => 'Contact us on flowbypak.site@gmail.com']);
        }
        if ($method === 'POST' && $path === '/pak-tts/synthesize') {
            Http::json(['success' => false, 'disabled' => true, 'error' => 'ToolDisabled', 'message' => 'Voice studio has been removed. Only Google Flow is active.'], 410);
        }

        return false;
    }

    private static function saveToolCookies(string $rawToolKey, array $admin): void
    {
        $rawKey = strtolower(trim($rawToolKey));
        $toolKey = self::TOOL_KEY_MAP[$rawKey] ?? $rawKey;
        $toolCookies = Db::readToolCookies();
        $existing = $toolCookies[$toolKey] ?? [
            'id' => $toolKey,
            'name' => strtoupper($toolKey),
            'cookieCount' => 0,
            'status' => 'EMPTY',
            'cookies' => [],
        ];

        $body = Http::body();
        $inputData = $body['cookies'] ?? ($body['data'] ?? $body);
        $parsedCookies = Db::parseCookiesInput($inputData);
        if (empty($parsedCookies)) {
            Http::json(['success' => false, 'error' => 'No valid cookies detected. Please paste JSON cookie array or Netscape format.'], 400);
        }

        $toolCookies[$toolKey] = array_merge($existing, [
            'id' => $toolKey,
            'cookies' => $parsedCookies,
            'cookieCount' => count($parsedCookies),
            'status' => 'ACTIVE',
            'updatedAt' => date('m/d/Y, H:i:s'),
            'updatedBy' => $admin['email'] ?? 'Super Admin',
        ]);
        Db::writeToolCookies($toolCookies);

        $count = count($parsedCookies);
        Http::json([
            'success' => true,
            'count' => $count,
            'message' => "{$toolCookies[$toolKey]['name']} cookies saved successfully ($count cookies).",
            'tool' => $toolCookies[$toolKey],
            'toolCookies' => $toolCookies,
        ]);
    }

    private static function saveSlot(string $rawSlotNum, array $admin): void
    {
        $slotNum = (int)$rawSlotNum;
        if ($slotNum < 1 || $slotNum > 15) {
            Http::json(['success' => false, 'error' => "Invalid slot number $rawSlotNum. Allowed: 1 to 15."], 400);
        }

        $body = Http::body();
        $inputData = $body['cookies'] ?? ($body['data'] ?? $body);
        $cookies = Db::parseCookiesInput($inputData);
        if (empty($cookies)) {
            Http::json(['success' => false, 'error' => 'Please paste a valid JSON cookie array or Netscape format.'], 400);
        }

        $defaultType = Helpers::getSlotTypeTag($slotNum);
        $existing = Db::readSlotByNumber($slotNum);
        $slotName = (!empty($body['name']) && is_string($body['name']) && trim($body['name']) !== '')
            ? trim($body['name'])
            : (!empty($existing['name']) ? (string)$existing['name'] : "Server $slotNum ($defaultType)");

        $slotPayload = [
            'slotNumber' => $slotNum,
            'name' => $slotName,
            'serverType' => $defaultType,
            'status' => 'ACTIVE',
            'cookieCount' => count($cookies),
            'updatedAt' => date('m/d/Y, H:i:s'),
            'updatedBy' => $admin['email'] ?? 'Super Admin',
            'cookies' => $cookies,
        ];

        $res = Db::upsertSingleSlot($slotPayload, false);
        $cnt = count($cookies);
        $unchanged = empty($res['changed']);
        Http::json([
            'success' => true,
            'count' => $cnt,
            'unchanged' => $unchanged,
            'versionTs' => $res['versionTs'] ?? 0,
            'message' => $unchanged
                ? "Account $slotNum cookies already up to date ($cnt cookies)."
                : "Account $slotNum cookies saved successfully ($cnt cookies).",
            'slot' => $res['slot'],
        ]);
    }

    private static function bulkSlots(): void
    {
        $body = Http::body();
        $action = $body['action'] ?? '';
        $slotNumbers = $body['slotNumbers'] ?? null;
        $slots = Db::readSlots();

        if ($action === 'enable_all') {
            $enabledCount = 0;
            foreach ($slots as $i => $s) {
                if (!empty($s['cookies'])) {
                    $slots[$i]['status'] = 'ACTIVE';
                    $enabledCount++;
                }
            }
            Db::writeSlots($slots);
            Http::json([
                'success' => true,
                'message' => "All $enabledCount loaded account slots are now ENABLED & ACTIVE simultaneously!",
                'slots' => $slots,
            ]);
        }
        if ($action === 'disable_all') {
            foreach ($slots as $i => $s) {
                if (($s['status'] ?? '') === 'ACTIVE') {
                    $slots[$i]['status'] = 'STANDBY';
                }
            }
            Db::writeSlots($slots);
            Http::json(['success' => true, 'message' => 'All account slots have been set to STANDBY.', 'slots' => $slots]);
        }
        if (is_array($slotNumbers)) {
            foreach ($slots as $i => $s) {
                if (in_array($s['slotNumber'], $slotNumbers, false) && !empty($s['cookies'])) {
                    $slots[$i]['status'] = 'ACTIVE';
                }
            }
            Db::writeSlots($slots);
            Http::json(['success' => true, 'message' => 'Selected account slots have been updated.', 'slots' => $slots]);
        }
        Http::json(['success' => false, 'error' => 'Invalid bulk action'], 400);
    }

    private static function toggleSlotStatus(int $slotNum): void
    {
        $body = Http::body();
        $slot = Db::readSlotByNumber($slotNum);
        if (!$slot) {
            Http::json(['success' => false, 'error' => "Slot $slotNum not found."], 404);
        }
        $current = $slot['status'] ?? 'EMPTY';
        if ($current === 'EMPTY' || empty($slot['cookies'])) {
            Http::json(['success' => false, 'error' => 'Cannot activate an empty slot. Please upload cookies first.'], 400);
        }
        $newStatus = $body['status'] ?? ($current === 'ACTIVE' ? 'STANDBY' : 'ACTIVE');
        $slot['status'] = $newStatus;
        Db::upsertSingleSlot($slot, true);
        $slots = Db::readSlots();
        $activeCount = count(array_filter($slots, fn($s) => ($s['status'] ?? '') === 'ACTIVE'));
        Http::json([
            'success' => true,
            'message' => "Account $slotNum is now $newStatus. ($activeCount accounts active simultaneously)",
            'slot' => $slot,
            'slots' => $slots,
        ]);
    }

    private static function activateSlot(int $slotNum): void
    {
        $body = Http::body();
        $exclusive = !empty($body['exclusive']);
        $slot = Db::readSlotByNumber($slotNum);
        if (!$slot) {
            Http::json(['success' => false, 'error' => "Slot $slotNum not found."], 404);
        }
        if (empty($slot['cookies'])) {
            Http::json(['success' => false, 'error' => 'Cannot activate an empty slot. Please upload cookies first.'], 400);
        }
        if ($exclusive) {
            $slots = Db::readSlots();
            foreach ($slots as $i => $s) {
                if ((int)($s['slotNumber'] ?? 0) === $slotNum) {
                    $slots[$i]['status'] = 'ACTIVE';
                    $slot = $slots[$i];
                } elseif (($s['status'] ?? '') === 'ACTIVE') {
                    $slots[$i]['status'] = 'STANDBY';
                }
            }
            Db::writeSlots($slots);
        } else {
            $slot['status'] = 'ACTIVE';
            Db::upsertSingleSlot($slot, true);
            $slots = Db::readSlots();
        }
        $activeCount = count(array_filter($slots, fn($s) => ($s['status'] ?? '') === 'ACTIVE'));
        Http::json([
            'success' => true,
            'message' => "Account $slotNum is now ACTIVE! ($activeCount accounts active simultaneously)",
            'slot' => $slot,
            'slots' => $slots,
        ]);
    }

    private static function renameSlot(int $slotNum): void
    {
        $body = Http::body();
        $slot = Db::readSlotByNumber($slotNum);
        if (!$slot) {
            Http::json(['success' => false, 'error' => "Slot $slotNum not found."], 404);
        }
        $name = trim((string)($body['name'] ?? ''));
        if ($name === '') {
            Http::json(['success' => false, 'error' => 'Slot name cannot be empty.'], 400);
        }
        $slot['name'] = $name;
        Db::upsertSingleSlot($slot, true);
        Http::json(['success' => true, 'message' => "Account $slotNum renamed to '$name'.", 'slot' => $slot, 'slots' => Db::readSlots()]);
    }

    private static function clearSlot(int $slotNum): void
    {
        $slot = Db::readSlotByNumber($slotNum);
        if (!$slot) {
            Http::json(['success' => false, 'error' => "Slot $slotNum not found."], 404);
        }
        $slot['cookies'] = [];
        $slot['cookieCount'] = 0;
        $slot['status'] = 'EMPTY';
        $slot['updatedAt'] = null;
        Db::upsertSingleSlot($slot, true);
        Http::json(['success' => true, 'message' => "Account $slotNum cookies cleared.", 'slot' => $slot, 'slots' => Db::readSlots()]);
    }

    private static function pickRoundRobinNanoSlot(array $slotsList): array
    {
        $nanoPool = array_values(array_filter($slotsList, fn($s) => (int)($s['slotNumber'] ?? 0) >= 11 && (int)($s['slotNumber'] ?? 0) <= 15));
        usort($nanoPool, fn($a, $b) => ((int)$a['slotNumber']) <=> ((int)$b['slotNumber']));
        $activeWithCookies = array_values(array_filter($nanoPool, fn($s) => ($s['status'] ?? '') === 'ACTIVE' && !empty($s['cookies'])));
        $candidates = !empty($activeWithCookies)
            ? $activeWithCookies
            : array_values(array_filter($nanoPool, fn($s) => !empty($s['cookies'])));
        if (empty($candidates)) {
            return ['slot' => null, 'assignedIndex' => 0];
        }
        if (\App\Database::isEnabled()) {
            $counter = (int)Db::getMetaValue('nano_rr_counter', 0);
            $assignedIndex = $counter % count($candidates);
            Db::setMetaValue('nano_rr_counter', ($counter + 1) % 1000000000);
            return ['slot' => $candidates[$assignedIndex], 'assignedIndex' => $assignedIndex];
        }
        $rrFile = Db::dataDir() . DIRECTORY_SEPARATOR . 'nano_rr_counter.txt';
        $counter = is_file($rrFile) ? (int)@file_get_contents($rrFile) : 0;
        $assignedIndex = $counter % count($candidates);
        @file_put_contents($rrFile, (string)(($counter + 1) % 1000000000), LOCK_EX);
        return ['slot' => $candidates[$assignedIndex], 'assignedIndex' => $assignedIndex];
    }

    private static function publicSlotsList(): void
    {
        $access = Helpers::resolveSlotRequestAccess(false);
        if (!empty($access['flowLocked'])) {
            Http::json([
                'success' => true, 'flowLocked' => true, 'plan' => 'basic',
                'count' => 0, 'files' => [], 'slots' => [], 'tools' => [],
                'message' => 'Extensions and AI Tools are locked on Basic Plan. Upgrade your plan to unlock.',
            ]);
        }
        $rawMode = strtolower(trim((string)Http::query('mode', '')));
        $includeCookies = in_array(strtolower((string)Http::query('includeCookies', '')), ['1', 'true', 'yes'], true);
        $isNanoMode = in_array($rawMode, ['nano', 'nano_banana', 'nanobanana'], true);
        $isAllMode = ($rawMode === 'all');

        $scopedSlots = $isAllMode
            ? Db::readSlots()
            : ($isNanoMode ? Db::readSlotsByRange(11, 15) : Db::readSlotsByRange(1, 10));

        $files = [];
        $maxVer = 0;
        foreach ($scopedSlots as $s) {
            $num = (int)$s['slotNumber'];
            $vts = (int)($s['versionTs'] ?? 0);
            if ($vts > $maxVer) {
                $maxVer = $vts;
            }
            $typeTag = Helpers::getSlotTypeTag($num);
            $fallbackName = "Server $num ($typeTag)";
            $displayName = (!empty($s['name']) && !str_starts_with((string)$s['name'], 'Account ')) ? $s['name'] : $fallbackName;
            $count = (int)($s['cookieCount'] ?? count($s['cookies'] ?? []));
            $isActive = ($s['status'] ?? '') === 'ACTIVE';
            $item = [
                'id' => "slot_$num",
                'slotNumber' => $num,
                'filename' => "account_$num.json",
                'name' => $displayName,
                'serverType' => $typeTag,
                'mode' => $num >= 11 ? 'nano_banana' : 'google_flow',
                'cookieCount' => $count,
                'status' => $s['status'] ?? 'EMPTY',
                'isActive' => $isActive,
                'hasCookies' => $count > 0,
                'updatedAt' => $s['updatedAt'] ?? null,
                'versionTs' => $vts,
            ];
            if ($includeCookies && $isActive && $count > 0 && !empty($s['cookies'])) {
                $item['cookies'] = $s['cookies'];
            }
            $files[] = $item;
        }

        $slotsVer = $maxVer > 0 ? (string)$maxVer : Db::getSlotsVersion();
        $settings = Db::readSettings();
        $assignedServer = !$isNanoMode
            ? Helpers::computeUserAssignedSlot($access['user'] ?? null, $scopedSlots, $settings)
            : null;
        $accessPolicy = array_merge(
            Db::defaultSettings()['accessPolicy'],
            is_array($settings['accessPolicy'] ?? null) ? $settings['accessPolicy'] : []
        );

        Http::json([
            'success' => true,
            'mode' => $isNanoMode ? 'nano_banana' : ($isAllMode ? 'all' : 'google_flow'),
            'slotsVersion' => $slotsVer,
            'assignedServer' => $assignedServer,
            'accessPolicy' => $accessPolicy,
            'count' => count($files),
            'files' => $files,
            'slots' => $files,
            'tools' => [],
        ]);
    }

    private static function publicActiveCookie(): void
    {
        $access = Helpers::resolveSlotRequestAccess(true);
        if (!empty($access['forcedLogout'])) {
            Http::json($access['forcedLogout'], 401);
        }
        if (!empty($access['flowLocked'])) {
            Http::json([
                'success' => true, 'flowLocked' => true, 'plan' => 'basic',
                'cookieCount' => 0, 'cookies' => [], 'data' => [],
                'message' => 'Google Flow AI is locked on Basic Plan. Upgrade to access Flow AI.',
                'activeSlots' => [],
            ]);
        }

        $rawMode = strtolower(trim((string)Http::query('mode', '')));
        $requestedSlot = Http::query('slot') ?? Http::query('id') ?? Http::query('slotNumber');
        $rotate = Http::query('rotate');
        $forceRotate = ($rotate === '1' || $rotate === 'true' || strtolower((string)$requestedSlot) === 'auto');
        $digits = preg_replace('/\D/', '', (string)$requestedSlot);
        $requestedNum = (!$forceRotate && $digits !== '') ? (int)$digits : null;
        $isNanoMode = in_array($rawMode, ['nano', 'nano_banana', 'nanobanana'], true) || ($requestedNum !== null && $requestedNum >= 11 && $requestedNum <= 15);

        $poolSlots = $isNanoMode ? Db::readSlotsByRange(11, 15) : Db::readSlotsByRange(1, 10);
        usort($poolSlots, fn($a, $b) => ((int)$a['slotNumber']) <=> ((int)$b['slotNumber']));
        $activeSlots = array_values(array_filter($poolSlots, fn($s) => ($s['status'] ?? '') === 'ACTIVE' && !empty($s['cookies'])));

        $activeSlot = null;
        $assignedIndex = 0;
        if (!$forceRotate && $requestedNum !== null) {
            foreach ($poolSlots as $s) {
                if ((int)$s['slotNumber'] === $requestedNum && !empty($s['cookies'])) {
                    $activeSlot = $s;
                    break;
                }
            }
            if ($activeSlot && !empty($activeSlots)) {
                foreach ($activeSlots as $idx => $as) {
                    if ((int)$as['slotNumber'] === (int)$activeSlot['slotNumber']) {
                        $assignedIndex = $idx;
                        break;
                    }
                }
            }
        }
        if (!$activeSlot && $isNanoMode) {
            $rr = self::pickRoundRobinNanoSlot($poolSlots);
            $activeSlot = $rr['slot'];
            $assignedIndex = $rr['assignedIndex'];
        }
        if (!$activeSlot && !$isNanoMode && !empty($activeSlots)) {
            $assigned = Helpers::computeUserAssignedSlot($access['user'] ?? null, $poolSlots);
            $assignedNum = (int)($assigned['defaultSlot'] ?? 0);
            foreach ($activeSlots as $idx => $as) {
                if ((int)$as['slotNumber'] === $assignedNum) {
                    $activeSlot = $as;
                    $assignedIndex = $idx;
                    break;
                }
            }
            if (!$activeSlot) {
                $activeSlot = $activeSlots[0];
            }
        }
        if (!$activeSlot) {
            foreach ($poolSlots as $s) {
                if (!empty($s['cookies'])) {
                    $activeSlot = $s;
                    break;
                }
            }
        }
        if (!$activeSlot) {
            foreach ($poolSlots as $s) {
                if (($s['status'] ?? '') === 'ACTIVE') {
                    $activeSlot = $s;
                    break;
                }
            }
            $activeSlot = $activeSlot ?? ($poolSlots[0] ?? null);
        }

        if (!$activeSlot) {
            Http::json([
                'success' => true,
                'slotNumber' => $isNanoMode ? 11 : 1,
                'name' => $isNanoMode ? 'Server 11 (Nano Banana Pro)' : 'Server 1',
                'mode' => $isNanoMode ? 'nano_banana' : 'google_flow',
                'cookieCount' => 0,
                'updatedAt' => null,
                'cookies' => [],
                'activeCount' => 0,
                'activeSlots' => [],
            ]);
        }

        $activeSlotsOut = array_map(fn($s) => [
            'slotNumber' => $s['slotNumber'],
            'name' => $s['name'],
            'cookieCount' => $s['cookieCount'],
            'id' => 'slot_' . $s['slotNumber'],
            'updatedAt' => $s['updatedAt'] ?? null,
        ], $activeSlots);

        Http::json([
            'success' => true,
            'mode' => $isNanoMode ? 'nano_banana' : 'google_flow',
            'slotNumber' => $activeSlot['slotNumber'],
            'assignedIndex' => $assignedIndex,
            'assignedOrder' => $assignedIndex + 1,
            'name' => $activeSlot['name'],
            'cookieCount' => $activeSlot['cookieCount'],
            'updatedAt' => $activeSlot['updatedAt'] ?? null,
            'versionTs' => (int)($activeSlot['versionTs'] ?? 0),
            'cookies' => $activeSlot['cookies'] ?? [],
            'data' => $activeSlot['cookies'] ?? [],
            'activeCount' => count($activeSlots),
            'activeSlots' => $activeSlotsOut,
            'file' => [
                'id' => 'slot_' . $activeSlot['slotNumber'],
                'filename' => 'account_' . $activeSlot['slotNumber'] . '.json',
                'cookieCount' => $activeSlot['cookieCount'],
                'name' => $activeSlot['name'],
            ],
        ]);
    }

    private static function publicAllTools(): void
    {
        Http::json([
            'success' => false,
            'disabled' => true,
            'error' => 'ToolDisabled',
            'message' => 'AI Chat Tools have been removed. Only Google Flow is active.',
            'tools' => [],
            'counts' => [],
        ], 410);
    }

    private static function publicToolCookie(string $rawToolKey): void
    {
        Http::json([
            'success' => false,
            'disabled' => true,
            'error' => 'ToolDisabled',
            'message' => 'This tool has been removed. Only Google Flow is active.',
            'cookies' => [],
            'data' => [],
        ], 410);
    }

    private static function publicCookieById(string $id): void
    {
        if (isset(self::TOOL_KEY_MAP[strtolower($id)])) {
            Http::json([
                'success' => false, 'disabled' => true, 'error' => 'ToolDisabled',
                'message' => 'This tool has been removed. Only Google Flow is active.',
                'cookies' => [], 'data' => [],
            ], 410);
        }
        $access = Helpers::resolveSlotRequestAccess(true);
        if (!empty($access['forcedLogout'])) {
            Http::json($access['forcedLogout'], 401);
        }
        if (!empty($access['flowLocked'])) {
            Http::json([
                'success' => true, 'flowLocked' => true, 'plan' => 'basic',
                'cookieCount' => 0, 'cookies' => [], 'data' => [],
                'message' => 'Google Flow AI is locked on Basic Plan. Upgrade to access Flow AI.',
            ]);
        }

        $num = str_starts_with($id, 'slot_') ? (int)substr($id, 5) : (ctype_digit($id) ? (int)$id : 0);
        $targetSlot = ($num >= 1 && $num <= 15) ? Db::readSlotByNumber($num) : null;
        if ($targetSlot) {
            $resolvedSlot = $targetSlot;
            $slotNum = (int)$targetSlot['slotNumber'];
            if ($slotNum >= 11 && $slotNum <= 15 && empty($targetSlot['cookies'])) {
                $nanoPool = Db::readSlotsByRange(11, 15);
                $rr = self::pickRoundRobinNanoSlot($nanoPool);
                if ($rr['slot']) {
                    $resolvedSlot = $rr['slot'];
                }
            }
            $vts = (int)($resolvedSlot['versionTs'] ?? 0);
            if ($vts > 0) {
                $etag = 'W/"slot-' . $resolvedSlot['slotNumber'] . '-' . $vts . '"';
                header('ETag: ' . $etag);
                if (Http::header('if-none-match') === $etag) {
                    http_response_code(304);
                    exit;
                }
            }
            $cnt = (int)($resolvedSlot['cookieCount'] ?? count($resolvedSlot['cookies'] ?? []));
            Http::json([
                'success' => true,
                'mode' => ((int)$resolvedSlot['slotNumber']) >= 11 ? 'nano_banana' : 'google_flow',
                'slotNumber' => $resolvedSlot['slotNumber'],
                'versionTs' => $vts,
                'data' => $resolvedSlot['cookies'] ?? [],
                'cookies' => $resolvedSlot['cookies'] ?? [],
                'file' => [
                    'id' => 'slot_' . $resolvedSlot['slotNumber'],
                    'filename' => 'account_' . $resolvedSlot['slotNumber'] . '.json',
                    'cookieCount' => $cnt,
                    'name' => $resolvedSlot['name'],
                    'serverType' => Helpers::getSlotTypeTag((int)$resolvedSlot['slotNumber']),
                ],
            ]);
        }
        Http::json(['success' => false, 'error' => "Session '$id' not found."], 404);
    }

    private static function publicPrompts(): void
    {
        $category = Http::query('category');
        $sort = Http::query('sort');
        $q = Http::query('q') ?? Http::query('search') ?? '';
        $offset = max(0, (int)Http::query('offset', 0));
        $limit = max(1, min(2000, (int)Http::query('limit', 1000)));

        $all = Db::readPrompts();
        $rev = Db::getPromptsRevision();
        $catKey = ($category && $category !== 'all') ? strtolower((string)$category) : 'all';
        $sortKey = ($sort === 'popular' || $sort === 'featured') ? $sort : 'latest';
        $queryTerm = strtolower(trim((string)$q));

        $etag = "W/\"pr-$rev-$catKey-$sortKey-$queryTerm-$offset-$limit\"";
        header('Cache-Control: public, max-age=30, stale-while-revalidate=120');
        header("ETag: $etag");
        if (Http::header('if-none-match') === $etag) {
            http_response_code(304);
            exit;
        }

        $filtered = $catKey === 'all'
            ? $all
            : array_values(array_filter($all, fn($p) => strtolower((string)($p['category'] ?? '')) === $catKey));

        if ($sortKey === 'popular') {
            usort($filtered, fn($a, $b) => ((int)($b['views'] ?? 0)) <=> ((int)($a['views'] ?? 0)));
        } elseif ($sortKey === 'featured') {
            usort($filtered, fn($a, $b) => ((int)($b['likes'] ?? 0)) <=> ((int)($a['likes'] ?? 0)));
        } else {
            usort($filtered, function ($a, $b) {
                $ta = !empty($a['createdAt']) ? (strtotime((string)$a['createdAt']) ?: 0) : 0;
                $tb = !empty($b['createdAt']) ? (strtotime((string)$b['createdAt']) ?: 0) : 0;
                return $tb <=> $ta;
            });
        }

        if ($queryTerm !== '') {
            $filtered = array_values(array_filter($filtered, function ($p) use ($queryTerm) {
                return str_contains(strtolower((string)($p['title'] ?? '')), $queryTerm)
                    || str_contains(strtolower((string)($p['prompt'] ?? '')), $queryTerm)
                    || str_contains(strtolower((string)($p['model'] ?? '')), $queryTerm);
            }));
        }

        $total = count($filtered);
        $paginated = array_slice($filtered, $offset, $limit);
        $nextOffset = $offset + $limit;
        $hasMore = $nextOffset < $total;

        Http::json([
            'success' => true,
            'items' => $paginated,
            'total' => $total,
            'nextOffset' => $hasMore ? $nextOffset : null,
            'hasMore' => $hasMore,
        ]);
    }

    private static function adminCreatePrompt(): void
    {
        $body = Http::body();
        $decodedPrompt = Db::decodeWafSafeText($body['prompt'] ?? '');
        $decodedTitle = Db::decodeWafSafeText($body['title'] ?? '');
        if (!$decodedPrompt || trim((string)$decodedPrompt) === '') {
            Http::json(['error' => 'ValidationError', 'message' => 'Prompt text is required'], 400);
        }

        $finalImageUrl = $body['image'] ?? '/flowbypak-logo.png';
        if (!empty($body['imageData'])) {
            $validImg = Helpers::decodeValidBase64Image($body['imageData']);
            if (!$validImg) {
                Http::json(['error' => 'InvalidImage', 'message' => 'Only genuine PNG, JPG, WEBP, or GIF images are allowed.'], 400);
            }
            $root = dirname(__DIR__, 2);
            $pubDir = "$root/public/uploads/prompts";
            $distDir = "$root/dist/uploads/prompts";
            if (!is_dir($pubDir)) @mkdir($pubDir, 0775, true);
            if (!is_dir($distDir)) @mkdir($distDir, 0775, true);
            $base = preg_replace('/[^a-zA-Z0-9_-]/', '', preg_replace('/\.[^.]+$/', '', (string)($body['imageName'] ?? 'prompt')) ?? 'prompt');
            $cleanBase = substr($base ?: 'prompt', 0, 40);
            $safeName = (int)round(microtime(true) * 1000) . "-$cleanBase.{$validImg['ext']}";
            file_put_contents("$pubDir/$safeName", $validImg['buffer']);
            if (is_dir($distDir)) {
                file_put_contents("$distDir/$safeName", $validImg['buffer']);
            }
            $finalImageUrl = "/uploads/prompts/$safeName";
        }

        $prompts = Db::readPrompts();
        $newPrompt = [
            'id' => 'p-' . (int)round(microtime(true) * 1000),
            'title' => $decodedTitle ? trim((string)$decodedTitle) : 'Creative AI Prompt',
            'prompt' => trim((string)$decodedPrompt),
            'category' => $body['category'] ?? 'general',
            'model' => $body['model'] ?? 'Veo 3.1 & Flux',
            'mediaType' => 'image',
            'image' => $finalImageUrl,
            'images' => [$finalImageUrl],
            'width' => 1024,
            'height' => 1024,
            'likes' => random_int(50, 249),
            'views' => random_int(150, 649),
            'author' => [
                'name' => 'Flow by Pak',
                'username' => 'pakflow',
                'avatar' => '/flowbypak-logo.png',
                'profileUrl' => '#',
            ],
            'createdAt' => Db::isoNow(),
        ];

        array_unshift($prompts, $newPrompt);
        Db::writePrompts($prompts);
        Http::json(['success' => true, 'message' => 'Prompt uploaded successfully', 'prompt' => $newPrompt]);
    }

    private static function adminDeletePrompt(string $id): void
    {
        $prompts = Db::readPrompts();
        $target = null;
        $filtered = [];
        foreach ($prompts as $p) {
            if ((string)($p['id'] ?? '') === $id) {
                $target = $p;
            } else {
                $filtered[] = $p;
            }
        }
        if (count($filtered) === count($prompts)) {
            Http::json(['error' => 'NotFound', 'message' => 'Prompt not found'], 404);
        }
        Db::writePrompts($filtered);

        if (!empty($target['image']) && str_starts_with((string)$target['image'], '/uploads/prompts/')) {
            $fileName = basename((string)$target['image']);
            $stillUsed = false;
            foreach ($filtered as $p) {
                if (($p['image'] ?? '') === $target['image']) {
                    $stillUsed = true;
                    break;
                }
            }
            if (!$stillUsed && $fileName !== '') {
                $root = dirname(__DIR__, 2);
                foreach (["$root/public/uploads/prompts/$fileName", "$root/dist/uploads/prompts/$fileName"] as $full) {
                    if (is_file($full)) {
                        @unlink($full);
                    }
                }
            }
        }
        Http::json(['success' => true, 'message' => 'Prompt deleted successfully']);
    }

    private static function synthesizeTts(): void
    {
        $body = Http::body();
        $text = (string)($body['text'] ?? '');
        if (trim($text) === '') {
            Http::json(['error' => 'Text is required'], 400);
        }
        try {
            $mp3 = PakTts::synthesizePakTtsEdgeNeural([
                'text' => $text,
                'voice' => (string)($body['voice'] ?? 'en-US-AvaMultilingualNeural'),
                'rate' => $body['rate'] ?? 1,
                'pitch' => $body['pitch'] ?? 0,
                'volume' => $body['volume'] ?? 0,
            ]);
            header('Content-Type: audio/mpeg');
            header('Content-Length: ' . strlen($mp3));
            header('Cache-Control: no-store');
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            echo $mp3;
            exit;
        } catch (\Throwable $e) {
            Http::json(['error' => 'SynthesisFailed', 'message' => $e->getMessage() ?: 'Failed to synthesize neural voice'], 502);
        }
    }
}
