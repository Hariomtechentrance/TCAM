<?php
/**
 * Registration Reports - Admin Panel
 * Comprehensive reporting and analytics
 */

session_start();
require_once 'security-config.php';
require_once 'secure-database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-production.php');
    exit;
}

$db = SecureDatabase::getInstance();

// Get report parameters
$reportType = $_GET['report_type'] ?? 'overview';
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Generate reports based on type
switch ($reportType) {
    case 'overview':
        $stats = [
            'total' => $db->execute("SELECT COUNT(*) as count FROM registrations")->fetch()['count'],
            'this_month' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')")->fetch()['count'],
            'last_month' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', date('now', strtotime('-1 month'))")->fetch()['count'],
            'this_year' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE strftime('%Y', created_at) = strftime('%Y', 'now')")->fetch()['count'],
            'active' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE status = 'active'")->fetch()['count'],
            'cancelled' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE status = 'cancelled'")->fetch()['count'],
        ];
        
        // City distribution
        $cityStats = $db->execute("SELECT city, COUNT(*) as count FROM registrations GROUP BY city ORDER BY count DESC LIMIT 10")->fetchAll();
        
        // Document type distribution
        $docStats = $db->execute("SELECT document_type, COUNT(*) as count FROM registrations GROUP BY document_type ORDER BY count DESC")->fetchAll();
        
        // Monthly registration trend
        $monthlyTrend = $db->execute("
            SELECT strftime('%Y-%m', created_at) as month, COUNT(*) as count 
            FROM registrations 
            WHERE created_at >= date('now', '-11 months')
            GROUP BY strftime('%Y-%m', created_at) 
            ORDER BY month
        ")->fetchAll();
        break;
        
    case 'detailed':
        $registrations = $db->execute("
            SELECT * FROM registrations 
            WHERE DATE(created_at) BETWEEN ? AND ? 
            ORDER BY created_at DESC
        ", [$startDate, $endDate])->fetchAll();
        break;
        
    case 'export':
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tcam_registrations_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Header
        fputcsv($output, ['TCAM ID', 'Name', 'Mobile', 'Email', 'City', 'State', 'Document Type', 'Document Number', 'Status', 'Registration Date', 'Created At']);
        
        // Data
        $registrations = $db->execute("SELECT * FROM registrations ORDER BY created_at DESC")->fetchAll();
        foreach ($registrations as $reg) {
            fputcsv($output, [
                $reg['reg_id'],
                $reg['name'],
                $reg['mobile'],
                $reg['email'] ?? '',
                $reg['city'],
                $reg['state'],
                $reg['document_type'] ?? '',
                $reg['document_number'] ?? '',
                $reg['status'] ?? 'active',
                $reg['joined'],
                $reg['created_at']
            ]);
        }
        
        fclose($output);
        exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Registration Reports - TCAM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .controls {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .control-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
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
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .report-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .report-card h2 {
            color: #764ba2;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #764ba2;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 1.5rem;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 1.5rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }
        
        th {
            background: #764ba2;
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .control-form {
                grid-template-columns: 1fr;
            }
            
            .reports-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
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
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <!-- Report Controls -->
        <div class="controls">
            <form method="GET" class="control-form">
                <div class="form-group">
                    <label for="report_type">Report Type</label>
                    <select name="report_type" id="report_type">
                        <option value="overview" <?php echo $reportType === 'overview' ? 'selected' : ''; ?>>Overview</option>
                        <option value="detailed" <?php echo $reportType === 'detailed' ? 'selected' : ''; ?>>Detailed Report</option>
                        <option value="export" <?php echo $reportType === 'export' ? 'selected' : ''; ?>>Export CSV</option>
                    </select>
                </div>
                
                <?php if ($reportType === 'detailed'): ?>
                    <div class="form-group">
                        <label for="start_date">Start Date</label>
                        <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="end_date">End Date</label>
                        <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Generate Report
                </button>
                
                <?php if ($reportType === 'export'): ?>
                    <button type="button" onclick="window.location.href='?report_type=export'" class="btn btn-success">
                        <i class="fas fa-download"></i> Download CSV
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <?php if ($reportType === 'overview'): ?>
            <!-- Overview Report -->
            <div class="reports-grid">
                <div class="report-card">
                    <h2><i class="fas fa-chart-line"></i> Registration Statistics</h2>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['total']); ?></div>
                            <div class="stat-label">Total Registrations</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['this_month']); ?></div>
                            <div class="stat-label">This Month</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['last_month']); ?></div>
                            <div class="stat-label">Last Month</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['this_year']); ?></div>
                            <div class="stat-label">This Year</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo number_format($stats['cancelled']); ?></div>
                            <div class="stat-label">Cancelled</div>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <h2><i class="fas fa-chart-area"></i> Monthly Trend</h2>
                    <div class="chart-container">
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>

                <div class="report-card">
                    <h2><i class="fas fa-map-marker-alt"></i> Top Cities</h2>
                    <div class="chart-container">
                        <canvas id="cityChart"></canvas>
                    </div>
                </div>

                <div class="report-card">
                    <h2><i class="fas fa-id-card"></i> Document Types</h2>
                    <div class="chart-container">
                        <canvas id="docChart"></canvas>
                    </div>
                </div>
            </div>

        <?php elseif ($reportType === 'detailed'): ?>
            <!-- Detailed Report -->
            <div class="report-card">
                <h2><i class="fas fa-list"></i> Detailed Registration Report</h2>
                <p style="margin-bottom: 1rem; color: #666;">
                    Showing registrations from <strong><?php echo date('d M Y', strtotime($startDate)); ?></strong> 
                    to <strong><?php echo date('d M Y', strtotime($endDate)); ?></strong>
                    (<?php echo count($registrations); ?> records)
                </p>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>TCAM ID</th>
                                <th>Name</th>
                                <th>Mobile</th>
                                <th>City</th>
                                <th>Document Type</th>
                                <th>Status</th>
                                <th>Registration Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($registrations as $reg): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($reg['reg_id']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['name']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['mobile']); ?></td>
                                    <td><?php echo htmlspecialchars($reg['city']); ?></td>
                                    <td><?php echo ucfirst($reg['document_type'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $reg['status'] ?? 'active'; ?>">
                                            <?php echo ucfirst($reg['status'] ?? 'Active'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($reg['joined'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($reportType === 'overview'): ?>
        <script>
            // Monthly Trend Chart
            const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($monthlyTrend, 'month')); ?>,
                    datasets: [{
                        label: 'Registrations',
                        data: <?php echo json_encode(array_column($monthlyTrend, 'count')); ?>,
                        borderColor: '#764ba2',
                        backgroundColor: 'rgba(118, 75, 162, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // City Distribution Chart
            const cityCtx = document.getElementById('cityChart').getContext('2d');
            new Chart(cityCtx, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode(array_column($cityStats, 'city')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($cityStats, 'count')); ?>,
                        backgroundColor: [
                            '#764ba2', '#667eea', '#f39c12', '#27ae60', '#e74c3c',
                            '#3498db', '#9b59b6', '#1abc9c', '#34495e', '#e67e22'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Document Type Chart
            const docCtx = document.getElementById('docChart').getContext('2d');
            new Chart(docCtx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_map('ucfirst', array_column($docStats, 'document_type'))); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($docStats, 'count')); ?>,
                        backgroundColor: [
                            '#764ba2', '#667eea', '#f39c12', '#27ae60', '#e74c3c',
                            '#3498db', '#9b59b6', '#1abc9c', '#34495e', '#e67e22'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        </script>
    <?php endif; ?>
</body>
</html>
