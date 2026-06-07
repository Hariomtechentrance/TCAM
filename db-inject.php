<?php
/**
 * BATCH DATA INJECTION TOOL — DELETE FROM SERVER AFTER USE
 * Accepts JSON batches of bookings records via POST.
 * Access: https://tcam.in/db-inject.php
 */
$secret = 'tcam_inject_2024';

header('Content-Type: application/json');

if (($_POST['secret'] ?? '') !== $secret) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$dbPath = getenv('TCAM_DB_PATH') ?: __DIR__ . '/tcam_bookings.db';

try {
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Ensure bookings table exists with all columns
    $db->exec('CREATE TABLE IF NOT EXISTS bookings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bookingId TEXT UNIQUE,
        name TEXT,
        dob TEXT DEFAULT \'\',
        district TEXT DEFAULT \'\',
        email TEXT DEFAULT \'\',
        phone TEXT,
        proof TEXT DEFAULT \'\',
        proof_file TEXT DEFAULT \'\',
        photo TEXT DEFAULT \'\',
        message TEXT DEFAULT \'\',
        aadhar_number TEXT DEFAULT \'\',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    try { $db->exec("ALTER TABLE bookings ADD COLUMN aadhar_number TEXT DEFAULT ''"); } catch (PDOException $e) {}

    $action = $_POST['action'] ?? 'insert';

    if ($action === 'count') {
        $count = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
        echo json_encode(['count' => (int)$count]);
        exit;
    }

    $records = json_decode($_POST['records'] ?? '[]', true);
    if (!is_array($records)) {
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $db->beginTransaction();
    $stmt = $db->prepare('INSERT OR IGNORE INTO bookings
        (bookingId, name, dob, district, email, phone, proof, proof_file, photo, message, aadhar_number, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

    $inserted = 0;
    foreach ($records as $r) {
        $stmt->execute([
            $r['bookingId']    ?? '',
            $r['name']         ?? '',
            $r['dob']          ?? '',
            $r['district']     ?? '',
            $r['email']        ?? '',
            $r['phone']        ?? '',
            $r['proof']        ?? '',
            $r['proof_file']   ?? '',
            $r['photo']        ?? '',
            $r['message']      ?? '',
            $r['aadhar_number'] ?? '',
            $r['created_at']   ?? date('Y-m-d H:i:s'),
        ]);
        $inserted++;
    }
    $db->commit();

    $total = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
    echo json_encode(['inserted' => $inserted, 'total_in_db' => (int)$total]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) { $db->rollBack(); }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
