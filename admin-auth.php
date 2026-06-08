<?php
/**
 * Admin Auth Guard
 * Include at top of every admin page to enforce login
 */
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.gc_maxlifetime', 7200);  // 2 hours — must exceed the 30-min activity timeout
$_https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
ini_set('session.cookie_secure', $_https ? '1' : '0');
session_start();

function requireAdminLogin() {
    // Check if logged in
    if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: admin-login.php');
        exit;
    }
    // Session timeout: 30 min
    if (isset($_SESSION['admin_last_activity']) && (time() - $_SESSION['admin_last_activity']) > 1800) {
        session_unset();
        session_destroy();
        header('Location: admin-login.php?timeout=1');
        exit;
    }
    $_SESSION['admin_last_activity'] = time();
}

requireAdminLogin();
