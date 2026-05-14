<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Core\ExcelImporter;
use App\Core\Paths;
use App\Core\Session;
use App\Core\View;
use App\Models\Sku;

final class ImportController
{
    public function show(): void
    {
        \App\Core\Auth::assertAdmin();
        echo View::render('admin/import', [
            'summary'    => Sku::summary(),
            'excelPath'  => Paths::base((string) Env::get('EXCEL_PATH', '')),
            'message'    => Session::flash('import_message'),
            'errorMessage' => Session::flash('import_error'),
        ]);
    }

    public function upload(): void
    {
        \App\Core\Auth::assertAdmin();
        if (!isset($_FILES['workbook']) || $_FILES['workbook']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('import_error', 'No file uploaded, or upload failed.');
            header('Location: ' . View::url('admin/import'));
            return;
        }

        $upload    = $_FILES['workbook'];
        $extension = strtolower(pathinfo((string) $upload['name'], PATHINFO_EXTENSION));
        if ($extension !== 'xlsx') {
            Session::flash('import_error', 'Only .xlsx files are accepted.');
            header('Location: ' . View::url('admin/import'));
            return;
        }

        // Move into the configured source location, backing up the old one.
        $envPath  = (string) Env::get('EXCEL_PATH', 'DECKO Warehouse SKU and Barcode list.xlsx');
        $absolute = Paths::base($envPath);
        if (is_file($absolute)) {
            $backup = $absolute . '.' . date('Ymd-His') . '.bak';
            @rename($absolute, $backup);
        }

        if (!move_uploaded_file($upload['tmp_name'], $absolute)) {
            Session::flash('import_error', 'Could not move uploaded file into place.');
            header('Location: ' . View::url('admin/import'));
            return;
        }

        $this->runImport();
        header('Location: ' . View::url('admin/import'));
    }

    public function rerun(): void
    {
        \App\Core\Auth::assertAdmin();
        $this->runImport();
        header('Location: ' . View::url('admin/import'));
    }

    private function runImport(): void
    {
        @ini_set('memory_limit', '1G');
        @set_time_limit(300);

        try {
            $importer = new ExcelImporter();
            $result   = $importer->run();
        } catch (\Throwable $e) {
            Session::flash('import_error', 'Import failed: ' . $e->getMessage());
            error_log('Import failed: ' . $e->getMessage());
            return;
        }

        Session::flash(
            'import_message',
            sprintf(
                'Import complete — %d SKUs, %d photos, %d ms.',
                $result['rows_imported'],
                $result['photos_saved'],
                $result['duration_ms'],
            )
        );
    }
}
