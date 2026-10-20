<?php
/**
 * Dynamic Tournament Page — TCAM
 * Theme-matched with main website (white/orange/light)
 */
$dbPath = getenv('TCAM_DB_PATH') ?: __DIR__ . '/tcam_bookings.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

$db->exec("CREATE TABLE IF NOT EXISTS tournaments (
    id INTEGER PRIMARY KEY AUTOINCREMENT, tournament_id TEXT UNIQUE, name TEXT NOT NULL,
    start_date TEXT, end_date TEXT, venue TEXT, city TEXT, state TEXT DEFAULT 'Maharashtra',
    participants INTEGER DEFAULT 0, status TEXT DEFAULT 'upcoming', winner TEXT DEFAULT '',
    runner_up TEXT DEFAULT '', organizer TEXT DEFAULT '', contact_person TEXT DEFAULT '',
    contact_mobile TEXT DEFAULT '', prize_money TEXT DEFAULT '', description TEXT DEFAULT '',
    image_path TEXT DEFAULT '', featured INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$tournaments = $db->query("SELECT * FROM tournaments ORDER BY CASE status WHEN 'ongoing' THEN 1 WHEN 'upcoming' THEN 2 WHEN 'completed' THEN 3 END, start_date DESC")->fetchAll();
$featured = null;
foreach ($tournaments as $t) {
    if ($t['featured'] || $t['status'] === 'ongoing') { $featured = $t; break; }
}
if (!$featured && !empty($tournaments)) $featured = $tournaments[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tournaments - TCAM</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Poppins:wght@600;700;800;900&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: #0B0F14;
            margin: 0;
            color: #F2F4F7;
        }
        .container {
            max-width: 1100px;
            margin: -40px auto 0 auto;
            position: relative;
            z-index: 2;
            background: #141A22;
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            padding: 40px 24px 40px 24px;
            animation: tcamContainerIn 0.7s cubic-bezier(.4,0,.2,1) both;
        }
        @keyframes tcamContainerIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .back-link {
            display: inline-block;
            margin-bottom: 24px;
            color: #FF7A1A;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .back-link:hover { text-decoration: underline; }
        .subtitle {
            text-align: center;
            color: #8A94A6;
            margin-bottom: 36px;
            font-size: 1rem;
        }

        /* Featured */
        .featured {
            background: linear-gradient(135deg, #1a150f, #241a10);
            border: 2px solid rgba(255,122,26,0.25);
            border-radius: 16px;
            padding: 36px 28px;
            margin-bottom: 36px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .featured:hover {
            box-shadow: 0 8px 32px rgba(255,122,26,0.2);
            transform: translateY(-3px);
        }
        .featured .tag {
            display: inline-block;
            background: linear-gradient(45deg, #FF7A1A, #ff9a4d);
            color: #fff;
            padding: 5px 18px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 14px;
        }
        .featured h2 {
            font-family: 'Poppins', Arial, sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: #F2F4F7;
        }
        .featured .desc {
            color: #C4CAD4;
            max-width: 600px;
            margin: 0 auto 18px;
            font-size: 0.95rem;
        }
        .featured-meta {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 32px;
            margin: 20px 0;
        }
        .featured-meta div { text-align: center; }
        .featured-meta .icon { font-size: 1.5rem; margin-bottom: 4px; }
        .featured-meta .label { font-size: 0.72rem; color: #8A94A6; text-transform: uppercase; letter-spacing: 0.5px; }
        .featured-meta .value { font-weight: 600; font-size: 0.92rem; color: #F2F4F7; }
        .btn-register {
            display: inline-block;
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            background: linear-gradient(90deg, #FF7A1A, #ff9a4d);
            color: #fff;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(255,107,53,0.28);
            transition: all 0.3s;
            margin-top: 16px;
        }
        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 32px rgba(255,107,53,0.4);
        }

        /* Grid */
        .grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: 20px; }
        .card {
            background: #1a222c;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 24px 20px;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.35);
            border-color: #FF7A1A;
        }
        .card .status-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 16px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            width: fit-content;
        }
        .status-upcoming { background: rgba(255,154,77,0.15); color: #ff9a4d; }
        .status-ongoing { background: rgba(243,156,18,0.15); color: #ffb84d; }
        .status-completed { background: rgba(39,174,96,0.18); color: #4ade80; }
        .card h3 { font-family: 'Poppins', Arial, sans-serif; font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; color: #F2F4F7; }
        .card .meta { font-size: 0.85rem; color: #8A94A6; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .card .footer { margin-top: auto; padding-top: 14px; display: flex; justify-content: space-between; align-items: center; }
        .card .apply-btn {
            padding: 8px 18px;
            background: linear-gradient(45deg, #FF7A1A, #ff9a4d);
            color: #fff;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.82rem;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        .card .apply-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255,107,53,0.25); }
        .empty { text-align: center; padding: 60px; color: #8A94A6; }
        .empty i { font-size: 2.5rem; margin-bottom: 12px; display: block; }

        /* Featured Image */
        .featured-image {
            width: 100%;
            max-width: 500px;
            margin: 0 auto 20px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .featured-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.4s;
        }
        .featured-image:hover img {
            transform: scale(1.03);
        }

        /* Card Image */
        .card-image {
            width: 100%;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .card-image img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
            transition: transform 0.4s;
        }
        .card:hover .card-image img {
            transform: scale(1.05);
        }

        @media(max-width:600px) { .grid { grid-template-columns: 1fr; } .featured { padding: 20px 16px; } .featured-image { max-width: 100%; } }
    </style>
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/theme.css">
</head>
<body class="tcam-dark-bg">
    <!-- Header -->
    <header class="site-header">
        <div class="nav-overlay" id="navOverlay" onclick="closeMenu()"></div>
        <div class="nav-container">
            <button class="hamburger" id="hamburger" aria-label="Open menu" onclick="toggleMenu()">
                <span></span><span></span><span></span>
            </button>

            <a href="index.html" class="logo">
                <img src="images/logo.png" alt="TCAM Logo" style="height:40px;vertical-align:middle;margin-right:10px;">
                TCAM
            </a>
            <nav>
                <ul class="nav-menu" id="navMenu">
                    <li><a href="index.html"                   onclick="closeMenu()">Home</a></li>
                    <li><a href="about.html"                   onclick="closeMenu()">About Us</a></li>
                    <li><a href="managment.html"               onclick="closeMenu()">Management</a></li>
                    <li><a href="contact.html"                 onclick="closeMenu()">Contact</a></li>
                    <li><a href="tournament.php"               onclick="closeMenu()">Tournament</a></li>
                    <li><a href="gallery.php"                  onclick="closeMenu()">Gallery</a></li>
                    <li><a href="student-search.php"           onclick="closeMenu()">🔍 Search Data</a></li>
                    <li><a href="single-registration-enhanced.html" onclick="closeMenu()">Update Registration</a></li>
                    <!-- Mobile-only buttons inside drawer -->
                    <li class="show-mobile-only mobile-register-row" style="border-bottom:none;margin-top:0.5rem;">
                        <a href="index.html#booking" style="display:block;text-align:center;width:100%;border-radius:8px;background:#D8F84A;color:#0B0F14;text-decoration:none;padding:0.7rem 1.5rem;font-weight:800;text-transform:uppercase;" onclick="closeMenu()">Register Now</a>
                    </li>
                    <li class="show-mobile-only mobile-register-row" style="border-bottom:none;margin-top:0.6rem;">
                        <a href="index.html" style="display:block;text-align:center;width:100%;border-radius:8px;background:#FF7A1A;color:#fff;text-decoration:none;padding:0.7rem 1.5rem;font-weight:bold;" onclick="closeMenu()">🏏 Register as Coach</a>
                    </li>
                    <li class="show-mobile-only mobile-register-row" style="border-bottom:none;margin-top:0.6rem;">
                        <a href="coach-login.php" style="display:block;text-align:center;width:100%;border-radius:8px;background:#312e81;color:#fff;text-decoration:none;padding:0.6rem 1rem;font-weight:600;" onclick="closeMenu()">🔑 Coach Login</a>
                    </li>
                </ul>
            </nav>
            <div style="display:flex;gap:0.6rem;align-items:center;flex-shrink:0;">
                <a href="index.html#booking" class="register-btn header-register-btn" style="text-decoration:none;display:inline-flex;align-items:center;">Register Now</a>
                <a href="index.html" class="register-btn header-register-btn" style="background:#FF7A1A;text-decoration:none;display:inline-flex;align-items:center;">Register as Coach</a>
                <a href="coach-login.php" class="register-btn header-register-btn" style="background:#312e81;display:inline-flex;align-items:center;text-decoration:none;">Coach Login</a>
            </div>
        </div>
    </header>

    <div class="tcam-hero">
        <a href="index.html" class="tcam-hero-back">&larr; Back to Home</a>
        <span class="tcam-hero-eyebrow">Compete &amp; Win</span>
        <h1>🏆 Tournaments</h1>
        <p>Compete in premier tennis cricket events across Maharashtra</p>
    </div>

    <div class="container">

        <?php if ($featured): ?>
        <div class="featured">
            <div class="tag">🏆 <?php echo $featured['status'] === 'ongoing' ? 'Live Now' : ($featured['status'] === 'upcoming' ? 'Featured Tournament' : 'Latest Tournament'); ?></div>
            <?php if ($featured['image_path'] && file_exists(__DIR__ . '/' . $featured['image_path'])): ?>
                <div class="featured-image">
                    <img src="<?php echo htmlspecialchars($featured['image_path']); ?>" alt="<?php echo htmlspecialchars($featured['name']); ?>">
                </div>
            <?php endif; ?>
            <h2><?php echo htmlspecialchars($featured['name']); ?></h2>
            <?php if ($featured['description']): ?>
                <p class="desc"><?php echo htmlspecialchars($featured['description']); ?></p>
            <?php endif; ?>
            <div class="featured-meta">
                <div><div class="icon"></div><div class="label">Date</div><div class="value"><?php echo $featured['start_date'] ? date('d M Y', strtotime($featured['start_date'])) : 'TBD'; ?><?php echo $featured['end_date'] ? ' – ' . date('d M Y', strtotime($featured['end_date'])) : ''; ?></div></div>
                <div><div class="icon"></div><div class="label">Venue</div><div class="value"><?php echo htmlspecialchars($featured['venue'] ?: 'TBD'); ?><?php echo $featured['city'] ? ', ' . htmlspecialchars($featured['city']) : ''; ?></div></div>
                <?php if ($featured['contact_mobile']): ?>
                <div><div class="icon">📞</div><div class="label">Contact</div><div class="value"><?php echo htmlspecialchars($featured['contact_mobile']); ?></div></div>
                <?php endif; ?>
                <?php if ($featured['participants']): ?>
                <div><div class="icon">👥</div><div class="label">Teams</div><div class="value"><?php echo $featured['participants']; ?></div></div>
                <?php endif; ?>
            </div>
            <a href="single-registration-enhanced.html?event=<?php echo urlencode($featured['name']); ?>" class="btn-register">Register Now</a>
        </div>
        <?php endif; ?>

        <?php if (empty($tournaments)): ?>
            <div class="empty">
                <i> </i>
                <h3>No tournaments yet</h3>
                <p>Check back soon for exciting tournament announcements!</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($tournaments as $t): ?>
                    <?php if ($featured && $t['id'] === $featured['id']) continue; ?>
                    <div class="card reveal">
                        <?php if ($t['image_path'] && file_exists(__DIR__ . '/' . $t['image_path'])): ?>
                            <div class="card-image">
                                <img src="<?php echo htmlspecialchars($t['image_path']); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>">
                            </div>
                        <?php endif; ?>
                        <span class="status-tag status-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span>
                        <h3><?php echo htmlspecialchars($t['name']); ?></h3>
                        <div class="meta"><span></span> <?php echo $t['start_date'] ? date('d M Y', strtotime($t['start_date'])) : 'TBD'; ?><?php echo $t['end_date'] ? ' – ' . date('d M Y', strtotime($t['end_date'])) : ''; ?></div>
                        <div class="meta"><span></span> <?php echo htmlspecialchars($t['venue'] ?: 'TBD'); ?><?php echo $t['city'] ? ', ' . htmlspecialchars($t['city']) : ''; ?></div>
                        <?php if ($t['winner'] && $t['status'] === 'completed'): ?>
                            <div class="meta"><span></span> Winner: <strong style="color:#27ae60"><?php echo htmlspecialchars($t['winner']); ?></strong></div>
                        <?php endif; ?>
                        <div class="footer">
                            <?php if ($t['prize_money']): ?>
                                <span style="color:#8A94A6;font-size:0.85rem;">₹<?php echo htmlspecialchars($t['prize_money']); ?></span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <?php if ($t['status'] !== 'completed'): ?>
                                <a href="single-registration-enhanced.html?event=<?php echo urlencode($t['name']); ?>" class="apply-btn">Register</a>
                            <?php else: ?>
                                <span style="color:#8A94A6;font-size:0.8rem;">Completed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

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
    <script src="js/header.js"></script>
    <script src="js/animations.js"></script>
</body>
</html>
