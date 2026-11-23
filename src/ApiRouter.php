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

        if (($isExtOrigin || $isExtPath) && !$isLogoutPath) {
            $minVer = Helpers::normalizeExtVersionString(Helpers::MIN_PAKFLOW_EXT_VERSION, '1.5');
            try {
                $cfgMin = Db::readSettings()['minExtVersion'] ?? null;
                if ($cfgMin !== null && trim((string)$cfgMin) !== '') {
                    $parsedMin = Helpers::normalizeExtVersionString($cfgMin, $minVer);
                    if (version_compare($parsedMin, '1.0', '>=')) {
                        $minVer = $parsedMin;
                    }
                }
            } catch (\Throwable $_e) {}
            $clientVer = Helpers::getPakFlowExtVersionString();
            if (Helpers::isExtVersionOutdated($clientVer, $minVer)) {
                Http::json([
                    'success' => false,
                    'authenticated' => false,
                    'forceLogout' => true,
                    'outdatedExtension' => true,
                    'error' => 'Extension Outdated',
                    'message' => "Download latest extension (v{$minVer}) from your dashboard.",
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
