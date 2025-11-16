<aside class="nav-panel" id="nav-panel" aria-hidden="true">
    <div class="nav-panel__inner">
        <div class="nav-panel__brand">
            <span><?php echo htmlspecialchars($brand ?? 'H3x'); ?></span>
        </div>
        <nav>
            <ul>
                <?php foreach ($navLinks as $link): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($link['href']); ?>">
                            <span class="nav-icon" aria-hidden="true"><?php echo $link['icon']; ?></span>
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
