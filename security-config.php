<?php
/**
 * TCAM Security Configuration
 * Comprehensive security protection against common attacks
 */

// Prevent direct access
if (!defined('SECURITY_LOADED')) {
    define('SECURITY_LOADED', true);
    
    // Security Headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\'; connect-src \'self\'; frame-ancestors \'none\'; form-action \'self\';');
    
    // Hide PHP version
    header('X-Powered-By: TCAM Security');
    
    // Session Security (Secure cookie only over HTTPS so local HTTP still works)
    ini_set('session.cookie_httponly', 1);
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    ini_set('session.cookie_secure', $https ? '1' : '0');
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');

    if ($https) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    
    // Error Reporting (disable in production)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if ($host !== '' && !in_array($host, ['localhost', '127.0.0.1'], true)) {
        error_reporting(0);
        ini_set('display_errors', 0);
    }
}

/**
 * Security Class
 */
class Security {
    
    /**
     * Generate a stateless HMAC CSRF token (works cross-origin — no session required).
     * The token rotates every hour and the previous hour's token is also accepted.
     */
    public static function generateCSRFToken() {
        $secret = getenv('CSRF_SECRET') ?: 'tcam-default-csrf-secret-change-in-production';
        $hour = (int) floor(time() / 3600);
        return hash_hmac('sha256', 'tcam:' . $hour, $secret);
    }

    /**
     * Validate a CSRF token (current or previous hour accepted for clock-skew tolerance).
     */
    public static function validateCSRFToken($token) {
        if (!$token) return false;
        $secret = getenv('CSRF_SECRET') ?: 'tcam-default-csrf-secret-change-in-production';
        $hour = (int) floor(time() / 3600);
        return hash_equals(hash_hmac('sha256', 'tcam:' . $hour, $secret), $token)
            || hash_equals(hash_hmac('sha256', 'tcam:' . ($hour - 1), $secret), $token);
    }
    
    /**
     * Sanitize Input
     */
    public static function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitizeInput'], $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate Email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate Phone Number (Indian format)
     */
    public static function validatePhone($phone) {
        return preg_match('/^[6-9]\d{9}$/', $phone);
    }
    
    /**
     * Validate Name
     */
    public static function validateName($name) {
        return preg_match('/^[a-zA-Z\s\.]{2,50}$/', $name);
    }

    /**
     * Normalize ID document numbers for storage and lookup (Aadhar / PAN / DL / etc.)
     */
    public static function normalizeDocumentNumber($documentType, $documentNumber) {
        $raw = trim((string) $documentNumber);
        $t = strtolower((string) $documentType);
        if ($t === 'aadhar') {
            $digits = preg_replace('/\D/', '', $raw);
            return strlen($digits) === 12 ? $digits : strtoupper(preg_replace('/\s+/', '', $raw));
        }
        if ($t === 'pan') {
            return strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $raw), 0, 10));
        }
        return strtoupper(preg_replace('/\s+/', '', $raw));
    }

    /**
     * Mask document numbers for audit logs (reduces PII in search_logs)
     */
    public static function maskForAuditLog($searchType, $searchTerm) {
        if ($searchType === 'document' && strlen($searchTerm) > 4) {
            return '***' . substr($searchTerm, -4);
        }
        if ($searchType === 'mobile' && strlen($searchTerm) >= 10) {
            return substr($searchTerm, 0, 2) . '******' . substr($searchTerm, -2);
        }
        return $searchType === 'name' ? '[name]' : $searchTerm;
    }
    
    /**
     * Rate Limiting
     */
    public static function rateLimit($limit = 10, $window = 60) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $key = 'rate_limit_' . md5($ip);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 0, 'start' => time()];
        }
        
        $data = $_SESSION[$key];
        
        if (time() - $data['start'] > $window) {
            $_SESSION[$key] = ['count' => 1, 'start' => time()];
            return true;
        }
        
        if ($data['count'] >= $limit) {
            http_response_code(429);
            die('Too many requests. Please try again later.');
        }
        
        $_SESSION[$key]['count']++;
        return true;
    }
    
    /**
     * Log Security Events
     */
    public static function logEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'event' => $event,
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/security.log';
        file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Block Suspicious IPs
     */
    public static function blockSuspiciousIP($ip) {
        $blockFile = __DIR__ . '/blocked_ips.txt';
        $blockedIPs = file_exists($blockFile) ? file($blockFile, FILE_IGNORE_NEW_LINES) : [];
        
        if (!in_array($ip, $blockedIPs)) {
            file_put_contents($blockFile, $ip . "\n", FILE_APPEND | LOCK_EX);
        }
    }
    
    /**
     * Check if IP is blocked
     */
    public static function isIPBlocked($ip) {
        $blockFile = __DIR__ . '/blocked_ips.txt';
        if (!file_exists($blockFile)) return false;
        
        $blockedIPs = file($blockFile, FILE_IGNORE_NEW_LINES);
        return in_array($ip, $blockedIPs);
    }
    
    /**
     * Validate File Upload
     */
    public static function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
        // Check file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'error' => 'File too large'];
        }
        
        // Check file type
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            return ['success' => false, 'error' => 'Invalid file type'];
        }
        
        // Check MIME type
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf'
        ];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $allowedMimes)) {
            return ['success' => false, 'error' => 'Invalid MIME type'];
        }
        
        return ['success' => true];
    }
    
    /**
     * Generate Secure Filename
     */
    public static function generateSecureFilename($originalName) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        return bin2hex(random_bytes(16)) . '.' . $extension;
    }
}

// Initialize session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for blocked IPs
if (Security::isIPBlocked($_SERVER['REMOTE_ADDR'])) {
    http_response_code(403);
    die('Access denied');
}
?>
