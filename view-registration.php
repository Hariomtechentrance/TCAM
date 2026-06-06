<?php
/**
 * View Registration - Admin Panel
 * Allows admin to view complete registration details
 */

session_start();
require_once 'security-config.php';
require_once 'secure-database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-production.php');
    exit;
}

$registrationId = (int)($_GET['id'] ?? 0);
$registration = null;

if ($registrationId > 0) {
    $db = SecureDatabase::getInstance();
    $registrations = $db->execute("SELECT * FROM registrations WHERE id = ?", [$registrationId])->fetchAll();
    if (!empty($registrations)) {
        $registration = $registrations[0];
    }
}

if (!$registration) {
    echo "<script>alert('Registration not found!'); window.close();</script>";
    exit;
}

// Get event registrations for this student
$eventRegistrations = $db->execute("SELECT * FROM event_registrations WHERE registration_id = ? ORDER BY created_at DESC", [$registrationId])->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Registration - TCAM Admin</title>
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
            padding: 20px;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #764ba2;
        }
        
        .header h1 {
            color: #764ba2;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #666;
            font-size: 0.9rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .section {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #764ba2;
        }
        
        .section h2 {
            color: #764ba2;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .info-item {
            margin-bottom: 1rem;
        }
        
        .info-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            color: #333;
            font-size: 1rem;
        }
        
        .photo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .photo {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid #764ba2;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
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
        
        .events-section {
            margin-top: 2rem;
        }
        
        .event-list {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #27ae60;
        }
        
        .event-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 3px solid #27ae60;
        }
        
        .event-name {
            font-weight: 600;
            color: #27ae60;
            margin-bottom: 0.5rem;
        }
        
        .event-date {
            color: #666;
            font-size: 0.875rem;
        }
        
        .buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.8rem 2rem;
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
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Registration Details</h1>
            <p>TCAM ID: <strong><?php echo htmlspecialchars($registration['reg_id']); ?></strong></p>
        </div>

        <div class="photo-section">
            <?php if ($registration['photo']): ?>
                <img src="uploads/<?php echo htmlspecialchars($registration['photo']); ?>" 
                     alt="Student Photo" class="photo">
            <?php else: ?>
                <div class="photo" style="background: #e1e5e9; display: flex; align-items: center; justify-content: center; color: #999; font-size: 3rem;">
                    <i class="fas fa-user"></i>
                </div>
            <?php endif; ?>
            
            <div class="status-badge status-<?php echo $registration['status'] ?? 'active'; ?>">
                <?php echo ucfirst($registration['status'] ?? 'Active'); ?>
            </div>
        </div>

        <div class="content-grid">
            <div class="section">
                <h2><i class="fas fa-user"></i> Personal Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['date_of_birth'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Blood Group</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['blood_group'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Parent/Guardian</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['parent_name'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2><i class="fas fa-id-card"></i> Contact Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Mobile Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['mobile']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['email'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Emergency Contact</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['emergency_contact'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Full Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['address'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2><i class="fas fa-map-marker-alt"></i> Location Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">City</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['city']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">State</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['state']); ?></div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2><i class="fas fa-file-alt"></i> Document Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Document Type</div>
                        <div class="info-value"><?php echo ucfirst($registration['document_type'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Document Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['document_number'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            </div>

            <div class="section">
                <h2><i class="fas fa-calendar-alt"></i> Registration Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">TCAM ID</div>
                        <div class="info-value"><strong><?php echo htmlspecialchars($registration['reg_id']); ?></strong></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Registration Date</div>
                        <div class="info-value"><?php echo date('d M Y', strtotime($registration['joined'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Previous Tournaments</div>
                        <div class="info-value"><?php echo htmlspecialchars($registration['previous_tournaments'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Created On</div>
                        <div class="info-value"><?php echo date('d M Y H:i', strtotime($registration['created_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($eventRegistrations)): ?>
            <div class="events-section">
                <div class="event-list">
                    <h2><i class="fas fa-trophy"></i> Tournament Registrations</h2>
                    <?php foreach ($eventRegistrations as $event): ?>
                        <div class="event-item">
                            <div class="event-name"><?php echo htmlspecialchars($event['event_name']); ?></div>
                            <div class="event-date">
                                Registered on: <?php echo date('d M Y H:i', strtotime($event['created_at'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="buttons">
            <a href="edit-registration.php?id=<?php echo $registrationId; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> Edit Registration
            </a>
            <a href="student-search.php?search_type=reg_id&search_term=<?php echo urlencode($registration['reg_id']); ?>" 
               target="_blank" class="btn btn-success">
                <i class="fas fa-download"></i> Download Data
            </a>
            <button onclick="window.close()" class="btn btn-warning">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
    </div>
</body>
</html>
