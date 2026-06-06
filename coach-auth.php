<?php
/**
 * TCAM Coach Auth Guard
 * Protects coach pages and enforces district login.
 */

session_start();
require_once 'security-config.php';

if (empty($_SESSION['coach_logged_in']) || $_SESSION['coach_logged_in'] !== true) {
    header('Location: coach-login.php');
    exit;
}

if (isset($_SESSION['coach_last_activity']) && (time() - $_SESSION['coach_last_activity']) > 1800) {
    session_unset();
    session_destroy();
    header('Location: coach-login.php?timeout=1');
    exit;
}

$_SESSION['coach_last_activity'] = time();
