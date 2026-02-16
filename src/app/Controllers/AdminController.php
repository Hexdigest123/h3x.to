<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BlogPost;
use App\Models\SocialLink;
use App\Models\User;
use App\Models\VisitorAnalytics;

class AdminController extends Controller
{
    /**
     * Whitelist of icon filenames allowed for social links.
     * Only files physically present in public/images/ should appear here.
     */
    private const ALLOWED_ICONS = [
        'github.svg',
        'gitlab.svg',
        'url.svg',
    ];

    /**
     * Validate and normalise an icon path submitted from the form.
     *
     * Returns the safe `/images/<file>` path when the value is an allowed
     * filename, or the default fallback icon when it is not.
     */
    private function validateIconPath(?string $value): string
    {
        $filename = basename(trim($value ?? ''));

        if ($filename !== '' && in_array($filename, self::ALLOWED_ICONS, true)) {
            return '/images/' . $filename;
        }

        return '/images/url.svg';
    }

    private function requireLogin(): void
    {
        if (empty($_SESSION['admin_user'])) {
            $this->redirect('admin/login');
        }
    }

    private function requireCsrf(): void
    {
        if (!$this->verifyCsrfToken()) {
            $this->flash('error', 'Invalid or expired form token. Please try again.');
            $this->redirect('admin');
        }
    }

    private function loginAttemptsPath(string $ip): string
    {
        return sys_get_temp_dir() . '/h3x_login_' . hash('sha256', $ip) . '.json';
    }

    private function isLoginLocked(): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $path = $this->loginAttemptsPath($ip);

        if (!file_exists($path)) {
            return false;
        }

        $data = json_decode(@file_get_contents($path), true);
        if (!is_array($data)) {
            return false;
        }

        $attempts = $data['attempts'] ?? 0;
        $lastAttempt = $data['last_attempt'] ?? 0;

        if ($attempts >= self::LOGIN_MAX_ATTEMPTS) {
            if (time() - $lastAttempt < self::LOGIN_LOCKOUT_SECONDS) {
                return true;
            }
            @unlink($path);
        }

