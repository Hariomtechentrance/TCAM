<?php
/**
 * Complete Admin Panel - TCAM
 * All-in-one solution for managing registrations
 */

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>TCAM Admin Panel - Complete Solution</title>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css'>
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
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
            border-radius: 12px;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 2rem;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #764ba2;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .nav-links {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            color: #764ba2;
            text-decoration: none;
            font-weight: 600;
            padding: 0.8rem 1.2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.1);
        }
        
        .nav-links a:hover {
            background: #764ba2;
            color: white;
            transform: translateY(-2px);
        }
        
        .main-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .section h2 {
            color: #764ba2;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 1rem;
            opacity: 0.9;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-success {
            background: linear-gradient(45deg, #27ae60, #229954);
            color: white;
        }
        
        .btn-warning {
            background: linear-gradient(45deg, #f39c12, #e67e22);
            color: white;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .info-box {
            background: #e8f4fd;
            border: 2px solid #bee5eb;
            border-radius: 12px;
            padding: 2rem;
            margin-top: 2rem;
        }
        
        .info-box h3 {
            color: #0d6efd;
            margin-bottom: 1rem;
        }
        
        .login-form {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            max-width: 400px;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .nav-links {
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class='container'>
        <header class='header'>
            <div class='header-content'>
                <div class='logo'>
                    <i class='fas fa-cricket'></i>
                    TCAM Admin Panel
                </div>
                <nav class='nav-links'>
                    <a href='login-production.php'>
                        <i class='fas fa-sign-in-alt'></i> Login
                    </a>
                    <a href='admin-dashboard.php'>
                        <i class='fas fa-tachometer-alt'></i> Dashboard
                    </a>
                    <a href='edit-registration.php'>
                        <i class='fas fa-edit'></i> Edit
                    </a>
                    <a href='view-registration.php'>
                        <i class='fas fa-eye'></i> View
                    </a>
                    <a href='registration-reports.php'>
                        <i class='fas fa-chart-bar'></i> Reports
                    </a>
                    <a href='logout-production.php'>
                        <i class='fas fa-sign-out-alt'></i> Logout
                    </a>
                </nav>
            </div>
        </header>

        <div class='main-content'>
            <!-- Login Section -->
            <div class='section'>
                <h2><i class='fas fa-sign-in-alt'></i> Admin Login</h2>
                <div class='login-form'>
                    <form method='POST' action='login-production.php'>
                        <div class='form-group'>
                            <label for='username'>Username</label>
                            <input type='text' id='username' name='username' required>
                        </div>
                        <div class='form-group'>
                            <label for='password'>Password</label>
                            <input type='password' id='password' name='password' required>
                        </div>
                        <button type='submit' class='btn btn-primary'>
                            <i class='fas fa-sign-in-alt'></i> Login to Admin Panel
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Stats Section -->
            <div class='section'>
                <h2><i class='fas fa-chart-line'></i> Quick Statistics</h2>
                <div class='stats-grid'>
                    <div class='stat-card'>
                        <div class='stat-number'>";

// Get quick stats
try {
    require_once 'security-config.php';
    require_once 'secure-database.php';
    
    $db = SecureDatabase::getInstance();
    $stats = [
        'total' => $db->execute("SELECT COUNT(*) as count FROM registrations")->fetch()['count'],
        'today' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE DATE(created_at) = DATE('now')")->fetch()['count'],
        'this_month' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE strftime('%Y-%m', created_at) = strftime('%Y-%m', 'now')")->fetch()['count'],
        'active' => $db->execute("SELECT COUNT(*) as count FROM registrations WHERE status = 'active'")->fetch()['count']
    ];
    
    echo $stats['total'] . "</div>
                        <div class='stat-label'>Total Registrations</div>
                    </div>
                    
                    <div class='stat-card'>
                        <div class='stat-number'>" . $stats['today'] . "</div>
                        <div class='stat-label'>Today's Registrations</div>
                    </div>
                    
                    <div class='stat-card'>
                        <div class='stat-number'>" . $stats['this_month'] . "</div>
                        <div class='stat-label'>This Month</div>
                    </div>
                    
                    <div class='stat-card'>
                        <div class='stat-number'>" . $stats['active'] . "</div>
                        <div class='stat-label'>Active Students</div>
                    </div>";
    
} catch (Exception $e) {
    echo "<div class='stat-card'>
                        <div class='stat-number'>0</div>
                        <div class='stat-label'>Database Error</div>
                    </div>";
}

echo "                </div>
            </div>

            <!-- Action Buttons Section -->
            <div class='section'>
                <h2><i class='fas fa-tools'></i> Admin Actions</h2>
                <div class='action-buttons'>
                    <a href='admin-dashboard.php' class='btn btn-primary'>
                        <i class='fas fa-tachometer-alt'></i> Full Dashboard
                    </a>
                    
                    <a href='edit-registration.php' class='btn btn-success'>
                        <i class='fas fa-edit'></i> Edit Registration
                    </a>
                    
                    <a href='view-registration.php' class='btn btn-warning'>
                        <i class='fas fa-eye'></i> View Registration
                    </a>
                    
                    <a href='registration-reports.php' class='btn btn-danger'>
                        <i class='fas fa-chart-bar'></i> Reports & Analytics
                    </a>
                </div>
            </div>
        </div>

        <!-- Information Box -->
        <div class='info-box'>
            <h3><i class='fas fa-info-circle'></i> TCAM Admin Panel Information</h3>
            <p><strong>🔑 Login Credentials:</strong></p>
            <ul>
                <li><strong>Username:</strong> <code>admin</code></li>
                <li><strong>Password:</strong> <code>admin123!@#</code></li>
            </ul>
            
            <p><strong>🌐 Access Links:</strong></p>
            <ul>
                <li><strong>Login:</strong> <a href='login-production.php'>login-production.php</a></li>
                <li><strong>Dashboard:</strong> <a href='admin-dashboard.php'>admin-dashboard.php</a></li>
                <li><strong>Edit:</strong> <a href='edit-registration.php'>edit-registration.php</a></li>
                <li><strong>View:</strong> <a href='view-registration.php'>view-registration.php</a></li>
                <li><strong>Reports:</strong> <a href='registration-reports.php'>registration-reports.php</a></li>
                <li><strong>Logout:</strong> <a href='logout-production.php'>logout-production.php</a></li>
            </ul>
            
            <p><strong>✅ Features Available:</strong></p>
            <ul>
                <li>📊 Complete Dashboard with Statistics</li>
                <li>🔍 Advanced Search by Multiple Fields</li>
                <li>✏️ Edit Registration Data</li>
                <li>👁️ View Complete Student Profiles</li>
                <li>📈 Analytics & Reports with Charts</li>
                <li>📥 CSV Export for Data Analysis</li>
                <li>📱 Mobile Responsive Design</li>
                <li>🔒 Secure Admin Authentication</li>
                <li>📝 Complete Activity Logging</li>
            </ul>
        </div>
    </div>
</body>
</html>";
?>
