<?php
/**
 * TCAM Admin Panel V2
 * Full-featured admin dashboard with database-backed CRUD
 */
require_once 'admin-auth.php';

// Run migration on first load
$dbPath = __DIR__ . '/tcam_bookings.db';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS tournaments (id INTEGER PRIMARY KEY AUTOINCREMENT, tournament_id TEXT UNIQUE, name TEXT NOT NULL, start_date TEXT, end_date TEXT, venue TEXT, city TEXT, state TEXT DEFAULT 'Maharashtra', participants INTEGER DEFAULT 0, status TEXT DEFAULT 'upcoming', winner TEXT DEFAULT '', runner_up TEXT DEFAULT '', organizer TEXT DEFAULT '', contact_person TEXT DEFAULT '', contact_mobile TEXT DEFAULT '', prize_money TEXT DEFAULT '', description TEXT DEFAULT '', image_path TEXT DEFAULT '', featured INTEGER DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
$db->exec("CREATE TABLE IF NOT EXISTS gallery_images (id INTEGER PRIMARY KEY AUTOINCREMENT, filename TEXT NOT NULL, original_name TEXT, category TEXT DEFAULT 'general', tournament_id INTEGER DEFAULT NULL, caption TEXT DEFAULT '', uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP)");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCAM Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --bg-dark: #0f0c29;
            --bg-card: rgba(255,255,255,0.06);
            --bg-card-hover: rgba(255,255,255,0.1);
            --border: rgba(255,255,255,0.08);
            --text: #e8e6f0;
            --text-muted: rgba(255,255,255,0.5);
            --accent: #ff6b35;
            --accent2: #764ba2;
            --accent3: #667eea;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            --sidebar-w: 260px;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(135deg,#0f0c29,#302b63,#24243e); color:var(--text); min-height:100vh; display:flex; }

        /* ── SIDEBAR ────────────────────────── */
        .sidebar {
            width:var(--sidebar-w); min-height:100vh; background:rgba(0,0,0,0.4); backdrop-filter:blur(20px);
            border-right:1px solid var(--border); padding:24px 0; position:fixed; z-index:100; display:flex; flex-direction:column;
        }
        .sidebar-brand { padding:0 24px 24px; border-bottom:1px solid var(--border); text-align:center; }
        .sidebar-brand .icon { width:48px; height:48px; background:linear-gradient(135deg,var(--accent),#f7931e); border-radius:14px; display:inline-flex; align-items:center; justify-content:center; font-size:22px; margin-bottom:8px; }
        .sidebar-brand h2 { font-size:1.2rem; font-weight:800; color:#fff; }
        .sidebar-brand p { font-size:0.7rem; color:var(--text-muted); margin-top:3px; }
        .sidebar-nav { flex:1; padding:16px 0; }
        .sidebar-nav a {
            display:flex; align-items:center; gap:12px; padding:12px 24px; color:var(--text-muted);
            text-decoration:none; font-size:0.9rem; font-weight:500; transition:all 0.2s; border-left:3px solid transparent;
        }
        .sidebar-nav a:hover, .sidebar-nav a.active { color:#fff; background:var(--bg-card); border-left-color:var(--accent); }
        .sidebar-nav a i { width:20px; text-align:center; font-size:1rem; }
        .sidebar-footer { padding:16px 24px; border-top:1px solid var(--border); }
        .sidebar-footer a { color:var(--text-muted); text-decoration:none; font-size:0.85rem; display:flex; align-items:center; gap:8px; transition:color 0.2s; }
        .sidebar-footer a:hover { color:var(--danger); }

        /* ── MAIN ───────────────────────────── */
        .main { margin-left:var(--sidebar-w); flex:1; padding:24px 32px; min-height:100vh; }
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; }
        .topbar h1 { font-size:1.6rem; font-weight:800; }
        .topbar .user { font-size:0.85rem; color:var(--text-muted); }
        .topbar .user strong { color:#fff; }

        /* ── TAB PANELS ─────────────────────── */
        .tab-panel { display:none; animation:fadeIn 0.3s; }
        .tab-panel.active { display:block; }
        @keyframes fadeIn { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:translateY(0)} }

        /* ── CARDS ──────────────────────────── */
        .stats-row { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:28px; }
        .stat-card {
            background:var(--bg-card); border:1px solid var(--border); border-radius:16px; padding:20px;
            transition:all 0.3s; cursor:default;
        }
        .stat-card:hover { background:var(--bg-card-hover); transform:translateY(-2px); }
        .stat-card .icon { width:40px; height:40px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px; margin-bottom:12px; }
        .stat-card .number { font-size:1.8rem; font-weight:800; margin-bottom:4px; }
        .stat-card .label { font-size:0.8rem; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; }

        /* ── TABLE ──────────────────────────── */
        .card { background:var(--bg-card); border:1px solid var(--border); border-radius:16px; padding:24px; margin-bottom:20px; }
        .card-title { font-size:1.1rem; font-weight:700; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        table { width:100%; min-width:1200px; border-collapse:collapse; font-size:0.85rem; }
        thead th { background:rgba(118,75,162,0.2); color:var(--accent2); padding:12px 10px; text-align:left; font-weight:600; font-size:0.78rem; text-transform:uppercase; letter-spacing:0.5px; border-bottom:1px solid var(--border); white-space:nowrap; }
        tbody td { padding:10px; border-bottom:1px solid var(--border); vertical-align:middle; }
        tbody tr:hover { background:var(--bg-card-hover); }
        thead th:last-child, tbody td:last-child { position:sticky; right:0; background:rgba(15,12,41,0.95); z-index:2; }
        .actions-cell { display:flex; gap:4px; justify-content:flex-end; }
        .badge { padding:3px 10px; border-radius:20px; font-size:0.7rem; font-weight:600; text-transform:uppercase; }
        .badge-active { background:rgba(39,174,96,0.2); color:#2ecc71; }
        .badge-cancelled { background:rgba(231,76,60,0.2); color:#e74c3c; }
        .badge-upcoming { background:rgba(102,126,234,0.2); color:#667eea; }
        .badge-ongoing { background:rgba(243,156,18,0.2); color:#f39c12; }
        .badge-completed { background:rgba(39,174,96,0.2); color:#27ae60; }

        /* ── BUTTONS ────────────────────────── */
        .btn { padding:8px 14px; border:none; border-radius:8px; font-size:0.8rem; font-weight:600; cursor:pointer; transition:all 0.2s; font-family:'Inter',sans-serif; display:inline-flex; align-items:center; gap:6px; }
        .btn:hover { transform:translateY(-1px); }
        .btn-primary { background:var(--accent); color:#fff; }
        .btn-primary:hover { box-shadow:0 4px 12px rgba(255,107,53,0.3); }
        .btn-success { background:var(--success); color:#fff; }
        .btn-danger { background:var(--danger); color:#fff; }
        .btn-warning { background:var(--warning); color:#fff; }
        .btn-ghost { background:rgba(255,255,255,0.08); color:var(--text); border:1px solid var(--border); }
        .btn-ghost:hover { background:rgba(255,255,255,0.15); }
        .btn-lg { padding:12px 24px; font-size:0.9rem; }
        .actions-cell { display:flex; gap:4px; }

        /* ── FILTERS ────────────────────────── */
        .filters { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:12px; margin-bottom:16px; padding:16px; background:rgba(0,0,0,0.2); border-radius:12px; }
        .filters input, .filters select {
            width:100%; padding:10px 12px; background:rgba(255,255,255,0.06); border:1px solid var(--border);
            border-radius:8px; color:#fff; font-size:0.85rem; font-family:'Inter',sans-serif; outline:none; transition:border 0.2s;
        }
        .filters input:focus, .filters select:focus { border-color:var(--accent); }
        .filters input::placeholder { color:var(--text-muted); }
        .filters select option { background:#1a1a2e; color:#fff; }
        .filters label { font-size:0.75rem; color:var(--text-muted); margin-bottom:4px; display:block; text-transform:uppercase; letter-spacing:0.3px; }
        .filter-group { display:flex; flex-direction:column; }
        .filter-actions { display:flex; gap:8px; align-items:flex-end; }

        /* ── MODAL ──────────────────────────── */
        .modal-overlay {
            display:none; position:fixed; top:0; left:0; width:100%; height:100%;
            background:rgba(0,0,0,0.7); z-index:1000; justify-content:center; align-items:center;
            backdrop-filter:blur(8px);
        }
        .modal-overlay.active { display:flex; }
        .modal {
            background:#1a1a2e; border:1px solid var(--border); border-radius:20px;
            padding:32px; max-width:600px; width:90%; max-height:85vh; overflow-y:auto;
            animation:slideUp 0.3s cubic-bezier(.4,0,.2,1);
        }
        @keyframes slideUp { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
        .modal h2 { font-size:1.3rem; font-weight:700; margin-bottom:20px; display:flex; align-items:center; gap:8px; }
        .modal .form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .modal .form-group { margin-bottom:16px; }
        .modal .form-group label { display:block; font-size:0.8rem; color:var(--text-muted); margin-bottom:6px; text-transform:uppercase; letter-spacing:0.3px; }
        .modal .form-group input, .modal .form-group select, .modal .form-group textarea {
            width:100%; padding:10px 12px; background:rgba(255,255,255,0.06); border:1px solid var(--border);
            border-radius:8px; color:#fff; font-size:0.9rem; font-family:'Inter',sans-serif; outline:none;
        }
        .modal .form-group textarea { min-height:80px; resize:vertical; }
        .modal .form-group input:focus, .modal .form-group select:focus, .modal .form-group textarea:focus { border-color:var(--accent); }
        .modal-footer { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }
        .modal .close-btn { position:absolute; top:16px; right:20px; background:none; border:none; color:var(--text-muted); font-size:1.5rem; cursor:pointer; }

        /* ── GALLERY GRID ───────────────────── */
        .gallery-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:12px; }
        .gallery-card {
            position:relative; border-radius:12px; overflow:hidden; aspect-ratio:1;
            border:1px solid var(--border); transition:all 0.3s; cursor:pointer;
        }
        .gallery-card:hover { border-color:var(--accent); transform:scale(1.02); }
        .gallery-card img { width:100%; height:100%; object-fit:cover; }
        .gallery-card .delete-overlay {
            position:absolute; top:6px; right:6px; width:28px; height:28px; background:rgba(231,76,60,0.9);
            border-radius:50%; display:none; align-items:center; justify-content:center; cursor:pointer; color:#fff; font-size:12px;
        }
        .gallery-card:hover .delete-overlay { display:flex; }
        .gallery-card .caption { position:absolute; bottom:0; left:0; right:0; padding:6px 8px; background:linear-gradient(transparent,rgba(0,0,0,0.8)); font-size:0.7rem; color:#fff; }

        /* ── UPLOAD AREA ────────────────────── */
        .upload-area {
            border:2px dashed var(--border); border-radius:16px; padding:40px; text-align:center;
            transition:all 0.3s; cursor:pointer; margin-bottom:20px;
        }
        .upload-area:hover, .upload-area.dragover { border-color:var(--accent); background:rgba(255,107,53,0.05); }
        .upload-area i { font-size:2rem; color:var(--accent); margin-bottom:12px; }
        .upload-area p { color:var(--text-muted); font-size:0.9rem; }

        /* ── PHOTO ──────────────────────────── */
        .photo-thumb { width:40px; height:40px; border-radius:8px; object-fit:cover; border:1px solid var(--border); }

        /* ── RESPONSIVE ─────────────────────── */
        @media(max-width:1024px) {
            .sidebar { width:70px; } .sidebar-brand h2, .sidebar-brand p, .sidebar-nav a span, .sidebar-footer span { display:none; }
            .sidebar-nav a { justify-content:center; padding:14px 0; } .sidebar-nav a i { width:auto; font-size:1.2rem; }
            .main { margin-left:70px; padding:16px; }
        }
        @media(max-width:768px) {
            .sidebar { display:none; } .main { margin-left:0; }
            .modal .form-row { grid-template-columns:1fr; }
        }

        /* ── TOAST ──────────────────────────── */
        .toast-container { position:fixed; top:20px; right:20px; z-index:2000; }
        .toast {
            background:rgba(0,0,0,0.9); border:1px solid var(--border); border-radius:12px;
            padding:14px 20px; margin-bottom:8px; font-size:0.85rem; color:#fff;
            animation:slideIn 0.3s; backdrop-filter:blur(10px); display:flex; align-items:center; gap:8px;
        }
        @keyframes slideIn { from{opacity:0;transform:translateX(40px)} to{opacity:1;transform:translateX(0)} }
        .toast.success { border-left:3px solid var(--success); }
        .toast.error { border-left:3px solid var(--danger); }

        /* No results */
        .no-results { text-align:center; padding:40px; color:var(--text-muted); }
        .no-results i { font-size:2.5rem; margin-bottom:12px; display:block; }

        /* scrollbar */
        ::-webkit-scrollbar { width:6px; }
        ::-webkit-scrollbar-track { background:transparent; }
        ::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.15); border-radius:3px; }
    </style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-brand">
        <div class="icon">🏏</div>
        <h2>TCAM Admin</h2>
        <p>Management Portal</p>
    </div>
    <nav class="sidebar-nav">
        <a href="#" class="active" data-tab="dashboard"><i class="fas fa-chart-pie"></i><span>Dashboard</span></a>
        <a href="#" data-tab="registrations"><i class="fas fa-users"></i><span>Registrations</span></a>
        <a href="#" data-tab="coaches"><i class="fas fa-user-tie"></i><span>Coaches</span></a>
        <a href="#" data-tab="coach-reports"><i class="fas fa-file-alt"></i><span>Coach Reports</span></a>
        <a href="#" data-tab="tournaments"><i class="fas fa-trophy"></i><span>Tournaments</span></a>
        <a href="#" data-tab="gallery"><i class="fas fa-images"></i><span>Gallery</span></a>
        <a href="#" data-tab="media"><i class="fas fa-photo-video"></i><span>Media</span></a>
        <a href="#" data-tab="hero"><i class="fas fa-film"></i><span>Hero</span></a>
        <a href="#" data-tab="contacts"><i class="fas fa-envelope"></i><span>Messages</span></a>
        <a href="#" data-tab="settings"><i class="fas fa-cog"></i><span>Settings</span></a>
    </nav>
    <div class="sidebar-footer">
        <a href="index.html"><i class="fas fa-globe"></i><span>View Website</span></a>
        <a href="logout.php" style="margin-top:10px;"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <div class="topbar">
        <h1 id="pageTitle">Dashboard</h1>
        <div class="user">Welcome, <strong><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></strong> &bull; <?php echo date('d M Y, h:i A'); ?></div>
    </div>

    <div class="toast-container" id="toastContainer"></div>

    <!-- ═══════════════════════════════ DASHBOARD ═══════════════════════════════ -->
    <div class="tab-panel active" id="tab-dashboard">
        <div class="stats-row" id="statsRow">
            <div class="stat-card"><div class="icon" style="background:rgba(255,107,53,0.2);color:var(--accent)"><i class="fas fa-users"></i></div><div class="number" id="statTotal">-</div><div class="label">Total Registrations</div></div>
            <div class="stat-card"><div class="icon" style="background:rgba(39,174,96,0.2);color:var(--success)"><i class="fas fa-user-check"></i></div><div class="number" id="statActive">-</div><div class="label">Active</div></div>
            <div class="stat-card"><div class="icon" style="background:rgba(102,126,234,0.2);color:var(--accent3)"><i class="fas fa-trophy"></i></div><div class="number" id="statTournaments">-</div><div class="label">Tournaments</div></div>
            <div class="stat-card"><div class="icon" style="background:rgba(243,156,18,0.2);color:var(--warning)"><i class="fas fa-calendar-day"></i></div><div class="number" id="statToday">-</div><div class="label">Today</div></div>
            <div class="stat-card"><div class="icon" style="background:rgba(118,75,162,0.2);color:var(--accent2)"><i class="fas fa-images"></i></div><div class="number" id="statGallery">-</div><div class="label">Gallery Photos</div></div>
            <div class="stat-card"><div class="icon" style="background:rgba(39,174,96,0.2);color:var(--success)"><i class="fas fa-user-tie"></i></div><div class="number" id="statCoaches">-</div><div class="label">Total Coaches</div></div>
            <div class="stat-card"><div class="icon" style="background:rgba(255,179,71,0.2);color:var(--warning)"><i class="fas fa-file-alt"></i></div><div class="number" id="statReports">-</div><div class="label">Coach Reports</div></div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
            <div class="card"><div class="card-title"><i class="fas fa-chart-bar"></i> Registration Overview</div><canvas id="regChart" height="200"></canvas></div>
            <div class="card"><div class="card-title"><i class="fas fa-chart-pie"></i> Tournament Status</div><canvas id="tournChart" height="200"></canvas></div>
        </div>
    </div>

    <!-- ═══════════════════════════ REGISTRATIONS ══════════════════════════════ -->
    <div class="tab-panel" id="tab-registrations">
        <div class="card">
            <div class="card-title" style="justify-content:space-between;">
                <span><i class="fas fa-filter"></i> Search & Filter Registrations</span>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-success" onclick="exportCSV()"><i class="fas fa-file-csv"></i> Export CSV</button>
                </div>
            </div>
            <div class="filters" id="regFilters">
                <div class="filter-group"><label>Name</label><input type="text" id="fName" placeholder="Search name..." oninput="debounceSearch()"></div>
                <div class="filter-group"><label>Mobile</label><input type="text" id="fMobile" placeholder="Mobile number..." oninput="debounceSearch()"></div>
                <div class="filter-group"><label>Email</label><input type="text" id="fEmail" placeholder="Search email..." oninput="debounceSearch()"></div>
                <div class="filter-group"><label>District</label><input type="text" id="fCity" placeholder="District..." oninput="debounceSearch()"></div>
                <div class="filter-group"><label>TCAM ID</label><input type="text" id="fRegId" placeholder="TCAM ID..." oninput="debounceSearch()"></div>
                <div class="filter-group"><label>Document Number</label><input type="text" id="fDocNum" placeholder="Aadhar/PAN..." oninput="debounceSearch()"></div>
                <div class="filter-group"><label>Tournament</label><input type="text" id="fTournament" placeholder="Tournament name..." oninput="debounceSearch()"></div>
                <div class="filter-group"><label>ID Proof Type</label>
                    <select id="fDocType" onchange="loadRegistrations()">
                        <option value="">All Types</option>
                        <option value="aadhar">Aadhar Card</option>
                        <option value="pan">PAN Card</option>
                        <option value="voter">Voter ID</option>
                        <option value="passport">Passport</option>
                        <option value="driving">Driving License</option>
                        <option value="school">School ID</option>
                    </select>
                </div>
                <div class="filter-group"><label>Date From</label><input type="date" id="fDateFrom" onchange="loadRegistrations()"></div>
                <div class="filter-group"><label>Date To</label><input type="date" id="fDateTo" onchange="loadRegistrations()"></div>
                <div class="filter-group"><label>Quick Range</label>
                    <select id="fPreset" onchange="applyDatePreset()">
                        <option value="">Custom / None</option>
                        <option value="last7">Last 7 days</option>
                        <option value="last30">Last 30 days</option>
                        <option value="last365">Last 365 days</option>
                    </select>
                </div>
                <div class="filter-group"><label>DOB</label><input type="date" id="fDob" onchange="loadRegistrations()"></div>
                <div class="filter-actions">
                    <button class="btn btn-warning" onclick="clearRegFilters()"><i class="fas fa-times"></i> Clear</button>
                    <button class="btn btn-primary" onclick="loadRegistrations()"><i class="fas fa-search"></i> Search</button>
                </div>
            </div>
            <div id="regCount" style="font-size:0.8rem;color:var(--text-muted);margin-bottom:8px;"></div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr>
                        <th>Photo</th><th>TCAM ID</th><th>Name</th><th>Mobile</th><th>City</th><th>DOB</th><th>ID Proof</th><th>Doc#</th><th>Events</th><th>Registered</th><th>Actions</th>
                    </tr></thead>
                    <tbody id="regTableBody"><tr><td colspan="11" class="no-results"><i class="fas fa-spinner fa-spin"></i><br>Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════ TOURNAMENTS ════════════════════════════════ -->
    <div class="tab-panel" id="tab-tournaments">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <span></span>
            <button class="btn btn-primary btn-lg" onclick="showAddTournament()"><i class="fas fa-plus"></i> Add Tournament</button>
        </div>
        <div class="card">
            <div class="card-title"><i class="fas fa-trophy"></i> All Tournaments</div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr>
                        <th>ID</th><th>Name</th><th>Dates</th><th>Venue</th><th>City</th><th>Teams</th><th>Status</th><th>Winner</th><th>Actions</th>
                    </tr></thead>
                    <tbody id="tournTableBody"><tr><td colspan="9" class="no-results"><i class="fas fa-spinner fa-spin"></i><br>Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════ GALLERY ════════════════════════════════════ -->
    <div class="tab-panel" id="tab-gallery">
        <div class="card">
            <div class="card-title" style="justify-content:space-between;">
                <span><i class="fas fa-cloud-upload-alt"></i> Upload Images</span>
            </div>
            <form id="galleryUploadForm" enctype="multipart/form-data">
                <div style="display:grid;grid-template-columns:1fr 1fr 2fr;gap:12px;margin-bottom:16px;">
                    <div class="filter-group"><label>Category</label>
                        <select id="uploadCategory" name="category">
                            <option value="general">General</option>
                            <option value="tournaments">Tournaments</option>
                            <option value="events">Events</option>
                            <option value="students">Students</option>
                            <option value="achievements">Achievements</option>
                            <option value="certificates">Certificates</option>
                            <option value="infrastructure">Infrastructure</option>
                        </select>
                    </div>
                    <div class="filter-group"><label>Caption (optional)</label><input type="text" id="uploadCaption" name="caption" placeholder="Photo caption..."></div>
                    <div class="filter-group"><label>Images (multi-select)</label><input type="file" id="uploadFiles" name="images[]" multiple accept="image/*" style="padding-top:6px;"></div>
                </div>
                <div style="display:flex;gap:8px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload Images</button>
                    <button type="button" class="btn btn-ghost" onclick="syncImages()"><i class="fas fa-sync-alt"></i> Sync Images</button>
                </div>
            </form>
        </div>
        <div class="card">
            <div class="card-title" style="justify-content:space-between;">
                <span><i class="fas fa-images"></i> Gallery Images</span>
                <div style="display:flex;gap:8px;">
                    <select id="galleryFilter" onchange="loadGallery()" style="padding:6px 10px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:#fff;font-size:0.85rem;">
                        <option value="">All Categories</option>
                        <option value="tournaments">Tournaments</option>
                        <option value="events">Events</option>
                        <option value="students">Students</option>
                        <option value="achievements">Achievements</option>
                        <option value="certificates">Certificates</option>
                        <option value="infrastructure">Infrastructure</option>
                        <option value="general">General</option>
                    </select>
                </div>
            </div>
            <div class="gallery-grid" id="galleryGrid">
                <div class="no-results"><i class="fas fa-spinner fa-spin"></i><br>Loading...</div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════ MEDIA MANAGEMENT ═══════════════════════════ -->
    <div class="tab-panel" id="tab-media">
        <div class="card">
            <div class="card-title" style="justify-content:space-between;">
                <span><i class="fas fa-photo-video"></i> Media Library</span>
                <div style="display:flex;gap:8px;align-items:center;">
                    <select id="mediaSectionFilter" onchange="loadMedia()" style="padding:6px 10px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:#fff;font-size:0.85rem;">
                        <option value="">All Sections</option>
                        <option value="gallery">Gallery</option>
                        <option value="hero">Hero</option>
                        <option value="banner">Banner</option>
                        <option value="students">Students</option>
                    </select>
                    <button class="btn btn-primary" onclick="openMediaUpload()"><i class="fas fa-upload"></i> Upload</button>
                    <button class="btn btn-ghost" onclick="syncImages()" style="margin-left:6px"><i class="fas fa-sync-alt"></i> Sync</button>
                </div>
            </div>
            <div class="gallery-grid" id="mediaGrid">
                <div class="no-results"><i class="fas fa-spinner fa-spin"></i><br>Loading...</div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════ HERO / BANNER MANAGEMENT ═════════════════════ -->
    <div class="tab-panel" id="tab-hero">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div class="card-title"><i class="fas fa-film"></i> Hero / Banner Manager</div>
            <button class="btn btn-primary btn-lg" onclick="showHeroModal()"><i class="fas fa-plus"></i> Add Banner</button>
        </div>
        <div class="card">
            <div class="card-title"><i class="fas fa-film"></i> Current Banners</div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>ID</th><th>Title</th><th>Preview</th><th>Link</th><th>Enabled</th><th>Position</th><th>Actions</th></tr></thead>
                    <tbody id="heroTableBody"><tr><td colspan="7" class="no-results"><i class="fas fa-spinner fa-spin"></i><br>Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════ CONTACT MESSAGES ══════════════════════════ -->
    <div class="tab-panel" id="tab-contacts">
        <div class="card">
            <div class="card-title" style="justify-content:space-between;">
                <span><i class="fas fa-envelope"></i> Contact Messages <span id="unreadBadge" style="background:var(--danger);color:#fff;padding:2px 8px;border-radius:10px;font-size:0.7rem;margin-left:6px;"></span></span>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr>
                        <th>Status</th><th>Name</th><th>Email</th><th>Phone</th><th>Message</th><th>Date</th><th>Actions</th>
                    </tr></thead>
                    <tbody id="contactTableBody"><tr><td colspan="7" class="no-results"><i class="fas fa-spinner fa-spin"></i><br>Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-panel" id="tab-coaches">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div class="card-title"><i class="fas fa-user-tie"></i> District Coaches</div>
            <button class="btn btn-primary btn-lg" onclick="showAddCoach()"><i class="fas fa-plus"></i> Add Coach</button>
        </div>
        <div class="card">
            <div class="filters">
                <div class="filter-group"><label>Search Coach</label><input id="coachSearch" type="text" placeholder="Username, name or district" oninput="debounceCoachSearch()"></div>
                <div class="filter-actions"><button class="btn btn-warning" onclick="clearCoachFilters()"><i class="fas fa-times"></i> Clear</button></div>
            </div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>Username</th><th>Name</th><th>District</th><th>Mobile</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody id="coachTableBody"><tr><td colspan="6" class="no-results"><i class="fas fa-spinner fa-spin"></i><br>Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="tab-panel" id="tab-coach-reports">
        <div class="card">
            <div class="card-title"><i class="fas fa-file-alt"></i> Coach Reports</div>
            <div style="overflow-x:auto;">
                <table>
                    <thead><tr><th>ID</th><th>Coach</th><th>District</th><th>Mobile</th><th>Event</th><th>Report</th><th>Created</th><th>Actions</th></tr></thead>
                    <tbody id="coachReportBody"><tr><td colspan="8" class="no-results"><i class="fas fa-spinner fa-spin"></i><br>Loading...</td></tr></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════ SETTINGS ═══════════════════════════════════ -->
    <div class="tab-panel" id="tab-settings">
        <div class="card" style="max-width:500px;">
            <div class="card-title"><i class="fas fa-lock"></i> Change Password</div>
            <form id="changePasswordForm">
                <div class="filter-group" style="margin-bottom:16px;"><label>Current Password</label><input type="password" id="currentPass" required style="padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:#fff;width:100%;"></div>
                <div class="filter-group" style="margin-bottom:16px;"><label>New Password</label><input type="password" id="newPass" required minlength="6" style="padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:#fff;width:100%;"></div>
                <div class="filter-group" style="margin-bottom:16px;"><label>Confirm Password</label><input type="password" id="confirmPass" required minlength="6" style="padding:10px 12px;background:rgba(255,255,255,0.06);border:1px solid var(--border);border-radius:8px;color:#fff;width:100%;"></div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Password</button>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════ MODALS ═══════════════════════════════════════ -->

<!-- Add/Edit Tournament Modal -->
<div class="modal-overlay" id="tournamentModal">
    <div class="modal" style="position:relative;">
        <button class="close-btn" onclick="closeModal('tournamentModal')">&times;</button>
        <h2 id="tournModalTitle"><i class="fas fa-trophy"></i> Add Tournament</h2>
        <form id="tournamentForm" enctype="multipart/form-data">
            <input type="hidden" id="tEditId" value="">
            <div class="form-row">
                <div class="form-group"><label>Tournament Name *</label><input type="text" id="tName" required></div>
                <div class="form-group"><label>Venue</label><input type="text" id="tVenue"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Start Date</label><input type="date" id="tStartDate"></div>
                <div class="form-group"><label>End Date</label><input type="date" id="tEndDate"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>City</label><input type="text" id="tCity"></div>
                <div class="form-group"><label>State</label><input type="text" id="tState" value="Maharashtra"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Participants</label><input type="number" id="tParticipants" value="0"></div>
                <div class="form-group"><label>Status</label>
                    <select id="tStatus"><option value="upcoming">Upcoming</option><option value="ongoing">Ongoing</option><option value="completed">Completed</option></select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Winner</label><input type="text" id="tWinner"></div>
                <div class="form-group"><label>Runner Up</label><input type="text" id="tRunnerUp"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Organizer</label><input type="text" id="tOrganizer"></div>
                <div class="form-group"><label>Prize Money</label><input type="text" id="tPrize"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Contact Person</label><input type="text" id="tContact"></div>
                <div class="form-group"><label>Contact Mobile</label><input type="text" id="tContactMobile"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea id="tDescription"></textarea></div>
            <div class="form-group"><label>Tournament Poster/Image</label><input type="file" id="tImage" accept="image/*" style="padding-top:8px;"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('tournamentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Tournament</button>
            </div>
        </form>
    </div>
</div>

<!-- View/Edit Registration Modal -->
<div class="modal-overlay" id="regModal">
    <div class="modal" style="position:relative;">
        <button class="close-btn" onclick="closeModal('regModal')">&times;</button>
        <h2 id="regModalTitle"><i class="fas fa-user"></i> Registration Details</h2>
        <div id="regModalBody"></div>
    </div>
</div>

<!-- Add/Edit Coach Modal -->
<div class="modal-overlay" id="coachModal">
    <div class="modal" style="position:relative;">
        <button class="close-btn" onclick="closeModal('coachModal')">&times;</button>
        <h2 id="coachModalTitle"><i class="fas fa-user-tie"></i> Add Coach</h2>
        <form id="coachForm">
            <input type="hidden" id="coachId" value="">
            <div class="form-row">
                <div class="form-group"><label>Coach Name</label><input type="text" id="coachName" required></div>
                <div class="form-group"><label>Username</label><input type="text" id="coachUsername" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>District</label><input type="text" id="coachDistrict" required></div>
                <div class="form-group"><label>Mobile</label><input type="text" id="coachMobile" required></div>
            </div>
            <div class="form-group"><label>Password <span style="font-size:0.8rem;color:#aaa">(leave blank when editing)</span></label><input type="password" id="coachPassword"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('coachModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Coach</button>
            </div>
        </form>
    </div>
</div>

    <!-- Media Upload Modal -->
    <div class="modal-overlay" id="mediaUploadModal">
        <div class="modal" style="position:relative;">
            <button class="close-btn" onclick="closeModal('mediaUploadModal')">&times;</button>
            <h2><i class="fas fa-upload"></i> Upload Media</h2>
            <form id="mediaUploadForm" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group"><label>Section</label>
                        <select id="mediaSection" name="section"><option value="gallery">Gallery</option><option value="hero">Hero</option><option value="banner">Banner</option><option value="students">Students</option></select>
                    </div>
                    <div class="form-group"><label>Alt Text</label><input type="text" id="mediaAlt" name="alt_text"></div>
                </div>
                <div class="form-group"><label>Image File</label><input type="file" id="mediaFile" name="image" accept="image/*" required></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('mediaUploadModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Hero/Banner Modal -->
    <div class="modal-overlay" id="heroModal">
        <div class="modal" style="position:relative;">
            <button class="close-btn" onclick="closeModal('heroModal')">&times;</button>
            <h2 id="heroModalTitle"><i class="fas fa-film"></i> Add Banner</h2>
            <form id="heroForm" enctype="multipart/form-data">
                <input type="hidden" id="heroId" value="">
                <div class="form-group"><label>Title</label><input type="text" id="heroTitle"></div>
                <div class="form-group"><label>Subtitle</label><input type="text" id="heroSubtitle"></div>
                <div class="form-group"><label>Link</label><input type="text" id="heroLink"></div>
                <div class="form-row">
                    <div class="form-group"><label>Position</label><input type="number" id="heroPosition" value="0"></div>
                    <div class="form-group"><label>Enabled</label>
                        <select id="heroEnabled"><option value="1">Yes</option><option value="0">No</option></select>
                    </div>
                </div>
                <div class="form-group"><label>Image (optional)</label><input type="file" id="heroImage" name="image" accept="image/*"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('heroModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

<script>
const API = 'admin-api.php';
let searchTimer;

// ── TAB NAVIGATION ─────────────────────────
document.querySelectorAll('.sidebar-nav a').forEach(a => {
    a.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.sidebar-nav a').forEach(x => x.classList.remove('active'));
        a.classList.add('active');
        const tab = a.dataset.tab;
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        document.getElementById('pageTitle').textContent = a.querySelector('span')?.textContent || tab;
        if (tab === 'dashboard') loadDashboard();
        if (tab === 'registrations') loadRegistrations();
        if (tab === 'coaches') loadCoaches();
        if (tab === 'coach-reports') loadCoachReports();
        if (tab === 'tournaments') loadTournaments();
        if (tab === 'gallery') loadGallery();
        if (tab === 'media') loadMedia();
        if (tab === 'hero') loadHero();
        if (tab === 'contacts') loadContacts();
    });
});

// ── TOAST ───────────────────────────────────
function toast(msg, type = 'success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.innerHTML = (type === 'success' ? '<i class="fas fa-check-circle"></i>' : '<i class="fas fa-exclamation-circle"></i>') + ' ' + msg;
    c.appendChild(t);
    setTimeout(() => t.remove(), 4000);
}

// ── MODAL HELPERS ──────────────────────────
function closeModal(id) { document.getElementById(id).classList.remove('active'); }

// ── DASHBOARD ──────────────────────────────
async function loadDashboard() {
    try {
        const r = await fetch(API + '?action=stats');
        const j = await r.json();
        if (j.status === 'success') {
            const d = j.data;
            document.getElementById('statTotal').textContent = d.total;
            document.getElementById('statActive').textContent = d.active;
            document.getElementById('statTournaments').textContent = d.tournaments;
            document.getElementById('statToday').textContent = d.today;
            document.getElementById('statGallery').textContent = d.gallery;
            document.getElementById('statCoaches').textContent = d.coachCount || 0;
            document.getElementById('statReports').textContent = d.reportCount || 0;
            // Charts
            renderCharts(d);
        }
    } catch(e) { console.error(e); }
}

let regChartInstance, tournChartInstance;
function renderCharts(d) {
    if (regChartInstance) regChartInstance.destroy();
    const ctx1 = document.getElementById('regChart').getContext('2d');
    regChartInstance = new Chart(ctx1, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Inactive/Cancelled'],
            datasets: [{ data: [d.active, Math.max(0, d.total - d.active)], backgroundColor: ['#27ae60','#e74c3c'], borderWidth: 0 }]
        },
        options: { responsive:true, plugins:{ legend:{ labels:{ color:'#aaa' } } } }
    });
    if (tournChartInstance) tournChartInstance.destroy();
    const ctx2 = document.getElementById('tournChart').getContext('2d');
    tournChartInstance = new Chart(ctx2, {
        type: 'bar',
        data: {
            labels: ['Upcoming','Ongoing','Completed'],
            datasets: [{ label:'Tournaments', data:[d.upcoming,d.ongoing,d.completed], backgroundColor:['#667eea','#f39c12','#27ae60'], borderRadius:8, borderWidth:0 }]
        },
        options: { responsive:true, plugins:{ legend:{ display:false } }, scales:{ x:{ ticks:{color:'#aaa'}, grid:{display:false} }, y:{ ticks:{color:'#aaa'}, grid:{color:'rgba(255,255,255,0.05)'} } } }
    });
}

// ── REGISTRATIONS ──────────────────────────
function debounceSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadRegistrations, 400);
}

function applyDatePreset() {
    const preset = document.getElementById('fPreset').value;
    if (!preset) return;
    const to = new Date();
    let from = new Date();
    if (preset === 'last7') from.setDate(to.getDate() - 7);
    if (preset === 'last30') from.setDate(to.getDate() - 30);
    if (preset === 'last365') from.setDate(to.getDate() - 365);
    document.getElementById('fDateFrom').value = from.toISOString().slice(0,10);
    document.getElementById('fDateTo').value = to.toISOString().slice(0,10);
    loadRegistrations();
}

async function loadRegistrations() {
    const params = new URLSearchParams({action:'registrations'});
    const fields = {
        name:'fName',
        mobile:'fMobile',
        email:'fEmail',
        reg_id:'fRegId',
        document_number:'fDocNum',
        document_type:'fDocType',
        date_from:'fDateFrom',
        date_to:'fDateTo',
        dob:'fDob'
    };
    for (const [k,id] of Object.entries(fields)) {
        const v = document.getElementById(id)?.value?.trim();
        if (v) params.set(k, v);
    }
    const city = document.getElementById('fCity')?.value?.trim(); if (city) params.set('city', city);
    const tourn = document.getElementById('fTournament')?.value?.trim(); if (tourn) params.set('tournament', tourn);
    try {
        const r = await fetch(API + '?' + params.toString());
        const j = await r.json();
        const tb = document.getElementById('regTableBody');
        document.getElementById('regCount').textContent = `Showing ${j.count ?? 0} registration(s)`;
        if (!j.data || j.data.length === 0) {
            tb.innerHTML = '<tr><td colspan="11" class="no-results"><i class="fas fa-inbox"></i><br>No registrations found</td></tr>';
            return;
        }
        tb.innerHTML = j.data.map(r => {
            const events = (r.events||[]).map(e => e.event_name).join(', ') || '-';
            let src = r.photo || '';
            if (src && !src.startsWith('uploads/')) src = 'uploads/' + src;
            const photo = src ? `<img src="${src}" class="photo-thumb" onerror="this.src='data:image/svg+xml,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;40&quot; height=&quot;40&quot; fill=&quot;%23666&quot;><rect width=&quot;40&quot; height=&quot;40&quot; fill=&quot;%23222&quot;/><text x=&quot;50%&quot; y=&quot;55%&quot; dominant-baseline=&quot;middle&quot; text-anchor=&quot;middle&quot; font-size=&quot;14&quot;>👤</text></svg>'">` : '<span style="color:var(--text-muted)">👤</span>';
            const status = (r.status || 'active');
            return `<tr>
                <td>${photo}</td>
                <td><strong style="color:var(--accent)">${r.reg_id||'-'}</strong></td>
                <td>${r.name||''}</td>
                <td>${r.mobile||''}</td>
                <td>${r.city||''}</td>
                <td>${r.date_of_birth||'-'}</td>
                <td><span class="badge badge-${r.document_type||''}" style="background:rgba(102,126,234,0.15);color:#667eea">${(r.document_type||'').toUpperCase()}</span></td>
                <td style="font-size:0.78rem">${r.document_number||'-'}</td>
                <td style="font-size:0.78rem;max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${events}">${events}</td>
                <td style="font-size:0.78rem">${r.created_at ? new Date(r.created_at).toLocaleDateString('en-IN') : '-'}</td>
                <td><div class="actions-cell">
                    <button class="btn btn-success" style="padding:5px 8px" onclick="viewReg(${r.id})" title="View"><i class="fas fa-eye"></i></button>
                    <button class="btn btn-primary" style="padding:5px 8px" onclick="editReg(${r.id})" title="Edit"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-danger" style="padding:5px 8px" onclick="deleteReg(${r.id})" title="Delete"><i class="fas fa-trash"></i></button>
                </div></td>
            </tr>`;
        }).join('');
    } catch(e) { console.error(e); }
}

function clearRegFilters() {
    ['fName','fMobile','fEmail','fRegId','fDocNum','fDocType','fCity','fTournament','fDateFrom','fDateTo','fDob','fPreset'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.value = '';
    });
    loadRegistrations();
}

async function viewReg(id) {
    const r = await fetch(API + '?action=registration_detail&id=' + id);
    const j = await r.json();
    if (j.status !== 'success') { toast(j.message,'error'); return; }
    const d = j.data;
    const events = (d.events||[]).map(e => `<li style="margin-bottom:4px">${e.event_name} <span style="color:var(--text-muted);font-size:0.75rem">(${e.created_at||''})</span></li>`).join('') || '<li>None</li>';
    let psrc = d.photo || '';
    if (psrc && !psrc.startsWith('uploads/')) psrc = 'uploads/' + psrc;
    const photo = psrc ? `<img src="${psrc}" style="width:100px;height:100px;border-radius:12px;object-fit:cover;border:2px solid var(--border);">` : '';
    // proof file link
    let proofLink = '';
    if (d.document_number) {
        let pf = d.document_number;
        if (pf && !pf.startsWith('uploads/')) pf = 'uploads/' + pf;
        proofLink = `<div><strong>ID Proof:</strong> <a href="${pf}" target="_blank" rel="noopener noreferrer">View Document</a></div>`;
    }
    document.getElementById('regModalTitle').innerHTML = '<i class="fas fa-user"></i> ' + (d.name||'Registration');
    document.getElementById('regModalBody').innerHTML = `
        <div style="display:flex;gap:20px;margin-bottom:16px;align-items:flex-start;">
            ${photo}
            <div>
                <div style="font-size:1.4rem;font-weight:800;color:var(--accent)">TCAM ID: ${d.reg_id||'-'}</div>
                <div style="color:var(--text-muted);margin-top:4px">Registered: ${d.created_at||''}</div>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;font-size:0.9rem;">
            <div><strong>Name:</strong> ${d.name||''}</div>
            <div><strong>Mobile:</strong> ${d.mobile||''}</div>
            <div><strong>Email:</strong> ${d.email||'-'}</div>
            <div><strong>DOB:</strong> ${d.date_of_birth||'-'}</div>
            <div><strong>City:</strong> ${d.city||''}</div>
            <div><strong>State:</strong> ${d.state||''}</div>
            <div><strong>Document:</strong> ${(d.document_type||'').toUpperCase()} — ${d.document_number||''}</div>
            ${proofLink}
            <div><strong>Blood Group:</strong> ${d.blood_group||'-'}</div>
            <div><strong>Parent:</strong> ${d.parent_name||'-'}</div>
            <div><strong>Emergency:</strong> ${d.emergency_contact||'-'}</div>
            <div style="grid-column:1/-1"><strong>Address:</strong> ${d.address||'-'}</div>
        </div>
        <div style="margin-top:16px;"><strong>Tournaments/Events:</strong><ul style="margin-top:6px;padding-left:20px;">${events}</ul></div>
    `;
    document.getElementById('regModal').classList.add('active');
}

async function editReg(id) {
    const r = await fetch(API + '?action=registration_detail&id=' + id);
    const j = await r.json();
    if (j.status !== 'success') { toast(j.message,'error'); return; }
    const d = j.data;
    document.getElementById('regModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Registration';
    document.getElementById('regModalBody').innerHTML = `
        <form id="editRegForm">
            <input type="hidden" id="erID" value="${d.id}">
            <div class="form-row"><div class="form-group"><label>Name</label><input type="text" id="erName" value="${d.name||''}"></div><div class="form-group"><label>Mobile</label><input type="text" id="erMobile" value="${d.mobile||''}"></div></div>
            <div class="form-row"><div class="form-group"><label>Email</label><input type="email" id="erEmail" value="${d.email||''}"></div><div class="form-group"><label>DOB</label><input type="date" id="erDob" value="${d.date_of_birth||''}"></div></div>
            <div class="form-row"><div class="form-group"><label>City</label><input type="text" id="erCity" value="${d.city||''}"></div><div class="form-group"><label>State</label><input type="text" id="erState" value="${d.state||''}"></div></div>
            <div class="form-row"><div class="form-group"><label>Document Type</label><input type="text" id="erDocType" value="${d.document_type||''}"></div><div class="form-group"><label>Document Number</label><input type="text" id="erDocNum" value="${d.document_number||''}"></div></div>
            <div class="form-row"><div class="form-group"><label>Parent Name</label><input type="text" id="erParent" value="${d.parent_name||''}"></div><div class="form-group"><label>Emergency Contact</label><input type="text" id="erEmergency" value="${d.emergency_contact||''}"></div></div>
            <div class="form-group"><label>Address</label><textarea id="erAddress">${d.address||''}</textarea></div>
            <div class="form-row"><div class="form-group"><label>Blood Group</label><input type="text" id="erBlood" value="${d.blood_group||''}"></div>
            <div class="form-group"><label>Status</label><select id="erStatus"><option value="active" ${(d.status||'active')==='active'?'selected':''}>Active</option><option value="cancelled" ${d.status==='cancelled'?'selected':''}>Cancelled</option></select></div></div>
            <div class="modal-footer"><button type="button" class="btn btn-ghost" onclick="closeModal('regModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button></div>
        </form>
    `;
    document.getElementById('editRegForm').addEventListener('submit', async e => {
        e.preventDefault();
        const fd = new FormData();
        fd.append('action','update_registration');
        fd.append('id', document.getElementById('erID').value);
        ['name:erName','mobile:erMobile','email:erEmail','date_of_birth:erDob','city:erCity','state:erState','document_type:erDocType','document_number:erDocNum','parent_name:erParent','emergency_contact:erEmergency','address:erAddress','blood_group:erBlood','status:erStatus'].forEach(p=>{
            const [k,id] = p.split(':');
            fd.append(k, document.getElementById(id).value);
        });
        const res = await fetch(API+'?action=update_registration',{method:'POST',body:fd});
        const jr = await res.json();
        if (jr.status==='success') { toast('Registration updated!'); closeModal('regModal'); loadRegistrations(); }
        else toast(jr.message,'error');
    });
    document.getElementById('regModal').classList.add('active');
}

async function deleteReg(id) {
    if (!confirm('Are you sure you want to delete this registration? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('action','delete_registration');
    fd.append('id', id);
    const r = await fetch(API+'?action=delete_registration',{method:'POST',body:fd});
    const j = await r.json();
    if (j.status==='success') { toast('Registration deleted'); loadRegistrations(); }
    else toast(j.message,'error');
}

function exportCSV() {
    const params = new URLSearchParams({action:'export_csv'});
    const fields = {name:'fName',mobile:'fMobile',date_from:'fDateFrom',date_to:'fDateTo',document_type:'fDocType'};
    for (const [k,id] of Object.entries(fields)) {
        const v = document.getElementById(id)?.value?.trim();
        if (v) params.set(k, v);
    }
    window.open(API + '?' + params.toString(), '_blank');
}

// ── TOURNAMENTS ────────────────────────────
async function loadTournaments() {
    const r = await fetch(API + '?action=tournaments');
    const j = await r.json();
    const tb = document.getElementById('tournTableBody');
    if (!j.data || j.data.length === 0) {
        tb.innerHTML = '<tr><td colspan="9" class="no-results"><i class="fas fa-trophy"></i><br>No tournaments yet. Add one!</td></tr>';
        return;
    }
    tb.innerHTML = j.data.map(t => `<tr>
        <td><strong>${t.tournament_id||''}</strong></td>
        <td>${t.name||''}</td>
        <td style="white-space:nowrap;font-size:0.8rem">${t.start_date||''}<br>${t.end_date||''}</td>
        <td>${t.venue||''}</td>
        <td>${t.city||''}</td>
        <td>${t.participants||0}</td>
        <td><span class="badge badge-${t.status}">${t.status||'upcoming'}</span></td>
        <td>${t.winner||'-'}</td>
        <td><div class="actions-cell">
            <button class="btn btn-primary" style="padding:5px 8px" onclick='editTournament(${JSON.stringify(t)})' title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger" style="padding:5px 8px" onclick="deleteTournament(${t.id})" title="Delete"><i class="fas fa-trash"></i></button>
        </div></td>
    </tr>`).join('');
}

function showAddTournament() {
    document.getElementById('tournModalTitle').innerHTML = '<i class="fas fa-plus"></i> Add Tournament';
    document.getElementById('tEditId').value = '';
    ['tName','tVenue','tStartDate','tEndDate','tCity','tWinner','tRunnerUp','tOrganizer','tPrize','tContact','tContactMobile','tDescription'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('tState').value = 'Maharashtra';
    document.getElementById('tParticipants').value = '0';
    document.getElementById('tStatus').value = 'upcoming';
    document.getElementById('tournamentModal').classList.add('active');
}

function editTournament(t) {
    document.getElementById('tournModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Tournament';
    document.getElementById('tEditId').value = t.id;
    document.getElementById('tName').value = t.name||'';
    document.getElementById('tVenue').value = t.venue||'';
    document.getElementById('tStartDate').value = t.start_date||'';
    document.getElementById('tEndDate').value = t.end_date||'';
    document.getElementById('tCity').value = t.city||'';
    document.getElementById('tState').value = t.state||'Maharashtra';
    document.getElementById('tParticipants').value = t.participants||0;
    document.getElementById('tStatus').value = t.status||'upcoming';
    document.getElementById('tWinner').value = t.winner||'';
    document.getElementById('tRunnerUp').value = t.runner_up||'';
    document.getElementById('tOrganizer').value = t.organizer||'';
    document.getElementById('tPrize').value = t.prize_money||'';
    document.getElementById('tContact').value = t.contact_person||'';
    document.getElementById('tContactMobile').value = t.contact_mobile||'';
    document.getElementById('tDescription').value = t.description||'';
    document.getElementById('tournamentModal').classList.add('active');
}

document.getElementById('tournamentForm').addEventListener('submit', async e => {
    e.preventDefault();
    const editId = document.getElementById('tEditId').value;
    const fd = new FormData();
    fd.append('action', editId ? 'update_tournament' : 'add_tournament');
    if (editId) fd.append('id', editId);
    ['name:tName','venue:tVenue','start_date:tStartDate','end_date:tEndDate','city:tCity','state:tState','participants:tParticipants','status:tStatus','winner:tWinner','runner_up:tRunnerUp','organizer:tOrganizer','prize_money:tPrize','contact_person:tContact','contact_mobile:tContactMobile','description:tDescription'].forEach(p => {
        const [k,id] = p.split(':');
        fd.append(k, document.getElementById(id).value);
    });
    const fileInput = document.getElementById('tImage');
    if (fileInput.files.length > 0) fd.append('image', fileInput.files[0]);

    const action = editId ? 'update_tournament' : 'add_tournament';
    const r = await fetch(API+'?action='+action,{method:'POST',body:fd});
    const j = await r.json();
    if (j.status==='success') { toast(editId ? 'Tournament updated!' : 'Tournament added!'); closeModal('tournamentModal'); loadTournaments(); }
    else toast(j.message,'error');
});

async function deleteTournament(id) {
    if (!confirm('Delete this tournament? This cannot be undone.')) return;
    const fd = new FormData();
    fd.append('action','delete_tournament');
    fd.append('id', id);
    const r = await fetch(API+'?action=delete_tournament',{method:'POST',body:fd});
    const j = await r.json();
    if (j.status==='success') { toast('Tournament deleted'); loadTournaments(); }
    else toast(j.message,'error');
}

// ── GALLERY ────────────────────────────────
async function loadGallery() {
    const cat = document.getElementById('galleryFilter').value;
    const params = new URLSearchParams({action:'gallery'});
    if (cat) params.set('category', cat);
    const r = await fetch(API+'?'+params.toString());
    const j = await r.json();
    const grid = document.getElementById('galleryGrid');
    // If no gallery images found, attempt a one-time filesystem sync then reload
    let galleryItems = j.data || [];
    if ((!galleryItems || galleryItems.length === 0) && !window._gallerySyncAttempted) {
        window._gallerySyncAttempted = true;
        await syncImages();
        const r2 = await fetch(API + '?action=gallery' + (cat ? ('&category=' + encodeURIComponent(cat)) : ''));
        const j2 = await r2.json();
        galleryItems = j2.data || [];
    }

    // Also fetch media items (imported from root or other folders) and include them
    const mediaResp = await fetch(API + '?action=media_list');
    const mediaJson = await mediaResp.json();
    const mediaItems = mediaJson.data || [];

    // Combine galleryItems and mediaItems into a render list
    const renderItems = [];
    galleryItems.forEach(img => { renderItems.push({ type: 'gallery', id: img.id, src: 'uploads/gallery/' + img.filename, caption: img.caption || '' }); });
    mediaItems.forEach(m => {
        // only include image-like media
        if (!m.filepath) return;
        let src = m.filepath;
        // if filepath is not already a URL under uploads, use as-is (root relative)
        renderItems.push({ type: 'media', id: m.id, src: src, caption: m.alt_text || '' });
    });

    if (!renderItems.length) {
        grid.innerHTML = '<div class="no-results" style="grid-column:1/-1"><i class="fas fa-images"></i><br>No images uploaded yet</div>';
        return;
    }

    grid.innerHTML = renderItems.map(item => `
        <div class="gallery-card">
            <img src="${item.src}" alt="${item.caption||''}" loading="lazy" onerror="this.src='data:image/svg+xml,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;160&quot; height=&quot;160&quot;><rect width=&quot;160&quot; height=&quot;160&quot; fill=&quot;%23111&quot;/><text x=&quot;50%&quot; y=&quot;50%&quot; font-size=&quot;40&quot; text-anchor=&quot;middle&quot; dominant-baseline=&quot;middle&quot;>🖼</text></svg>'">
            <div class="delete-overlay" onclick="${item.type==='gallery' ? 'deleteGallery('+item.id+')' : 'deleteMedia('+item.id+')'}"><i class="fas fa-trash"></i></div>
            ${item.caption ? '<div class="caption">'+item.caption+'</div>' : ''}
        </div>
    `).join('');
}

document.getElementById('galleryUploadForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action','upload_gallery');
    fd.append('category', document.getElementById('uploadCategory').value);
    fd.append('caption', document.getElementById('uploadCaption').value);
    const files = document.getElementById('uploadFiles').files;
    if (files.length === 0) { toast('Please select images','error'); return; }
    for (let i = 0; i < files.length; i++) fd.append('images[]', files[i]);
    const r = await fetch(API+'?action=upload_gallery',{method:'POST',body:fd});
    const j = await r.json();
    if (j.status==='success') {
        toast(j.message);
        document.getElementById('uploadFiles').value = '';
        document.getElementById('uploadCaption').value = '';
        loadGallery();
    } else toast(j.message,'error');
});

async function deleteGallery(id) {
    if (!confirm('Delete this image?')) return;
    const fd = new FormData();
    fd.append('action','delete_gallery');
    fd.append('id', id);
    const r = await fetch(API+'?action=delete_gallery',{method:'POST',body:fd});
    const j = await r.json();
    if (j.status==='success') { toast('Image deleted'); loadGallery(); }
}

// ── SETTINGS ───────────────────────────────
document.getElementById('changePasswordForm').addEventListener('submit', async e => {
    e.preventDefault();
    const np = document.getElementById('newPass').value;
    const cp = document.getElementById('confirmPass').value;
    if (np !== cp) { toast('Passwords do not match','error'); return; }
    const fd = new FormData();
    fd.append('action','change_password');
    fd.append('current_password', document.getElementById('currentPass').value);
    fd.append('new_password', np);
    const r = await fetch(API+'?action=change_password',{method:'POST',body:fd});
    const j = await r.json();
    if (j.status==='success') { toast(j.message); document.getElementById('changePasswordForm').reset(); }
    else toast(j.message,'error');
});

// ── CONTACT MESSAGES ───────────────────────
async function loadContacts() {
    const r = await fetch(API + '?action=contacts');
    const j = await r.json();
    const tb = document.getElementById('contactTableBody');
    if (!j.data || j.data.length === 0) {
        tb.innerHTML = '<tr><td colspan="7" class="no-results"><i class="fas fa-envelope-open"></i><br>No contact messages yet</td></tr>';
        document.getElementById('unreadBadge').textContent = '';
        return;
    }
    const unread = j.data.filter(c => !c.is_read).length;
    document.getElementById('unreadBadge').textContent = unread > 0 ? unread + ' unread' : '';
    tb.innerHTML = j.data.map(c => {
        const isUnread = !c.is_read;
        return `<tr style="${isUnread?'background:rgba(255,107,53,0.06)':''}">
            <td>${isUnread ? '<span class="badge" style="background:rgba(255,107,53,0.2);color:var(--accent)">NEW</span>' : '<span class="badge badge-active">Read</span>'}</td>
            <td style="font-weight:${isUnread?'700':'400'}">${c.name||''}</td>
            <td style="font-size:0.82rem">${c.email||''}</td>
            <td style="font-size:0.82rem">${c.phone||'-'}</td>
            <td style="font-size:0.82rem;max-width:250px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${(c.message||'').replace(/"/g,'&quot;')}">${c.message||''}</td>
            <td style="font-size:0.78rem;white-space:nowrap">${c.created_at ? new Date(c.created_at).toLocaleString('en-IN',{day:'2-digit',month:'short',year:'numeric',hour:'2-digit',minute:'2-digit'}) : '-'}</td>
            <td><div class="actions-cell">
                ${isUnread ? '<button class="btn btn-success" style="padding:5px 8px" onclick="markRead('+c.id+')" title="Mark Read"><i class="fas fa-check"></i></button>' : ''}
                <button class="btn btn-primary" style="padding:5px 8px" onclick="viewContact('+JSON.stringify(c).replace(/'/g,"\\'")+')'" title="View"><i class="fas fa-eye"></i></button>
                <button class="btn btn-danger" style="padding:5px 8px" onclick="deleteContact(${c.id})" title="Delete"><i class="fas fa-trash"></i></button>
            </div></td>
        </tr>`;
    }).join('');
}

async function markRead(id) {
    const fd = new FormData();
    fd.append('id', id);
    await fetch(API+'?action=mark_contact_read',{method:'POST',body:fd});
    toast('Marked as read');
    loadContacts();
}

function viewContact(c) {
    document.getElementById('regModalTitle').innerHTML = '<i class="fas fa-envelope"></i> Message from ' + (c.name||'');
    document.getElementById('regModalBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px 24px;font-size:0.9rem;margin-bottom:16px;">
            <div><strong>Name:</strong> ${c.name||''}</div>
            <div><strong>Email:</strong> <a href="mailto:${c.email||''}" style="color:var(--accent)">${c.email||''}</a></div>
            <div><strong>Phone:</strong> ${c.phone||'-'}</div>
            <div><strong>Date:</strong> ${c.created_at||''}</div>
        </div>
        <div style="background:rgba(0,0,0,0.2);padding:16px;border-radius:12px;border:1px solid var(--border);">
            <strong>Message:</strong><br><br>
            <div style="white-space:pre-wrap;line-height:1.6;">${c.message||''}</div>
        </div>
    `;
    document.getElementById('regModal').classList.add('active');
    if (!c.is_read) markRead(c.id);
}

async function deleteContact(id) {
    if (!confirm('Delete this message?')) return;
    const fd = new FormData();
    fd.append('id', id);
    await fetch(API+'?action=delete_contact',{method:'POST',body:fd});
    toast('Message deleted');
    loadContacts();
}

// ── COACHES ─────────────────────────────────
let coachSearchTimer;
function debounceCoachSearch() {
    clearTimeout(coachSearchTimer);
    coachSearchTimer = setTimeout(loadCoaches, 300);
}

async function loadCoaches() {
    const q = document.getElementById('coachSearch').value.trim();
    const params = new URLSearchParams({action:'coaches'});
    const r = await fetch(API+'?'+params.toString());
    const j = await r.json();
    const tb = document.getElementById('coachTableBody');
    if (!j.data || j.data.length === 0) {
        tb.innerHTML = '<tr><td colspan="6" class="no-results"><i class="fas fa-user-slash"></i><br>No coaches found</td></tr>';
        return;
    }
    const filtered = q ? j.data.filter(c => [c.username, c.name, c.district, c.mobile].some(v => (v||'').toLowerCase().includes(q.toLowerCase()))) : j.data;
    if (!filtered.length) {
        tb.innerHTML = '<tr><td colspan="6" class="no-results"><i class="fas fa-search"></i><br>No coaches match your search</td></tr>';
        return;
    }
    tb.innerHTML = filtered.map(c => `<tr>
        <td>${c.username||''}</td>
        <td>${c.name||''}</td>
        <td>${c.district||''}</td>
        <td>${c.mobile||''}</td>
        <td>${c.created_at||''}</td>
        <td><div class="actions-cell">
            <button class="btn btn-primary" style="padding:5px 8px" onclick="editCoach(${c.id})" title="Edit"><i class="fas fa-edit"></i></button>
            <button class="btn btn-danger" style="padding:5px 8px" onclick="deleteCoach(${c.id})" title="Delete"><i class="fas fa-trash"></i></button>
        </div></td>
    </tr>`).join('');
}

function clearCoachFilters() {
    document.getElementById('coachSearch').value = '';
    loadCoaches();
}

function showAddCoach() {
    document.getElementById('coachModalTitle').innerHTML = '<i class="fas fa-user-tie"></i> Add Coach';
    document.getElementById('coachId').value = '';
    ['coachName','coachUsername','coachDistrict','coachMobile','coachPassword'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('coachUsername').removeAttribute('readonly');
    document.getElementById('coachModal').classList.add('active');
}

async function editCoach(id) {
    const r = await fetch(API + '?action=coach_detail&id=' + id);
    const j = await r.json();
    if (j.status !== 'success') { toast(j.message,'error'); return; }
    const c = j.data;
    document.getElementById('coachModalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Coach';
    document.getElementById('coachId').value = c.id;
    document.getElementById('coachName').value = c.name || '';
    document.getElementById('coachUsername').value = c.username || '';
    document.getElementById('coachUsername').setAttribute('readonly', 'readonly');
    document.getElementById('coachDistrict').value = c.district || '';
    document.getElementById('coachMobile').value = c.mobile || '';
    document.getElementById('coachPassword').value = '';
    document.getElementById('coachModal').classList.add('active');
}

async function deleteCoach(id) {
    if (!confirm('Delete this coach and all their reports?')) return;
    const fd = new FormData();
    fd.append('action','delete_coach');
    fd.append('id', id);
    const r = await fetch(API+'?action=delete_coach',{method:'POST',body:fd});
    const j = await r.json();
    if (j.status === 'success') { toast('Coach deleted'); loadCoaches(); loadCoachReports(); }
    else toast(j.message,'error');
}

async function loadCoachReports() {
    const r = await fetch(API + '?action=coach_reports');
    const j = await r.json();
    const tb = document.getElementById('coachReportBody');
    if (!j.data || j.data.length === 0) {
        tb.innerHTML = '<tr><td colspan="8" class="no-results"><i class="fas fa-file-alt"></i><br>No coach reports available</td></tr>';
        return;
    }
    tb.innerHTML = j.data.map(rp => `<tr>
        <td>${rp.id}</td>
        <td>${rp.coach_name||rp.coach_username||'-'}</td>
        <td>${rp.coach_district||'-'}</td>
        <td>${rp.coach_mobile||'-'}</td>
        <td>${rp.event_name||'-'}</td>
        <td style="max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="${(rp.report_text||'').replace(/"/g,'&quot;')}">${rp.report_text||'-'}</td>
        <td>${rp.created_at||''}</td>
        <td><button class="btn btn-primary" style="padding:5px 8px" onclick="viewReport(${rp.id})"><i class="fas fa-eye"></i></button></td>
    </tr>`).join('');
}

function viewReport(id) {
    const rIndex = document.getElementById('coachReportBody').querySelectorAll('tr');
    const found = Array.from(rIndex).find(row => row.firstChild && row.firstChild.textContent.trim() == id);
    if (!found) return;
    const cells = found.children;
    const title = cells[1].textContent.trim();
    const message = `<div style="font-size:0.95rem;line-height:1.6;"><strong>Coach:</strong> ${cells[1].textContent.trim()}<br><strong>District:</strong> ${cells[2].textContent.trim()}<br><strong>Mobile:</strong> ${cells[3].textContent.trim()}<br><strong>Event:</strong> ${cells[4].textContent.trim()}<br><strong>Report:</strong><br>${cells[5].textContent.trim()}</div>`;
    document.getElementById('regModalTitle').innerHTML = '<i class="fas fa-file-alt"></i> Report from ' + title;
    document.getElementById('regModalBody').innerHTML = message;
    document.getElementById('regModal').classList.add('active');
}

document.getElementById('coachForm').addEventListener('submit', async e => {
    e.preventDefault();
    const id = document.getElementById('coachId').value;
    const fd = new FormData();
    fd.append('action', id ? 'update_coach' : 'add_coach');
    if (id) fd.append('id', id);
    fd.append('name', document.getElementById('coachName').value.trim());
    fd.append('district', document.getElementById('coachDistrict').value.trim());
    fd.append('mobile', document.getElementById('coachMobile').value.trim());
    if (!id) fd.append('username', document.getElementById('coachUsername').value.trim());
    const pw = document.getElementById('coachPassword').value;
    if (pw) fd.append('password', pw);
    const action = id ? 'update_coach' : 'add_coach';
    const r = await fetch(API + '?action=' + action, { method: 'POST', body: fd });
    const j = await r.json();
    if (j.status === 'success') {
        toast(id ? 'Coach updated successfully' : 'Coach added successfully');
        closeModal('coachModal');
        loadCoaches();
        loadCoachReports();
    } else {
        toast(j.message, 'error');
    }
});

// ── INIT ───────────────────────────────────
// ── MEDIA / HERO CLIENT FUNCTIONS ─────────────────
function openMediaUpload() { document.getElementById('mediaUploadForm').reset(); document.getElementById('mediaUploadModal').classList.add('active'); }

async function loadMedia() {
    const section = document.getElementById('mediaSectionFilter').value || '';
    const params = new URLSearchParams({action:'media_list'});
    if (section) params.set('section', section);
    const r = await fetch(API + '?' + params.toString());
    const j = await r.json();
    const grid = document.getElementById('mediaGrid');
    if (!j.data || j.data.length === 0) {
        if (!window._mediaSyncAttempted) {
            window._mediaSyncAttempted = true;
            await syncImages();
            const r2 = await fetch(API + '?' + params.toString());
            const j2 = await r2.json();
            if (j2.data && j2.data.length) j = j2; else { grid.innerHTML = '<div class="no-results" style="grid-column:1/-1"><i class="fas fa-photo-video"></i><br>No media found</div>'; return; }
        } else {
            grid.innerHTML = '<div class="no-results" style="grid-column:1/-1"><i class="fas fa-photo-video"></i><br>No media found</div>';
            return;
        }
    }
    grid.innerHTML = j.data.map(m => `
        <div class="gallery-card">
            <img src="${m.filepath}" alt="${m.alt_text||''}" loading="lazy" onerror="this.src='data:image/svg+xml,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;160&quot; height=&quot;160&quot;><rect width=&quot;160&quot; height=&quot;160&quot; fill=&quot;%23111&quot;/><text x=&quot;50%&quot; y=&quot;50%&quot; font-size=&quot;40&quot; text-anchor=&quot;middle&quot; dominant-baseline=&quot;middle&quot;>🖼</text></svg>'">
            <div class="delete-overlay" onclick="deleteMedia(${m.id})"><i class="fas fa-trash"></i></div>
            <div style="position:absolute;left:6px;top:6px;display:flex;gap:6px;">
                <button class="btn btn-ghost" onclick="toggleMedia(${m.id},${m.enabled?0:1})">${m.enabled?'<i class=\"fas fa-eye\"></i>':'<i class=\"fas fa-eye-slash\"></i>'}</button>
            </div>
        </div>
    `).join('');
}

document.getElementById('mediaUploadForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action','media_upload');
    fd.append('section', document.getElementById('mediaSection').value);
    fd.append('alt_text', document.getElementById('mediaAlt').value);
    const f = document.getElementById('mediaFile').files[0];
    if (!f) { toast('Select an image','error'); return; }
    fd.append('image', f);
    const r = await fetch(API + '?action=media_upload', { method:'POST', body: fd});
    const j = await r.json();
    if (j.status === 'success') { toast('Uploaded'); closeModal('mediaUploadModal'); loadMedia(); } else toast(j.message,'error');
});

async function deleteMedia(id) {
    if (!confirm('Delete this media?')) return;
    const fd = new FormData(); fd.append('action','media_delete'); fd.append('id', id);
    const r = await fetch(API + '?action=media_delete',{method:'POST',body:fd}); const j = await r.json();
    if (j.status === 'success') { toast('Deleted'); loadMedia(); } else toast(j.message,'error');
}

async function toggleMedia(id, enabled) {
    const fd = new FormData(); fd.append('action','media_toggle'); fd.append('id', id); fd.append('enabled', enabled);
    const r = await fetch(API + '?action=media_toggle',{method:'POST',body:fd}); const j = await r.json();
    if (j.status === 'success') { toast('Updated'); loadMedia(); } else toast(j.message,'error');
}

// Sync filesystem images into DB (scans common folders)
async function syncImages() {
    if (!confirm('Scan server folders and import any missing images into the admin library?')) return;
    const r = await fetch(API + '?action=sync_media', { method: 'POST' });
    const j = await r.json();
    if (j.status === 'success') {
        const addedGallery = j.inserted.gallery.length;
        const addedMedia = j.inserted.media.length;
        toast(`Imported ${addedGallery} gallery and ${addedMedia} media items`);
        loadGallery(); loadMedia(); loadHero();
    } else {
        toast(j.message || 'Sync failed','error');
    }
}

// Hero functions
function showHeroModal(id) {
    document.getElementById('heroForm').reset(); document.getElementById('heroId').value = id ? id : '';
    document.getElementById('heroModalTitle').textContent = id ? 'Edit Banner' : 'Add Banner';
    document.getElementById('heroModal').classList.add('active');
    if (id) {
        fetch(API + '?action=hero_list').then(r=>r.json()).then(j=>{
            if (j.status==='success') {
                const item = j.data.find(x=>x.id==id);
                if (item) {
                    document.getElementById('heroTitle').value = item.title||'';
                    document.getElementById('heroSubtitle').value = item.subtitle||'';
                    document.getElementById('heroLink').value = item.link||'';
                    document.getElementById('heroPosition').value = item.position||0;
                    document.getElementById('heroEnabled').value = item.enabled?1:0;
                }
            }
        });
    }
}

document.getElementById('heroForm').addEventListener('submit', async e => {
    e.preventDefault();
    const fd = new FormData();
    fd.append('action','hero_save');
    const id = document.getElementById('heroId').value; if (id) fd.append('id', id);
    fd.append('title', document.getElementById('heroTitle').value);
    fd.append('subtitle', document.getElementById('heroSubtitle').value);
    fd.append('link', document.getElementById('heroLink').value);
    fd.append('position', document.getElementById('heroPosition').value);
    fd.append('enabled', document.getElementById('heroEnabled').value);
    const f = document.getElementById('heroImage').files[0]; if (f) fd.append('image', f);
    const r = await fetch(API + '?action=hero_save', { method:'POST', body: fd }); const j = await r.json();
    if (j.status === 'success') { toast(j.message); closeModal('heroModal'); loadHero(); } else toast(j.message,'error');
});

async function loadHero() {
    const r = await fetch(API + '?action=hero_list'); const j = await r.json();
    const tb = document.getElementById('heroTableBody');
    if (!j.data || j.data.length === 0) { tb.innerHTML = '<tr><td colspan="7" class="no-results"><i class="fas fa-film"></i><br>No banners configured</td></tr>'; return; }
    tb.innerHTML = j.data.map(h => `<tr>
        <td>${h.id}</td>
        <td>${h.title||'-'}</td>
        <td style="max-width:200px"><img src="${h.media_path||h.image_path||''}" style="width:160px;height:60px;object-fit:cover;border-radius:8px;" onerror="this.src='data:image/svg+xml,<svg xmlns=&quot;http://www.w3.org/2000/svg&quot; width=&quot;160&quot; height=&quot;60&quot;><rect width=&quot;160&quot; height=&quot;60&quot; fill=&quot;%23111&quot;/></svg>'"></td>
        <td>${h.link||'-'}</td>
        <td>${h.enabled?'<span class="badge badge-active">Yes</span>':'<span class="badge badge-cancelled">No</span>'}</td>
        <td>${h.position||0}</td>
        <td><div class="actions-cell"><button class="btn btn-primary" onclick="showHeroModal(${h.id})"><i class="fas fa-edit"></i></button><button class="btn btn-danger" onclick="deleteHero(${h.id})"><i class="fas fa-trash"></i></button></div></td>
    </tr>`).join('');
}

async function deleteHero(id) {
    if (!confirm('Delete this banner?')) return; const fd = new FormData(); fd.append('action','hero_delete'); fd.append('id', id);
    const r = await fetch(API + '?action=hero_delete',{method:'POST',body:fd}); const j = await r.json(); if (j.status==='success') { toast('Deleted'); loadHero(); } else toast(j.message,'error');
}

// ── INIT ───────────────────────────────────
loadDashboard();
loadGallery();
// Preload media and hero counts
loadMedia();
loadHero();
</script>
</body>
</html>
