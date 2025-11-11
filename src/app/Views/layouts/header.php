<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? APP_NAME; ?></title>
    <script src="<?php echo BASE_URL; ?>js/main.js"></script>
    <script src="<?php echo BASE_URL; ?>js/jquery-3.7.1.min.js"></script>
</head>
<body>
    <nav>
        <div class="container">
            <a href="<?php echo BASE_URL; ?>"><?php echo APP_NAME; ?></a>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                <li><a href="<?php echo BASE_URL; ?>about">Über uns</a></li>
                <li><a href="<?php echo BASE_URL; ?>/user">Benutzer</a></li>
            </ul>
        </div>
    </nav>
    <main class="container">
