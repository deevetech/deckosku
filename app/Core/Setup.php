<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use Throwable;

/**
 * Setup — first-run installation gate.
 *
 * `isInstalled()` returns true when the schema has been applied AND at least one
 * admin user exists. Until then, `public/index.php` redirects every non-/setup
 * request to the setup wizard. Once installed, the wizard POST refuses to run
 * again so a hostile re-submission cannot wipe data.
 */
final class Setup
{
    private static ?bool $cached = null;

    public static function isInstalled(): bool
    {
        if (self::$cached !== null) {
            return self::$cached;
        }

        try {
            $pdo    = Database::connection();
            $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

            $stmt = $pdo->query($driver === 'sqlite'
                ? "SELECT name FROM sqlite_master WHERE type='table' AND name='users' LIMIT 1"
                : "SHOW TABLES LIKE 'users'");
            if ($stmt === false || $stmt->fetchColumn() === false) {
                return self::$cached = false;
            }

            $count = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
            return self::$cached = $count > 0;
        } catch (PDOException | Throwable) {
            return self::$cached = false;
        }
    }

    public static function dbReachable(): bool
    {
        try {
            Database::connection();
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public static function dbError(): ?string
    {
        try {
            Database::connection();
            return null;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
}
