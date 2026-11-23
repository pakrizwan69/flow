<?php
declare(strict_types=1);

namespace App;

use App\Routes\AdminRoutes;
use App\Routes\AuthRoutes;
use App\Routes\CookieSlotRoutes;
use App\Routes\ResellerRoutes;

final class ApiRouter
{
    public static function dispatch(string $method, string $apiPath): bool
    {
        $origin = Http::header('origin');
        $isExtOrigin = str_starts_with($origin, 'chrome-extension://');
        $isExtPath = $apiPath === '/user/login' || $apiPath === '/user/me';
        $isLogoutPath = $apiPath === '/auth/logout' || $apiPath === '/user/logout';
        $isAdminPath = str_starts_with($apiPath, '/admin/') || $apiPath === '/admin';

        // Admin Cookie Uploader (/api/admin/*) is already JWT-protected and must NOT be blocked by User Extension minExtVersion bumps
        if (($isExtOrigin || $isExtPath) && !$isLogoutPath && !$isAdminPath) {
            $minVer = Helpers::normalizeExtVersionString(Helpers::MIN_PAKFLOW_EXT_VERSION, '1.5');
            $latestVer = $minVer;
            try {
                $st = Db::readSettings();
                $cfgMin = $st['minExtVersion'] ?? null;
                if ($cfgMin !== null && trim((string)$cfgMin) !== '') {
                    $parsedMin = Helpers::normalizeExtVersionString($cfgMin, $minVer);
                    if (version_compare($parsedMin, '1.0', '>=')) {
                        $minVer = $parsedMin;
                    }
                }
                $cfgLatest = $st['extensions']['flow']['version'] ?? ($st['windowsApp']['version'] ?? null);
                if ($cfgLatest !== null && trim((string)$cfgLatest) !== '') {
                    $latestVer = Helpers::normalizeExtVersionString($cfgLatest, $minVer);
                }
            } catch (\Throwable $_e) {}
            $clientVer = Helpers::getPakFlowExtVersionString();
            if (Helpers::isExtVersionOutdated($clientVer, $minVer)) {
                Http::json([
                    'success' => false,
                    'authenticated' => false,
                    'forceLogout' => true,
                    'outdatedExtension' => true,
                    'minVersion' => $minVer,
                    'latestVersion' => $latestVer,
                    'downloadUrl' => '/downloads/latest',
                    'error' => 'Extension Update Required',
                    'message' => "Your extension (v{$clientVer}) is outdated. Please download and install the latest PakFlow Extension (v{$latestVer}) from your dashboard.",
                ], 403);
            }
        }

        if (AuthRoutes::dispatch($method, $apiPath)) {
            return true;
        }
        if (AdminRoutes::dispatch($method, $apiPath)) {
            return true;
        }
        if (CookieSlotRoutes::dispatch($method, $apiPath)) {
            return true;
        }
        if (ResellerRoutes::dispatch($method, $apiPath)) {
            return true;
        }

        return false;
    }
}
