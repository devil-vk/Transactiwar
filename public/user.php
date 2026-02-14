<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_auth(['user', 'admin']);
log_event($pdo, '/user.php', 'page_access');

$userId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$userId) {
    http_response_code(400);
    exit('Invalid user ID');
}

$stmt = $pdo->prepare(
    'SELECT id, username, first_name, last_name, bio, profile_image, created_at FROM users WHERE id = :id LIMIT 1'
);
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if ($user === false) {
    http_response_code(404);
    exit('User not found');
}

$pageTitle = 'Public Profile';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-soft p-4">
            <div class="d-flex align-items-center gap-3">
                <img class="profile-avatar" src="/avatar.php?u=<?= e((string) $user['id']) ?>" alt="Profile image">
                <div>
                    <h1 class="h4 mb-1"><?= e((string) $user['username']) ?></h1>
                    <p class="text-muted mb-0">
                        <?= e(trim(((string) ($user['first_name'] ?? '')) . ' ' . ((string) ($user['last_name'] ?? '')))) ?>
                    </p>
                    <small class="text-muted">Member since <?= e((string) $user['created_at']) ?></small>
                </div>
            </div>
            <hr>
            <h2 class="h6">Biography</h2>
            <p style="white-space: pre-wrap;"><?= e((string) ($user['bio'] ?? 'No biography available.')) ?></p>
            <?php if ((int) $user['id'] !== current_user_id()): ?>
                <a class="btn btn-primary" href="/transfer.php?to=<?= e((string) $user['id']) ?>">Transfer Money</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
