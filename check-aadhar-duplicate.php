<?php
require_once 'cors.php';
header('Content-Type: application/json; charset=UTF-8');

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
    $db = new PDO('sqlite:' . __DIR__ . '/tcam_bookings.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $stmt = $db->prepare('SELECT bookingId FROM bookings WHERE aadhar_number = ? LIMIT 1');
    $stmt->execute([$aadhar]);
    $row = $stmt->fetch();
    echo json_encode(['duplicate' => !empty($row)]);
} catch (\Throwable $e) {
    echo json_encode(['duplicate' => false]);
}
