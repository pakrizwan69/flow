<?php
declare(strict_types=1);

namespace App;

final class Env
{
    private static bool $loaded = false;

    public static function load(?string $rootDir = null): void
    {
        if (self::$loaded) {
            return;
        }
        self::$loaded = true;

        $root = $rootDir ?? dirname(__DIR__);
        $envFile = $root . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envFile) || !is_readable($envFile)) {
            return;
        }

        $lines = @file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }
            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = trim(substr($trimmed, 7));
            }
            $eqPos = strpos($trimmed, '=');
            if ($eqPos === false) {
                continue;
            }
            $key = trim(substr($trimmed, 0, $eqPos));
            $val = trim(substr($trimmed, $eqPos + 1));
            if ($key === '') {
                continue;
            }

            if (
                (str_starts_with($val, '"') && str_ends_with($val, '"') && strlen($val) >= 2) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'") && strlen($val) >= 2)
            ) {
                $val = substr($val, 1, -1);
            }

            if (getenv($key) === false && !array_key_exists($key, $_ENV)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }

    public static function get(string $key, string $default = ''): string
    {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($val === false || $val === null || $val === '') {
            return $default;
        }
        return (string)$val;
    }
}
