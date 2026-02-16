<?php

// Autoloader
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') !== 0) {
        return;
    }

    $relative = str_replace('\\', '/', substr($class, 4));

    if (strpos($relative, '..') !== false) {
        return;
    }

    $file = realpath('../app/' . $relative . '.php');
    $baseDir = realpath('../app');

    if ($file !== false && $baseDir !== false && strpos($file, $baseDir . DIRECTORY_SEPARATOR) === 0) {
        require_once $file;
    }
});

require_once '../config/config.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] === 'https');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

// App initialisieren
use App\Core\App;

$app = new App();
