<?php
/**
 * Unified Admin Dashboard - TCAM
 * All admin features in one comprehensive dashboard
 */

// Start session
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin-login.php');
    exit;
}

// Sample data for demo
$sampleRegistrations = [
    [
        'reg_id' => '1001',
        'name' => 'Rahul Sharma',
        'mobile' => '9876543210',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'status' => 'active',
        'joined' => '2026-02-27',
        'created_at' => '2026-02-27 06:26:24',
        'id_proof_type' => 'aadhar',
        'document_number' => '123456789012',
        'email' => 'rahul.sharma@email.com'
    ],
    [
        'reg_id' => '1002',
        'name' => 'Priya Patel',
        'mobile' => '9876543211',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'status' => 'active',
        'joined' => '2026-02-27',
        'created_at' => '2026-02-27 06:26:24',
        'id_proof_type' => 'pan',
        'document_number' => 'ABCDE1234F',
        'email' => 'priya.patel@email.com'
    ],
    [
        'reg_id' => '1003',
        'name' => 'Amit Kumar',
        'mobile' => '9876543212',
        'city' => 'Nashik',
        'state' => 'Maharashtra',
        'status' => 'active',
        'joined' => '2026-02-27',
        'created_at' => '2026-02-27 06:26:24',
        'id_proof_type' => 'voter',
        'document_number' => 'VOT1234567',
        'email' => 'amit.kumar@email.com'
    ],
    [
        'reg_id' => '1004',
        'name' => 'Rahul Verma',
        'mobile' => '9876543213',
        'city' => 'Thane',
        'state' => 'Maharashtra',
        'status' => 'cancelled',
        'joined' => '2026-03-15',
        'created_at' => '2026-03-15 10:30:00',
        'id_proof_type' => 'passport',
        'document_number' => 'P12345678',
        'email' => 'rahul.verma@email.com'
    ],
    [
        'reg_id' => '1005',
        'name' => 'Sneha Reddy',
        'mobile' => '9876543214',
        'city' => 'Nagpur',
        'state' => 'Maharashtra',
        'status' => 'active',
        'joined' => '2026-04-01',
        'created_at' => '2026-04-01 14:20:00',
        'id_proof_type' => 'dl',
        'document_number' => 'DL987654321',
        'email' => 'sneha.reddy@email.com'
    ]
];

// Tournament data (replace this with your new data)
$tournamentData = [
    [
        'tournament_id' => 'T001',
        'tournament_name' => 'TCAM Cricket Championship 2024',
        'start_date' => '2024-03-15',
        'end_date' => '2024-03-25',
        'venue' => 'Mumbai Cricket Ground',
        'city' => 'Mumbai',
        'state' => 'Maharashtra',
        'participants' => 16,
        'status' => 'completed',
        'winner' => 'Mumbai Warriors',
        'runner_up' => 'Pune Panthers',
        'organizer' => 'TCAM Sports Committee',
        'contact_person' => 'John Doe',
        'contact_mobile' => '9876543210',
        'prize_money' => '50000',
        'created_at' => '2024-02-01 10:00:00'
    ],
    [
        'tournament_id' => 'T002',
        'tournament_name' => 'Summer Cricket League 2024',
        'start_date' => '2024-04-10',
        'end_date' => '2024-04-20',
        'venue' => 'Pune Sports Complex',
        'city' => 'Pune',
        'state' => 'Maharashtra',
        'participants' => 12,
        'status' => 'ongoing',
        'winner' => 'TBD',
        'runner_up' => 'TBD',
        'organizer' => 'Pune Cricket Association',
        'contact_person' => 'Jane Smith',
        'contact_mobile' => '9876543211',
        'prize_money' => '30000',
        'created_at' => '2024-03-01 15:30:00'
    ],
    [
        'tournament_id' => 'T003',
        'tournament_name' => 'Youth Cricket Tournament 2024',
        'start_date' => '2024-05-01',
        'end_date' => '2024-05-10',
        'venue' => 'Nashik Cricket Academy',
        'city' => 'Nashik',
        'state' => 'Maharashtra',
        'participants' => 8,
        'status' => 'upcoming',
        'winner' => 'TBD',
        'runner_up' => 'TBD',
        'organizer' => 'Nashik Sports Club',
        'contact_person' => 'Mike Johnson',
        'contact_mobile' => '9876543212',
        'prize_money' => '20000',
        'created_at' => '2024-04-01 09:15:00'
    ]
];

