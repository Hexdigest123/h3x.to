(function ($) {
    let navTrigger = null;
    let releaseNavFocusTrap = null;

    function trapFocus(container) {
        const focusableSelector = 'a[href], button:not([disabled]), input, textarea, select, [tabindex]';
        const target = $(container);

        if (!target.length) {
            return function () {};
        }

        function onKeyDown(event) {
            if (event.key !== 'Tab') return;

            const focusable = target.find(focusableSelector).filter(':visible');
            if (!focusable.length) {
                event.preventDefault();
                return;
            }

            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            const activeElement = document.activeElement;

            if (event.shiftKey) {
                if (activeElement === first || !target[0].contains(activeElement)) {
                    event.preventDefault();
                    last.focus();
                }
                return;
            }

            if (activeElement === last || !target[0].contains(activeElement)) {
                event.preventDefault();
                first.focus();
            }
        }

        target.on('keydown.trapFocus', onKeyDown);

        return function () {
            target.off('keydown.trapFocus', onKeyDown);
        };
    }

    function toggleNav(panel, state) {
        if (!panel.length) return;
        const shouldOpen = typeof state === 'boolean' ? state : !panel.hasClass('is-open');
        panel.toggleClass('is-open', shouldOpen).attr('aria-hidden', !shouldOpen);
        $('body').toggleClass('lock-scroll', shouldOpen);
        $('[data-target="#' + panel.attr('id') + '"]').attr('aria-expanded', shouldOpen);

        if (shouldOpen) {
            navTrigger = document.activeElement;
            if (releaseNavFocusTrap) {
                releaseNavFocusTrap();
            }
            releaseNavFocusTrap = trapFocus(panel.find('.nav-panel__inner'));

            const firstNavLink = panel.find('nav ul li a').first();
            if (firstNavLink.length) {
                firstNavLink[0].focus();
            }
            return;
        }

        if (releaseNavFocusTrap) {
            releaseNavFocusTrap();
            releaseNavFocusTrap = null;
        }

        if (navTrigger && typeof navTrigger.focus === 'function') {
            navTrigger.focus();
        }
    }

    function toggleLost(panel, state) {
        if (!panel.length) return;
        const shouldOpen = typeof state === 'boolean' ? state : !panel.hasClass('is-visible');
        panel.toggleClass('is-visible', shouldOpen).attr('aria-hidden', !shouldOpen);
        $('body').toggleClass('lock-scroll', shouldOpen);
        $('[data-action="lost"]').attr('aria-expanded', shouldOpen);
    }

    function filterPosts(query) {
        const normalized = query.trim().toLowerCase();
        let prefix = null;
        let textMatch = normalized;

        const prefixMatch = normalized.match(/^(bug|bugs|project|projects|notes):\s*/);
        if (prefixMatch) {
            prefix = prefixMatch[1].replace(/s$/, '');
            textMatch = normalized.slice(prefixMatch[0].length).trim();
        }

        $('.blog-card').each(function () {
            const $card = $(this);
            const category = ($card.data('category') || '').toString().toLowerCase();
            const haystack = ($card.data('search') || '').toString();
            const matchesCategory = !prefix || category.startsWith(prefix);
            const matchesText = !textMatch || haystack.includes(textMatch);
            const isVisible = matchesCategory && matchesText;

            $card.toggleClass('is-hidden', !isVisible);
        });

        // Hide section shells that have no visible cards
        $('.blog-section').each(function () {
            const $section = $(this);
            const hasVisible = $section.find('.blog-card').not('.is-hidden').length > 0;
            $section.toggleClass('is-section-hidden', !hasVisible);
        });

        // Show "no results" message when all cards hidden
        const totalVisible = $('.blog-card').not('.is-hidden').length;
        $('.search-no-results').toggleClass('is-visible', totalVisible === 0 && normalized.length > 0);
    }

    $(function () {
        const navPanel = $('#nav-panel');
        const lostPanel = $('#lost-panel');
        const intro = $('#intro-screen');
        const searchInput = $('#post-search');
        const blogModal = $('.blog-modal');
        const modalTitle = blogModal.find('.blog-modal__title');
        const modalMeta = blogModal.find('.blog-modal__meta');
        const modalBody = blogModal.find('.blog-modal__body');
        const modalBodyClassTarget = $('body');
        const searchStatus = $('#search-status');
        let blogModalTrigger = null;
        let releaseBlogModalFocusTrap = null;
        let searchStatusTimer = null;

        function dismissIntro() {
            if (intro.hasClass('is-hidden')) return;
            intro.addClass('is-hidden');
            $('body').addClass('intro-complete');
        }

        setTimeout(dismissIntro, 1700);

        intro.on('click', dismissIntro);

        const analytics = (window.h3xAnalytics && typeof window.h3xAnalytics.trackInteraction === 'function')
            ? window.h3xAnalytics
            : null;

        function trackInteraction(type, details) {
            if (!analytics) return;
            analytics.trackInteraction(type, details);
        }

        function openBlogModal() {
            blogModalTrigger = document.activeElement;
            blogModal.addClass('is-open');
            modalBodyClassTarget.addClass('lock-scroll modal-open');
            if (releaseBlogModalFocusTrap) {
                releaseBlogModalFocusTrap();
            }
            releaseBlogModalFocusTrap = trapFocus(blogModal.find('.blog-modal__dialog'));
            if (modalTitle.length) {
                modalTitle.attr('tabindex', '-1');
                modalTitle[0].focus();
            }
        }

        function closeBlogModal() {
            blogModal.removeClass('is-open');
            modalBodyClassTarget.removeClass('lock-scroll modal-open');
            if (releaseBlogModalFocusTrap) {
                releaseBlogModalFocusTrap();
                releaseBlogModalFocusTrap = null;
            }
            if (blogModalTrigger && typeof blogModalTrigger.focus === 'function') {
                blogModalTrigger.focus();
            }
        }

        if (searchInput.length) {
            searchInput.on('input', function () {
                filterPosts($(this).val());
                if (searchStatusTimer) {
                    clearTimeout(searchStatusTimer);
                }
                searchStatusTimer = setTimeout(function () {
                    const visibleCount = $('.blog-card').not('.is-hidden').length;
                    searchStatus.text(visibleCount + (visibleCount === 1 ? ' post shown' : ' posts shown'));
                }, 300);
            });
        }

        $('.blog-card').on('click', '.read-more-btn', function () {
            const card = $(this).closest('.blog-card');
            const fullContent = card.find('.blog-card__content').html();
            const title = card.find('h3').text();
            const meta = card.find('.blog-card__meta').text();

            modalTitle.text(title);
            modalMeta.text(meta);
            modalBody.html(fullContent || '<p>No content</p>');

            openBlogModal();

            trackInteraction('post_open', { title: title, target: title, meta: 'read_more' });
        });

        $('.blog-modal').on('click', '[data-dismiss="blog-modal"]', function () {
            closeBlogModal();
        });

        blogModal.on('click', function (event) {
            if (event.target === this) {
                closeBlogModal();
            }
        });

        $('.menu-toggle').on('click', function () {
            toggleNav(navPanel);
        });

        navPanel.on('click', '[data-dismiss="panel"]', function () {
            toggleNav(navPanel, false);
        });

        navPanel.on('click', 'a', function () {
            toggleNav(navPanel, false);
            const link = $(this);
            const label = link.text().trim();
            const href = link.attr('href') || '';
            trackInteraction('nav_click', { title: label, target: href || label });
        });

        $('[data-action="lost"]').on('click', function () {
            toggleLost(lostPanel, true);
        });

        lostPanel.on('click', '[data-dismiss="lost"]', function () {
            toggleLost(lostPanel, false);
        });

        $(document).on('keyup', function (event) {
            if (event.key === 'Escape') {
                if (blogModal.hasClass('is-open')) { closeBlogModal(); return; }
                if (navPanel.hasClass('is-open')) { toggleNav(navPanel, false); return; }
                if (lostPanel.hasClass('is-visible')) { toggleLost(lostPanel, false); return; }
            }
        });

        lostPanel.on('click', function (event) {
            if (event.target === this) {
                toggleLost(lostPanel, false);
            }
        });

        navPanel.on('click', function (event) {
            if (event.target === this) {
                toggleNav(navPanel, false);
            }
        });

        $('.file-input-label input[type="file"]').on('change', function () {
            const label = $(this).closest('.file-input-label');
            const span = label.find('span');
            if (this.files && this.files.length > 0) {
                span.text(this.files[0].name);
                label.addClass('has-file');
                if (!label.next('.file-clear-btn').length) {
                    $('<button type="button" class="file-clear-btn" aria-label="Clear file selection">&times;</button>')
                        .insertAfter(label);
                }
            } else {
                span.text('Choose file');
                label.removeClass('has-file');
                label.next('.file-clear-btn').remove();
            }
        });

        $(document).on('click', '.file-clear-btn', function () {
            const label = $(this).prev('.file-input-label');
            const input = label.find('input[type="file"]');
            input.val('');
            label.find('span').text('Choose file');
            label.removeClass('has-file');
            $(this).remove();
        });

        $(document).on('click', '[data-confirm]', function (e) {
            if (!confirm($(this).data('confirm'))) {
                e.preventDefault();
            }
        });

        $(document).on('click', '[data-confirm-import]', function (e) {
            const form = $(this).closest('form');
            const fileInput = form.find('input[type="file"]');
            if (!fileInput.val()) {
                e.preventDefault();
                alert('Please select a JSON file first.');
                return;
            }
            if (!confirm('Import posts from the selected file?')) {
                e.preventDefault();
            }
        });

        // Track hover/read interest on blog cards
        const hoverTimers = new WeakMap();
        const hoverTracked = new WeakMap();

        $('.blog-card').on('mouseenter', function () {
            const card = this;
            if (hoverTracked.get(card)) return;

            const timer = setTimeout(function () {
                const title = $(card).find('h3').text();
                trackInteraction('post_hover', { title: title, target: title, durationMs: 2000, meta: 'hover' });
                hoverTracked.set(card, true);
            }, 2000);

            hoverTimers.set(card, timer);
        });

        $('.blog-card').on('mouseleave', function () {
            const timer = hoverTimers.get(this);
            if (timer) {
                clearTimeout(timer);
                hoverTimers.delete(this);
            }
        });
    });
})(jQuery);
