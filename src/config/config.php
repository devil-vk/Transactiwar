<?php

declare(strict_types=1);

function env(string $key, string $default = ''): string
{
    $value = getenv($key);

    return $value === false ? $default : $value;
}

define('BASE_PATH', dirname(__DIR__, 2));
define('PUBLIC_PATH', BASE_PATH . '/public');
define('UPLOAD_PATH', BASE_PATH . '/uploads/profile_images');
define('UPLOAD_MAX_SIZE', (int) env('UPLOAD_MAX_SIZE', '2097152'));

define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_PORT', (int) env('DB_PORT', '3306'));
define('DB_NAME', env('DB_NAME', 'transactiwar'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Signup credit (in paise). Default: Rs. 1,00,000 = 10,000,000 paise
define('SIGNUP_CREDIT_PAISE', (int) env('SIGNUP_CREDIT_PAISE', '10000000'));

define('APP_ENV', env('APP_ENV', 'production'));
define('SESSION_IDLE_TIMEOUT', (int) env('SESSION_IDLE_TIMEOUT', '900'));
define('SESSION_REGEN_INTERVAL', (int) env('SESSION_REGEN_INTERVAL', '300'));
define('SESSION_COOKIE_SECURE', env('SESSION_COOKIE_SECURE', APP_ENV === 'production' ? '1' : '0') === '1');

date_default_timezone_set('Asia/Kolkata');

if (APP_ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}
