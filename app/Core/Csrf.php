<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CSRF — synchroniser-token pattern.
 * One token per session; checked on every state-changing request.
 */
final class Csrf
{
    private const KEY = '_csrf_token';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!is_string($token) || strlen($token) !== 64) {
            $token = bin2hex(random_bytes(32));
            Session::put(self::KEY, $token);
        }
        return $token;
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES) . '">';
    }

    public static function validate(?string $submitted): bool
    {
        $expected = Session::get(self::KEY);
        if (!is_string($expected) || !is_string($submitted)) {
            return false;
        }
        return hash_equals($expected, $submitted);
    }

    public static function assertFromRequest(): void
    {
        $submitted = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::validate(is_string($submitted) ? $submitted : null)) {
            http_response_code(419);
            echo 'CSRF token mismatch. Please refresh and try again.';
            exit;
        }
    }
}
