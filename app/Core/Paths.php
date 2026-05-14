<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Path helpers — single source of truth for file system locations.
 */
final class Paths
{
    private static string $basePath = '';

    public static function setBase(string $basePath): void
    {
        self::$basePath = rtrim(str_replace('\\', '/', $basePath), '/');
    }

    public static function base(string $relative = ''): string
    {
        return self::$basePath . ($relative === '' ? '' : '/' . ltrim(str_replace('\\', '/', $relative), '/'));
    }

    public static function storage(string $relative = ''): string
    {
        return self::base('storage' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }

    public static function photos(string $relative = ''): string
    {
        return self::storage('photos' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }

    public static function views(string $relative = ''): string
    {
        return self::base('app/Views' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }

    public static function public(string $relative = ''): string
    {
        return self::base('public' . ($relative === '' ? '' : '/' . ltrim($relative, '/')));
    }
}
