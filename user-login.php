<?php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['bookingId'], $data['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing credentials']);
    exit;
}

$db = new SQLite3('tcam_bookings.db');
$stmt = $db->prepare('SELECT * FROM bookings WHERE bookingId = :bookingId');
$stmt->bindValue(':bookingId', $data['bookingId'], SQLITE3_TEXT);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

if ($user && isset($user['password']) && $user['password'] === $data['password']) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid TCAM ID or password']);
}
?>
