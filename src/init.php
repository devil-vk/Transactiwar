<?php

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/utils/security.php';
require_once __DIR__ . '/utils/flash.php';
require_once __DIR__ . '/auth/session.php';
require_once __DIR__ . '/auth/security_guard.php';
require_once __DIR__ . '/utils/logger.php';

start_secure_session();
apply_security_headers();

if (!is_dir(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0750, true);
}

$pdo = db();
