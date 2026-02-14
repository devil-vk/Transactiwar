<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_guest();
log_event($pdo, '/register.php', 'page_access');

$errors = [];
$username = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token($_POST['csrf_token'] ?? null);

    $username = sanitize_text((string) ($_POST['username'] ?? ''), 30);
    $email = sanitize_text((string) ($_POST['email'] ?? ''), 255);
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $phone = sanitize_text((string) ($_POST['phone'] ?? ''), 15);

    if (!is_valid_username($username)) {
        $errors[] = 'Username must be 4-30 characters and contain only letters, numbers, and underscores.';
    }

    if (!is_valid_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }

        if (!preg_match('/^[0-9]{10}$/', $phone)) {
            $errors[] = 'Please enter a valid 10-digit mobile number.';
        }

    if (strlen($password) < 12) {
        $errors[] = 'Password must be at least 12 characters long.';
    }

    if (!hash_equals($password, $passwordConfirm)) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors === []) {
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email OR phone_number = :phone LIMIT 1');
        $checkStmt->execute([
            ':username' => $username,
            ':email' => $email,
            ':phone' => $phone,
        ]);

        if ($checkStmt->fetch() !== false) {
            $errors[] = 'Username, email or phone number already exists.';
        } else {
            $hash = hash_password_secure($password);

            try {
                $insertStmt = $pdo->prepare(
                    'INSERT INTO users (username, email, phone_number, password_hash, balance_paise) VALUES (:username, :email, :phone, :password_hash, :balance_paise)'
                );
                $insertStmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':password_hash' => $hash,
                    ':balance_paise' => SIGNUP_CREDIT_PAISE,
                ]);

                log_event($pdo, '/register.php', 'register_success');
                flash('success', 'Registration complete. You received ' . e(format_paise(SIGNUP_CREDIT_PAISE)) . ' signup credit. Please login.');
                header('Location: /login.php');
                exit;
            } catch (PDOException $e) {
                $errors[] = 'Registration failed. Try again.';
            }
        }
    }

    if ($errors !== []) {
        log_event($pdo, '/register.php', 'register_failed');
    }
}

$pageTitle = 'Register';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-soft p-4">
            <h1 class="h4 mb-3">Register</h1>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endforeach; ?>
            <form method="post" novalidate>
                <?= csrf_token_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <input class="form-control" id="username" name="username" type="text" required maxlength="30" value="<?= e($username) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="email">Email</label>
                    <input class="form-control" id="email" name="email" type="email" required maxlength="255" value="<?= e($email) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="phone">Mobile Number (10 digits)</label>
                    <input class="form-control" id="phone" name="phone" type="text" required maxlength="10" pattern="[0-9]{10}" value="<?= e($phone) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password">Password</label>
                    <input class="form-control" id="password" name="password" type="password" required minlength="12">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="password_confirm">Confirm Password</label>
                    <input class="form-control" id="password_confirm" name="password_confirm" type="password" required minlength="12">
                </div>
                <button class="btn btn-primary w-100" type="submit">Create Account</button>
            </form>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
