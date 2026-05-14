<?php

declare(strict_types=1);

// Shared bootstrap for CLI scripts.
require __DIR__ . '/../vendor/autoload.php';

use App\Core\Env;
use App\Core\Paths;

$basePath = dirname(__DIR__);
Paths::setBase($basePath);
Env::load($basePath);

// Make sure storage subfolders exist.
foreach (['logs', 'photos', 'cache', 'uploads'] as $sub) {
    $dir = Paths::storage($sub);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}
