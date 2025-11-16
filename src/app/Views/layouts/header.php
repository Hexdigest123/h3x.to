<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo $description ?? 'H3x security portfolio'; ?>">
    <title><?php echo $title ?? APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jacques+Francois+Shadow&family=Playfair+Display:wght@400;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css?v=2">
    <script>
        window.APP_BASE_URL = "<?php echo BASE_URL; ?>";
        window.APP_CLIENT_IP = "<?php echo $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ($_SERVER['REMOTE_ADDR'] ?? ''); ?>";
    </script>
</head>
<body class="site-body">
<div class="intro-screen" id="intro-screen">
    <div class="intro-mark">H3x</div>
</div>
<div class="texture" aria-hidden="true"></div>
    <header class="site-header">
        <a class="brand-mark" href="<?php echo BASE_URL; ?>" aria-label="Return to welcome screen">
            <span class="brand-icon"><?php echo $brand ?? 'H3x'; ?></span>
            <?php if (!empty($brandTagline ?? '')): ?>
                <span class="brand-label"><?php echo $brandTagline; ?></span>
            <?php endif; ?>
        </a>
    <button class="icon-btn menu-toggle" data-target="#nav-panel" aria-controls="nav-panel" aria-expanded="false">
        <span class="sr-only">Toggle navigation</span>
        <img src="<?php echo BASE_URL; ?>images/burger.svg" alt="" loading="lazy">
    </button>
</header>
<main class="site-main">
