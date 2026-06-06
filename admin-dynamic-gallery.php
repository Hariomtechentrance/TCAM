<?php
/**
 * Dynamic Gallery Admin Dashboard - TCAM
 * Images uploaded appear directly in their respective sections
 */

// Start session
session_start();

// Auto-login for demo
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
    $_SESSION['login_time'] = time();
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
    ]
];

// Tournament data
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
    ]
];

// Get uploaded images from directories
function getUploadedImages($directory) {
    $images = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && $file !== 'index.php') {
                $imagePath = $directory . '/' . $file;
                // Better image detection - check file extension and try getimagesize
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                
                if (in_array($extension, $validExtensions)) {
                    $imageSize = @getimagesize($imagePath);
                    if ($imageSize !== false) {
                        $images[] = [
                            'name' => $file,
                            'path' => $imagePath,
                            'url' => $imagePath,
                            'size' => filesize($imagePath),
                            'modified' => filemtime($imagePath)
                        ];
                    }
                }
            }
        }
        // Sort by modified date (newest first)
        usort($images, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
    }
    return $images;
}

// Get images for different categories
$galleryImages = [
    'tournaments' => getUploadedImages('gallery/tournaments'),
    'events' => getUploadedImages('gallery/events'),
    'students' => getUploadedImages('gallery/students'),
    'achievements' => getUploadedImages('gallery/achievements'),
    'fullsize' => getUploadedImages('gallery/fullsize'),
    'infrastructure' => getUploadedImages('gallery/infrastructure')
];

// Calculate statistics
$stats = [
    'total_registrations' => count($sampleRegistrations),
    'today' => 2,
    'active' => count(array_filter($sampleRegistrations, function($r) { return $r['status'] === 'active'; })),
    'total_tournaments' => count($tournamentData),
    'completed_tournaments' => count(array_filter($tournamentData, function($t) { return $t['status'] === 'completed'; })),
    'total_images' => array_sum(array_map('count', $galleryImages))
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
        $uploadSuccess = "Image uploaded successfully! It will appear in the gallery below.";
        
        // Refresh the images array
        $category = str_replace('gallery/', '', $uploadDir);
        if (isset($galleryImages[$category])) {
            $galleryImages[$category] = getUploadedImages($uploadDir);
        }
    } else {
        $uploadError = "Failed to upload image.";
    }
}

