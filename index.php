<?php
declare(strict_types=1);

// Flow by Pak — PHP 7.4 / 8.x Application Entrypoint (Converted from Node.js/Express)
@ini_set('display_errors', '0');
@ini_set('log_errors', '1');
@ini_set('error_log', __DIR__ . '/data/php_error.log');
@ini_set('memory_limit', '256M');
@set_time_limit(120);

if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool {
        return $needle === '' || ($haystack !== '' && substr_compare($haystack, $needle, -strlen($needle)) === 0);
    }
}
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool {
        return $needle === '' || strpos($haystack, $needle) !== false;
    }
}

require_once __DIR__ . '/src/Http.php';

use App\Http;

Http::sendCorsAndSecurityHeaders();

$method = Http::method();
$reqPath = Http::path();

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public';
$distDir = __DIR__ . DIRECTORY_SEPARATOR . 'dist';

// Helper to safely resolve a path inside a base directory
$safeResolve = static function (string $baseDir, string $relPath): ?string {
    $cleanRel = ltrim(str_replace(['..', '\\'], ['', '/'], $relPath), '/');
    if ($cleanRel === '') {
        return null;
    }
    $full = $baseDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanRel);
    if (is_file($full) && is_readable($full)) {
        return $full;
    }
    return null;
};

// Fast-path static assets before loading DB/routes/Env
if ($method === 'GET' || $method === 'HEAD') {
    // /uploads/* static files
    if (str_starts_with($reqPath, '/uploads/')) {
        $rel = substr($reqPath, strlen('/uploads/'));
        foreach ([$publicDir . DIRECTORY_SEPARATOR . 'uploads', $distDir . DIRECTORY_SEPARATOR . 'uploads'] as $dir) {
            $file = $safeResolve($dir, $rel);
            if ($file !== null) {
                Http::sendFile($file, Http::mimeType($file), null, 'public, max-age=604800');
            }
        }
    }

    // /assets/* with index-*.js fallback and immutable caching
    if (str_starts_with($reqPath, '/assets/')) {
        $file = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($reqPath)) ?? '';
        $targetPath = $distDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . $file;
        if (!is_file($targetPath) && str_starts_with($file, 'index-') && str_ends_with($file, '.js')) {
            $targetPath = $distDir . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'index-PakFlowV10.js';
        }
        if (is_file($targetPath)) {
            $isImmutable = isset($_GET['v']) || preg_match('/-[A-Za-z0-9_-]{6,}\.(js|css)$/', $file);
            $cacheControl = $isImmutable ? 'public, max-age=31536000, immutable' : 'public, max-age=86400';
            Http::sendFile($targetPath, Http::mimeType($targetPath), null, $cacheControl);
        }
    }

    // /videos/*
    if (str_starts_with($reqPath, '/videos/')) {
        $file = $safeResolve($distDir . DIRECTORY_SEPARATOR . 'videos', substr($reqPath, strlen('/videos/')));
        if ($file !== null) {
            Http::sendFile($file, Http::mimeType($file), null, 'public, max-age=2592000, immutable');
        }
    }

    // Other static files in dist/ (logos, favicons, banners, etc., excluding index.html and /downloads/*)
    if ($reqPath !== '/' && $reqPath !== '/index.html' && !str_starts_with($reqPath, '/downloads/')) {
        $file = $safeResolve($distDir, $reqPath);
        if ($file !== null) {
            $isMedia = preg_match('/\.(png|jpe?g|webp|gif|svg|ico|woff2?|mp4|webm)$/i', $file) === 1;
            Http::sendFile($file, Http::mimeType($file), null, $isMedia ? 'public, max-age=2592000' : 'public, max-age=86400');
        }
    }
}

