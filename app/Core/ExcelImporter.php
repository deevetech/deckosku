<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * ExcelImporter — reads the DECKO warehouse workbook and loads it into SQLite.
 *
 * Responsibilities:
 *   1. Truncate skus + categories (the workbook is the source of truth).
 *   2. Walk every data row, splitting into category rows ("DD - DECKODURANTE...")
 *      and SKU rows ("DD-CRE-BL").
 *   3. Map each row's columns to the typed schema fields.
 *   4. Pull every embedded drawing in the sheet, look up which cell it anchors to,
 *      then save it under /storage/photos/ with a deterministic file name and
 *      record the relative path on the right SKU column.
 *   5. Build a denormalised `search_haystack` per row for fast LIKE queries.
 */
final class ExcelImporter
{
    /** Map of column index (1-based) → schema field name. */
    private const COLUMN_MAP = [
        2  => 'sku_code',
        3  => 'description',
        6  => 'internal_barcode',
        8  => 'gtin_barcode',
        9  => 'variant_label',
        10 => 'unit_length_cm',
        11 => 'unit_width_cm',
        12 => 'unit_height_cm',
        13 => 'unit_weight_kg',
        14 => 'unit_volume_cm3',
        15 => 'units_per_box',
        18 => 'box_barcode',
        19 => 'box_length_cm',
        20 => 'box_width_cm',
        21 => 'box_height_cm',
        22 => 'box_weight_kg',
        23 => 'box_volume_cm3',
        25 => 'unit_length_in',
        26 => 'unit_width_in',
        27 => 'unit_height_in',
        28 => 'unit_weight_lb',
        29 => 'unit_volume_in3',
        31 => 'box_length_in',
        32 => 'box_width_in',
        33 => 'box_height_in',
        34 => 'box_weight_lb',
        35 => 'box_volume_in3',
    ];

    /** Map of column index (1-based) → photo field name (where the embedded drawing should be saved). */
    private const PHOTO_COLUMNS = [
        4  => 'photo_product',
        5  => 'photo_internal_barcode',
        7  => 'photo_gtin_barcode',
        16 => 'photo_box',
        17 => 'photo_box_barcode',
    ];

    private string $sourceFile;
    private string $sheetName;
    private int $headerRow;
    private int $dataStarts;

    private int $rowsTotal    = 0;
    private int $rowsImported = 0;
    private int $rowsFailed   = 0;
    private int $photosSaved  = 0;

    /** @var array<string,string> Sticky logs of skipped rows. */
    private array $skipped = [];

    public function __construct(?string $sourceFile = null)
    {
        $envFile         = (string) Env::get('EXCEL_PATH', 'DECKO Warehouse SKU and Barcode list.xlsx');
        $this->sourceFile = $sourceFile ?? Paths::base($envFile);
        $this->sheetName  = (string) Env::get('EXCEL_SHEET', 'New Warehouse');
        $this->headerRow  = Env::int('EXCEL_HEADER_ROW', 2);
        $this->dataStarts = Env::int('EXCEL_DATA_STARTS', 3);
    }

