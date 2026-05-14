<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Models\Sku;

/**
 * LabelsController — print-ready label sheets and per-SKU spec sheets.
 *
 * The PDF output is produced by the browser's native print engine through
 * CSS `@page` rules — no PHP PDF library required. This keeps the dependency
 * footprint small and gives the user direct control over paper size and margins.
 */
final class LabelsController
{
    public function showLabels(): void
    {
        Auth::assertAdmin();

        $q          = isset($_GET['q'])    ? (string) $_GET['q'] : '';
        $categoryId = isset($_GET['c'])    ? (int)    $_GET['c'] : 0;
        $selected   = isset($_GET['skus']) ? array_filter(explode(',', (string) $_GET['skus'])) : [];
        $format     = isset($_GET['format']) ? (string) $_GET['format'] : '4x2';

        echo View::render('admin/labels', [
            'categories'   => Sku::allCategories(),
            'skus'         => Sku::search(['q' => $q, 'category_id' => $categoryId]),
            'selectedSkus' => $selected,
            'query'        => $q,
            'categoryId'   => $categoryId,
            'format'       => in_array($format, ['4x2', '3.5x1.5', '4x6'], true) ? $format : '4x2',
        ]);
    }

    public function showSpec(string $code): void
    {
        // Spec sheet is viewable by anyone signed in — it's the same data they can already see.
        $row = Sku::findByCode($code);
        if ($row === null) {
            http_response_code(404);
            return;
        }
        echo View::render('admin/spec-sheet', ['sku' => $row], layout: 'blank-print');
    }
}
