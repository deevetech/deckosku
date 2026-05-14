<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\ExcelImporter;

// Optional CLI override: php bin/import.php /path/to/file.xlsx
$override = $argv[1] ?? null;

try {
    $importer = new ExcelImporter($override);
    $result   = $importer->run();
} catch (Throwable $e) {
    fwrite(STDERR, "Import failed: {$e->getMessage()}\n");
    exit(1);
}

echo "✓ Excel import complete\n";
echo "  rows total:    {$result['rows_total']}\n";
echo "  rows imported: {$result['rows_imported']}\n";
echo "  rows failed:   {$result['rows_failed']}\n";
echo "  photos saved:  {$result['photos_saved']}\n";
echo "  duration:      {$result['duration_ms']} ms\n";
if ($result['notes'] !== '') {
    echo "  notes:         {$result['notes']}\n";
}
