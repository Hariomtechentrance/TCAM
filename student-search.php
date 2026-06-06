<?php
/**
 * Student Search Portal
 * Allows students to find their previous registrations and download data
 */

require_once 'security-config.php';
require_once 'secure-database.php';

Security::rateLimit(20, 300);

function tcam_student_search_normalize_document($raw) {
    $raw = trim($raw);
    $digits = preg_replace('/\D/', '', $raw);
    if (strlen($digits) === 12) {
        return $digits;
    }
    return strtoupper(preg_replace('/\s+/', '', $raw));
}

function tcam_student_search_normalize_booking($row) {
    // Map bookings columns to the display schema used in results
    return [
        'id'              => $row['id'],
        'reg_id'          => $row['bookingId'],
        'name'            => $row['name'],
        'mobile'          => $row['phone'],
        'email'           => $row['email'] ?? '',
        'city'            => $row['district'],
        'state'           => 'Maharashtra',
        'joined'          => substr($row['created_at'], 0, 10),
        'photo'           => $row['photo'] ?? '',
        'document_number' => '',
        'download_token'  => null,
        '_table'          => 'bookings',
    ];
}

function tcam_student_search_execute($db, $searchType, $searchTerm) {
    $searchType = strtolower($searchType);
    $results = [];

    switch ($searchType) {
        case 'mobile':
            if (!Security::validatePhone($searchTerm)) {
                return ['error' => 'Please enter a valid 10-digit mobile number.', 'results' => []];
            }
            // Search main bookings table
            $stmt = $db->execute(
                'SELECT * FROM bookings WHERE phone = ? ORDER BY created_at DESC LIMIT 50',
                [$searchTerm]
            );
            foreach ($stmt->fetchAll() as $row) {
                $results[] = tcam_student_search_normalize_booking($row);
            }
            // Also check registrations table
            foreach ($db->select('registrations', ['mobile' => $searchTerm]) as $row) {
                $row['_table'] = 'registrations';
                $results[] = $row;
            }
            break;

        case 'reg_id':
            // bookingId format: TCAM-xxx or plain digits
            $stmt = $db->execute(
                'SELECT * FROM bookings WHERE bookingId = ? OR bookingId LIKE ? ORDER BY created_at DESC LIMIT 50',
                [$searchTerm, '%' . $searchTerm . '%']
            );
            foreach ($stmt->fetchAll() as $row) {
                $results[] = tcam_student_search_normalize_booking($row);
            }
            // Also check registrations table
            foreach ($db->select('registrations', ['reg_id' => $searchTerm]) as $row) {
                $row['_table'] = 'registrations';
                $results[] = $row;
            }
            break;

        case 'document':
            $norm = tcam_student_search_normalize_document($searchTerm);
            if (strlen($norm) < 8 || strlen($norm) > 20) {
                return ['error' => 'Enter a valid Aadhar number (12 digits).', 'results' => []];
            }
            $stmt = $db->execute(
                'SELECT * FROM registrations WHERE document_number_normalized = ? OR document_number = ? ORDER BY created_at DESC LIMIT 50',
                [$norm, $searchTerm]
            );
            foreach ($stmt->fetchAll() as $row) {
                $row['_table'] = 'registrations';
                $results[] = $row;
            }
            break;

        case 'name':
            if (!Security::validateName($searchTerm)) {
                return ['error' => 'Please enter a valid name.', 'results' => []];
            }
            $stmt = $db->execute(
                'SELECT * FROM bookings WHERE name LIKE ? ORDER BY created_at DESC LIMIT 25',
                ['%' . $searchTerm . '%']
            );
            foreach ($stmt->fetchAll() as $row) {
                $results[] = tcam_student_search_normalize_booking($row);
            }
            $stmt2 = $db->execute(
                'SELECT * FROM registrations WHERE name LIKE ? ORDER BY created_at DESC LIMIT 25',
                ['%' . $searchTerm . '%']
            );
            foreach ($stmt2->fetchAll() as $row) {
                $row['_table'] = 'registrations';
                $results[] = $row;
            }
            break;

        default:
            return ['error' => 'Invalid search type.', 'results' => []];
    }

    return ['results' => $results];
}

