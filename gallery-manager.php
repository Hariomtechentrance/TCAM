<?php
/**
 * Gallery Manager - TCAM
 * Upload and manage gallery images
 */

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login-production.php');
    exit;
}

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = $_POST['category'] ?? 'gallery/fullsize';
    $thumbnailDir = 'gallery/thumbnails';
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    if (!is_dir($thumbnailDir)) {
        mkdir($thumbnailDir, 0755, true);
    }
    
    $file = $_FILES['image'];
    $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9.-]/', '_', $file['name']);
    $targetFile = $uploadDir . '/' . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        $success = "Image uploaded successfully!";
        
        // Create thumbnail (simple version)
        if (function_exists('gd_info')) {
            createThumbnail($targetFile, $thumbnailDir . '/' . $fileName);
        }
    } else {
        $error = "Failed to upload image.";
    }
}

function createThumbnail($source, $target) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) return false;
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $type = $imageInfo[2];
    
    // Calculate new dimensions
    $newWidth = 300;
    $newHeight = 300;
    
    if ($width > $height) {
        $newHeight = ($height / $width) * $newWidth;
    } else {
        $newWidth = ($width / $height) * $newHeight;
    }
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $sourceImg = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $sourceImg = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $sourceImg = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Create thumbnail
    $thumbImg = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($thumbImg, $sourceImg, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save thumbnail
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($thumbImg, $target, 85);
            break;
        case IMAGETYPE_PNG:
            imagepng($thumbImg, $target, 8);
            break;
        case IMAGETYPE_GIF:
            imagegif($thumbImg, $target);
            break;
    }
    
    imagedestroy($sourceImg);
    imagedestroy($thumbImg);
    return true;
}

// Get existing images
$galleryImages = [];
$categories = [
    'gallery/fullsize' => 'Main Gallery',
    'gallery/tournaments' => 'Tournaments',
    'gallery/events' => 'Events',
    'gallery/students' => 'Students',
    'gallery/achievements' => 'Achievements',
    'gallery/certificates' => 'Certificates',
    'gallery/infrastructure' => 'Infrastructure'
];

foreach ($categories as $dir => $name) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && $file !== 'index.php') {
                $galleryImages[$name][] = [
                    'path' => $dir . '/' . $file,
                    'name' => $file,
                    'url' => $dir . '/' . $file,
                    'thumbnail' => 'gallery/thumbnails/' . $file
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>TCAM Gallery Manager</title>
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
            padding: 1.5rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #764ba2;
            margin-bottom: 0.5rem;
        }
        
        .upload-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #764ba2;
        }
        
        .btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
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
        
        .gallery-section {
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
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .gallery-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        
        .gallery-item-info {
            padding: 1rem;
        }
        
        .gallery-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            word-break: break-all;
        }
        
        .gallery-item-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-small {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border: none;
            border-radius: 4px;
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
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
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
        
        .nav-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        .nav-links a {
            background: rgba(255, 255, 255, 0.95);
            color: #764ba2;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .nav-links a:hover {
            background: #764ba2;
            color: white;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
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
                <i class="fas fa-images"></i> TCAM Gallery Manager
            </div>
            <div style="color: #666; font-size: 0.9rem;">
                Upload and manage your gallery images
            </div>
        </div>

        <div class="nav-links">
            <a href="admin-simple.php">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
            <a href="gallery-manager.php">
                <i class="fas fa-images"></i> Gallery Manager
            </a>
            <a href="login-production.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>

        <?php if (isset($success)): ?>
            <div class="success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="upload-section">
            <h2 class="section-title">
                <i class="fas fa-upload"></i> Upload New Image
            </h2>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="category">Category</label>
                    <select id="category" name="category" required>
                        <?php foreach ($categories as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="image">Choose Image</label>
                    <input type="file" id="image" name="image" accept="image/*" required>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-upload"></i> Upload Image
                </button>
            </form>
        </div>

        <?php foreach ($categories as $dir => $name): ?>
            <?php if (isset($galleryImages[$name]) && !empty($galleryImages[$name])): ?>
                <div class="gallery-section">
                    <h2 class="section-title">
                        <i class="fas fa-folder"></i> <?php echo $name; ?>
                        <span style="font-size: 0.8rem; color: #666; margin-left: 0.5rem;">
                            (<?php echo count($galleryImages[$name]); ?> images)
                        </span>
                    </h2>
                    
                    <div class="gallery-grid">
                        <?php foreach ($galleryImages[$name] as $image): ?>
                            <div class="gallery-item">
                                <img src="<?php echo $image['url']; ?>" alt="<?php echo $image['name']; ?>">
                                <div class="gallery-item-info">
                                    <div class="gallery-item-name"><?php echo $image['name']; ?></div>
                                    <div class="gallery-item-actions">
                                        <button class="btn-small btn-view" onclick="window.open('<?php echo $image['url']; ?>', '_blank')">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                        <button class="btn-small btn-delete" onclick="if(confirm('Delete this image?')) { window.location.href='delete-image.php?path=<?php echo urlencode($image['path']); ?>'; }">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>

        <?php if (empty(array_filter($galleryImages))): ?>
            <div class="gallery-section">
                <div style="text-align: center; padding: 3rem; color: #666;">
                    <i class="fas fa-images" style="font-size: 3rem; margin-bottom: 1rem; color: #764ba2;"></i>
                    <h3>No Images Yet</h3>
                    <p>Upload your first image using the form above.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
