<?php
/**
 * Secure Database Connection for TCAM
 * Prevents SQL injection and provides secure database operations
 */

require_once 'security-config.php';

class SecureDatabase {
    private $pdo;
    private static $instance = null;
    
    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->connect();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Secure database connection
     */
    /**
     * Apply lightweight migrations (SQLite) for tokens, events, normalized IDs
     */
    private function ensureSchema() {
        $alters = [
            'ALTER TABLE registrations ADD COLUMN document_number_normalized VARCHAR(64) DEFAULT NULL',
            'ALTER TABLE registrations ADD COLUMN download_token VARCHAR(64) DEFAULT NULL',
        ];
        foreach ($alters as $sql) {
            try {
                $this->pdo->exec($sql);
            } catch (PDOException $e) {
                // Column may already exist
            }
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS event_registrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            registration_id INTEGER NOT NULL,
            event_name TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (registration_id) REFERENCES registrations(id)
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS coach_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            district TEXT NOT NULL,
            mobile TEXT DEFAULT '',
            name TEXT DEFAULT '',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS coach_reports (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            coach_id INTEGER NOT NULL,
            district TEXT NOT NULL,
            event_name TEXT,
            report_text TEXT,
            selected_registrations TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (coach_id) REFERENCES coach_users(id)
        )");

        // Media management: images used across site (gallery, banners, hero sections)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS media_images (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            filename TEXT NOT NULL,
            filepath TEXT NOT NULL,
            section TEXT DEFAULT 'gallery', -- logical section e.g. hero, gallery, banner
            alt_text TEXT DEFAULT '',
            enabled INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        // Hero / banner entries (can reference media_images or have custom images)
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS hero_banners (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT DEFAULT '',
            subtitle TEXT DEFAULT '',
            image_id INTEGER DEFAULT NULL,
            image_path TEXT DEFAULT '',
            link TEXT DEFAULT '',
            enabled INTEGER DEFAULT 1,
            position INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (image_id) REFERENCES media_images(id)
        )");

        // Districts table used for coach registration dropdowns
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS districts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT UNIQUE NOT NULL
        )");

        // Seed some Maharashtra districts if table is empty
        try {
            $cnt = $this->pdo->query("SELECT COUNT(*) as c FROM districts")->fetchColumn();
            if ((int)$cnt === 0) {
                $districts = [
                    'Ahmednagar','Akola','Amravati','Aurangabad','Beed','Bhandara','Buldhana','Chandrapur',
                    'Dhule','Gadchiroli','Gondia','Hingoli','Jalgaon','Jalna','Kolhapur','Latur',
                    'Mumbai City','Mumbai Suburban','Nagpur','Nanded','Nandurbar','Nashik','Osmanabad','Palghar',
                    'Parbhani','Pune','Raigad','Ratnagiri','Sangli','Satara','Sindhudurg','Solapur',
                    'Thane','Wardha','Washim','Yavatmal'
                ];
                $ins = $this->pdo->prepare('INSERT OR IGNORE INTO districts (name) VALUES (?)');
                foreach ($districts as $d) { $ins->execute([$d]); }
            }
        } catch (PDOException $e) {
            // ignore seed failures
        }

        try { $this->pdo->exec("ALTER TABLE coach_users ADD COLUMN mobile TEXT DEFAULT ''"); } catch (PDOException $e) {}
        try { $this->pdo->exec("ALTER TABLE coach_users ADD COLUMN district TEXT NOT NULL DEFAULT ''"); } catch (PDOException $e) {}
        try { $this->pdo->exec("ALTER TABLE registrations ADD COLUMN district TEXT DEFAULT ''"); } catch (PDOException $e) {}

        $stmt = $this->pdo->query("SELECT id, document_type, document_number FROM registrations WHERE document_number IS NOT NULL AND document_number != '' AND (document_number_normalized IS NULL OR document_number_normalized = '')");
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $norm = Security::normalizeDocumentNumber($row['document_type'] ?? '', $row['document_number']);
                $u = $this->pdo->prepare('UPDATE registrations SET document_number_normalized = ? WHERE id = ?');
                $u->execute([$norm, $row['id']]);
            }
        }

        $tokStmt = $this->pdo->query("SELECT id FROM registrations WHERE download_token IS NULL OR download_token = ''");
        if ($tokStmt) {
            while ($row = $tokStmt->fetch(PDO::FETCH_ASSOC)) {
                $token = bin2hex(random_bytes(32));
                $u = $this->pdo->prepare('UPDATE registrations SET download_token = ? WHERE id = ?');
                $u->execute([$token, $row['id']]);
            }
        }
    }

    private function connect() {
        try {
            // SQLite connection (override path via TCAM_DB_PATH for Render persistent disk)
            $dbPath = getenv('TCAM_DB_PATH') ?: __DIR__ . '/tcam_bookings.db';
            
            $this->pdo = new PDO('sqlite:' . $dbPath);
            
            // Set PDO attributes for security
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
            // Enable foreign key constraints
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            
            // Set secure permissions
            chmod($dbPath, 0600);

            $this->ensureSchema();
            
        } catch (PDOException $e) {
            Security::logEvent('DATABASE_CONNECTION_ERROR', ['error' => $e->getMessage()]);
            die('Database connection failed. Please try again later.');
        }
    }
    
    /**
     * Execute prepared statement securely
     */
    public function execute($query, $params = []) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            Security::logEvent('DATABASE_QUERY_ERROR', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Database operation failed');
        }
    }
    
    /**
     * Insert data securely
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $query = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute(array_values($data));
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            Security::logEvent('DATABASE_INSERT_ERROR', [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to insert data');
        }
    }
    
    /**
     * Select data securely
     */
    public function select($table, $conditions = [], $columns = '*', $limit = null) {
        $query = "SELECT $columns FROM $table";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClauses = [];
            foreach ($conditions as $column => $value) {
                $whereClauses[] = "$column = ?";
                $params[] = $value;
            }
            $query .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        if ($limit) {
            $query .= " LIMIT ?";
            $params[] = $limit;
        }
        
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Security::logEvent('DATABASE_SELECT_ERROR', [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Update data securely
     */
    public function update($table, $data, $conditions) {
        $setClauses = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setClauses[] = "$column = ?";
            $params[] = $value;
        }
        
        $whereClauses = [];
        foreach ($conditions as $column => $value) {
            $whereClauses[] = "$column = ?";
            $params[] = $value;
        }
        
        $query = "UPDATE $table SET " . implode(', ', $setClauses) . " WHERE " . implode(' AND ', $whereClauses);
        
        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            Security::logEvent('DATABASE_UPDATE_ERROR', [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to update data');
        }
    }
    
    /**
     * Delete data securely
     */
    public function delete($table, $conditions) {
        $whereClauses = [];
        $params = [];
        
        foreach ($conditions as $column => $value) {
            $whereClauses[] = "$column = ?";
            $params[] = $value;
        }
        
        $query = "DELETE FROM $table WHERE " . implode(' AND ', $whereClauses);
        
        try {
            $stmt = $this->pdo->prepare($query);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            Security::logEvent('DATABASE_DELETE_ERROR', [
                'table' => $table,
                'error' => $e->getMessage()
            ]);
            throw new Exception('Failed to delete data');
        }
    }
    
    /**
     * Get PDO instance for custom queries
     */
    public function getPDO() {
        return $this->pdo;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->pdo->rollback();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
