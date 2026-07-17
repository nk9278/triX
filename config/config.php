<?php
/**
 * Core Configuration
 */

// Define absolute path to project root
define('BASE_PATH', dirname(__DIR__));

// Application Environment (development/production)
define('APP_ENV', 'development');

// Base URL (adjust depending on deployment environment)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . $domainName . '/');

// Security Settings
define('SESSION_LIFETIME', 86400); // 24 hours

// Error Reporting
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
}

// Database Credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'trix_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
