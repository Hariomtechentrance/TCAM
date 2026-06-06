<?php
/**
 * Dynamic Gallery Page — TCAM
 * Theme-matched with main website (white/orange/light)
 */
$dbPath = __DIR__ . '/tcam_bookings.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->exec("CREATE TABLE IF NOT EXISTS gallery_images (
    id INTEGER PRIMARY KEY AUTOINCREMENT, filename TEXT NOT NULL, original_name TEXT,
    category TEXT DEFAULT 'general', tournament_id INTEGER DEFAULT NULL,
    caption TEXT DEFAULT '', uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$dbImages = $db->query("SELECT * FROM gallery_images ORDER BY uploaded_at DESC")->fetchAll();
$categories = $db->query("SELECT category, COUNT(*) as cnt FROM gallery_images GROUP BY category ORDER BY cnt DESC")->fetchAll();

// Scan existing static images
$staticImages = [];
$exts = ['jpg','jpeg','png','gif','webp'];
$dir = __DIR__;
$exclude = ['logo.png','Meenakshi_Maam.jpg','mayuri-giri.jpg','Ghadigaonkar2.jpg','Facebook.webp','Instagram.webp','Youtube.webp'];
foreach (scandir($dir) as $f) {
    if ($f[0] === '.') continue;
    $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
    if (in_array($ext, $exts) && !in_array($f, $exclude) && (strpos($f, 'IMG-') === 0 || preg_match('/^\d+\.jpeg$/', $f))) {
        $staticImages[] = $f;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - TCAM</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            color: #222;
        }
        .container {
            max-width: 1200px;
            margin: 60px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(0,0,0,0.08);
            padding: 40px 24px 32px 24px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 24px;
            color: #ff6b35;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .back-link:hover { text-decoration: underline; }
        h1 {
            color: #ff6b35;
            text-align: center;
            margin-bottom: 8px;
            font-size: 2.2rem;
        }
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 28px;
        }

        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin-bottom: 28px;
        }
        .filter-tab {
            padding: 8px 18px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            background: #f8f9fa;
            border: 1px solid #eee;
            color: #666;
            transition: all 0.3s;
        }
        .filter-tab:hover { background: #fff0e6; border-color: #ff6b35; color: #ff6b35; }
        .filter-tab.active { background: linear-gradient(45deg, #ff6b35, #f7931e); border-color: #ff6b35; color: #fff; }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
        }
        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            cursor: pointer;
            background: #eee;
            transition: all 0.3s;
        }
        .gallery-item:hover { transform: scale(1.04); box-shadow: 0 6px 20px rgba(0,0,0,0.15); z-index: 2; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.4s; }
        .gallery-item:hover img { transform: scale(1.08); }
        .gallery-item .overlay {
            position: absolute; bottom: 0; left: 0; right: 0; padding: 6px 8px;
            background: linear-gradient(transparent, rgba(0,0,0,0.6));
            opacity: 0; transition: opacity 0.3s;
            font-size: 0.7rem; color: #fff;
        }
        .gallery-item:hover .overlay { opacity: 1; }

        /* Lightbox */
        .lightbox {
            display: none; position: fixed; z-index: 2000; top: 0; left: 0;
            width: 100vw; height: 100vh; background: rgba(0,0,0,0.88);
            justify-content: center; align-items: center;
        }
        .lightbox.active { display: flex; animation: fadeIn 0.3s; }
        @keyframes fadeIn { from{opacity:0} to{opacity:1} }
        .lightbox img { max-width: 90vw; max-height: 85vh; border-radius: 12px; box-shadow: 0 12px 40px rgba(0,0,0,0.4); animation: popIn 0.4s cubic-bezier(.4,0,.2,1); }
        @keyframes popIn { from{transform:scale(0.9);opacity:0} to{transform:scale(1);opacity:1} }
        .lightbox .close { position: absolute; top: 20px; right: 28px; font-size: 2.5rem; color: #fff; cursor: pointer; transition: color 0.2s; }
        .lightbox .close:hover { color: #ff6b35; }
        .lightbox .nav-btn { position: absolute; top: 50%; transform: translateY(-50%); font-size: 2.5rem; color: #fff; cursor: pointer; padding: 16px; opacity: 0.7; transition: opacity 0.2s; }
        .lightbox .nav-btn:hover { opacity: 1; }
        .lightbox .prev { left: 16px; }
        .lightbox .next { right: 16px; }

        .section-title { font-size: 1.1rem; font-weight: 700; margin: 28px 0 14px; color: #764ba2; display: flex; align-items: center; gap: 8px; }

        @media(max-width:900px) { .gallery-grid { grid-template-columns: repeat(3, 1fr); } }
        @media(max-width:600px) { .gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 8px; } }
    </style>
    <link rel="stylesheet" href="css/responsive.css">
</head>
<body>
    <div class="container">
        <a href="index.html" class="back-link">&larr; Back to Home</a>
        <h1>📸 Gallery</h1>
        <p class="subtitle">Moments from our tournaments and events</p>

        <!-- All photos in one unified grid — no "Archive" label shown to visitors -->
        <div class="gallery-grid" id="mainGallery">
            <?php foreach ($dbImages as $img): ?>
                <div class="gallery-item" data-category="<?php echo htmlspecialchars($img['category']); ?>">
                    <img src="uploads/gallery/<?php echo htmlspecialchars($img['filename']); ?>" alt="<?php echo htmlspecialchars($img['caption'] ?: $img['original_name']); ?>" loading="lazy">
                    <?php if ($img['caption']): ?>
                        <div class="overlay"><?php echo htmlspecialchars($img['caption']); ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            <?php foreach ($staticImages as $f): ?>
                <div class="gallery-item" data-category="photo">
                    <img src="<?php echo htmlspecialchars($f); ?>" alt="" loading="lazy">
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Lightbox -->
    <div class="lightbox" id="lightbox">
        <span class="close" onclick="closeLightbox()">&times;</span>
        <span class="nav-btn prev" onclick="navigateLightbox(-1)">&#8249;</span>
        <img src="" alt="Gallery">
        <span class="nav-btn next" onclick="navigateLightbox(1)">&#8250;</span>
    </div>

    <script>
        const allImgs = Array.from(document.querySelectorAll('#mainGallery .gallery-item img'));
        let currentIndex = 0;

        allImgs.forEach((img, i) => {
            img.addEventListener('click', function() {
                currentIndex = i;
                document.querySelector('#lightbox img').src = this.src;
                document.getElementById('lightbox').classList.add('active');
            });
        });

        function closeLightbox() {
            document.getElementById('lightbox').classList.remove('active');
        }

        function navigateLightbox(dir) {
            currentIndex = (currentIndex + dir + allImgs.length) % allImgs.length;
            document.querySelector('#lightbox img').src = allImgs[currentIndex].src;
        }

        document.getElementById('lightbox').addEventListener('click', function(e) {
            if (e.target === this) closeLightbox();
        });

        document.addEventListener('keydown', e => {
            if (!document.getElementById('lightbox').classList.contains('active')) return;
            if (e.key === 'Escape') closeLightbox();
            if (e.key === 'ArrowLeft') navigateLightbox(-1);
            if (e.key === 'ArrowRight') navigateLightbox(1);
        });
    </script>

    <footer class="site-footer">
        <div class="footer-inner">
            <div>
                <div class="footer-logo-row">
                    <img src="./images/logo.png" alt="TCAM Logo">
                    <span class="footer-brand-name">TCAM</span>
                </div>
                <p class="footer-tagline">Tennis Cricket Association of Maharashtra — Affiliated to Tennis Cricket Association of India. Nurturing champions, building futures.</p>
            </div>
            <div>
                <h4 class="footer-heading">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="index.html">Home</a></li>
                    <li><a href="about.html">About Us</a></li>
                    <li><a href="managment.html">Management</a></li>
                    <li><a href="tournament.php">Tournaments</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="contact.html">Contact Us</a></li>
                </ul>
            </div>
            <div>
                <h4 class="footer-heading">Get Involved</h4>
                <ul class="footer-links">
                    <li><a href="index.html#booking">Register as Player</a></li>
                    <li><a href="single-registration-enhanced.html">Update Registration</a></li>
                    <li><a href="student-search.php">Search Registration</a></li>
                </ul>
            </div>
            <div>
                <h4 class="footer-heading">Follow Us</h4>
                <div class="footer-social">
                    <a href="https://www.facebook.com/share/173JkY8Mw4/" target="_blank" class="social-link">
                        <img src="Facebook.webp" alt="Facebook"> Facebook
                    </a>
                    <a href="https://www.instagram.com/tenniscricketmaharashtra?igsh=anphb3MyZDExdTdu" target="_blank" class="social-link">
                        <img src="Instagram.webp" alt="Instagram"> Instagram
                    </a>
                    <a href="https://youtube.com/@tenniscricketmaharashtra?si=pVYR1NoF-RY67Toy" target="_blank" class="social-link">
                        <img src="Youtube.webp" alt="YouTube"> YouTube
                    </a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            &copy; 2024 Tennis Cricket Association of Maharashtra (TCAM). All rights reserved.
        </div>
    </footer>
</body>
</html>
