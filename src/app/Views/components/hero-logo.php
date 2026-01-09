<section class="hero" id="top">
    <div class="hero-text">
        <?php foreach ($heroLayers as $layer): ?>
            <span class="hero-layer <?php echo $layer['variant']; ?>"><?php echo htmlspecialchars($layer['text']); ?></span>
        <?php endforeach; ?>
    </div>
    <p class="hero-subline"><?php echo htmlspecialchars($heroSubline); ?></p>
</section>
