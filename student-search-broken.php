<?php
/**
 * Student Search Portal
 * Allows students to find their previous registrations and download data
 */

require_once 'security-config.php';
require_once 'secure-database.php';

// Rate limiting
Security::rateLimit(10, 300); // 10 searches per 5 minutes

$searchResults = [];
$error = '';
$success = '';

// Handle AJAX requests from landing page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_type']) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
        exit;
    }
    
    $searchType = Security::sanitizeInput($_POST['search_type'] ?? '');
    $searchTerm = Security::sanitizeInput($_POST['search_term'] ?? '');
    
    if (empty($searchTerm)) {
        echo json_encode(['status' => 'error', 'message' => 'Please enter search details']);
        exit;
    }
    
    try {
        $db = SecureDatabase::getInstance();
        
        // Log the search
        $db->insert('search_logs', [
            'user_type' => 'student',
            'search_term' => $searchTerm,
            'search_type' => $searchType,
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Search based on type
        switch ($searchType) {
            case 'mobile':
                if (!Security::validatePhone($searchTerm)) {
                    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 10-digit mobile number.']);
                    exit;
                }
                $searchResults = $db->select('registrations', ['mobile' => $searchTerm]);
                break;
                
            case 'reg_id':
                if (!is_numeric($searchTerm) || strlen($searchTerm) !== 4) {
                    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid 4-digit TCAM ID.']);
                    exit;
                }
                $searchResults = $db->select('registrations', ['reg_id' => $searchTerm]);
                break;
                
            case 'document':
                if (strlen($searchTerm) < 8 || strlen($searchTerm) > 20) {
                    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid document number (8-20 characters).']);
                    exit;
                }
                $searchResults = $db->select('registrations', ['document_number' => $searchTerm]);
                break;
                
            case 'name':
                if (!Security::validateName($searchTerm)) {
                    echo json_encode(['status' => 'error', 'message' => 'Please enter a valid name.']);
                    exit;
                }
                $searchResults = $db->execute(
                    "SELECT * FROM registrations WHERE name LIKE ? ORDER BY created_at DESC",
                    ["%$searchTerm%"]
                )->fetchAll();
                break;
                
            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid search type.']);
                exit;
        }
        
        echo json_encode([
            'status' => 'success', 
            'results' => $searchResults,
            'count' => count($searchResults)
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Search failed. Please try again.']);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $searchType = Security::sanitizeInput($_GET['search_type'] ?? '');
    $searchTerm = Security::sanitizeInput($_GET['search_term'] ?? '');
    
    if (empty($searchTerm)) {
        $error = "Please enter your search details.";
    } else {
            try {
                $db = SecureDatabase::getInstance();
                
                // Log the search
                $db->insert('search_logs', [
                    'user_type' => 'student',
                    'search_term' => $searchTerm,
                    'search_type' => $searchType,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                // Search based on type
                switch ($searchType) {
                    case 'mobile':
                        if (!Security::validatePhone($searchTerm)) {
                            $error = "Please enter a valid 10-digit mobile number.";
                        } else {
                            $searchResults = $db->select('registrations', ['mobile' => $searchTerm]);
                        }
                        break;
                        
                    case 'reg_id':
                        if (!is_numeric($searchTerm) || strlen($searchTerm) !== 4) {
                            $error = "Please enter a valid 4-digit TCAM ID.";
                        } else {
                            $searchResults = $db->select('registrations', ['reg_id' => $searchTerm]);
                        }
                        break;
                        
                    case 'document':
                        // Validate document number (basic validation)
                        if (strlen($searchTerm) < 8 || strlen($searchTerm) > 20) {
                            $error = "Please enter a valid document number (8-20 characters).";
                        } else {
                            $searchResults = $db->select('registrations', ['document_number' => $searchTerm]);
                        }
                        break;
                        
                    case 'name':
                        if (!Security::validateName($searchTerm)) {
                            $error = "Please enter a valid name.";
                        } else {
                            $searchResults = $db->execute(
                                "SELECT * FROM registrations WHERE name LIKE ? ORDER BY created_at DESC",
                                ["%$searchTerm%"]
                            )->fetchAll();
                        }
                        break;
                        
                    default:
                        $error = "Invalid search type.";
                }
                
                if (empty($error) && empty($searchResults)) {
                    $error = "No registrations found. Please check your details and try again.";
                }
                
            } catch (Exception $e) {
                $error = "Search failed. Please try again later.";
                Security::logEvent('STUDENT_SEARCH_ERROR', [
                    'search_type' => $searchType,
                    'search_term' => $searchTerm,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}

// Handle download requests
if (isset($_GET['download']) && isset($_GET['id'])) {
    $registrationId = (int)$_GET['id'];
    $downloadType = Security::sanitizeInput($_GET['download'] ?? '');
    
    try {
        $db = SecureDatabase::getInstance();
        
        // Get registration data
        $registration = $db->select('registrations', ['id' => $registrationId]);
        
        if (empty($registration)) {
            $error = "Registration not found.";
        } else {
            $reg = $registration[0];
            
            // Log the download
            $db->insert('download_logs', [
                'registration_id' => $registrationId,
                'user_type' => 'student',
                'download_type' => $downloadType,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
            
            if ($downloadType === 'pdf') {
                // Generate PDF receipt
                generatePDFReceipt($reg);
            } elseif ($downloadType === 'json') {
                // Generate JSON data
                generateJSONData($reg);
            } else {
                $error = "Invalid download type.";
            }
        }
    } catch (Exception $e) {
        $error = "Download failed. Please try again later.";
    }
}

// Generate PDF receipt function
function generatePDFReceipt($registration) {
    // Simple HTML to PDF conversion (you can use a library like TCPDF in production)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="TCAM_Receipt_' . $registration['reg_id'] . '.pdf"');
    
    $html = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .header { text-align: center; border-bottom: 2px solid #764ba2; padding-bottom: 20px; }
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
            <div class='field'><span class='label'>TCAM ID:</span> {$registration['reg_id']}</div>
            <div class='field'><span class='label'>Name:</span> {$registration['name']}</div>
            <div class='field'><span class='label'>Mobile:</span> {$registration['mobile']}</div>
            <div class='field'><span class='label'>City:</span> {$registration['city']}</div>
            <div class='field'><span class='label'>State:</span> {$registration['state']}</div>
            <div class='field'><span class='label'>Registration Date:</span> {$registration['joined']}</div>";
    
    if (!empty($registration['document_number'])) {
        $html .= "<div class='field'><span class='label'>Document Number:</span> {$registration['document_number']}</div>";
    }
    
    if (!empty($registration['date_of_birth'])) {
        $html .= "<div class='field'><span class='label'>Date of Birth:</span> {$registration['date_of_birth']}</div>";
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

// Generate CSRF token
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Search Portal - TCAM</title>
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
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
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
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-info {
            background: #3498db;
            color: white;
        }
        
        .btn-small {
            padding: 8px 16px;
            font-size: 14px;
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
        
        .results-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
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
        
        .info-value {
            color: #333;
        }
        
        .result-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .photo-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e1e5e9;
        }
        
        .back-link {
            text-align: center;
            margin-top: 30px;
        }
        
        .back-link a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .result-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .result-actions {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Student Search Portal</h1>
            <p>Find your previous registrations and download your data</p>
        </div>

        <?php if (isset($error) && !empty($error)): ?>
            <div class="message error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success) && !empty($success)): ?>
            <div class="message success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="search-container">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="search_type">Search By</label>
                    <select name="search_type" id="search_type" required>
                        <option value="">Select Search Type</option>
                        <option value="mobile">Mobile Number</option>
                        <option value="reg_id">TCAM ID</option>
                        <option value="document">Document Number (Aadhar/Other)</option>
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
        </div>

        <?php if (!empty($searchResults)): ?>
            <div class="results-container">
                <h2 style="padding: 20px; color: #764ba2;">Found <?php echo count($searchResults); ?> Registration(s)</h2>
                
                <?php foreach ($searchResults as $result): ?>
                    <div class="result-card">
                        <div class="result-header">
                            <div class="result-title">
                                TCAM ID: <?php echo htmlspecialchars($result['reg_id']); ?>
                            </div>
                            <?php if ($result['photo']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($result['photo']); ?>" 
                                     alt="Photo" class="photo-thumbnail">
                            <?php endif; ?>
                        </div>
                        
                        <div class="result-info">
                            <div class="info-item">
                                <span class="info-label">Name:</span>
                                <span class="info-value"><?php echo htmlspecialchars($result['name']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Mobile:</span>
                                <span class="info-value"><?php echo htmlspecialchars($result['mobile']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">City:</span>
                                <span class="info-value"><?php echo htmlspecialchars($result['city']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">State:</span>
                                <span class="info-value"><?php echo htmlspecialchars($result['state']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Joined:</span>
                                <span class="info-value"><?php echo htmlspecialchars($result['joined']); ?></span>
                            </div>
                            <?php if (!empty($result['document_number'])): ?>
                                <div class="info-item">
                                    <span class="info-label">Document:</span>
                                    <span class="info-value"><?php echo htmlspecialchars($result['document_number']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="result-actions">
                            <a href="?download=pdf&id=<?php echo $result['id']; ?>" 
                               class="btn btn-success btn-small">📄 Download PDF</a>
                            <a href="?download=json&id=<?php echo $result['id']; ?>" 
                               class="btn btn-info btn-small">📊 Download JSON</a>
                            <a href="single-registration.html?reuse=<?php echo $result['id']; ?>" 
                               class="btn btn-primary btn-small">🔄 Use for New Registration</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="back-link">
            <a href="index.html" class="btn btn-primary">← Back to Website</a>
        </div>
    </div>
</body>
</html>
