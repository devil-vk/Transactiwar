<?php

declare(strict_types=1);

function require_auth(array $roles = []): void
{
    if (!is_authenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/dashboard.php';
        header('Location: /login.php');
        exit;
    }

    if ($roles !== [] && !in_array(current_role(), $roles, true)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

function require_guest(): void
{
    if (is_authenticated()) {
        header('Location: /dashboard.php');
        exit;
    }
}
