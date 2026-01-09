<section class="profile-hero" id="top">
    <div class="profile-hero__avatar">
        <img src="<?php echo htmlspecialchars($profile['avatar']); ?>" alt="Portrait of <?php echo htmlspecialchars($profile['alias']); ?>">
    </div>
    <div class="profile-hero__meta">
        <p class="profile-handle"><?php echo htmlspecialchars($profile['handle']); ?></p>
        <h1 class="profile-name"><?php echo htmlspecialchars($profile['alias']); ?></h1>
        <p class="profile-role"><?php echo htmlspecialchars($profile['role']); ?></p>
        <p class="profile-summary"><?php echo htmlspecialchars($profile['summary']); ?></p>
    </div>
</section>