// Calculate statistics
$stats = [
    'total_registrations' => count($sampleRegistrations),
    'today' => 2, // Demo data
    'active' => count(array_filter($sampleRegistrations, function($r) { return $r['status'] === 'active'; })),
    'cancelled' => count(array_filter($sampleRegistrations, function($r) { return $r['status'] === 'cancelled'; })),
    'total_tournaments' => count($tournamentData),
    'completed_tournaments' => count(array_filter($tournamentData, function($t) { return $t['status'] === 'completed'; })),
    'ongoing_tournaments' => count(array_filter($tournamentData, function($t) { return $t['status'] === 'ongoing'; })),
    'upcoming_tournaments' => count(array_filter($tournamentData, function($t) { return $t['status'] === 'upcoming'; }))
];

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = $_POST['category'] ?? 'gallery/fullsize';
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $file['name']);
    $targetFile = $uploadDir . '/' . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        $uploadSuccess = "Image uploaded successfully!";
    } else {
        $uploadError = "Failed to upload image.";
    }
}

// Handle data export
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_data'])) {
    $dateFrom = $_POST['date_from'] ?? '';
    $dateTo = $_POST['date_to'] ?? '';
    
    // Filter data based on date range
    $exportData = $sampleRegistrations;
    if ($dateFrom && $dateTo) {
        $exportData = array_filter($sampleRegistrations, function($r) use ($dateFrom, $dateTo) {
            return $r['joined'] >= $dateFrom && $r['joined'] <= $dateTo;
        });
    }
    
    // Create CSV
    $csvContent = "TCAM ID,Name,Mobile,Email,City,State,ID Proof,Document Number,Status,Joined Date\n";
    foreach ($exportData as $row) {
        $csvContent .= "{$row['reg_id']},{$row['name']},{$row['mobile']},{$row['email']},{$row['city']},{$row['state']},{$row['id_proof_type']},{$row['document_number']},{$row['status']},{$row['joined']}\n";
    }
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="tcam_registrations_' . date('Y-m-d') . '.csv"');
    echo $csvContent;
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TCAM Unified Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }
        
        .container {
            max-width: 1600px;
            margin: 1rem auto;
            padding: 0 1rem;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .logo {
            font-size: 2rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }
        
        .user-info {
            color: #666;
            font-size: 0.9rem;
        }
        
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 1.5rem;
        }
        
        .left-panel {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .section {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .section-title {
            color: #764ba2;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
        }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            font-size: 0.9rem;
        }
        
        .data-table th {
            background: #764ba2;
            color: white;
            padding: 0.8rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .data-table td {
            padding: 0.8rem;
            border-bottom: 1px solid #e1e5e9;
        }
        
        .data-table tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 0.25rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.75rem;
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
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .status-badge {
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
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
        
        .filter-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 0.8rem;
        }
        
        .form-group {
            margin-bottom: 0.8rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.3rem;
            font-weight: 600;
            color: #333;
            font-size: 0.85rem;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.6rem;
            border: 2px solid #e1e5e9;
            border-radius: 4px;
            font-size: 0.85rem;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        .chart-container {
            position: relative;
            height: 200px;
            margin-top: 1rem;
        }
        
        .gallery-upload {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .export-section {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .success, .error {
            padding: 0.8rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 0.9rem;
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
        
        .filter-results {
            text-align: center;
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.85rem;
        }
        
        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }
            
            .filter-grid {
                grid-template-columns: 1fr;
            }
            
            .data-table {
                font-size: 0.8rem;
            }
            
            .data-table th, .data-table td {
                padding: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-cricket"></i> TCAM Unified Admin Dashboard
            </div>
            <div class="user-info">
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>!
                | Logged in: <?php echo date('h:i A, d M Y', $_SESSION['login_time'] ?? time()); ?>
            </div>
        </div>

        <div class="main-grid">
            <!-- Left Panel -->
            <div class="left-panel">
                <!-- Statistics Section -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-chart-line"></i> Statistics Overview
                    </h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['total_registrations']); ?></div>
                            <div class="stat-label">Registrations</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['active']); ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['total_tournaments']); ?></div>
                            <div class="stat-label">Tournaments</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo number_format($stats['today']); ?></div>
                            <div class="stat-label">Today</div>
                        </div>
                    </div>
                </div>

                <!-- Tournament Management Section -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-trophy"></i> Tournament Management
                    </h2>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="tournamentTable">
                            <thead>
                                <tr>
                                    <th>Tournament ID</th>
                                    <th>Tournament Name</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Venue</th>
                                    <th>City</th>
                                    <th>Participants</th>
                                    <th>Status</th>
                                    <th>Winner</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tournamentData as $index => $tournament): ?>
                                    <tr data-index="<?php echo $index; ?>">
                                        <td><strong><?php echo htmlspecialchars($tournament['tournament_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($tournament['tournament_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($tournament['start_date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($tournament['end_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($tournament['venue']); ?></td>
                                        <td><?php echo htmlspecialchars($tournament['city']); ?></td>
                                        <td><?php echo htmlspecialchars($tournament['participants']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $tournament['status']; ?>">
                                                <?php echo ucfirst($tournament['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($tournament['winner']); ?></td>
                                        <td>
                                            <div class="actions">
                                                <button onclick="viewTournament(<?php echo $index; ?>)" class="btn btn-success">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editTournament(<?php echo $index; ?>)" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteTournament(<?php echo $index; ?>)" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Data Management Section -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-users"></i> Student Registrations
                    </h2>
                    
                    <?php if (isset($uploadSuccess)): ?>
                        <div class="success">
                            <i class="fas fa-check-circle"></i> <?php echo $uploadSuccess; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($uploadError)): ?>
                        <div class="error">
                            <i class="fas fa-exclamation-circle"></i> <?php echo $uploadError; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Advanced Filters -->
                    <div class="filter-section">
                        <h3 style="color: #764ba2; margin-bottom: 0.8rem; text-align: center; font-size: 1rem;">
                            <i class="fas fa-filter"></i> Advanced Filters
                        </h3>
                        
                        <div class="filter-grid">
                            <div class="form-group">
                                <label for="searchName">Name</label>
                                <input type="text" id="searchName" placeholder="Search name..." onkeyup="filterData()">
                            </div>
                            
                            <div class="form-group">
                                <label for="searchMobile">Mobile</label>
                                <input type="text" id="searchMobile" placeholder="Mobile number..." onkeyup="filterData()">
                            </div>
                            
                            <div class="form-group">
                                <label for="searchTcamId">TCAM ID</label>
                                <input type="text" id="searchTcamId" placeholder="TCAM ID..." onkeyup="filterData()">
                            </div>
                            
                            <div class="form-group">
                                <label for="searchIdProof">ID Proof</label>
                                <select id="searchIdProof" onchange="filterData()">
                                    <option value="">All Types</option>
                                    <option value="aadhar">Aadhar</option>
                                    <option value="pan">PAN</option>
                                    <option value="voter">Voter</option>
                                    <option value="passport">Passport</option>
                                    <option value="dl">Driving License</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="dateFrom">Date From</label>
                                <input type="date" id="dateFrom" onchange="filterData()">
                            </div>
                            
                            <div class="form-group">
                                <label for="dateTo">Date To</label>
                                <input type="date" id="dateTo" onchange="filterData()">
                            </div>
                            
                            <div class="form-group">
                                <label for="searchStatus">Status</label>
                                <select id="searchStatus" onchange="filterData()">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <button onclick="clearFilters()" class="btn btn-warning" style="width: 100%;">
                                    <i class="fas fa-times"></i> Clear
                                </button>
                            </div>
                        </div>
                        
                        <div class="filter-results" id="filterResults">Showing all records</div>
                    </div>
                    
                    <!-- Data Table -->
                    <div style="overflow-x: auto;">
                        <table class="data-table" id="dataTable">
                            <thead>
                                <tr>
                                    <th>TCAM ID</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>Email</th>
                                    <th>City</th>
                                    <th>ID Proof</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="tableBody">
                                <?php foreach ($sampleRegistrations as $index => $registration): ?>
                                    <tr data-index="<?php echo $index; ?>" 
                                        data-name="<?php echo strtolower(htmlspecialchars($registration['name'])); ?>"
                                        data-mobile="<?php echo htmlspecialchars($registration['mobile']); ?>"
                                        data-tcamid="<?php echo htmlspecialchars($registration['reg_id']); ?>"
                                        data-idproof="<?php echo htmlspecialchars($registration['id_proof_type']); ?>"
                                        data-status="<?php echo htmlspecialchars($registration['status']); ?>"
                                        data-joined="<?php echo htmlspecialchars($registration['joined']); ?>">
                                        <td><strong><?php echo htmlspecialchars($registration['reg_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($registration['name']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['city']); ?></td>
                                        <td>
                                            <span class="status-badge" style="background: #e8f4fd; color: #0d6efd;">
                                                <?php echo ucfirst($registration['id_proof_type']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-badge status-<?php echo $registration['status']; ?>">
                                                <?php echo ucfirst($registration['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d M Y', strtotime($registration['joined'])); ?></td>
                                        <td>
                                            <div class="actions">
                                                <button onclick="viewRegistration(<?php echo $index; ?>)" class="btn btn-success">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editRegistration(<?php echo $index; ?>)" class="btn btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteRegistration(<?php echo $index; ?>)" class="btn btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <!-- Gallery Upload Section -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-images"></i> Gallery Upload
                    </h2>
                    
                    <div class="gallery-upload">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" required>
                                    <option value="gallery/fullsize">Main Gallery</option>
                                    <option value="gallery/tournaments">Tournaments</option>
                                    <option value="gallery/events">Events</option>
                                    <option value="gallery/students">Students</option>
                                    <option value="gallery/achievements">Achievements</option>
                                    <option value="gallery/certificates">Certificates</option>
                                    <option value="gallery/infrastructure">Infrastructure</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="image">Choose Image</label>
                                <input type="file" id="image" name="image" accept="image/*" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" style="width: 100%;">
                                <i class="fas fa-upload"></i> Upload Image
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Reports & Analytics Section -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-chart-bar"></i> Reports & Analytics
                    </h2>
                    
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                    
                    <div class="chart-container" style="margin-top: 2rem;">
                        <canvas id="idProofChart"></canvas>
                    </div>
                    
                    <div class="chart-container" style="margin-top: 2rem;">
                        <canvas id="tournamentChart"></canvas>
                    </div>
                </div>

                <!-- Data Export Section -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-download"></i> Data Export
                    </h2>
                    
                    <div class="export-section">
                        <form method="POST">
                            <div class="form-group">
                                <label for="date_from">Export From Date</label>
                                <input type="date" id="date_from" name="date_from">
                            </div>
                            
                            <div class="form-group">
                                <label for="date_to">Export To Date</label>
                                <input type="date" id="date_to" name="date_to">
                            </div>
                            
                            <button type="submit" name="export_data" class="btn btn-success" style="width: 100%;">
                                <i class="fas fa-file-csv"></i> Export CSV
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Advanced filtering functionality
        function filterData() {
            const nameFilter = document.getElementById('searchName').value.toLowerCase();
            const mobileFilter = document.getElementById('searchMobile').value;
            const tcamIdFilter = document.getElementById('searchTcamId').value;
            const idProofFilter = document.getElementById('searchIdProof').value;
            const statusFilter = document.getElementById('searchStatus').value;
            const dateFromFilter = document.getElementById('dateFrom').value;
            const dateToFilter = document.getElementById('dateTo').value;
            
            const rows = document.querySelectorAll('#tableBody tr');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const mobile = row.getAttribute('data-mobile');
                const tcamId = row.getAttribute('data-tcamid');
                const idProof = row.getAttribute('data-idproof');
                const status = row.getAttribute('data-status');
                const joined = row.getAttribute('data-joined');
                
                let showRow = true;
                
                if (nameFilter && !name.includes(nameFilter)) showRow = false;
                if (mobileFilter && !mobile.includes(mobileFilter)) showRow = false;
                if (tcamIdFilter && !tcamId.includes(tcamIdFilter)) showRow = false;
                if (idProofFilter && idProof !== idProofFilter) showRow = false;
                if (statusFilter && status !== statusFilter) showRow = false;
                if (dateFromFilter && joined < dateFromFilter) showRow = false;
                if (dateToFilter && joined > dateToFilter) showRow = false;
                
                row.style.display = showRow ? '' : 'none';
                if (showRow) visibleCount++;
            });
            
            const filterResults = document.getElementById('filterResults');
            const totalRows = rows.length;
            filterResults.textContent = visibleCount === totalRows ? 'Showing all records' : `Showing ${visibleCount} of ${totalRows} records`;
        }
        
        function clearFilters() {
            ['searchName', 'searchMobile', 'searchTcamId', 'searchIdProof', 'searchStatus', 'dateFrom', 'dateTo'].forEach(id => {
                document.getElementById(id).value = '';
            });
            filterData();
        }
        
        // Tournament action functions
        function viewTournament(index) {
            const tournaments = <?php echo json_encode($tournamentData); ?>;
            const tournament = tournaments[index];
            
            const details = `
Tournament Details:
==================
Tournament ID: ${tournament.tournament_id}
Name: ${tournament.tournament_name}
Start Date: ${tournament.start_date}
End Date: ${tournament.end_date}
Venue: ${tournament.venue}
City: ${tournament.city}
State: ${tournament.state}
Participants: ${tournament.participants}
Status: ${tournament.status}
Winner: ${tournament.winner}
Runner Up: ${tournament.runner_up}
Organizer: ${tournament.organizer}
Contact Person: ${tournament.contact_person}
Contact Mobile: ${tournament.contact_mobile}
Prize Money: ₹${tournament.prize_money}
Created: ${tournament.created_at}
            `.trim();
            
            alert(details);
        }
        
        function editTournament(index) {
            const tournaments = <?php echo json_encode($tournamentData); ?>;
            const tournament = tournaments[index];
            
            alert(`Edit Tournament:\n\nTournament ID: ${tournament.tournament_id}\nName: ${tournament.tournament_name}\nVenue: ${tournament.venue}\n\nThis would open the edit form with all tournament data pre-filled.`);
        }
        
        function deleteTournament(index) {
            const tournaments = <?php echo json_encode($tournamentData); ?>;
            const tournament = tournaments[index];
            
            if (confirm(`Delete tournament:\n\nTournament ID: ${tournament.tournament_id}\nName: ${tournament.tournament_name}\nVenue: ${tournament.venue}\n\nThis action cannot be undone.`)) {
                alert(`Tournament deleted successfully!`);
            }
        }
        
        // Action functions
        function viewRegistration(index) {
            const registrations = <?php echo json_encode($sampleRegistrations); ?>;
            const registration = registrations[index];
            
            const details = `
Registration Details:
==================
TCAM ID: ${registration.reg_id}
Name: ${registration.name}
Mobile: ${registration.mobile}
Email: ${registration.email}
City: ${registration.city}
State: ${registration.state}
ID Proof: ${registration.id_proof_type}
Document Number: ${registration.document_number}
Status: ${registration.status}
Joined: ${registration.joined}
Created: ${registration.created_at}
            `.trim();
            
            alert(details);
        }
        
        function editRegistration(index) {
            const registrations = <?php echo json_encode($sampleRegistrations); ?>;
            const registration = registrations[index];
            
            alert(`Edit Registration:\n\nTCAM ID: ${registration.reg_id}\nName: ${registration.name}\nMobile: ${registration.mobile}\n\nThis would open the edit form with all registration data pre-filled.`);
        }
        
        function deleteRegistration(index) {
            const registrations = <?php echo json_encode($sampleRegistrations); ?>;
            const registration = registrations[index];
            
            if (confirm(`Delete registration for:\n\nTCAM ID: ${registration.reg_id}\nName: ${registration.name}\nMobile: ${registration.mobile}\n\nThis action cannot be undone.`)) {
                alert(`Registration deleted successfully!`);
            }
        }
        
        // Charts
        document.addEventListener('DOMContentLoaded', function() {
            // Status Chart
            const statusCtx = document.getElementById('statusChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Cancelled'],
                    datasets: [{
                        data: [<?php echo $stats['active']; ?>, <?php echo $stats['cancelled']; ?>],
                        backgroundColor: ['#28a745', '#dc3545'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom' },
                        title: { display: true, text: 'Registration Status' }
                    }
                }
            });
            
            // ID Proof Chart
            const idProofCtx = document.getElementById('idProofChart').getContext('2d');
            const idProofCounts = {};
            <?php foreach ($sampleRegistrations as $reg): ?>
                idProofCounts['<?php echo $reg['id_proof_type']; ?>'] = (idProofCounts['<?php echo $reg['id_proof_type']; ?>'] || 0) + 1;
            <?php endforeach; ?>
            
            new Chart(idProofCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(idProofCounts),
                    datasets: [{
                        label: 'Count',
                        data: Object.values(idProofCounts),
                        backgroundColor: '#764ba2'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'ID Proof Types' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            
            // Tournament Status Chart
            const tournamentCtx = document.getElementById('tournamentChart').getContext('2d');
            const tournamentCounts = {
                completed: <?php echo $stats['completed_tournaments']; ?>,
                ongoing: <?php echo $stats['ongoing_tournaments']; ?>,
                upcoming: <?php echo $stats['upcoming_tournaments']; ?>
            };
            
            new Chart(tournamentCtx, {
                type: 'bar',
                data: {
                    labels: ['Completed', 'Ongoing', 'Upcoming'],
                    datasets: [{
                        label: 'Tournaments',
                        data: [tournamentCounts.completed, tournamentCounts.ongoing, tournamentCounts.upcoming],
                        backgroundColor: ['#28a745', '#ffc107', '#17a2b8']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Tournament Status' }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            
            // Initialize filter
            filterData();
        });
    </script>
</body>
</html>
