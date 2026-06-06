<?php
/**
 * Secure Contact Form Handler
 * Prevents XSS, CSRF, and implements rate limiting
 */

require_once 'security-config.php';

// Rate limiting
Security::rateLimit(5, 300); // 5 submissions per 5 minutes

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        Security::logEvent('CSRF_TOKEN_INVALID', ['form' => 'contact']);
        http_response_code(403);
        echo "Invalid request. Please refresh the page and try again.";
        exit;
    }
    
    // Sanitize and validate inputs
    $name = Security::sanitizeInput($_POST['name'] ?? '');
    $email = Security::sanitizeInput($_POST['email'] ?? '');
    $message = Security::sanitizeInput($_POST['message'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo "All fields are required.";
        exit;
    }
    
    // Validate email
    if (!Security::validateEmail($email)) {
        http_response_code(400);
        echo "Please enter a valid email address.";
        exit;
    }
    
    // Validate name
    if (!Security::validateName($name)) {
        http_response_code(400);
        echo "Please enter a valid name (letters only, 2-50 characters).";
        exit;
    }
    
    // Validate message length
    if (strlen($message) < 10 || strlen($message) > 1000) {
        http_response_code(400);
        echo "Message must be between 10 and 1000 characters.";
        exit;
    }
    
    // Check for suspicious content
    $suspiciousPatterns = [
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/javascript:/i',
        '/on\w+\s*=/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $message)) {
            Security::logEvent('SUSPICIOUS_CONTENT', [
                'form' => 'contact',
                'name' => $name,
                'email' => $email,
                'message' => substr($message, 0, 100)
            ]);
            http_response_code(400);
            echo "Invalid message content.";
            exit;
        }
    }
    
    // Save to file securely
    $date = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'];
    $entry = "Date: $date | IP: $ip | Name: $name | Email: $email | Message: $message" . PHP_EOL;
    
    $file = __DIR__ . '/contacts.txt';
    
    // Ensure file exists and has proper permissions
    if (!file_exists($file)) {
        touch($file);
        chmod($file, 0600);
    }
    
    if (file_put_contents($file, $entry, FILE_APPEND | LOCK_EX) !== false) {
        // Log successful submission
        Security::logEvent('CONTACT_FORM_SUBMITTED', [
            'name' => $name,
            'email' => $email
        ]);
        
        echo "success";
    } else {
        Security::logEvent('CONTACT_FORM_ERROR', ['error' => 'Failed to write to file']);
        http_response_code(500);
        echo "Failed to save data. Please try again.";
    }
    exit;
}

http_response_code(400);
echo "Invalid request method";
?>
