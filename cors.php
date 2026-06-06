<?php
/**
 * CORS headers for public API endpoints.
 * Set CORS_ALLOWED_ORIGINS env var to a comma-separated list of allowed origins,
 * or leave empty / set to "*" to allow all origins.
 * Example: CORS_ALLOWED_ORIGINS=https://tcam-frontend.onrender.com
 */
$allowedEnv = getenv('CORS_ALLOWED_ORIGINS');
if ($allowedEnv === false || trim($allowedEnv) === '') {
    $allowedEnv = '*';
}
$origins = array_filter(array_map('trim', explode(',', $allowedEnv)));

$requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array('*', $origins, true)) {
    header('Access-Control-Allow-Origin: *');
} elseif ($requestOrigin && in_array($requestOrigin, $origins, true)) {
    header('Access-Control-Allow-Origin: ' . $requestOrigin);
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
