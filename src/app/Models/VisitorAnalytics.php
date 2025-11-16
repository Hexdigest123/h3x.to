<?php

namespace App\Models;

use App\Core\Model;

class VisitorAnalytics extends Model
{
    public function upsertSession(array $data)
    {
        $sql = <<<SQL
            INSERT INTO visitor_sessions (
                session_id,
                fingerprint_hash,
                ip_address,
                ip_country,
                ip_city,
                ip_region,
                ip_timezone,
                user_agent,
                browser,
                browser_version,
                os,
                os_version,
                device,
                device_vendor,
                device_model,
                screen_resolution,
                screen_color_depth,
                viewport_size,
                pixel_ratio,
                timezone_offset,
                timezone_name,
                language,
                languages,
                platform,
                do_not_track,
                cookies_enabled,
                canvas_fingerprint,
                webgl_vendor,
                webgl_renderer,
                audio_fingerprint,
                installed_fonts,
                plugins,
                has_adblock,
                connection_type,
                connection_downlink,
                connection_rtt,
                referrer,
                entry_page,
                utm_source,
                utm_medium,
                utm_campaign,
                utm_term,
                utm_content,
                total_active_time,
                total_inactive_time,
                page_views,
                clicks,
                scrolls,
                max_scroll_depth,
                bounce,
                engaged,
                converted,
                javascript_enabled,
                local_storage_enabled,
                session_storage_enabled,
                indexed_db_enabled,
                battery_charging,
                battery_level,
                effective_connection_type,
                save_data
            )
            VALUES (
                :session_id,
                :fingerprint_hash,
                :ip_address,
                :ip_country,
                :ip_city,
                :ip_region,
                :ip_timezone,
                :user_agent,
                :browser,
                :browser_version,
                :os,
                :os_version,
                :device,
                :device_vendor,
                :device_model,
                :screen_resolution,
                :screen_color_depth,
                :viewport_size,
                :pixel_ratio,
                :timezone_offset,
                :timezone_name,
                :language,
                :languages,
                :platform,
                :do_not_track,
                :cookies_enabled,
                :canvas_fingerprint,
                :webgl_vendor,
                :webgl_renderer,
                :audio_fingerprint,
                :installed_fonts,
                :plugins,
                :has_adblock,
                :connection_type,
                :connection_downlink,
                :connection_rtt,
                :referrer,
                :entry_page,
                :utm_source,
                :utm_medium,
                :utm_campaign,
                :utm_term,
                :utm_content,
                :total_active_time,
                :total_inactive_time,
                :page_views,
                :clicks,
                :scrolls,
                :max_scroll_depth,
                :bounce,
                :engaged,
                :converted,
                :javascript_enabled,
                :local_storage_enabled,
                :session_storage_enabled,
                :indexed_db_enabled,
                :battery_charging,
                :battery_level,
                :effective_connection_type,
                :save_data
            )
            ON CONFLICT (session_id) DO UPDATE SET
                fingerprint_hash = EXCLUDED.fingerprint_hash,
                ip_address = EXCLUDED.ip_address,
                ip_country = COALESCE(EXCLUDED.ip_country, visitor_sessions.ip_country),
                ip_city = COALESCE(EXCLUDED.ip_city, visitor_sessions.ip_city),
                ip_region = COALESCE(EXCLUDED.ip_region, visitor_sessions.ip_region),
                ip_timezone = COALESCE(EXCLUDED.ip_timezone, visitor_sessions.ip_timezone),
                user_agent = EXCLUDED.user_agent,
                browser = EXCLUDED.browser,
                browser_version = EXCLUDED.browser_version,
                os = EXCLUDED.os,
                os_version = EXCLUDED.os_version,
                device = EXCLUDED.device,
                device_vendor = COALESCE(EXCLUDED.device_vendor, visitor_sessions.device_vendor),
                device_model = COALESCE(EXCLUDED.device_model, visitor_sessions.device_model),
                screen_resolution = EXCLUDED.screen_resolution,
                screen_color_depth = EXCLUDED.screen_color_depth,
                viewport_size = EXCLUDED.viewport_size,
                pixel_ratio = EXCLUDED.pixel_ratio,
                timezone_offset = EXCLUDED.timezone_offset,
                timezone_name = EXCLUDED.timezone_name,
                language = EXCLUDED.language,
                languages = COALESCE(EXCLUDED.languages, visitor_sessions.languages),
                platform = EXCLUDED.platform,
                do_not_track = EXCLUDED.do_not_track,
                cookies_enabled = EXCLUDED.cookies_enabled,
                canvas_fingerprint = EXCLUDED.canvas_fingerprint,
                webgl_vendor = EXCLUDED.webgl_vendor,
                webgl_renderer = EXCLUDED.webgl_renderer,
                audio_fingerprint = COALESCE(EXCLUDED.audio_fingerprint, visitor_sessions.audio_fingerprint),
                installed_fonts = COALESCE(EXCLUDED.installed_fonts, visitor_sessions.installed_fonts),
                plugins = COALESCE(EXCLUDED.plugins, visitor_sessions.plugins),
                has_adblock = EXCLUDED.has_adblock,
                connection_type = EXCLUDED.connection_type,
                connection_downlink = EXCLUDED.connection_downlink,
                connection_rtt = EXCLUDED.connection_rtt,
                referrer = COALESCE(visitor_sessions.referrer, EXCLUDED.referrer),
                entry_page = visitor_sessions.entry_page,
                utm_source = COALESCE(visitor_sessions.utm_source, EXCLUDED.utm_source),
                utm_medium = COALESCE(visitor_sessions.utm_medium, EXCLUDED.utm_medium),
                utm_campaign = COALESCE(visitor_sessions.utm_campaign, EXCLUDED.utm_campaign),
                utm_term = COALESCE(visitor_sessions.utm_term, EXCLUDED.utm_term),
                utm_content = COALESCE(visitor_sessions.utm_content, EXCLUDED.utm_content),
                total_active_time = visitor_sessions.total_active_time + EXCLUDED.total_active_time,
                total_inactive_time = visitor_sessions.total_inactive_time + EXCLUDED.total_inactive_time,
                page_views = visitor_sessions.page_views + EXCLUDED.page_views,
                clicks = visitor_sessions.clicks + EXCLUDED.clicks,
                scrolls = visitor_sessions.scrolls + EXCLUDED.scrolls,
                max_scroll_depth = GREATEST(visitor_sessions.max_scroll_depth, EXCLUDED.max_scroll_depth),
                bounce = CASE WHEN visitor_sessions.page_views + EXCLUDED.page_views > 1 OR EXCLUDED.engaged = TRUE THEN FALSE ELSE visitor_sessions.bounce END,
                engaged = visitor_sessions.engaged OR EXCLUDED.engaged,
                converted = visitor_sessions.converted OR EXCLUDED.converted,
                javascript_enabled = EXCLUDED.javascript_enabled,
                local_storage_enabled = EXCLUDED.local_storage_enabled,
                session_storage_enabled = EXCLUDED.session_storage_enabled,
                indexed_db_enabled = EXCLUDED.indexed_db_enabled,
                battery_charging = COALESCE(EXCLUDED.battery_charging, visitor_sessions.battery_charging),
                battery_level = COALESCE(EXCLUDED.battery_level, visitor_sessions.battery_level),
                effective_connection_type = EXCLUDED.effective_connection_type,
                save_data = EXCLUDED.save_data,
                last_activity = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            RETURNING id
        SQL;

        $this->db->query($sql);

        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }

