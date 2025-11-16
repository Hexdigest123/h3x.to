<?php if (!empty($linkCards)): ?>
<section class="card-grid link-grid">
    <div class="card-grid__inner">
        <?php foreach ($linkCards as $link): ?>
            <a class="info-card" href="<?php echo htmlspecialchars($link['href']); ?>" target="_blank" rel="noreferrer">
                <span class="info-card__icon">
                    <img src="<?php echo htmlspecialchars($link['icon']); ?>" alt="" loading="lazy">
                </span>
                <span class="info-card__label"><?php echo htmlspecialchars($link['label']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
