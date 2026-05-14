<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Core\Paths;

$pdo    = Database::connection();
$driver = Database::driver();

$schemaPath = $driver === 'mysql'
    ? Paths::base('database/schema.mysql.sql')
    : Paths::base('database/schema.sql');

$sql = file_get_contents($schemaPath);
if ($sql === false) {
    fwrite(STDERR, "Cannot read schema file: {$schemaPath}\n");
    exit(1);
}

if ($driver === 'mysql') {
    // MySQL's PDO::exec only runs a single statement at a time — split on `;`.
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt === '' || str_starts_with($stmt, '--')) {
            continue;
        }
        $pdo->exec($stmt);
    }
    echo "✓ MySQL schema applied to database `" . (string) \App\Core\Env::get('DB_NAME', 'decko_size') . "`" . PHP_EOL;
} else {
    // SQLite PDO supports multi-statement exec.
    $pdo->exec($sql);
    echo "✓ Schema applied to " . Paths::base((string) \App\Core\Env::get('DB_PATH', 'storage/database.sqlite')) . PHP_EOL;
}
