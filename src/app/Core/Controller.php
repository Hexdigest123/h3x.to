<?php

namespace App\Core;

class Controller
{
    public function model($model)
    {
        $resolved = realpath('../app/Models/' . $model . '.php');
        $modelsDir = realpath('../app/Models');

        if ($resolved === false || $modelsDir === false || strpos($resolved, $modelsDir . DIRECTORY_SEPARATOR) !== 0) {
            die('Invalid model.');
        }

        require_once $resolved;
        $modelClass = 'App\\Models\\' . basename($model);
        return new $modelClass();
    }

    public function view($view, $data = [])
    {
        $resolved = realpath('../app/Views/' . $view . '.php');
        $viewsDir = realpath('../app/Views');

        if ($resolved === false || $viewsDir === false || strpos($resolved, $viewsDir . DIRECTORY_SEPARATOR) !== 0) {
            die('Invalid view.');
        }

        extract($data);
        require $resolved;
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

    protected function generateCsrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    protected function csrfTokenField(): string
    {
        $token = $this->generateCsrfToken();
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    protected function verifyCsrfToken(): bool
    {
        $submitted = $_POST['_csrf_token'] ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';

        if ($stored === '' || $submitted === '') {
            return false;
        }

        $valid = hash_equals($stored, $submitted);

        if ($valid) {
            // Rotate: consume the token so it cannot be replayed.
            // A fresh token is generated on the next page load.
            unset($_SESSION['csrf_token']);
        }

        return $valid;
    }
}
