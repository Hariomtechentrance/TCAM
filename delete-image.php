<?php
/**
 * Delete Gallery Image
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login-production.php');
    exit;
}

$imagePath = $_GET['path'] ?? '';

if (!empty($imagePath) && file_exists($imagePath)) {
    // Delete the image
    if (unlink($imagePath)) {
        // Also try to delete thumbnail
        $thumbnailPath = 'gallery/thumbnails/' . basename($imagePath);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
        
        $_SESSION['success'] = 'Image deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete image.';
    }
} else {
    $_SESSION['error'] = 'Image not found.';
}

header('Location: gallery-manager.php');
exit;
?>
