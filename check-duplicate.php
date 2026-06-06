<?php
/**
 * Check for duplicate registration (hints for UI — server enforces rules in save-id-registration-enhanced.php)
 */

require_once 'cors.php';
header('Content-Type: application/json; charset=UTF-8');

require_once 'security-config.php';
require_once 'secure-database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

$mobile = Security::sanitizeInput($_POST['mobile'] ?? '');
$document_type = Security::sanitizeInput($_POST['document_type'] ?? '');
$document_number = Security::sanitizeInput($_POST['document_number'] ?? '');

if (empty($mobile) || empty($document_number) || empty($document_type)) {
    echo json_encode(['status' => 'success', 'duplicate' => false]);
    exit;
}

try {
    $db = SecureDatabase::getInstance();
    $norm = Security::normalizeDocumentNumber($document_type, $document_number);

    $byNorm = $db->execute(
        'SELECT * FROM registrations WHERE document_number_normalized = ? LIMIT 1',
        [$norm]
    )->fetchAll();

    if (!empty($byNorm)) {
        $reg = $byNorm[0];
        if ($reg['mobile'] === $mobile) {
            echo json_encode([
                'status' => 'success',
                'duplicate' => false,
                'same_profile' => true,
                'existing_id' => $reg['reg_id'],
                'message' => 'This document and mobile match your existing TCAM ID. Submitting will only add the new event — no duplicate profile.'
            ]);
            exit;
        }
        echo json_encode([
            'status' => 'duplicate',
            'duplicate' => true,
            'duplicate_type' => 'document number',
            'existing_id' => $reg['reg_id'],
            'message' => 'This document is registered to a different mobile number.'
        ]);
        exit;
    }

    if (Security::validatePhone($mobile)) {
        $existingMobile = $db->select('registrations', ['mobile' => $mobile]);
        if (!empty($existingMobile)) {
            echo json_encode([
                'status' => 'duplicate',
                'duplicate' => true,
                'duplicate_type' => 'mobile number',
                'existing_id' => $existingMobile[0]['reg_id'],
                'message' => 'This mobile is already used with another document. Use the same ID as your first registration.'
            ]);
            exit;
        }
    }

    echo json_encode(['status' => 'success', 'duplicate' => false, 'same_profile' => false]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error']);
}
