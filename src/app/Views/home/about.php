<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<h1><?php echo htmlspecialchars($title ?? '', ENT_QUOTES, 'UTF-8'); ?></h1>
<p><?php echo htmlspecialchars($description ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
<p>DAS IST DIE ABOUT PAGE</p>

<div class="welcome-box">
    <h2>Willkommen zum MVC Boilerplate</h2>
    <p>Dies ist eine einfache MVC-Struktur in PHP.</p>
    <ul>
        <li>Model-View-Controller Pattern</li>
        <li>Routing System</li>
        <li>Datenbankanbindung mit PDO</li>
        <li>Autoloading</li>
    </ul>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
