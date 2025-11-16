<?php

namespace App\Controllers;

use App\Core\Controller;

class HomeController extends Controller
{
    public function index()
    {
        $heroLayers = [
            ['text' => 'H3x', 'variant' => 'outline'],
            ['text' => 'H3x', 'variant' => 'shadow'],
            ['text' => 'H3x', 'variant' => 'solid'],
        ];

        $profile = [
            'alias' => 'Hexdigest',
            'handle' => '@BruteShard.to',
            'role' => 'Security Research · Offensive R&D',
            'summary' => 'Focusing on practical misconfigurations, anon-key abuse and sharing calm write-ups for the community.',
            'avatar' => 'https://images.unsplash.com/photo-1457369804613-52c61a468e7d?auto=format&fit=facearea&w=400&h=400&q=80',
            'tags' => [
                ['label' => 'BruteShard.to', 'href' => 'https://bruteshard.to', 'icon' => '◆'],
                ['label' => '@Hexdigest', 'href' => 'https://twitter.com/Hexdigest', 'icon' => '◆'],
                ['label' => 'Keybase', 'href' => 'https://keybase.io/hexdigest', 'icon' => '◆'],
            ],
        ];

        $projects = [
            [
                'slug' => 'supabase-anon',
                'label' => 'BruteShard.to',
                'title' => 'Supabase anon key misconfiguration',
                'date' => 'June 30, 2023',
                'summary' => 'Using the anon key to pivot into internal APIs and leverage row level policies for data exfiltration.',
                'sections' => [
                    [
                        'title' => 'What it is',
                        'items' => [
                            'The anon key is a public JWT used by client apps to access Supabase.',
                            'It governs access through Row Level Security (RLS) and policies mis-configured by the user.',
                        ],
                    ],
                    [
                        'title' => 'Common misconfigurations',
                        'items' => [
                            'Overly broad policies that don\'t scope by user claims.',
                            'Allowing "*" policies that skip auth or rely on client-side filtering.',
                            'Re-using the anon key in server-side scripts where RLS isn\'t enforced.',
                        ],
                    ],
                    [
                        'title' => 'Where it breaks',
                        'items' => [
                            'Downgraded auth controls in auth tables or password resets.',
                            'Cross-project leaks when the anon key is shared with audiences.',
                            'Complete read/write of verified tables in edge proxies allowing RCE.',
                        ],
                    ],
                ],
                'impact' => [
                    'Data exfiltration (read).',
                    'Privilege escalation to service roles.',
                    'Compliance violations for GDPR/PII.',
                ],
            ],
        ];

        $navLinks = [
            ['label' => 'Welcome', 'href' => '#top', 'icon' => '⌂'],
            ['label' => 'Projects', 'href' => '#project-supabase-anon', 'icon' => '❖'],
            ['label' => 'Bugs', 'href' => BASE_URL . 'about', 'icon' => '⚡'],
            ['label' => 'Admin', 'href' => BASE_URL . 'admin', 'icon' => '✦'],
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
            'brandTagline' => 'Recon log',
            'heroLayers' => $heroLayers,
            'heroSubline' => 'Case files and tiny stories from security field notes.',
            'profile' => $profile,
            'projects' => $projects,
            'navLinks' => $navLinks,
            'lost' => $lost,
        ];

        $this->view('home/index', $data);
    }
}
