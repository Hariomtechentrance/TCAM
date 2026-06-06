<?php
/**
 * Edit Registration - Admin Panel
 * Allows admin to edit student registration details
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = SecureDatabase::getInstance();
        
        $updateData = [
            'name' => Security::sanitizeInput($_POST['name'] ?? ''),
            'mobile' => Security::sanitizeInput($_POST['mobile'] ?? ''),
            'email' => Security::sanitizeInput($_POST['email'] ?? ''),
            'city' => Security::sanitizeInput($_POST['city'] ?? ''),
            'state' => Security::sanitizeInput($_POST['state'] ?? ''),
            'date_of_birth' => Security::sanitizeInput($_POST['date_of_birth'] ?? ''),
            'document_type' => Security::sanitizeInput($_POST['document_type'] ?? ''),
            'document_number' => Security::sanitizeInput($_POST['document_number'] ?? ''),
            'address' => Security::sanitizeInput($_POST['address'] ?? ''),
            'parent_name' => Security::sanitizeInput($_POST['parent_name'] ?? ''),
            'emergency_contact' => Security::sanitizeInput($_POST['emergency_contact'] ?? ''),
            'blood_group' => Security::sanitizeInput($_POST['blood_group'] ?? ''),
            'joined' => Security::sanitizeInput($_POST['joined'] ?? ''),
            'previous_tournaments' => Security::sanitizeInput($_POST['previous_tournaments'] ?? ''),
            'status' => Security::sanitizeInput($_POST['status'] ?? 'active')
        ];
        
        // Handle photo upload if new photo provided
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $photoValidation = Security::validateFileUpload($_FILES['photo'], ['jpg', 'jpeg', 'png'], 2097152);
            if (!$photoValidation['success']) {
                throw new Exception($photoValidation['error']);
            }
            
            $uploadDir = __DIR__ . '/uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $photoFilename = Security::generateSecureFilename($_FILES['photo']['name']);
            $photoPath = $uploadDir . $photoFilename;
            
            if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photoPath)) {
                throw new Exception('Failed to save photo.');
            }
            
            chmod($photoPath, 0644);
            $updateData['photo'] = $photoFilename;
            
            // Delete old photo if exists
            if (!empty($registration['photo'])) {
                $oldPhotoPath = $uploadDir . $registration['photo'];
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
        }
        
        // Update registration
        $db->update('registrations', $updateData, ['id' => $registrationId]);
        
        // Log the action
        Security::logEvent('REGISTRATION_UPDATED', [
            'registration_id' => $registrationId,
            'reg_id' => $registration['reg_id'],
            'admin_id' => $_SESSION['admin_id'] ?? 'unknown'
        ]);
        
        echo "<script>alert('Registration updated successfully!'); window.opener.location.reload(); window.close();</script>";
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Registration - TCAM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            max-width: 800px;
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
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .photo-section {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .current-photo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 12px;
            border: 3px solid #764ba2;
            margin-bottom: 1rem;
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
        }
        
        .btn-primary {
            background: #764ba2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a3785;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
            transform: translateY(-2px);
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #721c24;
        }
        
        @media (max-width: 768px) {
            .form-grid {
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
            <h1>Edit Registration</h1>
            <p>TCAM ID: <strong><?php echo htmlspecialchars($registration['reg_id']); ?></strong></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="photo-section">
                <?php if ($registration['photo']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($registration['photo']); ?>" 
                         alt="Current Photo" class="current-photo">
                <?php endif; ?>
                <div class="form-group">
                    <label for="photo">Update Photo (optional)</label>
                    <input type="file" name="photo" id="photo" accept="image/*">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" name="name" id="name" 
                           value="<?php echo htmlspecialchars($registration['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="mobile">Mobile Number *</label>
                    <input type="tel" name="mobile" id="mobile" 
                           value="<?php echo htmlspecialchars($registration['mobile']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" 
                           value="<?php echo htmlspecialchars($registration['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status">
                        <option value="active" <?php echo ($registration['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="cancelled" <?php echo ($registration['status'] ?? '') === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="pending" <?php echo ($registration['status'] ?? '') === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="city">City *</label>
                    <input type="text" name="city" id="city" 
                           value="<?php echo htmlspecialchars($registration['city']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="state">State *</label>
                    <input type="text" name="state" id="state" 
                           value="<?php echo htmlspecialchars($registration['state']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="date_of_birth">Date of Birth</label>
                    <input type="date" name="date_of_birth" id="date_of_birth" 
                           value="<?php echo htmlspecialchars($registration['date_of_birth'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="document_type">Document Type</label>
                    <select name="document_type" id="document_type">
                        <option value="">Select Document</option>
                        <option value="aadhar" <?php echo ($registration['document_type'] ?? '') === 'aadhar' ? 'selected' : ''; ?>>Aadhar Card</option>
                        <option value="pan" <?php echo ($registration['document_type'] ?? '') === 'pan' ? 'selected' : ''; ?>>PAN Card</option>
                        <option value="voter" <?php echo ($registration['document_type'] ?? '') === 'voter' ? 'selected' : ''; ?>>Voter ID</option>
                        <option value="passport" <?php echo ($registration['document_type'] ?? '') === 'passport' ? 'selected' : ''; ?>>Passport</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="document_number">Document Number</label>
                    <input type="text" name="document_number" id="document_number" 
                           value="<?php echo htmlspecialchars($registration['document_number'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="parent_name">Parent/Guardian Name</label>
                    <input type="text" name="parent_name" id="parent_name" 
                           value="<?php echo htmlspecialchars($registration['parent_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="emergency_contact">Emergency Contact</label>
                    <input type="tel" name="emergency_contact" id="emergency_contact" 
                           value="<?php echo htmlspecialchars($registration['emergency_contact'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="blood_group">Blood Group</label>
                    <select name="blood_group" id="blood_group">
                        <option value="">Select Blood Group</option>
                        <option value="A+" <?php echo ($registration['blood_group'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo ($registration['blood_group'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo ($registration['blood_group'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo ($registration['blood_group'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                        <option value="O+" <?php echo ($registration['blood_group'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo ($registration['blood_group'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                        <option value="AB+" <?php echo ($registration['blood_group'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo ($registration['blood_group'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="joined">Registration Date</label>
                    <input type="date" name="joined" id="joined" 
                           value="<?php echo htmlspecialchars($registration['joined']); ?>">
                </div>

                <div class="form-group full-width">
                    <label for="address">Full Address</label>
                    <textarea name="address" id="address"><?php echo htmlspecialchars($registration['address'] ?? ''); ?></textarea>
                </div>

                <div class="form-group full-width">
                    <label for="previous_tournaments">Previous Tournaments</label>
                    <textarea name="previous_tournaments" id="previous_tournaments"><?php echo htmlspecialchars($registration['previous_tournaments'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Registration
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.close()">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</body>
</html>
