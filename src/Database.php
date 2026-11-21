<?php
declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;
    private static ?bool $enabled = null;
    private static array $lastAttempts = [];

    public static function isEnabled(): bool
    {
        if (self::$enabled !== null) {
            return self::$enabled;
        }
        $dbName = Env::get('DB_NAME');
        self::$enabled = ($dbName !== '' && extension_loaded('pdo_mysql'));
        return self::$enabled;
    }

    public static function resetConnection(): void
    {
        self::$pdo = null;
        self::$enabled = null;
        self::$lastAttempts = [];
    }

    public static function isTransientConnectionError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        $driverCode = ($e instanceof \PDOException && isset($e->errorInfo[1])) ? (int)$e->errorInfo[1] : 0;
        if (in_array($driverCode, [2002, 2006, 2013, 1205, 1213], true)) {
            return true;
        }
        return str_contains($msg, 'server has gone away')
            || str_contains($msg, 'lost connection')
            || str_contains($msg, 'deadlock found')
            || str_contains($msg, 'lock wait timeout')
            || str_contains($msg, 'broken pipe')
            || str_contains($msg, '[2006]')
            || str_contains($msg, '[2013]');
    }

    public static function lastDiagnostics(): array
    {
        $sockets = ['/var/lib/mysql/mysql.sock', '/run/mysqld/mysqld.sock', '/tmp/mysql.sock'];
        $pdoSock = (string)@ini_get('pdo_mysql.default_socket');
        $mysqliSock = (string)@ini_get('mysqli.default_socket');
        if ($pdoSock !== '') $sockets[] = $pdoSock;
        if ($mysqliSock !== '') $sockets[] = $mysqliSock;
        $found = array_values(array_filter(array_unique($sockets), static fn(string $p): bool => @file_exists($p)));

        return [
            'configuredHost' => Env::get('DB_HOST', 'localhost'),
            'configuredPort' => Env::get('DB_PORT', '3306'),
            'configuredSocket' => Env::get('DB_SOCKET'),
            'pdoDefaultSocket' => $pdoSock,
            'mysqliDefaultSocket' => $mysqliSock,
            'socketsFound' => $found,
            'serverAddr' => (string)($_SERVER['SERVER_ADDR'] ?? ''),
            'phpVersion' => PHP_VERSION,
            'attempts' => self::$lastAttempts,
        ];
    }

    private static function buildCandidateDsns(string $host, string $port, string $name, string $charset): array
    {
        $dsns = [];
        $explicitSocket = Env::get('DB_SOCKET');
        if ($explicitSocket !== '') {
            $dsns[] = ['label' => 'explicit_socket', 'dsn' => "mysql:unix_socket=$explicitSocket;dbname=$name;charset=$charset"];
        }

        if (strcasecmp($host, 'localhost') === 0) {
            $dsns[] = ['label' => 'localhost_socket', 'dsn' => "mysql:host=localhost;dbname=$name;charset=$charset"];

            $sockCandidates = [
                (string)@ini_get('pdo_mysql.default_socket'),
                (string)@ini_get('mysqli.default_socket'),
                '/var/lib/mysql/mysql.sock',
                '/run/mysqld/mysqld.sock',
            ];
            foreach (array_unique($sockCandidates) as $sock) {
                if ($sock !== '' && $sock !== $explicitSocket && @file_exists($sock)) {
                    $dsns[] = ['label' => 'unix_socket:' . $sock, 'dsn' => "mysql:unix_socket=$sock;dbname=$name;charset=$charset"];
                }
            }

            $dsns[] = ['label' => 'tcp_127.0.0.1', 'dsn' => "mysql:host=127.0.0.1;port=$port;dbname=$name;charset=$charset"];
        } else {
            $dsns[] = ['label' => 'configured_host', 'dsn' => "mysql:host=$host;port=$port;dbname=$name;charset=$charset"];
            if ($host === '127.0.0.1') {
                $dsns[] = ['label' => 'localhost_socket', 'dsn' => "mysql:host=localhost;dbname=$name;charset=$charset"];
            }
        }

        return $dsns;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $host = Env::get('DB_HOST', '127.0.0.1');
        $port = Env::get('DB_PORT', '3306');
        $name = Env::get('DB_NAME');
        $user = Env::get('DB_USER', 'root');
        $pass = Env::get('DB_PASS', '');
        $charset = Env::get('DB_CHARSET', 'utf8mb4');

        if ($name === '') {
            throw new \RuntimeException('DB_NAME is not configured in .env');
        }

        // Persistent connections avoid Hostinger's per-account [2002] Operation not permitted rate limit
        $usePersistent = strtolower(Env::get('DB_PERSISTENT', 'true')) !== 'false';
        $options = [
            PDO::ATTR_PERSISTENT => $usePersistent,
            PDO::ATTR_TIMEOUT => 5,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $candidates = self::buildCandidateDsns($host, $port, $name, $charset);
        self::$lastAttempts = [];
        $firstEx = null;

        foreach ($candidates as $cand) {
            try {
                $pdo = new PDO($cand['dsn'], $user, $pass, $options);
                if ($pdo->inTransaction()) {
                    @$pdo->rollBack();
                }
                self::$pdo = $pdo;
                return self::$pdo;
            } catch (\PDOException $e) {
                $firstEx ??= $e;
                $msg = $e->getMessage();
                self::$lastAttempts[] = ['mode' => $cand['label'], 'error' => $msg];
                $isConn2002 = str_contains($msg, '[2002]') || (int)($e->errorInfo[1] ?? 0) === 2002;
                if (!$isConn2002) {
                    throw $e;
                }
            }
        }

        throw $firstEx ?? new \RuntimeException('Unable to connect to MySQL database');
    }
}
