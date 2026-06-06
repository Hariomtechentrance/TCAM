<?php
/**
 * TCAM Tournaments Page - Dedicated Tournament Section
 * Displays tournament information and specific tournament photo
 */

// Start session
session_start();

// Auto-login for demo
if (!isset($_SESSION['admin_logged_in'])) {
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = 'admin';
    $_SESSION['login_time'] = time();
}

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

// Check if the specific tournament image exists
$tournamentImagePath = 'uploads/tournaments/1775475616_WhatsApp_Image_2026-04-02_at_9.45.47___am.jpeg';
$tournamentImageExists = file_exists($tournamentImagePath);

// Get all tournament images
function getTournamentImages($directory) {
    $images = [];
    if (is_dir($directory)) {
        $files = scandir($directory);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && $file !== 'index.php') {
                $imagePath = $directory . '/' . $file;
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
        usort($images, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
    }
    return $images;
}

$tournamentImages = getTournamentImages('uploads/tournaments');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TCAM Tournaments - Tournament Management</title>
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
        
        .tournament-hero {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .tournament-image {
            max-width: 100%;
            max-height: 400px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            margin-bottom: 1rem;
        }
        
        .tournament-title {
            font-size: 2.5rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 1rem;
        }
        
        .tournament-subtitle {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 2rem;
        }
        
        .tournament-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .tournament-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
        }
        
        .tournament-card:hover {
            transform: translateY(-5px);
        }
        
        .tournament-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .tournament-id {
            background: #764ba2;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .tournament-status {
            padding: 0.3rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-ongoing {
            background: #fff3cd;
            color: #856404;
        }
        
        .tournament-name {
            font-size: 1.3rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .tournament-details {
            margin-bottom: 1rem;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .detail-label {
            font-weight: 600;
            color: #666;
        }
        
        .detail-value {
            color: #333;
        }
        
        .tournament-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 0.6rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #764ba2;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        .gallery-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .gallery-title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .gallery-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .gallery-item:hover {
            transform: translateY(-5px);
        }
        
        .gallery-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        
        .gallery-info {
            padding: 0.8rem;
        }
        
        .gallery-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            word-break: break-all;
        }
        
        .gallery-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.3rem 0.6rem;
            font-size: 0.8rem;
        }
        
        .no-image {
            text-align: center;
            padding: 2rem;
            color: #666;
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 0.5rem;
            }
            
            .tournament-grid {
                grid-template-columns: 1fr;
            }
            
            .gallery-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <i class="fas fa-trophy"></i> TCAM Tournaments
            </div>
            <div class="user-info">
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong>!
                | Logged in: <?php echo date('h:i A, d M Y', $_SESSION['login_time'] ?? time()); ?>
            </div>
        </div>

        <!-- Tournament Hero Section with Specific Image -->
        <div class="tournament-hero">
            <?php if ($tournamentImageExists): ?>
                <img src="<?php echo $tournamentImagePath; ?>" alt="Tournament Photo" class="tournament-image">
                <h1 class="tournament-title">TCAM Tournaments</h1>
                <p class="tournament-subtitle">Championships, Leagues & Cricket Excellence</p>
            <?php else: ?>
                <div class="no-image">
                    <i class="fas fa-image" style="font-size: 4rem; margin-bottom: 1rem; color: #ddd;"></i>
                    <h1 class="tournament-title">TCAM Tournaments</h1>
                    <p class="tournament-subtitle">Championships, Leagues & Cricket Excellence</p>
                    <p>Specific tournament image not found. Upload image to: uploads/tournaments/</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Tournament Cards -->
        <div class="tournament-grid">
            <?php foreach ($tournamentData as $tournament): ?>
                <div class="tournament-card">
                    <div class="tournament-header">
                        <span class="tournament-id"><?php echo htmlspecialchars($tournament['tournament_id']); ?></span>
                        <span class="tournament-status status-<?php echo $tournament['status']; ?>">
                            <?php echo ucfirst($tournament['status']); ?>
                        </span>
                    </div>
                    
                    <h3 class="tournament-name"><?php echo htmlspecialchars($tournament['tournament_name']); ?></h3>
                    
                    <div class="tournament-details">
                        <div class="detail-row">
                            <span class="detail-label">📅 Start Date:</span>
                            <span class="detail-value"><?php echo date('d M Y', strtotime($tournament['start_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">📅 End Date:</span>
                            <span class="detail-value"><?php echo date('d M Y', strtotime($tournament['end_date'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">🏟️ Venue:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($tournament['venue']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">🏙️ City:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($tournament['city']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">👥 Participants:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($tournament['participants']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">🏆 Winner:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($tournament['winner']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">💰 Prize Money:</span>
                            <span class="detail-value">₹<?php echo number_format($tournament['prize_money']); ?></span>
                        </div>
                    </div>
                    
                    <div class="tournament-actions">
                        <button class="btn btn-primary" onclick="viewTournamentDetails('<?php echo htmlspecialchars($tournament['tournament_id']); ?>')">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                        <button class="btn btn-success" onclick="editTournament('<?php echo htmlspecialchars($tournament['tournament_id']); ?>')">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tournament Gallery -->
        <div class="gallery-section">
            <h2 class="gallery-title">
                <i class="fas fa-images"></i> Tournament Gallery
            </h2>
            
            <?php if (!empty($tournamentImages)): ?>
                <div class="gallery-grid">
                    <?php foreach ($tournamentImages as $image): ?>
                        <div class="gallery-item">
                            <img src="<?php echo $image['url']; ?>" alt="<?php echo $image['name']; ?>">
                            <div class="gallery-info">
                                <div class="gallery-name"><?php echo substr($image['name'], 20); ?></div>
                                <div class="gallery-actions">
                                    <button class="btn btn-info btn-small" onclick="window.open('<?php echo $image['url']; ?>', '_blank')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-image">
                    <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 1rem; color: #ddd;"></i>
                    <p>No tournament images uploaded yet.</p>
                    <p>Upload images to uploads/tournaments/ directory.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function viewTournamentDetails(tournamentId) {
            const tournaments = <?php echo json_encode($tournamentData); ?>;
            const tournament = tournaments.find(t => t.tournament_id === tournamentId);
            
            if (tournament) {
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
        }
        
        function editTournament(tournamentId) {
            alert(`Edit Tournament: ${tournamentId}\n\nThis would open the tournament edit form with all details pre-filled.`);
        }
    </script>
</body>
</html>
