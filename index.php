<?php
/**
 * Bare-root convenience redirect.
 *
 * In production the web host's DocumentRoot should point at /public/, in
 * which case Apache never opens this file. On a default XAMPP install where
 * the document root is `htdocs/`, hitting the project URL without a trailing
 * `/public/` lands here and gets bounced one level deeper.
 */

declare(strict_types=1);

$scheme = ($_SERVER['HTTPS'] ?? 'off') === 'on' ? 'https' : 'http';
$host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$path   = rtrim(dirname((string) ($_SERVER['PHP_SELF'] ?? '/')), '/\\');

header('Location: ' . $scheme . '://' . $host . $path . '/public/', true, 302);
exit;
