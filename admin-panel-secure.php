<?php
/**
 * Secure Admin Panel for TCAM
 * Requires authentication and implements comprehensive security
 */

require_once 'security-config.php';
require_once 'secure-auth.php';
require_once 'secure-database.php';

// Require admin authentication
$auth = new SecureAuth();
$auth->requireAdmin();

// Get database instance
$db = SecureDatabase::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !Security::validateCSRFToken($_POST['csrf_token'])) {
        Security::logEvent('CSRF_TOKEN_INVALID', ['form' => 'admin_panel']);
        die('Invalid request. Please refresh the page and try again.');
    }
    
    $action = Security::sanitizeInput($_POST['action'] ?? '');
    
    switch ($action) {
        case 'delete_registration':
            $regId = Security::sanitizeInput($_POST['reg_id'] ?? '');
            if (!empty($regId) && is_numeric($regId)) {
                try {
                    // Get registration info before deletion
                    $registration = $db->select('registrations', ['id' => $regId]);
                    
                    if (!empty($registration)) {
                        $reg = $registration[0];
                        
                        // Delete photo file if exists
                        $photoPath = __DIR__ . '/uploads/' . $reg['photo'];
                        if (file_exists($photoPath)) {
                            unlink($photoPath);
                        }
                        
                        // Delete from database
                        $db->delete('registrations', ['id' => $regId]);
                        
                        Security::logEvent('REGISTRATION_DELETED', [
                            'admin_id' => $_SESSION['user_id'],
                            'reg_id' => $regId,
                            'deleted_user' => $reg['name']
                        ]);
                        
                        $successMessage = "Registration deleted successfully.";
                    }
                } catch (Exception $e) {
                    $errorMessage = "Failed to delete registration.";
                }
            }
            break;
            
        case 'block_user':
            $mobile = Security::sanitizeInput($_POST['mobile'] ?? '');
            if (!empty($mobile)) {
                Security::blockSuspiciousIP($mobile);
                $successMessage = "User blocked successfully.";
            }
            break;
    }
}

// Get registrations with pagination and search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;
$search = Security::sanitizeInput($_GET['search'] ?? '');

try {
    if (!empty($search)) {
        // Search functionality
        $query = "SELECT * FROM registrations WHERE 
                  name LIKE ? OR 
                  mobile LIKE ? OR 
                  city LIKE ? OR 
                  state LIKE ? 
                  ORDER BY created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $searchParam = "%$search%";
        $registrations = $db->execute($query, [
            $searchParam, $searchParam, $searchParam, $searchParam, $limit, $offset
        ])->fetchAll();
        
        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) as total FROM registrations WHERE 
                      name LIKE ? OR 
                      mobile LIKE ? OR 
                      city LIKE ? OR 
                      state LIKE ?";
        $totalResult = $db->execute($countQuery, [
            $searchParam, $searchParam, $searchParam, $searchParam
        ])->fetch();
        $total = $totalResult['total'];
    } else {
        // Get all registrations
        $registrations = $db->select('registrations', [], '*', $limit);
        
        // Get total count
        $totalResult = $db->execute("SELECT COUNT(*) as total FROM registrations")->fetch();
        $total = $totalResult['total'];
    }
    
    $totalPages = ceil($total / $limit);
    
} catch (Exception $e) {
    $errorMessage = "Failed to load registrations.";
    $registrations = [];
    $total = 0;
    $totalPages = 0;
}

// Get security stats
try {
    $stats = [
        'total_registrations' => $db->execute("SELECT COUNT(*) as count FROM registrations")->fetch()['count'],
        'today_registrations' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE DATE(created_at) = DATE('now')")->fetch()['count'],
        'blocked_ips' => file_exists(__DIR__ . '/blocked_ips.txt') ? count(file(__DIR__ . '/blocked_ips.txt', FILE_IGNORE_NEW_LINES)) : 0,
        'security_events' => file_exists(__DIR__ . '/security.log') ? count(file(__DIR__ . '/security.log', FILE_IGNORE_NEW_LINES)) : 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_registrations' => 0,
        'today_registrations' => 0,
        'blocked_ips' => 0,
        'security_events' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCAM Secure Admin Panel</title>
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
            padding: 20px;
            border-radius: 12px;
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
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .search-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #764ba2;
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
        
        .table-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }
        
        th {
            background: #764ba2;
            color: white;
            font-weight: 600;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .photo {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e1e5e9;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        
        .page-link {
            padding: 8px 16px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #e1e5e9;
            border-radius: 6px;
            text-decoration: none;
            color: #764ba2;
            transition: all 0.3s ease;
        }
        
        .page-link:hover, .page-link.active {
            background: #764ba2;
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
        
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .search-input {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ TCAM Secure Admin Panel</h1>
            <div class="user-info">
                <div>
                    <strong>Admin:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?> | 
                    <strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['role']); ?>
                </div>
                <div>
                    <a href="logout.php" class="btn btn-secondary">Logout</a>
                </div>
            </div>
        </div>

        <?php if (isset($successMessage)): ?>
            <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
        <?php endif; ?>

        <?php if (isset($errorMessage)): ?>
            <div class="message error"><?php echo htmlspecialchars($errorMessage); ?></div>
        <?php endif; ?>

        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_registrations']; ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['today_registrations']; ?></div>
                <div class="stat-label">Today's Registrations</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['blocked_ips']; ?></div>
                <div class="stat-label">Blocked IPs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['security_events']; ?></div>
                <div class="stat-label">Security Events</div>
            </div>
        </div>

        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="text" name="search" class="search-input" placeholder="Search by name, mobile, city, or state..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if (!empty($search)): ?>
                    <a href="admin-panel-secure.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>City</th>
                        <th>State</th>
                        <th>Joined</th>
                        <th>Photo</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($registrations as $reg): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($reg['reg_id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($reg['name']); ?></td>
                            <td><?php echo htmlspecialchars($reg['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($reg['city']); ?></td>
                            <td><?php echo htmlspecialchars($reg['state']); ?></td>
                            <td><?php echo htmlspecialchars($reg['joined']); ?></td>
                            <td>
                                <?php if ($reg['photo']): ?>
                                    <img src="uploads/<?php echo htmlspecialchars($reg['photo']); ?>" alt="Photo" class="photo">
                                <?php else: ?>
                                    No Photo
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="delete_registration">
                                        <input type="hidden" name="reg_id" value="<?php echo $reg['id']; ?>">
                                        <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this registration?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" 
                       class="page-link <?php echo $page === $i ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
