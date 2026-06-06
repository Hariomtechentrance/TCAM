<?php
// TCAM Configuration for Production Deployment
// This file contains environment-specific settings

// Environment detection
$isProduction = isset($_SERVER['HTTP_HOST']) && !in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1']);

// Database configuration (override via TCAM_DB_PATH env var for Render disk)
define('DB_PATH', getenv('TCAM_DB_PATH') ?: __DIR__ . '/tcam_bookings.db');

// Upload directory configuration (override via TCAM_UPLOAD_DIR env var for Render disk)
define('UPLOAD_DIR', getenv('TCAM_UPLOAD_DIR') ?: __DIR__ . '/uploads');
define('UPLOAD_URL', $isProduction ? '/uploads/' : 'uploads/');

// File size limits (in bytes)
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('MAX_PHOTO_SIZE', 2 * 1024 * 1024); // 2MB

// Allowed file types
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
define('ALLOWED_DOCUMENT_TYPES', ['pdf', 'doc', 'docx']);

// Security settings
// Change these credentials to your desired admin username and password
define('ADMIN_USERNAME', 'tcam_admin');
define('ADMIN_PASSWORD_HASH', password_hash('YourSecurePassword123!', PASSWORD_DEFAULT));

// To change credentials:
// 1. Replace 'tcam_admin' with your desired username
// 2. Replace 'YourSecurePassword123!' with your desired password
// 3. The password_hash() function will automatically encrypt your password

// Email configuration (for contact forms)
define('CONTACT_EMAIL', 'info@tcam.in'); // Update with your actual email
define('FROM_EMAIL', 'noreply@tcam.in'); // Update with your domain email

// Site configuration
define('SITE_NAME', 'Tennis Cricket Association of Maharashtra');
define('SITE_URL', $isProduction ? 'https://tcam.in' : 'http://localhost/TCAM');

// Error reporting (disable in production)
if ($isProduction) {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(0);
} else {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
}

// Create uploads directory if it doesn't exist
if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Database initialization — uses PDO so no SQLite3 extension required
function initializeDatabase() {
    try {
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE IF NOT EXISTS bookings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            bookingId TEXT UNIQUE,
            name TEXT NOT NULL,
            dob TEXT DEFAULT \'\',
            district TEXT DEFAULT \'\',
            email TEXT DEFAULT \'\',
            phone TEXT NOT NULL,
            proof TEXT DEFAULT \'\',
            proof_file TEXT DEFAULT \'\',
            photo TEXT DEFAULT \'\',
            message TEXT DEFAULT \'\',
            aadhar_number TEXT DEFAULT \'\',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
        $db->exec('CREATE TABLE IF NOT EXISTS contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            phone TEXT DEFAULT \'\',
            message TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )');
    } catch (\Throwable $e) {
        // Silently fail — DB will be created on first actual write
    }
}

// Initialize database on first load
initializeDatabase();
?>