        return false;
    }

    private function recordFailedLogin(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $path = $this->loginAttemptsPath($ip);

        $data = ['attempts' => 0, 'last_attempt' => 0];
        if (file_exists($path)) {
            $existing = json_decode(@file_get_contents($path), true);
            if (is_array($existing)) {
                $data = $existing;
            }
        }

        $data['attempts'] = ($data['attempts'] ?? 0) + 1;
        $data['last_attempt'] = time();

        @file_put_contents($path, json_encode($data), LOCK_EX);
    }

    private function resetLoginAttempts(): void
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        @unlink($this->loginAttemptsPath($ip));
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
        return trim(htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    private const HTML_ALLOW = [
        'p' => [],
        'br' => [],
        'strong' => [],
        'em' => [],
        'ul' => [],
        'ol' => [],
        'li' => [],
        'a' => ['href'],
        'code' => [],
        'pre' => [],
        'blockquote' => [],
    ];

    private const SAFE_SCHEMES = ['http', 'https', 'mailto'];

    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 900;

    private function sanitizeHtml(?string $value): string
    {
        $raw = trim($value ?? '');
        if ($raw === '') {
            return '';
        }

        $allowedTagString = implode('', array_map(fn($t) => "<{$t}>", array_keys(self::HTML_ALLOW)));
        $clean = strip_tags($raw, $allowedTagString);

        $wrapped = '<div>' . $clean . '</div>';
        $doc = new \DOMDocument();
        @$doc->loadHTML(
            '<?xml encoding="UTF-8"><body>' . $wrapped . '</body>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
        );

        $this->scrubNode($doc->documentElement);

        $body = $doc->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }

        $html = '';
        foreach ($body->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }

        $html = preg_replace('/^<div>|<\/div>$/', '', trim($html));

        return trim($html);
    }

    private function scrubNode(\DOMNode $node): void
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
        foreach ($children as $child) {
            $this->scrubNode($child);
        }

        $tag = strtolower($node->nodeName);

        if (in_array($tag, ['html', 'body', 'div'], true)) {
            return;
        }

        if (!array_key_exists($tag, self::HTML_ALLOW)) {
            $fragment = $node->ownerDocument->createDocumentFragment();
            while ($node->firstChild) {
                $fragment->appendChild($node->firstChild);
            }
            $node->parentNode->replaceChild($fragment, $node);
            return;
        }

        $allowed = self::HTML_ALLOW[$tag];
        $toRemove = [];
        foreach ($node->attributes as $attr) {
            if (!in_array($attr->name, $allowed, true)) {
                $toRemove[] = $attr->name;
            }
        }
        foreach ($toRemove as $attrName) {
            $node->removeAttribute($attrName);
        }

        if ($node->hasAttribute('href')) {
            $href = trim($node->getAttribute('href'));
            $scheme = strtolower(parse_url($href, PHP_URL_SCHEME) ?? '');
            if ($scheme !== '' && !in_array($scheme, self::SAFE_SCHEMES, true)) {
                $node->removeAttribute('href');
            }
        }
    }

    private function sanitizeUrl(?string $value): string
    {
        $url = trim(filter_var($value ?? '', FILTER_SANITIZE_URL));

        if ($url === '') {
            return '';
        }

        $scheme = strtolower(parse_url($url, PHP_URL_SCHEME) ?? '');
        if ($scheme !== '' && !in_array($scheme, self::SAFE_SCHEMES, true)) {
            return '';
        }

        return $url;
    }

    private function normalizeCategory(string $value): ?string
    {
        $map = ['projects' => 'Projects', 'bugs' => 'Bugs'];
        return $map[strtolower($value)] ?? null;
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
        $adminAccount = $userModel->findByName($_SESSION['admin_user']['name'] ?? '');
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
            ['label' => 'Logout', 'href' => BASE_URL . 'admin/logout', 'icon' => BASE_URL . 'images/logout.svg', 'post' => true],
        ];

        $data = [
            'title' => 'Admin Dashboard',
            'description' => 'Private dashboard for h3x.to content signals.',
            'brand' => 'H3x Admin',
            'brandTagline' => 'signal room',
            'hideIntro' => true,
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
            'allowedIcons' => self::ALLOWED_ICONS,
            'csrfField' => $this->csrfTokenField(),
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
            if ($this->isLoginLocked()) {
                $errors[] = 'Too many failed attempts. Please wait before trying again.';
            } elseif (!$this->verifyCsrfToken()) {
                $errors[] = 'Invalid or expired form token. Please try again.';
            } else {
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
                    $this->resetLoginAttempts();
                    $adminAccount = $this->ensureAdminAccount();
                    session_regenerate_id(true);
                    $_SESSION['admin_user'] = [
                        'id' => $adminAccount->id ?? null,
                        'name' => $configuredName,
                        'email' => $adminAccount->email ?? null,
                    ];

                    $this->redirect('admin');
                } else {
                    $this->recordFailedLogin();
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
            'hideIntro' => true,
            'navLinks' => $navLinks,
            'csrfField' => $this->csrfTokenField(),
            'errors' => $errors,
        ];

        $this->view('admin/login', $data);
    }

    public function logout(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrfToken()) {
            $this->redirect('admin');
            return;
        }

        $_SESSION = [];
        session_destroy();

        session_start();
        session_regenerate_id(true);
        $this->redirect('admin/login');
    }

    public function createPost(): void
    {
        $this->requireLogin();
        $this->requireCsrf();
        $authorId = $_SESSION['admin_user']['id'] ?? null;
        $title = $this->sanitizeText($_POST['title'] ?? '');
        $slugInput = $this->sanitizeText($_POST['slug'] ?? '');
        $category = $this->normalizeCategory($_POST['category'] ?? 'projects');
        $shortDescription = $this->sanitizeText($_POST['short_description'] ?? '');
        $description = $this->sanitizeText($_POST['description'] ?? '');
        $html = $this->sanitizeHtml($_POST['html'] ?? '');
        $isPublic = isset($_POST['is_public']) && filter_var($_POST['is_public'], FILTER_VALIDATE_BOOL);
        $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);

        if ($title === '' || $html === '' || !$authorId || $category === null) {
            $this->flash('error', 'Title, content, valid category, and admin account are required to create a post.');
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
            'author_id' => $authorId,
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
        $this->requireCsrf();
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

        if (!$postId || $title === '' || $html === '' || $category === null) {
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
        $this->requireCsrf();
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
        $this->requireCsrf();
        $name = $this->sanitizeText($_POST['name'] ?? '');
        $url = $this->sanitizeUrl($_POST['url'] ?? '');
        $iconPath = $this->validateIconPath($_POST['icon_path'] ?? '');
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
            'icon_path' => $iconPath,
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
        $this->requireCsrf();
        $linkId = filter_var($id, FILTER_VALIDATE_INT);
        $name = $this->sanitizeText($_POST['name'] ?? '');
        $url = $this->sanitizeUrl($_POST['url'] ?? '');
        $iconPath = $this->validateIconPath($_POST['icon_path'] ?? '');
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
            'icon_path' => $iconPath,
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
        $this->requireCsrf();
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

    public function exportPosts(): void
    {
        $this->requireLogin();

        /** @var BlogPost $blogModel */
        $blogModel = $this->model('BlogPost');
        $posts = $blogModel->getAllPostsForExport();

        $exportData = [
            'version' => 1,
            'exported_at' => date('c'),
            'post_count' => count($posts),
            'posts' => array_map(function ($post) {
                return [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'category' => $post->category,
                    'short_description' => $post->short_description ?? '',
                    'description' => $post->description ?? '',
                    'html' => $post->html,
                    'is_public' => (bool) $post->is_public,
                    'created_at' => $post->created_at,
                    'published_at' => $post->published_at,
                ];
            }, $posts),
        ];

        $filename = 'blog-posts-export-' . date('Y-m-d') . '.json';
        $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        exit();
    }

    public function importPosts(): void
    {
        $this->requireLogin();
        $this->requireCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->flash('error', 'Invalid request method.');
            $this->redirect('admin');
            return;
        }

        $file = $_FILES['import_file'] ?? null;

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->flash('error', 'No file uploaded or upload failed. Please select a valid JSON file.');
            $this->redirect('admin');
            return;
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            $this->flash('error', 'File too large. Maximum size is 10 MB.');
            $this->redirect('admin');
            return;
        }

        $rawContent = file_get_contents($file['tmp_name']);
        if ($rawContent === false || trim($rawContent) === '') {
            $this->flash('error', 'Unable to read the uploaded file.');
            $this->redirect('admin');
            return;
        }

        $decoded = json_decode($rawContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->flash('error', 'Invalid JSON format: ' . json_last_error_msg());
            $this->redirect('admin');
            return;
        }

        // Accept both envelope format {"posts": [...]} and flat array [...]
        $posts = [];
        if (isset($decoded['posts']) && is_array($decoded['posts'])) {
            $posts = $decoded['posts'];
        } elseif (is_array($decoded) && !empty($decoded) && isset($decoded[0])) {
            $posts = $decoded;
        } else {
            $this->flash('error', 'JSON must contain a "posts" array or be a flat array of post objects.');
            $this->redirect('admin');
            return;
        }

        if (empty($posts)) {
            $this->flash('error', 'No posts found in the uploaded file.');
            $this->redirect('admin');
            return;
        }

        $authorId = $_SESSION['admin_user']['id'] ?? null;
        if (!$authorId) {
            $this->flash('error', 'Admin account is required to import posts.');
            $this->redirect('admin');
            return;
        }

        /** @var BlogPost $blogModel */
        $blogModel = $this->model('BlogPost');

        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($posts as $index => $postData) {
            $position = $index + 1;

            if (!is_array($postData)) {
                $errors[] = "Entry #{$position}: not a valid object.";
                $skipped++;
                continue;
            }

            $title = $this->sanitizeText($postData['title'] ?? '');
            $html = $this->sanitizeHtml($postData['html'] ?? '');

            if ($title === '' || $html === '') {
                $errors[] = "Entry #{$position}: title and html content are required.";
                $skipped++;
                continue;
            }

            $slugInput = $this->sanitizeText($postData['slug'] ?? '');
            $slug = $slugInput !== '' ? $this->slugify($slugInput) : $this->slugify($title);

            // Deduplicate slug: append -2, -3, etc. if slug exists
            $baseSlug = $slug;
            $suffix = 2;
            while ($blogModel->getPostBySlug($slug) !== null) {
                $slug = $baseSlug . '-' . $suffix;
                $suffix++;
            }

            $category = $this->normalizeCategory($postData['category'] ?? 'projects');
            if ($category === null) {
                $errors[] = "Entry #{$position}: invalid category.";
                $skipped++;
                continue;
            }
            $shortDescription = $this->sanitizeText($postData['short_description'] ?? '');
            $description = $this->sanitizeText($postData['description'] ?? '');
            $isPublic = !empty($postData['is_public']) && filter_var($postData['is_public'], FILTER_VALIDATE_BOOL);
            $publishedAt = $isPublic ? date('c') : null;

            $created = $blogModel->createPost([
                'title' => $title,
                'slug' => $slug,
                'category' => $category,
                'short_description' => $shortDescription,
                'description' => $description,
                'html' => $html,
                'is_public' => $isPublic,
                'author_id' => $authorId,
                'published_at' => $publishedAt,
            ]);

            if ($created) {
                $imported++;
            } else {
                $errors[] = "Entry #{$position} (\"{$title}\"): database insert failed.";
                $skipped++;
            }
        }

        if ($imported > 0) {
            $this->flash('success', "Imported {$imported} post(s) successfully.");
        }

        if ($skipped > 0) {
            $errorSummary = "Skipped {$skipped} entry/entries.";
            if (!empty($errors)) {
                $errorSummary .= ' ' . implode(' ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $errorSummary .= ' ...and ' . (count($errors) - 5) . ' more.';
                }
            }
            $this->flash('error', $errorSummary);
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
