<?php
/**
 * Admin Search System
 * Allows admin to search student data by various criteria including document numbers
 */

require_once 'security-config.php';
session_start();
require_once 'secure-database.php';

// Require admin authentication — same session flag login-production.php sets,
// so this page actually works for the admin account that's really in use.
if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-production.php');
    exit;
}

// Rate limiting
Security::rateLimit(20, 300); // 20 searches per 5 minutes

$searchResults = [];
$error = '';
$success = '';
$totalResults = 0;

// Export links are plain GET requests (so the browser can download the
// file directly) that re-run the same last search rather than re-posting.
$isExportRequest = isset($_GET['export']) && isset($_GET['search_type'], $_GET['search_term']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || $isExportRequest) {
    // GET-based export re-runs a search the admin already performed, from
    // their own authenticated session — CSRF doesn't apply to a same-session
    // re-read, so only POST (which can add/delete data) needs the token.
    if (!$isExportRequest && (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token']))) {
        $error = "Invalid request. Please refresh the page and try again.";
    } else {
        $searchType = Security::sanitizeInput($isExportRequest ? ($_GET['search_type'] ?? '') : ($_POST['search_type'] ?? ''));
        $searchTerm = Security::sanitizeInput($isExportRequest ? ($_GET['search_term'] ?? '') : ($_POST['search_term'] ?? ''));

        if (empty($searchTerm)) {
            $error = "Please enter search criteria.";
        } else {
            try {
                $db = SecureDatabase::getInstance();
                
                // Log the search
                $db->insert('search_logs', [
                    'user_type' => 'admin',
                    'search_term' => $searchTerm,
                    'search_type' => $searchType,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                // Both `registrations` (the detailed ID-registration form) and
                // `bookings` (the main homepage "Register Now" form) hold real
                // students, so every search here unions both — otherwise this
                // silently misses everyone who came through the main form,
                // which is most of them.
                $regsSelect = "SELECT 'registration' AS source, id AS source_id, reg_id, name, mobile,
                        city, state, document_number, document_type, date_of_birth, parent_name,
                        address, emergency_contact, blood_group, photo, joined
                    FROM registrations WHERE %s";
                $bookingsSelect = "SELECT 'booking' AS source, id AS source_id, bookingId AS reg_id, name, phone AS mobile,
                        district AS city, '' AS state, aadhar_number AS document_number, '' AS document_type,
                        dob AS date_of_birth, '' AS parent_name, '' AS address, '' AS emergency_contact,
                        '' AS blood_group, photo, created_at AS joined
                    FROM bookings WHERE %s";

                switch ($searchType) {
                    case 'all':
                        $term = "%$searchTerm%";
                        $regsWhere = "(name LIKE ? OR mobile LIKE ? OR city LIKE ? OR state LIKE ? OR document_number LIKE ? OR parent_name LIKE ? OR address LIKE ?)";
                        $bookingsWhere = "(name LIKE ? OR phone LIKE ? OR district LIKE ? OR aadhar_number LIKE ?)";
                        $query = sprintf($regsSelect, $regsWhere) . " UNION ALL " . sprintf($bookingsSelect, $bookingsWhere) . " ORDER BY joined DESC";
                        $searchResults = $db->execute($query, [
                            $term, $term, $term, $term, $term, $term, $term,
                            $term, $term, $term, $term,
                        ])->fetchAll();
                        break;

                    case 'mobile':
                        if (Security::validatePhone($searchTerm)) {
                            $query = sprintf($regsSelect, 'mobile = ?') . " UNION ALL " . sprintf($bookingsSelect, 'phone = ?') . " ORDER BY joined DESC";
                            $searchResults = $db->execute($query, [$searchTerm, $searchTerm])->fetchAll();
                        } else {
                            $error = "Please enter a valid 10-digit mobile number.";
                        }
                        break;

                    case 'reg_id':
                        $term = "%$searchTerm%";
                        $query = sprintf($regsSelect, 'reg_id LIKE ?') . " UNION ALL " . sprintf($bookingsSelect, 'bookingId LIKE ?') . " ORDER BY joined DESC";
                        $searchResults = $db->execute($query, [$term, $term])->fetchAll();
                        break;

                    case 'document':
                        $term = "%$searchTerm%";
                        $query = sprintf($regsSelect, 'document_number LIKE ?') . " UNION ALL " . sprintf($bookingsSelect, 'aadhar_number LIKE ?') . " ORDER BY joined DESC";
                        $searchResults = $db->execute($query, [$term, $term])->fetchAll();
                        break;

                    case 'name':
                        $term = "%$searchTerm%";
                        $query = sprintf($regsSelect, 'name LIKE ?') . " UNION ALL " . sprintf($bookingsSelect, 'name LIKE ?') . " ORDER BY joined DESC";
                        $searchResults = $db->execute($query, [$term, $term])->fetchAll();
                        break;

                    case 'city':
                        // Bookings don't have a separate city field, but district is the closest equivalent.
                        $term = "%$searchTerm%";
                        $query = sprintf($regsSelect, 'city LIKE ?') . " UNION ALL " . sprintf($bookingsSelect, 'district LIKE ?') . " ORDER BY joined DESC";
                        $searchResults = $db->execute($query, [$term, $term])->fetchAll();
                        break;

                    case 'state':
                        // Bookings have no state field at all — registrations only.
                        $searchResults = $db->execute(
                            sprintf($regsSelect, 'state LIKE ?') . " ORDER BY joined DESC",
                            ["%$searchTerm%"]
                        )->fetchAll();
                        break;

                    case 'parent':
                        // Parent/guardian name only exists on the detailed registrations form.
                        $searchResults = $db->execute(
                            sprintf($regsSelect, 'parent_name LIKE ?') . " ORDER BY joined DESC",
                            ["%$searchTerm%"]
                        )->fetchAll();
                        break;

                    case 'date_range':
                        $dateRange = explode(' to ', $searchTerm);
                        if (count($dateRange) === 2) {
                            $query = sprintf($regsSelect, 'DATE(joined) BETWEEN ? AND ?') . " UNION ALL " . sprintf($bookingsSelect, 'DATE(created_at) BETWEEN ? AND ?') . " ORDER BY joined DESC";
                            $searchResults = $db->execute($query, [$dateRange[0], $dateRange[1], $dateRange[0], $dateRange[1]])->fetchAll();
                        } else {
                            $error = "Please enter date range in format: YYYY-MM-DD to YYYY-MM-DD";
                        }
                        break;

                    default:
                        $error = "Invalid search type.";
                }
                
                if (empty($error)) {
                    $totalResults = count($searchResults);
                    if ($totalResults === 0) {
                        $error = "No results found for your search criteria.";
                    }
                }
                
            } catch (Exception $e) {
                $error = "Search failed. Please try again later.";
                Security::logEvent('ADMIN_SEARCH_ERROR', [
                    'admin_id' => ($_SESSION['admin_id'] ?? 0),
                    'search_type' => $searchType,
                    'search_term' => $searchTerm,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    $action = Security::sanitizeInput($_POST['bulk_action'] ?? '');
    $selectedIds = $_POST['selected_ids'] ?? [];
    
    if (empty($selectedIds)) {
        $error = "Please select at least one registration.";
    } else {
        try {
            $db = SecureDatabase::getInstance();
            $db->beginTransaction();
            
            foreach ($selectedIds as $rawId) {
                // Values are "registration:5" or "booking:12" now that results
                // can come from either table.
                if (!preg_match('/^(registration|booking):(\d+)$/', (string) $rawId, $m)) {
                    continue;
                }
                $source = $m[1];
                $id = (int) $m[2];
                $table = $source === 'booking' ? 'bookings' : 'registrations';

                switch ($action) {
                    case 'delete':
                        $rows = $db->select($table, ['id' => $id]);
                        if (!empty($rows)) {
                            $row = $rows[0];
                            $photoFile = preg_replace('#^/?uploads/#', '', $row['photo'] ?? '');
                            $photoPath = __DIR__ . '/uploads/' . $photoFile;
                            if ($photoFile !== '' && file_exists($photoPath)) {
                                unlink($photoPath);
                            }

                            $db->delete($table, ['id' => $id]);
                            if ($table === 'registrations') {
                                $db->execute('DELETE FROM event_registrations WHERE registration_id = ?', [$id]);
                            }

                            Security::logEvent('BULK_DELETE_REGISTRATION', [
                                'admin_id' => ($_SESSION['admin_id'] ?? 0),
                                'source' => $source,
                                'display_id' => $row['reg_id'] ?? $row['bookingId'] ?? '',
                                'deleted_user' => $row['name'],
                            ]);
                        }
                        break;

                    case 'export':
                        // Will be handled separately
                        break;
                }
            }
            
            $db->commit();
            $success = count($selectedIds) . " registrations processed successfully.";
            
        } catch (Exception $e) {
            $db->rollback();
            $error = "Bulk action failed. Please try again.";
        }
    }
}

// Handle export functionality
if (isset($_GET['export']) && !empty($searchResults)) {
    $exportType = Security::sanitizeInput($_GET['export']);
    
    switch ($exportType) {
        case 'csv':
            exportToCSV($searchResults);
            break;
        case 'excel':
            exportToExcel($searchResults);
            break;
        case 'pdf':
            exportToPDF($searchResults);
            break;
    }
}

function exportToCSV($results) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="TCAM_Students_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Header row
    fputcsv($output, [
        'TCAM ID', 'Name', 'Mobile', 'City', 'State', 'Document Number', 
        'Document Type', 'Date of Birth', 'Parent Name', 'Address', 
        'Emergency Contact', 'Blood Group', 'Joined', 'Photo'
    ]);
    
    // Data rows
    foreach ($results as $row) {
        fputcsv($output, [
            $row['reg_id'],
            $row['name'],
            $row['mobile'],
            $row['city'],
            $row['state'],
            $row['document_number'] ?? '',
            $row['document_type'] ?? '',
            $row['date_of_birth'] ?? '',
            $row['parent_name'] ?? '',
            $row['address'] ?? '',
            $row['emergency_contact'] ?? '',
            $row['blood_group'] ?? '',
            $row['joined'],
            $row['photo'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

function exportToExcel($results) {
    // For now, redirect to CSV (you can integrate a proper Excel library later)
    exportToCSV($results);
}

function exportToPDF($results) {
    require_once __DIR__ . '/lib/fpdf/fpdf.php';

    $safe = function ($text) {
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', (string) $text);
        return $converted !== false ? $converted : (string) $text;
    };

    $pdf = new FPDF('L', 'mm', 'A4');
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->Cell(0, 10, $safe('Tennis Cricket Association Maharashtra'), 0, 1, 'C');
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->Cell(0, 7, 'Student Registration Report', 0, 1, 'C');
    $pdf->Cell(0, 6, 'Generated on: ' . date('Y-m-d H:i:s') . '  |  Total Records: ' . count($results), 0, 1, 'C');
    $pdf->Ln(4);

    $headers = ['TCAM ID', 'Name', 'Mobile', 'City', 'State', 'Document Number', 'Joined'];
    $widths = [35, 55, 30, 40, 35, 45, 35];

    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->SetFillColor(118, 75, 162);
    $pdf->SetTextColor(255, 255, 255);
    foreach ($headers as $i => $h) {
        $pdf->Cell($widths[$i], 8, $h, 1, 0, 'C', true);
    }
    $pdf->Ln();

    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetTextColor(0, 0, 0);
    foreach ($results as $row) {
        $pdf->Cell($widths[0], 7, $safe($row['reg_id']), 1);
        $pdf->Cell($widths[1], 7, $safe($row['name']), 1);
        $pdf->Cell($widths[2], 7, $safe($row['mobile']), 1);
        $pdf->Cell($widths[3], 7, $safe($row['city']), 1);
        $pdf->Cell($widths[4], 7, $safe($row['state']), 1);
        $pdf->Cell($widths[5], 7, $safe($row['document_number'] ?? 'N/A'), 1);
        $pdf->Cell($widths[6], 7, $safe($row['joined']), 1);
        $pdf->Ln();
    }

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="TCAM_Students_' . date('Y-m-d') . '.pdf"');
    echo $pdf->Output('S');
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
    <title>Admin Search - TCAM</title>
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
            max-width: 1400px;
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
        }
        
        .header h1 {
            color: #764ba2;
            margin-bottom: 10px;
        }
        
        .user-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }
        
        .search-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 2fr auto;
            gap: 15px;
            align-items: end;
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
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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
        
        .results-header {
            background: #764ba2;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .bulk-actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #555;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .photo {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e1e5e9;
        }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .export-buttons {
            display: flex;
            gap: 10px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #764ba2;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .results-header {
                flex-direction: column;
                gap: 15px;
            }
            
            .bulk-actions {
                flex-direction: column;
                width: 100%;
            }
            
            .export-buttons {
                flex-direction: column;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Admin Search System</h1>
            <div class="user-info">
                <div>
                    <strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?>
                </div>
                <div>
                    <a href="admin-dashboard.php" class="btn btn-secondary">← Admin Panel</a>
                    <a href="login-production.php?logout=1" class="btn btn-danger">Logout</a>
                </div>
            </div>
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

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalResults; ?></div>
                <div class="stat-label">Search Results</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    $db = SecureDatabase::getInstance();
                    echo $db->execute("SELECT COUNT(*) as count FROM registrations")->fetch()['count']; 
                ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    echo $db->execute("SELECT COUNT(*) as count FROM registrations WHERE document_number IS NOT NULL AND document_number != ''")->fetch()['count']; 
                ?></div>
                <div class="stat-label">With Document Numbers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php 
                    echo $db->execute("SELECT COUNT(*) as count FROM registrations WHERE DATE(created_at) = DATE('now')")->fetch()['count']; 
                ?></div>
                <div class="stat-label">Today's Registrations</div>
            </div>
        </div>

        <div class="search-container">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="search_type">Search By</label>
                        <select name="search_type" id="search_type" required>
                            <option value="">Select Search Type</option>
                            <option value="all">All Fields</option>
                            <option value="mobile">Mobile Number</option>
                            <option value="reg_id">TCAM ID</option>
                            <option value="document">Document Number</option>
                            <option value="name">Name</option>
                            <option value="city">City</option>
                            <option value="state">State</option>
                            <option value="parent">Parent Name</option>
                            <option value="date_range">Date Range (YYYY-MM-DD to YYYY-MM-DD)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="search_term">Search Criteria</label>
                        <input type="text" id="search_term" name="search_term" 
                               placeholder="Enter search details..." required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                </div>
            </form>
        </div>

        <?php if (!empty($searchResults)): ?>
            <div class="results-container">
                <div class="results-header">
                    <div>
                        <h3>Found <?php echo count($searchResults); ?> Result(s)</h3>
                    </div>
                    <div class="export-buttons">
                        <a href="?export=csv&search_type=<?php echo urlencode($searchType); ?>&search_term=<?php echo urlencode($searchTerm); ?>" class="btn btn-success btn-small">📊 Export CSV</a>
                        <a href="?export=pdf&search_type=<?php echo urlencode($searchType); ?>&search_term=<?php echo urlencode($searchTerm); ?>" class="btn btn-danger btn-small">📄 Export PDF</a>
                    </div>
                </div>
                
                <form method="POST" id="bulkForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                    <input type="hidden" name="bulk_action" id="bulkAction" value="">
                    
                    <table>
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAll"></th>
                                <th>Photo</th>
                                <th>TCAM ID</th>
                                <th>Source</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>City</th>
                                <th>State</th>
                                <th>Document Number</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($searchResults as $result):
                                $photoFile = preg_replace('#^/?uploads/#', '', $result['photo'] ?? '');
                                $editPage = $result['source'] === 'booking' ? 'edit-booking.php' : 'edit-registration.php';
                            ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_ids[]" value="<?php echo htmlspecialchars($result['source']); ?>:<?php echo (int) $result['source_id']; ?>"></td>
                                    <td>
                                        <?php if ($photoFile): ?>
                                            <img src="uploads/<?php echo htmlspecialchars($photoFile); ?>"
                                                 alt="Photo" class="photo">
                                        <?php else: ?>
                                            No Photo
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($result['reg_id']); ?></strong></td>
                                    <td><?php echo $result['source'] === 'booking' ? 'Booking' : 'Registration'; ?></td>
                                    <td><?php echo htmlspecialchars($result['name']); ?></td>
                                    <td><?php echo htmlspecialchars($result['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars($result['city']); ?></td>
                                    <td><?php echo htmlspecialchars($result['state']); ?></td>
                                    <td><?php echo htmlspecialchars($result['document_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($result['joined']); ?></td>
                                    <td>
                                        <div class="actions">
                                            <a href="<?php echo $editPage; ?>?id=<?php echo (int) $result['source_id']; ?>" target="_blank"
                                               class="btn btn-primary btn-small">✏️ Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="bulk-actions" style="padding: 20px; background: #f8f9fa;">
                        <button type="button" onclick="performBulkAction('delete')" 
                                class="btn btn-danger btn-small">🗑️ Delete Selected</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Select all functionality
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="selected_ids[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
        
        // Bulk action functionality
        function performBulkAction(action) {
            const selected = document.querySelectorAll('input[name="selected_ids[]"]:checked');
            if (selected.length === 0) {
                alert('Please select at least one registration.');
                return;
            }
            
            if (action === 'delete' && !confirm(`Are you sure you want to delete ${selected.length} registration(s)?`)) {
                return;
            }
            
            document.getElementById('bulkAction').value = action;
            document.getElementById('bulkForm').submit();
        }
        
        // Search type placeholder update
        document.getElementById('search_type').addEventListener('change', function() {
            const searchInput = document.getElementById('search_term');
            const placeholders = {
                'all': 'Search in all fields...',
                'mobile': 'Enter 10-digit mobile number...',
                'reg_id': 'Enter TCAM ID...',
                'document': 'Enter document number...',
                'name': 'Enter student name...',
                'city': 'Enter city name...',
                'state': 'Enter state name...',
                'parent': 'Enter parent name...',
                'date_range': 'YYYY-MM-DD to YYYY-MM-DD'
            };
            
            searchInput.placeholder = placeholders[this.value] || 'Enter search details...';
        });
    </script>
</body>
</html>
