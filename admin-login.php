<?php
/**
 * Admin Login Page — TCAM
 */
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
$_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.cookie_secure', $_https ? '1' : '0');
session_start();
require_once 'config.php';

// If already logged in, redirect
if (!empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: admin-panel-v2.php');
    exit;
}

$error = '';
$timeout = isset($_GET['timeout']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $dbPath = getenv('TCAM_DB_PATH') ?: __DIR__ . '/tcam_bookings.db';
        try {
            $db = new PDO('sqlite:' . $dbPath);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Auto-migrate
            $db->exec("CREATE TABLE IF NOT EXISTS admin_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                last_login DATETIME
            )");
            $cnt = $db->query("SELECT COUNT(*) FROM admin_users")->fetchColumn();
            if ($cnt === 0) {
                // No admin exists — seed from config credentials only; change these before deploying
                $seedHash = defined('ADMIN_PASSWORD_HASH') ? ADMIN_PASSWORD_HASH : password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                $seedUser = defined('ADMIN_USERNAME') ? ADMIN_USERNAME : 'admin';
                $db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)")->execute([$seedUser, $seedHash]);
            }

            $stmt = $db->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $authenticated = false;
            if ($user && password_verify($password, $user['password_hash'])) {
                $authenticated = true;
            } elseif (defined('ADMIN_USERNAME') && defined('ADMIN_PASSWORD_HASH')
                && $username === ADMIN_USERNAME
                && password_verify($password, ADMIN_PASSWORD_HASH)) {
                $authenticated = true;
            }

            if ($authenticated) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                $_SESSION['admin_user_id'] = $user['id'] ?? 0;
                $_SESSION['admin_last_activity'] = time();
                $_SESSION['login_time'] = time();

                if ($user) {
                    $db->prepare("UPDATE admin_users SET last_login = datetime('now') WHERE id = ?")->execute([$user['id']]);
                }
                header('Location: admin-panel-v2.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (PDOException $e) {
            $error = 'Database error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — TCAM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            overflow: hidden;
        }
        .particles {
            position: fixed; top:0; left:0; width:100%; height:100%; z-index:0; pointer-events:none;
        }
        .particles span {
            position: absolute;
            width: 6px; height: 6px;
            background: rgba(255,107,53,0.3);
            border-radius: 50%;
            animation: float 8s infinite ease-in-out;
        }
        .particles span:nth-child(2) { width:4px; height:4px; left:20%; top:30%; animation-delay:1s; background:rgba(118,75,162,0.4); }
        .particles span:nth-child(3) { width:8px; height:8px; left:70%; top:20%; animation-delay:2s; }
        .particles span:nth-child(4) { width:3px; height:3px; left:40%; top:70%; animation-delay:3s; background:rgba(102,126,234,0.4); }
        .particles span:nth-child(5) { width:5px; height:5px; left:80%; top:60%; animation-delay:4s; }
        .particles span:nth-child(6) { width:7px; height:7px; left:10%; top:80%; animation-delay:5s; background:rgba(255,179,71,0.3); }
        @keyframes float {
            0%,100% { transform: translateY(0) translateX(0); opacity:0.6; }
            25% { transform: translateY(-40px) translateX(20px); opacity:1; }
            50% { transform: translateY(-80px) translateX(-10px); opacity:0.4; }
            75% { transform: translateY(-40px) translateX(30px); opacity:0.8; }
        }
        .login-card {
            position: relative; z-index:1;
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 24px;
            padding: 48px 40px;
            max-width: 420px;
            width: 90%;
            box-shadow: 0 32px 64px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.05) inset;
            animation: slideUp 0.6s cubic-bezier(.4,0,.2,1);
        }
        @keyframes slideUp {
            from { opacity:0; transform:translateY(40px); }
            to { opacity:1; transform:translateY(0); }
        }
        .login-logo {
            text-align: center; margin-bottom: 32px;
        }
        .login-logo .icon {
            width: 64px; height: 64px;
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            border-radius: 18px;
            display: inline-flex; align-items: center; justify-content: center;
            font-size: 28px; margin-bottom: 16px;
            box-shadow: 0 8px 24px rgba(255,107,53,0.3);
        }
        .login-logo h1 {
            color: #fff; font-size: 1.8rem; font-weight: 800; letter-spacing:-0.5px;
        }
        .login-logo p { color: rgba(255,255,255,0.5); font-size: 0.9rem; margin-top:6px; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block; color: rgba(255,255,255,0.7); font-size:0.85rem; font-weight:600; margin-bottom:8px; letter-spacing:0.5px; text-transform:uppercase;
        }
        .form-group input {
            width: 100%; padding: 14px 16px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.12); border-radius: 12px; color: #fff; font-size: 1rem; font-family: 'Inter',sans-serif; outline: none; transition: all 0.3s;
        }
        .form-group input:focus {
            border-color: #ff6b35; background: rgba(255,255,255,0.12); box-shadow: 0 0 0 3px rgba(255,107,53,0.15);
        }
        .form-group input::placeholder { color: rgba(255,255,255,0.3); }
        .password-field {
            position: relative;
        }
        .password-field input {
            padding-right: 44px;
        }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 14px;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: rgba(255,255,255,0.72);
            cursor: pointer;
            font-size: 1rem;
            padding: 0;
        }
        .password-toggle:hover {
            color: #ffffff;
        }
        .login-btn {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #ff6b35, #f7931e); color: #fff; border: none; border-radius: 12px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.3s; font-family: 'Inter',sans-serif; letter-spacing:0.5px;
        }
        .login-btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(255,107,53,0.4); }
        .login-btn:active { transform: translateY(0); }
        .error-msg {
            background: rgba(231,76,60,0.15); border:1px solid rgba(231,76,60,0.3); color: #ff6b6b; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align:center;
        }
        .timeout-msg {
            background: rgba(255,179,71,0.15); border:1px solid rgba(255,179,71,0.3); color: #ffb347; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; font-size: 0.9rem; text-align:center;
        }
        .back-link {
            text-align:center; margin-top:24px;
        }
        .back-link a {
            color:rgba(255,255,255,0.4); text-decoration:none; font-size:0.85rem; transition:color 0.3s;
        }
        .back-link a:hover { color:#ff6b35; }
    </style>
</head>
<body>
    <div class="particles">
        <span style="left:5%;top:10%"></span><span></span><span></span><span></span><span></span><span></span>
    </div>
    <div class="login-card">
        <div class="login-logo">
            <div class="icon">🏏</div>
            <h1>TCAM Admin</h1>
            <p>Tennis Cricket Association Maharashtra</p>
        </div>

        <?php if ($timeout): ?>
            <div class="timeout-msg">⏰ Session expired. Please log in again.</div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error-msg">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter admin username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Enter password" required>
                    <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password">👁️</button>
                </div>
            </div>
            <button type="submit" class="login-btn">Sign In →</button>
        </form>
        <div class="back-link">
            <a href="index.html">← Back to Website</a>
        </div>
    </div>
    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', () => {
            const isPassword = passwordInput.getAttribute('type') === 'password';
            passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
            togglePassword.textContent = isPassword ? '🙈' : '👁️';
            togglePassword.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        });
    </script>
</body>
</html>
