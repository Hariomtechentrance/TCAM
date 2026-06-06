<?php
/**
 * Returns a stateless HMAC CSRF token — works cross-origin without a session.
 */
require_once __DIR__ . '/cors.php';
header('Content-Type: application/json; charset=UTF-8');
require_once __DIR__ . '/security-config.php';

echo json_encode(['csrf_token' => Security::generateCSRFToken()]);
