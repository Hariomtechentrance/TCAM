<?php
/**
 * Update Database Schema for Student Search Features
 * Adds Aadhar/document number and other required fields
 */

require_once 'security-config.php';
require_once 'secure-database.php';

try {
    $db = SecureDatabase::getInstance();
    
    // Add new columns to registrations table
    $alterQueries = [
        // Add document number field (Aadhar/other ID)
        "ALTER TABLE registrations ADD COLUMN document_number VARCHAR(20) DEFAULT NULL",
        
        // Add document type field
        "ALTER TABLE registrations ADD COLUMN document_type VARCHAR(20) DEFAULT NULL",
        
        // Add date of birth field
        "ALTER TABLE registrations ADD COLUMN date_of_birth DATE DEFAULT NULL",
        
        // Add parent/guardian name
        "ALTER TABLE registrations ADD COLUMN parent_name VARCHAR(100) DEFAULT NULL",
        
        // Add address field
        "ALTER TABLE registrations ADD COLUMN address TEXT DEFAULT NULL",
        
        // Add emergency contact
        "ALTER TABLE registrations ADD COLUMN emergency_contact VARCHAR(15) DEFAULT NULL",
        
        // Add blood group
        "ALTER TABLE registrations ADD COLUMN blood_group VARCHAR(5) DEFAULT NULL",
        
        // Add previous tournament participation
        "ALTER TABLE registrations ADD COLUMN previous_tournaments TEXT DEFAULT NULL",
        
        // Add unique constraint for document number
        "CREATE UNIQUE INDEX IF NOT EXISTS idx_document_number ON registrations(document_number)",
        
        // Add indexes for better search performance
        "CREATE INDEX IF NOT EXISTS idx_mobile_search ON registrations(mobile)",
        "CREATE INDEX IF NOT EXISTS idx_name_search ON registrations(name)",
        "CREATE INDEX IF NOT EXISTS idx_reg_id_search ON registrations(reg_id)"
    ];
    
    echo "<h2>🔄 Updating Database Schema...</h2>\n";
    
    foreach ($alterQueries as $query) {
        try {
            $db->execute($query);
            echo "✅ Success: " . substr($query, 0, 50) . "...\n<br>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                echo "⚠️  Column already exists: " . substr($query, 0, 50) . "...\n<br>";
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n<br>";
            }
        }
    }
    
    // Create search logs table
    $createSearchLogsTable = "CREATE TABLE IF NOT EXISTS search_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_type VARCHAR(20) NOT NULL,
        search_term VARCHAR(100) NOT NULL,
        search_type VARCHAR(20) NOT NULL,
        results_count INTEGER DEFAULT 0,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )";
    
    $db->execute($createSearchLogsTable);
    echo "✅ Search logs table created\n<br>";
    
    // Create download logs table
    $createDownloadLogsTable = "CREATE TABLE IF NOT EXISTS download_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        registration_id INTEGER NOT NULL,
        user_type VARCHAR(20) NOT NULL,
        download_type VARCHAR(20) NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        user_agent TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (registration_id) REFERENCES registrations(id)
    )";
    
    $db->execute($createDownloadLogsTable);
    echo "✅ Download logs table created\n<br>";
    
    echo "<h2>✅ Database Schema Updated Successfully!</h2>\n";
    echo "<p>Your database now supports student search and download features.</p>";
    echo "<p><a href='index.html'>← Back to Website</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Database Update Failed</h2>\n";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
