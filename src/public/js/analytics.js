(function () {
    const baseUrl = window.APP_BASE_URL || '/';
    const endpoint = baseUrl + 'analytics/collect';
    const start = performance.now();
    const state = {
        clicks: 0,
        scrolls: 0,
        mouseMoves: 0,
        keyPresses: 0,
        maxScrollDepth: 0,
        pageViews: 1,
    };

    const sessionId = getSessionId();

    document.addEventListener('click', () => state.clicks += 1);
    document.addEventListener('mousemove', () => state.mouseMoves += 1);
    document.addEventListener('keydown', () => state.keyPresses += 1);
    window.addEventListener('scroll', () => {
        state.scrolls += 1;
        updateScrollDepth(state);
    }, { passive: true });

    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            flushAnalytics(true);
        }
    });
    window.addEventListener('beforeunload', () => flushAnalytics(true));

    // Initial send shortly after load so performance metrics are populated
    window.addEventListener('load', () => {
        setTimeout(() => flushAnalytics(false), 1200);
    });

    function flushAnalytics(useBeacon) {
        buildPayload()
            .then((payload) => sendAnalyticsPayload(payload, useBeacon))
            .catch(() => {});
    }

    function getSessionId() {
        const key = 'h3x_session_id';
        try {
            const existing = localStorage.getItem(key);
            if (existing) return existing;
            const next = (crypto.randomUUID && crypto.randomUUID()) || randomHex(32);
            localStorage.setItem(key, next);
            return next;
        } catch (e) {
            return (crypto.randomUUID && crypto.randomUUID()) || randomHex(32);
        }
    }

    function randomHex(length) {
        const chars = 'abcdef0123456789';
        let out = '';
        for (let i = 0; i < length; i++) {
            out += chars[Math.floor(Math.random() * chars.length)];
        }
        return out;
    }

    async function buildPayload() {
        const performanceData = readPerformanceTiming();
        const ipInfo = await getIpInfo();
        const canvasFingerprint = getCanvasFingerprint();
        const webgl = getWebGLInfo();
        const audioFp = await getAudioFingerprint();
        const battery = await getBatteryInfo();
        const connection = getConnectionInfo();
        const fonts = getFontList();
        const plugins = getPluginList();
        const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || null;
        const now = performance.now();

        return {
            session_id: sessionId,
            fingerprint_hash: simpleHash([
                navigator.userAgent,
                navigator.language,
                navigator.languages ? navigator.languages.join(',') : '',
                screen.width,
                screen.height,
                canvasFingerprint,
                webgl.vendor || '',
                webgl.renderer || '',
            ].join('|')),
            entry_page: window.location.href,
            page_title: document.title,
            page_path: window.location.pathname,
            page_query_string: window.location.search || null,
            page_hash: window.location.hash || null,
            referrer: document.referrer || null,
            ip_country: ipInfo && (ipInfo.countryCode || ipInfo.country || null),
            ip_city: ipInfo && (ipInfo.city || null),
            ip_region: ipInfo && (ipInfo.regionName || ipInfo.region || null),
            ip_timezone: ipInfo && (ipInfo.timezone || null),
            user_agent: navigator.userAgent,
            browser: detectBrowser(),
            browser_version: detectBrowserVersion(),
            os: detectOS(),
            os_version: detectOSVersion(),
            device: detectDeviceType(),
            device_vendor: navigator.vendor || null,
            device_model: navigator.userAgentData && navigator.userAgentData.model ? navigator.userAgentData.model : null,
            screen_resolution: `${screen.width}x${screen.height}`,
            screen_color_depth: screen.colorDepth || null,
            viewport_size: `${window.innerWidth}x${window.innerHeight}`,
            pixel_ratio: Number(window.devicePixelRatio || 1).toFixed(2),
            timezone_offset: new Date().getTimezoneOffset(),
            timezone_name: timeZone,
            language: navigator.language || null,
            languages: navigator.languages || [],
            platform: navigator.platform || null,
            do_not_track: ['1', 'yes'].includes(String(navigator.doNotTrack || '').toLowerCase()),
            cookies_enabled: navigator.cookieEnabled,
            canvas_fingerprint: canvasFingerprint,
            webgl_vendor: webgl.vendor,
            webgl_renderer: webgl.renderer,
            audio_fingerprint: audioFp,
            installed_fonts: fonts,
            plugins,
            has_adblock: detectAdblock(),
            connection_type: connection.type,
            connection_downlink: connection.downlink,
            connection_rtt: connection.rtt,
            effective_connection_type: connection.effectiveType,
            save_data: connection.saveData,
            local_storage_enabled: storageAvailable('localStorage'),
            session_storage_enabled: storageAvailable('sessionStorage'),
            indexed_db_enabled: !!window.indexedDB,
            battery_charging: battery.battery_charging,
            battery_level: battery.battery_level,
            utm_source: getParam('utm_source'),
            utm_medium: getParam('utm_medium'),
            utm_campaign: getParam('utm_campaign'),
            utm_term: getParam('utm_term'),
            utm_content: getParam('utm_content'),
            dns_time: performanceData.dns,
            tcp_time: performanceData.tcp,
            request_time: performanceData.request,
            response_time: performanceData.response,
            dom_processing_time: performanceData.domProcessing,
            dom_content_loaded_time: performanceData.domContentLoaded,
            load_time: performanceData.load,
            time_on_page: Math.round((now - start) / 1000),
            active_time: Math.round((now - start) / 1000),
            inactive_time: 0,
            max_scroll_depth: Math.round(state.maxScrollDepth),
            scrolls: state.scrolls,
            clicks: state.clicks,
            mouse_movements: state.mouseMoves,
            key_presses: state.keyPresses,
            page_views: state.pageViews,
        };
    }

    function buildInteractionPayload(type, details) {
        const title = details && details.title ? details.title : document.title;
        const target = details && details.target ? details.target : null;
        const meta = details && details.meta ? details.meta : null;
        const durationMs = details && details.durationMs ? details.durationMs : null;

        return {
            session_id: sessionId,
            fingerprint_hash: null,
            entry_page: window.location.href,
            page_title: `Interaction: ${type}${target ? ' - ' + target : ''}`,
            page_path: details && details.pagePath ? details.pagePath : window.location.pathname,
            page_query_string: window.location.search || null,
            page_hash: window.location.hash || null,
            referrer: document.referrer || null,
            page_views: 0,
            clicks: type === 'nav_click' ? 1 : 0,
            scrolls: 0,
            mouse_movements: 0,
            key_presses: 0,
            active_time: 0,
            inactive_time: 0,
            time_on_page: 0,
            event_type: type,
            event_target: target,
            event_meta: meta,
            event_duration_ms: durationMs,
            page_title_fallback: title,
        };
    }

    function trackInteraction(type, details = {}) {
        try {
            const payload = buildInteractionPayload(type, details);
            sendAnalyticsPayload(payload, true);
        } catch (e) {
            // ignore tracking failures
        }
    }

    function updateScrollDepth(currentState) {
        const scrollTop = window.scrollY || document.documentElement.scrollTop;
        const docHeight = Math.max(
            document.body.scrollHeight, document.documentElement.scrollHeight,
            document.body.offsetHeight, document.documentElement.offsetHeight,
            document.body.clientHeight, document.documentElement.clientHeight
        );
        const windowHeight = window.innerHeight;
        const maxScroll = docHeight - windowHeight;
        if (maxScroll <= 0) return;
        const percent = (scrollTop / maxScroll) * 100;
        currentState.maxScrollDepth = Math.max(currentState.maxScrollDepth, percent);
    }

    function detectBrowser() {
        const ua = navigator.userAgent;
        if (/Edg\//.test(ua)) return 'Edge';
        if (/OPR\//.test(ua) || /Opera/.test(ua)) return 'Opera';
        if (/Chrome\//.test(ua)) return 'Chrome';
        if (/Safari\//.test(ua) && !/Chrome\//.test(ua)) return 'Safari';
        if (/Firefox\//.test(ua)) return 'Firefox';
        return 'Other';
    }

    function detectBrowserVersion() {
        const ua = navigator.userAgent;
        const match = ua.match(/(Chrome|Firefox|Version|Edg|OPR)[\/ ](\d+(\.\d+)?)/);
        return match ? match[2] : null;
    }

    function detectOS() {
        const ua = navigator.userAgent;
        if (/Windows/.test(ua)) return 'Windows';
        if (/Mac OS X/.test(ua)) return 'macOS';
        if (/Android/.test(ua)) return 'Android';
        if (/iPhone|iPad|iPod/.test(ua)) return 'iOS';
        if (/Linux/.test(ua)) return 'Linux';
        return 'Other';
    }

    function detectOSVersion() {
        const ua = navigator.userAgent;
        const mac = ua.match(/Mac OS X ([\d_]+)/);
        if (mac) return mac[1].replace(/_/g, '.');
        const windows = ua.match(/Windows NT ([\d.]+)/);
        if (windows) return windows[1];
        const android = ua.match(/Android ([\d.]+)/);
        if (android) return android[1];
        const ios = ua.match(/OS ([\d_]+) like Mac OS X/);
        if (ios) return ios[1].replace(/_/g, '.');
        return null;
    }

    function detectDeviceType() {
        const ua = navigator.userAgent;
        if (/Mobile|iPhone|Android/.test(ua)) return 'Mobile';
        if (/iPad|Tablet/.test(ua)) return 'Tablet';
        if (/bot|crawl|spider/i.test(ua)) return 'Bot';
        return 'Desktop';
    }

    function getConnectionInfo() {
        const navConn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (!navConn) return {};
        return {
            type: navConn.type || null,
            effectiveType: navConn.effectiveType || null,
            downlink: navConn.downlink || null,
            rtt: navConn.rtt || null,
            saveData: navConn.saveData || false,
        };
    }

    function getBatteryInfo() {
        if (!navigator.getBattery) return Promise.resolve({});
        return navigator.getBattery().then((battery) => ({
            battery_charging: battery.charging,
            battery_level: Math.round((battery.level || 0) * 100),
        })).catch(() => ({}));
    }

    function storageAvailable(type) {
        try {
            const storage = window[type];
            const test = '__h3x__';
            storage.setItem(test, test);
            storage.removeItem(test);
            return true;
        } catch (e) {
            return false;
        }
    }

    let ipInfoCache = null;
    function getIpInfo() {
        if (ipInfoCache) return Promise.resolve(ipInfoCache);
        const ip = (window.APP_CLIENT_IP || '').trim();
        if (isLocalIp(ip)) return Promise.resolve(null);

        return fetch('https://ip-api.com/json/' + encodeURIComponent(ip || ''), { method: 'GET', mode: 'cors' })
            .then((res) => res.ok ? res.json() : null)
            .then((data) => {
                ipInfoCache = data;
                return data;
            })
            .catch(() => null);
    }

    function isLocalIp(ip) {
        if (!ip) return true;
        return ip.startsWith('127.') ||
            ip.startsWith('10.') ||
            ip.startsWith('192.168.') ||
            /^172\.(1[6-9]|2\d|3[0-1])\./.test(ip) ||
            ip === '::1' ||
            ip.startsWith('fc') ||
            ip.startsWith('fd') ||
            ip.startsWith('fe80:');
    }

    function getParameterByName(name) {
        return new URLSearchParams(window.location.search).get(name);
    }

    function getParam(name) {
        return getParameterByName(name);
    }

    function getCanvasFingerprint() {
        try {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            ctx.textBaseline = 'top';
            ctx.font = "16px 'Arial'";
            ctx.fillStyle = '#f60';
            ctx.fillRect(125, 1, 62, 20);
            ctx.fillStyle = '#069';
            ctx.fillText('h3x.to fingerprint', 2, 15);
            ctx.fillStyle = 'rgba(102, 200, 0, 0.7)';
            ctx.fillText('h3x.to fingerprint', 4, 17);
            const data = canvas.toDataURL();
            return simpleHash(data);
        } catch (e) {
            return null;
        }
    }

    function getWebGLInfo() {
        try {
            const canvas = document.createElement('canvas');
            const gl = canvas.getContext('webgl') || canvas.getContext('experimental-webgl');
            if (!gl) return {};
            const debugInfo = gl.getExtension('WEBGL_debug_renderer_info');
            if (!debugInfo) return {};
            return {
                vendor: gl.getParameter(debugInfo.UNMASKED_VENDOR_WEBGL) || null,
                renderer: gl.getParameter(debugInfo.UNMASKED_RENDERER_WEBGL) || null,
            };
        } catch (e) {
            return {};
        }
    }

    function getAudioFingerprint() {
        const OfflineCtx = window.OfflineAudioContext || window.webkitOfflineAudioContext;
        if (OfflineCtx) {
            try {
                const ctx = new OfflineCtx(1, 44100, 44100);
                const oscillator = ctx.createOscillator();
                oscillator.type = 'triangle';
                oscillator.frequency.value = 10000;

                const compressor = ctx.createDynamicsCompressor();
                compressor.threshold.value = -50;
                compressor.knee.value = 40;
                compressor.ratio.value = 12;
                compressor.attack.value = 0;
                compressor.release.value = 0.25;

                oscillator.connect(compressor);
                compressor.connect(ctx.destination);
                oscillator.start(0);
                oscillator.stop(0.05);

                return ctx.startRendering().then((buffer) => {
                    const channelData = buffer.getChannelData(0);
                    const sample = Array.from(channelData.slice(0, 20)).join(',');
                    return simpleHash(sample);
                }).catch(() => null);
            } catch (e) {
                return Promise.resolve(null);
            }
        }

        // Safari fallback: do nothing to avoid audible output
        return Promise.resolve(null);
    }

    function getFontList() {
        try {
            const fonts = new Set();
            if (document.fonts && document.fonts.forEach) {
                document.fonts.forEach((font) => fonts.add(font.family));
            }
            return Array.from(fonts);
        } catch (e) {
            return [];
        }
    }

    function getPluginList() {
        try {
            return Array.from(navigator.plugins || []).map((p) => p.name).slice(0, 20);
        } catch (e) {
            return [];
        }
    }

    function detectAdblock() {
        try {
            const bait = document.createElement('div');
            bait.className = 'adsbox';
            bait.style.position = 'absolute';
            bait.style.left = '-999px';
            document.body.appendChild(bait);
            const blocked = getComputedStyle(bait).display === 'none' || bait.clientHeight === 0;
            document.body.removeChild(bait);
            return blocked;
        } catch (e) {
            return null;
        }
    }

    function simpleHash(str) {
        let hash = 0;
        if (!str) return null;
        for (let i = 0; i < str.length; i++) {
            hash = ((hash << 5) - hash) + str.charCodeAt(i);
            hash |= 0;
        }
        return hash.toString(16);
    }

    function readPerformanceTiming() {
        const nav = performance.getEntriesByType('navigation')[0];
        if (nav) {
            return {
                dns: Math.round(nav.domainLookupEnd - nav.domainLookupStart),
                tcp: Math.round(nav.connectEnd - nav.connectStart),
                request: Math.round(nav.responseStart - nav.requestStart),
                response: Math.round(nav.responseEnd - nav.responseStart),
                domProcessing: Math.round(nav.domComplete - nav.domLoading || 0),
                domContentLoaded: Math.round(nav.domContentLoadedEventEnd - nav.startTime),
                load: Math.round(nav.duration),
            };
        }

        const timing = performance.timing || {};
        return {
            dns: delta(timing.domainLookupStart, timing.domainLookupEnd),
            tcp: delta(timing.connectStart, timing.connectEnd),
            request: delta(timing.requestStart, timing.responseStart),
            response: delta(timing.responseStart, timing.responseEnd),
            domProcessing: delta(timing.domLoading, timing.domComplete),
            domContentLoaded: delta(timing.navigationStart, timing.domContentLoadedEventEnd),
            load: delta(timing.navigationStart, timing.loadEventEnd),
        };
    }

    function sendAnalyticsPayload(payload, useBeacon) {
        const body = JSON.stringify(payload);

        if (useBeacon && navigator.sendBeacon) {
            try {
                navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
                return;
            } catch (e) {
                // fall back to fetch
            }
        }

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            keepalive: useBeacon,
            body,
        }).catch(() => {});
    }

    function delta(start, end) {
        if (!start || !end) return null;
        return Math.max(0, Math.round(end - start));
    }

    window.h3xAnalytics = window.h3xAnalytics || {};
    window.h3xAnalytics.trackInteraction = trackInteraction;
})();