// /downloads/* routes — ALWAYS serve a verified real ZIP (auto-rebuilds from extension-src if wiped by Git deploy; NEVER falls through to index.html!)
$chatAliases = [
    '/downloads/Social_Pak_Extension.zip',
    '/downloads/PakFlow_AI_Tools_Extension.zip',
    '/downloads/PakFlow_Extension_v1.0.2.zip',
];
if ($method === 'GET' && in_array($reqPath, $chatAliases, true)) {
    Http::json(['success' => false, 'disabled' => true, 'error' => 'ToolDisabled', 'message' => 'This extension has been removed. Only Google Flow is active.'], 410);
}

if (($method === 'GET' || $method === 'HEAD') && str_starts_with($reqPath, '/downloads/')) {
    $isValidZipFile = static function (string $path): bool {
        clearstatcache(true, $path);
        if (!is_file($path) || !is_readable($path)) {
            return false;
        }
        $sz = @filesize($path);
        if ($sz === false || $sz < 50000) {
            return false;
        }
        $fh = @fopen($path, 'rb');
        if (!$fh) {
            return false;
        }
        $sig = fread($fh, 4);
        fclose($fh);
        return $sig === "PK\x03\x04";
    };

    $rel = substr($reqPath, strlen('/downloads/'));
    $exactBase = basename($rel);
    $isLatestReq = ($reqPath === '/downloads/latest' || $reqPath === '/downloads/PakFlow_latest.zip');
    $isZipReq = $isLatestReq || str_ends_with(strtolower($exactBase), '.zip');

    if (!$isZipReq) {
        foreach ([$publicDir . DIRECTORY_SEPARATOR . 'downloads', $distDir . DIRECTORY_SEPARATOR . 'downloads'] as $dir) {
            $file = $safeResolve($dir, $rel);
            if ($file !== null) {
                Http::sendFile($file, Http::mimeType($file), null, 'public, max-age=7200');
            }
        }
        Http::json(['success' => false, 'error' => 'DownloadNotFound', 'message' => 'Requested file not found.'], 404);
    }

    // 1) If an exact versioned ZIP was requested and exists validly on disk, serve it immediately (0 DB overhead)
    if (!$isLatestReq && $exactBase !== '') {
        foreach (["$distDir/downloads/$exactBase", "$publicDir/downloads/$exactBase"] as $exactF) {
            if ($isValidZipFile($exactF)) {
                Http::sendFile($exactF, 'application/zip', $exactBase, 'no-cache, no-store, must-revalidate');
            }
        }
    }

    // 2) Resolve active configured version & filename from app settings (MySQL/JSON)
    $dynName = 'PakFlow_v1.7.zip';
    $dynVer = '1.7';
    try {
        require_once __DIR__ . '/src/Env.php';
        require_once __DIR__ . '/src/Database.php';
        require_once __DIR__ . '/src/Db.php';
        \App\Env::load(__DIR__);
        $extFlow = \App\Db::readSettings()['extensions']['flow'] ?? [];
        if (!empty($extFlow['fileName'])) {
            $dynName = basename((string)$extFlow['fileName']);
        }
        if (!empty($extFlow['version'])) {
            $vClean = ltrim(trim((string)$extFlow['version']), 'vV');
            if (preg_match('/^\d+(?:\.\d+){0,3}$/', $vClean)) {
                $dynVer = $vClean;
            }
        }
    } catch (\Throwable $_e) {}

    foreach (["$distDir/downloads/$dynName", "$publicDir/downloads/$dynName"] as $f) {
        if ($isValidZipFile($f)) {
            Http::sendFile($f, 'application/zip', $dynName, 'no-cache, no-store, must-revalidate');
        }
    }

    // 3) Auto-heal: if configured or requested versioned ZIP is missing on disk (e.g. after Git deploy), rebuild it on the fly from extension-src/
    $buildVer = $dynVer;
    if (!$isLatestReq && preg_match('/v?(\d+(?:\.\d+){1,3})\.zip$/i', $exactBase, $reqVm)) {
        $buildVer = $reqVm[1];
    }
    try {
        require_once __DIR__ . '/src/Helpers.php';
        require_once __DIR__ . '/src/Routes/AdminRoutes.php';
        $rebuilt = \App\Routes\AdminRoutes::buildExtensionZipToDisk($buildVer);
        if ($rebuilt && !empty($rebuilt['path']) && $isValidZipFile($rebuilt['path'])) {
            Http::sendFile($rebuilt['path'], 'application/zip', $rebuilt['fileName'], 'no-cache, no-store, must-revalidate');
        }
    } catch (\Throwable $_rebuildErr) {
        error_log('[Downloads Auto-Rebuild Error] ' . $_rebuildErr->getMessage());
    }

    // 4) Fallback to the newest valid PakFlow*.zip present on disk (NEVER serve HTML as a ZIP!)
    $candidates = [];
    foreach (["$distDir/downloads", "$publicDir/downloads"] as $scanDir) {
        if (is_dir($scanDir)) {
            foreach (glob($scanDir . '/*.zip') ?: [] as $zf) {
                $bn = basename($zf);
                if (stripos($bn, 'Social_Pak') !== false || stripos($bn, 'AI_Tools') !== false) {
                    continue;
                }
                if ($isValidZipFile($zf)) {
                    $candidates[$zf] = @filemtime($zf) ?: 0;
                }
            }
        }
    }
    if (!empty($candidates)) {
        arsort($candidates);
        $bestZip = (string)array_key_first($candidates);
        Http::sendFile($bestZip, 'application/zip', basename($bestZip), 'no-cache, no-store, must-revalidate');
    }

    Http::json(['success' => false, 'error' => 'ExtensionZipUnavailable', 'message' => 'Extension package is temporarily unavailable. Please retry in a moment.'], 404);
}