    /**
     * @return array{rows_total:int,rows_imported:int,rows_failed:int,photos_saved:int,duration_ms:int,notes:string}
     */
    public function run(): array
    {
        if (!is_file($this->sourceFile)) {
            throw new \RuntimeException("Excel source not found: {$this->sourceFile}");
        }

        $start = microtime(true);

        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $pdo->exec('DELETE FROM skus');
            $pdo->exec('DELETE FROM categories');
            if ($pdo->getAttribute(\PDO::ATTR_DRIVER_NAME) === 'sqlite') {
                $pdo->exec("DELETE FROM sqlite_sequence WHERE name IN ('skus','categories')");
            } else {
                $pdo->exec('ALTER TABLE skus AUTO_INCREMENT = 1');
                $pdo->exec('ALTER TABLE categories AUTO_INCREMENT = 1');
            }

            // Clear out any previously extracted photos so old ones don't linger.
            $this->wipePhotosDir();

            $spreadsheet = IOFactory::load($this->sourceFile);
            $sheet       = $spreadsheet->getSheetByName($this->sheetName);
            if ($sheet === null) {
                throw new \RuntimeException("Sheet not found: {$this->sheetName}");
            }

            $maxRow = $sheet->getHighestDataRow();
            $this->rowsTotal = max(0, $maxRow - $this->dataStarts + 1);

            $currentCategoryId = null;
            $currentCategoryCode = null;
            $categorySortOrder   = 0;

            // Index two photo sources:
            //   (a) PHPSpreadsheet anchored drawings ("Insert > Picture > Place on Cells")
            //   (b) Rich-data "Insert > Picture > Place in Cell" — the dominant format here
            $drawingsByCell = $this->indexDrawings($sheet);

            $rich = new RichDataExtractor($this->sourceFile);
            $rich->load($this->resolveSheetXmlPath($spreadsheet, $sheet));

            for ($row = $this->dataStarts; $row <= $maxRow; $row++) {
                $col1 = $this->cellString($sheet, 1, $row);
                $sku  = $this->cellString($sheet, 2, $row);

                // Category info can appear on its own row (col2 empty) OR on the
                // first SKU row of a section (col1 + col2 both present). Either
                // way, treat col1 as the source of truth and update the current
                // category whenever it has a non-empty value.
                if ($col1 !== '') {
                    [$code, $name]       = $this->splitCategory($col1);
                    if ($currentCategoryCode !== $code) {
                        $categorySortOrder++;
                        $currentCategoryId   = $this->upsertCategory($pdo, $code, $name, $categorySortOrder);
                        $currentCategoryCode = $code;
                    }
                }

                // SKU row: column 2 must be a non-empty SKU code.
                if ($sku === '') {
                    continue;
                }

                $payload = $this->buildSkuPayload($sheet, $row, $sku, $currentCategoryId);

                // Save embedded photos for this row, if any.
                foreach (self::PHOTO_COLUMNS as $colIdx => $field) {
                    // First try anchored drawings, then the rich-data extractor.
                    $key = $row . '|' . $colIdx;
                    if (isset($drawingsByCell[$key])) {
                        $savedPath = $this->saveDrawing($drawingsByCell[$key], $sku, $field);
                        if ($savedPath !== null) {
                            $payload[$field] = $savedPath;
                            $this->photosSaved++;
                            continue;
                        }
                    }

                    $bytes = $rich->imageBytesForCell($colIdx, $row);
                    if ($bytes !== null) {
                        $mediaPath = $rich->mediaPathForCell($colIdx, $row) ?? '';
                        $savedPath = $this->saveBlob($bytes, $mediaPath, $sku, $field);
                        if ($savedPath !== null) {
                            $payload[$field] = $savedPath;
                            $this->photosSaved++;
                        }
                    }
                }

                $payload['search_haystack'] = $this->buildHaystack($payload);

                try {
                    $this->insertSku($pdo, $payload);
                    $this->rowsImported++;
                } catch (\Throwable $e) {
                    $this->rowsFailed++;
                    $this->skipped[$sku] = $e->getMessage();
                }
            }

            // Persist import log.
            $duration = (int) round((microtime(true) - $start) * 1000);
            $log      = $pdo->prepare(
                'INSERT INTO import_log
                 (source_file, rows_total, rows_imported, rows_failed, photos_saved, duration_ms, notes)
                 VALUES (:src, :total, :imp, :failed, :photos, :ms, :notes)'
            );
            $notes = $this->skipped === [] ? '' : 'Skipped: ' . implode('; ', array_map(
                static fn (string $s, string $r): string => "{$s} → {$r}",
                array_keys($this->skipped),
                array_values($this->skipped),
            ));
            $log->execute([
                ':src'    => basename($this->sourceFile),
                ':total'  => $this->rowsTotal,
                ':imp'    => $this->rowsImported,
                ':failed' => $this->rowsFailed,
                ':photos' => $this->photosSaved,
                ':ms'     => $duration,
                ':notes'  => $notes,
            ]);

            $pdo->commit();
            $rich->close();

            return [
                'rows_total'    => $this->rowsTotal,
                'rows_imported' => $this->rowsImported,
                'rows_failed'   => $this->rowsFailed,
                'photos_saved'  => $this->photosSaved,
                'duration_ms'   => $duration,
                'notes'         => $notes,
            ];
        } catch (\Throwable $e) {
            $pdo->rollBack();
            isset($rich) && $rich->close();
            throw $e;
        }
    }

    /**
     * Resolve which `xl/worksheets/sheetN.xml` corresponds to the active sheet,
     * by walking the workbook's relationships.
     */
    private function resolveSheetXmlPath(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, Worksheet $sheet): string
    {
        foreach ($spreadsheet->getAllSheets() as $index => $candidate) {
            if ($candidate === $sheet) {
                return 'xl/worksheets/sheet' . ($index + 1) . '.xml';
            }
        }
        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * Save a raw image blob (from rich-data extraction) to /storage/photos.
     */
    private function saveBlob(string $bytes, string $mediaPath, string $sku, string $field): ?string
    {
        $extension = strtolower(pathinfo($mediaPath, PATHINFO_EXTENSION) ?: 'png');
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            $extension = 'png';
        }

        $safeSku   = preg_replace('/[^A-Za-z0-9_-]+/', '_', $sku) ?? 'sku';
        $safeField = str_replace('photo_', '', $field);
        $fileName  = "{$safeSku}__{$safeField}.{$extension}";
        $target    = Paths::photos($fileName);

        if (file_put_contents($target, $bytes) === false) {
            return null;
        }
        return $fileName;
    }

    /**
     * Build an array keyed by "row|col" (1-based) pointing to each Drawing object.
     *
     * @return array<string,Drawing|MemoryDrawing>
     */
    private function indexDrawings(Worksheet $sheet): array
    {
        $indexed = [];
        foreach ($sheet->getDrawingCollection() as $drawing) {
            $coordinate = $drawing->getCoordinates(); // e.g. "D3"
            if ($coordinate === '') {
                continue;
            }
            [$col, $row] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($coordinate);
            $colIndex    = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col);
            $indexed[$row . '|' . $colIndex] = $drawing;
        }
        return $indexed;
    }

    private function wipePhotosDir(): void
    {
        $dir = Paths::photos();
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
            return;
        }
        foreach (glob($dir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function saveDrawing(Drawing|MemoryDrawing $drawing, string $sku, string $field): ?string
    {
        $safeSku  = preg_replace('/[^A-Za-z0-9_-]+/', '_', $sku) ?? 'sku';
        $safeField = str_replace('photo_', '', $field);

        if ($drawing instanceof MemoryDrawing) {
            $extension = match ($drawing->getMimeType()) {
                MemoryDrawing::MIMETYPE_PNG  => 'png',
                MemoryDrawing::MIMETYPE_GIF  => 'gif',
                MemoryDrawing::MIMETYPE_JPEG => 'jpg',
                default                       => 'png',
            };
            $fileName = "{$safeSku}__{$safeField}.{$extension}";
            $target   = Paths::photos($fileName);

            ob_start();
            call_user_func($drawing->getRenderingFunction(), $drawing->getImageResource());
            $blob = ob_get_clean();
            if ($blob === false || $blob === '') {
                return null;
            }
            file_put_contents($target, $blob);
            return $fileName;
        }

        $path = $drawing->getPath();
        if ($path === '' || !is_readable($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: 'png');
        if (!in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'webp'], true)) {
            $extension = 'png';
        }

        $fileName = "{$safeSku}__{$safeField}.{$extension}";
        $target   = Paths::photos($fileName);
        copy($path, $target);

        return $fileName;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitCategory(string $raw): array
    {
        $raw = trim($raw);
        if (str_contains($raw, '-')) {
            [$code, $name] = array_pad(explode('-', $raw, 2), 2, '');
            return [trim($code), trim($name)];
        }
        return [substr($raw, 0, 8), $raw];
    }

    private function upsertCategory(PDO $pdo, string $code, string $name, int $sort): int
    {
        if ($code === '') {
            $code = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name) ?: 'UNK', 0, 3));
        }

        $stmt = $pdo->prepare('SELECT id FROM categories WHERE code = :code LIMIT 1');
        $stmt->execute([':code' => $code]);
        $id = $stmt->fetchColumn();
        if ($id !== false) {
            return (int) $id;
        }

        $insert = $pdo->prepare(
            'INSERT INTO categories (code, name, sort_order) VALUES (:code, :name, :sort)'
        );
        $insert->execute([
            ':code' => $code,
            ':name' => $name,
            ':sort' => $sort,
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * @return array<string,mixed>
     */
    private function buildSkuPayload(Worksheet $sheet, int $row, string $sku, ?int $categoryId): array
    {
        $payload = [
            'category_id' => $categoryId,
            'sku_code'    => $sku,
        ];

        foreach (self::COLUMN_MAP as $col => $field) {
            if ($field === 'sku_code') {
                continue;
            }
            $value = $this->cellRaw($sheet, $col, $row);
            $payload[$field] = $this->coerce($field, $value);
        }

        foreach (self::PHOTO_COLUMNS as $field) {
            if (!array_key_exists($field, $payload)) {
                $payload[$field] = null;
            }
        }

        return $payload;
    }

    private function coerce(string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $trimmed = trim($value);
            if ($trimmed === '' || $trimmed === '#VALUE!' || $trimmed === '#REF!' || $trimmed === '#N/A') {
                return null;
            }
            $value = $trimmed;
        }

        $isNumericField = str_starts_with($field, 'unit_') || str_starts_with($field, 'box_')
            || $field === 'units_per_box';

        if ($isNumericField) {
            if (is_numeric($value)) {
                if ($field === 'units_per_box') {
                    return (int) $value;
                }
                return (float) $value;
            }
            return null;
        }

        return is_string($value) ? $value : (string) $value;
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function buildHaystack(array $payload): string
    {
        $parts = [
            $payload['sku_code']         ?? '',
            $payload['description']      ?? '',
            $payload['variant_label']    ?? '',
            $payload['internal_barcode'] ?? '',
            $payload['gtin_barcode']     ?? '',
            $payload['box_barcode']      ?? '',
        ];
        return strtolower(implode(' ', array_filter($parts, static fn ($v) => $v !== null && $v !== '')));
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function insertSku(PDO $pdo, array $payload): void
    {
        $columns      = array_keys($payload);
        $placeholders = array_map(static fn (string $c): string => ':' . $c, $columns);

        $sql = sprintf(
            'INSERT INTO skus (%s) VALUES (%s)',
            implode(', ', $columns),
            implode(', ', $placeholders),
        );

        $stmt = $pdo->prepare($sql);
        foreach ($payload as $column => $value) {
            $stmt->bindValue(':' . $column, $value);
        }
        $stmt->execute();
    }

    private function cellString(Worksheet $sheet, int $col, int $row): string
    {
        $value = $sheet->getCell([$col, $row])->getCalculatedValue();
        if (is_array($value)) {
            return '';
        }
        return $value === null ? '' : trim((string) $value);
    }

    private function cellRaw(Worksheet $sheet, int $col, int $row): mixed
    {
        return $sheet->getCell([$col, $row])->getCalculatedValue();
    }
}
