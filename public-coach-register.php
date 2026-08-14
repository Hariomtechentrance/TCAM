<?php
require_once 'cors.php';
header('Content-Type: application/json; charset=UTF-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid method']);
    exit;
}
require_once 'secure-database.php';
require_once 'includes/coach-account.php';

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

    // Also create a coach login so the account works immediately: username
    // is the coach's name, password is their mobile number.
    $loginMessage = '';
    try {
        $account = createCoachAccount($db, $name, $mobile, $district);
        if ($account['ok']) {
            $loginMessage = ' You can log in to the Coach Panel now — username: "' . $account['username'] . '", password: your mobile number.';
        }
    } catch (Exception $e) {
        // non-fatal: application row is still saved even if the login couldn't be created
    }

    echo json_encode(['status'=>'success','message'=>'Registration completed. Your application is received and account will be visible in admin panel shortly.' . $loginMessage]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'Unable to submit application.']);
}
