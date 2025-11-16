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

    <?php if (!empty($flash['error']) || !empty($flash['success'])): ?>
        <div class="alert-card<?php echo !empty($flash['success']) ? ' alert-success' : ''; ?>" role="status">
            <?php if (!empty($flash['success'])): ?>
                <strong>Saved</strong>
                <ul>
                    <?php foreach ($flash['success'] as $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($flash['error'])): ?>
                <strong>Needs attention</strong>
                <ul>
                    <?php foreach ($flash['error'] as $message): ?>
                        <li><?php echo htmlspecialchars($message); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

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

    <div class="crud-grid">
        <div class="crud-card">
            <div class="crud-header">
                <h2>Create post</h2>
                <span class="pill">Frontend feed</span>
            </div>
            <form method="POST" action="<?php echo BASE_URL; ?>admin/createPost" class="crud-form">
                <div class="crud-row">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" required placeholder="New finding">
                </div>
                <div class="crud-row">
                    <label for="slug">Slug (optional)</label>
                    <input id="slug" name="slug" type="text" placeholder="new-finding">
                </div>
                <div class="crud-row two-col">
                    <div>
                        <label for="category">Category</label>
                        <select id="category" name="category">
                            <option value="projects">Projects</option>
                            <option value="bugs">Bugs</option>
                        </select>
                    </div>
                    <div>
                        <label for="short_description">Summary</label>
                        <input id="short_description" name="short_description" type="text" placeholder="Short description for cards">
                    </div>
                </div>
                <div class="crud-row">
                    <label for="description">Description (plain text)</label>
                    <textarea id="description" name="description" rows="2" placeholder="One-liner about this post"></textarea>
                </div>
                <div class="crud-row">
                    <label for="html">Body (HTML allowed)</label>
                    <textarea id="html" name="html" rows="5" placeholder="<p>Result details...</p>" required></textarea>
                </div>
                <div class="crud-actions">
                    <label class="checkbox-field">
                        <input type="checkbox" name="is_public" value="1" checked>
                        <span>Publish immediately</span>
                    </label>
                    <button type="submit" class="primary-btn">Save post</button>
                </div>
            </form>
        </div>
        <div class="crud-card">
            <div class="crud-header">
                <h2>Create navigation link</h2>
                <span class="pill">Social/cards</span>
            </div>
            <form method="POST" action="<?php echo BASE_URL; ?>admin/createLink" class="crud-form">
                <div class="crud-row">
                    <label for="link_name">Label</label>
                    <input id="link_name" name="name" type="text" required placeholder="GitHub">
                </div>
                <div class="crud-row">
                    <label for="link_url">URL</label>
                    <input id="link_url" name="url" type="url" required placeholder="https://github.com/hexdigest">
                </div>
                <div class="crud-row two-col">
                    <div>
                        <label for="icon_path">Icon path</label>
                        <input id="icon_path" name="icon_path" type="text" placeholder="/images/github.svg">
                    </div>
                    <div>
                        <label for="display_order">Order</label>
                        <input id="display_order" name="display_order" type="number" min="0" value="0">
                    </div>
                </div>
                <div class="crud-actions">
                    <label class="checkbox-field">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>Set active</span>
                    </label>
                    <button type="submit" class="primary-btn">Save link</button>
                </div>
            </form>
        </div>
    </div>

    <div class="crud-grid">
        <div class="crud-card">
            <div class="crud-header">
                <h2>Manage posts</h2>
                <span class="pill pill-ghost"><?php echo (int) ($postStats['total'] ?? 0); ?> total</span>
            </div>
            <?php if (!empty($allPosts)): ?>
                <div class="crud-list">
                    <?php foreach ($allPosts as $post): ?>
                        <details class="crud-item">
                            <summary>
                                <div>
                                    <strong><?php echo htmlspecialchars($post->title); ?></strong>
                                    <p class="muted"><?php echo htmlspecialchars(ucfirst($post->category ?? 'projects')); ?> · #<?php echo (int) $post->id; ?></p>
                                </div>
                                <span class="pill <?php echo !empty($post->is_public) ? 'pill-success' : 'pill-warn'; ?>">
                                    <?php echo !empty($post->is_public) ? 'Public' : 'Draft'; ?>
                                </span>
                            </summary>
                            <form method="POST" action="<?php echo BASE_URL; ?>admin/updatePost/<?php echo (int) $post->id; ?>" class="crud-form">
                                <div class="crud-row">
                                    <label for="title_<?php echo (int) $post->id; ?>">Title</label>
                                    <input id="title_<?php echo (int) $post->id; ?>" name="title" type="text" required value="<?php echo htmlspecialchars($post->title); ?>">
                                </div>
                                <div class="crud-row two-col">
                                    <div>
                                        <label for="slug_<?php echo (int) $post->id; ?>">Slug</label>
                                        <input id="slug_<?php echo (int) $post->id; ?>" name="slug" type="text" value="<?php echo htmlspecialchars($post->slug); ?>">
                                    </div>
                                    <div>
                                        <label for="category_<?php echo (int) $post->id; ?>">Category</label>
                                        <select id="category_<?php echo (int) $post->id; ?>" name="category">
                                            <option value="projects" <?php echo strtolower($post->category ?? '') === 'projects' ? 'selected' : ''; ?>>Projects</option>
                                            <option value="bugs" <?php echo strtolower($post->category ?? '') === 'bugs' ? 'selected' : ''; ?>>Bugs</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="crud-row">
                                    <label for="short_<?php echo (int) $post->id; ?>">Summary</label>
                                    <input id="short_<?php echo (int) $post->id; ?>" name="short_description" type="text" value="<?php echo htmlspecialchars($post->short_description ?? ''); ?>">
                                </div>
                                <div class="crud-row">
                                    <label for="desc_<?php echo (int) $post->id; ?>">Description</label>
                                    <textarea id="desc_<?php echo (int) $post->id; ?>" name="description" rows="2"><?php echo htmlspecialchars($post->description ?? ''); ?></textarea>
                                </div>
                                <div class="crud-row">
                                    <label for="html_<?php echo (int) $post->id; ?>">Body (HTML allowed)</label>
                                    <textarea id="html_<?php echo (int) $post->id; ?>" name="html" rows="5" required><?php echo htmlspecialchars($post->html ?? ''); ?></textarea>
                                </div>
                                <div class="crud-actions">
                                    <label class="checkbox-field">
                                        <input type="checkbox" name="is_public" value="1" <?php echo !empty($post->is_public) ? 'checked' : ''; ?>>
                                        <span>Public</span>
                                    </label>
                                    <button type="submit" class="primary-btn">Update</button>
                                    <button
                                        type="submit"
                                        class="primary-btn danger ghost"
                                        formaction="<?php echo BASE_URL; ?>admin/deletePost/<?php echo (int) $post->id; ?>"
                                        formmethod="POST"
                                        onclick="return confirm('Delete this post?');"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </form>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="stat-empty">No posts stored yet.</p>
            <?php endif; ?>
        </div>
        <div class="crud-card">
            <div class="crud-header">
                <h2>Manage social links</h2>
                <span class="pill pill-ghost"><?php echo (int) ($linkStats['total'] ?? 0); ?> total</span>
            </div>
            <?php if (!empty($socialLinks)): ?>
                <div class="crud-list">
                    <?php foreach ($socialLinks as $link): ?>
                        <details class="crud-item">
                            <summary>
                                <div>
                                    <strong><?php echo htmlspecialchars($link->name); ?></strong>
                                    <p class="muted"><?php echo htmlspecialchars($link->url); ?></p>
                                </div>
                                <span class="pill <?php echo !empty($link->is_active) ? 'pill-success' : 'pill-warn'; ?>">
                                    <?php echo !empty($link->is_active) ? 'Active' : 'Disabled'; ?>
                                </span>
                            </summary>
                            <form method="POST" action="<?php echo BASE_URL; ?>admin/updateLink/<?php echo (int) $link->id; ?>" class="crud-form">
                                <div class="crud-row">
                                    <label for="link_name_<?php echo (int) $link->id; ?>">Label</label>
                                    <input id="link_name_<?php echo (int) $link->id; ?>" name="name" type="text" required value="<?php echo htmlspecialchars($link->name); ?>">
                                </div>
                                <div class="crud-row">
                                    <label for="link_url_<?php echo (int) $link->id; ?>">URL</label>
                                    <input id="link_url_<?php echo (int) $link->id; ?>" name="url" type="url" required value="<?php echo htmlspecialchars($link->url); ?>">
                                </div>
                                <div class="crud-row two-col">
                                    <div>
                                        <label for="link_icon_<?php echo (int) $link->id; ?>">Icon path</label>
                                        <input id="link_icon_<?php echo (int) $link->id; ?>" name="icon_path" type="text" value="<?php echo htmlspecialchars($link->icon_path); ?>">
                                    </div>
                                    <div>
                                        <label for="link_order_<?php echo (int) $link->id; ?>">Order</label>
                                        <input id="link_order_<?php echo (int) $link->id; ?>" name="display_order" type="number" min="0" value="<?php echo (int) $link->display_order; ?>">
                                    </div>
                                </div>
                                <div class="crud-actions">
                                    <label class="checkbox-field">
                                        <input type="checkbox" name="is_active" value="1" <?php echo !empty($link->is_active) ? 'checked' : ''; ?>>
                                        <span>Active</span>
                                    </label>
                                    <button type="submit" class="primary-btn">Update</button>
                                    <button
                                        type="submit"
                                        class="primary-btn danger ghost"
                                        formaction="<?php echo BASE_URL; ?>admin/deleteLink/<?php echo (int) $link->id; ?>"
                                        formmethod="POST"
                                        onclick="return confirm('Delete this link?');"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </form>
                        </details>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="stat-empty">No navigation links configured.</p>
            <?php endif; ?>
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
