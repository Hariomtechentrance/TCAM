<?php
/**
 * Admin API — TCAM
 * RESTful JSON API for admin AJAX operations
 */
session_start();

// Check auth
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

$dbPath = getenv('TCAM_DB_PATH') ?: __DIR__ . '/tcam_bookings.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

// Auto-migrate
$db->exec("CREATE TABLE IF NOT EXISTS tournaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    tournament_id TEXT UNIQUE,
    name TEXT NOT NULL,
    start_date TEXT, end_date TEXT, venue TEXT, city TEXT,
    state TEXT DEFAULT 'Maharashtra', participants INTEGER DEFAULT 0,
    status TEXT DEFAULT 'upcoming', winner TEXT DEFAULT '', runner_up TEXT DEFAULT '',
    organizer TEXT DEFAULT '', contact_person TEXT DEFAULT '', contact_mobile TEXT DEFAULT '',
    prize_money TEXT DEFAULT '', description TEXT DEFAULT '', image_path TEXT DEFAULT '',
    featured INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS gallery_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL, original_name TEXT, category TEXT DEFAULT 'general',
    tournament_id INTEGER DEFAULT NULL, caption TEXT DEFAULT '',
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    reg_id VARCHAR(10),
    name TEXT NOT NULL,
    mobile TEXT DEFAULT '',
    email TEXT DEFAULT '',
    city TEXT DEFAULT '',
    state TEXT DEFAULT '',
    district TEXT DEFAULT '',
    date_of_birth DATE DEFAULT '',
    dob TEXT DEFAULT '',
    document_type TEXT DEFAULT '',
    document_number TEXT DEFAULT '',
    document_number_normalized TEXT DEFAULT '',
    download_token TEXT DEFAULT '',
    address TEXT DEFAULT '',
    parent_name TEXT DEFAULT '',
    emergency_contact TEXT DEFAULT '',
    blood_group TEXT DEFAULT '',
    joined DATE DEFAULT '',
    previous_tournaments TEXT DEFAULT '',
    aadhar_number TEXT DEFAULT '',
    proof TEXT DEFAULT '',
    proof_file TEXT DEFAULT '',
    photo TEXT DEFAULT '',
    status TEXT DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
// Add any missing columns to existing registrations table on live server
try { $db->exec("ALTER TABLE registrations ADD COLUMN reg_id VARCHAR(10)"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN status TEXT DEFAULT 'active'"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN date_of_birth TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN district TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN dob TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN aadhar_number TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN proof TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN proof_file TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN photo TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN city TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN state TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN document_type TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN document_number TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN document_number_normalized TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN download_token TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN address TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN parent_name TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN emergency_contact TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN blood_group TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN joined TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE registrations ADD COLUMN previous_tournaments TEXT DEFAULT ''"); } catch (PDOException $e) {}
// Ensure bookings table has aadhar_number column (may be missing on old schema)
try { $db->exec("ALTER TABLE bookings ADD COLUMN aadhar_number TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE bookings ADD COLUMN dob TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE bookings ADD COLUMN district TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE bookings ADD COLUMN photo TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE bookings ADD COLUMN proof TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE bookings ADD COLUMN proof_file TEXT DEFAULT ''"); } catch (PDOException $e) {}
$db->exec("CREATE TABLE IF NOT EXISTS coach_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    district TEXT NOT NULL,
    mobile TEXT DEFAULT '',
    name TEXT DEFAULT '',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
try { $db->exec("ALTER TABLE coach_users ADD COLUMN mobile TEXT DEFAULT ''"); } catch (PDOException $e) {}
try { $db->exec("ALTER TABLE coach_users ADD COLUMN district TEXT NOT NULL DEFAULT ''"); } catch (PDOException $e) {}
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
$db->exec("CREATE TABLE IF NOT EXISTS event_registrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    registration_id INTEGER NOT NULL,
    event_name TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES registrations(id)
)");
$db->exec("CREATE TABLE IF NOT EXISTS contact_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, email TEXT NOT NULL, phone TEXT DEFAULT '',
    message TEXT NOT NULL, is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS media_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    filepath TEXT NOT NULL,
    section TEXT DEFAULT 'gallery',
    alt_text TEXT DEFAULT '',
    enabled INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS hero_banners (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT DEFAULT '',
    subtitle TEXT DEFAULT '',
    image_id INTEGER DEFAULT NULL,
    image_path TEXT DEFAULT '',
    link TEXT DEFAULT '',
    enabled INTEGER DEFAULT 1,
    position INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("CREATE TABLE IF NOT EXISTS districts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL UNIQUE
)");
$districtList = ['Ahmednagar','Akola','Amravati','Aurangabad','Beed','Bhandara','Buldhana','Chandrapur','Dhule','Gadchiroli','Gondia','Hingoli','Jalgaon','Jalna','Kolhapur','Latur','Mumbai City','Mumbai Suburban','Nagpur','Nanded','Nandurbar','Nashik','Osmanabad','Palghar','Parbhani','Pune','Raigad','Ratnagiri','Sangli','Satara','Sindhudurg','Solapur','Thane','Wardha','Washim','Yavatmal'];
$distStmt = $db->prepare("INSERT OR IGNORE INTO districts (name) VALUES (?)");
foreach ($districtList as $d) { $distStmt->execute([$d]); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

switch ($action) {

    // ── DASHBOARD STATS ─────────────────────
    case 'stats':
        $total = 0; $today = 0; $active = 0;
        try { $total += (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(); } catch (\Exception $e) {}
        try { $today += (int)$db->query("SELECT COUNT(*) FROM bookings WHERE DATE(created_at) = DATE('now')")->fetchColumn(); } catch (\Exception $e) {}
        try { $active += (int)$db->query("SELECT COUNT(*) FROM bookings")->fetchColumn(); } catch (\Exception $e) {}
        try { $total += (int)$db->query("SELECT COUNT(*) FROM registrations")->fetchColumn(); } catch (\Exception $e) {}
        try { $today += (int)$db->query("SELECT COUNT(*) FROM registrations WHERE DATE(created_at) = DATE('now')")->fetchColumn(); } catch (\Exception $e) {}
        try { $active += (int)$db->query("SELECT COUNT(*) FROM registrations WHERE status = 'active' OR status IS NULL OR status = ''")->fetchColumn(); } catch (\Exception $e) {}
        $tournaments = $db->query("SELECT COUNT(*) FROM tournaments")->fetchColumn();
        $gallery = $db->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();
        $upcoming = $db->query("SELECT COUNT(*) FROM tournaments WHERE status = 'upcoming'")->fetchColumn();
        $ongoing = $db->query("SELECT COUNT(*) FROM tournaments WHERE status = 'ongoing'")->fetchColumn();
        $completed = $db->query("SELECT COUNT(*) FROM tournaments WHERE status = 'completed'")->fetchColumn();
        $contacts = $db->query("SELECT COUNT(*) FROM contact_messages")->fetchColumn();
        $unread = $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
        $coachCount = $db->query("SELECT COUNT(*) FROM coach_users")->fetchColumn();
        $reportCount = $db->query("SELECT COUNT(*) FROM coach_reports")->fetchColumn();
        echo json_encode([
            'status' => 'success',
            'data' => compact('total', 'today', 'active', 'tournaments', 'gallery', 'upcoming', 'ongoing', 'completed', 'contacts', 'unread', 'coachCount', 'reportCount')
        ]);
        break;

    // ── REGISTRATIONS ────────────────────────
    case 'registrations':
        if ($method === 'GET') {
            $results = [];

            // ── Query bookings table ──────────────
            try {
                $where = []; $params = [];
                if (!empty($_GET['name']))    { $where[] = "name LIKE ?";            $params[] = '%'.$_GET['name'].'%'; }
                if (!empty($_GET['mobile']))  { $where[] = "phone LIKE ?";           $params[] = '%'.$_GET['mobile'].'%'; }
                if (!empty($_GET['reg_id']))  { $where[] = "bookingId = ?";          $params[] = $_GET['reg_id']; }
                if (!empty($_GET['email']))   { $where[] = "email LIKE ?";           $params[] = '%'.$_GET['email'].'%'; }
                if (!empty($_GET['city']))    { $where[] = "district LIKE ?";        $params[] = '%'.$_GET['city'].'%'; }
                if (!empty($_GET['dob']))     { $where[] = "dob = ?";               $params[] = $_GET['dob']; }
                if (!empty($_GET['date_from'])) { $where[] = "DATE(created_at) >= ?"; $params[] = $_GET['date_from']; }
                if (!empty($_GET['date_to']))   { $where[] = "DATE(created_at) <= ?"; $params[] = $_GET['date_to']; }
                if (!empty($_GET['document_number'])) {
                    $where[] = "(aadhar_number LIKE ? OR proof_file LIKE ?)";
                    $params[] = '%'.$_GET['document_number'].'%';
                    $params[] = '%'.$_GET['document_number'].'%';
                }
                if (!empty($_GET['document_type'])) { $where[] = "proof LIKE ?"; $params[] = '%'.$_GET['document_type'].'%'; }

                $sql = "SELECT * FROM bookings";
                if ($where) $sql .= " WHERE ".implode(" AND ", $where);
                $sql .= " ORDER BY created_at DESC";
                $stmt = $db->prepare($sql); $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    $results[] = [
                        'id'            => 'b_'.$r['id'],
                        'reg_id'        => $r['bookingId'] ?? '',
                        'name'          => $r['name'] ?? '',
                        'mobile'        => $r['phone'] ?? '',
                        'email'         => $r['email'] ?? '',
                        'city'          => $r['district'] ?? '',
                        'date_of_birth' => $r['dob'] ?? '',
                        'document_type' => $r['proof'] ?? 'aadhar',
                        'document_number'=> $r['aadhar_number'] ?? '',
                        'photo'         => $r['photo'] ?? '',
                        'proof_file'    => $r['proof_file'] ?? '',
                        'status'        => 'active',
                        'events'        => [],
                        'created_at'    => $r['created_at'] ?? '',
                        '_source'       => 'bookings'
                    ];
                }
            } catch (\Exception $e) {}

            // ── Query registrations table ─────────
            try {
                $where = []; $params = [];
                if (!empty($_GET['name']))    { $where[] = "name LIKE ?";            $params[] = '%'.$_GET['name'].'%'; }
                if (!empty($_GET['mobile']))  { $where[] = "mobile LIKE ?";          $params[] = '%'.$_GET['mobile'].'%'; }
                if (!empty($_GET['reg_id']))  { $where[] = "reg_id = ?";             $params[] = $_GET['reg_id']; }
                if (!empty($_GET['email']))   { $where[] = "email LIKE ?";           $params[] = '%'.$_GET['email'].'%'; }
                if (!empty($_GET['city']))    { $where[] = "(city LIKE ? OR district LIKE ?)"; $params[] = '%'.$_GET['city'].'%'; $params[] = '%'.$_GET['city'].'%'; }
                if (!empty($_GET['dob']))     { $where[] = "(date_of_birth = ? OR dob = ?)"; $params[] = $_GET['dob']; $params[] = $_GET['dob']; }
                if (!empty($_GET['date_from'])) { $where[] = "DATE(created_at) >= ?"; $params[] = $_GET['date_from']; }
                if (!empty($_GET['date_to']))   { $where[] = "DATE(created_at) <= ?"; $params[] = $_GET['date_to']; }
                if (!empty($_GET['document_number'])) { $where[] = "(document_number LIKE ? OR aadhar_number LIKE ?)"; $params[] = '%'.$_GET['document_number'].'%'; $params[] = '%'.$_GET['document_number'].'%'; }
                if (!empty($_GET['document_type']))   { $where[] = "(document_type LIKE ? OR proof LIKE ?)"; $params[] = '%'.$_GET['document_type'].'%'; $params[] = '%'.$_GET['document_type'].'%'; }

                $sql = "SELECT * FROM registrations";
                if ($where) $sql .= " WHERE ".implode(" AND ", $where);
                $sql .= " ORDER BY created_at DESC";
                $stmt = $db->prepare($sql); $stmt->execute($params);
                foreach ($stmt->fetchAll() as $r) {
                    $results[] = [
                        'id'            => 'r_'.$r['id'],
                        'reg_id'        => $r['reg_id'] ?? '',
                        'name'          => $r['name'] ?? '',
                        'mobile'        => $r['mobile'] ?? '',
                        'email'         => $r['email'] ?? '',
                        'city'          => $r['city'] ?? $r['district'] ?? '',
                        'date_of_birth' => $r['date_of_birth'] ?? $r['dob'] ?? '',
                        'document_type' => $r['document_type'] ?? $r['proof'] ?? 'aadhar',
                        'document_number'=> $r['document_number'] ?? $r['aadhar_number'] ?? '',
                        'photo'         => $r['photo'] ?? '',
                        'proof_file'    => $r['proof_file'] ?? $r['document_number'] ?? '',
                        'status'        => $r['status'] ?? 'active',
                        'events'        => [],
                        'created_at'    => $r['created_at'] ?? '',
                        '_source'       => 'registrations'
                    ];
                }
            } catch (\Exception $e) {}

            // Sort combined results newest first
            usort($results, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

            echo json_encode(['status' => 'success', 'data' => $results, 'count' => count($results)]);
        }
        break;

    // ── SYNC FILESYSTEM IMAGES INTO DB ─────────────────
    case 'sync_media':
        if ($method === 'POST') {
            // ensure tables exist
            $db->exec("CREATE TABLE IF NOT EXISTS media_images (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                filepath TEXT NOT NULL,
                section TEXT DEFAULT 'gallery',
                alt_text TEXT DEFAULT '',
                enabled INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $db->exec("CREATE TABLE IF NOT EXISTS gallery_images (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                filename TEXT NOT NULL,
                original_name TEXT,
                category TEXT DEFAULT 'general',
                tournament_id INTEGER DEFAULT NULL,
                caption TEXT DEFAULT '',
                uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            $scanDirs = [
                __DIR__ . '/uploads/gallery/',
                __DIR__ . '/gallery/',
                __DIR__ . '/images/',
                __DIR__ . '/bootstrap/',
                __DIR__ . '/',
                __DIR__ . '/uploads/media/',
                __DIR__ . '/uploads/tournaments/',
                __DIR__ . '/uploads/coaches/',
                __DIR__ . '/uploads/'
            ];
            $inserted = ['media'=>[], 'gallery'=>[]];
            $extPattern = '/\.(jpg|jpeg|png|gif|webp)$/i';
            foreach ($scanDirs as $d) {
                if (!is_dir($d)) continue;
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d));
                foreach ($files as $f) {
                    if (!$f->isFile()) continue;
                    $path = $f->getPathname();
                    if (!preg_match($extPattern, $path)) continue;
                    $rel = ltrim(str_replace(__DIR__, '', $path), '/');

                    // If under uploads/gallery or gallery/ treat as gallery_images
                    if (strpos($rel, 'uploads/gallery/') === 0 || strpos($rel, 'gallery/') === 0) {
                        $fname = basename($path);
                        $uploadsDir = __DIR__ . '/uploads/gallery/'; if (!is_dir($uploadsDir)) mkdir($uploadsDir,0755,true);
                        // If file is not already in uploads/gallery, copy it there so admin panel URL matches
                        if (strpos($rel, 'uploads/gallery/') !== 0) {
                            // generate safe unique filename to avoid collisions
                            $base = pathinfo($fname, PATHINFO_FILENAME);
                            $ext = pathinfo($fname, PATHINFO_EXTENSION);
                            $target = $uploadsDir . $fname;
                            $i = 1;
                            while (file_exists($target)) { $target = $uploadsDir . $base . '_' . $i . '.' . $ext; $i++; }
                            @copy($path, $target);
                            $fname = basename($target);
                        }
                        $exists = $db->prepare("SELECT COUNT(*) FROM gallery_images WHERE filename = ?");
                        $exists->execute([$fname]);
                        if ($exists->fetchColumn() == 0) {
                            $stmt = $db->prepare("INSERT INTO gallery_images (filename, original_name, category, caption) VALUES (?,?,?,?)");
                            $stmt->execute([$fname, $fname, 'general', 'Imported']);
                            $inserted['gallery'][] = $fname;
                        }
                    } else {
                        // media_images table
                        $exists = $db->prepare("SELECT COUNT(*) FROM media_images WHERE filepath = ?");
                        $exists->execute([$rel]);
                        if ($exists->fetchColumn() == 0) {
                            $fname = basename($path);
                            $section = 'media';
                            if (strpos($rel, 'tournaments/') !== false) $section = 'tournaments';
                            if (strpos($rel, 'coaches/') !== false) $section = 'coaches';
                            $stmt = $db->prepare("INSERT INTO media_images (filename, filepath, section, alt_text, enabled) VALUES (?,?,?,?,1)");
                            $stmt->execute([$fname, $rel, $section, 'Imported']);
                            $inserted['media'][] = $rel;
                        }
                    }
                }
            }
            echo json_encode(['status'=>'success','inserted'=>$inserted]);
        }
        break;

    case 'registration_detail':
        $rawId = $_GET['id'] ?? '';
        // ID format: 'b_123' = bookings table, 'r_123' = registrations table
        if (strpos($rawId, 'b_') === 0) {
            $id = (int)substr($rawId, 2);
            try {
                $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
                $stmt->execute([$id]);
                $b = $stmt->fetch();
                if (!$b) { echo json_encode(['status'=>'error','message'=>'Not found']); break; }
                echo json_encode(['status'=>'success','data'=>[
                    'id'            => $rawId,
                    'reg_id'        => $b['bookingId'] ?? '',
                    'name'          => $b['name'] ?? '',
                    'mobile'        => $b['phone'] ?? '',
                    'email'         => $b['email'] ?? '',
                    'city'          => $b['district'] ?? '',
                    'date_of_birth' => $b['dob'] ?? '',
                    'document_type' => $b['proof'] ?? 'aadhar',
                    'document_number'=> $b['aadhar_number'] ?? '',
                    'photo'         => $b['photo'] ?? '',
                    'proof_file'    => $b['proof_file'] ?? '',
                    'address'       => $b['message'] ?? '',
                    'status'        => 'active',
                    'created_at'    => $b['created_at'] ?? '',
                    'events'        => []
                ]]);
            } catch (\Exception $e) { echo json_encode(['status'=>'error','message'=>'Not found']); }
        } else {
            $id = (int)(strpos($rawId, 'r_') === 0 ? substr($rawId, 2) : $rawId);
            try {
                $stmt = $db->prepare("SELECT * FROM registrations WHERE id = ?");
                $stmt->execute([$id]);
                $reg = $stmt->fetch();
                if (!$reg) { echo json_encode(['status'=>'error','message'=>'Not found']); break; }
                $evStmt = $db->prepare("SELECT * FROM event_registrations WHERE registration_id = ? ORDER BY created_at DESC");
                $evStmt->execute([$id]);
                $reg['events'] = $evStmt->fetchAll();
                $reg['id'] = $rawId;
                echo json_encode(['status'=>'success','data'=>$reg]);
            } catch (\Exception $e) { echo json_encode(['status'=>'error','message'=>'Not found']); }
        }
        break;

    case 'update_registration':
        if ($method === 'POST') {
            $rawId = $_POST['id'] ?? '';
            if (strpos($rawId, 'b_') === 0) {
                $id = (int)substr($rawId, 2);
                $map = ['name'=>'name','mobile'=>'phone','email'=>'email','city'=>'district','date_of_birth'=>'dob'];
                $set=[]; $params=[];
                foreach ($map as $postKey => $col) {
                    if (isset($_POST[$postKey])) { $set[]="$col = ?"; $params[]=$_POST[$postKey]; }
                }
                if ($set) { $params[]=$id; $db->prepare("UPDATE bookings SET ".implode(',',$set)." WHERE id=?")->execute($params); }
            } else {
                $id = (int)(strpos($rawId,'r_')===0 ? substr($rawId,2) : $rawId);
                $fields = ['name','mobile','email','city','district','document_type','document_number','address','status','date_of_birth','dob'];
                $set=[]; $params=[];
                foreach ($fields as $f) { if (isset($_POST[$f])) { $set[]="$f = ?"; $params[]=$_POST[$f]; } }
                if ($set) { $params[]=$id; $db->prepare("UPDATE registrations SET ".implode(',',$set)." WHERE id=?")->execute($params); }
            }
            echo json_encode(['status'=>'success','message'=>'Registration updated']);
        }
        break;

    case 'delete_registration':
        if ($method === 'POST') {
            $rawId = $_POST['id'] ?? '';
            if (strpos($rawId, 'b_') === 0) {
                $id = (int)substr($rawId, 2);
                try { $db->prepare("DELETE FROM bookings WHERE id=?")->execute([$id]); } catch (\Exception $e) {}
            } else {
                $id = (int)(strpos($rawId,'r_')===0 ? substr($rawId,2) : $rawId);
                try { $db->prepare("DELETE FROM event_registrations WHERE registration_id=?")->execute([$id]); } catch (\Exception $e) {}
                try { $db->prepare("DELETE FROM registrations WHERE id=?")->execute([$id]); } catch (\Exception $e) {}
            }
            echo json_encode(['status'=>'success','message'=>'Registration deleted']);
        }
        break;

    // ── TOURNAMENTS ──────────────────────────
    case 'tournaments':
        if ($method === 'GET') {
            $rows = $db->query("SELECT * FROM tournaments ORDER BY created_at DESC")->fetchAll();
            echo json_encode(['status'=>'success','data'=>$rows]);
        }
        break;

    case 'add_tournament':
        if ($method === 'POST') {
            $tid = 'T' . str_pad((string)rand(100,9999), 4, '0', STR_PAD_LEFT);
            $stmt = $db->prepare("INSERT INTO tournaments (tournament_id, name, start_date, end_date, venue, city, state, participants, status, winner, runner_up, organizer, contact_person, contact_mobile, prize_money, description, featured) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $tid,
                $_POST['name'] ?? '',
                $_POST['start_date'] ?? '',
                $_POST['end_date'] ?? '',
                $_POST['venue'] ?? '',
                $_POST['city'] ?? '',
                $_POST['state'] ?? 'Maharashtra',
                (int)($_POST['participants'] ?? 0),
                $_POST['status'] ?? 'upcoming',
                $_POST['winner'] ?? '',
                $_POST['runner_up'] ?? '',
                $_POST['organizer'] ?? '',
                $_POST['contact_person'] ?? '',
                $_POST['contact_mobile'] ?? '',
                $_POST['prize_money'] ?? '',
                $_POST['description'] ?? '',
                (int)($_POST['featured'] ?? 0)
            ]);

            // Handle image upload
            if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $fname = 'tournament_' . $tid . '_' . time() . '.' . $ext;
                $dir = __DIR__ . '/uploads/tournaments/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname);
                $db->prepare("UPDATE tournaments SET image_path = ? WHERE tournament_id = ?")->execute(['uploads/tournaments/' . $fname, $tid]);
            }

            echo json_encode(['status'=>'success','message'=>'Tournament added','tournament_id'=>$tid]);
        }
        break;

    case 'update_tournament':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $fields = ['name','start_date','end_date','venue','city','state','participants','status','winner','runner_up','organizer','contact_person','contact_mobile','prize_money','description','featured'];
            $set = []; $params = [];
            foreach ($fields as $f) {
                if (isset($_POST[$f])) {
                    $set[] = "$f = ?";
                    $params[] = ($f === 'participants' || $f === 'featured') ? (int)$_POST[$f] : $_POST[$f];
                }
            }
            if (empty($set)) { echo json_encode(['status'=>'error','message'=>'No data']); break; }
            $params[] = $id;
            $db->prepare("UPDATE tournaments SET " . implode(', ', $set) . " WHERE id = ?")->execute($params);

            // Image update
            if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $fname = 'tournament_' . $id . '_' . time() . '.' . $ext;
                $dir = __DIR__ . '/uploads/tournaments/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                move_uploaded_file($_FILES['image']['tmp_name'], $dir . $fname);
                $db->prepare("UPDATE tournaments SET image_path = ? WHERE id = ?")->execute(['uploads/tournaments/' . $fname, $id]);
            }

            echo json_encode(['status'=>'success','message'=>'Tournament updated']);
        }
        break;

    case 'delete_tournament':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("DELETE FROM tournaments WHERE id = ?")->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Tournament deleted']);
        }
        break;

    // ── GALLERY ──────────────────────────────
    case 'gallery':
        if ($method === 'GET') {
            $sql = "SELECT * FROM gallery_images";
            $params = [];
            if (!empty($_GET['category'])) {
                $sql .= " WHERE category = ?";
                $params[] = $_GET['category'];
            }
            $sql .= " ORDER BY uploaded_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]);
        }
        break;

    case 'upload_gallery':
        if ($method === 'POST') {
            $category = $_POST['category'] ?? 'general';
            $caption = $_POST['caption'] ?? '';
            $tournament_id = !empty($_POST['tournament_id']) ? (int)$_POST['tournament_id'] : null;
            $uploaded = [];

            $dir = __DIR__ . '/uploads/gallery/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            // Handle multiple files
            $files = $_FILES['images'] ?? null;
            if ($files && is_array($files['name'])) {
                for ($i = 0; $i < count($files['name']); $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $ext = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                        if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) continue;
                        $fname = $category . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($files['tmp_name'][$i], $dir . $fname)) {
                            $stmt = $db->prepare("INSERT INTO gallery_images (filename, original_name, category, tournament_id, caption) VALUES (?,?,?,?,?)");
                            $stmt->execute([$fname, $files['name'][$i], $category, $tournament_id, $caption]);
                            $uploaded[] = $fname;
                        }
                    }
                }
            }
            echo json_encode(['status'=>'success','message'=>count($uploaded).' image(s) uploaded','files'=>$uploaded]);
        }
        break;

    case 'delete_gallery':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT filename FROM gallery_images WHERE id = ?");
            $stmt->execute([$id]);
            $img = $stmt->fetch();
            if ($img) {
                $path = __DIR__ . '/uploads/gallery/' . $img['filename'];
                if (file_exists($path)) unlink($path);
                $db->prepare("DELETE FROM gallery_images WHERE id = ?")->execute([$id]);
            }
            echo json_encode(['status'=>'success','message'=>'Image deleted']);
        }
        break;

    // ── MEDIA (Unified media_images table) ─────────────────
    case 'media_list':
        if ($method === 'GET') {
            $sql = "SELECT * FROM media_images";
            $params = [];
            if (!empty($_GET['section'])) { $sql .= " WHERE section = ?"; $params[] = $_GET['section']; }
            $sql .= " ORDER BY created_at DESC";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]);
        }
        break;

    case 'media_upload':
        if ($method === 'POST') {
            $section = $_POST['section'] ?? 'gallery';
            $alt = $_POST['alt_text'] ?? '';
            if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['status'=>'error','message'=>'No image uploaded']); break;
            }
            $f = $_FILES['image'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) { echo json_encode(['status'=>'error','message'=>'Invalid image type']); break; }
            $dir = __DIR__ . '/uploads/media/'; if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = $section . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (!move_uploaded_file($f['tmp_name'], $dir . $fname)) { echo json_encode(['status'=>'error','message'=>'Upload failed']); break; }
            $db->prepare("INSERT INTO media_images (filename, filepath, section, alt_text, enabled) VALUES (?,?,?,?,1)")->execute([$fname, 'uploads/media/' . $fname, $section, $alt]);
            echo json_encode(['status'=>'success','message'=>'Image uploaded']);
        }
        break;

    case 'media_delete':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT filepath FROM media_images WHERE id = ?"); $stmt->execute([$id]); $row = $stmt->fetch();
            if ($row) { $p = __DIR__ . '/' . ltrim($row['filepath'], '/'); if (file_exists($p)) @unlink($p); $db->prepare("DELETE FROM media_images WHERE id = ?")->execute([$id]); }
            echo json_encode(['status'=>'success','message'=>'Media deleted']);
        }
        break;

    case 'media_toggle':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $enabled = (int)($_POST['enabled'] ?? 0);
            $db->prepare("UPDATE media_images SET enabled = ? WHERE id = ?")->execute([$enabled, $id]);
            echo json_encode(['status'=>'success','message'=>'Updated']);
        }
        break;

    // ── HERO / BANNERS ──────────────────────
    case 'hero_list':
        if ($method === 'GET') {
            $rows = $db->query("SELECT hb.*, mi.filepath AS media_path, mi.filename as media_filename FROM hero_banners hb LEFT JOIN media_images mi ON mi.id = hb.image_id ORDER BY hb.position ASC, hb.created_at DESC")->fetchAll();
            echo json_encode(['status'=>'success','data'=>$rows]);
        }
        break;

    case 'hero_save':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $title = $_POST['title'] ?? '';
            $subtitle = $_POST['subtitle'] ?? '';
            $link = $_POST['link'] ?? '';
            $position = (int)($_POST['position'] ?? 0);
            $enabled = isset($_POST['enabled']) ? (int)$_POST['enabled'] : 1;

            // optional image upload
            $image_id = null; $image_path = '';
            if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $f = $_FILES['image']; $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                $dir = __DIR__ . '/uploads/media/'; if (!is_dir($dir)) mkdir($dir,0755,true);
                $fname = 'hero_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                move_uploaded_file($f['tmp_name'], $dir . $fname);
                $db->prepare("INSERT INTO media_images (filename, filepath, section, alt_text, enabled) VALUES (?,?,?,?,1)")->execute([$fname, 'uploads/media/' . $fname, 'hero', '']);
                $image_id = $db->lastInsertId();
                $image_path = 'uploads/media/' . $fname;
            }

            if ($id > 0) {
                $stmtParts = [];$params = [];
                $stmtParts[] = 'title = ?'; $params[] = $title;
                $stmtParts[] = 'subtitle = ?'; $params[] = $subtitle;
                $stmtParts[] = 'link = ?'; $params[] = $link;
                $stmtParts[] = 'position = ?'; $params[] = $position;
                $stmtParts[] = 'enabled = ?'; $params[] = $enabled;
                if ($image_id) { $stmtParts[] = 'image_id = ?'; $params[] = $image_id; $stmtParts[] = 'image_path = ?'; $params[] = $image_path; }
                $params[] = $id;
                $db->prepare("UPDATE hero_banners SET " . implode(', ', $stmtParts) . " WHERE id = ?")->execute($params);
                echo json_encode(['status'=>'success','message'=>'Hero updated']);
            } else {
                $db->prepare("INSERT INTO hero_banners (title, subtitle, image_id, image_path, link, enabled, position) VALUES (?,?,?,?,?,?,?)")->execute([$title,$subtitle,$image_id,$image_path,$link,$enabled,$position]);
                echo json_encode(['status'=>'success','message'=>'Hero created']);
            }
        }
        break;

    case 'hero_delete':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("SELECT image_path FROM hero_banners WHERE id = ?"); $stmt->execute([$id]); $row = $stmt->fetch();
            if ($row && !empty($row['image_path'])) { $p = __DIR__ . '/' . ltrim($row['image_path'],'/'); if (file_exists($p)) @unlink($p); }
            $db->prepare("DELETE FROM hero_banners WHERE id = ?")->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Hero deleted']);
        }
        break;

    // ── DISTRICTS ──────────────────────────
    case 'districts':
        if ($method === 'GET') {
            $rows = $db->query("SELECT id, name FROM districts ORDER BY name ASC")->fetchAll();
            echo json_encode(['status'=>'success','data'=>$rows]);
        }
        break;

    case 'coaches':
        if ($method === 'GET') {
            $rows = $db->query("SELECT id, username, name, district, mobile, created_at FROM coach_users ORDER BY created_at DESC")->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    case 'coach_detail':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $db->prepare("SELECT id, username, name, district, mobile, created_at FROM coach_users WHERE id = ?");
        $stmt->execute([$id]);
        $coach = $stmt->fetch();
        if (!$coach) { echo json_encode(['status' => 'error', 'message' => 'Coach not found']); break; }
        echo json_encode(['status' => 'success', 'data' => $coach]);
        break;

    case 'add_coach':
        if ($method === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $name = trim($_POST['name'] ?? '');
            $district = trim($_POST['district'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');

            if ($username === '' || $password === '' || $district === '') {
                echo json_encode(['status' => 'error', 'message' => 'Username, password, and district are required']);
                break;
            }
            $stmt = $db->prepare("SELECT COUNT(*) FROM coach_users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetchColumn() > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
                break;
            }
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO coach_users (username, password_hash, district, mobile, name) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $district, $mobile, $name]);
            echo json_encode(['status' => 'success', 'message' => 'Coach added successfully']);
        }
        break;

    case 'update_coach':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $district = trim($_POST['district'] ?? '');
            $mobile = trim($_POST['mobile'] ?? '');
            $newPassword = $_POST['password'] ?? '';

            $updates = [];
            $params = [];
            if ($name !== '') { $updates[] = 'name = ?'; $params[] = $name; }
            if ($district !== '') { $updates[] = 'district = ?'; $params[] = $district; }
            if ($mobile !== '') { $updates[] = 'mobile = ?'; $params[] = $mobile; }
            if ($newPassword !== '') { $updates[] = 'password_hash = ?'; $params[] = password_hash($newPassword, PASSWORD_DEFAULT); }
            if (empty($updates)) { echo json_encode(['status' => 'error', 'message' => 'No changes provided']); break; }
            $params[] = $id;
            $db->prepare("UPDATE coach_users SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
            echo json_encode(['status' => 'success', 'message' => 'Coach updated successfully']);
        }
        break;

    case 'delete_coach':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("DELETE FROM coach_reports WHERE coach_id = ?")->execute([$id]);
            $db->prepare("DELETE FROM coach_users WHERE id = ?")->execute([$id]);
            echo json_encode(['status' => 'success', 'message' => 'Coach deleted']);
        }
        break;

    case 'coach_reports':
        if ($method === 'GET') {
            $rows = $db->query("SELECT cr.id, cr.coach_id, cu.username AS coach_username, cu.name AS coach_name, cu.district AS coach_district, cu.mobile AS coach_mobile, cr.event_name, cr.report_text, cr.selected_registrations, cr.created_at FROM coach_reports cr LEFT JOIN coach_users cu ON cu.id = cr.coach_id ORDER BY cr.created_at DESC")->fetchAll();
            echo json_encode(['status' => 'success', 'data' => $rows]);
        }
        break;

    // ── EXPORT CSV ────────────────────────────
    case 'export_csv':
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="tcam_registrations_' . date('Y-m-d') . '.csv"');

        $where = []; $params = [];
        if (!empty($_GET['name'])) { $where[] = "name LIKE ?"; $params[] = '%'.$_GET['name'].'%'; }
        if (!empty($_GET['mobile'])) { $where[] = "mobile LIKE ?"; $params[] = '%'.$_GET['mobile'].'%'; }
        if (!empty($_GET['date_from'])) { $where[] = "DATE(created_at) >= ?"; $params[] = $_GET['date_from']; }
        if (!empty($_GET['date_to'])) { $where[] = "DATE(created_at) <= ?"; $params[] = $_GET['date_to']; }
        if (!empty($_GET['document_type'])) { $where[] = "document_type = ?"; $params[] = $_GET['document_type']; }
        if (!empty($_GET['status'])) { $where[] = "status = ?"; $params[] = $_GET['status']; }

        $sql = "SELECT * FROM registrations";
        if (!empty($where)) $sql .= " WHERE " . implode(" AND ", $where);
        $sql .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $out = fopen('php://output', 'w');
        fputcsv($out, ['TCAM ID','Name','Mobile','Email','City','State','DOB','Document Type','Document Number','Status','Registered Date']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['reg_id'] ?? '', $r['name'] ?? '', $r['mobile'] ?? '', $r['email'] ?? '',
                $r['city'] ?? '', $r['state'] ?? '', $r['date_of_birth'] ?? '',
                $r['document_type'] ?? '', $r['document_number'] ?? '',
                $r['status'] ?? 'active', $r['created_at'] ?? ''
            ]);
        }
        fclose($out);
        exit;

    // ── CHANGE PASSWORD ──────────────────────
    case 'change_password':
        if ($method === 'POST') {
            $current = $_POST['current_password'] ?? '';
            $newPass = $_POST['new_password'] ?? '';
            if (strlen($newPass) < 6) {
                echo json_encode(['status'=>'error','message'=>'Password must be at least 6 characters']);
                break;
            }
            $uid = $_SESSION['admin_user_id'] ?? 0;
            $stmt = $db->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
            $stmt->execute([$uid]);
            $user = $stmt->fetch();
            if (!$user || !password_verify($current, $user['password_hash'])) {
                echo json_encode(['status'=>'error','message'=>'Current password is incorrect']);
                break;
            }
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $db->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([$hash, $uid]);
            echo json_encode(['status'=>'success','message'=>'Password changed successfully']);
        }
        break;

    // ── CONTACT MESSAGES ─────────────────────
    case 'contacts':
        if ($method === 'GET') {
            $rows = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT 200")->fetchAll();
            echo json_encode(['status'=>'success','data'=>$rows]);
        }
        break;

    case 'mark_contact_read':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Marked as read']);
        }
        break;

    case 'delete_contact':
        if ($method === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
            echo json_encode(['status'=>'success','message'=>'Message deleted']);
        }
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Unknown action: ' . $action]);
}
