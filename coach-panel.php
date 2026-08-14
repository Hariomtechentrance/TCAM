<?php
/**
 * TCAM Coach Panel
 * Shows district registrations and generates coach reports.
 */
require_once 'coach-auth.php';
require_once 'secure-database.php';

$db = SecureDatabase::getInstance();
$district = $_SESSION['coach_district'];
$search = trim($_GET['search'] ?? '');

// Every coach can see every student, regardless of which district they
// registered under — the coach's own district is only used for the PDF
// filename/team label, never to restrict who shows up here.
$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(name LIKE ? OR phone LIKE ? OR bookingId LIKE ? OR email LIKE ? OR district LIKE ?)";
    $term = "%$search%";
    array_push($params, $term, $term, $term, $term, $term);
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$perPage = 100;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = (int)$db->execute("SELECT COUNT(*) c FROM bookings $whereSql", $params)->fetch()['c'];
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT * FROM bookings $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$results = $db->execute($sql, $params)->fetchAll();

function htmlEscape($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coach Panel — TCAM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin:0; padding:0; }
        .topbar { background:#1f2937; color:#fff; padding:18px 24px; display:flex; justify-content:space-between; flex-wrap:wrap; align-items:center; gap:12px; }
        .topbar h1 { font-size:1.3rem; margin:0; }
        .topbar a { color:#d1d5db; text-decoration:none; margin-left:12px; }
        .container { max-width:1200px; margin:24px auto; padding:0 18px; }
        .panel { background:#fff; border-radius:18px; padding:24px; box-shadow:0 16px 40px rgba(15,23,42,0.08); }
        .panel h2 { margin:0 0 16px; font-size:1.2rem; color:#111827; }
        .filters { display:grid; grid-template-columns:1fr auto; gap:12px; margin-bottom:20px; }
        .filters input, .filters select { width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:12px; font-size:1rem; }
        .filters button { padding:12px 18px; border:none; border-radius:12px; background:#2563eb; color:#fff; cursor:pointer; }
        table { width:100%; border-collapse:collapse; margin-top:12px; }
        th, td { padding:14px 12px; border-bottom:1px solid #e5e7eb; text-align:left; vertical-align:middle; }
        th { background:#f9fafb; color:#374151; font-weight:700; }
        tr:hover { background:#f8fafc; }
        .text-small { color:#6b7280; font-size:.95rem; }
        .report-area { margin-top:24px; }
        .report-area textarea { width:100%; min-height:140px; padding:14px; border:1px solid #d1d5db; border-radius:14px; font-size:1rem; resize:vertical; }
        .report-area button { margin-top:14px; padding:14px 18px; border:none; border-radius:12px; background:#10b981; color:#fff; font-weight:700; cursor:pointer; }
        .select-all { display:inline-flex; align-items:center; gap:10px; margin-bottom:8px; color:#374151; }
        .empty-state { padding:40px; text-align:center; color:#6b7280; }
        @media (max-width: 780px) { .filters { grid-template-columns:1fr; } }
    </style>
    <script>
        function toggleSelectAll(source) {
            document.querySelectorAll('input[name="selected_ids[]"]').forEach(function(checkbox) {
                checkbox.checked = source.checked;
            });
        }
    </script>
</head>
<body>
    <div class="topbar">
        <div>
            <h1>Coach Dashboard</h1>
            <div class="text-small">Your district: <?php echo htmlEscape($district); ?> — Logged in as <?php echo htmlEscape($_SESSION['coach_username']); ?></div>
        </div>
        <div>
            <a href="coach-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <h2>All Student Registrations</h2>
            <form class="filters" method="GET" action="coach-panel.php">
                <input type="text" name="search" placeholder="Search by name / mobile / booking ID / email / district" value="<?php echo htmlEscape($search); ?>">
                <button type="submit">Filter</button>
            </form>
            <div class="text-small" style="margin-bottom:12px;"><?php echo (int)$total; ?> student<?php echo $total === 1 ? '' : 's'; ?> found — page <?php echo $page; ?> of <?php echo $totalPages; ?></div>

            <?php if (empty($results)): ?>
                <div class="empty-state">No students found. Please adjust the search.</div>
            <?php else: ?>
            <form method="POST" action="coach-generate-report.php" autocomplete="off">
                <div class="select-all">
                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)" autocomplete="off">
                    <label for="selectAll">Select all visible students</label>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Booking ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>DOB</th>
                            <th>District</th>
                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_ids[]" value="<?php echo (int)$row['id']; ?>" autocomplete="off"></td>
                                <td><?php echo htmlEscape($row['bookingId']); ?></td>
                                <td><?php echo htmlEscape($row['name']); ?></td>
                                <td><?php echo htmlEscape($row['phone']); ?></td>
                                <td><?php echo htmlEscape($row['dob']); ?></td>
                                <td><?php echo htmlEscape($row['district']); ?></td>
                                <td><?php echo htmlEscape($row['created_at'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                <div style="display:flex; gap:10px; align-items:center; margin-top:16px;">
                    <?php if ($page > 1): ?>
                        <a href="coach-panel.php?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>">&larr; Previous</a>
                    <?php endif; ?>
                    <span class="text-small">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="coach-panel.php?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>">Next &rarr;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="report-area">
                    <label for="report_text">Coach Report</label>
                    <textarea id="report_text" name="report_text" placeholder="Write your report here... eg. These candidates are appearing for the tournament, selected for district squad."></textarea>
                    <button type="submit" formaction="coach-generate-report.php">Download Selected Students PDF</button>
                </div>

                <div class="report-area">
                    <h2 style="margin:0 0 14px;">Team Entry Report (PDF with photos)</h2>
                    <label for="report_title">Championship / Tournament Title</label>
                    <input type="text" id="report_title" name="report_title" placeholder="e.g. 5th Senior National Tennis Cricket Championship (Men/Women) - 2026" style="width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:12px; margin-bottom:14px;">
                    <label for="report_venue">Venue</label>
                    <input type="text" id="report_venue" name="report_venue" placeholder="e.g. Centurion University Bhubaneswar" style="width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:12px; margin-bottom:14px;">
                    <label for="report_dates">Dates</label>
                    <input type="text" id="report_dates" name="report_dates" placeholder="e.g. 09-Mar-2026 To 14-Mar-2026" style="width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:12px; margin-bottom:14px;">
                    <label for="report_team_label">Team Label</label>
                    <input type="text" id="report_team_label" name="report_team_label" placeholder="e.g. <?php echo htmlEscape($district); ?> (Women)" style="width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:12px; margin-bottom:14px;">
                    <button type="submit" formaction="coach-team-report.php" style="background:#7c3aed;">Download Team Entry Report (PDF with Photos)</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
