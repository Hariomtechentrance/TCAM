<?php
/**
 * Working Admin Dashboard - TCAM
 * Simple, working admin panel
 */

// Start session
session_start();

// Check if admin is logged in (simple check for demo)
if (!isset($_SESSION['admin_logged_in'])) {
    // For demo: auto-login if not logged in
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
    $_SESSION['login_time'] = time();
}

// Get database connection
try {
    $db = new SQLite3('database.sqlite');
    
    // Get statistics
    $stats = [
        'total' => $db->query("SELECT COUNT(*) FROM registrations")->fetchArray()[0],
        'today' => $db->query("SELECT COUNT(*) FROM registrations WHERE DATE(created_at) = DATE('now')")->fetchArray()[0],
        'active' => $db->query("SELECT COUNT(*) FROM registrations WHERE status = 'active' OR status IS NULL")->fetchArray()[0]
    ];
    
} catch (Exception $e) {
    $stats = [
        'total' => 0,
        'today' => 0,
        'active' => 0
    ];
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TCAM Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
        
        .data-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }
        
        .section-title {
            color: #764ba2;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            text-align: center;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .data-table th {
            background: #764ba2;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
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
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
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
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #721c24;
        }
        
        .welcome-message {
            background: #e8f5e8;
            color: #856404;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            border-left: 4px solid #856404;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-cricket"></i> TCAM Admin Dashboard
            </div>
            <div style="color: #666; font-size: 0.9rem;">
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>!
                <br>Logged in at: <?php echo date('h:i A, d M Y', $_SESSION['login_time'] ?? time()); ?>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Total Registrations</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['today']); ?></div>
                <div class="stat-label">Today's Registrations</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                <div class="stat-label">Active Students</div>
            </div>
        </div>

        <div class="data-section">
            <h2 class="section-title">
                <i class="fas fa-users"></i> Recent Registrations
            </h2>
            
            <?php
            try {
                $registrations = $db->query("SELECT * FROM registrations ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($registrations)) {
                    echo "<table class='data-table'>";
                    echo "<thead>";
                    echo "<tr>";
                    echo "<th>TCAM ID</th>";
                    echo "<th>Name</th>";
                    echo "<th>Mobile</th>";
                    echo "<th>City</th>";
                    echo "<th>Status</th>";
                    echo "<th>Joined</th>";
                    echo "<th>Actions</th>";
                    echo "</tr>";
                    echo "</thead>";
                    echo "<tbody>";
                    
                    foreach ($registrations as $registration) {
                        echo "<tr>";
                        echo "<td><strong>" . htmlspecialchars($registration['reg_id']) . "</strong></td>";
                        echo "<td>" . htmlspecialchars($registration['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($registration['mobile']) . "</td>";
                        echo "<td>" . htmlspecialchars($registration['city']) . "</td>";
                        echo "<td>";
                        echo "<span class='status-badge status-" . ($registration['status'] ?? 'active') . "'>";
                        echo ucfirst($registration['status'] ?? 'Active');
                        echo "</span>";
                        echo "</td>";
                        echo "<td>" . date('d M Y', strtotime($registration['created_at'])) . "</td>";
                        echo "<td>";
                        echo "<div class='actions'>";
                        echo "<a href='edit-registration.php?id=" . $registration['id'] . "' class='btn btn-primary'><i class='fas fa-edit'></i> Edit</a>";
                        echo "<a href='view-registration.php?id=" . $registration['id'] . "' class='btn btn-success'><i class='fas fa-eye'></i> View</a>";
                        echo "<a href='#' onclick='if(confirm(\"Delete this registration?\")) { window.location.href=\"delete-registration.php?id=" . $registration['id'] . "\"; }' class='btn btn-danger'><i class='fas fa-trash'></i> Delete</a>";
                        echo "</div>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    
                    echo "</tbody>";
                    echo "</table>";
                } else {
                    echo "<p style='text-align: center; color: #666; padding: 2rem;'>No registrations found in database.</p>";
                }
                
            } catch (Exception $e) {
                echo "<div class='error'>";
                echo "<strong>Database Error:</strong> " . htmlspecialchars($e->getMessage());
                echo "</div>";
            }
            ?>
        </div>

        <div class="data-section">
            <h2 class="section-title">
                <i class="fas fa-tools"></i> Admin Actions
            </h2>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <a href="add-registration.php" class="btn btn-primary" style="text-align: center;">
                    <i class="fas fa-plus"></i> Add New Registration
                </a>
                
                <a href="export-data.php" class="btn btn-success" style="text-align: center;">
                    <i class="fas fa-download"></i> Export All Data
                </a>
                
                <a href="logout.php" class="btn btn-warning" style="text-align: center;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</body>
</html>
