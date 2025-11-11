<?php

namespace App\Core;

class Controller
{
    public function model($model)
    {
        require_once '../app/Models/' . $model . '.php';
        $modelClass = 'App\\Models\\' . $model;
        return new $modelClass();
    }

    public function view($view, $data = [])
    {
        extract($data);

        if (file_exists('../app/Views/' . $view . '.php')) {
            require_once '../app/Views/' . $view . '.php';
        } else {
            die('View nicht gefunden: ' . $view);
        }
    }

    public function redirect($url)
    {
        header('Location: ' . BASE_URL . $url);
        exit();
    }

    public function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
