<?php

declare(strict_types=1);

function session_user_agent_hash(): string
{
    return hash('sha256', (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown'));
}

function session_ip_fingerprint(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $parts = explode('.', $ip);

        return $parts[0] . '.' . $parts[1] . '.' . $parts[2];
    }

    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $parts = explode(':', $ip);

        return implode(':', array_slice($parts, 0, 4));
    }

    return 'unknown';
}

function initialize_session(): void
{
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', SESSION_COOKIE_SECURE ? '1' : '0');
    ini_set('session.cookie_samesite', 'Strict');

    session_name('transactiwar_sid');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => SESSION_COOKIE_SECURE,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);

    session_start();
}

function reset_session_with_flash(string $type, string $message): void
{
    logout_user();
    initialize_session();
    flash($type, $message);
}

function start_secure_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        initialize_session();
    }

    $now = time();

    if (!isset($_SESSION['created_at'])) {
        $_SESSION['created_at'] = $now;
        $_SESSION['last_regenerated'] = $now;
        $_SESSION['last_activity'] = $now;
    }

    if (isset($_SESSION['last_activity']) && ($now - (int) $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        reset_session_with_flash('warning', 'Your session expired due to inactivity. Please login again.');
        return;
    }

    $_SESSION['last_activity'] = $now;

    if (!isset($_SESSION['last_regenerated']) || ($now - (int) $_SESSION['last_regenerated']) >= SESSION_REGEN_INTERVAL) {
        session_regenerate_id(true);
        $_SESSION['last_regenerated'] = $now;
    }

    if (isset($_SESSION['user_agent_hash']) && !hash_equals((string) $_SESSION['user_agent_hash'], session_user_agent_hash())) {
        reset_session_with_flash('danger', 'Session validation failed. Please login again.');
        return;
    }

    if (isset($_SESSION['ip_fingerprint']) && !hash_equals((string) $_SESSION['ip_fingerprint'], hash('sha256', session_ip_fingerprint()))) {
        reset_session_with_flash('danger', 'Session IP check failed. Please login again.');
    }
}

function login_user(array $user): void
{
    session_regenerate_id(true);

    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['username'] = (string) $user['username'];
    $_SESSION['role'] = (string) ($user['role'] ?? 'user');
    $_SESSION['user_agent_hash'] = session_user_agent_hash();
    $_SESSION['ip_fingerprint'] = hash('sha256', session_ip_fingerprint());
    $_SESSION['last_activity'] = time();
    $_SESSION['last_regenerated'] = time();
}

function logout_user(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?? '/',
            'domain' => $params['domain'] ?? '',
            'secure' => (bool) ($params['secure'] ?? false),
            'httponly' => (bool) ($params['httponly'] ?? true),
            'samesite' => $params['samesite'] ?? 'Strict',
        ]);
    }

    session_destroy();
}

function is_authenticated(): bool
{
    return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
}

function current_user_id(): int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
}

function current_username(): string
{
    return isset($_SESSION['username']) ? (string) $_SESSION['username'] : 'guest';
}

function current_role(): string
{
    return isset($_SESSION['role']) ? (string) $_SESSION['role'] : 'guest';
}
