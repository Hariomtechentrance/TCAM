<?php
/**
 * TCAM Admin Team Entry Report (PDF with photos)
 */
// FPDF builds the whole PDF (every embedded photo included) in memory before
// output, so a large team selection can exceed the container's default
// 128M limit — bump it just for this request.
ini_set('memory_limit', '512M');

require_once 'security-config.php';
session_start();
require_once 'secure-database.php';
require_once 'config.php';
require_once 'includes/team-report-pdf.php';

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-production.php');
    exit;
}

// Checkbox values are "booking:123" or "registration:45" since rows can
// come from either table.
$rawSelected = $_POST['selected_ids'] ?? [];
$bookingIds = [];
$regIds = [];
foreach ((is_array($rawSelected) ? $rawSelected : []) as $raw) {
    if (preg_match('/^(booking|registration):(\d+)$/', (string) $raw, $m)) {
        if ($m[1] === 'booking') {
            $bookingIds[] = (int) $m[2];
        } else {
            $regIds[] = (int) $m[2];
        }
    } elseif (ctype_digit((string) $raw)) {
        // Back-compat: plain numeric ids are treated as registrations.
        $regIds[] = (int) $raw;
    }
}
if (empty($bookingIds) && empty($regIds)) {
    header('Location: admin-dashboard.php?error=1');
    exit;
}

$title = trim($_POST['report_title'] ?? '');
$venue = trim($_POST['report_venue'] ?? '');
$dates = trim($_POST['report_dates'] ?? '');
$teamLabel = trim($_POST['report_team_label'] ?? '');

$db = SecureDatabase::getInstance();
$registrations = [];

if (!empty($regIds)) {
    $placeholders = implode(',', array_fill(0, count($regIds), '?'));
    $rows = $db->execute("SELECT * FROM registrations WHERE id IN ($placeholders)", $regIds)->fetchAll();
    foreach ($rows as $row) {
        $registrations[] = $row;
    }
}

if (!empty($bookingIds)) {
    $placeholders = implode(',', array_fill(0, count($bookingIds), '?'));
    $rows = $db->execute("SELECT * FROM bookings WHERE id IN ($placeholders)", $bookingIds)->fetchAll();
    foreach ($rows as $row) {
        // The PDF template expects registrations-table columns; bookings
        // only has name/dob/district/phone/photo, so map what's available.
        $registrations[] = [
            'reg_id' => $row['bookingId'] ?? '',
            'name' => $row['name'] ?? '',
            'parent_name' => '',
            'dob' => $row['dob'] ?? '',
            'address' => '',
            'city' => $row['district'] ?? '',
            'state' => '',
            'photo' => preg_replace('#^/?uploads/#', '', $row['photo'] ?? ''),
        ];
    }
}

usort($registrations, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));

if (empty($registrations)) {
    header('Location: admin-dashboard.php?error=1');
    exit;
}

$meta = [
    'title' => $title,
    'venue' => $venue,
    'dates' => $dates,
    'team_label' => $teamLabel,
];

$pdfContent = renderTeamEntryReportPdf($registrations, $meta, UPLOAD_DIR);

$filename = 'TCAM_Team_Entry_Report_' . date('Ymd_His') . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfContent));
echo $pdfContent;
exit;
