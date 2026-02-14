<?php

declare(strict_types=1);

function apply_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 0');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net 'unsafe-inline'; script-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
}

function truncate_text(string $value, int $maxLength): string
{
    if ($maxLength <= 0) {
        return '';
    }

    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLength);
    }

    return substr($value, 0, $maxLength);
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function sanitize_text(string $value, int $maxLength = 255): string
{
    $value = trim(strip_tags($value));

    return truncate_text($value, $maxLength);
}

function sanitize_multiline_text(string $value, int $maxLength = 5000): string
{
    $value = trim($value);
    $value = strip_tags($value);

    return truncate_text($value, $maxLength);
}

function is_valid_username(string $username): bool
{
    return (bool) preg_match('/^[a-zA-Z0-9_]{4,30}$/', $username);
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 255;
}

function hash_password_secure(string $password): string
{
    $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;

    return password_hash($password, $algo);
}

function verify_password_secure(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

function generate_csrf_token(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_token_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(generate_csrf_token()) . '">';
}

function validate_csrf_token(?string $token): bool
{
    if (!isset($_SESSION['csrf_token']) || !is_string($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

function require_valid_csrf_token(?string $token): void
{
    if (!validate_csrf_token($token)) {
        http_response_code(419);
        exit('Invalid CSRF token.');
    }
}

function rupees_to_paise(string $input): ?int
{
    $normalized = trim($input);

    if (!preg_match('/^(?:0|[1-9]\d{0,8})(?:\.\d{1,2})?$/', $normalized)) {
        return null;
    }

    [$rupees, $fraction] = array_pad(explode('.', $normalized, 2), 2, '0');
    $fraction = str_pad($fraction, 2, '0');

    return ((int) $rupees * 100) + (int) substr($fraction, 0, 2);
}

function format_paise(int $paise): string
{
    $rupees = $paise / 100;

    return 'Rs. ' . number_format($rupees, 2);
}

function random_filename(string $extension): string
{
    return bin2hex(random_bytes(16)) . '.' . $extension;
}
