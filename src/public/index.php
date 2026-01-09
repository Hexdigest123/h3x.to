<?php

// Autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('App\\', '', $class);
    $class = str_replace('\\', '/', $class);
    $file = '../app/' . $class . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// Konfiguration laden
require_once '../config/config.php';

// Session starten
session_start();

// App initialisieren
use App\Core\App;

$app = new App();
