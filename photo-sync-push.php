<?php
/**
 * LOCAL RUNNER — run on this machine only, never deploy this file.
 * Usage: php photo-sync-push.php
 *
 * Logs into the live admin panel, asks photo-sync-receive.php which
 * bookings/registrations photo files it's missing, and uploads exactly
 * those from this machine's uploads/ folder. Safe to re-run — every step
 * is check-before-write, so files already on the server are skipped.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    die("This tool is CLI-only and must not be run over the web.\n");
}

$endpointBase = 'https://tcam.in';
$localUploadDir = __DIR__ . '/uploads';
$localDb = __DIR__ . '/tcam_bookings.db';
$cookieJar = tempnam(sys_get_temp_dir(), 'tcam_photo_sync_cookies_');

function prompt(string $label, bool $hidden = false): string
{
    echo $label;
    if ($hidden && stripos(PHP_OS, 'WIN') === false) {
        system('stty -echo');
        $value = trim(fgets(STDIN));
        system('stty echo');
        echo "\n";
    } else {
        $value = trim(fgets(STDIN));
    }
    return $value;
}

function curlRequest(string $url, array $fields, string $cookieJar): string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
    ]);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    if ($err) {
        fwrite(STDERR, "CURL ERROR: $err\n");
        return '';
    }
    return $resp !== false ? $resp : '';
}

echo "=== TCAM photo sync ===\n";
$username = getenv('TCAM_ADMIN_USERNAME') ?: prompt('Admin username: ');
$password = getenv('TCAM_ADMIN_PASSWORD') ?: prompt('Admin password: ', true);

curlRequest("$endpointBase/login-production.php", [
    'username' => $username,
    'password' => $password,
], $cookieJar);

$check = curlRequest("$endpointBase/photo-sync-receive.php", [
    'action' => 'check',
    'filenames' => json_encode(['__login_check__.jpg']),
], $cookieJar);
$checkData = json_decode($check, true);
if (!is_array($checkData) || !array_key_exists('missing', $checkData)) {
    die("Login failed or endpoint unreachable. Response: $check\n");
}
echo "Logged in OK.\n";

if (!file_exists($localDb)) {
    die("ERROR: local DB not found at $localDb\n");
}
$pdo = new PDO('sqlite:' . $localDb);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$filenames = [];
foreach (['bookings', 'registrations'] as $table) {
    $stmt = $pdo->query("SELECT photo FROM $table WHERE photo IS NOT NULL AND photo != ''");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $f = preg_replace('#^/?uploads/#', '', $row['photo']);
        if ($f !== '') {
            $filenames[$f] = true;
        }
    }
}
$filenames = array_keys($filenames);
echo "Referenced photo filenames in local DB: " . count($filenames) . "\n";

$missing = [];
foreach (array_chunk($filenames, 300) as $chunk) {
    $resp = curlRequest("$endpointBase/photo-sync-receive.php", [
        'action' => 'check',
        'filenames' => json_encode($chunk),
    ], $cookieJar);
    $data = json_decode($resp, true);
    foreach ($data['missing'] ?? [] as $m) {
        $missing[] = $m;
    }
}
echo "Missing on live server: " . count($missing) . "\n\n";

$uploaded = 0;
$noLocalCopy = 0;
$failed = 0;
$done = 0;
foreach ($missing as $name) {
    $done++;
    $localPath = $localUploadDir . '/' . $name;
    if (!file_exists($localPath)) {
        $noLocalCopy++;
        continue;
    }

    $ch = curl_init("$endpointBase/photo-sync-receive.php");
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_POSTFIELDS => [
            'action' => 'upload',
            'filename' => $name,
            'file' => new CURLFile($localPath),
        ],
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($resp ?: '', true);
    $status = $data['status'] ?? null;

    if ($status === 'saved' || $status === 'exists') {
        $uploaded++;
    } else {
        $failed++;
        echo "FAILED: $name -> " . ($resp ?: 'no response') . "\n";
    }

    if ($done % 50 === 0) {
        echo "progress: $done / " . count($missing) . "\n";
    }
}

@unlink($cookieJar);

echo "\nDone.\n";
echo "Uploaded: $uploaded\n";
echo "Missing locally too (nothing to send): $noLocalCopy\n";
echo "Failed: $failed\n";
