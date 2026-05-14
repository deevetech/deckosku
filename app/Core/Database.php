<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

/**
 * Database — thin wrapper around PDO with sane defaults.
 *
 * Single shared connection per request. SQLite is the default driver because
 * this dashboard ships with a single .sqlite file, but the layout would extend
 * cleanly to MySQL if the catalogue ever outgrows SQLite (it won't for ~400 SKUs).
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $driver = (string) Env::get('DB_DRIVER', 'sqlite');

        try {
            self::$instance = match ($driver) {
                'sqlite' => self::makeSqlite(),
                'mysql'  => self::makeMysql(),
                default  => throw new \RuntimeException("Unsupported DB_DRIVER: {$driver}"),
            };
        } catch (PDOException $e) {
            throw new \RuntimeException('Database connection failed: ' . $e->getMessage(), 0, $e);
        }

        return self::$instance;
    }

    public static function driver(): string
    {
        return (string) Env::get('DB_DRIVER', 'sqlite');
    }

    private static function makeSqlite(): PDO
    {
        $relative = (string) Env::get('DB_PATH', 'storage/database.sqlite');
        $absolute = Paths::base($relative);

        $dir = dirname($absolute);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $pdo = new PDO('sqlite:' . $absolute, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Enable foreign keys and write-ahead logging for better concurrency.
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');

        return $pdo;
    }

    private static function makeMysql(): PDO
    {
        $host    = (string) Env::get('DB_HOST', '127.0.0.1');
        $port    = (string) Env::get('DB_PORT', '3306');
        $db      = (string) Env::get('DB_NAME', 'decko_size');
        $user    = (string) Env::get('DB_USER', 'root');
        $pass    = (string) Env::get('DB_PASS', '');
        $charset = (string) Env::get('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ]);
    }
}
