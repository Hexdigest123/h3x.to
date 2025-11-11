-- Erstelle ENUM Types
CREATE TYPE blog_category AS ENUM ('Projects', 'Bugs');
CREATE TYPE user_role AS ENUM ('Admin', 'User');
CREATE TYPE device_type AS ENUM ('Desktop', 'Mobile', 'Tablet', 'Bot', 'Unknown');
CREATE TYPE os_type AS ENUM ('Windows', 'macOS', 'Linux', 'iOS', 'Android', 'Other');
CREATE TYPE browser_type AS ENUM ('Chrome', 'Firefox', 'Safari', 'Edge', 'Opera', 'Other');


-- ============================================
-- Tabelle: users
-- ============================================
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL DEFAULT 'User',
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP WITH TIME ZONE,
    is_active BOOLEAN DEFAULT TRUE
);

-- Index für schnellere Suche
CREATE INDEX idx_users_name ON users(name);
CREATE INDEX idx_users_role ON users(role);

-- ============================================
-- Tabelle: blog_posts
-- ============================================
CREATE TABLE blog_posts (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    short_description VARCHAR(500),
    html TEXT NOT NULL,
    category blog_category NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    author_id INTEGER NOT NULL,
    views INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    published_at TIMESTAMP WITH TIME ZONE,
    
    CONSTRAINT fk_blog_author 
        FOREIGN KEY (author_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE
);

-- Indizes für bessere Performance
CREATE INDEX idx_blog_slug ON blog_posts(slug);
CREATE INDEX idx_blog_category ON blog_posts(category);
CREATE INDEX idx_blog_public ON blog_posts(is_public);
CREATE INDEX idx_blog_author ON blog_posts(author_id);
CREATE INDEX idx_blog_created ON blog_posts(created_at DESC);

-- ============================================
-- Tabelle: social_links
-- ============================================
CREATE TABLE social_links (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    url VARCHAR(500) NOT NULL,
    icon_path VARCHAR(255) NOT NULL,
    display_order INTEGER DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT unique_social_name UNIQUE(name)
);

-- Index für Sortierung
CREATE INDEX idx_social_order ON social_links(display_order);
CREATE INDEX idx_social_active ON social_links(is_active);

-- ============================================
-- Tabelle: blog_tags (Optional - für bessere Kategorisierung)
-- ============================================
CREATE TABLE blog_tags (
    id SERIAL PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_tags_slug ON blog_tags(slug);

-- ============================================
-- Tabelle: blog_post_tags (Many-to-Many Relation)
-- ============================================
CREATE TABLE blog_post_tags (
    blog_post_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    PRIMARY KEY (blog_post_id, tag_id),
    
    CONSTRAINT fk_post_tag_post 
        FOREIGN KEY (blog_post_id) 
        REFERENCES blog_posts(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_post_tag_tag 
        FOREIGN KEY (tag_id) 
        REFERENCES blog_tags(id) 
        ON DELETE CASCADE
);

CREATE INDEX idx_post_tags_post ON blog_post_tags(blog_post_id);
CREATE INDEX idx_post_tags_tag ON blog_post_tags(tag_id);

-- ============================================
-- Tabelle: blog_comments (Optional - für Kommentare)
-- ============================================
CREATE TABLE blog_comments (
    id SERIAL PRIMARY KEY,
    blog_post_id INTEGER NOT NULL,
    user_id INTEGER,
    author_name VARCHAR(100),
    author_email VARCHAR(255),
    content TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_comment_post 
        FOREIGN KEY (blog_post_id) 
        REFERENCES blog_posts(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_comment_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE SET NULL,
    
    CONSTRAINT check_author_info 
        CHECK (
            (user_id IS NOT NULL) OR 
            (author_name IS NOT NULL AND author_email IS NOT NULL)
        )
);

CREATE INDEX idx_comments_post ON blog_comments(blog_post_id);
CREATE INDEX idx_comments_approved ON blog_comments(is_approved);
CREATE INDEX idx_comments_created ON blog_comments(created_at DESC);

-- ============================================
-- Tabelle: sessions (für Session-Management)
-- ============================================
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INTEGER,
    data TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP WITH TIME ZONE NOT NULL,
    
    CONSTRAINT fk_session_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE CASCADE
);

CREATE INDEX idx_sessions_user ON sessions(user_id);
CREATE INDEX idx_sessions_expires ON sessions(expires_at);

-- ============================================
-- Tabelle: activity_log (für Audit Trail)
-- ============================================
CREATE TABLE activity_log (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50),
    entity_id INTEGER,
    ip_address INET,
    user_agent TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_activity_user 
        FOREIGN KEY (user_id) 
        REFERENCES users(id) 
        ON DELETE SET NULL
);

CREATE INDEX idx_activity_user ON activity_log(user_id);
CREATE INDEX idx_activity_created ON activity_log(created_at DESC);
CREATE INDEX idx_activity_entity ON activity_log(entity_type, entity_id);




-- ============================================
-- Tabelle: visitor_sessions
-- ============================================
CREATE TABLE visitor_sessions (
    id SERIAL PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL UNIQUE,
    fingerprint_hash VARCHAR(64) NOT NULL,
    
    -- IP & Location
    ip_address INET NOT NULL,
    ip_country VARCHAR(2),
    ip_city VARCHAR(100),
    ip_region VARCHAR(100),
    ip_timezone VARCHAR(50),
    
    -- User Agent Details
    user_agent TEXT NOT NULL,
    browser browser_type,
    browser_version VARCHAR(50),
    os os_type,
    os_version VARCHAR(50),
    device device_type,
    device_vendor VARCHAR(100),
    device_model VARCHAR(100),
    
    -- Screen & Display
    screen_resolution VARCHAR(20),
    screen_color_depth INTEGER,
    viewport_size VARCHAR(20),
    pixel_ratio DECIMAL(3,2),
    
    -- Browser Fingerprinting
    timezone_offset INTEGER,
    timezone_name VARCHAR(50),
    language VARCHAR(10),
    languages TEXT[],
    platform VARCHAR(50),
    do_not_track BOOLEAN,
    cookies_enabled BOOLEAN,
    
    -- Canvas & WebGL Fingerprinting
    canvas_fingerprint VARCHAR(64),
    webgl_vendor VARCHAR(100),
    webgl_renderer VARCHAR(100),
    
    -- Audio Fingerprinting
    audio_fingerprint VARCHAR(64),
    
    -- Fonts Detection
    installed_fonts TEXT[],
    
    -- Plugins & Extensions
    plugins TEXT[],
    has_adblock BOOLEAN DEFAULT FALSE,
    
    -- Connection Info
    connection_type VARCHAR(50),
    connection_downlink DECIMAL(5,2),
    connection_rtt INTEGER,
    
    -- Referrer & Entry
    referrer TEXT,
    entry_page TEXT NOT NULL,
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100),
    utm_campaign VARCHAR(100),
    utm_term VARCHAR(100),
    utm_content VARCHAR(100),
    
    -- Session Timing
    session_start TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    session_end TIMESTAMP WITH TIME ZONE,
    last_activity TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    -- Activity Metrics
    total_active_time INTEGER DEFAULT 0, -- in Sekunden
    total_inactive_time INTEGER DEFAULT 0, -- in Sekunden
    page_views INTEGER DEFAULT 1,
    clicks INTEGER DEFAULT 0,
    scrolls INTEGER DEFAULT 0,
    max_scroll_depth INTEGER DEFAULT 0, -- in Prozent
    
    -- Engagement Metrics
    bounce BOOLEAN DEFAULT TRUE,
    engaged BOOLEAN DEFAULT FALSE, -- > 30 Sekunden aktiv
    converted BOOLEAN DEFAULT FALSE,
    
    -- Technical Details
    javascript_enabled BOOLEAN DEFAULT TRUE,
    local_storage_enabled BOOLEAN,
    session_storage_enabled BOOLEAN,
    indexed_db_enabled BOOLEAN,
    
    -- Battery API (falls verfügbar)
    battery_charging BOOLEAN,
    battery_level INTEGER,
    
    -- Network Information
    effective_connection_type VARCHAR(10),
    save_data BOOLEAN,
    
    -- Timestamps
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Indizes für visitor_sessions
CREATE INDEX idx_visitor_session_id ON visitor_sessions(session_id);
CREATE INDEX idx_visitor_fingerprint ON visitor_sessions(fingerprint_hash);
CREATE INDEX idx_visitor_ip ON visitor_sessions(ip_address);
CREATE INDEX idx_visitor_created ON visitor_sessions(created_at DESC);
CREATE INDEX idx_visitor_device ON visitor_sessions(device);
CREATE INDEX idx_visitor_os ON visitor_sessions(os);
CREATE INDEX idx_visitor_browser ON visitor_sessions(browser);
CREATE INDEX idx_visitor_country ON visitor_sessions(ip_country);
CREATE INDEX idx_visitor_bounce ON visitor_sessions(bounce);
CREATE INDEX idx_visitor_engaged ON visitor_sessions(engaged);

-- ============================================
-- Tabelle: page_views
-- ============================================
CREATE TABLE page_views (
    id SERIAL PRIMARY KEY,
    visitor_session_id INTEGER NOT NULL,
    
    -- Page Info
    page_url TEXT NOT NULL,
    page_title VARCHAR(255),
    page_path VARCHAR(500),
    page_query_string TEXT,
    page_hash VARCHAR(255),
    
    -- Timing Metrics (Navigation Timing API)
    dns_time INTEGER, -- DNS lookup time in ms
    tcp_time INTEGER, -- TCP connection time in ms
    request_time INTEGER, -- Request time in ms
    response_time INTEGER, -- Response time in ms
    dom_processing_time INTEGER, -- DOM processing time in ms
    dom_content_loaded_time INTEGER, -- DOMContentLoaded time in ms
    load_time INTEGER, -- Full page load time in ms
    
    -- Time on Page
    time_on_page INTEGER, -- in Sekunden
    active_time INTEGER DEFAULT 0, -- aktive Zeit in Sekunden
    inactive_time INTEGER DEFAULT 0, -- inaktive Zeit in Sekunden
    
    -- Scroll Metrics
    max_scroll_depth INTEGER DEFAULT 0, -- in Prozent
    scroll_events INTEGER DEFAULT 0,
    
    -- Interaction Metrics
    clicks INTEGER DEFAULT 0,
    mouse_movements INTEGER DEFAULT 0,
    key_presses INTEGER DEFAULT 0,
    
    -- Exit Info
    exit_page BOOLEAN DEFAULT FALSE,
    exit_type VARCHAR(50), -- 'close', 'navigate', 'back', 'forward'
    
    -- Timestamps
    viewed_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    left_at TIMESTAMP WITH TIME ZONE,
    
    CONSTRAINT fk_pageview_session 
        FOREIGN KEY (visitor_session_id) 
        REFERENCES visitor_sessions(id) 
        ON DELETE CASCADE
);

-- Indizes für page_views
CREATE INDEX idx_pageview_session ON page_views(visitor_session_id);
CREATE INDEX idx_pageview_url ON page_views(page_url);
CREATE INDEX idx_pageview_path ON page_views(page_path);
CREATE INDEX idx_pageview_viewed ON page_views(viewed_at DESC);
CREATE INDEX idx_pageview_exit ON page_views(exit_page);

-- ============================================
-- Tabelle: visitor_events
-- ============================================
CREATE TABLE visitor_events (
    id SERIAL PRIMARY KEY,
    visitor_session_id INTEGER NOT NULL,
    page_view_id INTEGER,
    
    -- Event Details
    event_type VARCHAR(50) NOT NULL, -- 'click', 'scroll', 'form_submit', 'video_play', etc.
    event_category VARCHAR(100),
    event_action VARCHAR(100),
    event_label VARCHAR(255),
    event_value INTEGER,
    
    -- Element Info
    element_id VARCHAR(255),
    element_class VARCHAR(255),
    element_tag VARCHAR(50),
    element_text TEXT,
    element_href TEXT,
    
    -- Position Info
    mouse_x INTEGER,
    mouse_y INTEGER,
    scroll_position INTEGER,
    
    -- Additional Data
    metadata JSONB,
    
    -- Timestamp
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_event_session 
        FOREIGN KEY (visitor_session_id) 
        REFERENCES visitor_sessions(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_event_pageview 
        FOREIGN KEY (page_view_id) 
        REFERENCES page_views(id) 
        ON DELETE CASCADE
);

-- Indizes für visitor_events
CREATE INDEX idx_event_session ON visitor_events(visitor_session_id);
CREATE INDEX idx_event_pageview ON visitor_events(page_view_id);
CREATE INDEX idx_event_type ON visitor_events(event_type);
CREATE INDEX idx_event_created ON visitor_events(created_at DESC);
CREATE INDEX idx_event_metadata ON visitor_events USING gin(metadata);

-- ============================================
-- Tabelle: visitor_heatmap
-- ============================================
CREATE TABLE visitor_heatmap (
    id SERIAL PRIMARY KEY,
    page_path VARCHAR(500) NOT NULL,
    
    -- Click/Hover Position
    x_position INTEGER NOT NULL,
    y_position INTEGER NOT NULL,
    viewport_width INTEGER NOT NULL,
    viewport_height INTEGER NOT NULL,
    
    -- Interaction Type
    interaction_type VARCHAR(20) NOT NULL, -- 'click', 'hover', 'scroll'
    
    -- Aggregation
    count INTEGER DEFAULT 1,
    
    -- Date for aggregation
    date DATE DEFAULT CURRENT_DATE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Indizes für visitor_heatmap
CREATE INDEX idx_heatmap_page ON visitor_heatmap(page_path);
CREATE INDEX idx_heatmap_date ON visitor_heatmap(date);
CREATE INDEX idx_heatmap_type ON visitor_heatmap(interaction_type);
CREATE INDEX idx_heatmap_position ON visitor_heatmap(x_position, y_position);

-- ============================================
-- Tabelle: visitor_performance
-- ============================================
CREATE TABLE visitor_performance (
    id SERIAL PRIMARY KEY,
    visitor_session_id INTEGER NOT NULL,
    
    -- Resource Timing
    resource_url TEXT NOT NULL,
    resource_type VARCHAR(50), -- 'script', 'stylesheet', 'image', 'xhr', etc.
    resource_size INTEGER, -- in bytes
    resource_duration INTEGER, -- in ms
    
    -- Timing Details
    dns_duration INTEGER,
    tcp_duration INTEGER,
    request_duration INTEGER,
    response_duration INTEGER,
    
    -- Cache Info
    from_cache BOOLEAN DEFAULT FALSE,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_performance_session 
        FOREIGN KEY (visitor_session_id) 
        REFERENCES visitor_sessions(id) 
        ON DELETE CASCADE
);

-- Indizes für visitor_performance
CREATE INDEX idx_performance_session ON visitor_performance(visitor_session_id);
CREATE INDEX idx_performance_type ON visitor_performance(resource_type);
CREATE INDEX idx_performance_url ON visitor_performance(resource_url);

-- ============================================
-- Tabelle: visitor_errors
-- ============================================
CREATE TABLE visitor_errors (
    id SERIAL PRIMARY KEY,
    visitor_session_id INTEGER NOT NULL,
    page_view_id INTEGER,
    
    -- Error Details
    error_type VARCHAR(50) NOT NULL, -- 'javascript', 'network', 'console'
    error_message TEXT NOT NULL,
    error_stack TEXT,
    error_line INTEGER,
    error_column INTEGER,
    error_file TEXT,
    
    -- Browser Context
    user_agent TEXT,
    page_url TEXT,
    
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP,
    
    CONSTRAINT fk_error_session 
        FOREIGN KEY (visitor_session_id) 
        REFERENCES visitor_sessions(id) 
        ON DELETE CASCADE,
    
    CONSTRAINT fk_error_pageview 
        FOREIGN KEY (page_view_id) 
        REFERENCES page_views(id) 
        ON DELETE SET NULL
);

-- Indizes für visitor_errors
CREATE INDEX idx_error_session ON visitor_errors(visitor_session_id);
CREATE INDEX idx_error_type ON visitor_errors(error_type);
CREATE INDEX idx_error_created ON visitor_errors(created_at DESC);

-- ============================================
-- TRIGGERS für Visitor Tables
-- ============================================

-- Trigger für visitor_sessions
CREATE TRIGGER update_visitor_sessions_updated_at
    BEFORE UPDATE ON visitor_sessions
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Trigger für visitor_heatmap
CREATE TRIGGER update_visitor_heatmap_updated_at
    BEFORE UPDATE ON visitor_heatmap
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- ============================================
-- FUNCTIONS für Analytics
-- ============================================

-- Function: Calculate session duration
CREATE OR REPLACE FUNCTION calculate_session_duration(session_id_param INTEGER)
RETURNS INTEGER AS $$
DECLARE
    duration INTEGER;
BEGIN
    SELECT EXTRACT(EPOCH FROM (session_end - session_start))::INTEGER
    INTO duration
    FROM visitor_sessions
    WHERE id = session_id_param;
    
    RETURN COALESCE(duration, 0);
END;
$$ LANGUAGE plpgsql;

-- Function: Get unique visitors count
CREATE OR REPLACE FUNCTION get_unique_visitors(start_date DATE, end_date DATE)
RETURNS INTEGER AS $$
BEGIN
    RETURN (
        SELECT COUNT(DISTINCT fingerprint_hash)
        FROM visitor_sessions
        WHERE DATE(created_at) BETWEEN start_date AND end_date
    );
END;
$$ LANGUAGE plpgsql;

-- Function: Get bounce rate
CREATE OR REPLACE FUNCTION get_bounce_rate(start_date DATE, end_date DATE)
RETURNS DECIMAL(5,2) AS $$
DECLARE
    total_sessions INTEGER;
    bounced_sessions INTEGER;
BEGIN
    SELECT COUNT(*) INTO total_sessions
    FROM visitor_sessions
    WHERE DATE(created_at) BETWEEN start_date AND end_date;
    
    SELECT COUNT(*) INTO bounced_sessions
    FROM visitor_sessions
    WHERE DATE(created_at) BETWEEN start_date AND end_date
    AND bounce = TRUE;
    
    IF total_sessions = 0 THEN
        RETURN 0;
    END IF;
    
    RETURN (bounced_sessions::DECIMAL / total_sessions * 100);
END;
$$ LANGUAGE plpgsql;

-- Function: Update heatmap aggregation
CREATE OR REPLACE FUNCTION update_heatmap_click(
    page_path_param VARCHAR(500),
    x_param INTEGER,
    y_param INTEGER,
    viewport_width_param INTEGER,
    viewport_height_param INTEGER
)
RETURNS void AS $$
BEGIN
    INSERT INTO visitor_heatmap (
        page_path, x_position, y_position, 
        viewport_width, viewport_height, 
        interaction_type, count
    )
    VALUES (
        page_path_param, x_param, y_param,
        viewport_width_param, viewport_height_param,
        'click', 1
    )
    ON CONFLICT ON CONSTRAINT visitor_heatmap_unique
    DO UPDATE SET 
        count = visitor_heatmap.count + 1,
        updated_at = CURRENT_TIMESTAMP;
END;
$$ LANGUAGE plpgsql;

-- Unique constraint für heatmap aggregation
ALTER TABLE visitor_heatmap 
ADD CONSTRAINT visitor_heatmap_unique 
UNIQUE (page_path, x_position, y_position, viewport_width, viewport_height, interaction_type, date);

-- ============================================
-- VIEWS für Analytics
-- ============================================

-- View: Daily Visitor Statistics
CREATE VIEW daily_visitor_stats AS
SELECT 
    DATE(created_at) AS date,
    COUNT(*) AS total_sessions,
    COUNT(DISTINCT fingerprint_hash) AS unique_visitors,
    COUNT(*) FILTER (WHERE bounce = TRUE) AS bounced_sessions,

-- ============================================
-- FUNCTIONS & TRIGGERS
-- ============================================

-- Function: Update updated_at timestamp
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger für users
CREATE TRIGGER update_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Trigger für blog_posts
CREATE TRIGGER update_blog_posts_updated_at
    BEFORE UPDATE ON blog_posts
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Trigger für social_links
CREATE TRIGGER update_social_links_updated_at
    BEFORE UPDATE ON social_links
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Trigger für blog_comments
CREATE TRIGGER update_blog_comments_updated_at
    BEFORE UPDATE ON blog_comments
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Trigger für sessions
CREATE TRIGGER update_sessions_updated_at
    BEFORE UPDATE ON sessions
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Function: Auto-generate slug from title
CREATE OR REPLACE FUNCTION generate_slug(title TEXT)
RETURNS TEXT AS $$
BEGIN
    RETURN lower(
        regexp_replace(
            regexp_replace(
                regexp_replace(title, '[äÄ]', 'ae', 'g'),
                '[öÖ]', 'oe', 'g'
            ),
            '[üÜ]', 'ue', 'g'
        )
    );
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- VIEWS (für häufige Abfragen)
-- ============================================

-- View: Public Blog Posts mit Author Info
CREATE VIEW public_blog_posts AS
SELECT 
    bp.id,
    bp.title,
    bp.slug,
    bp.short_description,
    bp.category,
    bp.views,
    bp.created_at,
    bp.published_at,
    u.name AS author_name,
    (SELECT COUNT(*) FROM blog_comments WHERE blog_post_id = bp.id AND is_approved = TRUE) AS comment_count
FROM blog_posts bp
JOIN users u ON bp.author_id = u.id
WHERE bp.is_public = TRUE
ORDER BY bp.created_at DESC;

-- View: Active Social Links
CREATE VIEW active_social_links AS
SELECT 
    id,
    name,
    url,
    icon_path,
    display_order
FROM social_links
WHERE is_active = TRUE
ORDER BY display_order ASC;

-- ============================================
-- PERMISSIONS & SECURITY
-- ============================================

-- Revoke public access
REVOKE ALL ON ALL TABLES IN SCHEMA public FROM PUBLIC;
REVOKE ALL ON ALL SEQUENCES IN SCHEMA public FROM PUBLIC;
REVOKE ALL ON ALL FUNCTIONS IN SCHEMA public FROM PUBLIC;

-- Grant access to mvc_user
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO mvc_user;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO mvc_user;
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO mvc_user;

-- ============================================
-- MAINTENANCE
-- ============================================

-- Function: Clean old sessions
CREATE OR REPLACE FUNCTION clean_expired_sessions()
RETURNS void AS $$
BEGIN
    DELETE FROM sessions WHERE expires_at < CURRENT_TIMESTAMP;
END;
$$ LANGUAGE plpgsql;

-- Function: Clean old activity logs (älter als 90 Tage)
CREATE OR REPLACE FUNCTION clean_old_activity_logs()
RETURNS void AS $$
BEGIN
    DELETE FROM activity_log WHERE created_at < CURRENT_TIMESTAMP - INTERVAL '90 days';
END;
$$ LANGUAGE plpgsql;

-- ============================================
-- STATISTICS
-- ============================================

-- View: Blog Statistics
CREATE VIEW blog_statistics AS
SELECT 
    COUNT(*) AS total_posts,
    COUNT(*) FILTER (WHERE is_public = TRUE) AS public_posts,
    COUNT(*) FILTER (WHERE category = 'Projects') AS project_posts,
    COUNT(*) FILTER (WHERE category = 'Bugs') AS bug_posts,
    SUM(views) AS total_views,
    AVG(views) AS avg_views
FROM blog_posts;

-- ============================================
-- COMMENTS
-- ============================================

COMMENT ON TABLE users IS 'Benutzer-Tabelle für Authentifizierung';
COMMENT ON TABLE blog_posts IS 'Haupttabelle für Blog-Einträge';
COMMENT ON TABLE social_links IS 'Social Media Links für die Website';
COMMENT ON TABLE blog_tags IS 'Tags für bessere Kategorisierung von Blog-Posts';
COMMENT ON TABLE blog_post_tags IS 'Many-to-Many Relation zwischen Posts und Tags';
COMMENT ON TABLE blog_comments IS 'Kommentare zu Blog-Posts';
COMMENT ON TABLE sessions IS 'Session-Management für eingeloggte Benutzer';
COMMENT ON TABLE activity_log IS 'Audit Trail für Benutzeraktivitäten';

-- ============================================
-- ANALYZE
-- ============================================

ANALYZE users;
ANALYZE blog_posts;
ANALYZE social_links;
ANALYZE blog_tags;
ANALYZE blog_post_tags;
ANALYZE blog_comments;
ANALYZE sessions;
ANALYZE activity_log;

-- Ende der Initialisierung
