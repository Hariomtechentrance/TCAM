<?php
/**
 * Admin Dashboard - TCAM
 * Complete admin panel for managing registrations
 */

require_once 'security-config.php';
session_start();
require_once 'secure-database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-production.php');
    exit;
}

// Get statistics — every real registration lives in one of two tables:
// `bookings` (the main homepage "Register Now" form) and `registrations`
// (the separate, more detailed "Enhanced ID Registration" form). Both must
// be counted and listed together for the dashboard to reflect reality.
$db = SecureDatabase::getInstance();
$bookingsTotal = (int) $db->execute("SELECT COUNT(*) as count FROM bookings")->fetch()['count'];
$regsTotal = (int) $db->execute("SELECT COUNT(*) as count FROM registrations")->fetch()['count'];
$bookingsToday = (int) $db->execute("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = DATE('now')")->fetch()['count'];
$regsToday = (int) $db->execute("SELECT COUNT(*) as count FROM registrations WHERE DATE(joined) = DATE('now')")->fetch()['count'];
$bookingsMonth = (int) $db->execute("SELECT COUNT(*) as count FROM bookings WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')")->fetch()['count'];
$regsMonth = (int) $db->execute("SELECT COUNT(*) as count FROM registrations WHERE strftime('%Y-%m', joined) = strftime('%Y-%m', 'now')")->fetch()['count'];

$stats = [
    'total_registrations' => $bookingsTotal + $regsTotal,
    'today_registrations' => $bookingsToday + $regsToday,
    'this_month' => $bookingsMonth + $regsMonth,
    'pending_events' => $db->execute("SELECT COUNT(*) as count FROM event_registrations")->fetch()['count']
];

