<?php
/**
 * Get registration data for reuse (requires download token — mitigates IDOR)
 */

require_once 'cors.php';
header('Content-Type: application/json; charset=UTF-8');

require_once 'security-config.php';
require_once 'secure-database.php';

Security::rateLimit(30, 300);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$id = Security::sanitizeInput($_GET['id'] ?? '');
$token = Security::sanitizeInput($_GET['token'] ?? '');

if (empty($id) || !ctype_digit((string) $id) || $token === '') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    $registration = $db->select('registrations', ['id' => (int) $id]);

    if (empty($registration)) {
        echo json_encode(['status' => 'error', 'message' => 'Registration not found']);
        exit;
    }

    $reg = $registration[0];
    if (empty($reg['download_token']) || !hash_equals($reg['download_token'], $token)) {
        echo json_encode(['status' => 'error', 'message' => 'Access denied']);
        exit;
    }

    Security::logEvent('REGISTRATION_DATA_ACCESSED', [
        'registration_id' => $id,
        'reg_id' => $reg['reg_id'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ]);

    echo json_encode([
        'status' => 'success',
        'registration' => [
            'reg_id' => $reg['reg_id'],
            'name' => $reg['name'],
            'mobile' => $reg['mobile'],
            'email' => $reg['email'],
            'city' => $reg['city'],
            'state' => $reg['state'],
            'date_of_birth' => $reg['date_of_birth'],
            'document_type' => $reg['document_type'],
            'document_number' => $reg['document_number'],
            'address' => $reg['address'],
            'parent_name' => $reg['parent_name'],
            'emergency_contact' => $reg['emergency_contact'],
            'blood_group' => $reg['blood_group'],
            'previous_tournaments' => $reg['previous_tournaments']
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
