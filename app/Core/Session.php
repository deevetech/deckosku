<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Session — secure session bootstrap with explicit cookie params.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $name      = (string) Env::get('SESSION_NAME', 'decko_session');
        $lifetime  = Env::int('SESSION_LIFETIME_MINUTES', 480) * 60;
        $secure    = Env::bool('SESSION_SECURE', false);
        $samesite  = (string) Env::get('SESSION_SAMESITE', 'Lax');

        session_name($name);
        session_set_cookie_params([
            'lifetime' => $lifetime,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => $samesite,
        ]);

        session_start();

        // Rotate the session id periodically to defeat fixation.
        if (!isset($_SESSION['_started_at'])) {
            $_SESSION['_started_at'] = time();
        } elseif ((time() - $_SESSION['_started_at']) > 1800) {
            session_regenerate_id(true);
            $_SESSION['_started_at'] = time();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, ?string $value = null): ?string
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $message = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $message;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'],
            ]);
        }
        session_destroy();
    }
}
