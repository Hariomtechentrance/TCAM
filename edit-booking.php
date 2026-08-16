<?php
/**
 * Edit Booking - Admin Panel
 * Allows admin to edit a registration that came through the main
 * "Register Now" booking form (bookings table).
 */

require_once 'security-config.php';
session_start();
require_once 'secure-database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-production.php');
    exit;
}

$bookingId = (int)($_GET['id'] ?? 0);
$booking = null;

$db = SecureDatabase::getInstance();
if ($bookingId > 0) {
    $rows = $db->execute("SELECT * FROM bookings WHERE id = ?", [$bookingId])->fetchAll();
    if (!empty($rows)) {
        $booking = $rows[0];
    }
}

if (!$booking) {
    echo "<script>alert('Booking not found!'); window.close();</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $updateData = [
            'name' => Security::sanitizeInput($_POST['name'] ?? ''),
            'phone' => Security::sanitizeInput($_POST['phone'] ?? ''),
            'email' => Security::sanitizeInput($_POST['email'] ?? ''),
            'dob' => Security::sanitizeInput($_POST['dob'] ?? ''),
            'district' => Security::sanitizeInput($_POST['district'] ?? ''),
            'aadhar_number' => Security::sanitizeInput($_POST['aadhar_number'] ?? ''),
            'message' => Security::sanitizeInput($_POST['message'] ?? ''),
        ];

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
            $updateData['photo'] = 'uploads/' . $photoFilename;

            $oldPhoto = preg_replace('#^/?uploads/#', '', $booking['photo'] ?? '');
            if ($oldPhoto !== '') {
                $oldPhotoPath = $uploadDir . $oldPhoto;
                if (file_exists($oldPhotoPath)) {
                    unlink($oldPhotoPath);
                }
            }
        }

        $db->update('bookings', $updateData, ['id' => $bookingId]);

        echo "<script>alert('Booking updated successfully!'); window.opener.location.reload(); window.close();</script>";
        exit;
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$photoFile = preg_replace('#^/?uploads/#', '', $booking['photo'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Booking - TCAM Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 700px; margin: 0 auto; background: rgba(255, 255, 255, 0.95); padding: 2rem; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #764ba2; }
        .header h1 { color: #764ba2; margin-bottom: 0.5rem; }
        .header p { color: #666; font-size: 0.9rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        .form-group { display: flex; flex-direction: column; }
        .form-group.full-width { grid-column: 1 / -1; }
        .form-group label { margin-bottom: 0.5rem; font-weight: 600; color: #333; }
        .form-group input, .form-group textarea { padding: 0.8rem; border: 2px solid #e1e5e9; border-radius: 8px; font-size: 1rem; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #764ba2; }
        .form-group textarea { height: 80px; resize: vertical; }
        .photo-section { text-align: center; margin-bottom: 2rem; }
        .current-photo { width: 120px; height: 120px; object-fit: cover; border-radius: 12px; border: 3px solid #764ba2; margin-bottom: 1rem; }
        .buttons { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; }
        .btn { padding: 0.8rem 2rem; border: none; border-radius: 8px; cursor: pointer; font-size: 1rem; font-weight: 600; }
        .btn-primary { background: #764ba2; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .error { background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid #721c24; }
        @media (max-width: 600px) { .form-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Booking</h1>
            <p>Booking ID: <strong><?php echo htmlspecialchars($booking['bookingId']); ?></strong></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><strong>Error:</strong> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="photo-section">
                <?php if ($photoFile): ?>
                    <img src="uploads/<?php echo htmlspecialchars($photoFile); ?>" alt="Current Photo" class="current-photo">
                <?php endif; ?>
                <div class="form-group">
                    <label for="photo">Update Photo (optional)</label>
                    <input type="file" name="photo" id="photo" accept="image/*">
                </div>
            </div>

            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($booking['name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Mobile Number *</label>
                    <input type="tel" name="phone" id="phone" value="<?php echo htmlspecialchars($booking['phone']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($booking['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth</label>
                    <input type="date" name="dob" id="dob" value="<?php echo htmlspecialchars($booking['dob'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="district">District *</label>
                    <input type="text" name="district" id="district" value="<?php echo htmlspecialchars($booking['district'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="aadhar_number">Aadhar Number</label>
                    <input type="text" name="aadhar_number" id="aadhar_number" value="<?php echo htmlspecialchars($booking['aadhar_number'] ?? ''); ?>">
                </div>
                <div class="form-group full-width">
                    <label for="message">Message / Notes</label>
                    <textarea name="message" id="message"><?php echo htmlspecialchars($booking['message'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="buttons">
                <button type="submit" class="btn btn-primary">Update Booking</button>
                <button type="button" class="btn btn-secondary" onclick="window.close()">Cancel</button>
            </div>
        </form>
    </div>
</body>
</html>
