<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/init.php';

require_auth(['user', 'admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

require_valid_csrf_token($_POST['csrf_token'] ?? null);

if (!isset($_FILES['profile_image']) || !is_array($_FILES['profile_image'])) {
    flash('danger', 'No file uploaded.');
    header('Location: /profile.php');
    exit;
}

$file = $_FILES['profile_image'];

if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    flash('danger', 'Upload failed with error code: ' . (string) ($file['error'] ?? 'unknown'));
    header('Location: /profile.php');
    exit;
}

$tmpName = (string) ($file['tmp_name'] ?? '');
$size = (int) ($file['size'] ?? 0);

if (!is_uploaded_file($tmpName)) {
    flash('danger', 'Invalid upload source.');
    header('Location: /profile.php');
    exit;
}

if ($size <= 0 || $size > UPLOAD_MAX_SIZE) {
    flash('danger', 'Invalid file size. Max size is 2 MB.');
    header('Location: /profile.php');
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($tmpName);

$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];

if (!array_key_exists($mime, $allowedTypes)) {
    flash('danger', 'Only JPG, PNG, and WEBP files are allowed.');
    header('Location: /profile.php');
    exit;
}

if (getimagesize($tmpName) === false) {
    flash('danger', 'Uploaded file is not a valid image.');
    header('Location: /profile.php');
    exit;
}

$extension = $allowedTypes[$mime];
$newFileName = random_filename($extension);
$destination = UPLOAD_PATH . '/' . $newFileName;

$stmt = $pdo->prepare('SELECT profile_image FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => current_user_id()]);
$current = $stmt->fetch();

if ($current === false) {
    flash('danger', 'User account not found.');
    header('Location: /login.php');
    exit;
}

if (!move_uploaded_file($tmpName, $destination)) {
    flash('danger', 'Unable to save uploaded file.');
    header('Location: /profile.php');
    exit;
}

$updateStmt = $pdo->prepare('UPDATE users SET profile_image = :profile_image WHERE id = :id');
$updateStmt->execute([
    ':profile_image' => $newFileName,
    ':id' => current_user_id(),
]);

$oldFile = isset($current['profile_image']) ? basename((string) $current['profile_image']) : '';
if ($oldFile !== '') {
    $oldPath = UPLOAD_PATH . '/' . $oldFile;
    if (is_file($oldPath) && str_starts_with((string) realpath($oldPath), (string) realpath(UPLOAD_PATH) . DIRECTORY_SEPARATOR)) {
        unlink($oldPath);
    }
}

log_event($pdo, '/upload.php', 'profile_image_uploaded');
flash('success', 'Profile image uploaded successfully.');
header('Location: /profile.php');
exit;