// Handle image deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imagePath = $_POST['image_path'] ?? '';
    $category = $_POST['category'] ?? '';
    
    if (!empty($imagePath) && file_exists($imagePath)) {
        if (unlink($imagePath)) {
            $deleteSuccess = "Image deleted successfully!";
            
            // Refresh the images array
            if (isset($galleryImages[$category])) {
                $galleryImages[$category] = getUploadedImages('gallery/' . $category);
            }
        } else {
            $deleteError = "Failed to delete image.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TCAM Dynamic Gallery Admin Dashboard</title>
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
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-ongoing {
            background: #fff3cd;
            color: #856404;
        }
        
        .gallery-upload {
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 1rem;
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
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .gallery-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
            position: relative;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
        }
        
        .gallery-item img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        
        .gallery-item-info {
            padding: 0.5rem;
        }
        
        .gallery-item-name {
            font-size: 0.7rem;
            font-weight: 600;
            color: #333;
            word-break: break-all;
            margin-bottom: 0.25rem;
        }
        
        .gallery-item-actions {
            display: flex;
            gap: 0.25rem;
        }
        
        .btn-small {
            padding: 0.2rem 0.4rem;
            font-size: 0.6rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-view {
            background: #28a745;
            color: white;
        }
        
        .btn-delete {
            background: #dc3545;
            color: white;
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
        
        .empty-gallery {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
        }
        
        .tab-container {
            margin-bottom: 1rem;
        }
        
        .tab-buttons {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
        
        .tab-button {
            padding: 0.5rem 1rem;
            background: #e1e5e9;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            background: #764ba2;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .chart-container {
            position: relative;
            height: 200px;
            margin-top: 1rem;
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
            
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
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
                <i class="fas fa-cricket"></i> TCAM Dynamic Gallery Admin
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
                            <div class="stat-number"><?php echo number_format($stats['total_images']); ?></div>
                            <div class="stat-label">Images</div>
                        </div>
                    </div>
                </div>

                <!-- Tournament Section with Gallery -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-trophy"></i> Tournament Management & Gallery
                    </h2>
                    
                    <?php if (isset($uploadSuccess)): ?>
                        <div class="success">
                            <i class="fas fa-check-circle"></i> <?php echo $uploadSuccess; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($deleteSuccess)): ?>
                        <div class="success">
                            <i class="fas fa-check-circle"></i> <?php echo $deleteSuccess; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tournament Gallery Upload -->
                    <div class="gallery-upload">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="category">Upload to Tournament Gallery</label>
                                <select id="category" name="category" required>
                                    <option value="gallery/tournaments">Tournaments</option>
                                    <option value="gallery/events">Events</option>
                                    <option value="gallery/students">Students</option>
                                    <option value="gallery/achievements">Achievements</option>
                                    <option value="gallery/fullsize">Main Gallery</option>
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

                    <!-- Tournament Gallery Display -->
                    <div class="tab-container">
                        <div class="tab-buttons">
                            <button class="tab-button active" onclick="showTab('tournaments')">Tournaments (<?php echo count($galleryImages['tournaments']); ?>)</button>
                            <button class="tab-button" onclick="showTab('events')">Events (<?php echo count($galleryImages['events']); ?>)</button>
                            <button class="tab-button" onclick="showTab('students')">Students (<?php echo count($galleryImages['students']); ?>)</button>
                            <button class="tab-button" onclick="showTab('achievements')">Achievements (<?php echo count($galleryImages['achievements']); ?>)</button>
                            <button class="tab-button" onclick="showTab('fullsize')">Main Gallery (<?php echo count($galleryImages['fullsize']); ?>)</button>
                            <button class="tab-button" onclick="showTab('infrastructure')">Infrastructure (<?php echo count($galleryImages['infrastructure']); ?>)</button>
                        </div>

                        <?php foreach ($galleryImages as $category => $images): ?>
                            <div id="tab-<?php echo $category; ?>" class="tab-content <?php echo $category === 'tournaments' ? 'active' : ''; ?>">
                                <?php if (!empty($images)): ?>
                                    <div class="gallery-grid">
                                        <?php foreach ($images as $image): ?>
                                            <div class="gallery-item">
                                                <img src="<?php echo $image['url']; ?>" alt="<?php echo $image['name']; ?>">
                                                <div class="gallery-item-info">
                                                    <div class="gallery-item-name"><?php echo substr($image['name'], 11); ?></div>
                                                    <div class="gallery-item-actions">
                                                        <button class="btn-small btn-view" onclick="window.open('<?php echo $image['url']; ?>', '_blank')">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="delete_image" value="1">
                                                            <input type="hidden" name="image_path" value="<?php echo $image['path']; ?>">
                                                            <input type="hidden" name="category" value="<?php echo $category; ?>">
                                                            <button type="submit" class="btn-small btn-delete" onclick="return confirm('Delete this image?')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-gallery">
                                        <i class="fas fa-images" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                                        <p>No images in <?php echo ucfirst($category); ?> gallery yet.</p>
                                        <p>Upload images using the form above.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tournament Data Table -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-list"></i> Tournament Details
                    </h2>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Start</th>
                                    <th>End</th>
                                    <th>Venue</th>
                                    <th>Status</th>
                                    <th>Winner</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tournamentData as $tournament): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($tournament['tournament_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($tournament['tournament_name']); ?></td>
                                        <td><?php echo date('d M Y', strtotime($tournament['start_date'])); ?></td>
                                        <td><?php echo date('d M Y', strtotime($tournament['end_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($tournament['venue']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $tournament['status']; ?>">
                                                <?php echo ucfirst($tournament['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($tournament['winner']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="right-panel">
                <!-- Student Registrations -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-users"></i> Recent Registrations
                    </h2>
                    
                    <div style="overflow-x: auto;">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>TCAM ID</th>
                                    <th>Name</th>
                                    <th>Mobile</th>
                                    <th>City</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($sampleRegistrations as $registration): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($registration['reg_id']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($registration['name']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($registration['city']); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $registration['status']; ?>">
                                                <?php echo ucfirst($registration['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Gallery Statistics -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-chart-bar"></i> Gallery Statistics
                    </h2>
                    
                    <div class="chart-container">
                        <canvas id="galleryChart"></canvas>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="section">
                    <h2 class="section-title">
                        <i class="fas fa-tools"></i> Quick Actions
                    </h2>
                    
                    <div style="display: grid; gap: 0.8rem;">
                        <button onclick="alert('This would open advanced search')" class="btn btn-primary" style="width: 100%;">
                            <i class="fas fa-search"></i> Advanced Search
                        </button>
                        
                        <button onclick="alert('This would export all data')" class="btn btn-success" style="width: 100%;">
                            <i class="fas fa-download"></i> Export Data
                        </button>
                        
                        <button onclick="alert('This would open reports')" class="btn btn-warning" style="width: 100%;">
                            <i class="fas fa-chart-line"></i> Generate Reports
                        </button>
                        
                        <button onclick="window.location.reload()" class="btn btn-danger" style="width: 100%;">
                            <i class="fas fa-sync"></i> Refresh Gallery
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab functionality
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById('tab-' + tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
        }
        
        // Gallery Statistics Chart
        document.addEventListener('DOMContentLoaded', function() {
            const galleryCtx = document.getElementById('galleryChart').getContext('2d');
            const galleryData = {
                tournaments: <?php echo count($galleryImages['tournaments']); ?>,
                events: <?php echo count($galleryImages['events']); ?>,
                students: <?php echo count($galleryImages['students']); ?>,
                achievements: <?php echo count($galleryImages['achievements']); ?>,
                fullsize: <?php echo count($galleryImages['fullsize']); ?>,
                infrastructure: <?php echo count($galleryImages['infrastructure']); ?>
            };
            
            new Chart(galleryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Tournaments', 'Events', 'Students', 'Achievements', 'Main Gallery', 'Infrastructure'],
                    datasets: [{
                        data: [galleryData.tournaments, galleryData.events, galleryData.students, galleryData.achievements, galleryData.fullsize, galleryData.infrastructure],
                        backgroundColor: ['#764ba2', '#667eea', '#28a745', '#ffc107', '#17a2b8', '#dc3545'],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { 
                            position: 'bottom',
                            labels: {
                                font: { size: 10 }
                            }
                        },
                        title: { 
                            display: true, 
                            text: 'Image Distribution',
                            font: { size: 12 }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
