<?php
declare(strict_types=1);

namespace App;

final class Http
{
    private static ?array $cachedJsonBody = null;
    private static bool $bodyParsed = false;

    public static function method(): string
    {
        return strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    }

    public static function path(): string
    {
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $qPos = strpos($uri, '?');
        if ($qPos !== false) {
            $uri = substr($uri, 0, $qPos);
        }
        $uri = rawurldecode($uri);
        if ($uri === '') {
            return '/';
        }
        return $uri;
    }

    public static function header(string $name): string
    {
        $target = strtolower($name);
        if ($target === 'authorization') {
            $auth = $_SERVER['HTTP_AUTHORIZATION']
                ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
                ?? '';
            if ($auth === '' && function_exists('apache_request_headers')) {
                $headers = apache_request_headers();
                foreach ($headers as $k => $v) {
                    if (strtolower((string)$k) === 'authorization') {
                        $auth = (string)$v;
                        break;
                    }
                }
            }
            return trim((string)$auth);
        }

        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        if (isset($_SERVER[$serverKey])) {
            return trim((string)$_SERVER[$serverKey]);
        }
        if ($target === 'content-type' && isset($_SERVER['CONTENT_TYPE'])) {
            return trim((string)$_SERVER['CONTENT_TYPE']);
        }
        if ($target === 'content-length' && isset($_SERVER['CONTENT_LENGTH'])) {
            return trim((string)$_SERVER['CONTENT_LENGTH']);
        }
        if (function_exists('apache_request_headers')) {
            foreach (apache_request_headers() as $k => $v) {
                if (strtolower((string)$k) === $target) {
                    return trim((string)$v);
                }
            }
        }
        return '';
    }

    public static function clientIp(): string
    {
        $xff = self::header('x-forwarded-for');
        if ($xff !== '') {
            $parts = explode(',', $xff);
            $first = trim($parts[0]);
            if ($first !== '') {
                return $first;
            }
        }
        $cfIp = self::header('cf-connecting-ip');
        if ($cfIp !== '') {
            return $cfIp;
        }
        $realIp = self::header('x-real-ip');
        if ($realIp !== '') {
            return $realIp;
        }
        return (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    }

    public static function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    public static function body(): array
    {
        if (self::$bodyParsed) {
            return self::$cachedJsonBody ?? [];
        }
        self::$bodyParsed = true;

        $contentType = strtolower(self::header('content-type'));
        $raw = file_get_contents('php://input');

        if ($raw !== false && $raw !== '') {
            if (str_contains($contentType, 'application/json') || str_starts_with(ltrim($raw), '{') || str_starts_with(ltrim($raw), '[')) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    self::$cachedJsonBody = $decoded;
                    return self::$cachedJsonBody;
                }
            }
            if (str_contains($contentType, 'application/x-www-form-urlencoded')) {
                $parsed = [];
                parse_str($raw, $parsed);
                if (is_array($parsed) && !empty($parsed)) {
                    self::$cachedJsonBody = $parsed;
                    return self::$cachedJsonBody;
                }
            }
        }

        if (!empty($_POST)) {
            self::$cachedJsonBody = $_POST;
            return self::$cachedJsonBody;
        }

        self::$cachedJsonBody = [];
        return self::$cachedJsonBody;
    }

    public static function rawBody(): string
    {
        $raw = file_get_contents('php://input');
        return $raw === false ? '' : $raw;
    }

    public static function sendCorsAndSecurityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        $origin = self::header('origin');
        if ($origin !== '') {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin');
        } else {
            header('Access-Control-Allow-Origin: *');
        }
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Admin-Token, X-Device-Id, X-Requested-With, X-Client-App, X-Fingerprint, X-PakFlow-Extension-Version, X-PakFlow-Version, X-Ext-Version, Accept, Origin, Cache-Control, Pragma, If-None-Match');
        header('Access-Control-Max-Age: 86400');
        if (strtolower(self::header('access-control-request-private-network')) === 'true') {
            header('Access-Control-Allow-Private-Network: true');
        }
    }

    public static function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        // Prevent LiteSpeed, CDN, and browser disk caches from serving stale API/cookie/server state
        $hasCacheControl = false;
        foreach (headers_list() as $h) {
            if (stripos($h, 'Cache-Control:') === 0) {
                $hasCacheControl = true;
                break;
            }
        }
        if (!$hasCacheControl) {
            header('Cache-Control: no-store, no-cache, must-revalidate, private');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('X-LiteSpeed-Cache-Control: no-cache');
        }
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($data, $flags);
        if ($json === false) {
            error_log('[Http::json] json_encode failed: ' . json_last_error_msg());
            http_response_code(500);
            echo '{"success":false,"error":"JsonEncodeError"}';
            exit;
        }

        // Let Apache mod_deflate / LiteSpeed / Cloudflare handle HTTP compression cleanly
        // (Manual gzencode + Content-Length in PHP behind LiteSpeed/Apache causes double-compression 502s)
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit;
    }

    public static function sendFile(string $filePath, string $contentType = 'application/octet-stream', ?string $downloadName = null, ?string $cacheControl = null): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            http_response_code(404);
            exit;
        }
        $size = filesize($filePath);
        $mtime = filemtime($filePath) ?: time();
        $etag = 'W/"' . dechex((int)$size) . '-' . dechex((int)$mtime) . '"';
        $lastModified = gmdate('D, d M Y H:i:s', (int)$mtime) . ' GMT';

        header('ETag: ' . $etag);
        header('Last-Modified: ' . $lastModified);
        if ($cacheControl !== null) {
            header('Cache-Control: ' . $cacheControl);
        }
        $ifNoneMatch = self::header('if-none-match');
        $ifModifiedSince = self::header('if-modified-since');
        if (($ifNoneMatch !== '' && $ifNoneMatch === $etag)
            || ($ifModifiedSince !== '' && strtotime($ifModifiedSince) >= (int)$mtime)) {
            http_response_code(304);
            exit;
        }

        header('Content-Type: ' . $contentType);
        if ($downloadName !== null) {
            header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
        }

        if ($size !== false) {
            header('Content-Length: ' . $size);
        }

        // Clear output buffers before streaming
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        readfile($filePath);
        exit;
    }

    public static function mimeType(string $filePath): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        switch ($ext) {
            case 'html':
            case 'htm': return 'text/html; charset=utf-8';
            case 'js':
            case 'mjs': return 'application/javascript; charset=utf-8';
            case 'css': return 'text/css; charset=utf-8';
            case 'json': return 'application/json; charset=utf-8';
            case 'png': return 'image/png';
            case 'jpg':
            case 'jpeg': return 'image/jpeg';
            case 'gif': return 'image/gif';
            case 'webp': return 'image/webp';
            case 'svg': return 'image/svg+xml';
            case 'ico': return 'image/x-icon';
            case 'woff': return 'font/woff';
            case 'woff2': return 'font/woff2';
            case 'ttf': return 'font/ttf';
            case 'otf': return 'font/otf';
            case 'mp4': return 'video/mp4';
            case 'webm': return 'video/webm';
            case 'mp3': return 'audio/mpeg';
            case 'zip': return 'application/zip';
            case 'exe': return 'application/octet-stream';
            case 'txt': return 'text/plain; charset=utf-8';
            default: return function_exists('mime_content_type') ? (mime_content_type($filePath) ?: 'application/octet-stream') : 'application/octet-stream';
        }
    }
}
