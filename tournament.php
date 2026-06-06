<?php
/**
 * Dynamic Tournament Page — TCAM
 * Theme-matched with main website (white/orange/light)
 */
$dbPath = __DIR__ . '/tcam_bookings.db';
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
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            color: #222;
        }
        .container {
            max-width: 1100px;
            margin: 60px auto 0 auto;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 32px rgba(0,0,0,0.08);
            padding: 40px 24px 40px 24px;
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
            margin-bottom: 36px;
            font-size: 1rem;
        }

        /* Featured */
        .featured {
            background: linear-gradient(135deg, #fff5f0, #fff0e6);
            border: 2px solid rgba(255,107,53,0.2);
            border-radius: 16px;
            padding: 36px 28px;
            margin-bottom: 36px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.3s, transform 0.3s;
        }
        .featured:hover {
            box-shadow: 0 8px 32px rgba(255,107,53,0.15);
            transform: translateY(-3px);
        }
        .featured .tag {
            display: inline-block;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
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
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 12px;
            color: #333;
        }
        .featured .desc {
            color: #555;
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
        .featured-meta .label { font-size: 0.72rem; color: #999; text-transform: uppercase; letter-spacing: 0.5px; }
        .featured-meta .value { font-weight: 600; font-size: 0.92rem; color: #333; }
        .btn-register {
            display: inline-block;
            padding: 12px 32px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.95rem;
            text-decoration: none;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
            color: #fff;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 16px;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255,107,53,0.3);
        }

        /* Grid */
        .grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); gap: 20px; }
        .card {
            background: #f8f9fa;
            border: 1px solid #eee;
            border-radius: 14px;
            padding: 24px 20px;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(118,75,162,0.12);
            border-color: #ff6b35;
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
        .status-upcoming { background: rgba(102,126,234,0.15); color: #667eea; }
        .status-ongoing { background: rgba(243,156,18,0.15); color: #e67e00; }
        .status-completed { background: rgba(39,174,96,0.15); color: #27ae60; }
        .card h3 { font-size: 1.1rem; font-weight: 700; margin-bottom: 12px; color: #333; }
        .card .meta { font-size: 0.85rem; color: #666; margin-bottom: 6px; display: flex; align-items: center; gap: 6px; }
        .card .footer { margin-top: auto; padding-top: 14px; display: flex; justify-content: space-between; align-items: center; }
        .card .apply-btn {
            padding: 8px 18px;
            background: linear-gradient(45deg, #ff6b35, #f7931e);
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
        .empty { text-align: center; padding: 60px; color: #999; }
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
</head>
<body>
    <div class="container">
        <a href="index.html" class="back-link">&larr; Back to Home</a>
        <h1>🏆 Tournaments</h1>
        <p class="subtitle">Compete in premier tennis cricket events across Maharashtra</p>

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
                <div><div class="icon">📅</div><div class="label">Date</div><div class="value"><?php echo $featured['start_date'] ? date('d M Y', strtotime($featured['start_date'])) : 'TBD'; ?><?php echo $featured['end_date'] ? ' – ' . date('d M Y', strtotime($featured['end_date'])) : ''; ?></div></div>
                <div><div class="icon">📍</div><div class="label">Venue</div><div class="value"><?php echo htmlspecialchars($featured['venue'] ?: 'TBD'); ?><?php echo $featured['city'] ? ', ' . htmlspecialchars($featured['city']) : ''; ?></div></div>
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
                <i>🏏</i>
                <h3>No tournaments yet</h3>
                <p>Check back soon for exciting tournament announcements!</p>
            </div>
        <?php else: ?>
            <div class="grid">
                <?php foreach ($tournaments as $t): ?>
                    <?php if ($featured && $t['id'] === $featured['id']) continue; ?>
                    <div class="card">
                        <?php if ($t['image_path'] && file_exists(__DIR__ . '/' . $t['image_path'])): ?>
                            <div class="card-image">
                                <img src="<?php echo htmlspecialchars($t['image_path']); ?>" alt="<?php echo htmlspecialchars($t['name']); ?>">
                            </div>
                        <?php endif; ?>
                        <span class="status-tag status-<?php echo $t['status']; ?>"><?php echo ucfirst($t['status']); ?></span>
                        <h3><?php echo htmlspecialchars($t['name']); ?></h3>
                        <div class="meta"><span>📅</span> <?php echo $t['start_date'] ? date('d M Y', strtotime($t['start_date'])) : 'TBD'; ?><?php echo $t['end_date'] ? ' – ' . date('d M Y', strtotime($t['end_date'])) : ''; ?></div>
                        <div class="meta"><span>📍</span> <?php echo htmlspecialchars($t['venue'] ?: 'TBD'); ?><?php echo $t['city'] ? ', ' . htmlspecialchars($t['city']) : ''; ?></div>
                        <?php if ($t['winner'] && $t['status'] === 'completed'): ?>
                            <div class="meta"><span>🏆</span> Winner: <strong style="color:#27ae60"><?php echo htmlspecialchars($t['winner']); ?></strong></div>
                        <?php endif; ?>
                        <div class="footer">
                            <?php if ($t['prize_money']): ?>
                                <span style="color:#999;font-size:0.85rem;">₹<?php echo htmlspecialchars($t['prize_money']); ?></span>
                            <?php else: ?>
                                <span></span>
                            <?php endif; ?>
                            <?php if ($t['status'] !== 'completed'): ?>
                                <a href="single-registration-enhanced.html?event=<?php echo urlencode($t['name']); ?>" class="apply-btn">Register</a>
                            <?php else: ?>
                                <span style="color:#999;font-size:0.8rem;">Completed</span>
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
</body>
</html>