// Redirect removed extra tool pages directly to the Google Flow dashboard
$removedToolRoutes = [
    '/chatgpt', '/grok', '/supergrok', '/meta', '/meta-ai', '/muse', '/muse-ai',
    '/pak-tts', '/tts', '/bunny-tts', '/tts11', '/fire-tts', '/minimax', '/elevenlabs', '/voice-clone',
    '/prompts', '/prompt-library', '/promptify', '/surfshark', '/urban',
    '/whisk', '/whisk-studio', '/openart', '/gemini-live', '/nano-banana-pro',
];
$normalizedRoute = $reqPath === '/' ? '/' : (rtrim($reqPath, '/') ?: '/');
if (($method === 'GET' || $method === 'HEAD') && in_array($normalizedRoute, $removedToolRoutes, true)) {
    header('Location: /dashboard', true, 302);
    exit;
}

// Direct Login / Signup / Clean Dashboard UI (served without loading API/DB classes)
$servePhpView = static function (string $viewFile): void {
    clearstatcache(true, $viewFile);
    $mtime = @filemtime($viewFile) ?: 0;
    $size = @filesize($viewFile) ?: 0;
    $etag = "\"vw-$mtime-$size\"";
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header("ETag: $etag");
    $ifNoneMatch = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
    if ($ifNoneMatch === $etag || $ifNoneMatch === "W/$etag") {
        http_response_code(304);
        exit;
    }
    if (!ini_get('zlib.output_compression') && function_exists('ob_gzhandler') && !headers_sent()) {
        @ob_start('ob_gzhandler');
    }
    require $viewFile;
    exit;
};

$cleanAppRoutes = ['/', '/about', '/login', '/register', '/signup', '/dashboard', '/settings', '/pricing', '/reset-password'];
if (($method === 'GET' || $method === 'HEAD') && in_array($normalizedRoute, $cleanAppRoutes, true)) {
    $appView = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'app.php';
    if (is_file($appView)) {
        $servePhpView($appView);
    }
}

if (($method === 'GET' || $method === 'HEAD') && in_array($normalizedRoute, ['/admin/email', '/admin/emails'], true)) {
    $emailView = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin-email.php';
    if (is_file($emailView)) {
        $servePhpView($emailView);
    }
}

