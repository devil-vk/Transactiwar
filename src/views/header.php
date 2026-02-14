<?php

declare(strict_types=1);

$pageTitle = $pageTitle ?? 'TransactiWar';
$messages = consume_flash_messages();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/">TransactiWar</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <?php if (is_authenticated()): ?>
                    <li class="nav-item"><a class="nav-link" href="/dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/users.php">Search Users</a></li>
                    <li class="nav-item"><a class="nav-link" href="/transfer.php">Transfer</a></li>
                    <li class="nav-item"><a class="nav-link" href="/history.php">History</a></li>
                    <li class="nav-item"><a class="nav-link" href="/profile.php">My Profile</a></li>
                <?php endif; ?>
            </ul>
            <div class="d-flex align-items-center gap-2 text-white">
                <?php if (is_authenticated()): ?>
                    <span class="small">Signed in as <?= e(current_username()) ?></span>
                    <form action="/logout.php" method="post" class="m-0">
                        <?= csrf_token_field() ?>
                        <button class="btn btn-outline-light btn-sm" type="submit">Logout</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-outline-light btn-sm" href="/login.php">Login</a>
                    <a class="btn btn-warning btn-sm" href="/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
<main class="container py-4">
    <?php foreach ($messages as $msg): ?>
        <div class="alert alert-<?= e((string) ($msg['type'] ?? 'info')) ?>" role="alert">
            <?= e((string) ($msg['message'] ?? '')) ?>
        </div>
    <?php endforeach; ?>
