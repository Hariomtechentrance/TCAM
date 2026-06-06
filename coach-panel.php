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
$event_name = trim($_GET['event_name'] ?? '');

$eventRows = $db->execute("SELECT DISTINCT er.event_name FROM event_registrations er JOIN registrations r ON r.id = er.registration_id WHERE r.district = ? ORDER BY er.event_name ASC", [$district])->fetchAll();
$eventOptions = array_column($eventRows, 'event_name');

$where = ['r.district = ?'];
$params = [$district];
if ($search !== '') {
    $where[] = "(r.name LIKE ? OR r.mobile LIKE ? OR r.reg_id LIKE ? OR r.document_number LIKE ? OR r.city LIKE ? OR r.state LIKE ? )";
    $term = "%$search%";
    array_push($params, $term, $term, $term, $term, $term, $term);
}
if ($event_name !== '') {
    $where[] = "er.event_name = ?";
    $params[] = $event_name;
}

$sql = "SELECT r.*, GROUP_CONCAT(DISTINCT er.event_name, ', ') as events FROM registrations r LEFT JOIN event_registrations er ON er.registration_id = r.id";
if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " GROUP BY r.id ORDER BY r.created_at DESC LIMIT 300";
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
            <div class="text-small">District: <?php echo htmlEscape($district); ?> — Logged in as <?php echo htmlEscape($_SESSION['coach_username']); ?></div>
        </div>
        <div>
            <a href="coach-logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="container">
        <div class="panel">
            <h2>District Registrations</h2>
            <form class="filters" method="GET" action="coach-panel.php">
                <input type="text" name="search" placeholder="Search by name / mobile / TCAM ID / city" value="<?php echo htmlEscape($search); ?>">
                <select name="event_name">
                    <option value="">All tournaments/events</option>
                    <?php foreach ($eventOptions as $eventOption): ?>
                        <option value="<?php echo htmlEscape($eventOption); ?>" <?php echo $eventOption === $event_name ? 'selected' : ''; ?>><?php echo htmlEscape($eventOption); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Filter</button>
            </form>

            <?php if (empty($results)): ?>
                <div class="empty-state">No candidates found for this district and filter. Please adjust the search or select a different event.</div>
            <?php else: ?>
            <form method="POST" action="coach-generate-report.php">
                <div class="select-all">
                    <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                    <label for="selectAll">Select all visible candidates</label>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>TCAM ID</th>
                            <th>Name</th>
                            <th>Mobile</th>
                            <th>City</th>
                            <th>Event(s)</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $row): ?>
                            <tr>
                                <td><input type="checkbox" name="selected_ids[]" value="<?php echo (int)$row['id']; ?>"></td>
                                <td><?php echo htmlEscape($row['reg_id']); ?></td>
                                <td><?php echo htmlEscape($row['name']); ?></td>
                                <td><?php echo htmlEscape($row['mobile']); ?></td>
                                <td><?php echo htmlEscape($row['city']); ?></td>
                                <td><?php echo htmlEscape($row['events'] ?: '-'); ?></td>
                                <td><?php echo htmlEscape($row['joined'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="report-area">
                    <label for="event_name">Tournament / Event Name</label>
                    <input type="text" id="event_name" name="event_name" value="<?php echo htmlEscape($event_name); ?>" style="width:100%; padding:12px 14px; border:1px solid #d1d5db; border-radius:12px; margin-bottom:14px;">
                    <label for="report_text">Coach Report</label>
                    <textarea id="report_text" name="report_text" placeholder="Write your report here... eg. These candidates are appearing for the tournament, selected for district squad."></textarea>
                    <button type="submit">Download Selected Students PDF</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
