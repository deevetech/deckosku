<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Env;
use App\Core\Paths;

$dbPath = Paths::base((string) Env::get('DB_PATH', 'storage/database.sqlite'));

foreach ([$dbPath, $dbPath . '-journal', $dbPath . '-wal', $dbPath . '-shm'] as $file) {
    if (is_file($file)) {
        unlink($file);
        echo "✗ removed {$file}\n";
    }
}

echo "✓ Database reset. Next: php bin/migrate.php && php bin/seed.php && php bin/import.php\n";
