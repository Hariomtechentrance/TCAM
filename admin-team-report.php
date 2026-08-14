<?php
/**
 * TCAM Admin Team Entry Report (PDF with photos)
 */
require_once 'security-config.php';
session_start();
require_once 'secure-database.php';
require_once 'config.php';
require_once 'includes/team-report-pdf.php';

if (empty($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login-production.php');
    exit;
}

$selectedIds = $_POST['selected_ids'] ?? [];
$selectedIds = array_map('intval', is_array($selectedIds) ? $selectedIds : []);
$selectedIds = array_values(array_filter($selectedIds, fn($id) => $id > 0));
if (empty($selectedIds)) {
    header('Location: admin-dashboard.php?error=1');
    exit;
}

$title = trim($_POST['report_title'] ?? '');
$venue = trim($_POST['report_venue'] ?? '');
$dates = trim($_POST['report_dates'] ?? '');
$teamLabel = trim($_POST['report_team_label'] ?? '');

$db = SecureDatabase::getInstance();
$placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
$sql = "SELECT * FROM registrations WHERE id IN ($placeholders) ORDER BY name ASC";
$registrations = $db->execute($sql, $selectedIds)->fetchAll();

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