        return $this->db->fetch();
    }

    public function recordPageView(int $sessionId, array $data)
    {
        $sql = <<<SQL
            INSERT INTO page_views (
                visitor_session_id,
                page_url,
                page_title,
                page_path,
                page_query_string,
                page_hash,
                dns_time,
                tcp_time,
                request_time,
                response_time,
                dom_processing_time,
                dom_content_loaded_time,
                load_time,
                time_on_page,
                active_time,
                inactive_time,
                max_scroll_depth,
                scroll_events,
                clicks,
                mouse_movements,
                key_presses,
                exit_page,
                exit_type
            ) VALUES (
                :visitor_session_id,
                :page_url,
                :page_title,
                :page_path,
                :page_query_string,
                :page_hash,
                :dns_time,
                :tcp_time,
                :request_time,
                :response_time,
                :dom_processing_time,
                :dom_content_loaded_time,
                :load_time,
                :time_on_page,
                :active_time,
                :inactive_time,
                :max_scroll_depth,
                :scroll_events,
                :clicks,
                :mouse_movements,
                :key_presses,
                :exit_page,
                :exit_type
            )
        SQL;

        $this->db->query($sql);

        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }

        return $this->db->execute();
    }

    public function sessionSummary()
    {
        $sql = <<<SQL
            SELECT
                COUNT(*) AS sessions,
                COALESCE(SUM(page_views), 0) AS total_page_views,
                COALESCE(SUM(total_active_time), 0) AS total_active_time,
                COALESCE(SUM(total_inactive_time), 0) AS total_inactive_time,
                COALESCE(SUM(clicks), 0) AS total_clicks,
                COALESCE(SUM(scrolls), 0) AS total_scrolls,
                COALESCE(AVG(page_views), 0) AS avg_pages_per_session,
                COUNT(*) FILTER (WHERE bounce = TRUE) AS bounce_sessions,
                COUNT(*) FILTER (WHERE engaged = TRUE) AS engaged_sessions
            FROM visitor_sessions
        SQL;

        $this->db->query($sql);
        return $this->db->fetch();
    }

    public function recentSessions($limit = 6)
    {
        $sql = <<<SQL
            SELECT
                id,
                session_id,
                ip_address,
                ip_country,
                ip_city,
                ip_region,
                browser,
                os,
                device,
                entry_page,
                page_views,
                clicks,
                max_scroll_depth,
                created_at,
                last_activity
            FROM visitor_sessions
            ORDER BY last_activity DESC
            LIMIT :limit
        SQL;

        $this->db->query($sql);
        $this->db->bind(':limit', (int) $limit);
        return $this->db->fetchAll();
    }

    public function topBrowsers($limit = 5)
    {
        $sql = <<<SQL
            SELECT browser, COUNT(*) as total
            FROM visitor_sessions
            WHERE browser IS NOT NULL
            GROUP BY browser
            ORDER BY total DESC
            LIMIT :limit
        SQL;

        $this->db->query($sql);
        $this->db->bind(':limit', (int) $limit);
        return $this->db->fetchAll();
    }

    public function topCountries($limit = 5)
    {
        $sql = <<<SQL
            SELECT ip_country, COUNT(*) as total
            FROM visitor_sessions
            WHERE ip_country IS NOT NULL
            GROUP BY ip_country
            ORDER BY total DESC
            LIMIT :limit
        SQL;

        $this->db->query($sql);
        $this->db->bind(':limit', (int) $limit);
        return $this->db->fetchAll();
    }
}
