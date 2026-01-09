<?php

namespace App\Core;

use App\Controllers\HomeController;
use App\Controllers\AdminController;
use App\Controllers\AnalyticsController;

class App
{
    protected $controller;
    protected $method = 'index';
    protected $params = [];

    // Map of allowed route names to controller classes (prevents file inclusion)
    protected const CONTROLLER_MAP = [
        'home' => HomeController::class,
        'admin' => AdminController::class,
        'analytics' => AnalyticsController::class,
    ];

    protected const DEFAULT_CONTROLLER = 'home';

    public function __construct()
    {
        $url = $this->parseUrl();

        // Controller lookup via whitelist map - no user input in file paths
        $controllerKey = self::DEFAULT_CONTROLLER;
        if (isset($url[0])) {
            $requestedKey = strtolower($url[0]);
            if (array_key_exists($requestedKey, self::CONTROLLER_MAP)) {
                $controllerKey = $requestedKey;
                unset($url[0]);
            }
        }

        // Instantiate controller from safe, hardcoded class map
        $controllerClass = self::CONTROLLER_MAP[$controllerKey];
        $this->controller = new $controllerClass();

        // Methode prüfen
        if (isset($url[1]) && method_exists($this->controller, $url[1])) {
            $this->method = $url[1];
            unset($url[1]);
        }

        // Parameter setzen
        $this->params = $url ? array_values($url) : [];

        // Controller-Methode mit Parametern aufrufen
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    protected function parseUrl()
    {
        if (isset($_GET['url'])) {
            $sanitized = filter_var($_GET['url'], FILTER_SANITIZE_URL);
            $trimmed = trim($sanitized, '/');

            if ($trimmed === '') {
                return [];
            }

            return explode('/', $trimmed);
        }
        return [];
    }
}
