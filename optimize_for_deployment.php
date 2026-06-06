<?php
/**
 * TCAM Deployment Optimization Script
 * Run this script before deploying to GoDaddy to optimize assets and clean up files
 */

echo "TCAM Deployment Optimization Script\n";
echo "===================================\n\n";

// Files and directories to remove for production
$filesToRemove = [
    'settings.json',
    'test.php',
    '.git',
    'optimize_for_deployment.php' // Remove this script itself
];

// Large images that should be compressed (over 200KB)
$largeImages = [];
$imageDir = __DIR__;
$images = glob($imageDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

foreach ($images as $image) {
    $size = filesize($image);
    if ($size > 200 * 1024) { // 200KB
        $largeImages[] = [
            'file' => basename($image),
            'size' => round($size / 1024, 2) . ' KB'
        ];
    }
}

echo "ANALYSIS RESULTS:\n";
echo "-----------------\n";

// Check files to remove
echo "Files/folders that should be removed for production:\n";
foreach ($filesToRemove as $file) {
    if (file_exists($file)) {
        echo "✓ Found: $file (should be removed)\n";
    } else {
        echo "✗ Not found: $file\n";
    }
}

echo "\nLarge images that should be optimized:\n";
if (empty($largeImages)) {
    echo "✓ No large images found\n";
} else {
    foreach ($largeImages as $img) {
        echo "⚠ {$img['file']} - {$img['size']}\n";
    }
}

// Check database file permissions
echo "\nDatabase file check:\n";
if (file_exists('tcam_bookings.db')) {
    $perms = substr(sprintf('%o', fileperms('tcam_bookings.db')), -4);
    echo "✓ Database file exists (permissions: $perms)\n";
} else {
    echo "⚠ Database file not found (will be created automatically)\n";
}

// Check uploads directory
echo "\nUploads directory check:\n";
if (is_dir('uploads')) {
    $perms = substr(sprintf('%o', fileperms('uploads')), -4);
    echo "✓ Uploads directory exists (permissions: $perms)\n";
} else {
    echo "⚠ Uploads directory not found (will be created automatically)\n";
}

// Check configuration
echo "\nConfiguration check:\n";
if (file_exists('config.php')) {
    $config = file_get_contents('config.php');
    if (strpos($config, 'yourdomain.com') !== false) {
        echo "⚠ Domain not updated in config.php - please update SITE_URL\n";
    } else {
        echo "✓ Domain appears to be configured\n";
    }
    
    if (strpos($config, 'tcam2024!') !== false) {
        echo "⚠ Default admin password detected - please change it\n";
    } else {
        echo "✓ Admin password appears to be customized\n";
    }
} else {
    echo "✗ config.php not found\n";
}

echo "\nRECOMMENDATIONS:\n";
echo "----------------\n";

if (!empty($largeImages)) {
    echo "1. Compress these large images before deployment:\n";
    foreach ($largeImages as $img) {
        echo "   - {$img['file']}\n";
    }
    echo "   Use tools like TinyPNG, ImageOptim, or online compressors\n\n";
}

echo "2. Before uploading to GoDaddy:\n";
echo "   - Update domain in config.php\n";
echo "   - Change admin password in config.php\n";
echo "   - Remove development files listed above\n";
echo "   - Test all functionality locally first\n\n";

echo "3. After uploading to GoDaddy:\n";
echo "   - Set file permissions as specified in DEPLOYMENT_GUIDE.md\n";
echo "   - Test website functionality\n";
echo "   - Check error logs if issues occur\n\n";

echo "✓ Optimization analysis complete!\n";
echo "See DEPLOYMENT_GUIDE.md for detailed deployment instructions.\n";
?>
