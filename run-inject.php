<?php
/**
 * LOCAL RUNNER — execute this on your machine, not on the server.
 * Reads local tcam_bookings.db and pushes all records to tcam.in in batches.
 * Usage: php run-inject.php
 */

$localDb   = __DIR__ . '/tcam_bookings.db';
$endpoint  = 'https://tcam.in/db-inject.php';
$secret    = 'tcam_inject_2024';
$batchSize = 100;

// Verify local DB
if (!file_exists($localDb)) {
    die("ERROR: local DB not found at $localDb\n");
}

$pdo = new PDO('sqlite:' . $localDb);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$total = (int)$pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
echo "Local records to push: $total\n";

// Check current live count
$check = curlPost($endpoint, ['secret' => $secret, 'action' => 'count', 'records' => '[]']);
$liveCount = json_decode($check, true)['count'] ?? '?';
echo "Live DB currently has: $liveCount records\n\n";

$offset   = 0;
$pushed   = 0;

while ($offset < $total) {
    $rows = $pdo->query("SELECT * FROM bookings LIMIT $batchSize OFFSET $offset")->fetchAll();

    $response = curlPost($endpoint, [
        'secret'  => $secret,
        'action'  => 'insert',
        'records' => json_encode($rows),
    ]);

    $data = json_decode($response, true);
    if (isset($data['error'])) {
        echo "ERROR at offset $offset: " . $data['error'] . "\n";
        break;
    }

    $pushed += count($rows);
    $offset += $batchSize;
    echo "Pushed $pushed / $total (live total: " . ($data['total_in_db'] ?? '?') . ")\n";
}

echo "\nDone! Live DB now has " . ($data['total_in_db'] ?? '?') . " records.\n";

function curlPost(string $url, array $fields): string {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_POSTFIELDS     => $fields,
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($err) { echo "CURL ERROR: $err\n"; return '{}'; }
    return $resp ?: '{}';
}
