<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<section class="admin-shell">
    <div class="admin-hero">
        <p class="admin-eyebrow">Signed in as <?php echo htmlspecialchars($currentUser['name'] ?? 'admin'); ?></p>
        <h1>Operations dashboard</h1>
        <p class="admin-subtitle">Quick view of the signals already collected on the public page.</p>
        <div class="admin-actions">
            <a class="primary-btn ghost" href="<?php echo BASE_URL; ?>">View site</a>
            <a class="primary-btn danger ghost" href="<?php echo BASE_URL; ?>admin/logout">Logout</a>
        </div>
    </div>

    <div class="stat-grid">
        <div class="stat-card">
            <p class="stat-label">Entries collected</p>
            <p class="stat-value"><?php echo (int) ($postStats['total'] ?? 0); ?></p>
            <p class="stat-subtext">
                <?php echo (int) ($postStats['public'] ?? 0); ?> public ·
                <?php echo (int) ($postStats['private'] ?? 0); ?> private
            </p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Latest signal</p>
            <?php if (!empty($postStats['latest'])): ?>
                <p class="stat-value"><?php echo htmlspecialchars($postStats['latest']->title); ?></p>
                <p class="stat-subtext">
                    <?php echo ucfirst(htmlspecialchars($postStats['latest']->category)); ?> ·
                    <?php echo htmlspecialchars(date('M j, Y', strtotime($postStats['latest']->date))); ?> ·
                    <?php echo htmlspecialchars($postStats['latest']->status); ?>
                </p>
            <?php else: ?>
                <p class="stat-empty">No entries yet.</p>
            <?php endif; ?>
        </div>
        <div class="stat-card">
            <p class="stat-label">Navigation links live</p>
            <p class="stat-value"><?php echo (int) ($linkStats['active'] ?? 0); ?></p>
            <p class="stat-subtext">
                <?php echo (int) ($linkStats['inactive'] ?? 0); ?> disabled of <?php echo (int) ($linkStats['total'] ?? 0); ?> total
            </p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Admin users</p>
            <p class="stat-value"><?php echo is_array($users) ? count($users) : 0; ?></p>
            <p class="stat-subtext">Accounts able to log in</p>
        </div>
        <div class="stat-card">
            <p class="stat-label">Visitor sessions</p>
            <p class="stat-value"><?php echo (int) ($visitorSummary->sessions ?? 0); ?></p>
            <p class="stat-subtext">
                <?php echo (int) ($visitorSummary->total_page_views ?? 0); ?> page views ·
                <?php echo (int) ($visitorSummary->engaged_sessions ?? 0); ?> engaged ·
                <?php echo (int) ($visitorSummary->bounce_sessions ?? 0); ?> bounce
            </p>
        </div>
    </div>

    <div class="insight-grid">
        <div class="insight-card">
            <div class="insight-header">
                <h2>Category spread</h2>
                <span class="pill">Content mix</span>
            </div>
            <?php if (!empty($postStats['categories'])): ?>
                <div class="category-pills">
                    <?php foreach ($postStats['categories'] as $category => $count): ?>
                        <span class="pill pill-ghost">
                            <?php echo htmlspecialchars(ucfirst($category)); ?>
                            <span class="pill-count"><?php echo (int) $count; ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="stat-empty">No content collected yet.</p>
            <?php endif; ?>
        </div>

        <div class="insight-card">
            <div class="insight-header">
                <h2>Audience mix</h2>
                <span class="pill">Browsers & regions</span>
            </div>
            <div class="two-col-list">
                <div>
                    <p class="muted">Top browsers</p>
                    <?php if (!empty($topBrowsers)): ?>
                        <ul class="insight-list">
                            <?php foreach ($topBrowsers as $browser): ?>
                                <li>
                                    <div>
                                        <strong><?php echo htmlspecialchars($browser->browser); ?></strong>
                                    </div>
                                    <span class="pill pill-ghost"><?php echo (int) $browser->total; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="stat-empty">No browser data yet.</p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="muted">Top countries</p>
                    <?php if (!empty($topCountries)): ?>
                        <ul class="insight-list">
                            <?php foreach ($topCountries as $country): ?>
                                <li>
                                    <div>
                                        <strong><?php echo htmlspecialchars($country->ip_country); ?></strong>
                                    </div>
                                    <span class="pill pill-ghost"><?php echo (int) $country->total; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="stat-empty">No country signals yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="insight-card">
            <div class="insight-header">
                <h2>Recent posts</h2>
                <span class="pill">Fresh</span>
            </div>
            <?php if (!empty($recentPosts)): ?>
                <ul class="insight-list">
                    <?php foreach ($recentPosts as $post): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($post->title); ?></strong>
                                <p class="muted">
                                    <?php echo ucfirst(htmlspecialchars($post->category ?? 'notes')); ?> ·
                                    <?php echo htmlspecialchars(date('M j, Y', strtotime($post->published_at ?: $post->created_at ?? 'now'))); ?>
                                </p>
                            </div>
                            <span class="pill <?php echo !empty($post->is_public) ? 'pill-success' : 'pill-warn'; ?>">
                                <?php echo !empty($post->is_public) ? 'Public' : 'Draft'; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="stat-empty">Nothing to list.</p>
            <?php endif; ?>
        </div>

        <div class="insight-card">
            <div class="insight-header">
                <h2>Social links</h2>
                <span class="pill">Routes</span>
            </div>
            <p class="stat-subtext">
                <?php echo (int) ($linkStats['active'] ?? 0); ?> active ·
                <?php echo (int) ($linkStats['inactive'] ?? 0); ?> disabled
            </p>
            <?php if (!empty($socialLinks)): ?>
                <ul class="insight-list">
                    <?php foreach ($socialLinks as $link): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($link->name); ?></strong>
                                <p class="muted"><?php echo htmlspecialchars($link->url); ?></p>
                            </div>
                            <span class="pill <?php echo !empty($link->is_active) ? 'pill-success' : 'pill-warn'; ?>">
                                <?php echo !empty($link->is_active) ? 'Active' : 'Disabled'; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="stat-empty">No navigation links configured.</p>
            <?php endif; ?>
        </div>

        <div class="insight-card">
            <div class="insight-header">
                <h2>Access list</h2>
                <span class="pill">Accounts</span>
            </div>
            <?php if (!empty($users)): ?>
                <ul class="insight-list">
                    <?php foreach ($users as $user): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($user->name); ?></strong>
                                <p class="muted">
                                    <?php
                                    $email = $user->email ?? null;
                                    echo $email ? htmlspecialchars($email) : 'No email set';
                                    ?>
                                </p>
                            </div>
                            <span class="pill pill-ghost">#<?php echo (int) $user->id; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="stat-empty">No user records present.</p>
            <?php endif; ?>
        </div>

        <div class="insight-card">
            <div class="insight-header">
                <h2>Recent visitors</h2>
                <span class="pill">Live</span>
            </div>
            <?php if (!empty($recentSessions)): ?>
                <ul class="insight-list">
                    <?php foreach ($recentSessions as $session): ?>
                        <li>
                            <div>
                                <strong><?php echo htmlspecialchars($session->browser ?? 'Unknown'); ?></strong>
                                <p class="muted">
                                    <?php echo htmlspecialchars($session->ip_country ?? ''); ?>
                                    <?php if (!empty($session->ip_city)): ?> · <?php echo htmlspecialchars($session->ip_city); ?><?php endif; ?>
                                    <?php if (!empty($session->ip_region)): ?> · <?php echo htmlspecialchars($session->ip_region); ?><?php endif; ?>
                                </p>
                                <p class="muted">
                                    <?php echo htmlspecialchars($session->device ?? ''); ?>
                                    · <?php echo htmlspecialchars($session->os ?? ''); ?>
                                </p>
                                <p class="muted">
                                    <?php echo htmlspecialchars($session->entry_page ?? ''); ?>
                                </p>
                            </div>
                            <span class="pill pill-ghost">
                                <?php echo (int) ($session->page_views ?? 0); ?> pages
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="stat-empty">No visitor sessions recorded yet.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require __DIR__ . '/../components/nav-panel.php'; ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
