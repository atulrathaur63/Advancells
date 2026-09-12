<?php
/**
 * Advancells HRMS - Configuration File
 */

// Environment (.env) Loader & Helper
if (!function_exists('load_env_file')) {
    function load_env_file(string $path): void {
        if (!file_exists($path) || !is_readable($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || str_starts_with($line, '//')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Strip surrounding single or double quotes
            if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
                $value = $matches[2];
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// Automatically load .env if present in project root
load_env_file(dirname(__DIR__) . '/.env');

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = getenv($key);
        if ($val !== false) {
            return $val;
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return $default;
    }
}

// Application settings
define('APP_NAME', env('APP_NAME', 'Advancells HRMS'));
define('APP_VERSION', '1.0.0');
define('COMPANY_NAME', env('COMPANY_NAME', 'Advancells Group'));
define('COMPANY_TAGLINE', 'Human Resource Management System');
define('COMPANY_ADDRESS', 'A-102 Sector 5, Noida, NCR, India');
define('COMPANY_EMAIL', 'info@advancells.com');
define('COMPANY_PHONE', '+91-9654321400');
define('CURRENCY_SYMBOL', '₹');

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Base URL detection
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/advancells/index.php';
$baseDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
if (empty($baseDir) || $baseDir === '/') {
    $baseDir = '/advancells';
}
define('BASE_URL', $protocol . $host . $baseDir);
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

// Database configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3306'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'advancells_hrms'));

// Environment: 'development' (shows demo logins & helpful debugs) or 'production'
define('APP_ENV', env('APP_ENV', 'development'));

// Session idle timeout in seconds (1800 = 30 minutes)
define('SESSION_IDLE_TIMEOUT', (int)env('SESSION_IDLE_TIMEOUT', 1800));

// Session configuration
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}