if (($method === 'GET' || $method === 'HEAD') && ($normalizedRoute === '/admin' || $normalizedRoute === '/admin/dashboard' || $normalizedRoute === '/admin/login')) {
    $adminView = __DIR__ . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'admin.php';
    if (is_file($adminView)) {
        $servePhpView($adminView);
    }
}

// Fast slot version endpoint (micro-cached 5s on disk for 20k+ users with 0 MySQL connections on cache hits)
if ($method === 'GET' && in_array($reqPath, ['/api/slots/version', '/api/cookies/version'], true)) {
    require_once __DIR__ . '/src/Env.php';
    \App\Env::load(__DIR__);
    $rawSlotParam = (string)(Http::query('slot') ?? Http::query('slotNumber') ?? '');
    $slotDigits = preg_replace('/\D/', '', $rawSlotParam) ?? '';
    $slotNum = ($slotDigits !== '' && (int)$slotDigits >= 1 && (int)$slotDigits <= 15) ? (int)$slotDigits : 0;

    $dataDir = __DIR__ . DIRECTORY_SEPARATOR . 'data';
    $versionCacheFile = $dataDir . DIRECTORY_SEPARATOR . 'version_cache.json';
    $cachedSnap = null;
    clearstatcache(true, $versionCacheFile);
    if (is_file($versionCacheFile) && is_readable($versionCacheFile)) {
        $age = time() - (@filemtime($versionCacheFile) ?: 0);
        if ($age >= 0 && $age <= 5) {
            $rawC = @file_get_contents($versionCacheFile);
            if ($rawC !== false && $rawC !== '') {
                $decC = json_decode($rawC, true);
                if (is_array($decC) && isset($decC['versions']) && is_array($decC['versions'])) {
                    $cachedSnap = $decC;
                }
            }
        }
    }

    if ($cachedSnap === null) {
        $versionsMap = ['max' => '0'];
        $useMysql = (\App\Env::get('DB_NAME') !== '' && extension_loaded('pdo_mysql'));
        if ($useMysql) {
            require_once __DIR__ . '/src/Database.php';
            $queryAllVers = static function (): array {
                $pdo = \App\Database::pdo();
                $rows = $pdo->query('SELECT slot_number, COALESCE(version_ts, 0) AS v FROM cookie_slots')->fetchAll();
                $map = ['max' => '0'];
                $maxV = 0;
                foreach ($rows as $r) {
                    $sn = (int)($r['slot_number'] ?? 0);
                    $v = (int)($r['v'] ?? 0);
                    if ($sn >= 1 && $sn <= 15) {
                        $map[(string)$sn] = (string)$v;
                    }
                    if ($v > $maxV) {
                        $maxV = $v;
                    }
                }
                $map['max'] = (string)$maxV;
                return $map;
            };
            try {
                $versionsMap = $queryAllVers();
            } catch (\Throwable $e) {
                if (\App\Database::isTransientConnectionError($e)) {
                    try {
                        \App\Database::resetConnection();
                        $versionsMap = $queryAllVers();
                    } catch (\Throwable $_retryErr) {
                        $versionsMap = ['max' => '0'];
                    }
                } else {
                    $slotsFile = $dataDir . DIRECTORY_SEPARATOR . 'slots.json';
                    clearstatcache(true, $slotsFile);
                    $versionsMap = ['max' => ((@filemtime($slotsFile) ?: 0) . '-' . (@filesize($slotsFile) ?: 0))];
                }
            }
        } else {
            $slotsFile = $dataDir . DIRECTORY_SEPARATOR . 'slots.json';
            clearstatcache(true, $slotsFile);
            $mtime = @filemtime($slotsFile) ?: 0;
            $size = @filesize($slotsFile) ?: 0;
            $versionsMap = ['max' => "$mtime-$size"];
        }

        $policyMeta = null;
        $rawShuffle = null;
        $latestExtVersion = null;
        $minExtVersion = null;
        try {
            require_once __DIR__ . '/src/Database.php';
            require_once __DIR__ . '/src/Db.php';
            $st = \App\Db::readSettings();
            $ap = is_array($st['accessPolicy'] ?? null) ? $st['accessPolicy'] : [];
            $policyMeta = [
                'requirePortalOpen' => !empty($ap['requirePortalOpen']),
                'blockOnLogout' => !array_key_exists('blockOnLogout', $ap) || (bool)$ap['blockOnLogout'],
                'blockOnExpired' => !array_key_exists('blockOnExpired', $ap) || (bool)$ap['blockOnExpired'],
                'portalGraceSeconds' => max(3, min(120, (int)($ap['portalGraceSeconds'] ?? 15))),
            ];
            $sh = is_array($st['serverShuffle'] ?? null) ? $st['serverShuffle'] : [];
            $rawShuffle = [
                'enabled' => !empty($sh['enabled']),
                'intervalMinutes' => max(5, min(43200, (int)($sh['intervalMinutes'] ?? 180))),
                'defaultSlot' => max(1, min(10, (int)($sh['defaultSlot'] ?? 1))),
                'shuffleEpoch' => max(1, (int)($sh['shuffleEpoch'] ?? 1)),
                'shuffleAnchorTs' => (int)($sh['shuffleAnchorTs'] ?? 0),
                'lastShuffledAt' => $sh['lastShuffledAt'] ?? null,
            ];
            $rawLatestExt = trim((string)($st['extensions']['flow']['version'] ?? ($st['windowsApp']['version'] ?? '')));
            $rawLatestExt = ltrim($rawLatestExt, 'vV');
            if ($rawLatestExt !== '' && preg_match('/^\d+(?:\.\d+){0,3}/', $rawLatestExt, $lm)) {
                $latestExtVersion = $lm[0];
            }
            $rawMinExt = trim((string)($st['minExtVersion'] ?? ''));
            $rawMinExt = ltrim($rawMinExt, 'vV');
            if ($rawMinExt !== '' && preg_match('/^\d+(?:\.\d+){0,3}/', $rawMinExt, $mm)) {
                $minExtVersion = $mm[0];
            }
        } catch (\Throwable $_metaErr) {}

        $cachedSnap = [
            'versions' => $versionsMap,
            'accessPolicy' => $policyMeta,
            'rawShuffle' => $rawShuffle,
            'latestExtVersion' => $latestExtVersion,
            'minExtVersion' => $minExtVersion,
        ];
        if ($rawShuffle !== null) {
            if (!is_dir($dataDir)) {
                @mkdir($dataDir, 0775, true);
            }
            $tmpCache = $versionCacheFile . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(3));
            $encoded = json_encode($cachedSnap, JSON_UNESCAPED_SLASHES);
            if ($encoded !== false && @file_put_contents($tmpCache, $encoded, LOCK_EX) !== false) {
                if (!@rename($tmpCache, $versionCacheFile)) {
                    @unlink($tmpCache);
                }
            }
        }
    }

    $ver = ($slotNum > 0 && isset($cachedSnap['versions'][(string)$slotNum]))
        ? (string)$cachedSnap['versions'][(string)$slotNum]
        : (string)($cachedSnap['versions']['max'] ?? '0');

    $policyMeta = $cachedSnap['accessPolicy'] ?? null;
    $shuffleMeta = null;
    if (is_array($cachedSnap['rawShuffle'] ?? null)) {
        $sh = $cachedSnap['rawShuffle'];
        $intervalMin = max(5, min(43200, (int)($sh['intervalMinutes'] ?? 180)));
        $shEnabled = !empty($sh['enabled']);
        $shEpoch = max(1, (int)($sh['shuffleEpoch'] ?? 1));
        $nowTs = time();
        $intervalSec = $intervalMin * 60;
        $shAnchor = (int)($sh['shuffleAnchorTs'] ?? 0);
        if ($shAnchor > 0 && $shAnchor <= $nowTs + 60) {
            $shBucket = (int)floor(max(0, $nowTs - $shAnchor) / $intervalSec);
            $nextShuffleTs = $shAnchor + (($shBucket + 1) * $intervalSec);
        } else {
            $shBucket = (int)floor($nowTs / $intervalSec);
            $nextShuffleTs = ($shBucket + 1) * $intervalSec;
        }
        $shuffleMeta = [
            'enabled' => $shEnabled,
            'intervalMinutes' => $intervalMin,
            'defaultSlot' => max(1, min(10, (int)($sh['defaultSlot'] ?? 1))),
            'shuffleEpoch' => $shEpoch,
            'shufflePeriod' => (string)($shEnabled ? ($shBucket + ($shEpoch * 100000)) : $shEpoch),
            'serverTime' => $nowTs,
            'nextShuffleTs' => $nextShuffleTs,
            'remainingSeconds' => max(0, $nextShuffleTs - $nowTs),
            'nextShuffleAt' => gmdate('Y-m-d\TH:i:s', $nextShuffleTs) . '.000Z',
            'lastShuffledAt' => $sh['lastShuffledAt'] ?? null,
        ];
    }

    // Never allow LiteSpeed/CDN to cache cross-slot or stale reshuffle state (disk micro-cache already shields MySQL)
    header('Cache-Control: no-store, no-cache, must-revalidate, private');
    header('X-LiteSpeed-Cache-Control: no-cache');
    Http::json([
        'success' => true,
        'version' => $ver,
        'slot' => $slotNum > 0 ? $slotNum : null,
        'latestExtVersion' => $cachedSnap['latestExtVersion'] ?? null,
        'minExtVersion' => $cachedSnap['minExtVersion'] ?? null,
        'accessPolicy' => $policyMeta,
        'serverShuffle' => $shuffleMeta,
    ]);
}

