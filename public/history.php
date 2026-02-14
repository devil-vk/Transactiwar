<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_auth(['user', 'admin']);
log_event($pdo, '/history.php', 'page_access');

$userId = current_user_id();

$stmt = $pdo->prepare(
    'SELECT t.id, t.sender_id, t.receiver_id, t.amount_paise, t.comment, t.created_at,
            s.username AS sender_username,
            r.username AS receiver_username
     FROM transactions t
     INNER JOIN users s ON s.id = t.sender_id
     INNER JOIN users r ON r.id = t.receiver_id
     WHERE t.sender_id = ? OR t.receiver_id = ?
     ORDER BY t.created_at DESC, t.id DESC
     LIMIT 100'
);
$stmt->execute([$userId, $userId]);
$transactions = $stmt->fetchAll();

$pageTitle = 'Transaction History';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="card card-soft p-4">
    <h1 class="h5">Sent and Received Transactions</h1>
    <div class="table-responsive mt-3">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Type</th>
                    <th>Counterparty</th>
                    <th>Amount</th>
                    <th>Comment</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions === []): ?>
                    <tr>
                        <td colspan="6" class="text-muted">No transactions yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <?php
                        $isSent = (int) $tx['sender_id'] === $userId;
                        $counterparty = $isSent ? (string) $tx['receiver_username'] : (string) $tx['sender_username'];
                        ?>
                        <tr>
                            <td><?= e((string) $tx['id']) ?></td>
                            <td>
                                <?php if ($isSent): ?>
                                    <span class="badge text-bg-danger">Sent</span>
                                <?php else: ?>
                                    <span class="badge text-bg-success">Received</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e($counterparty) ?></td>
                            <td><?= e(format_paise((int) $tx['amount_paise'])) ?></td>
                            <td><?= e((string) ($tx['comment'] ?? '')) ?></td>
                            <td><?= e((string) $tx['created_at']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