function tcam_student_search_log($db, $searchType, $searchTerm) {
    $db->insert('search_logs', [
        'user_type' => 'student',
        'search_term' => Security::maskForAuditLog($searchType, $searchTerm),
        'search_type' => $searchType,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
    ]);
}

$searchResults = [];
$error = '';
$success = '';

if (isset($_GET['download'], $_GET['id'], $_GET['token'])) {
    $registrationId = (int) $_GET['id'];
    $downloadType = Security::sanitizeInput($_GET['download']);
    $token = Security::sanitizeInput($_GET['token']);

    try {
        $db = SecureDatabase::getInstance();
        $registration = $db->select('registrations', ['id' => $registrationId]);

        if (empty($registration)) {
            http_response_code(404);
            echo 'Not found';
            exit;
        }
        $reg = $registration[0];
        if (empty($reg['download_token']) || !hash_equals($reg['download_token'], $token)) {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }

        $db->insert('download_logs', [
            'registration_id' => $registrationId,
            'user_type' => 'student',
            'download_type' => $downloadType,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);

        if ($downloadType === 'pdf') {
            generatePDFReceipt($reg);
        } elseif ($downloadType === 'json') {
            generateJSONData($reg);
        }
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json; charset=UTF-8');
    $searchType = Security::sanitizeInput($_GET['search_type'] ?? '');
    $searchTerm = Security::sanitizeInput($_GET['search_term'] ?? '');
    if ($searchTerm === '' || $searchType === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing search parameters']);
        exit;
    }
    try {
        $db = SecureDatabase::getInstance();
        tcam_student_search_log($db, $searchType, $searchTerm);
        $out = tcam_student_search_execute($db, $searchType, $searchTerm);
        if (!empty($out['error'])) {
            echo json_encode(['status' => 'error', 'message' => $out['error']]);
            exit;
        }
        echo json_encode([
            'status' => 'success',
            'results' => $out['results'],
            'count' => count($out['results'])
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Search failed. Please try again.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_type'])) {
    header('Content-Type: application/json; charset=UTF-8');
    if (!empty($_POST['csrf_token']) && !Security::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }
    $searchType = Security::sanitizeInput($_POST['search_type'] ?? '');
    $searchTerm = Security::sanitizeInput($_POST['search_term'] ?? '');
    if ($searchTerm === '') {
        echo json_encode(['status' => 'error', 'message' => 'Please enter search details']);
        exit;
    }
    try {
        $db = SecureDatabase::getInstance();
        tcam_student_search_log($db, $searchType, $searchTerm);
        $out = tcam_student_search_execute($db, $searchType, $searchTerm);
        if (!empty($out['error'])) {
            echo json_encode(['status' => 'error', 'message' => $out['error']]);
            exit;
        }
        echo json_encode([
            'status' => 'success',
            'results' => $out['results'],
            'count' => count($out['results'])
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Search failed. Please try again.']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && !isset($_GET['format']) && !isset($_GET['download']) && isset($_GET['search_term'], $_GET['search_type']) && $_GET['search_term'] !== '') {
    $searchType = Security::sanitizeInput($_GET['search_type'] ?? '');
    $searchTerm = Security::sanitizeInput($_GET['search_term'] ?? '');
    try {
        $db = SecureDatabase::getInstance();
        tcam_student_search_log($db, $searchType, $searchTerm);
        $out = tcam_student_search_execute($db, $searchType, $searchTerm);
        if (!empty($out['error'])) {
            $error = $out['error'];
        } else {
            $searchResults = $out['results'];
        }
    } catch (Exception $e) {
        $error = 'Search failed. Please try again.';
    }
}

// Generate PDF receipt function
function generatePDFReceipt($registration) {
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="TCAM_Receipt_' . $registration['reg_id'] . '.pdf"');
    
    $html = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .header { text-align: center; border-bottom: 2px solid #764ba2; padding-bottom: 20px; margin-bottom: 20px; }
            .content { margin: 20px 0; }
            .field { margin: 10px 0; }
            .label { font-weight: bold; color: #764ba2; }
            .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
        </style>
    </head>
    <body>
        <div class='header'>
            <h1>🏏 Tennis Cricket Association Maharashtra</h1>
            <h2>Registration Receipt</h2>
        </div>
        <div class='content'>
            <div class='field'><span class='label'>TCAM ID:</span> " . $registration['reg_id'] . "</div>
            <div class='field'><span class='label'>Name:</span> " . $registration['name'] . "</div>
            <div class='field'><span class='label'>Mobile:</span> " . $registration['mobile'] . "</div>
            <div class='field'><span class='label'>City:</span> " . $registration['city'] . "</div>
            <div class='field'><span class='label'>State:</span> " . $registration['state'] . "</div>
            <div class='field'><span class='label'>Registration Date:</span> " . $registration['joined'] . "</div>";
    
    if (!empty($registration['document_number'])) {
        $html .= "<div class='field'><span class='label'>Document Number:</span> " . $registration['document_number'] . "</div>";
    }
    
    $html .= "
        </div>
        <div class='footer'>
            <p>This is an official receipt from TCAM. Keep it safe for future reference.</p>
            <p>Generated on: " . date('Y-m-d H:i:s') . "</p>
        </div>
    </body>
    </html>";
    
    echo $html;
    exit;
}

// Generate JSON data function
function generateJSONData($registration) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="TCAM_Data_' . $registration['reg_id'] . '.json"');
    
    $data = [
        'tcam_id' => $registration['reg_id'],
        'name' => $registration['name'],
        'mobile' => $registration['mobile'],
        'city' => $registration['city'],
        'state' => $registration['state'],
        'registration_date' => $registration['joined'],
        'document_number' => $registration['document_number'] ?? '',
        'document_type' => $registration['document_type'] ?? '',
        'date_of_birth' => $registration['date_of_birth'] ?? '',
        'parent_name' => $registration['parent_name'] ?? '',
        'address' => $registration['address'] ?? '',
        'emergency_contact' => $registration['emergency_contact'] ?? '',
        'blood_group' => $registration['blood_group'] ?? '',
        'previous_tournaments' => $registration['previous_tournaments'] ?? '',
        'photo' => $registration['photo'] ?? ''
    ];
    
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Search Portal - TCAM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            text-align: center;
        }
        
        .header h1 {
            color: #764ba2;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1rem;
        }
        
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #764ba2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a3785;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(118, 75, 162, 0.3);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .results-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }
        
        .result-card {
            padding: 20px;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .result-card:last-child {
            border-bottom: none;
        }
        
        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .result-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #764ba2;
        }
        
        .result-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
        }
        
        .photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e1e5e9;
        }
        
        .result-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-info {
            background: #3498db;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .back-link {
            text-align: left;
            margin-bottom: 18px;
        }

        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 600;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .back-link a:hover {
            background: rgba(255, 255, 255, 0.35);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .result-info {
                grid-template-columns: 1fr;
            }
            
            .result-actions {
                justify-content: center;
            }
        }
    </style>
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="index.html">← Back to Website</a>
        </div>

        <div class="header">
            <h1>🔍 Student Search Portal</h1>
            <p>Find your previous registrations and download your data instantly!</p>
        </div>

        <?php if (isset($success) && $success !== ''): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error) && $error !== ''): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="search-container">
            <form method="GET" action="">
                
                <div class="form-group">
                    <label for="search_type">Search By</label>
                    <select name="search_type" id="search_type" required>
                        <option value="">Select Search Type</option>
                        <option value="mobile">Mobile Number</option>
                        <option value="reg_id">TCAM ID</option>
                        <option value="document">Document ID (Aadhar / PAN / Driving Licence)</option>
                        <option value="name">Name</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="search_term">Search Details</label>
                    <input type="text" id="search_term" name="search_term" 
                           placeholder="Enter your details..." required>
                </div>
                
                <button type="submit" class="btn btn-primary">🔍 Search Registrations</button>
            </form>
            <p style="margin-top:12px;color:#666;font-size:0.95rem;">Tip: Enter the same ID number you used on your registration form (spaces optional for Aadhar).</p>
        </div>

        <?php if (!empty($searchResults)): ?>
            <div class="results-container">
                <h2 style="color: #764ba2; margin-bottom: 20px;">Found <?php echo count($searchResults); ?> Registration(s)</h2>
                
                <?php foreach ($searchResults as $result): ?>
                    <div class="result-card">
                        <div class="result-header">
                            <div class="result-title">
                                TCAM ID: <?php echo htmlspecialchars($result['reg_id']); ?>
                            </div>
                            <?php if (!empty($result['photo'])): ?>
                                <?php $photoSrc = (strpos($result['photo'], 'uploads/') === 0) ? $result['photo'] : 'uploads/' . $result['photo']; ?>
                                <img src="<?php echo htmlspecialchars($photoSrc); ?>"
                                     alt="Photo" class="photo">
                            <?php endif; ?>
                        </div>
                        
                        <div class="result-info">
                            <div class="info-item">
                                <span class="info-label">Name:</span>
                                <span><?php echo htmlspecialchars($result['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Mobile:</span>
                                <span><?php echo htmlspecialchars($result['mobile']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">City:</span>
                                <span><?php echo htmlspecialchars($result['city']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">State:</span>
                                <span><?php echo htmlspecialchars($result['state']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Joined:</span>
                                <span><?php echo htmlspecialchars($result['joined']); ?></span>
                            </div>
                            <?php if (!empty($result['document_number'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Document:</span>
                                    <span><?php echo htmlspecialchars($result['document_number']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="result-actions">
                            <?php if (!empty($result['download_token'])): ?>
                            <?php
                            $tk  = urlencode($result['download_token']);
                            $rid = (int) $result['id'];
                            ?>
                            <a href="?download=pdf&amp;id=<?php echo $rid; ?>&amp;token=<?php echo $tk; ?>"
                               class="btn btn-success btn-small">📄 Download PDF</a>
                            <a href="?download=json&amp;id=<?php echo $rid; ?>&amp;token=<?php echo $tk; ?>"
                               class="btn btn-info btn-small">📊 Download JSON</a>
                            <a href="single-registration-enhanced.html?reuse=<?php echo $rid; ?>&amp;token=<?php echo $tk; ?>"
                               class="btn btn-danger btn-small">🔄 Update Registration</a>
                            <?php else: ?>
                            <a href="single-registration-enhanced.html"
                               class="btn btn-danger btn-small">🔄 Update Registration</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

    <footer class="site-footer">
        <div class="footer-inner">
            <div>
                <div class="footer-logo-row">
                    <img src="./images/logo.png" alt="TCAM Logo">
                    <span class="footer-brand-name">TCAM</span>
                </div>
                <p class="footer-tagline">Tennis Cricket Association of Maharashtra — Affiliated to Tennis Cricket Association of India. Nurturing champions, building futures.</p>
            </div>
            <div>
                <h4 class="footer-heading">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="managment.html">Management</a></li>
                    <li><a href="tournament.php">Tournaments</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="contact.html">Contact Us</a></li>
                </ul>
            </div>
            <div>
                <h4 class="footer-heading">Get Involved</h4>
                <ul class="footer-links">
                    <li><a href="index.html#booking">Register as Player</a></li>
                    <li><a href="single-registration-enhanced.html">Update Registration</a></li>
                    <li><a href="student-search.php">Search Registration</a></li>
                </ul>
            </div>
            <div>
                <h4 class="footer-heading">Follow Us</h4>
                <div class="footer-social">
                    <a href="https://www.facebook.com/share/173JkY8Mw4/" target="_blank" class="social-link">
                        <img src="Facebook.webp" alt="Facebook"> Facebook
                    </a>
                    <a href="https://www.instagram.com/tenniscricketmaharashtra?igsh=anphb3MyZDExdTdu" target="_blank" class="social-link">
                        <img src="Instagram.webp" alt="Instagram"> Instagram
                    </a>
                    <a href="https://youtube.com/@tenniscricketmaharashtra?si=pVYR1NoF-RY67Toy" target="_blank" class="social-link">
                        <img src="Youtube.webp" alt="YouTube"> YouTube
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2024 Tennis Cricket Association of Maharashtra (TCAM). All rights reserved.
        </div>
    </footer>
</body>
</html>
