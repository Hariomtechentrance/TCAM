<?php
// TCAM Booking Handler
require_once 'config.php';

require_once 'cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST request required']);
    exit;
}

if (!isset($_POST['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data']);
    exit;
}

// Validate 12-digit Aadhar number
$aadhar_number = preg_replace('/\s/', '', $_POST['aadhar_number'] ?? '');
if (!preg_match('/^\d{12}$/', $aadhar_number)) {
    echo json_encode(['status' => 'error', 'message' => 'A valid 12-digit Aadhar number is required.']);
    exit;
}

// File upload handling with security checks
function saveUploadedFile($fileField, $prefix = '') {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) return '';

    $file = $_FILES[$fileField];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    $allowedImageMimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];

    if ($file['size'] > MAX_PHOTO_SIZE) {
        throw new Exception('File size exceeds 2MB limit');
    }
    if (!array_key_exists($ext, $allowedImageMimes)) {
        throw new Exception('Invalid format. Only JPG, PNG, WEBP allowed');
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $actualMime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if ($actualMime !== $allowedImageMimes[$ext]) {
        throw new Exception('File content does not match its extension');
    }

    $filename = $prefix . bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = UPLOAD_DIR . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return UPLOAD_URL . $filename;
    }
    return '';
}

try {
    $proof_file_path = saveUploadedFile('proof_file', 'aadhar_');
    $photo_path      = saveUploadedFile('photo', 'photo_');
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

$db = new SQLite3(DB_PATH);

// Ensure aadhar_number column exists (migration-safe)
$db->exec('CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bookingId TEXT,
    name TEXT,
    dob TEXT,
    district TEXT,
    email TEXT,
    phone TEXT,
    proof TEXT,
    proof_file TEXT,
    photo TEXT,
    message TEXT,
    aadhar_number TEXT,
    created_at TEXT
)');
// Add column if upgrading old schema
try { $db->exec('ALTER TABLE bookings ADD COLUMN aadhar_number TEXT'); } catch(Exception $e) {}

// Duplicate Aadhar check
$dupStmt = $db->prepare('SELECT bookingId, name FROM bookings WHERE aadhar_number = ? LIMIT 1');
$dupStmt->bindValue(1, $aadhar_number, SQLITE3_TEXT);
$dupResult = $dupStmt->execute()->fetchArray(SQLITE3_ASSOC);
if ($dupResult) {
    echo json_encode([
        'status'  => 'duplicate',
        'message' => 'This Aadhar card is already registered (ID: ' . htmlspecialchars($dupResult['bookingId']) . '). If you want to update your details, please use the Enhanced Player Registration page.',
        'update_url' => 'single-registration-enhanced.html'
    ]);
    exit;
}

$bookingId = uniqid('TCAM-');
$stmt = $db->prepare('INSERT INTO bookings (bookingId, name, dob, district, email, phone, proof, proof_file, photo, message, aadhar_number, created_at)
    VALUES (:bookingId, :name, :dob, :district, :email, :phone, :proof, :proof_file, :photo, :message, :aadhar_number, :created_at)');
$stmt->bindValue(':bookingId',      $bookingId,                                          SQLITE3_TEXT);
$stmt->bindValue(':name',           $_POST['name'],                                      SQLITE3_TEXT);
$stmt->bindValue(':dob',            $_POST['dob']     ?? '',                             SQLITE3_TEXT);
$stmt->bindValue(':district',       $_POST['district'],                                  SQLITE3_TEXT);
$stmt->bindValue(':email',          $_POST['email']   ?? '',                             SQLITE3_TEXT);
$stmt->bindValue(':phone',          $_POST['phone'],                                     SQLITE3_TEXT);
$stmt->bindValue(':proof',          'aadhar',                                            SQLITE3_TEXT);
$stmt->bindValue(':proof_file',     $proof_file_path,                                   SQLITE3_TEXT);
$stmt->bindValue(':photo',          $photo_path,                                         SQLITE3_TEXT);
$stmt->bindValue(':message',        $_POST['message'] ?? '',                             SQLITE3_TEXT);
$stmt->bindValue(':aadhar_number',  $aadhar_number,                                      SQLITE3_TEXT);
$stmt->bindValue(':created_at',     date('Y-m-d H:i:s'),                               SQLITE3_TEXT);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'data'   => [
            'bookingId'    => $bookingId,
            'name'         => $_POST['name'],
            'dob'          => $_POST['dob']      ?? '',
            'district'     => $_POST['district'],
            'aadhar_number'=> $aadhar_number,
            'proof_file'   => $proof_file_path,
            'photo'        => $photo_path
        ]
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database insert failed']);
}
exit;
