<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\VisitorAnalytics;

class AnalyticsController extends Controller
{
    public function collect(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit();
        }

        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $visitorData = $this->normalizeVisitorData($payload);

        /** @var VisitorAnalytics $analytics */
        $analytics = $this->model('VisitorAnalytics');

        // Store/merge session
        $sessionRow = $analytics->upsertSession($visitorData['session']);
        $sessionIdPk = $sessionRow?->id ?? null;

        // Store page view if we have a valid session id
        if (!empty($sessionIdPk) && !empty($visitorData['page_view'])) {
            $visitorData['page_view']['visitor_session_id'] = $sessionIdPk;
            $analytics->recordPageView($sessionIdPk, $visitorData['page_view']);
        }

        $this->json(['status' => 'ok']);
    }

    private function normalizeVisitorData(array $incoming): array
    {
        $ip = $this->clientIp();
        $ipDetails = $this->lookupIpDetails($ip);
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $sessionId = $incoming['session_id'] ?? bin2hex(random_bytes(16));
        $fingerprint = $incoming['fingerprint_hash'] ?? hash('sha256', $sessionId . $userAgent);

        $languageArray = $this->toPgArray($incoming['languages'] ?? []);
        $fontsArray = $this->toPgArray($incoming['installed_fonts'] ?? []);
        $pluginsArray = $this->toPgArray($incoming['plugins'] ?? []);

        $browser = $this->mapEnum($incoming['browser'] ?? null, ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Other'], 'Other');
        $os = $this->mapEnum($incoming['os'] ?? null, ['Windows', 'macOS', 'Linux', 'iOS', 'Android', 'Other'], 'Other');
        $device = $this->mapEnum($incoming['device'] ?? null, ['Desktop', 'Mobile', 'Tablet', 'Bot', 'Unknown'], 'Unknown');

        $isEngaged = (($incoming['active_time'] ?? 0) >= 30) || (($incoming['max_scroll_depth'] ?? 0) >= 60);
        $isBounce = ($incoming['page_views'] ?? 1) <= 1 && !$isEngaged;

        $session = [
            'session_id' => $sessionId,
            'fingerprint_hash' => $fingerprint,
            'ip_address' => $ip,
            'ip_country' => $incoming['ip_country'] ?? ($ipDetails['countryCode'] ?? $ipDetails['country'] ?? null),
            'ip_city' => $incoming['ip_city'] ?? ($ipDetails['city'] ?? null),
            'ip_region' => $incoming['ip_region'] ?? ($ipDetails['regionName'] ?? $ipDetails['region'] ?? null),
            'ip_timezone' => $incoming['ip_timezone'] ?? ($ipDetails['timezone'] ?? null),
            'user_agent' => $userAgent,
            'browser' => $browser,
            'browser_version' => $incoming['browser_version'] ?? null,
            'os' => $os,
            'os_version' => $incoming['os_version'] ?? null,
            'device' => $device,
            'device_vendor' => $incoming['device_vendor'] ?? null,
            'device_model' => $incoming['device_model'] ?? null,
            'screen_resolution' => $incoming['screen_resolution'] ?? null,
            'screen_color_depth' => $incoming['screen_color_depth'] ?? null,
            'viewport_size' => $incoming['viewport_size'] ?? null,
            'pixel_ratio' => $incoming['pixel_ratio'] ?? null,
            'timezone_offset' => $incoming['timezone_offset'] ?? null,
            'timezone_name' => $incoming['timezone_name'] ?? null,
            'language' => $incoming['language'] ?? null,
            'languages' => $languageArray,
            'platform' => $incoming['platform'] ?? null,
            'do_not_track' => $incoming['do_not_track'] ?? null,
            'cookies_enabled' => $incoming['cookies_enabled'] ?? null,
            'canvas_fingerprint' => $incoming['canvas_fingerprint'] ?? null,
            'webgl_vendor' => $incoming['webgl_vendor'] ?? null,
            'webgl_renderer' => $incoming['webgl_renderer'] ?? null,
            'audio_fingerprint' => $incoming['audio_fingerprint'] ?? null,
            'installed_fonts' => $fontsArray,
            'plugins' => $pluginsArray,
            'has_adblock' => $incoming['has_adblock'] ?? null,
            'connection_type' => $incoming['connection_type'] ?? null,
            'connection_downlink' => $incoming['connection_downlink'] ?? null,
            'connection_rtt' => $incoming['connection_rtt'] ?? null,
            'referrer' => $incoming['referrer'] ?? null,
            'entry_page' => $incoming['entry_page'] ?? '/',
            'utm_source' => $incoming['utm_source'] ?? null,
            'utm_medium' => $incoming['utm_medium'] ?? null,
            'utm_campaign' => $incoming['utm_campaign'] ?? null,
            'utm_term' => $incoming['utm_term'] ?? null,
            'utm_content' => $incoming['utm_content'] ?? null,
            'total_active_time' => $incoming['active_time'] ?? 0,
            'total_inactive_time' => $incoming['inactive_time'] ?? 0,
            'page_views' => $incoming['page_views'] ?? 1,
            'clicks' => $incoming['clicks'] ?? 0,
            'scrolls' => $incoming['scrolls'] ?? 0,
            'max_scroll_depth' => $incoming['max_scroll_depth'] ?? 0,
            'bounce' => $isBounce,
            'engaged' => $isEngaged,
            'converted' => $incoming['converted'] ?? false,
            'javascript_enabled' => true,
            'local_storage_enabled' => $incoming['local_storage_enabled'] ?? null,
            'session_storage_enabled' => $incoming['session_storage_enabled'] ?? null,
            'indexed_db_enabled' => $incoming['indexed_db_enabled'] ?? null,
            'battery_charging' => $incoming['battery_charging'] ?? null,
            'battery_level' => $incoming['battery_level'] ?? null,
            'effective_connection_type' => $incoming['effective_connection_type'] ?? null,
            'save_data' => $incoming['save_data'] ?? null,
        ];

        $pageView = [
            'visitor_session_id' => 0,
            'page_url' => $incoming['entry_page'] ?? '/',
            'page_title' => $incoming['page_title'] ?? null,
            'page_path' => $incoming['page_path'] ?? null,
            'page_query_string' => $incoming['page_query_string'] ?? null,
            'page_hash' => $incoming['page_hash'] ?? null,
            'dns_time' => $incoming['dns_time'] ?? null,
            'tcp_time' => $incoming['tcp_time'] ?? null,
            'request_time' => $incoming['request_time'] ?? null,
            'response_time' => $incoming['response_time'] ?? null,
            'dom_processing_time' => $incoming['dom_processing_time'] ?? null,
            'dom_content_loaded_time' => $incoming['dom_content_loaded_time'] ?? null,
            'load_time' => $incoming['load_time'] ?? null,
            'time_on_page' => $incoming['time_on_page'] ?? null,
            'active_time' => $incoming['active_time'] ?? null,
            'inactive_time' => $incoming['inactive_time'] ?? null,
            'max_scroll_depth' => $incoming['max_scroll_depth'] ?? 0,
            'scroll_events' => $incoming['scrolls'] ?? 0,
            'clicks' => $incoming['clicks'] ?? 0,
            'mouse_movements' => $incoming['mouse_movements'] ?? 0,
            'key_presses' => $incoming['key_presses'] ?? 0,
            'exit_page' => $incoming['exit_page'] ?? false,
            'exit_type' => $incoming['exit_type'] ?? null,
        ];

        return ['session' => $session, 'page_view' => $pageView];
    }

    private function lookupIpDetails(string $ip): array
    {
        if (empty($ip) || $this->isPrivateIp($ip)) {
            return [];
        }

        $url = 'http://ip-api.com/json/' . rawurlencode($ip);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 2,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            return [];
        }

        $data = json_decode($response, true);
        if (!is_array($data) || ($data['status'] ?? '') !== 'success') {
            return [];
        }

        return $data;
    }

    private function isPrivateIp(string $ip): bool
    {
        if (empty($ip) || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return true;
        }

        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (filter_var($ip, FILTER_VALIDATE_IP, $flags) === false) {
            return true;
        }

        // Fallback patterns for link-local and loopback ranges to avoid needless lookups
        $privatePatterns = [
            '/^127\./',
            '/^10\./',
            '/^192\.168\./',
            '/^172\.(1[6-9]|2\d|3[0-1])\./',
            '/^169\.254\./',
            '/^::1$/',
            '/^fc/i',
            '/^fd/i',
            '/^fe80:/i',
        ];

        foreach ($privatePatterns as $pattern) {
            if (preg_match($pattern, $ip)) {
                return true;
            }
        }

        return false;
    }

    private function clientIp(): string
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($parts[0]);
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    private function toPgArray($items): ?string
    {
        if (empty($items) || !is_array($items)) {
            return null;
        }

        $escaped = array_map(function ($item) {
            $safe = str_replace(['\\', '"'], ['\\\\', '\\"'], (string) $item);
            return '"' . $safe . '"';
        }, $items);

        return '{' . implode(',', $escaped) . '}';
    }

    private function mapEnum(?string $value, array $allowed, $fallback = null): ?string
    {
        if ($value === null) {
            return $fallback;
        }

        foreach ($allowed as $option) {
            if (strcasecmp($option, $value) === 0) {
                return $option;
            }
        }

        return $fallback;
    }
}
