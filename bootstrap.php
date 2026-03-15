<?php
/**
 * Bootstrap the application
 */

// Load Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Load environment configuration (simple .env parsing)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

// Set default timezone
date_default_timezone_set($_ENV['TIMEZONE'] ?? 'UTC');

// Start session (secure settings will be applied in session configuration later)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (adjust for production)
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Define base paths
define('BASE_PATH', __DIR__);
define('PUBLIC_PATH', __DIR__ . '/public');
define('STORAGE_PATH', __DIR__ . '/storage');
define('VIEWS_PATH', __DIR__ . '/views');

// Include helper functions
require_once __DIR__ . '/src/Helpers/functions.php';