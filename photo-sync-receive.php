<?php
/**
 * ONE-TIME PHOTO SYNC RECEIVER — DELETE FROM SERVER AFTER USE.
 *
 * Backfills photo files for `bookings`/`registrations` rows whose photo
 * was bulk-imported into the production DB (via the now-deleted
 * db-inject.php tool) without the actual image file ever being uploaded
 * here. Run photo-sync-push.php locally against this endpoint once, then
 * delete both files from the repo and the server.
 *
 * Reuses the existing admin session — no separate secret to leak.
 */
require_once 'security-config.php';
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

Security::rateLimit(2000, 3600);

function safePhotoName(string $name): ?string
{
    $name = basename($name);
    if (!preg_match('/^[A-Za-z0-9._-]+\.(jpe?g|png|gif|webp)$/i', $name)) {
        return null;
    }
    return $name;
}

$action = $_POST['action'] ?? '';

if ($action === 'check') {
    $names = json_decode($_POST['filenames'] ?? '[]', true);
    $missing = [];
    foreach ((array) $names as $n) {
        $safe = safePhotoName((string) $n);
        if ($safe !== null && !file_exists(UPLOAD_DIR . '/' . $safe)) {
            $missing[] = $n;
        }
    }
    echo json_encode(['missing' => $missing]);
    exit;
}

if ($action === 'upload') {
    $safe = safePhotoName((string) ($_POST['filename'] ?? ''));
    if ($safe === null || empty($_FILES['file']['tmp_name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'bad_request']);
        exit;
    }

    $dest = UPLOAD_DIR . '/' . $safe;
    if (file_exists($dest)) {
        echo json_encode(['status' => 'exists']);
        exit;
    }

    if (!@getimagesize($_FILES['file']['tmp_name'])) {
        http_response_code(400);
        echo json_encode(['error' => 'not_an_image']);
        exit;
    }

    if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
        http_response_code(500);
        echo json_encode(['error' => 'save_failed']);
        exit;
    }
    chmod($dest, 0644);
    echo json_encode(['status' => 'saved']);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'unknown_action']);
