<?php
/**
 * TCAM Database Migration
 * Adds tournaments, gallery_images, admin_users tables
 * Safe to run multiple times (uses IF NOT EXISTS)
 */

$dbPath = __DIR__ . '/tcam_bookings.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Tournaments table
$db->exec("CREATE TABLE IF NOT EXISTS tournaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tournament_id TEXT UNIQUE,
    name TEXT NOT NULL,
    start_date TEXT,
    end_date TEXT,
    venue TEXT,
    city TEXT,
    state TEXT DEFAULT 'Maharashtra',
    participants INTEGER DEFAULT 0,
    status TEXT DEFAULT 'upcoming',
    winner TEXT DEFAULT '',
    runner_up TEXT DEFAULT '',
    organizer TEXT DEFAULT '',
    contact_person TEXT DEFAULT '',
    contact_mobile TEXT DEFAULT '',
    prize_money TEXT DEFAULT '',
    description TEXT DEFAULT '',
    image_path TEXT DEFAULT '',
    featured INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Gallery images table
$db->exec("CREATE TABLE IF NOT EXISTS gallery_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    original_name TEXT,
    category TEXT DEFAULT 'general',
    tournament_id INTEGER DEFAULT NULL,
    caption TEXT DEFAULT '',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Admin users table
$db->exec("CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME
)");

// Coach users table
$db->exec("CREATE TABLE IF NOT EXISTS coach_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    district TEXT NOT NULL,
    name TEXT DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// Coach reports table
$db->exec("CREATE TABLE IF NOT EXISTS coach_reports (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    coach_id INTEGER NOT NULL,
    district TEXT NOT NULL,
    event_name TEXT,
    report_text TEXT,
    selected_registrations TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coach_id) REFERENCES coach_users(id)
)");

// Create default admin user if not exists
$stmt = $db->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
$stmt->execute(['admin']);
if ($stmt->fetchColumn() == 0) {
    $hash = password_hash('tcam2026', PASSWORD_DEFAULT);
    $ins = $db->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
    $ins->execute(['admin', $hash]);
}

// Add district and DOB/status columns to registrations if missing
try { $db->exec("ALTER TABLE registrations ADD COLUMN district TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN date_of_birth TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN status TEXT DEFAULT 'active'"); } catch (PDOException $e) {}

echo json_encode(['status' => 'success', 'message' => 'Migration completed successfully']);
