<?php
/**
 * Update Database Schema for Admin Panel
 * Adds missing fields for admin functionality
 */

require_once 'security-config.php';
require_once 'secure-database.php';

echo "<h2>🔄 Updating Database Schema for Admin Panel</h2>";

try {
    $db = SecureDatabase::getInstance();
    
    // Add status field to registrations table
    try {
        $db->execute("ALTER TABLE registrations ADD COLUMN status VARCHAR(20) DEFAULT 'active'");
        echo "<p>✅ Added status column to registrations table</p>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'duplicate column name') === false) {
            echo "<p>❌ Error adding status column: " . $e->getMessage() . "</p>";
        } else {
            echo "<p>✅ Status column already exists</p>";
        }
    }
    
    // Create admin_logs table for audit trail
    try {
        $db->execute("
            CREATE TABLE IF NOT EXISTS admin_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                admin_id VARCHAR(50),
                action VARCHAR(50) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        echo "<p>✅ Admin logs table created</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error creating admin logs: " . $e->getMessage() . "</p>";
    }
    
    // Create event_registrations table if not exists
    try {
        $db->execute("
            CREATE TABLE IF NOT EXISTS event_registrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                registration_id INTEGER NOT NULL,
                event_name VARCHAR(100) NOT NULL,
                status VARCHAR(20) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (registration_id) REFERENCES registrations(id)
            )
        ");
        echo "<p>✅ Event registrations table created</p>";
    } catch (Exception $e) {
        echo "<p>❌ Error creating event registrations: " . $e->getMessage() . "</p>";
    }
    
    // Update existing registrations to have status if null
    try {
        $db->execute("UPDATE registrations SET status = 'active' WHERE status IS NULL");
        $updated = $db->execute("SELECT changes()")->fetch()['changes'];
        if ($updated > 0) {
            echo "<p>✅ Updated $updated registrations with default status</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Error updating statuses: " . $e->getMessage() . "</p>";
    }
    
    // Create indexes for better performance
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_registrations_status ON registrations(status)",
        "CREATE INDEX IF NOT EXISTS idx_registrations_created ON registrations(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_admin_logs_created ON admin_logs(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_event_registrations_reg_id ON event_registrations(registration_id)"
    ];
    
    foreach ($indexes as $index) {
        try {
            $db->execute($index);
            echo "<p>✅ Created database index</p>";
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "<p>❌ Error creating index: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h2>✅ Database Schema Updated Successfully!</h2>";
    echo "<p>Your admin panel now has full functionality.</p>";
    echo "<p><a href='admin-dashboard.php'>🎯 Go to Admin Dashboard</a></p>";
    echo "<p><a href='login-secure.php'>🔐 Go to Login</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
