<?php
/**
 * Gallery Setup - TCAM
 * Create directory structure for image gallery
 */

echo "<h2>🖼️ TCAM Gallery Setup</h2>";

// Define gallery directories
$galleryDirs = [
    'gallery' => 'Main gallery directory',
    'gallery/thumbnails' => 'Thumbnail images',
    'gallery/fullsize' => 'Full-size images',
    'gallery/events' => 'Event photos',
    'gallery/tournaments' => 'Tournament photos',
    'gallery/students' => 'Student photos',
    'gallery/certificates' => 'Certificate images',
    'gallery/achievements' => 'Achievement photos',
    'gallery/infrastructure' => 'Infrastructure photos'
];

echo "<h3>📁 Creating Gallery Directories</h3>";

foreach ($galleryDirs as $dir => $description) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<p>✅ Created: <strong>$dir</strong> - $description</p>";
        } else {
            echo "<p>❌ Failed to create: $dir</p>";
        }
    } else {
        echo "<p>✅ Already exists: <strong>$dir</strong> - $description</p>";
    }
}

// Create .htaccess for security
$htaccessContent = "
# Gallery Security
Options -Indexes
<Files \"*.php\">
    Deny from all
</Files>

# Allow image access
<FilesMatch \"\.(jpg|jpeg|png|gif|webp|svg)$\">
    Allow from all
</FilesMatch>

# Prevent direct access to thumbnails directory
<Directory \"thumbnails\">
    Options -Indexes
</Directory>
";

if (file_put_contents('gallery/.htaccess', $htaccessContent)) {
    echo "<p>✅ Created security file: gallery/.htaccess</p>";
} else {
    echo "<p>❌ Failed to create .htaccess</p>";
}

// Create index.php to prevent directory listing
$indexContent = "<?php
// Gallery Directory - Access Denied
header('HTTP/1.0 403 Forbidden');
exit('Access Denied');
?>";

foreach (['gallery', 'gallery/thumbnails', 'gallery/fullsize', 'gallery/events', 'gallery/tournaments', 'gallery/students', 'gallery/certificates', 'gallery/achievements', 'gallery/infrastructure'] as $dir) {
    if (is_dir($dir)) {
        if (file_put_contents($dir . '/index.php', $indexContent)) {
            echo "<p>✅ Security file added to: $dir</p>";
        }
    }
}

echo "<h3>📋 Directory Structure Created</h3>";
echo "<pre style='background: #f8f9fa; padding: 1rem; border-radius: 8px;'>";
echo "/Users/admin/Desktop/comp/bootstrap/
├── gallery/
│   ├── .htaccess (security)
│   ├── index.php (security)
│   ├── thumbnails/
│   │   └── index.php (security)
│   ├── fullsize/
│   │   └── index.php (security)
│   ├── events/
│   │   └── index.php (security)
│   ├── tournaments/
│   │   └── index.php (security)
│   ├── students/
│   │   └── index.php (security)
│   ├── certificates/
│   │   └── index.php (security)
│   ├── achievements/
│   │   └── index.php (security)
│   └── infrastructure/
│       └── index.php (security)
└── uploads/ (existing)
</pre>";

echo "<h3>📸 Where to Save Your Images</h3>";
echo "<div style='background: #e8f5e8; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;'>";
echo "<h4>🎯 Main Gallery Images:</h4>";
echo "<p><strong>Path:</strong> <code>gallery/fullsize/</code></p>";
echo "<p><strong>Use for:</strong> General gallery images, hero images, banners</p>";
echo "</div>";

echo "<div style='background: #e8f4fd; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;'>";
echo "<h4>🏆 Tournament Images:</h4>";
echo "<p><strong>Path:</strong> <code>gallery/tournaments/</code></p>";
echo "<p><strong>Use for:</strong> Tournament photos, competition images</p>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;'>";
echo "<h4>🎓 Student Images:</h4>";
echo "<p><strong>Path:</strong> <code>gallery/students/</code></p>";
echo "<p><strong>Use for:</strong> Student photos, group pictures</p>";
echo "</div>";

echo "<div style='background: #f8d7da; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;'>";
echo "<h4>🎪 Event Images:</h4>";
echo "<p><strong>Path:</strong> <code>gallery/events/</code></p>";
echo "<p><strong>Use for:</strong> Event photos, function images</p>";
echo "</div>";

echo "<div style='background: #d1ecf1; padding: 1.5rem; border-radius: 8px; margin-bottom: 1rem;'>";
echo "<h4>🏆 Achievement Images:</h4>";
echo "<p><strong>Path:</strong> <code>gallery/achievements/</code></p>";
echo "<p><strong>Use for:</strong> Awards, certificates, achievements</p>";
echo "</div>";

echo "<h3>🔧 Recommended Image Specifications</h3>";
echo "<div style='background: #f8f9fa; padding: 1.5rem; border-radius: 8px;'>";
echo "<h4>📏 Image Sizes:</h4>";
echo "<ul>";
echo "<li><strong>Full-size images:</strong> 1920x1080px (HD) or 1280x720px</li>";
echo "<li><strong>Thumbnails:</strong> 300x300px or 400x400px (square)</li>";
echo "<li><strong>Banner images:</strong> 1920x400px or 1600x500px</li>";
echo "<li><strong>Gallery images:</strong> 800x600px or 1024x768px</li>";
echo "</ul>";

echo "<h4>📁 File Formats:</h4>";
echo "<ul>";
echo "<li><strong>Photos:</strong> .jpg, .jpeg, .png</li>";
echo "<li><strong>Graphics:</strong> .png, .svg</li>";
echo "<li><strong>Web optimized:</strong> .webp (smaller file size)</li>";
echo "</ul>";

echo "<h4>🏷️ File Naming:</h4>";
echo "<ul>";
echo "<li><strong>Use lowercase:</strong> tournament-2024.jpg</li>";
echo "<li><strong>No spaces:</strong> Use hyphens (-) instead</li>";
echo "<li><strong>Descriptive:</strong> cricket-championship-finals.jpg</li>";
echo "<li><strong>Consistent:</strong> event-name-date.jpg</li>";
echo "</ul>";
echo "</div>";

echo "<h3>🚀 Next Steps</h3>";
echo "<div style='background: #d4edda; padding: 1.5rem; border-radius: 8px;'>";
echo "<ol>";
echo "<li><strong>Copy your images</strong> to the appropriate folders above</li>";
echo "<li><strong>Create thumbnails</strong> (300x300px) and save in gallery/thumbnails/</li>";
echo "<li><strong>Organize by category</strong> (events, tournaments, students, etc.)</li>";
echo "<li><strong>Optimize images</strong> for web (compress if needed)</li>";
echo "<li><strong>Test gallery</strong> once images are uploaded</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>✅ Gallery directories are ready!</strong></p>";
echo "<p>Start adding your images to the folders above.</p>";
?>
