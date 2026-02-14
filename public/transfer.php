<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_auth(['user', 'admin']);
log_event($pdo, '/transfer.php', 'page_access');

$senderId = current_user_id();
$errors = [];
$receiverUsernameInput = sanitize_text((string) ($_GET['user'] ?? ''), 50);
$receiverPhoneInput = sanitize_text((string) ($_GET['phone'] ?? ''), 15);
$amountInput = '';
$commentInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf_token($_POST['csrf_token'] ?? null);

    $receiverUsernameInput = sanitize_text((string) ($_POST['receiver_username'] ?? ''), 50);
    $receiverPhoneInput = sanitize_text((string) ($_POST['receiver_phone'] ?? ''), 15);
    $postedReceiverId = sanitize_text((string) ($_POST['receiver_id'] ?? ''), 20);
    $amountInput = sanitize_text((string) ($_POST['amount'] ?? ''), 20);
    $commentInput = sanitize_multiline_text((string) ($_POST['comment'] ?? ''), 255);

    $receiverId = 0;
    // Resolve receiver: prefer posted numeric id (set by lookup), otherwise resolve from phone, then username
    if (ctype_digit($postedReceiverId)) {
        $receiverId = (int) $postedReceiverId;
    } else {
        if (preg_match('/^[0-9]{10}$/', $receiverPhoneInput)) {
            $lookupStmt = $pdo->prepare('SELECT id FROM users WHERE phone_number = :phone LIMIT 1');
            $lookupStmt->execute([':phone' => $receiverPhoneInput]);
            $row = $lookupStmt->fetch();
            $receiverId = $row ? (int) $row['id'] : 0;
        }

        if ($receiverId === 0 && $receiverUsernameInput !== '') {
            $lookupStmt = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
            $lookupStmt->execute([':username' => $receiverUsernameInput]);
            $row = $lookupStmt->fetch();
            $receiverId = $row ? (int) $row['id'] : 0;
        }
    }

    if ($receiverId <= 0) {
        $errors[] = 'Receiver not found. Enter a valid username or 10-digit mobile number.';
    }

    if ($receiverId === $senderId) {
        $errors[] = 'You cannot transfer to yourself.';
    }

    $amountPaise = rupees_to_paise($amountInput);
    if ($amountPaise === null || $amountPaise <= 0) {
        $errors[] = 'Amount must be a positive number with up to 2 decimals.';
    }

    if ($errors === []) {
        try {
            $pdo->beginTransaction();

                $lockIds = [$senderId, $receiverId];
            sort($lockIds);

            $lockStmt = $pdo->prepare('SELECT id, username, balance_paise FROM users WHERE id IN (:id1, :id2) ORDER BY id FOR UPDATE');
            $lockStmt->bindValue(':id1', $lockIds[0], PDO::PARAM_INT);
            $lockStmt->bindValue(':id2', $lockIds[1], PDO::PARAM_INT);
            $lockStmt->execute();

            $lockedUsers = $lockStmt->fetchAll();
            $usersById = [];
            foreach ($lockedUsers as $row) {
                $usersById[(int) $row['id']] = $row;
            }

            if (!isset($usersById[$senderId]) || !isset($usersById[$receiverId])) {
                throw new RuntimeException('Sender or receiver account not found.');
            }

            $senderBalance = (int) $usersById[$senderId]['balance_paise'];
            if ($senderBalance < $amountPaise) {
                throw new RuntimeException('Insufficient balance for this transfer.');
            }

            $debitStmt = $pdo->prepare('UPDATE users SET balance_paise = balance_paise - :amount WHERE id = :id');
            $debitStmt->execute([
                ':amount' => $amountPaise,
                ':id' => $senderId,
            ]);

            $creditStmt = $pdo->prepare('UPDATE users SET balance_paise = balance_paise + :amount WHERE id = :id');
            $creditStmt->execute([
                ':amount' => $amountPaise,
                ':id' => $receiverId,
            ]);

            $txnStmt = $pdo->prepare(
                'INSERT INTO transactions (sender_id, receiver_id, amount_paise, comment) VALUES (:sender_id, :receiver_id, :amount_paise, :comment)'
            );
            $txnStmt->execute([
                ':sender_id' => $senderId,
                ':receiver_id' => $receiverId,
                ':amount_paise' => $amountPaise,
                ':comment' => $commentInput !== '' ? $commentInput : null,
            ]);

            $pdo->commit();

            log_event($pdo, '/transfer.php', 'transfer_success');
            flash('success', 'Transfer completed successfully.');
            header('Location: /history.php');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = $e instanceof RuntimeException ? $e->getMessage() : 'Transfer failed. Please try again.';
            log_event($pdo, '/transfer.php', 'transfer_failed');
        }
    }
}

$balanceStmt = $pdo->prepare('SELECT balance_paise FROM users WHERE id = :id LIMIT 1');
$balanceStmt->execute([':id' => $senderId]);
$currentBalance = (int) ($balanceStmt->fetch()['balance_paise'] ?? 0);

$pageTitle = 'Transfer Money';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card card-soft p-4">
            <h1 class="h5">Send Money</h1>
            <p class="text-muted">Available balance: <strong><?= e(format_paise($currentBalance)) ?></strong></p>
            <?php foreach ($errors as $error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endforeach; ?>
            <form method="post">
                <?= csrf_token_field() ?>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="receiver_username">Receiver Username</label>
                        <input class="form-control" id="receiver_username" name="receiver_username" type="text" maxlength="50" value="<?= e($receiverUsernameInput) ?>" placeholder="Username">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label" for="receiver_phone">Receiver Phone (10 digits)</label>
                        <input class="form-control" id="receiver_phone" name="receiver_phone" type="text" maxlength="10" pattern="[0-9]{10}" value="<?= e($receiverPhoneInput) ?>" placeholder="Mobile number">
                    </div>
                </div>
                <input type="hidden" id="receiver_id" name="receiver_id" value="">
                <div id="receiver_hint" class="form-text mt-1 text-muted mb-3"></div>
                <div class="mb-3">
                    <label class="form-label" for="amount">Amount (Rs.)</label>
                    <input class="form-control" id="amount" name="amount" type="text" placeholder="e.g. 10 or 10.50" required value="<?= e($amountInput) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="comment">Comment (optional)</label>
                    <textarea class="form-control" id="comment" name="comment" maxlength="255" rows="3"><?= e($commentInput) ?></textarea>
                </div>
                <button class="btn btn-primary" type="submit">Transfer</button>
            </form>
        </div>
    </div>
</div>
<script src="/assets/transfer.js"></script>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
