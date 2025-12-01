<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Detect Laravel Application Path
|--------------------------------------------------------------------------
|
| This supports both standard Laravel installation and cPanel shared hosting
| where public files are in public_html and app files are one level up.
|
| Standard:     /var/www/app/public/index.php    → /var/www/app/
| cPanel:       /home/user/public_html/index.php → /home/user/portfolio-app/
|
*/

// Check if we're in a split installation (cPanel shared hosting)
// The app path can be configured via LARAVEL_APP_PATH environment or auto-detected
$appPath = null;

// Option 1: Check for environment variable
if (getenv('LARAVEL_APP_PATH')) {
    $appPath = getenv('LARAVEL_APP_PATH');
}
// Option 2: Check for config file in public directory
elseif (file_exists(__DIR__ . '/app-path.php')) {
    $appPath = require __DIR__ . '/app-path.php';
}
// Option 3: Check common cPanel structure (../portfolio-app/)
elseif (file_exists(__DIR__ . '/../portfolio-app/bootstrap/app.php')) {
    $appPath = __DIR__ . '/../portfolio-app';
}
// Option 4: Standard Laravel structure
else {
    $appPath = __DIR__ . '/..';
}

// Normalize the path
$appPath = rtrim($appPath, '/');

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $appPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $appPath . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $appPath . '/bootstrap/app.php';

$app->handleRequest(Request::capture());
