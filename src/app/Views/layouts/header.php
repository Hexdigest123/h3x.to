<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?php echo $description ?? 'H3x security portfolio'; ?>">
    <title><?php echo $title ?? APP_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>css/main.css">
</head>
<body class="site-body">
<div class="texture" aria-hidden="true"></div>
<header class="site-header">
    <div class="brand-group">
        <a class="brand-mark" href="<?php echo BASE_URL; ?>" aria-label="Return to welcome screen">
            <span><?php echo $brand ?? 'H3x'; ?></span>
        </a>
        <span class="brand-label"><?php echo $brandTagline ?? 'Field Notes'; ?></span>
    </div>
    <div class="header-actions">
        <button class="icon-btn" data-action="lost" aria-controls="lost-panel" aria-expanded="false">
            <span class="sr-only">Open the lost helper</span>
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M12 3l2 4 4.5.6-3.3 3.2.9 4.4L12 13l-4.1 2.2.9-4.4-3.3-3.2L10 7z" fill="currentColor"></path>
            </svg>
        </button>
        <button class="icon-btn menu-toggle" data-target="#nav-panel" aria-controls="nav-panel" aria-expanded="false">
            <span class="sr-only">Toggle navigation</span>
            <span class="menu-icon">
                <span></span>
                <span></span>
            </span>
        </button>
    </div>
</header>
<main class="site-main">
