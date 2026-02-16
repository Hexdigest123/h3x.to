<?php
$groupedPosts = [];
if (!empty($blogPosts)) {
    foreach ($blogPosts as $post) {
        $category = strtolower($post->category ?? 'notes');
        $groupedPosts[$category][] = $post;
    }
}
?>

<section class="blog-grid" id="blog">
    <?php if (empty($groupedPosts)): ?>
        <div class="empty-state">
            <p>No entries yet. Publish a post to see it here.</p>
        </div>
    <?php else: ?>
        <?php foreach ($groupedPosts as $categoryKey => $posts): ?>
            <?php
            $sectionId = $categoryKey === 'projects' ? 'projects' : ($categoryKey === 'bugs' ? 'bugs' : 'notes');
            ?>
            <div class="blog-section" id="<?php echo htmlspecialchars($sectionId); ?>">
                <div class="section-heading">
                    <h2><?php echo htmlspecialchars(ucfirst($categoryKey), ENT_QUOTES, 'UTF-8'); ?></h2>
                </div>
                <div class="card-grid__inner blog-card-grid">
                    <?php foreach ($posts as $post): ?>
                        <?php
                        $summary = $post->short_description ?? $post->description ?? '';
                        $fullBody = $post->html ?? '';
                        $contentLength = strlen(strip_tags($fullBody));
                        $isLong = $contentLength > 500;
                        $searchableText = strtolower(trim(($post->title ?? '') . ' ' . $summary . ' ' . strip_tags($post->html ?? '')));
                        ?>
                        <article
                            class="blog-card"
                            data-category="<?php echo htmlspecialchars($categoryKey); ?>"
                            data-search="<?php echo htmlspecialchars($searchableText, ENT_QUOTES); ?>"
                        >
                            <div class="blog-card__header">
                                <span class="pill"><?php echo htmlspecialchars(ucfirst($categoryKey), ENT_QUOTES, 'UTF-8'); ?></span>
                                <h3><?php echo htmlspecialchars($post->title); ?></h3>
                                <p class="blog-card__meta">
                                    <?php
                                    $date = $post->created_at ?? 'now';
                                    echo date('F j, Y', strtotime($date));
                                    ?>
                                </p>
                            </div>
                            <?php if (!empty($summary)): ?>
                                <p class="blog-card__summary"><?php echo htmlspecialchars($summary); ?></p>
                            <?php endif; ?>
                            <div class="blog-card__divider" aria-hidden="true"></div>
                            <div class="blog-card__content <?php echo $isLong ? 'is-collapsed' : ''; ?>">
                                <?php echo $fullBody; ?>
                            </div>

                            <?php if ($isLong): ?>
                                <button class="read-more-btn" type="button">Read more</button>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php
        $renderedSections = array_keys($groupedPosts);
        foreach (['projects', 'bugs'] as $anchor):
            if (!in_array($anchor, $renderedSections)): ?>
                <div id="<?php echo htmlspecialchars($anchor); ?>"></div>
            <?php endif;
        endforeach; ?>
    <?php endif; ?>
    <p class="search-no-results">No posts match your search.</p>
</section>
