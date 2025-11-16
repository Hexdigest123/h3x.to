<section class="lost-panel" id="lost-panel" aria-hidden="true">
    <div class="lost-card">
        <div class="lost-illustration" aria-hidden="true">
            <div class="sun"></div>
            <div class="path"></div>
            <div class="hiker"></div>
        </div>
        <p class="lost-label"><?php echo htmlspecialchars($lost['label']); ?></p>
        <h2><?php echo htmlspecialchars($lost['title']); ?></h2>
        <p><?php echo htmlspecialchars($lost['message']); ?></p>
        <button class="ghost-arrow" data-dismiss="lost">
            <span class="sr-only">Back to main content</span>
            <span></span>
        </button>
    </div>
</section>
