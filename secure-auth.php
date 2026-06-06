<?php
/**
 * Secure Authentication System for TCAM
 * Handles user authentication, sessions, and authorization
 */

require_once 'security-config.php';
require_once 'secure-database.php';

class SecureAuth {
    private $db;
    
    public function __construct() {
        $this->db = SecureDatabase::getInstance();
        $this->createUsersTable();
    }
    
    /**
     * Create users table if not exists
     */
    private function createUsersTable() {
        $query = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) DEFAULT 'user',
            is_active BOOLEAN DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            login_attempts INTEGER DEFAULT 0,
            locked_until DATETIME
        )";
        
        $this->db->execute($query);
        
        // Create admin user if not exists
        $this->createDefaultAdmin();
    }
    
    /**
     * Create default admin user
     */
    private function createDefaultAdmin() {
        $admin = $this->db->select('users', ['username' => 'admin']);
        
        if (empty($admin)) {
            $passwordHash = password_hash('admin123!@#', PASSWORD_ARGON2ID);
            $this->db->insert('users', [
                'username' => 'admin',
                'email' => 'admin@tcam.com',
                'password_hash' => $passwordHash,
                'role' => 'admin'
            ]);
            
            Security::logEvent('DEFAULT_ADMIN_CREATED', ['username' => 'admin']);
        }
    }
    
    /**
     * Register new user
     */
    public function register($username, $email, $password, $role = 'user') {
        // Rate limiting
        Security::rateLimit(3, 300); // 3 attempts per 5 minutes
        
        // Validate inputs
        if (!Security::validateName($username)) {
            return ['success' => false, 'error' => 'Invalid username'];
        }
        
        if (!Security::validateEmail($email)) {
            return ['success' => false, 'error' => 'Invalid email'];
        }
        
        if (strlen($password) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }
        
        // Check if user exists
        $existing = $this->db->select('users', ['username' => $username]);
        if (!empty($existing)) {
            return ['success' => false, 'error' => 'Username already exists'];
        }
        
        $existing = $this->db->select('users', ['email' => $email]);
        if (!empty($existing)) {
            return ['success' => false, 'error' => 'Email already exists'];
        }
        
        // Create user
        $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
        
        try {
            $userId = $this->db->insert('users', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role
            ]);
            
            Security::logEvent('USER_REGISTERED', [
                'user_id' => $userId,
                'username' => $username,
                'email' => $email
            ]);
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Registration failed'];
        }
    }
    
    /**
     * Login user
     */
    public function login($username, $password) {
        // Rate limiting
        Security::rateLimit(5, 300); // 5 attempts per 5 minutes
        
        // Validate inputs
        $username = Security::sanitizeInput($username);
        
        // Get user
        $user = $this->db->select('users', ['username' => $username]);
        
        if (empty($user)) {
            $this->handleFailedLogin($username);
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        $user = $user[0];
        
        // Check if account is locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['success' => false, 'error' => 'Account temporarily locked'];
        }
        
        // Check if account is active
        if (!$user['is_active']) {
            return ['success' => false, 'error' => 'Account is inactive'];
        }
        
        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            $this->handleFailedLogin($username, $user['id']);
            return ['success' => false, 'error' => 'Invalid credentials'];
        }
        
        // Successful login
        $this->handleSuccessfulLogin($user['id']);
        
        // Create secure session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
        
        // Regenerate session ID
        session_regenerate_id(true);
        
        Security::logEvent('USER_LOGIN_SUCCESS', [
            'user_id' => $user['id'],
            'username' => $user['username']
        ]);
        
        return ['success' => true, 'user' => $user];
    }
    
    /**
     * Handle failed login
     */
    private function handleFailedLogin($username, $userId = null) {
        if ($userId) {
            $user = $this->db->select('users', ['id' => $userId]);
            if (!empty($user)) {
                $user = $user[0];
                $attempts = $user['login_attempts'] + 1;
                
                // Lock account after 5 failed attempts
                if ($attempts >= 5) {
                    $lockedUntil = date('Y-m-d H:i:s', strtotime('+30 minutes'));
                    $this->db->update('users', [
                        'login_attempts' => $attempts,
                        'locked_until' => $lockedUntil
                    ], ['id' => $userId]);
                    
                    Security::logEvent('ACCOUNT_LOCKED', [
                        'user_id' => $userId,
                        'username' => $username,
                        'attempts' => $attempts
                    ]);
                } else {
                    $this->db->update('users', ['login_attempts' => $attempts], ['id' => $userId]);
                }
            }
        }
        
        Security::logEvent('USER_LOGIN_FAILED', ['username' => $username]);
    }
    
    /**
     * Handle successful login
     */
    private function handleSuccessfulLogin($userId) {
        $this->db->update('users', [
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login' => date('Y-m-d H:i:s')
        ], ['id' => $userId]);
    }
    
    /**
     * Logout user
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            Security::logEvent('USER_LOGOUT', [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username']
            ]);
        }
        
        // Destroy session
        $_SESSION = [];
        session_destroy();
        
        // Clear session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']) && 
               isset($_SESSION['ip']) && 
               $_SESSION['ip'] === $_SERVER['REMOTE_ADDR'] &&
               (time() - $_SESSION['login_time']) < 3600; // 1 hour session
    }
    
    /**
     * Check if user is admin
     */
    public function isAdmin() {
        return $this->isLoggedIn() && 
               isset($_SESSION['role']) && 
               $_SESSION['role'] === 'admin';
    }
    
    /**
     * Require authentication
     */
    public function requireAuth() {
        if (!$this->isLoggedIn()) {
            Security::logEvent('UNAUTHORIZED_ACCESS_ATTEMPT', [
                'requested_page' => $_SERVER['REQUEST_URI']
            ]);
            header('HTTP/1.0 403 Forbidden');
            die('Access denied. Please login.');
        }
    }
    
    /**
     * Require admin role
     */
    public function requireAdmin() {
        $this->requireAuth();
        if (!$this->isAdmin()) {
            Security::logEvent('ADMIN_ACCESS_DENIED', [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'requested_page' => $_SERVER['REQUEST_URI']
            ]);
            header('HTTP/1.0 403 Forbidden');
            die('Admin access required.');
        }
    }
    
    /**
     * Get current user
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $user = $this->db->select('users', ['id' => $_SESSION['user_id']]);
        return !empty($user) ? $user[0] : null;
    }
    
    /**
     * Change password
     */
    public function changePassword($currentPassword, $newPassword) {
        $this->requireAuth();
        
        $user = $this->getCurrentUser();
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Current password is incorrect'];
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            return ['success' => false, 'error' => 'Password must be at least 8 characters'];
        }
        
        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_ARGON2ID);
        $this->db->update('users', ['password_hash' => $newPasswordHash], ['id' => $user['id']]);
        
        Security::logEvent('PASSWORD_CHANGED', ['user_id' => $user['id']]);
        
        return ['success' => true];
    }
}
?>
