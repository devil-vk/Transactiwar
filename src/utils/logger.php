<?php

declare(strict_types=1);

function client_ip(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!is_string($ip) || $ip === '') {
        return '0.0.0.0';
    }

    return truncate_text($ip, 45);
}

function log_event(PDO $pdo, string $pageAccessed, string $action): void
{
    $userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    $username = isset($_SESSION['username']) ? (string) $_SESSION['username'] : 'guest';
    $agent = isset($_SERVER['HTTP_USER_AGENT']) ? truncate_text((string) $_SERVER['HTTP_USER_AGENT'], 255) : null;

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO logs (user_id, username, page_accessed, action, client_ip, user_agent) VALUES (:user_id, :username, :page_accessed, :action, :client_ip, :user_agent)'
        );
        $stmt->execute([
            ':user_id' => $userId,
            ':username' => truncate_text($username, 50),
            ':page_accessed' => truncate_text($pageAccessed, 255),
            ':action' => truncate_text($action, 100),
            ':client_ip' => client_ip(),
            ':user_agent' => $agent,
        ]);
    } catch (Throwable $e) {
        // Never block request flow if logging fails.
    }
}
