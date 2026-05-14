<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Env;
use App\Core\ExcelImporter;
use App\Core\Paths;
use App\Core\Setup;
use App\Core\View;
use App\Models\User;
use Throwable;

/**
 * SetupController — first-run installation wizard.
 *
 * Lifecycle:
 *   1. Fresh deploy → /setup loads, runs an environment check.
 *   2. Operator submits admin email/password + (optional) workbook upload.
 *   3. Wizard runs the schema migration, seeds the admin, then triggers the
 *      ExcelImporter on the uploaded file (or the configured EXCEL_PATH).
 *   4. After success the wizard self-locks (Setup::isInstalled() returns true)
 *      so a second POST cannot wipe data.
 */
final class SetupController
{
    public function show(): void
    {
        $error = null;
        $env   = $this->envSnapshot();

        if (!$env['db_reachable']) {
            $error = 'Database not reachable: ' . ($env['db_error'] ?? 'unknown error') .
                     ' — set DB_* values in .env on the server before continuing.';
        }

        echo View::render('setup/index', [
            'env'           => $env,
            'alreadyDone'   => Setup::isInstalled(),
            'error'         => $error,
            'flash'         => null,
        ], layout: 'blank');
    }

    public function install(): void
    {
        // Idempotency lock: never run twice. Stops accidental data wipes.
        if (Setup::isInstalled()) {
            header('Location: ' . View::url('login'));
            exit;
        }

        Csrf::assertFromRequest();

        $name     = trim((string) ($_POST['admin_name'] ?? ''));
        $email    = strtolower(trim((string) ($_POST['admin_email'] ?? '')));
        $password = (string) ($_POST['admin_password'] ?? '');
        $confirm  = (string) ($_POST['admin_password_confirm'] ?? '');

        $errors = $this->validate($name, $email, $password, $confirm);
        if ($errors !== []) {
            $this->renderWithErrors($errors);
            return;
        }

        // Optional xlsx upload. If absent, the importer will fall back to the
        // EXCEL_PATH value from .env (the workbook bundled with the repo).
        $uploadedXlsx = $this->handleUpload($errors);
        if ($errors !== []) {
            $this->renderWithErrors($errors);
            return;
        }

        $log = [];
        try {
            $this->runMigration($log);
            $this->seedAdmin($name, $email, $password, $log);
            $this->runImport($uploadedXlsx, $log);
        } catch (Throwable $e) {
            $log[] = 'ERROR: ' . $e->getMessage();
            error_log('[Setup] install failed: ' . $e->getMessage());
            $this->renderWithErrors([], implode("\n", $log));
            return;
        }

        echo View::render('setup/done', [
            'log'   => $log,
            'email' => $email,
        ], layout: 'blank');
    }

    /** @return array<string,string> */
    private function validate(string $name, string $email, string $password, string $confirm): array
    {
        $errors = [];

        if ($name === '' || mb_strlen($name) > 100) {
            $errors['admin_name'] = 'Name is required (max 100 characters).';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['admin_email'] = 'Enter a valid email address.';
        }

        if (mb_strlen($password) < 12) {
            $errors['admin_password'] = 'Password must be at least 12 characters.';
        }

        if ($password !== $confirm) {
            $errors['admin_password_confirm'] = 'Passwords do not match.';
        }

        return $errors;
    }

    /** @param array<string,string> &$errors */
    private function handleUpload(array &$errors): ?string
    {
        $file = $_FILES['workbook'] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            $errors['workbook'] = 'Upload failed (php error code ' . (int) $file['error'] . ').';
            return null;
        }

