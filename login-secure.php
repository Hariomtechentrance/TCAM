<?php
/**
 * Secure Login System for TCAM
 * Implements comprehensive security measures
 */

require_once 'security-config.php';
require_once 'secure-auth.php';

// Initialize auth system
$auth = new SecureAuth();

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting
    Security::rateLimit(5, 300); // 5 attempts per 5 minutes
    
    $username = Security::sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {
        // Attempt login
        $result = $auth->login($username, $password);
        
        if ($result['success']) {
            // Redirect to admin panel
            header('Location: admin-dashboard.php');
            exit;
        } else {
            $error = $result['error'];
        }
    }
}

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCAM Secure Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #333;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #764ba2;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        .btn {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #764ba2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a3785;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(118, 75, 162, 0.3);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .security-info {
            margin-top: 30px;
            padding: 20px;
            background: #e8f4fd;
            border-radius: 8px;
            border-left: 4px solid #764ba2;
        }
        
        .security-info h3 {
            color: #764ba2;
            margin-bottom: 10px;
            font-size: 1rem;
        }
        
        .security-info ul {
            list-style: none;
            font-size: 0.85rem;
            color: #666;
        }
        
        .security-info li {
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }
        
        .security-info li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #764ba2;
            font-weight: bold;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #764ba2;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🛡️ TCAM</h1>
            <p>Secure Admin Login</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                       autocomplete="username">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required 
                       autocomplete="current-password">
            </div>
            
            <button type="submit" class="btn btn-primary">Secure Login</button>
        </form>

        <div class="security-info">
            <h3>🔒 Security Features</h3>
            <ul>
                <li>CSRF Protection</li>
                <li>Rate Limiting</li>
                <li>Account Lockout</li>
                <li>Secure Sessions</li>
                <li>Activity Logging</li>
            </ul>
        </div>

        <div class="back-link">
            <a href="index.html">← Back to Website</a>
        </div>
    </div>
</body>
</html>
