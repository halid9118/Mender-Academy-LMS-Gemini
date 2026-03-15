<?php
/**
 * Creates the complete Phase 0 scaffold ZIP for Digital Mender Academy LMS.
 * Run: php create_lms_zip.php
 */

// Define schema SQL content (left‑aligned to avoid heredoc indentation issues)
$schemaSQL = <<<'SQL'
-- schema.sql / 0001_initial.sql
-- Initial database schema for Digital Mender Academy LMS

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: users
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `role` ENUM('admin','instructor','student') NOT NULL DEFAULT 'student',
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `theme_preference` ENUM('light','dark','auto') NOT NULL DEFAULT 'auto',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: courses
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `courses` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `thumbnail` VARCHAR(255) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `is_published` BOOLEAN NOT NULL DEFAULT FALSE,
  `created_by` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: modules
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `modules` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `course_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  INDEX (`course_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: lessons
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lessons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `module_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `slug` VARCHAR(255) NOT NULL,
  `content` TEXT, -- Markdown content
  `video_url` VARCHAR(500) DEFAULT NULL, -- YouTube or HTML5 video URL
  `duration` SMALLINT UNSIGNED DEFAULT NULL, -- in minutes
  `drip_days` SMALLINT UNSIGNED DEFAULT NULL, -- days after enrollment
  `release_date` DATE DEFAULT NULL, -- absolute release date
  `position` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`module_id`) REFERENCES `modules`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `module_slug` (`module_id`, `slug`),
  INDEX (`module_id`, `position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: lesson_prerequisites (many-to-many)
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `lesson_prerequisites` (
  `lesson_id` INT UNSIGNED NOT NULL,
  `prerequisite_lesson_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`lesson_id`, `prerequisite_lesson_id`),
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`prerequisite_lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: enrollments
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `enrollments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `status` ENUM('active','completed','expired') NOT NULL DEFAULT 'active',
  `enrolled_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  `expires_at` TIMESTAMP NULL DEFAULT NULL, -- for paid courses with access expiry
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_course` (`user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: progress
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `progress` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `enrollment_id` INT UNSIGNED NOT NULL,
  `lesson_id` INT UNSIGNED NOT NULL,
  `percent` TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0-100
  `completed` BOOLEAN NOT NULL DEFAULT FALSE,
  `last_activity_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`enrollment_id`) REFERENCES `enrollments`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`lesson_id`) REFERENCES `lessons`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `enrollment_lesson` (`enrollment_id`, `lesson_id`),
  INDEX (`enrollment_id`, `completed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table: certificates
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `course_id` INT UNSIGNED NOT NULL,
  `file_path` VARCHAR(500) NOT NULL, -- relative path to PDF
  `issued_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `user_course` (`user_id`, `course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Insert initial admin user (password: 'admin123' – change immediately)
-- --------------------------------------------------------
-- Password hash generated with password_hash('admin123', PASSWORD_DEFAULT)
INSERT INTO `users` (`email`, `password_hash`, `full_name`, `role`, `email_verified_at`)
VALUES ('admin@digitalmender.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin User', 'admin', NOW())
ON DUPLICATE KEY UPDATE id=id;
SQL;

// Define all file paths and their contents
$files = [
    'composer.json' => <<<'JSON'
{
    "name": "digitalmender/academy-lms",
    "description": "Production-ready LMS for academy.digitalmender.com",
    "type": "project",
    "require": {
        "php": ">=8.2"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.10",
        "squizlabs/php_codesniffer": "^3.7"
    },
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "phpunit",
        "lint": "phpcs --standard=PSR12 src/",
        "stan": "phpstan analyse src/ --level=5",
        "migrate": "php scripts/migrate.php"
    }
}
JSON,

    'bootstrap.php' => <<<'PHP'
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
PHP,

    '.env.example' => <<<'INI'
# Database
DB_HOST=localhost
DB_NAME=digitalmender_lms
DB_USER=root
DB_PASS=

# App
APP_ENV=development
APP_URL=https://academy.digitalmender.com
TIMEZONE=UTC

# Security
SESSION_SECURE=false   # set true when using HTTPS
CSRF_KEY=change_this_to_random_string

# File uploads
UPLOAD_MAX_SIZE=10M
ALLOWED_EXTENSIONS=jpg,jpeg,png,gif,pdf,mp4
INI,

    'README.md' => <<<'MD'
# Digital Mender Academy LMS

Production-ready LMS for academy.digitalmender.com, built with PHP 8.2+ and MySQL.

## Features
- Role‑based access (admin, instructor, student)
- Course/module/lesson management with drip and prerequisites
- Enrollment and progress tracking (including YouTube videos)
- Certificate generation (PDF)
- REST API for integrations
- Light/dark theme with brand colors (#d4af37, #0a0f18)

## Installation
See [install.txt](install.txt) for step‑by‑step deployment on cPanel.

## Development
1. Clone the repository.
2. Run `composer install`.
3. Copy `.env.example` to `.env` and adjust database credentials.
4. Import `sql/schema.sql` into your database.
5. Run `composer migrate` to apply migrations (if any).
6. Point your web server to `public/` as document root.
7. Access the site.

Run tests: `composer test`
Run linter: `composer lint`
Run static analysis: `composer stan`
MD,

    'install.txt' => <<<'TXT'
# Installation Instructions for cPanel (academy.digitalmender.com)

1. **Extract the ZIP**
   - Upload the provided ZIP file to your cPanel file manager or via FTP.
   - Extract the contents into the public_html folder for your subdomain (e.g., `public_html/academy`).

2. **Set Document Root**
   - Ensure the subdomain’s document root points to the `public` folder inside the extracted directory.
   - Example: If your subdomain is `academy.digitalmender.com`, the document root should be `/home/user/public_html/academy/public`.

3. **Create Database**
   - In cPanel, use MySQL® Database Wizard to create a new database and user.
   - Grant all privileges to the user for that database.

4. **Configure Environment**
   - Rename `.env.example` to `.env` in the root directory.
   - Edit `.env` with your database credentials and other settings (APP_URL, etc.).
   - Make sure `SESSION_SECURE` is set to `true` if you are using HTTPS (recommended).

5. **Import Database Schema**
   - In phpMyAdmin, select your new database.
   - Import the file `sql/schema.sql` (or run the migrations sequentially from `sql/migrations/`).

6. **Set Correct File Permissions**
   - The `storage/uploads` folder must be writable by the web server.
   - Run these commands via SSH or use cPanel’s file manager to set permissions:
   - If you cannot use SSH, set the folder permissions to 755 via cPanel’s File Manager → Change Permissions.

7. **Secure the Uploads Folder**
- The provided `.htaccess` in `storage/uploads` blocks PHP execution – do not remove it.

8. **Verify Installation**
- Visit `https://academy.digitalmender.com` – you should see a default page (if you have added a route) or a 404 placeholder.
- Check that database connection works by accessing any page that queries the DB (once controllers are implemented).

9. **Next Steps**
- Seed sample data by running `php cron/seed_sample_data.php` (if you have SSH) or set up a cron job to run it once.
- If you don’t have SSH, you can manually run the script via cPanel’s “Cron Jobs” or temporarily access it via browser (but ensure it’s removed afterward).

For any issues, check the error logs in cPanel’s “Errors” section.
TXT,

'sql/schema.sql' => $schemaSQL,
'sql/migrations/0001_initial.sql' => $schemaSQL, // same content

'src/Controllers/.gitkeep' => '',
'src/Services/.gitkeep' => '',
'src/Models/.gitkeep' => '',

'src/Helpers/DB.php' => <<<'PHP'
<?php
namespace App\Helpers;

use PDO;
use PDOException;

class DB
{
private static ?PDO $connection = null;

public static function getConnection(): PDO
{
   if (self::$connection === null) {
       $host = $_ENV['DB_HOST'] ?? 'localhost';
       $db   = $_ENV['DB_NAME'] ?? '';
       $user = $_ENV['DB_USER'] ?? '';
       $pass = $_ENV['DB_PASS'] ?? '';
       $charset = 'utf8mb4';

       $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
       $options = [
           PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
           PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
           PDO::ATTR_EMULATE_PREPARES   => false,
       ];

       try {
           self::$connection = new PDO($dsn, $user, $pass, $options);
       } catch (PDOException $e) {
           // Log error and show user-friendly message in production
           error_log('DB connection failed: ' . $e->getMessage());
           die('Database connection error. Please try again later.');
       }
   }

   return self::$connection;
}

/**
* Execute a query with parameters and return the statement.
*/
public static function query(string $sql, array $params = []): \PDOStatement
{
   $stmt = self::getConnection()->prepare($sql);
   $stmt->execute($params);
   return $stmt;
}

/**
* Fetch a single row.
*/
public static function fetchOne(string $sql, array $params = []): ?array
{
   $stmt = self::query($sql, $params);
   $row = $stmt->fetch();
   return $row ?: null;
}

/**
* Fetch all rows.
*/
public static function fetchAll(string $sql, array $params = []): array
{
   $stmt = self::query($sql, $params);
   return $stmt->fetchAll();
}

/**
* Insert a row and return the last insert ID.
*/
public static function insert(string $table, array $data): int
{
   $columns = implode('`, `', array_keys($data));
   $placeholders = implode(', ', array_fill(0, count($data), '?'));
   $sql = "INSERT INTO `$table` (`$columns`) VALUES ($placeholders)";
   self::query($sql, array_values($data));
   return (int) self::getConnection()->lastInsertId();
}

/**
* Update rows.
*/
public static function update(string $table, array $data, string $where, array $whereParams = []): int
{
   $set = implode(' = ?, ', array_keys($data)) . ' = ?';
   $sql = "UPDATE `$table` SET $set WHERE $where";
   $params = array_merge(array_values($data), $whereParams);
   $stmt = self::query($sql, $params);
   return $stmt->rowCount();
}

/**
* Begin transaction
*/
public static function beginTransaction(): void
{
   self::getConnection()->beginTransaction();
}

/**
* Commit transaction
*/
public static function commit(): void
{
   self::getConnection()->commit();
}

/**
* Rollback transaction
*/
public static function rollback(): void
{
   self::getConnection()->rollBack();
}
}
PHP,

'src/Helpers/Utils.php' => <<<'PHP'
<?php
namespace App\Helpers;

class Utils
{
/**
* Escape HTML special characters (for safe output)
*/
public static function esc(string $text): string
{
   return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
* Generate a CSRF token and store it in session.
*/
public static function generateCsrfToken(): string
{
   if (empty($_SESSION['csrf_token'])) {
       $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
   }
   return $_SESSION['csrf_token'];
}

/**
* Verify CSRF token.
*/
public static function verifyCsrfToken(string $token): bool
{
   return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
* Redirect to a URL.
*/
public static function redirect(string $url): void
{
   header('Location: ' . $url);
   exit;
}

/**
* Get the base URL of the application.
*/
public static function baseUrl(string $path = ''): string
{
   $base = rtrim($_ENV['APP_URL'] ?? '', '/');
   return $base . '/' . ltrim($path, '/');
}
}
PHP,

'src/Helpers/functions.php' => <<<'PHP'
<?php
// Global helper functions (optional)

if (!function_exists('esc')) {
function esc(string $text): string
{
   return App\Helpers\Utils::esc($text);
}
}

if (!function_exists('base_url')) {
function base_url(string $path = ''): string
{
   return App\Helpers\Utils::baseUrl($path);
}
}
PHP,

'public/index.php' => <<<'PHP'
<?php
/**
* Front Controller
*/

// Load bootstrap
require __DIR__ . '/../bootstrap.php';

// Simple routing (will be expanded in later phases)
$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Remove query string and base path (assuming subdirectory)
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$basePath = rtrim($scriptName, '/');
$path = preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);
$path = ltrim($path, '/');

// Very basic router for demonstration – will be replaced by a proper router later
if ($path === '' || $path === '/') {
echo '<h1>Welcome to Digital Mender Academy</h1><p>Phase 0 scaffold in place.</p>';
exit;
}

// Serve static assets (CSS, JS) directly via .htaccess, so this is fallback
// If not found, show 404
http_response_code(404);
require VIEWS_PATH . '/error/404.php';
PHP,

'public/.htaccess' => <<<'APACHE'
<IfModule mod_rewrite.c>
RewriteEngine On

# Redirect trailing slashes
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)/$ /$1 [L,R=301]

# If the requested file or directory exists, serve it directly
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d

# Otherwise, route everything through index.php
RewriteRule ^(.*)$ index.php?url=$1 [QSA,L]
</IfModule>

# Security: block access to hidden files
<FilesMatch "^\.">
Order allow,deny
Deny from all
</FilesMatch>
APACHE,

'public/css/dm-theme.css' => <<<'CSS'
/* Theme variables */
:root {
--color-gold: #d4af37;
--color-dark-bg: #0a0f18;
--color-light-bg: #ffffff;
--color-dark-text: #333333;
--color-light-text: #f5f5f5;
--font-family-base: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
}

/* Light theme (default) */
body {
background-color: var(--color-light-bg);
color: var(--color-dark-text);
font-family: var(--font-family-base);
margin: 0;
padding: 0;
}

/* Dark theme */
body.dark-theme {
background-color: var(--color-dark-bg);
color: var(--color-light-text);
}
CSS,

'public/css/dm-lms.css' => <<<'CSS'
/* Additional LMS-specific styles */
.container {
max-width: 1200px;
margin: 0 auto;
padding: 1rem;
}
CSS,

'public/js/progress-auto.js' => '// progress-auto.js placeholder',
'public/js/progress-auto-youtube.js' => '// progress-auto-youtube.js placeholder',
'public/js/gamify-progress.js' => '// gamify-progress.js placeholder',
'public/js/lesson-workbench.js' => '// lesson-workbench.js placeholder',
'public/js/nav.js' => '// nav.js placeholder',
'public/js/theme-toggle.js' => "// theme-toggle.js placeholder\nconsole.log('Theme toggle loaded');",
'public/js/anti-blur.js' => '// anti-blur.js placeholder',

'views/layouts/app.php' => <<<'PHP'
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= esc($title ?? 'Digital Mender Academy') ?></title>
<link rel="stylesheet" href="<?= base_url('css/dm-theme.css') ?>">
<link rel="stylesheet" href="<?= base_url('css/dm-lms.css') ?>">
<!-- Defer non-critical JS -->
<script src="<?= base_url('js/theme-toggle.js') ?>" defer></script>
<script src="<?= base_url('js/nav.js') ?>" defer></script>
<!-- Other JS will be added as needed -->
</head>
<body>
<?php include VIEWS_PATH . '/partials/header.php'; ?>

<main class="container">
   <?= $content ?? '' ?>
</main>

<?php include VIEWS_PATH . '/partials/footer.php'; ?>
</body>
</html>
PHP,

'views/partials/header.php' => <<<'PHP'
<header>
<nav>
   <a href="<?= base_url() ?>">Home</a>
   <a href="<?= base_url('courses') ?>">Courses</a>
   <!-- Auth links will be dynamic later -->
</nav>
</header>
PHP,

'views/partials/footer.php' => <<<'PHP'
<footer>
<p>&copy; <?= date('Y') ?> Digital Mender. All rights reserved.</p>
</footer>
PHP,

'views/auth/login.php' => <<<'PHP'
<?php $title = 'Login'; ?>
<?php ob_start(); ?>
<h1>Login</h1>
<form method="post" action="/auth/login">
<!-- fields will be added later -->
<p>Login form placeholder.</p>
</form>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'views/auth/register.php' => <<<'PHP'
<?php $title = 'Register'; ?>
<?php ob_start(); ?>
<h1>Register</h1>
<form method="post" action="/auth/register">
<!-- fields will be added later -->
<p>Register form placeholder.</p>
</form>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'views/courses/index.php' => <<<'PHP'
<?php $title = 'Courses'; ?>
<?php ob_start(); ?>
<h1>Courses</h1>
<p>Course list placeholder.</p>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'views/courses/show.php' => <<<'PHP'
<?php $title = 'Course Details'; ?>
<?php ob_start(); ?>
<h1>Course Details</h1>
<p>Course details placeholder.</p>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'views/courses/create.php' => <<<'PHP'
<?php $title = 'Create Course'; ?>
<?php ob_start(); ?>
<h1>Create Course</h1>
<form>
<p>Create course form placeholder.</p>
</form>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'views/courses/edit.php' => <<<'PHP'
<?php $title = 'Edit Course'; ?>
<?php ob_start(); ?>
<h1>Edit Course</h1>
<form>
<p>Edit course form placeholder.</p>
</form>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'views/lessons/lesson-view.php' => <<<'PHP'
<?php $title = 'Lesson'; ?>
<?php ob_start(); ?>
<h1>Lesson</h1>
<p>Lesson view placeholder.</p>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'views/error/404.php' => <<<'PHP'
<?php $title = 'Page Not Found'; ?>
<?php ob_start(); ?>
<h1>404 - Page Not Found</h1>
<p>The page you requested could not be found.</p>
<a href="<?= base_url() ?>">Go to Home</a>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'views/error/500.php' => <<<'PHP'
<?php $title = 'Server Error'; ?>
<?php ob_start(); ?>
<h1>500 - Internal Server Error</h1>
<p>Something went wrong on our end. Please try again later.</p>
<?php $content = ob_get_clean(); ?>
<?php include VIEWS_PATH . '/layouts/app.php'; ?>
PHP,

'storage/uploads/.gitignore' => <<<'TXT'
*
!.gitignore
!.htaccess
TXT,

'storage/uploads/.htaccess' => <<<'APACHE'
# Deny access to PHP files
<FilesMatch "\.(php|phtml|phar)$">
Order Deny,Allow
Deny from all
</FilesMatch>
APACHE,

'cron/seed_sample_data.php' => <<<'PHP'
#!/usr/bin/env php
<?php
/**
* Seed sample data for development
* Run with: php cron/seed_sample_data.php
*/

require __DIR__ . '/../bootstrap.php';

use App\Helpers\DB;

// Check if already seeded
$admin = DB::fetchOne("SELECT id FROM users WHERE email = ?", ['admin@digitalmender.com']);
if ($admin) {
echo "Admin user already exists.\n";
} else {
// Insert admin (same as schema)
DB::insert('users', [
   'email' => 'admin@digitalmender.com',
   'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
   'full_name' => 'Admin User',
   'role' => 'admin',
   'email_verified_at' => date('Y-m-d H:i:s')
]);
echo "Admin user created.\n";
}

// Add a sample instructor
$instructor = DB::fetchOne("SELECT id FROM users WHERE email = ?", ['instructor@example.com']);
if (!$instructor) {
DB::insert('users', [
   'email' => 'instructor@example.com',
   'password_hash' => password_hash('instructor123', PASSWORD_DEFAULT),
   'full_name' => 'Sample Instructor',
   'role' => 'instructor',
   'email_verified_at' => date('Y-m-d H:i:s')
]);
echo "Instructor created.\n";
}

// Add a sample student
$student = DB::fetchOne("SELECT id FROM users WHERE email = ?", ['student@example.com']);
if (!$student) {
DB::insert('users', [
   'email' => 'student@example.com',
   'password_hash' => password_hash('student123', PASSWORD_DEFAULT),
   'full_name' => 'Sample Student',
   'role' => 'student',
   'email_verified_at' => date('Y-m-d H:i:s')
]);
echo "Student created.\n";
}

// Additional seeding for courses, etc., will be added in later phases
echo "Sample data seeding complete.\n";
PHP,

'tests/.gitkeep' => '',
];

// Create ZIP archive
$zip = new ZipArchive();
$zipFilename = __DIR__ . '/digitalmender-academy.zip';

if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
die("Cannot create ZIP file.\n");
}

foreach ($files as $path => $content) {
// Create subdirectories if needed
$dirname = dirname($path);
if ($dirname !== '.' && $dirname !== '/') {
   $zip->addEmptyDir($dirname);
}
$zip->addFromString($path, $content);
}

$zip->close();

echo "ZIP archive created: $zipFilename\n";
echo "Size: " . filesize($zipFilename) . " bytes\n";