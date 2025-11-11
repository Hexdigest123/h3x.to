<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $data = [
            'title' => 'Willkommen',
            'description' => 'MVC Boilerplate in PHP'
        ];

        $this->view('home/index', $data);
    }

    public function about()
    {
        $data = [
            'title' => 'Über uns',
            'version' => APP_VERSION,
            'description' => 'MVC About Page'
        ];

        $this->view('home/about', $data);
    }
}
