<?php
/**
 * Admin Auth Guard
 * Include at top of every admin page to enforce login
 */
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
