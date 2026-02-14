<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_auth(['user', 'admin']);
log_event($pdo, '/profile.php', 'page_access');

$userId = current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token($_POST['csrf_token'] ?? null);

    $firstName = sanitize_text((string) ($_POST['first_name'] ?? ''), 100);
    $lastName = sanitize_text((string) ($_POST['last_name'] ?? ''), 100);
    $bio = sanitize_multiline_text((string) ($_POST['bio'] ?? ''), 5000);

    $updateStmt = $pdo->prepare(
        'UPDATE users SET first_name = :first_name, last_name = :last_name, bio = :bio WHERE id = :id'
    );

    $updateStmt->execute([
        ':first_name' => $firstName !== '' ? $firstName : null,
        ':last_name' => $lastName !== '' ? $lastName : null,
        ':bio' => $bio !== '' ? $bio : null,
        ':id' => $userId,
    ]);

    log_event($pdo, '/profile.php', 'profile_updated');
    flash('success', 'Profile updated successfully.');
    header('Location: /profile.php');
    exit;
}

    $stmt = $pdo->prepare('SELECT id, username, email, phone_number, first_name, last_name, bio, profile_image, balance_paise FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if ($user === false) {
    logout_user();
    start_secure_session();
    flash('danger', 'Account not found.');
    header('Location: /login.php');
    exit;
}

$pageTitle = 'My Profile';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card card-soft p-4">
            <h1 class="h5">Profile Image</h1>
            <img class="profile-avatar my-3" src="/avatar.php?u=<?= e((string) $userId) ?>" alt="Profile image">
            <form action="/upload.php" method="post" enctype="multipart/form-data">
                <?= csrf_token_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="profile_image">Upload JPG / PNG / WEBP</label>
                    <input class="form-control" type="file" id="profile_image" name="profile_image" accept="image/jpeg,image/png,image/webp" required>
                </div>
                <button class="btn btn-outline-primary" type="submit">Upload Image</button>
            </form>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h2 class="h5">Update Profile</h2>
            <form method="post">
                <?= csrf_token_field() ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="username">Username (immutable)</label>
                        <input class="form-control" id="username" type="text" value="<?= e((string) $user['username']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control" id="email" type="email" value="<?= e((string) $user['email']) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Mobile Number</label>
                        <input class="form-control" id="phone" type="text" value="<?= e((string) ($user['phone_number'] ?? '')) ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="first_name">First Name</label>
                        <input class="form-control" id="first_name" name="first_name" type="text" maxlength="100" value="<?= e((string) ($user['first_name'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="last_name">Last Name</label>
                        <input class="form-control" id="last_name" name="last_name" type="text" maxlength="100" value="<?= e((string) ($user['last_name'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="bio">Biography</label>
                        <textarea class="form-control" id="bio" name="bio" rows="6" maxlength="5000"><?= e((string) ($user['bio'] ?? '')) ?></textarea>
                    </div>
                </div>
                <button class="btn btn-primary mt-3" type="submit">Save Profile</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
