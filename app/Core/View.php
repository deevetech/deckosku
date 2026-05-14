<?php

declare(strict_types=1);

namespace App\Core;

/**
 * View — PHP template renderer with layout support.
 *
 * Usage:
 *   View::render('dashboard/index', ['skus' => $rows], layout: 'app');
 *
 * Templates can call `$this->section('content', function () { ... })` style sections,
 * but for this project we keep it simple: a layout file includes one child via
 * the `$content` variable.
 */
final class View
{
    /** @var array<string,string> Shared data available to every template. */
    private static array $shared = [];

    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function render(string $template, array $data = [], ?string $layout = 'app'): string
    {
        $merged = array_merge(self::$shared, $data);
        $body   = self::renderRaw($template, $merged);

        if ($layout === null) {
            return $body;
        }

        $merged['content'] = $body;
        return self::renderRaw('layouts/' . $layout, $merged);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function output(string $template, array $data = [], ?string $layout = 'app'): void
    {
        echo self::render($template, $data, $layout);
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function renderRaw(string $template, array $data): string
    {
        $path = Paths::views($template . '.php');
        if (!is_file($path)) {
            throw new \RuntimeException("View not found: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $path;
        return (string) ob_get_clean();
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    public static function url(string $path = ''): string
    {
        $base = rtrim((string) Env::get('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }

    public static function asset(string $path): string
    {
        return self::url('assets/' . ltrim($path, '/'));
    }
}
