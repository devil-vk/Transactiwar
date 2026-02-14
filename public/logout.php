<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

require_valid_csrf_token($_POST['csrf_token'] ?? null);

if (is_authenticated()) {
    log_event($pdo, '/logout.php', 'logout');
}

logout_user();
start_secure_session();
flash('success', 'You have been logged out.');

header('Location: /login.php');
exit;
