<?php
/**
 * TCAM Coach Login and Registration Page
 */
session_start();
require_once 'security-config.php';
require_once 'secure-database.php';

if (!empty($_SESSION['coach_logged_in']) && $_SESSION['coach_logged_in'] === true) {
    header('Location: coach-panel.php');
    exit;
}

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'register') {
        $name = Security::sanitizeInput($_POST['name'] ?? '');
        $mobile = Security::sanitizeInput($_POST['mobile'] ?? '');
        $district = Security::sanitizeInput($_POST['district'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($name === '' || $mobile === '' || $district === '' || $username === '' || $password === '' || $confirm === '') {
            $error = 'All fields are required for registration.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } else {
            try {
                $db = SecureDatabase::getInstance();
                $stmt = $db->getPDO()->prepare('SELECT COUNT(*) FROM coach_users WHERE username = ?');
                $stmt->execute([$username]);
                if ($stmt->fetchColumn() > 0) {
                    $error = 'Username already exists. Please choose another.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $db->getPDO()->prepare('INSERT INTO coach_users (username, password_hash, district, mobile, name) VALUES (?, ?, ?, ?, ?)');
                    $ins->execute([$username, $hash, $district, $mobile, $name]);
                    $success = 'Registration complete. You can now login using your credentials.';
                }
            } catch (Exception $e) {
                $error = 'Unable to create coach account. Please try again later.';
            }
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {
            $error = 'Please enter both username and password.';
        } else {
            try {
                $db = SecureDatabase::getInstance();
                $stmt = $db->getPDO()->prepare('SELECT * FROM coach_users WHERE username = ? LIMIT 1');
                $stmt->execute([$username]);
                $coach = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($coach && password_verify($password, $coach['password_hash'])) {
                    $_SESSION['coach_logged_in'] = true;
                    $_SESSION['coach_username'] = $coach['username'];
                    $_SESSION['coach_user_id'] = $coach['id'];
                    $_SESSION['coach_district'] = $coach['district'];
                    $_SESSION['coach_last_activity'] = time();

                    header('Location: coach-panel.php');
                    exit;
                }

                $error = 'Invalid username or password.';
            } catch (Exception $e) {
                $error = 'Unable to verify credentials. Please try again later.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Login — TCAM</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: Arial, sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; background: linear-gradient(135deg,#667eea,#764ba2); color:#222; }
        .card { width:100%; max-width:460px; background:#fff; border-radius:18px; padding:32px; box-shadow:0 20px 60px rgba(0,0,0,0.12); }
        h1 { margin-bottom:16px; color:#312e81; font-size:1.8rem; }
        p { margin-bottom:24px; color:#555; }
        label { display:block; margin-bottom:8px; font-weight:600; color:#444; }
        input { width:100%; padding:14px 16px; border:1px solid #d1d5db; border-radius:10px; margin-bottom:18px; font-size:1rem; }
        button { width:100%; padding:14px; border:none; border-radius:10px; background:#4f46e5; color:#fff; font-weight:700; cursor:pointer; transition:transform .2s ease; }
        button:hover { transform:translateY(-1px); }
        .error { background:#fde2e2; color:#b91c1c; padding:12px 14px; border-radius:12px; margin-bottom:18px; }
        .success { background:#dcfce7; color:#166534; padding:12px 14px; border-radius:12px; margin-bottom:18px; }
        .toggle-row { display:flex; justify-content:space-between; gap:10px; margin-bottom:18px; }
        .toggle-row button { flex:1; padding:12px; font-weight:700; background:transparent; color:#4f46e5; border:1px solid #4f46e5; border-radius:10px; }
        .toggle-row button.active { background:#4f46e5; color:#fff; }
        .form-section { display:none; }
        .form-section.active { display:block; }
        .note { font-size:0.9rem; color:#6b7280; margin-top:16px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Coach Portal</h1>
        <p>Login or register once here. District is fixed during registration and can only be changed by admin.</p>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <div class="toggle-row">
            <button id="loginTab" class="active">Login</button>
            <button id="registerTab">Register</button>
        </div>
        <div id="loginSection" class="form-section active">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="login">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" required autofocus>
                <label for="password">Password</label>
                <input id="password" name="password" type="password" required>
                <button type="submit">Login</button>
            </form>
        </div>
        <div id="registerSection" class="form-section">
            <form method="POST" autocomplete="off">
                <input type="hidden" name="action" value="register">
                <label for="regName">Coach Name</label>
                <input id="regName" name="name" type="text" placeholder="Enter coach name" required>
                <label for="regMobile">Mobile Number</label>
                <input id="regMobile" name="mobile" type="text" placeholder="Enter mobile number" required>
                <label for="regDistrict">District</label>
                <input id="regDistrict" name="district" type="text" placeholder="Enter your district" required>
                <label for="regUsername">Username</label>
                <input id="regUsername" name="username" type="text" required>
                <label for="regPassword">Password</label>
                <input id="regPassword" name="password" type="password" required>
                <label for="regConfirmPassword">Confirm Password</label>
                <input id="regConfirmPassword" name="confirm_password" type="password" required>
                <button type="submit">Register Coach</button>
            </form>
            <div class="note">The district you choose here is fixed until admin approves a change.</div>
        </div>
    </div>
    <script>
        const loginTab = document.getElementById('loginTab');
        const registerTab = document.getElementById('registerTab');
        const loginSection = document.getElementById('loginSection');
        const registerSection = document.getElementById('registerSection');

        function showSection(section) {
            if (section === 'login') {
                loginTab.classList.add('active');
                registerTab.classList.remove('active');
                loginSection.classList.add('active');
                registerSection.classList.remove('active');
            } else {
                registerTab.classList.add('active');
                loginTab.classList.remove('active');
                registerSection.classList.add('active');
                loginSection.classList.remove('active');
            }
        }

        loginTab.addEventListener('click', () => showSection('login'));
        registerTab.addEventListener('click', () => showSection('register'));

        <?php if (!empty($_GET['register'])): ?>
            showSection('register');
        <?php endif; ?>
    </script>
</body>
</html>
