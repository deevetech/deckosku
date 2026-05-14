<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Env;
use App\Core\Paths;
use App\Core\Router;
use App\Core\Session;
use App\Core\Setup;
use App\Core\View;

$basePath = dirname(__DIR__);
Paths::setBase($basePath);
Env::load($basePath);

// Production error handling.
if (Env::bool('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED);
}

ini_set('log_errors', '1');
ini_set('error_log', Paths::storage('logs/php-error.log'));

Session::start();
View::share('appName', (string) Env::get('APP_NAME', 'DECKO Size Management'));
View::share('csrfToken', Csrf::token());
View::share('user', Auth::user());
View::share('isAdmin', Auth::isAdmin());

// First-run installer gate: if the schema/admin isn't in place yet, force every
// non-setup request to the wizard. Static asset URLs are handled by Apache
// (.htaccess) / dev-router.php before this script runs, so we only see route
// requests here.
$requestPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/';
$normalisedPath = '/' . trim((string) $requestPath, '/');
$isSetupPath    = $normalisedPath === '/setup' || str_starts_with($normalisedPath, '/setup/');
if (!$isSetupPath && !Setup::isInstalled()) {
    header('Location: ' . View::url('setup'));
    exit;
}

$router = new Router();

// Named middleware.
$router->middleware('auth', static function (): void {
    if (!Auth::check()) {
        header('Location: ' . View::url('login'));
        exit;
    }
});
$router->middleware('guest', static function (): void {
    if (Auth::check()) {
        header('Location: ' . View::url(''));
        exit;
    }
});
$router->middleware('csrf', static function (): void {
    Csrf::assertFromRequest();
});

// First-run installer (lock-on-success guard lives inside the controller).
$router->get('/setup',  [App\Controllers\SetupController::class, 'show']);
$router->post('/setup', [App\Controllers\SetupController::class, 'install']);

// Public routes.
$router->get('/login',  [App\Controllers\AuthController::class, 'showLogin'],  ['guest']);
$router->post('/login', [App\Controllers\AuthController::class, 'login'],     ['guest', 'csrf']);
$router->post('/logout',[App\Controllers\AuthController::class, 'logout'],    ['auth',  'csrf']);

// Dashboard (protected).
$router->get('/',                 [App\Controllers\DashboardController::class, 'index'],     ['auth']);
$router->get('/api/skus',         [App\Controllers\DashboardController::class, 'apiSkus'],   ['auth']);
$router->get('/api/sku/{code}',   [App\Controllers\DashboardController::class, 'apiDetail'], ['auth']);
$router->post('/api/sku/{code}',  [App\Controllers\DashboardController::class, 'apiUpdate'], ['auth']);
$router->get('/export.csv',       [App\Controllers\DashboardController::class, 'export'],    ['auth']);
$router->get('/photos/{file}',    [App\Controllers\DashboardController::class, 'photo'],     ['auth']);

// Admin / re-import.
$router->get('/admin/import',     [App\Controllers\ImportController::class, 'show'],     ['auth']);
$router->post('/admin/import',    [App\Controllers\ImportController::class, 'upload'],   ['auth', 'csrf']);
$router->post('/admin/reimport',  [App\Controllers\ImportController::class, 'rerun'],    ['auth', 'csrf']);

// Users (admin only — enforced inside the controller).
$router->get('/admin/users',              [App\Controllers\UsersController::class, 'index'],      ['auth']);
$router->get('/admin/users/new',          [App\Controllers\UsersController::class, 'showCreate'], ['auth']);
$router->post('/admin/users',             [App\Controllers\UsersController::class, 'create'],     ['auth', 'csrf']);
$router->get('/admin/users/{id}',         [App\Controllers\UsersController::class, 'showEdit'],   ['auth']);
$router->post('/admin/users/{id}',        [App\Controllers\UsersController::class, 'update'],     ['auth', 'csrf']);
$router->post('/admin/users/{id}/delete', [App\Controllers\UsersController::class, 'delete'],     ['auth', 'csrf']);

// Tools.
$router->get('/tools/calculator', [App\Controllers\ToolsController::class, 'showCalculator'], ['auth']);

// Labels + spec sheets (print).
$router->get('/admin/labels',    [App\Controllers\LabelsController::class, 'showLabels'], ['auth']);
$router->get('/sku/{code}/spec', [App\Controllers\LabelsController::class, 'showSpec'],   ['auth']);

// Self-service password change.
$router->get('/me/password',  [App\Controllers\AuthController::class, 'showPassword'],   ['auth']);
$router->post('/me/password', [App\Controllers\AuthController::class, 'updatePassword'], ['auth', 'csrf']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
