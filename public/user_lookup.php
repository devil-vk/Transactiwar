<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

// Simple JSON endpoint to lookup user by username or phone number
// GET parameter: q

$q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
header('Content-Type: application/json; charset=utf-8');

if ($q === '') {
    echo json_encode(['error' => 'Empty query']);
    exit;
}

try {
    if (preg_match('/^[0-9]{10}$/', $q)) {
        $stmt = $pdo->prepare('SELECT id, username, phone_number FROM users WHERE phone_number = :phone LIMIT 1');
        $stmt->execute([':phone' => $q]);
    } else {
        $stmt = $pdo->prepare('SELECT id, username, phone_number FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $q]);
    }

    $row = $stmt->fetch();
    if ($row === false) {
        echo json_encode(['found' => false]);
        exit;
    }

    echo json_encode(['found' => true, 'id' => (int) $row['id'], 'username' => $row['username'], 'phone' => $row['phone_number']]);
} catch (Throwable $e) {
    echo json_encode(['error' => 'Lookup failed']);
}
