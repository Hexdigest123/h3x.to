(function ($) {
    function toggleNav(panel, state) {
        if (!panel.length) return;
        const shouldOpen = typeof state === 'boolean' ? state : !panel.hasClass('is-open');
        panel.toggleClass('is-open', shouldOpen).attr('aria-hidden', !shouldOpen);
        $('body').toggleClass('lock-scroll', shouldOpen);
        $('[data-target="#' + panel.attr('id') + '"]').attr('aria-expanded', shouldOpen);
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

        setTimeout(function () {
            intro.addClass('is-hidden');
            $('body').addClass('intro-complete');
        }, 1700);

        if (searchInput.length) {
            searchInput.on('input', function () {
                filterPosts($(this).val());
            });
        }

        $('.blog-card').on('click', '.read-more-btn', function () {
            const card = $(this).closest('.blog-card');
            const fullContent = card.find('.blog-card__full').html();
            const title = card.find('h3').text();
            const meta = card.find('.blog-card__meta').text();

            modalTitle.text(title);
            modalMeta.text(meta);
            modalBody.html(fullContent || '<p>No content</p>');

            blogModal.addClass('is-open');
            $('body').addClass('lock-scroll');
        });

        $('.blog-modal').on('click', '[data-dismiss="blog-modal"]', function () {
            blogModal.removeClass('is-open');
            $('body').removeClass('lock-scroll');
        });

        blogModal.on('click', function (event) {
            if (event.target === this) {
                blogModal.removeClass('is-open');
                $('body').removeClass('lock-scroll');
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
        });

        $('[data-action="lost"]').on('click', function () {
            toggleLost(lostPanel, true);
        });

        lostPanel.on('click', '[data-dismiss="lost"]', function () {
            toggleLost(lostPanel, false);
        });

        $(document).on('keyup', function (event) {
            if (event.key === 'Escape') {
                toggleNav(navPanel, false);
                toggleLost(lostPanel, false);
                blogModal.removeClass('is-open');
                $('body').removeClass('lock-scroll');
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
    });
})(jQuery);
