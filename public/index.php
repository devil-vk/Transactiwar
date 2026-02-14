<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

log_event($pdo, '/index.php', 'page_access');

if (is_authenticated()) {
    header('Location: /dashboard.php');
    exit;
}

$pageTitle = 'Welcome to TransactiWar';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-soft p-4">
            <h1 class="h3 mb-3">Secure Virtual Money Transfers</h1>
            <p class="text-muted mb-4">
                TransactiWar is a native PHP platform designed with manual security controls against SQL Injection, XSS, CSRF,
                IDOR, session hijacking, and file upload abuse.
            </p>
            <div class="d-flex gap-2">
                <a class="btn btn-primary" href="/register.php">Create Account</a>
                <a class="btn btn-outline-secondary" href="/login.php">Sign In</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
