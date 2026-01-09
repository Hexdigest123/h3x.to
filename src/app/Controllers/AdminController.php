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

    private function getConfiguredAdmin(): array
    {
        return [
            'username' => trim(getenv('ADMIN_USERNAME') ?: (defined('ADMIN_USERNAME') ? ADMIN_USERNAME : '')),
            'password' => (string) (getenv('ADMIN_PASSWORD') ?: (defined('ADMIN_PASSWORD') ? ADMIN_PASSWORD : '')),
            'password_hash' => (string) (getenv('ADMIN_PASSWORD_HASH') ?: (defined('ADMIN_PASSWORD_HASH') ? ADMIN_PASSWORD_HASH : '')),
        ];
    }

    private function passwordMatches(string $provided, array $adminConfig): bool
    {
        $configuredHash = $adminConfig['password_hash'] ?? '';
        $configuredPlain = $adminConfig['password'] ?? '';

        if ($configuredHash !== '') {
            return password_verify($provided, $configuredHash);
        }

        if ($configuredPlain === '') {
            return false;
        }

        // Timing-safe compare using hashes so the plain password itself is not kept in memory for long.
        return hash_equals(
            hash('sha256', $configuredPlain),
            hash('sha256', $provided)
        );
    }

    private function slugify(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value);
        return trim($value, '-') ?: 'entry-' . time();
    }

    private function sanitizeText(?string $value): string
    {
        return trim(filter_var($value ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS));
    }

    private function sanitizeHtml(?string $value): string
    {
        // Only allow a handful of safe tags since stored HTML is rendered on the frontend.
        $clean = strip_tags($value ?? '', '<p><br><strong><em><ul><ol><li><a><code><pre><blockquote>');
        // Remove obvious script protocols.
        $clean = preg_replace('/javascript:/i', '', $clean);
        return trim($clean);
    }

    private function sanitizeUrl(?string $value): string
    {
        return trim(filter_var($value ?? '', FILTER_SANITIZE_URL));
    }

    private function normalizeCategory(string $value): string
    {
        $category = strtolower($value);
        return $category === 'projects' ? 'Projects' : 'Bugs';
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type][] = $message;
    }

    private function consumeFlash(): array
    {
        $flash = $_SESSION['flash'] ?? ['success' => [], 'error' => []];
        unset($_SESSION['flash']);
        return $flash;
    }

    private function ensureAdminAccount(): ?object
    {
        $adminConfig = $this->getConfiguredAdmin();
        $username = $adminConfig['username'] ?? '';
        $password = $adminConfig['password'] ?? '';
        $hash = $adminConfig['password_hash'] ?? '';

        if ($username === '' && $hash === '' && $password === '') {
            return null;
        }

        $passwordHash = $hash !== ''
            ? $hash
            : password_hash($password, PASSWORD_DEFAULT);

        /** @var User $userModel */
        $userModel = $this->model('User');
        return $userModel->upsertAdmin($username, $passwordHash);
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
        $adminAccount = $this->ensureAdminAccount();
        $users = [];
        if ($adminAccount) {
            $users[] = $adminAccount;
        }
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
            'allPosts' => $allPosts,
            'users' => $users,
            'recentPosts' => array_slice($allPosts, 0, 5),
            'currentUser' => $_SESSION['admin_user'],
            'visitorSummary' => $visitorSummary,
            'recentSessions' => $recentSessions,
            'topBrowsers' => $topBrowsers,
            'topCountries' => $topCountries,
            'flash' => $this->consumeFlash(),
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
            $name = $this->sanitizeText($_POST['name'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $adminConfig = $this->getConfiguredAdmin();
            $configuredName = $adminConfig['username'] ?? '';

            if ($name === '' || $password === '' || $configuredName === '') {
                $errors[] = 'Please provide both name and password.';
            } elseif (
                hash_equals(strtolower($configuredName), strtolower($name)) &&
                $this->passwordMatches($password, $adminConfig)
            ) {
                $adminAccount = $this->ensureAdminAccount();
                session_regenerate_id(true);
                $_SESSION['admin_user'] = [
                    'id' => $adminAccount->id ?? null,
                    'name' => $configuredName,
                    'email' => $adminAccount->email ?? null,
                ];

                $this->redirect('admin');
            } else {
                $errors[] = 'Invalid credentials. Check the name and password.';
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

    public function createPost(): void
    {
        $this->requireLogin();
        $adminAccount = $this->ensureAdminAccount();
        $title = $this->sanitizeText($_POST['title'] ?? '');
        $slugInput = $this->sanitizeText($_POST['slug'] ?? '');
        $category = $this->normalizeCategory($_POST['category'] ?? 'projects');
        $shortDescription = $this->sanitizeText($_POST['short_description'] ?? '');
        $description = $this->sanitizeText($_POST['description'] ?? '');
        $html = $this->sanitizeHtml($_POST['html'] ?? '');
        $isPublic = isset($_POST['is_public']) && filter_var($_POST['is_public'], FILTER_VALIDATE_BOOL);
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);

        if ($title === '' || $html === '' || !$adminAccount) {
            $this->flash('error', 'Title, content, and admin account are required to create a post.');
            $this->redirect('admin');
        }

        $publishedAt = $isPublic ? date('c') : null;

        /** @var BlogPost $blogModel */
        $blogModel = $this->model('BlogPost');
        $created = $blogModel->createPost([
            'title' => $title,
            'slug' => $slug,
            'category' => $category,
            'short_description' => $shortDescription,
            'description' => $description,
            'html' => $html,
            'is_public' => $isPublic,
            'author_id' => $adminAccount->id,
            'published_at' => $publishedAt,
        ]);

        if ($created) {
            $this->flash('success', 'Post created successfully.');
        } else {
            $this->flash('error', 'Unable to create the post. Please try again.');
        }

        $this->redirect('admin');
    }

    public function updatePost($id): void
    {
        $this->requireLogin();
        $postId = filter_var($id, FILTER_VALIDATE_INT);
        $title = $this->sanitizeText($_POST['title'] ?? '');
        $slugInput = $this->sanitizeText($_POST['slug'] ?? '');
        $category = $this->normalizeCategory($_POST['category'] ?? 'projects');
        $shortDescription = $this->sanitizeText($_POST['short_description'] ?? '');
        $description = $this->sanitizeText($_POST['description'] ?? '');
        $html = $this->sanitizeHtml($_POST['html'] ?? '');
        $isPublic = isset($_POST['is_public']) && filter_var($_POST['is_public'], FILTER_VALIDATE_BOOL);
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);
        $publishedAt = $isPublic ? date('c') : null;

        if (!$postId || $title === '' || $html === '') {
            $this->flash('error', 'Valid post data is required for updates.');
            $this->redirect('admin');
        }

        /** @var BlogPost $blogModel */
        $blogModel = $this->model('BlogPost');
        $updated = $blogModel->updatePost($postId, [
            'title' => $title,
            'slug' => $slug,
            'category' => $category,
            'short_description' => $shortDescription,
            'description' => $description,
            'html' => $html,
            'is_public' => $isPublic,
            'published_at' => $publishedAt,
        ]);

        if ($updated) {
            $this->flash('success', 'Post updated successfully.');
        } else {
            $this->flash('error', 'Unable to update the post.');
        }

        $this->redirect('admin');
    }

    public function deletePost($id): void
    {
        $this->requireLogin();
        $postId = filter_var($id, FILTER_VALIDATE_INT);

        if (!$postId) {
            $this->flash('error', 'Invalid post identifier.');
            $this->redirect('admin');
        }

        /** @var BlogPost $blogModel */
        $blogModel = $this->model('BlogPost');
        $deleted = $blogModel->deletePost($postId);

        if ($deleted) {
            $this->flash('success', 'Post deleted.');
        } else {
            $this->flash('error', 'Unable to delete the post.');
        }

        $this->redirect('admin');
    }

    public function createLink(): void
    {
        $this->requireLogin();
        $name = $this->sanitizeText($_POST['name'] ?? '');
        $url = $this->sanitizeUrl($_POST['url'] ?? '');
        $iconPath = $this->sanitizeUrl($_POST['icon_path'] ?? '');
        $displayOrder = filter_var($_POST['display_order'] ?? 0, FILTER_VALIDATE_INT) ?? 0;
        $isActive = isset($_POST['is_active']) && filter_var($_POST['is_active'], FILTER_VALIDATE_BOOL);

        if ($name === '' || $url === '') {
            $this->flash('error', 'Link name and URL are required.');
            $this->redirect('admin');
        }

        /** @var SocialLink $socialLinkModel */
        $socialLinkModel = $this->model('SocialLink');
        $created = $socialLinkModel->createLink([
            'name' => $name,
            'url' => $url,
            'icon_path' => $iconPath !== '' ? $iconPath : '/images/url.svg',
            'display_order' => $displayOrder,
            'is_active' => $isActive,
        ]);

        if ($created) {
            $this->flash('success', 'Link created successfully.');
        } else {
            $this->flash('error', 'Unable to create the link.');
        }

        $this->redirect('admin');
    }

    public function updateLink($id): void
    {
        $this->requireLogin();
        $linkId = filter_var($id, FILTER_VALIDATE_INT);
        $name = $this->sanitizeText($_POST['name'] ?? '');
        $url = $this->sanitizeUrl($_POST['url'] ?? '');
        $iconPath = $this->sanitizeUrl($_POST['icon_path'] ?? '');
        $displayOrder = filter_var($_POST['display_order'] ?? 0, FILTER_VALIDATE_INT) ?? 0;
        $isActive = isset($_POST['is_active']) && filter_var($_POST['is_active'], FILTER_VALIDATE_BOOL);

        if (!$linkId || $name === '' || $url === '') {
            $this->flash('error', 'Valid link data is required.');
            $this->redirect('admin');
        }

        /** @var SocialLink $socialLinkModel */
        $socialLinkModel = $this->model('SocialLink');
        $updated = $socialLinkModel->updateLink($linkId, [
            'name' => $name,
            'url' => $url,
            'icon_path' => $iconPath !== '' ? $iconPath : '/images/url.svg',
            'display_order' => $displayOrder,
            'is_active' => $isActive,
        ]);

        if ($updated) {
            $this->flash('success', 'Link updated.');
        } else {
            $this->flash('error', 'Unable to update the link.');
        }

        $this->redirect('admin');
    }

    public function deleteLink($id): void
    {
        $this->requireLogin();
        $linkId = filter_var($id, FILTER_VALIDATE_INT);

        if (!$linkId) {
            $this->flash('error', 'Invalid link identifier.');
            $this->redirect('admin');
        }

        /** @var SocialLink $socialLinkModel */
        $socialLinkModel = $this->model('SocialLink');
        $deleted = $socialLinkModel->deleteLink($linkId);

        if ($deleted) {
            $this->flash('success', 'Link deleted.');
        } else {
            $this->flash('error', 'Unable to delete the link.');
        }

        $this->redirect('admin');
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
