<?php
/**
 * TCAM Full Database Migration — ONE-TIME USE
 * Imports full_db_export.sql into the live server database.
 * DELETE THIS FILE AND full_db_export.sql AFTER SUCCESSFUL IMPORT.
 */

$SECRET   = 'tcam2024migrate';
$dbPath   = __DIR__ . '/tcam_bookings.db';
$sqlFile  = __DIR__ . '/full_db_export.sql';

// ── Auth ────────────────────────────────────────────────────────────────────
$authed = (($_SESSION['mig_auth'] ?? '') === 'yes');
session_start();
$authed = (($_SESSION['mig_auth'] ?? '') === 'yes');

if (!$authed) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['secret'] ?? '') === $SECRET) {
        $_SESSION['mig_auth'] = 'yes';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
    // Show login form
    echo '<!DOCTYPE html><html><head><title>TCAM DB Migrate</title>
    <style>body{font-family:sans-serif;max-width:420px;margin:80px auto;padding:20px}
    input,button{display:block;width:100%;padding:10px;margin:10px 0;font-size:1rem;box-sizing:border-box}
    button{background:#ff6b35;color:#fff;border:none;border-radius:6px;cursor:pointer}</style></head><body>
    <h2>TCAM DB Migration</h2>
    <form method="POST"><input type="password" name="secret" placeholder="Secret key" required>
    <button>Unlock</button></form></body></html>';
    exit;
}

// ── Run migration ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run'])) {
    header('Content-Type: text/plain; charset=UTF-8');

    if (!file_exists($sqlFile)) {
        echo "ERROR: full_db_export.sql not found on server.\n";
        exit;
    }

    set_time_limit(300);

    try {
        // Backup existing DB
        if (file_exists($dbPath) && filesize($dbPath) > 0) {
            copy($dbPath, $dbPath . '.bak.' . date('Ymd_His'));
            echo "Backed up existing database.\n";
        }

        $db = new PDO('sqlite:' . $dbPath);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('PRAGMA foreign_keys = OFF');
        $db->exec('PRAGMA journal_mode = WAL');

        $sql    = file_get_contents($sqlFile);
        $stmts  = array_filter(array_map('trim', explode(";\n", $sql)));

        $ok = 0; $skip = 0; $fail = 0;
        $db->beginTransaction();

        foreach ($stmts as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '' || $stmt === 'COMMIT' || $stmt === 'BEGIN TRANSACTION') continue;

            // Skip PRAGMA foreign_keys lines from dump
            if (stripos($stmt, 'PRAGMA') === 0) { $skip++; continue; }

            // For CREATE TABLE: use IF NOT EXISTS
            $stmt = preg_replace('/^CREATE TABLE\s+"/i',        'CREATE TABLE IF NOT EXISTS "', $stmt);
            $stmt = preg_replace('/^CREATE TABLE\s+(?!")/i',    'CREATE TABLE IF NOT EXISTS ',  $stmt);

            // For INSERT: use OR IGNORE to skip duplicates
            $stmt = preg_replace('/^INSERT INTO/i', 'INSERT OR IGNORE INTO', $stmt);

            try {
                $db->exec($stmt);
                $ok++;
            } catch (PDOException $e) {
                $fail++;
                if ($fail <= 5) {
                    echo "SKIP: " . $e->getMessage() . "\n  → " . substr($stmt, 0, 80) . "\n";
                }
            }
        }

        $db->commit();
        $db->exec('PRAGMA foreign_keys = ON');

        // Verify
        $counts = [];
        foreach (['bookings','registrations','tournaments','coach_users','districts','gallery_images'] as $t) {
            try { $counts[$t] = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn(); }
            catch (Exception $e) { $counts[$t] = 'N/A'; }
        }

        echo "\n=== Import Complete ===\n";
        echo "Statements executed: $ok\n";
        echo "Skipped/failed: $fail\n\n";
        echo "Table counts:\n";
        foreach ($counts as $t => $c) { echo "  $t: $c\n"; }
        echo "\n✅ Done! Go to admin panel: /admin-panel-v2.php\n";
        echo "⚠️  DELETE db-migrate.php and full_db_export.sql from server now!\n";

    } catch (Throwable $e) {
        if (isset($db) && $db->inTransaction()) $db->rollBack();
        echo "FATAL ERROR: " . $e->getMessage() . "\n";
    }
    exit;
}

// ── UI ───────────────────────────────────────────────────────────────────────
$sqlExists = file_exists($sqlFile);
$sqlSize   = $sqlExists ? round(filesize($sqlFile) / 1024, 1) . ' KB' : 'MISSING';
?>
<!DOCTYPE html>
<html>
<head>
<title>TCAM DB Migration</title>
<style>
body { font-family: sans-serif; max-width: 560px; margin: 60px auto; padding: 20px; background: #f5f5f5; }
.card { background: #fff; border-radius: 10px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
h2 { margin-top: 0; }
.info { background: #e8f4fd; border-left: 4px solid #2196f3; padding: 12px 16px; border-radius: 4px; margin: 16px 0; }
.warn { background: #fff3cd; border-left: 4px solid #ffc107; padding: 12px 16px; border-radius: 4px; margin: 16px 0; }
.err  { background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px 16px; border-radius: 4px; margin: 16px 0; }
button { background: #ff6b35; color: #fff; border: none; border-radius: 6px; padding: 12px 28px; font-size: 1rem; cursor: pointer; margin-top: 10px; }
button:hover { background: #e55a25; }
button:disabled { background: #aaa; }
pre { background: #1a1a2e; color: #7fff7f; padding: 16px; border-radius: 6px; font-size: 0.85rem; overflow-x: auto; min-height: 60px; }
</style>
</head>
<body>
<div class="card">
    <h2>🗄️ TCAM Database Migration</h2>

    <?php if (!$sqlExists): ?>
    <div class="err">❌ <strong>full_db_export.sql not found.</strong><br>
    Make sure it was pushed to GitHub and the server pulled it.</div>
    <?php else: ?>
    <div class="info">
        ✅ <strong>full_db_export.sql found</strong> (<?= $sqlSize ?>)<br>
        This will import all local data into the live server database.<br>
        Existing records will be skipped (no duplicates).
    </div>
    <?php endif; ?>

    <div class="warn">⚠️ <strong>Delete this file after use.</strong> It is a security risk.</div>

    <?php if ($sqlExists): ?>
    <form method="POST" onsubmit="runImport(event)">
        <input type="hidden" name="run" value="1">
        <button id="runBtn" type="submit">▶ Run Import Now</button>
    </form>
    <pre id="output">Output will appear here...</pre>
    <?php endif; ?>
</div>

<script>
async function runImport(e) {
    e.preventDefault();
    const btn = document.getElementById('runBtn');
    const out = document.getElementById('output');
    btn.disabled = true;
    btn.textContent = 'Importing...';
    out.textContent = 'Running import (may take 10-30 seconds)...\n';

    try {
        const fd = new FormData(e.target);
        const r  = await fetch(location.href, { method: 'POST', body: fd });
        const txt = await r.text();
        out.textContent = txt;
    } catch (err) {
        out.textContent = 'Error: ' + err.message;
    }

    btn.textContent = 'Done';
}
</script>
</body>
</html>
