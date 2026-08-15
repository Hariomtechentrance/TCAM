<?php
/**
 * Auth gate for maintenance/migration scripts that must never be reachable
 * anonymously over HTTP. Include this as the very first line of any such
 * script. Running the same script from a shell (php script.php) already
 * implies server access, so the CLI SAPI is left unrestricted.
 */

if (PHP_SAPI === 'cli') {
    return;
}

require_once __DIR__ . '/../security-config.php';
session_start();

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo "Forbidden. Log in at login-production.php first.";
    exit;
}
