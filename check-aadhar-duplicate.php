<?php
require_once 'cors.php';
header('Content-Type: application/json; charset=UTF-8');
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['duplicate' => false]);
    exit;
}

$aadhar = preg_replace('/\s/', '', $_POST['aadhar_number'] ?? '');
if (!preg_match('/^\d{12}$/', $aadhar)) {
    echo json_encode(['duplicate' => false]);
    exit;
}

try {
    $db = new SQLite3(DB_PATH);
    $stmt = $db->prepare('SELECT bookingId FROM bookings WHERE aadhar_number = ? LIMIT 1');
    $stmt->bindValue(1, $aadhar, SQLITE3_TEXT);
    $row = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
    echo json_encode(['duplicate' => !empty($row)]);
} catch (Exception $e) {
    echo json_encode(['duplicate' => false]);
}
