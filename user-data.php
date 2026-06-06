<?php
header('Content-Type: application/json');
if (!isset($_GET['bookingId'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing bookingId']);
    exit;
}
$db = new SQLite3('tcam_bookings.db');
$stmt = $db->prepare('SELECT * FROM bookings WHERE bookingId = :bookingId');
$stmt->bindValue(':bookingId', $_GET['bookingId'], SQLITE3_TEXT);
$result = $stmt->execute();
$user = $result->fetchArray(SQLITE3_ASSOC);

if ($user) {
    unset($user['password']); // Don't expose password
    echo json_encode(['status' => 'success', 'user' => $user]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
}
?>
