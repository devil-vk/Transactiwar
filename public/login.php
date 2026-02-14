<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_guest();
log_event($pdo, '/login.php', 'page_access');

$errors = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token($_POST['csrf_token'] ?? null);

    $username = sanitize_text((string) ($_POST['username'] ?? ''), 30);
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required.';
    }

    if ($errors === []) {
        $stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user !== false && verify_password_secure($password, (string) $user['password_hash'])) {
            login_user($user);

            if (password_needs_rehash((string) $user['password_hash'], defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)) {
                $rehashStmt = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                $rehashStmt->execute([
                    ':password_hash' => hash_password_secure($password),
                    ':id' => (int) $user['id'],
                ]);
            }

            log_event($pdo, '/login.php', 'login_success');

            $redirect = '/dashboard.php';
            if (isset($_SESSION['redirect_after_login']) && is_string($_SESSION['redirect_after_login']) && str_starts_with($_SESSION['redirect_after_login'], '/')) {
                $redirect = $_SESSION['redirect_after_login'];
            }
            unset($_SESSION['redirect_after_login']);

            header('Location: ' . $redirect);
            exit;
        }

        $errors[] = 'Invalid credentials.';
        log_event($pdo, '/login.php', 'login_failed');
    }
}

$pageTitle = 'Login';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card card-soft p-4">
            <h1 class="h4 mb-3">Login</h1>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endforeach; ?>
            <form method="post" novalidate>
                <?= csrf_token_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control" id="username" name="username" type="text" required maxlength="30" autocomplete="username" value="<?= e($username) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" required autocomplete="current-password">
                </div>
                <button class="btn btn-primary w-100" type="submit">Sign In</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
