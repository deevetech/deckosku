<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;

/**
 * ToolsController — utility pages built around the catalogue.
 *
 * Currently hosts the container-fit calculator. Future utilities (pallet stack,
 * mixed-SKU optimiser, etc.) belong here too.
 */
final class ToolsController
{
    public function showCalculator(): void
    {
        $rows = Database::connection()->query(
            'SELECT sku_code, description,
                    unit_length_cm, unit_width_cm, unit_height_cm, unit_weight_kg,
                    box_length_cm,  box_width_cm,  box_height_cm,  box_weight_kg,
                    units_per_box
             FROM skus
             WHERE box_length_cm > 0 AND box_width_cm > 0 AND box_height_cm > 0
             ORDER BY sku_code'
        )->fetchAll();

        $payload = array_map(static function (array $r): array {
            return [
                'sku'         => $r['sku_code'],
                'name'        => $r['description'],
                'unitsPerBox' => $r['units_per_box'] !== null ? (int) $r['units_per_box'] : null,
                'unit'        => [
                    'L' => $r['unit_length_cm'] !== null ? (float) $r['unit_length_cm'] : null,
                    'W' => $r['unit_width_cm']  !== null ? (float) $r['unit_width_cm']  : null,
                    'H' => $r['unit_height_cm'] !== null ? (float) $r['unit_height_cm'] : null,
                    'kg' => $r['unit_weight_kg'] !== null ? (float) $r['unit_weight_kg'] : null,
                ],
                'box'         => [
                    'L' => $r['box_length_cm'] !== null ? (float) $r['box_length_cm'] : null,
                    'W' => $r['box_width_cm']  !== null ? (float) $r['box_width_cm']  : null,
                    'H' => $r['box_height_cm'] !== null ? (float) $r['box_height_cm'] : null,
                    'kg' => $r['box_weight_kg'] !== null ? (float) $r['box_weight_kg'] : null,
                ],
            ];
        }, $rows);

        echo View::render('tools/calculator', ['skus' => $payload]);
    }
}
