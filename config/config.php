<?php
/**
 * Advancells HRMS - Configuration File
 */

// Application settings
define('APP_NAME', 'Advancells HRMS');
define('APP_VERSION', '1.0.0');
define('COMPANY_NAME', 'Advancells Biotech');
define('COMPANY_TAGLINE', 'Human Resource Management System');
define('COMPANY_ADDRESS', 'Advancells Tower, Sector 62, Noida, NCR, India');
define('COMPANY_EMAIL', 'hr@advancells.com');
define('COMPANY_PHONE', '+91 120 456 7890');
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
define('BASE_PATH', dirname(__DIR__));

// Database configuration
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'advancells_hrms');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}
