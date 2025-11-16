<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BlogPost;
use App\Models\SocialLink;
use App\Models\User;
use App\Models\VisitorAnalytics;

class AdminController extends Controller
{
    private function requireLogin(): void
    {
        if (empty($_SESSION['admin_user'])) {
            $this->redirect('admin/login');
        }
    }

    public function index(): void
    {
        $this->requireLogin();

        /** @var BlogPost $blogModel */
        $blogModel = $this->model('BlogPost');
        /** @var SocialLink $socialLinkModel */
        $socialLinkModel = $this->model('SocialLink');
        /** @var User $userModel */
        $userModel = $this->model('User');
        /** @var VisitorAnalytics $visitorAnalytics */
        $visitorAnalytics = $this->model('VisitorAnalytics');

        $allPosts = $blogModel->getAllPosts();
        $socialLinks = $socialLinkModel->getAllLinks();
        $users = $userModel->getAllUsers();
        $visitorSummary = $visitorAnalytics->sessionSummary();
        $recentSessions = $visitorAnalytics->recentSessions();
        $topBrowsers = $visitorAnalytics->topBrowsers();
        $topCountries = $visitorAnalytics->topCountries();

        $postStats = $this->summarizePosts($allPosts);
        $linkStats = $this->summarizeLinks($socialLinks);

        $navLinks = [
            ['label' => 'Welcome', 'href' => BASE_URL, 'icon' => BASE_URL . 'images/home.svg'],
            ['label' => 'Dashboard', 'href' => BASE_URL . 'admin', 'icon' => BASE_URL . 'images/admin.svg'],
            ['label' => 'Logout', 'href' => BASE_URL . 'admin/logout', 'icon' => BASE_URL . 'images/logout.svg'],
        ];

        $data = [
            'title' => 'Admin Dashboard',
            'description' => 'Private dashboard for h3x.to content signals.',
            'brand' => 'H3x Admin',
            'brandTagline' => 'signal room',
            'navLinks' => $navLinks,
            'postStats' => $postStats,
            'linkStats' => $linkStats,
            'socialLinks' => $socialLinks,
            'users' => $users,
            'recentPosts' => array_slice($allPosts, 0, 5),
            'currentUser' => $_SESSION['admin_user'],
            'visitorSummary' => $visitorSummary,
            'recentSessions' => $recentSessions,
            'topBrowsers' => $topBrowsers,
            'topCountries' => $topCountries,
        ];

        $this->view('admin/dashboard', $data);
    }

    public function login(): void
    {
        if (!empty($_SESSION['admin_user'])) {
            $this->redirect('admin');
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = trim($_POST['name'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if ($name === '' || $password === '') {
                $errors[] = 'Please provide both name and password.';
            } else {
                /** @var User $userModel */
                $userModel = $this->model('User');
                $user = $userModel->findByName($name);

                if ($user && password_verify($password, $user->password)) {
                    session_regenerate_id(true);
                    $_SESSION['admin_user'] = [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email ?? null,
                    ];

                    $this->redirect('admin');
                } else {
                    $errors[] = 'Invalid credentials. Check the name and password.';
                }
            }
        }

        $navLinks = [
            ['label' => 'Back to site', 'href' => BASE_URL, 'icon' => BASE_URL . 'images/home.svg'],
        ];

        $data = [
            'title' => 'Admin Login',
            'description' => 'Authenticate to access the h3x admin dashboard.',
            'brand' => 'H3x Admin',
            'brandTagline' => 'secured',
            'navLinks' => $navLinks,
            'errors' => $errors,
        ];

        $this->view('admin/login', $data);
    }

    public function logout(): void
    {
        unset($_SESSION['admin_user']);
        session_regenerate_id(true);
        $this->redirect('admin/login');
    }

    private function summarizePosts(array $posts): array
    {
        $totals = [
            'total' => count($posts),
            'public' => 0,
            'private' => 0,
            'categories' => [],
            'latest' => null,
        ];

        foreach ($posts as $post) {
            $isPublic = !empty($post->is_public);
            $totals['public'] += $isPublic ? 1 : 0;
            $totals['private'] += $isPublic ? 0 : 1;

            $category = strtolower($post->category ?? 'notes');
            $totals['categories'][$category] = ($totals['categories'][$category] ?? 0) + 1;

            $postDate = $post->published_at ?: $post->created_at;
            if (!empty($postDate)) {
                if (
                    empty($totals['latest']) ||
                    strtotime($postDate) > strtotime($totals['latest']->date)
                ) {
                    $totals['latest'] = (object) [
                        'title' => $post->title,
                        'category' => $category,
                        'date' => $postDate,
                        'status' => $isPublic ? 'Public' : 'Draft',
                    ];
                }
            }
        }

        return $totals;
    }

    private function summarizeLinks(array $links): array
    {
        $summary = [
            'total' => count($links),
            'active' => 0,
            'inactive' => 0,
        ];

        foreach ($links as $link) {
            if (!empty($link->is_active)) {
                $summary['active']++;
            } else {
                $summary['inactive']++;
            }
        }

        return $summary;
    }
}
