<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

$userId = filter_input(INPUT_GET, 'u', FILTER_VALIDATE_INT);
$defaultPath = __DIR__ . '/assets/default-avatar.svg';

$serveFile = static function (string $path, string $mime): void {
    if (!headers_sent()) {
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: private, max-age=300');
    }

    readfile($path);
    exit;
};

if (!$userId) {
    $serveFile($defaultPath, 'image/svg+xml');
}

$stmt = $pdo->prepare('SELECT profile_image FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if ($user === false || empty($user['profile_image'])) {
    $serveFile($defaultPath, 'image/svg+xml');
}

$fileName = basename((string) $user['profile_image']);
$base = realpath(UPLOAD_PATH);
$filePath = realpath(UPLOAD_PATH . '/' . $fileName);

if ($base === false || $filePath === false || !str_starts_with($filePath, $base . DIRECTORY_SEPARATOR) || !is_file($filePath)) {
    $serveFile($defaultPath, 'image/svg+xml');
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($filePath);
$allowed = ['image/jpeg', 'image/png', 'image/webp'];

if (!in_array($mime, $allowed, true)) {
    $serveFile($defaultPath, 'image/svg+xml');
}

$serveFile($filePath, $mime);
