<?php
require_once 'cors.php';
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid method']);
    exit;
}
require_once 'secure-database.php';

$name = trim($_POST['name'] ?? '');
$mobile = trim($_POST['mobile'] ?? '');
$email = trim($_POST['email'] ?? '');
$district = trim($_POST['district'] ?? '');

if ($name === '' || $mobile === '' || $district === '') {
    echo json_encode(['status'=>'error','message'=>'Name, mobile and district are required']);
    exit;
}

try {
    $db = SecureDatabase::getInstance()->getPDO();
    // create applications table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS coach_applications (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        mobile TEXT NOT NULL,
        email TEXT DEFAULT '',
        district TEXT NOT NULL,
        photo_path TEXT DEFAULT '',
        status TEXT DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    $photoPath = '';
    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $f = $_FILES['photo'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp'])) {
            $dir = __DIR__ . '/uploads/coaches/'; if (!is_dir($dir)) mkdir($dir,0755,true);
            $fname = 'coach_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (move_uploaded_file($f['tmp_name'], $dir . $fname)) {
                $photoPath = 'uploads/coaches/' . $fname;
            }
        }
    }

    $stmt = $db->prepare('INSERT INTO coach_applications (name,mobile,email,district,photo_path) VALUES (?,?,?,?,?)');
    $stmt->execute([$name,$mobile,$email,$district,$photoPath]);
    // Also create a coach user so the account appears in admin panel immediately.
    try {
        $usernameBase = preg_replace('/[^a-z0-9]+/i','', strtolower(explode(' ', $name)[0] ?? 'coach')) ?: 'coach';
        $username = $usernameBase . '_' . preg_replace('/[^0-9]/','', $mobile ?: (string)time());
        // ensure unique
        $uStmt = $db->prepare('SELECT COUNT(*) FROM coach_users WHERE username = ?');
        $i = 1; $orig = $username;
        while ($uStmt->execute([$username]) && $uStmt->fetchColumn() > 0) { $username = $orig . $i; $i++; }
        $randomPassword = bin2hex(random_bytes(4));
        $hash = password_hash($randomPassword, PASSWORD_DEFAULT);
        // create coach_users table if needed
        $db->exec("CREATE TABLE IF NOT EXISTS coach_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            district TEXT NOT NULL,
            mobile TEXT DEFAULT '',
            name TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        $ins = $db->prepare('INSERT OR IGNORE INTO coach_users (username,password_hash,district,mobile,name) VALUES (?,?,?,?,?)');
        $ins->execute([$username,$hash,$district,$mobile,$name]);
    } catch (Exception $e) {
        // non-fatal: continue
    }

    echo json_encode(['status'=>'success','message'=>'Registration completed. Your application is received and account will be visible in admin panel shortly.']);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'Unable to submit application.']);
}
