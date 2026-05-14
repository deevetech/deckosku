<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Paths;
use App\Core\View;
use App\Models\Sku;

final class DashboardController
{
    public function index(): void
    {
        $summary    = Sku::summary();
        $categories = Sku::allCategories();

        echo View::render('dashboard/index', [
            'summary'    => $summary,
            'categories' => $categories,
        ]);
    }

    public function apiSkus(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $filters = [
            'q'           => isset($_GET['q'])  ? (string) $_GET['q']  : '',
            'category_id' => isset($_GET['c'])  ? (int)    $_GET['c']  : 0,
            'limit'       => isset($_GET['limit']) ? (int) $_GET['limit'] : 500,
        ];

        $rows  = Sku::search($filters);
        $total = Sku::count($filters);

        echo json_encode([
            'total'   => $total,
            'results' => array_map([$this, 'formatRow'], $rows),
        ], JSON_THROW_ON_ERROR);
    }

    public function apiDetail(string $code): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        $row = Sku::findByCode($code);
        if ($row === null) {
            http_response_code(404);
            echo json_encode(['error' => 'SKU not found.']);
            return;
        }

        echo json_encode($this->formatRow($row), JSON_THROW_ON_ERROR);
    }

    /**
     * POST /api/sku/{code} — update an existing SKU (admin only).
     * Accepts a JSON body OR form-encoded data with the editable fields.
     */
    public function apiUpdate(string $code): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        \App\Core\Csrf::assertFromRequest();
        \App\Core\Auth::assertAdmin();

        $body    = file_get_contents('php://input') ?: '';
        $decoded = $body !== '' ? json_decode($body, true) : null;
        $input   = is_array($decoded) ? $decoded : $_POST;

        $existing = Sku::findByCode($code);
        if ($existing === null) {
            http_response_code(404);
            echo json_encode(['error' => 'SKU not found.']);
            return;
        }

        $payload = [];
        $errors  = [];
        foreach (Sku::editableFields() as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }
            $raw = $input[$field];
            if ($raw === null || (is_string($raw) && trim($raw) === '')) {
                $payload[$field] = null;
                continue;
            }

            $numeric = str_starts_with($field, 'unit_') || str_starts_with($field, 'box_') || $field === 'units_per_box';
            if ($numeric) {
                if (!is_numeric($raw)) {
                    $errors[$field] = 'Must be a number.';
                    continue;
                }
                $payload[$field] = $field === 'units_per_box' ? (int) $raw : (float) $raw;
            } else {
                $payload[$field] = trim((string) $raw);
            }
        }

        if ($errors !== []) {
            http_response_code(422);
            echo json_encode(['error' => 'Validation failed.', 'fields' => $errors]);
            return;
        }
        if ($payload === []) {
            http_response_code(400);
            echo json_encode(['error' => 'Nothing to update.']);
            return;
        }

        Sku::update($code, $payload);

        $fresh = Sku::findByCode($code);
        if ($fresh === null) {
            http_response_code(500);
            echo json_encode(['error' => 'Update succeeded but SKU could not be re-read.']);
            return;
        }
        echo json_encode($this->formatRow($fresh), JSON_THROW_ON_ERROR);
    }

    public function export(): void
    {
        $filters = [
            'q'           => isset($_GET['q']) ? (string) $_GET['q'] : '',
            'category_id' => isset($_GET['c']) ? (int)    $_GET['c'] : 0,
            'limit'       => 500,
        ];
        $rows = Sku::search($filters);

        $filename = 'decko-skus-' . date('Y-m-d-Hi') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        // BOM so Excel detects UTF-8 cleanly.
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Category', 'SKU', 'Description', 'Variant',
            'Internal Barcode', 'GTIN/EAN', 'Box Barcode', 'Units / Box',
            'Unit L (cm)', 'Unit W (cm)', 'Unit H (cm)', 'Unit Weight (kg)', 'Unit Volume (cm³)',
            'Box L (cm)',  'Box W (cm)',  'Box H (cm)',  'Box Weight (kg)',  'Box Volume (cm³)',
            'Unit L (in)', 'Unit W (in)', 'Unit H (in)', 'Unit Weight (lb)', 'Unit Volume (in³)',
            'Box L (in)',  'Box W (in)',  'Box H (in)',  'Box Weight (lb)',  'Box Volume (in³)',
        ]);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['category_code'] ?? '',
                $r['sku_code']         ?? '',
                $r['description']      ?? '',
                $r['variant_label']    ?? '',
                $r['internal_barcode'] ?? '',
                $r['gtin_barcode']     ?? '',
                $r['box_barcode']      ?? '',
                $r['units_per_box']    ?? '',
                $r['unit_length_cm']   ?? '',
                $r['unit_width_cm']    ?? '',
                $r['unit_height_cm']   ?? '',
                $r['unit_weight_kg']   ?? '',
                $r['unit_volume_cm3']  ?? '',
                $r['box_length_cm']    ?? '',
                $r['box_width_cm']     ?? '',
                $r['box_height_cm']    ?? '',
                $r['box_weight_kg']    ?? '',
                $r['box_volume_cm3']   ?? '',
                $r['unit_length_in']   ?? '',
                $r['unit_width_in']    ?? '',
                $r['unit_height_in']   ?? '',
                $r['unit_weight_lb']   ?? '',
                $r['unit_volume_in3']  ?? '',
                $r['box_length_in']    ?? '',
                $r['box_width_in']     ?? '',
                $r['box_height_in']    ?? '',
                $r['box_weight_lb']    ?? '',
                $r['box_volume_in3']   ?? '',
            ]);
        }
        fclose($out);
    }

    /**
     * Stream a photo from /storage/photos — never expose the storage folder
     * directly, even when behind auth.
     */
    public function photo(string $file): void
    {
        // Strict whitelist of valid filename pattern. Reject anything with traversal characters.
        if (!preg_match('/^[A-Za-z0-9._-]+\.(png|jpg|jpeg|gif|webp)$/i', $file)) {
            http_response_code(400);
            return;
        }
        $path = Paths::photos($file);
        if (!is_file($path)) {
            http_response_code(404);
            return;
        }

        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'png'         => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            default       => 'application/octet-stream',
        };

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=86400');
        readfile($path);
    }

    /**
     * @param array<string,mixed> $r
     * @return array<string,mixed>
     */
    private function formatRow(array $r): array
    {
        $photoUrl = static function (?string $file): ?string {
            if ($file === null || $file === '') {
                return null;
            }
            return View::url('photos/' . $file);
        };

        $out = [
            'sku'           => $r['sku_code'],
            'description'   => $r['description'],
            'variant'       => $r['variant_label'],
            'category'      => [
                'code' => $r['category_code'] ?? null,
                'name' => $r['category_name'] ?? null,
            ],
            'barcodes'      => [
                'internal' => $r['internal_barcode'],
                'gtin'     => $r['gtin_barcode'],
                'box'      => $r['box_barcode'],
            ],
            'units_per_box' => $r['units_per_box'] !== null ? (int) $r['units_per_box'] : null,
            'unit_metric'   => [
                'length' => $r['unit_length_cm']  !== null ? (float) $r['unit_length_cm']  : null,
                'width'  => $r['unit_width_cm']   !== null ? (float) $r['unit_width_cm']   : null,
                'height' => $r['unit_height_cm']  !== null ? (float) $r['unit_height_cm']  : null,
                'weight' => $r['unit_weight_kg']  !== null ? (float) $r['unit_weight_kg']  : null,
                'volume' => $r['unit_volume_cm3'] !== null ? (float) $r['unit_volume_cm3'] : null,
            ],
            'unit_imperial' => [
                'length' => $r['unit_length_in']  !== null ? (float) $r['unit_length_in']  : null,
                'width'  => $r['unit_width_in']   !== null ? (float) $r['unit_width_in']   : null,
                'height' => $r['unit_height_in']  !== null ? (float) $r['unit_height_in']  : null,
                'weight' => $r['unit_weight_lb']  !== null ? (float) $r['unit_weight_lb']  : null,
                'volume' => $r['unit_volume_in3'] !== null ? (float) $r['unit_volume_in3'] : null,
            ],
            'box_metric'    => [
                'length' => $r['box_length_cm']  !== null ? (float) $r['box_length_cm']  : null,
                'width'  => $r['box_width_cm']   !== null ? (float) $r['box_width_cm']   : null,
                'height' => $r['box_height_cm']  !== null ? (float) $r['box_height_cm']  : null,
                'weight' => $r['box_weight_kg']  !== null ? (float) $r['box_weight_kg']  : null,
                'volume' => $r['box_volume_cm3'] !== null ? (float) $r['box_volume_cm3'] : null,
            ],
            'box_imperial'  => [
                'length' => $r['box_length_in']  !== null ? (float) $r['box_length_in']  : null,
                'width'  => $r['box_width_in']   !== null ? (float) $r['box_width_in']   : null,
                'height' => $r['box_height_in']  !== null ? (float) $r['box_height_in']  : null,
                'weight' => $r['box_weight_lb']  !== null ? (float) $r['box_weight_lb']  : null,
                'volume' => $r['box_volume_in3'] !== null ? (float) $r['box_volume_in3'] : null,
            ],
            'photos'        => [
                'product'           => $photoUrl($r['photo_product']           ?? null),
                'internal_barcode'  => $photoUrl($r['photo_internal_barcode']  ?? null),
                'gtin_barcode'      => $photoUrl($r['photo_gtin_barcode']      ?? null),
                'box'               => $photoUrl($r['photo_box']               ?? null),
                'box_barcode'       => $photoUrl($r['photo_box_barcode']       ?? null),
            ],
        ];

        return $out;
    }
}
