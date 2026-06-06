<?php
/**
 * ONE-TIME DATABASE UPLOAD TOOL
 * DELETE THIS FILE FROM THE SERVER AFTER USE!
 * Access: https://tcam.in/db-upload.php
 */
$secret = 'tcam_upload_2024';
$dbPath = __DIR__ . '/tcam_bookings.db';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['secret'] ?? '') !== $secret) {
        die('<p style="color:red">Wrong secret key.</p>');
    }
    if (!isset($_FILES['dbfile']) || $_FILES['dbfile']['error'] !== UPLOAD_ERR_OK) {
        die('<p style="color:red">Upload failed. Error: ' . ($_FILES['dbfile']['error'] ?? 'no file') . '</p>');
    }
    $f = $_FILES['dbfile'];
    // Basic validation — SQLite files start with "SQLite format 3"
    $handle = fopen($f['tmp_name'], 'r');
    $header = fread($handle, 16);
    fclose($handle);
    if (strpos($header, 'SQLite format 3') === false) {
        die('<p style="color:red">Not a valid SQLite database file.</p>');
    }
    // Backup old DB just in case
    if (file_exists($dbPath)) {
        copy($dbPath, $dbPath . '.bak.' . time());
    }
    if (move_uploaded_file($f['tmp_name'], $dbPath)) {
        // Verify record count
        try {
            $db = new PDO('sqlite:' . $dbPath);
            $count = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
            echo '<div style="font-family:sans-serif;padding:30px;background:#d4edda;border-radius:8px;color:#155724;">';
            echo '<h2>✅ Database uploaded successfully!</h2>';
            echo '<p><strong>' . number_format($count) . ' registrations</strong> imported.</p>';
            echo '<p><a href="admin-panel-v2.php">→ Go to Admin Panel</a></p>';
            echo '<p style="color:#721c24;font-weight:bold;margin-top:20px;">⚠️ DELETE this file from the server now: db-upload.php</p>';
            echo '</div>';
        } catch (Exception $e) {
            echo '<p style="color:orange">Uploaded but could not verify: ' . $e->getMessage() . '</p>';
        }
    } else {
        echo '<p style="color:red">Failed to move uploaded file. Check server write permissions.</p>';
    }
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>TCAM DB Upload</title>
    <style>
        body { font-family: sans-serif; max-width: 500px; margin: 60px auto; padding: 20px; }
        input, button { display: block; width: 100%; padding: 10px; margin: 10px 0; font-size: 1rem; box-sizing: border-box; }
        button { background: #ff6b35; color: #fff; border: none; border-radius: 6px; cursor: pointer; }
        .warn { background: #fff3cd; padding: 12px; border-radius: 6px; border-left: 4px solid #ffc107; }
    </style>
</head>
<body>
    <h2>TCAM Database Upload</h2>
    <div class="warn">⚠️ Delete this file from the server immediately after use.</div>
    <form method="POST" enctype="multipart/form-data">
        <label>Secret Key:</label>
        <input type="password" name="secret" placeholder="Enter secret key" required>
        <label>Select tcam_bookings.db file:</label>
        <input type="file" name="dbfile" accept=".db" required>
        <button type="submit">Upload Database</button>
    </form>
    <p><small>Secret key: <code>tcam_upload_2024</code></small></p>
</body>
</html>
