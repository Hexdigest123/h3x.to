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

    $(function () {
        const navPanel = $('#nav-panel');
        const lostPanel = $('#lost-panel');

        $('.menu-toggle').on('click', function () {
            toggleNav(navPanel);
        });

        navPanel.on('click', '[data-dismiss="panel"]', function () {
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
