<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Paths;
use App\Models\User;

/**
 * Auth — session-backed authentication for a small team app.
 *
 * Stores the user id and a server-known fingerprint in the session.
 * Rate-limits failed logins by email to slow brute force.
 */
final class Auth
{
    private const SESSION_USER_KEY = '_auth_user_id';
    private const FAILED_KEY       = '_auth_failed';

    public static function attempt(string $email, string $password): bool
    {
        $email = strtolower(trim($email));

        if (self::isLockedOut($email)) {
            return false;
        }

        $user = User::findByEmail($email);
        if ($user === null || !password_verify($password, $user['password_hash'])) {
            self::recordFailure($email);
            return false;
        }

        // Rehash if PHP's preferred algorithm has changed.
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        self::clearFailures($email);
        session_regenerate_id(true);
        Session::put(self::SESSION_USER_KEY, (int) $user['id']);
        User::touchLogin((int) $user['id']);
        return true;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function user(): ?array
    {
        $id = Session::get(self::SESSION_USER_KEY);
        if (!is_int($id)) {
            return null;
        }
        return User::find($id);
    }

    public static function logout(): void
    {
        Session::forget(self::SESSION_USER_KEY);
        session_regenerate_id(true);
    }

    public static function id(): ?int
    {
        $id = Session::get(self::SESSION_USER_KEY);
        return is_int($id) ? $id : null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && ($user['role'] ?? '') === 'admin';
    }

    /**
     * Hard guard for admin-only routes. Sends 403 and exits when not admin.
     */
    public static function assertAdmin(): void
    {
        if (!self::isAdmin()) {
            http_response_code(403);
            require Paths::views('errors/403.php');
            exit;
        }
    }

    private static function recordFailure(string $email): void
    {
        $failures = $_SESSION[self::FAILED_KEY] ?? [];
        $record   = $failures[$email] ?? ['count' => 0, 'until' => 0];
        $record['count']++;
        if ($record['count'] >= 5) {
            $record['until'] = time() + 300; // 5 minutes
            $record['count'] = 0;
        }
        $failures[$email]            = $record;
        $_SESSION[self::FAILED_KEY]  = $failures;
    }

    private static function clearFailures(string $email): void
    {
        if (isset($_SESSION[self::FAILED_KEY][$email])) {
            unset($_SESSION[self::FAILED_KEY][$email]);
        }
    }

    private static function isLockedOut(string $email): bool
    {
        $until = $_SESSION[self::FAILED_KEY][$email]['until'] ?? 0;
        return $until > time();
    }
}