// Get search parameters
$searchType = $_GET['search_type'] ?? '';
$searchTerm = $_GET['search_term'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Build search filters per source table — column names differ between the
// two (mobile vs phone, reg_id vs bookingId, document_number vs
// aadhar_number, created_at vs joined), so each side needs its own set of
// conditions, ANDed together, then joined into one WHERE clause.
$bookingsConds = [];
$regsConds = [];
$bookingsParams = [];
$regsParams = [];

if (!empty($searchTerm) && !empty($searchType)) {
    $term = "%$searchTerm%";
    switch ($searchType) {
        case 'mobile':
            $bookingsConds[] = "phone LIKE ?"; $bookingsParams[] = $term;
            $regsConds[] = "mobile LIKE ?"; $regsParams[] = $term;
            break;
        case 'reg_id':
            $bookingsConds[] = "bookingId LIKE ?"; $bookingsParams[] = $term;
            $regsConds[] = "reg_id LIKE ?"; $regsParams[] = $term;
            break;
        case 'name':
            $bookingsConds[] = "name LIKE ?"; $bookingsParams[] = $term;
            $regsConds[] = "name LIKE ?"; $regsParams[] = $term;
            break;
        case 'document':
            $bookingsConds[] = "aadhar_number LIKE ?"; $bookingsParams[] = $term;
            $regsConds[] = "document_number LIKE ?"; $regsParams[] = $term;
            break;
        case 'email':
            $bookingsConds[] = "email LIKE ?"; $bookingsParams[] = $term;
            $regsConds[] = "email LIKE ?"; $regsParams[] = $term;
            break;
    }
}

// Date-range filter — applies on top of any text search above, filtered on
// each table's own "when registered" column (created_at / joined).
if (!empty($dateFrom)) {
    $bookingsConds[] = "DATE(created_at) >= ?"; $bookingsParams[] = $dateFrom;
    $regsConds[] = "DATE(joined) >= ?"; $regsParams[] = $dateFrom;
}
if (!empty($dateTo)) {
    $bookingsConds[] = "DATE(created_at) <= ?"; $bookingsParams[] = $dateTo;
    $regsConds[] = "DATE(joined) <= ?"; $regsParams[] = $dateTo;
}

$bookingsWhere = $bookingsConds ? ('WHERE ' . implode(' AND ', $bookingsConds)) : '';
$regsWhere = $regsConds ? ('WHERE ' . implode(' AND ', $regsConds)) : '';

$unifiedSelect = "SELECT 'booking' AS source, id AS source_id, bookingId AS display_id, name, phone AS mobile,
        email, district AS city, aadhar_number AS document_number, 'active' AS status, created_at AS joined, photo
    FROM bookings $bookingsWhere
    UNION ALL
    SELECT 'registration' AS source, id AS source_id, reg_id AS display_id, name, mobile,
        email, city, document_number, COALESCE(status, 'active') AS status, joined, photo
    FROM registrations $regsWhere";

$countParams = array_merge($bookingsParams, $regsParams);
$totalResults = (int) $db->execute(
    "SELECT COUNT(*) as total FROM ($unifiedSelect)",
    $countParams
)->fetch()['total'];

$listParams = array_merge($bookingsParams, $regsParams);
$registrations = $db->execute(
    "SELECT * FROM ($unifiedSelect) ORDER BY joined DESC LIMIT $limit OFFSET $offset",
    $listParams
)->fetchAll();

// Booking photos are stored as "uploads/xxx.jpg" (sometimes with a leading
// slash); registration photos are stored as a bare filename. Normalize both
// to a bare filename so the table can render one <img> pattern for either.
foreach ($registrations as &$row) {
    $row['photo'] = preg_replace('#^/?uploads/#', '', $row['photo'] ?? '');
}
unset($row);

$totalPages = max(1, (int) ceil($totalResults / $limit));

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $source = $_POST['source'] ?? 'registration';
    $id = (int)($_POST['id'] ?? 0);
    $table = $source === 'booking' ? 'bookings' : 'registrations';

    switch ($action) {
        case 'delete':
            $db->execute("DELETE FROM $table WHERE id = ?", [$id]);
            if ($table === 'registrations') {
                $db->execute("DELETE FROM event_registrations WHERE registration_id = ?", [$id]);
            }
            $message = "Registration deleted successfully!";
            break;

        case 'cancel':
            if ($table === 'registrations') {
                $db->execute("UPDATE registrations SET status = 'cancelled' WHERE id = ?", [$id]);
                $message = "Registration cancelled successfully!";
            } else {
                $message = "Bookings can be deleted but not cancelled — use Delete instead.";
            }
            break;

        case 'activate':
            if ($table === 'registrations') {
                $db->execute("UPDATE registrations SET status = 'active' WHERE id = ?", [$id]);
                $message = "Registration activated successfully!";
            }
            break;
    }

    if (isset($message)) {
        echo "<script>alert('$message'); window.location.href = 'admin-dashboard.php';</script>";
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - TCAM</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #764ba2;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
            align-items: center;
        }
        
        .nav-links a {
            color: #764ba2;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #5a3785;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 1rem;
        }
        
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 1.2fr 1.2fr 1fr 1fr auto auto;
            gap: 1rem;
            align-items: end;
        }

        .quick-filters {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e1e5e9;
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
        }

        .quick-filters-label {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
            margin-right: 0.3rem;
        }

        .quick-filters a {
            padding: 0.4rem 0.9rem;
            background: #f3f0fa;
            color: #764ba2;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .quick-filters a:hover {
            background: #764ba2;
            color: white;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #555;
        }
        
        .form-group select,
        .form-group input {
            padding: 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group select:focus,
        .form-group input:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
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
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .data-table {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }
        
        .table-header {
            background: #764ba2;
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-title {
            font-size: 1.2rem;
            font-weight: bold;
        }
        
        .table-info {
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.9rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #555;
            border-bottom: 2px solid #e1e5e9;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a {
            padding: 0.5rem 1rem;
            background: rgba(255, 255, 255, 0.95);
            color: #764ba2;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .pagination a:hover {
            background: #764ba2;
            color: white;
        }
        
        .pagination .active {
            background: #764ba2;
            color: white;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .photo-thumbnail {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 6px;
            border: 2px solid #e1e5e9;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-form {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .table-header {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }
            
            table {
                font-size: 0.875rem;
            }
            
            th, td {
                padding: 0.5rem;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-cricket"></i> TCAM Admin
            </div>
            <nav class="nav-links">
                <a href="admin-dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin-search.php"><i class="fas fa-search"></i> Search</a>
                <a href="registration-reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="login-production.php?logout=1"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total_registrations']); ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['today_registrations']); ?></div>
                <div class="stat-label">Today's Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['this_month']); ?></div>
                <div class="stat-label">This Month</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['pending_events']); ?></div>
                <div class="stat-label">Pending Events</div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="search_type">Search By</label>
                    <select name="search_type" id="search_type">
                        <option value="">All Registrations</option>
                        <option value="mobile" <?php echo $searchType === 'mobile' ? 'selected' : ''; ?>>Mobile Number</option>
                        <option value="reg_id" <?php echo $searchType === 'reg_id' ? 'selected' : ''; ?>>TCAM ID</option>
                        <option value="name" <?php echo $searchType === 'name' ? 'selected' : ''; ?>>Name</option>
                        <option value="document" <?php echo $searchType === 'document' ? 'selected' : ''; ?>>Document Number</option>
                        <option value="email" <?php echo $searchType === 'email' ? 'selected' : ''; ?>>Email</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="search_term">Search Term</label>
                    <input type="text" name="search_term" id="search_term"
                           value="<?php echo htmlspecialchars($searchTerm); ?>"
                           placeholder="Enter search term...">
                </div>
                <div class="form-group">
                    <label for="date_from">Registered From</label>
                    <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="form-group">
                    <label for="date_to">Registered To</label>
                    <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
                <a href="admin-dashboard.php" class="btn btn-warning">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
            <div class="quick-filters">
                <span class="quick-filters-label">Quick filters:</span>
                <a href="?date_from=<?php echo date('Y-m-d'); ?>&date_to=<?php echo date('Y-m-d'); ?>">Today</a>
                <a href="?date_from=<?php echo date('Y-m-01'); ?>&date_to=<?php echo date('Y-m-d'); ?>">This month</a>
                <a href="?date_from=<?php echo date('Y-m-d', strtotime('-1 month', strtotime(date('Y-m-01')))); ?>&date_to=<?php echo date('Y-m-d'); ?>">Last 2 months</a>
                <a href="?date_from=<?php echo date('Y-m-d', strtotime('-30 days')); ?>&date_to=<?php echo date('Y-m-d'); ?>">Last 30 days</a>
            </div>
        </div>

        <!-- Results Table -->
        <div class="data-table">
            <div class="table-header">
                <div class="table-title">
                    <?php
                    $titleBits = [];
                    if (!empty($searchTerm)) $titleBits[] = "matching \"" . htmlspecialchars($searchTerm) . "\"";
                    if (!empty($dateFrom) || !empty($dateTo)) {
                        $titleBits[] = "from " . ($dateFrom ?: '…') . " to " . ($dateTo ?: '…');
                    }
                    if ($titleBits) {
                        echo "Results (" . number_format($totalResults) . " found) — " . implode(', ', $titleBits);
                    } else {
                        echo "All Registrations (" . number_format($totalResults) . ")";
                    }
                    ?>
                </div>
                <div class="table-info">
                    Page <?php echo $page; ?> of <?php echo $totalPages; ?>
                </div>
            </div>
            
            <form id="teamReportForm" method="POST" action="admin-team-report.php" target="_blank">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" onclick="document.querySelectorAll('input[name=\'selected_ids[]\']').forEach(function(cb){cb.checked=this.checked;}, this)" title="Select all on this page"></th>
                        <th>Photo</th>
                        <th>TCAM ID</th>
                        <th>Source</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>City</th>
                        <th>Document</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($registrations)): ?>
                        <?php foreach ($registrations as $registration): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_ids[]" value="<?php echo htmlspecialchars($registration['source']); ?>:<?php echo (int)$registration['source_id']; ?>"></td>
                                <td>
                                    <?php if ($registration['photo']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($registration['photo']); ?>"
                                             alt="Photo" class="photo-thumbnail">
                                    <?php else: ?>
                                        <div class="photo-thumbnail" style="background: #e1e5e9; display: flex; align-items: center; justify-content: center; color: #999;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($registration['display_id']); ?></strong></td>
                                <td><?php echo $registration['source'] === 'booking' ? 'Booking' : 'Registration'; ?></td>
                                <td><?php echo htmlspecialchars($registration['name']); ?></td>
                                <td><?php echo htmlspecialchars($registration['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($registration['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($registration['city']); ?></td>
                                <td>
                                    <?php
                                    if (!empty($registration['document_number'])) {
                                        echo substr($registration['document_number'], 0, 4) . '****';
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $registration['status'] ?? 'active'; ?>">
                                        <?php echo ucfirst($registration['status'] ?? 'Active'); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d M Y', strtotime($registration['joined'])); ?></td>
                                <td>
                                    <div class="actions">
                                        <button onclick="editRegistration('<?php echo $registration['source']; ?>', <?php echo (int)$registration['source_id']; ?>)"
                                                class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <?php if ($registration['source'] === 'registration'): ?>
                                        <button onclick="viewRegistration(<?php echo (int)$registration['source_id']; ?>)"
                                                class="btn btn-sm btn-success">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button onclick="confirmAction('cancel', '<?php echo $registration['source']; ?>', <?php echo (int)$registration['source_id']; ?>)"
                                                class="btn btn-sm btn-warning">
                                            <i class="fas fa-ban"></i> Cancel
                                        </button>
                                        <?php endif; ?>
                                        <button onclick="confirmAction('delete', '<?php echo $registration['source']; ?>', <?php echo (int)$registration['source_id']; ?>)"
                                                class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" style="text-align: center; padding: 2rem; color: #666;">
                                <?php if (!empty($searchTerm)): ?>
                                    No registrations found matching your search criteria.
                                <?php else: ?>
                                    No registrations found.
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($registrations)): ?>
            <div style="padding: 1.25rem; border-top: 1px solid #e5e7eb;">
                <h3 style="margin: 0 0 0.75rem; font-size: 1.05rem; color: #111827;">Team Entry Report (PDF with photos)</h3>
                <p style="margin: 0 0 0.75rem; color: #666; font-size: 0.9rem;">Check the boxes above for the rows to include (this page only), fill in the tournament details, then generate.</p>
                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.75rem; margin-bottom: 0.75rem;">
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:4px;">Championship / Tournament Title</label>
                        <input type="text" name="report_title" placeholder="e.g. 5th Senior National Tennis Cricket Championship (Men/Women) - 2026" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:4px;">Venue</label>
                        <input type="text" name="report_venue" placeholder="e.g. Centurion University Bhubaneswar" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:4px;">Dates</label>
                        <input type="text" name="report_dates" placeholder="e.g. 09-Mar-2026 To 14-Mar-2026" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px;">
                    </div>
                    <div>
                        <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:4px;">Team Label</label>
                        <input type="text" name="report_team_label" placeholder="e.g. ITCF000527-MAHARASHTRA (WOMEN)" style="width:100%; padding:10px 12px; border:1px solid #d1d5db; border-radius:8px;">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-file-pdf"></i> Download Team Entry Report PDF</button>
            </div>
            <?php endif; ?>
            </form>
        </div>

        <!-- Pagination -->
        <?php
        $pageQueryTail = '&search_type=' . urlencode($searchType)
            . '&search_term=' . urlencode($searchTerm)
            . '&date_from=' . urlencode($dateFrom)
            . '&date_to=' . urlencode($dateTo);
        ?>
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $pageQueryTail; ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <?php if ($i == $page): ?>
                        <span class="active"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a href="?page=<?php echo $i; ?><?php echo $pageQueryTail; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $pageQueryTail; ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Hidden Forms for Actions -->
    <form id="actionForm" method="POST" style="display: none;">
        <input type="hidden" name="action" id="actionInput">
        <input type="hidden" name="source" id="sourceInput">
        <input type="hidden" name="id" id="idInput">
    </form>

    <script>
        function confirmAction(action, source, id) {
            const messages = {
                'delete': 'Are you sure you want to delete this registration? This action cannot be undone.',
                'cancel': 'Are you sure you want to cancel this registration?',
                'activate': 'Are you sure you want to activate this registration?'
            };

            if (confirm(messages[action])) {
                document.getElementById('actionInput').value = action;
                document.getElementById('sourceInput').value = source;
                document.getElementById('idInput').value = id;
                document.getElementById('actionForm').submit();
            }
        }

        function editRegistration(source, id) {
            const page = source === 'booking' ? 'edit-booking.php' : 'edit-registration.php';
            window.open(page + '?id=' + id, '_blank', 'width=800,height=600,scrollbars=yes');
        }

        function viewRegistration(id) {
            window.open('view-registration.php?id=' + id, '_blank', 'width=800,height=600,scrollbars=yes');
        }
    </script>
</body>
</html>
