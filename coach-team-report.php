<?php
/**
 * TCAM Coach Team Entry Report (PDF with photos)
 */
// FPDF builds the whole PDF (every embedded photo included) in memory before
// output, so a large team selection can exceed the container's default
// 128M limit — bump it just for this request.
ini_set('memory_limit', '512M');

require_once 'coach-auth.php';
require_once 'secure-database.php';
require_once 'config.php';
require_once 'includes/team-report-pdf.php';

$selectedIds = $_POST['selected_ids'] ?? [];
$district = $_SESSION['coach_district'];
$coachId = $_SESSION['coach_user_id'];

$selectedIds = array_map('intval', is_array($selectedIds) ? $selectedIds : []);
$selectedIds = array_values(array_filter($selectedIds, fn($id) => $id > 0));
if (empty($selectedIds)) {
    header('Location: coach-panel.php?error=1');
    exit;
}

$title = trim($_POST['report_title'] ?? '');
$venue = trim($_POST['report_venue'] ?? '');
$dates = trim($_POST['report_dates'] ?? '');
$teamLabel = trim($_POST['report_team_label'] ?? '');

$db = SecureDatabase::getInstance();
$placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
// Coaches can select students from any district, not just their own.
$sql = "SELECT * FROM bookings WHERE id IN ($placeholders) ORDER BY created_at DESC";
$bookingRows = $db->execute($sql, $selectedIds)->fetchAll();

if (empty($bookingRows)) {
    header('Location: coach-panel.php?error=1');
    exit;
}

// The PDF table layout was built for the `registrations` table's columns
// (reg_id, parent_name, address, city, state); `bookings` only has
// name/dob/district/phone/photo, so map what's available and leave the
// rest blank. Photo paths in `bookings` are stored as "uploads/xxx.jpg" or
// "/uploads/xxx.jpg" (already including the folder), while the renderer
// resolves photos relative to UPLOAD_DIR itself, so strip that prefix.
$registrations = array_map(function ($row) {
    return [
        'reg_id' => $row['bookingId'] ?? '',
        'name' => $row['name'] ?? '',
        'parent_name' => '',
        'dob' => $row['dob'] ?? '',
        'address' => '',
        'city' => $row['district'] ?? '',
        'state' => '',
        'photo' => preg_replace('#^/?uploads/#', '', $row['photo'] ?? ''),
    ];
}, $bookingRows);

$meta = [
    'title' => $title,
    'venue' => $venue,
    'dates' => $dates,
    'team_label' => $teamLabel !== '' ? $teamLabel : $district,
    'coach_name' => $_SESSION['coach_name'] ?? $_SESSION['coach_username'] ?? '',
];

$db->insert('coach_reports', [
    'coach_id' => $coachId,
    'district' => $district,
    'event_name' => $title,
    'report_text' => 'Team Entry Report (PDF with photos)',
    'selected_registrations' => json_encode(array_column($bookingRows, 'id')),
]);

$pdfContent = renderTeamEntryReportPdf($registrations, $meta, UPLOAD_DIR);

$filename = 'TCAM_Team_Entry_Report_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $district) . '_' . date('Ymd_His') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
exit;
