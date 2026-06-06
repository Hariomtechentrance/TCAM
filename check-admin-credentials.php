<?php
/**
 * Check Admin Credentials
 * Shows current admin users in database
 */

require_once 'security-config.php';
require_once 'secure-database.php';

echo "<h2>🔐 Admin Credentials</h2>";

try {
    $db = SecureDatabase::getInstance();
    
    // Get all users
    $users = $db->execute("SELECT id, username, email, role, is_active, created_at, last_login FROM users ORDER BY id")->fetchAll();
    
    if (!empty($users)) {
        echo "<div style='background: #f8f9fa; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem;'>";
        echo "<h3>👥 Registered Admin Users:</h3>";
        
        foreach ($users as $user) {
            echo "<div style='background: white; padding: 1rem; border-radius: 6px; margin-bottom: 1rem; border-left: 4px solid #764ba2;'>";
            echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;'>";
            echo "<div>";
            echo "<strong>🆔 Username:</strong> " . htmlspecialchars($user['username']) . "<br>";
            echo "<strong>📧 Email:</strong> " . htmlspecialchars($user['email']) . "<br>";
            echo "<strong>👤 Role:</strong> " . htmlspecialchars($user['role']) . "<br>";
            echo "<strong>📅 Created:</strong> " . date('d M Y', strtotime($user['created_at'])) . "<br>";
            echo "</div>";
            echo "<div>";
            echo "<strong>✅ Status:</strong> " . ($user['is_active'] ? 'Active' : 'Inactive') . "<br>";
            echo "<strong>🕐 Last Login:</strong> " . ($user['last_login'] ? date('d M Y H:i', strtotime($user['last_login'])) : 'Never') . "<br>";
            echo "</div>";
            echo "</div>";
            echo "</div>";
        }
        echo "</div>";
        
        echo "<div style='background: #e8f5e8; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #28a745;'>";
        echo "<h3>🔑 Default Login Credentials:</h3>";
        echo "<p><strong>Username:</strong> <code style='background: #f1f3f4; color: #388e3c; padding: 0.5rem; border-radius: 4px;'>admin</code></p>";
        echo "<p><strong>Password:</strong> <code style='background: #f1f3f4; color: #388e3c; padding: 0.5rem; border-radius: 4px;'>admin123!@#</code></p>";
        echo "<p style='color: #666; font-size: 0.9rem; margin-top: 1rem;'><strong>📍 Login URL:</strong> <a href='login-secure.php' style='color: #764ba2;'>http://localhost:8000/login-secure.php</a></p>";
        echo "</div>";
        
    } else {
        echo "<p style='color: #666;'>No users found in database.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<p><a href="login-secure.php">🔐 Go to Login</a></p>
<p><a href="admin-dashboard.php">🎯 Go to Admin Dashboard</a></p>
