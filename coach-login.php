<?php
/**
 * TCAM Coach Login and Registration Page
 */
require_once 'security-config.php';
session_start();
require_once 'secure-database.php';
require_once 'includes/coach-account.php';

if (!empty($_SESSION['coach_logged_in']) && $_SESSION['coach_logged_in'] === true) {
    header('Location: coach-panel.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'register') {
        $name = Security::sanitizeInput($_POST['name'] ?? '');
        $mobile = Security::sanitizeInput($_POST['mobile'] ?? '');
        $district = Security::sanitizeInput($_POST['district'] ?? '');

        if ($name === '' || $mobile === '' || $district === '') {
            $error = 'Name, mobile and district are required for registration.';
        } else {
            try {
                $db = SecureDatabase::getInstance();
                $account = createCoachAccount($db->getPDO(), $name, $mobile, $district);
                if ($account['ok']) {
                    header('Location: coach-login.php');
                    exit;
                } else {
                    $error = $account['error'];
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
                    $_SESSION['coach_name'] = $coach['name'] !== '' ? $coach['name'] : $coach['username'];
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/theme.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', Arial, sans-serif; min-height:100vh; display:flex; align-items:center; justify-content:center; padding:110px 20px 50px; background: linear-gradient(135deg, #0B0F14 0%, #1a1038 55%, #312e81 100%); color:#F2F4F7; }
        .card { width:100%; max-width:460px; background:#141A22; border:1px solid rgba(255,255,255,0.08); border-radius:20px; padding:32px; box-shadow:0 24px 60px rgba(0,0,0,0.5); transition: transform 0.35s cubic-bezier(.4,0,.2,1), box-shadow 0.35s cubic-bezier(.4,0,.2,1); animation: tcamContainerIn 0.7s cubic-bezier(.4,0,.2,1) both; }
        @keyframes tcamContainerIn { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        .card:hover { transform: translateY(-3px); box-shadow: 0 30px 70px rgba(0,0,0,0.6); }
        h1 { font-family:'Poppins', Arial, sans-serif; font-weight:800; margin-bottom:16px; color:#818cf8; font-size:1.8rem; }
        p { margin-bottom:24px; color:#8A94A6; }
        label { display:block; margin-bottom:8px; font-weight:600; color:#F2F4F7; }
        input { width:100%; padding:14px 16px; background:#0B0F14; color:#F2F4F7; border:1px solid rgba(255,255,255,0.14); border-radius:10px; margin-bottom:18px; font-size:1rem; font-family:inherit; transition: border-color 0.25s ease, box-shadow 0.25s ease; }
        input:focus { outline:none; border-color:#6366f1; box-shadow:0 0 0 4px rgba(99,102,241,0.2); }
        button { width:100%; padding:14px; border:none; border-radius:999px; background:linear-gradient(90deg,#4f46e5,#6366f1); color:#fff; font-weight:700; letter-spacing:0.3px; cursor:pointer; box-shadow:0 10px 24px rgba(79,70,229,0.28); transition: transform .25s cubic-bezier(.4,0,.2,1), box-shadow .25s ease, background .25s ease; }
        button:hover { transform:translateY(-3px); box-shadow:0 16px 32px rgba(79,70,229,0.4); background:linear-gradient(90deg,#4338ca,#4f46e5); }
        .error { background:rgba(239,68,68,0.14); color:#ff8a8a; border:1px solid rgba(239,68,68,0.3); padding:12px 14px; border-radius:12px; margin-bottom:18px; }
        .success { background:rgba(39,174,96,0.14); color:#4ade80; border:1px solid rgba(39,174,96,0.3); padding:12px 14px; border-radius:12px; margin-bottom:18px; }
        .toggle-row { display:flex; justify-content:space-between; gap:10px; margin-bottom:18px; }
        .toggle-row button { flex:1; padding:12px; font-weight:700; background:transparent; color:#818cf8; border:1px solid #4f46e5; border-radius:10px; box-shadow:none; }
        .toggle-row button:hover { transform:none; box-shadow:none; background:rgba(99,102,241,0.1); }
        .toggle-row button.active { background:#4f46e5; color:#fff; }
        .form-section { display:none; }
        .form-section.active { display:block; }
        .note { font-size:0.9rem; color:#6b7280; margin-top:16px; }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="site-header">
        <div class="nav-overlay" id="navOverlay" onclick="closeMenu()"></div>
        <div class="nav-container">
            <button class="hamburger" id="hamburger" aria-label="Open menu" onclick="toggleMenu()">
                <span></span><span></span><span></span>
            </button>

            <a href="index.html" class="logo">
                <img src="images/logo.png" alt="TCAM Logo" style="height:40px;vertical-align:middle;margin-right:10px;">
                TCAM
            </a>
            <nav>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.html"                   onclick="closeMenu()">Home</a></li>
                    <li><a href="about.html"                   onclick="closeMenu()">About Us</a></li>
                    <li><a href="managment.html"               onclick="closeMenu()">Management</a></li>
                    <li><a href="contact.html"                 onclick="closeMenu()">Contact</a></li>
                    <li><a href="tournament.php"               onclick="closeMenu()">Tournament</a></li>
                    <li><a href="gallery.php"                  onclick="closeMenu()">Gallery</a></li>
                    <li><a href="student-search.php"           onclick="closeMenu()">🔍 Search Data</a></li>
                    <li><a href="single-registration-enhanced.html" onclick="closeMenu()">Update Registration</a></li>
                    <!-- Mobile-only buttons inside drawer -->
                    <li class="show-mobile-only mobile-register-row" style="border-bottom:none;margin-top:0.5rem;">
                        <a href="index.html#booking" style="display:block;text-align:center;width:100%;border-radius:8px;background:#D8F84A;color:#0B0F14;text-decoration:none;padding:0.7rem 1.5rem;font-weight:800;text-transform:uppercase;" onclick="closeMenu()">Register Now</a>
                    </li>
                    <li class="show-mobile-only mobile-register-row" style="border-bottom:none;margin-top:0.6rem;">
                        <a href="index.html" style="display:block;text-align:center;width:100%;border-radius:8px;background:#FF7A1A;color:#fff;text-decoration:none;padding:0.7rem 1.5rem;font-weight:bold;" onclick="closeMenu()">🏏 Register as Coach</a>
                    </li>
                    <li class="show-mobile-only mobile-register-row" style="border-bottom:none;margin-top:0.6rem;">
                        <a href="coach-login.php" style="display:block;text-align:center;width:100%;border-radius:8px;background:#312e81;color:#fff;text-decoration:none;padding:0.6rem 1rem;font-weight:600;" onclick="closeMenu()">🔑 Coach Login</a>
                    </li>
                </ul>
            </nav>
            <div style="display:flex;gap:0.6rem;align-items:center;flex-shrink:0;">
                <a href="index.html#booking" class="register-btn header-register-btn" style="text-decoration:none;display:inline-flex;align-items:center;">Register Now</a>
                <a href="index.html" class="register-btn header-register-btn" style="background:#FF7A1A;text-decoration:none;display:inline-flex;align-items:center;">Register as Coach</a>
                <a href="coach-login.php" class="register-btn header-register-btn" style="background:#312e81;display:inline-flex;align-items:center;text-decoration:none;">Coach Login</a>
            </div>
        </div>
    </header>


    <div class="card">
        <h1>Coach Portal</h1>
        <p>Login or register once here. District is fixed during registration and can only be changed by admin.</p>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
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
                <button type="submit">Register Coach</button>
            </form>
            <div class="note">Your login username will be your name and your password will be your mobile number. The district you choose here is fixed until admin approves a change.</div>
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
    <script src="js/header.js"></script>
    <script src="js/animations.js"></script>
</body>
</html>