// Load backend DB/Env/Route classes only for /health, /api/*, /uploads*
if (
    $reqPath === '/health'
    || $reqPath === '/api'
    || str_starts_with($reqPath, '/api/')
    || str_starts_with($reqPath, '/uploads')
) {
    require_once __DIR__ . '/src/Env.php';
    require_once __DIR__ . '/src/Database.php';
    require_once __DIR__ . '/src/Db.php';
    require_once __DIR__ . '/src/Mailer.php';
    require_once __DIR__ . '/src/ExpiryNotifier.php';
    require_once __DIR__ . '/src/Helpers.php';
    require_once __DIR__ . '/src/Routes/AuthRoutes.php';
    require_once __DIR__ . '/src/Routes/AdminRoutes.php';
    require_once __DIR__ . '/src/Routes/CookieSlotRoutes.php';
    require_once __DIR__ . '/src/Routes/ResellerRoutes.php';
    require_once __DIR__ . '/src/ApiRouter.php';

    \App\Env::load(__DIR__);

    // Optional guarded JSON -> MySQL migration & status endpoints (require Admin JWT OR .env MIGRATE_KEY >= 12 chars)
    $authorizeMigrateRequest = static function (): void {
        $envKey = \App\Env::get('MIGRATE_KEY');
        $reqKey = (string)(Http::query('key') ?? '');
        $keyAuthorized = ($envKey !== '' && strlen($envKey) >= 12 && $reqKey !== '' && hash_equals($envKey, $reqKey));
        if (!$keyAuthorized) {
            \App\Helpers::requireAdmin();
        }
    };

    if (($method === 'GET' || $method === 'POST') && $reqPath === '/api/admin/migrate-mysql') {
        $authorizeMigrateRequest();
        require_once __DIR__ . '/database/migrate.php';
        try {
            $res = \App\runMysqlMigration(__DIR__);
            Http::json($res);
        } catch (\Throwable $e) {
            Http::json([
                'success' => false,
                'error' => 'MigrationFailed',
                'message' => $e->getMessage(),
                'diagnostics' => \App\Database::lastDiagnostics(),
            ], 500);
        }
    }

    if (($method === 'GET' || $method === 'POST') && $reqPath === '/api/admin/mysql-status') {
        $authorizeMigrateRequest();
        if (!\App\Database::isEnabled()) {
            Http::json([
                'success' => true,
                'storage' => 'json',
                'connected' => false,
                'dataMigrated' => false,
                'message' => 'MySQL is not configured in .env',
            ]);
        }
        try {
            $pdo = \App\Database::pdo();
            $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
            $tableSet = array_flip(array_map('strval', $tables));
            $countOf = static function (string $tbl) use ($pdo, $tableSet): int {
                return isset($tableSet[$tbl]) ? (int)$pdo->query("SELECT COUNT(*) FROM `$tbl`")->fetchColumn() : 0;
            };
            $counts = [
                'admins' => $countOf('admins'),
                'users' => $countOf('users'),
                'resellers' => $countOf('resellers'),
                'reseller_payments' => $countOf('reseller_payments'),
                'cookie_slots' => $countOf('cookie_slots'),
                'active_cookie_slots' => isset($tableSet['cookie_slots'])
                    ? (int)$pdo->query("SELECT COUNT(*) FROM `cookie_slots` WHERE `status` = 'ACTIVE'")->fetchColumn()
                    : 0,
                'app_settings' => $countOf('app_settings'),
                'deleted_users' => $countOf('deleted_users'),
                'sessions_by_ip' => $countOf('sessions_by_ip'),
            ];
            $latestUserId = isset($tableSet['users'])
                ? (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM `users`')->fetchColumn()
                : 0;
            Http::json([
                'success' => true,
                'storage' => 'mysql',
                'connected' => true,
                'database' => \App\Env::get('DB_NAME'),
                'dataMigrated' => ($counts['users'] > 0 && $counts['cookie_slots'] > 0),
                'counts' => $counts,
                'latestUserId' => $latestUserId,
            ]);
        } catch (\Throwable $e) {
            Http::json([
                'success' => false,
                'storage' => 'mysql',
                'connected' => false,
                'dataMigrated' => false,
                'error' => 'MySqlConnectionError',
                'message' => $e->getMessage(),
                'diagnostics' => \App\Database::lastDiagnostics(),
            ], 500);
        }
    }

    $backendUrl = \App\Env::get('BACKEND_URL')
        ?: \App\Env::get('VITE_API_PROXY_TARGET')
        ?: \App\Env::get('API_URL');

    // Health check endpoints
    if ($method === 'GET' && ($reqPath === '/health' || $reqPath === '/api/health')) {
        if (function_exists('fastcgi_finish_request')) {
            register_shutdown_function(static function (): void {
                @fastcgi_finish_request();
                @\App\ExpiryNotifier::run(false, 5);
            });
        }
        $mysqlEnabled = \App\Database::isEnabled();
        $mysqlConnected = false;
        $dataLoaded = false;
        if ($mysqlEnabled) {
            try {
                $pdo = \App\Database::pdo();
                $mysqlConnected = true;
                $tbl = $pdo->query("SHOW TABLES LIKE 'users'")->fetchColumn();
                if ($tbl !== false) {
                    $dataLoaded = ((int)$pdo->query('SELECT COUNT(*) FROM `users`')->fetchColumn()) > 0;
                }
            } catch (\Throwable $_e) {
                $mysqlConnected = false;
            }
        }
        Http::json([
            'status' => 'ok',
            'app' => 'flowbypak-frontend',
            'runtime' => 'php-' . PHP_VERSION,
            'timestamp' => \App\Db::isoNow(),
            'backendConfigured' => $backendUrl !== '',
            'storage' => $mysqlEnabled ? 'mysql' : 'json',
            'mysqlConnected' => $mysqlConnected,
            'dataLoaded' => $dataLoaded,
        ]);
    }

    // /api/* router
    if ($reqPath === '/api' || str_starts_with($reqPath, '/api/')) {
        $subPath = $reqPath === '/api' ? '/' : substr($reqPath, strlen('/api'));
        $retriedSchema = false;
        $retriedConn = false;
        dispatch_api:
        try {
            if (\App\ApiRouter::dispatch($method, $subPath)) {
                exit;
            }
        } catch (\Throwable $e) {
            if (!$retriedConn && \App\Database::isEnabled() && \App\Database::isTransientConnectionError($e)) {
                $retriedConn = true;
                try {
                    \App\Database::resetConnection();
                    \App\Db::clearCaches();
                    goto dispatch_api;
                } catch (\Throwable $_rcErr) {
                }
            }
            $isMissingTable = !$retriedSchema
                && \App\Database::isEnabled()
                && ($e instanceof \PDOException || str_contains($e->getMessage(), '42S02'))
                && (
                    (string)$e->getCode() === '42S02'
                    || str_contains($e->getMessage(), '42S02')
                    || str_contains($e->getMessage(), "doesn't exist")
                );
            if ($isMissingTable) {
                $retriedSchema = true;
                try {
                    $pdo = \App\Database::pdo();
                    if ($pdo->inTransaction()) {
                        @$pdo->rollBack();
                    }
                    require_once __DIR__ . '/database/migrate.php';
                    \App\ensureSchemaAndSeedIfEmpty(__DIR__);
                    goto dispatch_api;
                } catch (\Throwable $initErr) {
                    error_log('[PHP Auto-Schema Error] ' . $initErr->getMessage());
                }
            }
            error_log('[PHP API Error] ' . $method . ' ' . $reqPath . ': ' . $e->getMessage());
            Http::json([
                'success' => false,
                'error' => 'ServerError',
                'message' => $e->getMessage() ?: 'Unexpected server error',
            ], 500);
        }

        // Optional reverse proxy fallback if BACKEND_URL is set
        if ($backendUrl !== '' && function_exists('curl_init')) {
            $qs = $_SERVER['QUERY_STRING'] ?? '';
            $target = rtrim($backendUrl, '/') . $reqPath . ($qs !== '' ? '?' . $qs : '');
            $ch = curl_init($target);
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_POSTFIELDS => Http::rawBody(),
            ]);
            $resp = curl_exec($ch);
            if ($resp === false) {
                curl_close($ch);
                Http::json(['error' => 'Bad Gateway', 'message' => "Unable to reach backend service at $backendUrl"], 502);
            }
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 200;
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            curl_close($ch);
            http_response_code($status);
            echo substr($resp, $headerSize);
            exit;
        }

        Http::json([
            'success' => true,
            'ok' => true,
            'message' => 'Flow by Pak service operational',
        ]);
    }

    if (str_starts_with($reqPath, '/uploads') && $backendUrl === '') {
        Http::json([
            'success' => true,
            'ok' => true,
            'message' => 'Flow by Pak service operational',
        ]);
    }
}

// SPA fallback for GET / HEAD (e.g. /admin, /reseller)
if ($method === 'GET' || $method === 'HEAD') {
    $indexPath = $distDir . DIRECTORY_SEPARATOR . 'index.html';
    if (is_file($indexPath) && is_readable($indexPath)) {
        Http::sendFile($indexPath, 'text/html; charset=utf-8', null, 'no-cache');
    }
    http_response_code(500);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><title>Build Missing - Flow by Pak</title></head><body><h1>Production Build Missing</h1><p><code>dist/index.html</code> was not found.</p></body></html>';
    exit;
}

http_response_code(404);
Http::json(['success' => false, 'error' => 'NotFound']);
