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
                    <h2><?php echo ucfirst($categoryKey); ?></h2>
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
                                <span class="pill"><?php echo ucfirst($categoryKey); ?></span>
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
                            <div class="blog-card__full" hidden>
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
    <?php endif; ?>
</section>
