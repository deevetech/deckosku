<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class Sku
{
    /**
     * Search the catalogue. Filters are optional and combine with AND.
     *
     * @param array{q?:?string,category_id?:?int,limit?:int,offset?:int} $filters
     * @return array<int,array<string,mixed>>
     */
    public static function search(array $filters = []): array
    {
        $q          = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : 0;
        $limit      = isset($filters['limit'])  ? max(1, min(500, (int) $filters['limit'])) : 500;
        $offset     = isset($filters['offset']) ? max(0, (int) $filters['offset']) : 0;

        $sql = 'SELECT s.*, c.code AS category_code, c.name AS category_name
                FROM skus s
                LEFT JOIN categories c ON c.id = s.category_id
                WHERE 1=1';
        $params = [];

        if ($q !== '') {
            $sql .= ' AND s.search_haystack LIKE :q';
            $params[':q'] = '%' . strtolower($q) . '%';
        }
        if ($categoryId > 0) {
            $sql .= ' AND s.category_id = :cid';
            $params[':cid'] = $categoryId;
        }

        $sql .= ' ORDER BY c.sort_order, c.code, s.sku_code LIMIT :limit OFFSET :offset';

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * @param array{q?:?string,category_id?:?int} $filters
     */
    public static function count(array $filters = []): int
    {
        $q          = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $categoryId = isset($filters['category_id']) ? (int) $filters['category_id'] : 0;

        $sql    = 'SELECT COUNT(*) FROM skus s WHERE 1=1';
        $params = [];

        if ($q !== '') {
            $sql .= ' AND s.search_haystack LIKE :q';
            $params[':q'] = '%' . strtolower($q) . '%';
        }
        if ($categoryId > 0) {
            $sql .= ' AND s.category_id = :cid';
            $params[':cid'] = $categoryId;
        }

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function findByCode(string $code): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT s.*, c.code AS category_code, c.name AS category_name
             FROM skus s
             LEFT JOIN categories c ON c.id = s.category_id
             WHERE s.sku_code = :code LIMIT 1'
        );
        $stmt->execute([':code' => $code]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function allCategories(): array
    {
        $stmt = Database::connection()->query(
            'SELECT c.id, c.code, c.name, c.sort_order, COUNT(s.id) AS sku_count
             FROM categories c
             LEFT JOIN skus s ON s.category_id = c.id
             GROUP BY c.id
             ORDER BY c.sort_order, c.code'
        );
        return $stmt->fetchAll();
    }

    /**
     * Fields a user is allowed to edit through the dashboard. Photos and
     * sku_code are deliberately excluded — photos come from the workbook,
     * and renaming a primary key would break pin/recents in clients.
     *
     * @return array<int,string>
     */
    public static function editableFields(): array
    {
        return [
            'description', 'variant_label',
            'internal_barcode', 'gtin_barcode', 'box_barcode', 'units_per_box',
            'unit_length_cm',  'unit_width_cm',  'unit_height_cm',  'unit_weight_kg',  'unit_volume_cm3',
            'unit_length_in',  'unit_width_in',  'unit_height_in',  'unit_weight_lb',  'unit_volume_in3',
            'box_length_cm',   'box_width_cm',   'box_height_cm',   'box_weight_kg',   'box_volume_cm3',
            'box_length_in',   'box_width_in',   'box_height_in',   'box_weight_lb',   'box_volume_in3',
        ];
    }

    /**
     * Update an existing SKU and refresh its search haystack.
     *
     * @param array<string,mixed> $payload  Already-coerced values keyed by column name.
     */
    public static function update(string $code, array $payload): bool
    {
        $allowed = array_intersect_key($payload, array_flip(self::editableFields()));
        if ($allowed === []) {
            return false;
        }

        $pdo  = Database::connection();
        $current = self::findByCode($code);
        if ($current === null) {
            return false;
        }

        $sets = [];
        foreach (array_keys($allowed) as $col) {
            $sets[] = $col . ' = :' . $col;
        }
        $sets[] = 'updated_at = CURRENT_TIMESTAMP';

        // Rebuild the search haystack from the post-update values.
        $merged   = array_merge($current, $allowed);
        $haystack = strtolower(implode(' ', array_filter([
            $merged['sku_code'] ?? '',
            $merged['description'] ?? '',
            $merged['variant_label'] ?? '',
            $merged['internal_barcode'] ?? '',
            $merged['gtin_barcode'] ?? '',
            $merged['box_barcode'] ?? '',
        ], static fn ($v) => $v !== null && $v !== '')));
        $allowed['search_haystack'] = $haystack;
        $sets[] = 'search_haystack = :search_haystack';

        $sql  = 'UPDATE skus SET ' . implode(', ', $sets) . ' WHERE sku_code = :sku_code';
        $stmt = $pdo->prepare($sql);
        $allowed['sku_code'] = $code;
        foreach ($allowed as $col => $value) {
            $stmt->bindValue(':' . $col, $value);
        }
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    /**
     * @return array{total:int,categories:int,with_photos:int,last_import:?array<string,mixed>}
     */
    public static function summary(): array
    {
        $pdo = Database::connection();

        $total        = (int) $pdo->query('SELECT COUNT(*) FROM skus')->fetchColumn();
        $categories   = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
        $withPhotos   = (int) $pdo->query("SELECT COUNT(*) FROM skus WHERE photo_product IS NOT NULL AND photo_product != ''")->fetchColumn();

        $lastImport = $pdo->query('SELECT * FROM import_log ORDER BY id DESC LIMIT 1')->fetch();
        if ($lastImport === false) {
            $lastImport = null;
        }

        return [
            'total'       => $total,
            'categories'  => $categories,
            'with_photos' => $withPhotos,
            'last_import' => $lastImport,
        ];
    }
}
