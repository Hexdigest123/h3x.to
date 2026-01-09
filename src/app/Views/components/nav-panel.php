<aside class="nav-panel" id="nav-panel" aria-hidden="true">
    <div class="nav-panel__inner">
        <div class="nav-panel__brand">
            <span class="nav-panel__logo"><?php echo htmlspecialchars($brand ?? 'H3x'); ?></span>
            <?php if (!empty($brandTagline ?? '')): ?>
                <p class="nav-panel__tagline"><?php echo htmlspecialchars($brandTagline); ?></p>
            <?php endif; ?>
        </div>
        <nav>
            <ul>
                <?php foreach ($navLinks as $link): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($link['href']); ?>">
                            <span class="nav-icon" aria-hidden="true">
                                <img src="<?php echo htmlspecialchars($link['icon']); ?>" alt="">
                            </span>
                            <?php echo htmlspecialchars($link['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <button class="ghost-arrow" data-dismiss="panel">
            <span class="sr-only">Close navigation</span>
            <span></span>
        </button>
    </div>
</aside>
