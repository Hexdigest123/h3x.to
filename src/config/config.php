<?php

// Basis-URL: prefer env, otherwise infer from request, fallback to local dev
$envBaseUrl = getenv('APP_URL') ?: getenv('BASE_URL');

if (!$envBaseUrl && isset($_SERVER['HTTP_HOST'])) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        ? 'https'
        : 'http';
    $scheme = !empty($_SERVER['REQUEST_SCHEME']) ? $_SERVER['REQUEST_SCHEME'] : $scheme;
    $envBaseUrl = $scheme . '://' . $_SERVER['HTTP_HOST'];
}

$normalizedBaseUrl = rtrim($envBaseUrl ?: 'http://localhost:2001', '/') . '/';
define('BASE_URL', $normalizedBaseUrl);

// Datenbank-Konfiguration (PostgreSQL)
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_USER', getenv('DB_USER') ?: 'mvc_user');
define('DB_PASS', getenv('DB_PASS') ?: (getenv('DB_PASSWORD') ?: 'mvc_password'));
define('DB_NAME', getenv('DB_NAME') ?: 'mvc_db');
define('DB_PORT', getenv('DB_PORT') ?: '5432');

// Admin Konfiguration (Single account via environment)
define('ADMIN_USERNAME', getenv('ADMIN_USERNAME') ?: '');
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: '');
define('ADMIN_PASSWORD_HASH', getenv('ADMIN_PASSWORD_HASH') ?: '');

// App-Konfiguration
define('APP_NAME', 'H3X.TO MVC App');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development');

// Zeitzone
date_default_timezone_set('Europe/Berlin');

// Error Reporting (abhängig von Umgebung)
if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
