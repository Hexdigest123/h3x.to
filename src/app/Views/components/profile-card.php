<section class="panel profile-panel">
    <div class="profile-avatar">
        <img src="<?php echo htmlspecialchars($profile['avatar']); ?>" alt="Portrait of <?php echo htmlspecialchars($profile['alias']); ?>">
    </div>
    <div class="profile-body">
        <p class="profile-label"><?php echo htmlspecialchars($profile['handle']); ?></p>
        <h1><?php echo htmlspecialchars($profile['alias']); ?></h1>
        <p class="profile-role"><?php echo htmlspecialchars($profile['role']); ?></p>
        <p class="profile-summary"><?php echo htmlspecialchars($profile['summary']); ?></p>
        <div class="profile-tags">
            <?php foreach ($profile['tags'] as $tag): ?>
                <a class="tag-pill" href="<?php echo htmlspecialchars($tag['href']); ?>" target="_blank" rel="noreferrer">
                    <span class="tag-icon" aria-hidden="true"><?php echo $tag['icon']; ?></span>
                    <?php echo htmlspecialchars($tag['label']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
