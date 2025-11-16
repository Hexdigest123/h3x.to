<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<section class="admin-shell">
    <div class="admin-hero">
        <p class="admin-eyebrow">Private access required</p>
        <h1>H3x Admin</h1>
        <p class="admin-subtitle">Log in with your name and credentials to review the collected signals.</p>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert-card" role="alert">
            <strong>Authentication failed</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="auth-card">
        <form method="POST" action="<?php echo BASE_URL; ?>admin/login" class="auth-form">
            <div class="form-field">
                <label for="name">Admin name</label>
                <input id="name" name="name" type="text" placeholder="Hexdigest" required>
            </div>
            <div class="form-field">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="••••••••" required>
            </div>
            <button type="submit" class="primary-btn">Enter dashboard</button>
        </form>
        <p class="auth-footnote">Need to return to the site? <a href="<?php echo BASE_URL; ?>">Head back to welcome.</a></p>
    </div>
</section>

<?php require __DIR__ . '/../components/nav-panel.php'; ?>
<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
