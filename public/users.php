<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_auth(['user', 'admin']);
log_event($pdo, '/users.php', 'page_access');

$q = sanitize_text((string) ($_GET['q'] ?? ''), 50);
$results = [];

if ($q !== '') {
    if (ctype_digit($q)) {
        $stmt = $pdo->prepare(
            'SELECT id, username, first_name, last_name FROM users WHERE id = :id OR username LIKE :username ORDER BY username ASC LIMIT 25'
        );
        $stmt->execute([
            ':id' => (int) $q,
            ':username' => '%' . $q . '%',
        ]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT id, username, first_name, last_name FROM users WHERE username LIKE :username ORDER BY username ASC LIMIT 25'
        );
        $stmt->execute([
            ':username' => '%' . $q . '%',
        ]);
    }

    $results = $stmt->fetchAll();
}

$pageTitle = 'Search Users';
require_once __DIR__ . '/../src/views/header.php';
?>
<div class="card card-soft p-4">
    <h1 class="h5">Search by Username or User ID</h1>
    <form method="get" class="row g-2 mt-1">
        <div class="col-md-9">
            <input class="form-control" type="text" name="q" placeholder="e.g. 5 or john_doe" value="<?= e($q) ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit">Search</button>
        </div>
    </form>

    <?php if ($q !== ''): ?>
        <div class="table-responsive mt-4">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($results === []): ?>
                    <tr><td colspan="4" class="text-muted">No users found.</td></tr>
                <?php else: ?>
                    <?php foreach ($results as $row): ?>
                        <tr>
                            <td><?= e((string) $row['id']) ?></td>
                            <td><?= e((string) $row['username']) ?></td>
                            <td><?= e(trim(((string) ($row['first_name'] ?? '')) . ' ' . ((string) ($row['last_name'] ?? '')))) ?></td>
                            <td>
                                <a class="btn btn-sm btn-outline-secondary" href="/user.php?id=<?= e((string) $row['id']) ?>">View Profile</a>
                                <a class="btn btn-sm btn-primary" href="/transfer.php?to=<?= e((string) $row['id']) ?>">Transfer</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../src/views/footer.php'; ?>
