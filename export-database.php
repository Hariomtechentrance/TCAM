<?php
/**
 * Export TCAM Database
 * Exports all registration data for migration to GoDaddy
 */

require_once 'security-config.php';
require_once 'secure-database.php';

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="tcam_data.sql"');

try {
    $db = SecureDatabase::getInstance();
    
    // Get all registrations
    $registrations = $db->execute("SELECT * FROM registrations ORDER BY created_at DESC")->fetchAll();
    
    echo "-- TCAM Database Export\n";
    echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
    echo "-- Total registrations: " . count($registrations) . "\n\n";
    
    foreach ($registrations as $reg) {
        echo "INSERT INTO registrations (reg_id, name, mobile, email, city, state, date_of_birth, document_type, document_number, address, parent_name, emergency_contact, blood_group, joined, previous_tournaments, photo, created_at) VALUES (";
        echo "'" . $reg['reg_id'] . "', ";
        echo "'" . addslashes($reg['name']) . "', ";
        echo "'" . addslashes($reg['mobile']) . "', ";
        echo "'" . addslashes($reg['email']) . "', ";
        echo "'" . addslashes($reg['city']) . "', ";
        echo "'" . addslashes($reg['state']) . "', ";
        echo "'" . ($reg['date_of_birth'] ?? 'NULL') . "', ";
        echo "'" . addslashes($reg['document_type']) . "', ";
        echo "'" . addslashes($reg['document_number']) . "', ";
        echo "'" . addslashes($reg['address']) . "', ";
        echo "'" . addslashes($reg['parent_name']) . "', ";
        echo "'" . addslashes($reg['emergency_contact']) . "', ";
        echo "'" . addslashes($reg['blood_group']) . "', ";
        echo "'" . addslashes($reg['joined']) . "', ";
        echo "'" . addslashes($reg['previous_tournaments']) . "', ";
        echo "'" . addslashes($reg['photo']) . "', ";
        echo "'" . $reg['created_at'] . "');\n";
    }
    
} catch (Exception $e) {
    echo "-- Error: " . $e->getMessage() . "\n";
}
?>
