<?php
// TCAM Booking Handler
require_once 'cors.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST request required']);
    exit;
}

if (empty($_POST['name'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing data']);
    exit;
}

// Validate 12-digit Aadhar number
$aadhar_number = preg_replace('/\s/', '', $_POST['aadhar_number'] ?? '');
if (!preg_match('/^\d{12}$/', $aadhar_number)) {
    echo json_encode(['status' => 'error', 'message' => 'A valid 12-digit Aadhar number is required.']);
    exit;
}

// ── CONFIG ────────────────────────────────────────────────────────────────────
$dbPath   = __DIR__ . '/tcam_bookings.db';
$uploadDir = __DIR__ . '/uploads';
$uploadUrl = '/uploads/';
$maxPhotoSize = 2 * 1024 * 1024; // 2 MB

if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}

// ── FILE UPLOAD ───────────────────────────────────────────────────────────────
function saveUploadedFile($fileField, $prefix, $uploadDir, $uploadUrl, $maxSize) {
    if (!isset($_FILES[$fileField]) || $_FILES[$fileField]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $file = $_FILES[$fileField];
    if ($file['size'] > $maxSize) {
        throw new \RuntimeException('File size exceeds 2MB limit');
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        throw new \RuntimeException('Invalid format. Only JPG, PNG, WEBP allowed');
    }

    // Optional MIME check (skipped gracefully if fileinfo not available)
    if (function_exists('finfo_open')) {
        $mimeMap = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
        $finfo = @finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo !== false) {
            $actualMime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            if (isset($mimeMap[$ext]) && $actualMime !== $mimeMap[$ext]) {
                throw new \RuntimeException('File content does not match its extension');
            }
        }
    }

    $filename   = $prefix . bin2hex(random_bytes(16)) . '.' . $ext;
    $targetPath = $uploadDir . '/' . $filename;
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $uploadUrl . $filename;
    }
    return '';
}

try {
    $proof_file_path = saveUploadedFile('proof_file', 'aadhar_', $uploadDir, $uploadUrl, $maxPhotoSize);
    $photo_path      = saveUploadedFile('photo',      'photo_',  $uploadDir, $uploadUrl, $maxPhotoSize);
} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}

// ── DATABASE ──────────────────────────────────────────────────────────────────
try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $db->exec('CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bookingId TEXT UNIQUE,
        name TEXT NOT NULL,
        dob TEXT DEFAULT \'\',
        district TEXT DEFAULT \'\',
        email TEXT DEFAULT \'\',
        phone TEXT NOT NULL,
        proof TEXT DEFAULT \'\',
        proof_file TEXT DEFAULT \'\',
        photo TEXT DEFAULT \'\',
        message TEXT DEFAULT \'\',
        aadhar_number TEXT DEFAULT \'\',
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    )');
    try { $db->exec('ALTER TABLE bookings ADD COLUMN aadhar_number TEXT DEFAULT \'\''); } catch (\PDOException $e) {}
    try { $db->exec('ALTER TABLE bookings ADD COLUMN dob TEXT DEFAULT \'\''); } catch (\PDOException $e) {}
    try { $db->exec('ALTER TABLE bookings ADD COLUMN district TEXT DEFAULT \'\''); } catch (\PDOException $e) {}

    // Duplicate Aadhar check
    $dupStmt = $db->prepare('SELECT bookingId, name FROM bookings WHERE aadhar_number = ? LIMIT 1');
    $dupStmt->execute([$aadhar_number]);
    $dupResult = $dupStmt->fetch();
    if ($dupResult) {
        echo json_encode([
            'status'     => 'duplicate',
            'message'    => 'This Aadhar card is already registered (ID: ' . htmlspecialchars($dupResult['bookingId']) . '). To update your details, use the Enhanced Player Registration page.',
            'update_url' => 'single-registration-enhanced.html'
        ]);
        exit;
    }

    $bookingId = 'TCAM-' . strtoupper(bin2hex(random_bytes(4)));
    $stmt = $db->prepare('INSERT INTO bookings
        (bookingId, name, dob, district, email, phone, proof, proof_file, photo, message, aadhar_number, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $bookingId,
        $_POST['name'],
        $_POST['dob']      ?? '',
        $_POST['district'] ?? '',
        $_POST['email']    ?? '',
        $_POST['phone'],
        'aadhar',
        $proof_file_path,
        $photo_path,
        $_POST['message']  ?? '',
        $aadhar_number,
        date('Y-m-d H:i:s')
    ]);

    echo json_encode([
        'status' => 'success',
        'data'   => [
            'bookingId'     => $bookingId,
            'name'          => $_POST['name'],
            'dob'           => $_POST['dob']      ?? '',
            'district'      => $_POST['district'] ?? '',
            'aadhar_number' => $aadhar_number,
            'proof_file'    => $proof_file_path,
            'photo'         => $photo_path
        ]
    ]);

} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
