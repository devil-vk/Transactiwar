<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_auth(['user', 'admin']);
log_event($pdo, '/dashboard.php', 'page_access');

$userStmt = $pdo->prepare('SELECT id, username, first_name, last_name, balance_paise FROM users WHERE id = :id LIMIT 1');
$userStmt->execute([':id' => current_user_id()]);
$user = $userStmt->fetch();

if ($user === false) {
    logout_user();
    start_secure_session();
    flash('danger', 'Account not found.');
    header('Location: /login.php');
    exit;
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft p-4 h-100">
            <h1 class="h4">Welcome, <?= e((string) ($user['first_name'] ?: $user['username'])) ?></h1>
            <p class="text-muted">Current balance</p>
            <p class="display-6 fw-bold text-success"><?= e(format_paise((int) $user['balance_paise'])) ?></p>
            <a href="/transfer.php" class="btn btn-primary">Send Money</a>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4 h-100">
            <h2 class="h5">Quick Actions</h2>
            <div class="list-group mt-2">
                <a class="list-group-item list-group-item-action" href="/users.php">Search users by ID or username</a>
                <a class="list-group-item list-group-item-action" href="/history.php">View sent/received transaction history</a>
                <a class="list-group-item list-group-item-action" href="/profile.php">Update profile and upload photo</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
