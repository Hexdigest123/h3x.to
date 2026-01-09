<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\BlogPost;
use App\Models\SocialLink;

class HomeController extends Controller
{
    public function index()
    {
        /** @var BlogPost $blogModel */
        $blogModel = $this->model('BlogPost');
        /** @var SocialLink $socialLinkModel */
        $socialLinkModel = $this->model('SocialLink');

        $blogPosts = $blogModel->getPublicPosts();
        $socialLinks = $socialLinkModel->activeLinks();

        $profile = [
            'alias' => 'Hexdigest',
            'handle' => '@h3x.to',
            'role' => 'Security Research · Offensive Bug Bounty Hunting',
            'summary' => 'Focusing on learning penetration testing, malware development, and sharing my experiences in different tech fields.',
            'avatar' => BASE_URL . 'images/profile.png',
        ];

        $linkCards = [];
        if (!empty($socialLinks)) {
            foreach ($socialLinks as $link) {
                $linkCards[] = [
                    'label' => $link->name,
                    'href' => $link->url,
                    'icon' => str_starts_with($link->icon_path, 'http')
                        ? $link->icon_path
                        : BASE_URL . ltrim($link->icon_path, '/'),
                ];
            }
        } else {
            $linkCards = [
                [
                    'label' => 'Project site',
                    'href' => 'https://bruteshard.to',
                    'icon' => BASE_URL . 'images/url.svg',
                ],
                [
                    'label' => 'GitHub',
                    'href' => 'https://github.com/hexdigest',
                    'icon' => BASE_URL . 'images/github.svg',
                ],
                [
                    'label' => 'GitLab',
                    'href' => 'https://gitlab.com/hexdigest',
                    'icon' => BASE_URL . 'images/gitlab.svg',
                ],
            ];
        }

        $navLinks = [
            [
                'label' => 'Welcome',
                'href' => '#top',
                'icon' => BASE_URL . 'images/home.svg',
            ],
            [
                'label' => 'Projects',
                'href' => '#projects',
                'icon' => BASE_URL . 'images/project.svg',
            ],
            [
                'label' => 'Bugs',
                'href' => '#bugs',
                'icon' => BASE_URL . 'images/bug.svg',
            ],
            [
                'label' => 'Admin',
                'href' => BASE_URL . 'admin',
                'icon' => BASE_URL . 'images/admin.svg',
            ],
        ];

        $lost = [
            'label' => 'Field Compass',
            'title' => 'Are you lost?',
            'message' => 'The map is tricky out here. Follow the arrow to get back to the briefing.',
        ];

        $data = [
            'title' => 'H3x Portfolio',
            'description' => 'A calm place to browse Hexdigest case notes.',
            'brand' => 'H3x',
            'brandTagline' => '',
            'profile' => $profile,
            'linkCards' => $linkCards,
            'blogPosts' => $blogPosts,
            'navLinks' => $navLinks,
            'lost' => $lost,
        ];

        $this->view('home/index', $data);
    }
}