        $maxBytes = 50 * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            $errors['workbook'] = 'Workbook is larger than 50 MB.';
            return null;
        }

        $name = (string) ($file['name'] ?? '');
        if (!preg_match('/\.xlsx$/i', $name)) {
            $errors['workbook'] = 'Only .xlsx files are accepted.';
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file((string) $file['tmp_name']);
        $allowed = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/zip',
            'application/octet-stream',
        ];
        if (is_string($mime) && !in_array($mime, $allowed, true)) {
            $errors['workbook'] = 'File does not look like a valid .xlsx workbook (mime: ' . $mime . ').';
            return null;
        }

        $target = Paths::storage('uploads/setup-' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.xlsx');
        $dir    = dirname($target);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!move_uploaded_file((string) $file['tmp_name'], $target)) {
            $errors['workbook'] = 'Could not save the uploaded file. Check that storage/uploads is writable.';
            return null;
        }

        return $target;
    }

    /** @param array<int,string> &$log */
    private function runMigration(array &$log): void
    {
        $pdo    = Database::connection();
        $driver = Database::driver();

        $schemaPath = $driver === 'mysql'
            ? Paths::base('database/schema.mysql.sql')
            : Paths::base('database/schema.sql');

        $sql = file_get_contents($schemaPath);
        if ($sql === false) {
            throw new \RuntimeException("Cannot read schema file: {$schemaPath}");
        }

        if ($driver === 'mysql') {
            foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
                if ($stmt === '' || str_starts_with($stmt, '--')) {
                    continue;
                }
                $pdo->exec($stmt);
            }
        } else {
            $pdo->exec($sql);
        }

        $log[] = 'Schema applied (' . $driver . ').';
    }

    /** @param array<int,string> &$log */
    private function seedAdmin(string $name, string $email, string $password, array &$log): void
    {
        if (User::findByEmail($email) !== null) {
            $log[] = "Admin {$email} already exists — skipped re-creation.";
            return;
        }
        $id = User::create($name, $email, $password, 'admin');
        $log[] = "Admin user created (id={$id}, email={$email}).";
    }

    /** @param array<int,string> &$log */
    private function runImport(?string $uploadedXlsx, array &$log): void
    {
        $source = $uploadedXlsx ?? Paths::base((string) Env::get('EXCEL_PATH', 'DECKO Warehouse SKU and Barcode list.xlsx'));

        if (!is_file($source)) {
            $log[] = 'Workbook import skipped — no file uploaded and EXCEL_PATH is not present on disk.';
            return;
        }

        $importer = new ExcelImporter($source);
        $result   = $importer->run();

        $log[] = sprintf(
            'Workbook imported: %d rows, %d photos, %d ms.',
            (int) ($result['rows_imported'] ?? 0),
            (int) ($result['photos_saved']  ?? 0),
            (int) ($result['duration_ms']   ?? 0),
        );
    }

    /** @return array<string,mixed> */
    private function envSnapshot(): array
    {
        $driver = Database::driver();
        return [
            'driver'       => $driver,
            'db_reachable' => Setup::dbReachable(),
            'db_error'     => Setup::dbError(),
            'db_path'      => $driver === 'sqlite' ? (string) Env::get('DB_PATH', 'storage/database.sqlite') : null,
            'db_host'      => $driver === 'mysql'  ? (string) Env::get('DB_HOST', '127.0.0.1') : null,
            'db_name'      => $driver === 'mysql'  ? (string) Env::get('DB_NAME', 'decko_size') : null,
            'app_env'      => (string) Env::get('APP_ENV', 'local'),
            'app_url'      => (string) Env::get('APP_URL', ''),
        ];
    }

    /** @param array<string,string> $errors */
    private function renderWithErrors(array $errors = [], ?string $fatal = null): void
    {
        $env = $this->envSnapshot();
        echo View::render('setup/index', [
            'env'           => $env,
            'alreadyDone'   => Setup::isInstalled(),
            'error'         => $fatal,
            'fieldErrors'   => $errors,
            'old'           => [
                'admin_name'  => (string) ($_POST['admin_name']  ?? ''),
                'admin_email' => (string) ($_POST['admin_email'] ?? ''),
            ],
            'flash'         => null,
        ], layout: 'blank');
    }
}
