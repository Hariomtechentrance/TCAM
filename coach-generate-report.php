<?php
/**
 * TCAM Coach Selected Candidate PDF Export
 */
require_once 'coach-auth.php';
require_once 'secure-database.php';

$selectedIds = $_POST['selected_ids'] ?? [];
$reportText = trim($_POST['report_text'] ?? '');
$eventName = trim($_POST['event_name'] ?? '');
$district = $_SESSION['coach_district'];
$coachId = $_SESSION['coach_user_id'];

if (empty($selectedIds) || !is_array($selectedIds)) {
    header('Location: coach-panel.php?error=1');
    exit;
}

$selectedIds = array_map('intval', $selectedIds);
$selectedIds = array_filter($selectedIds, fn($id) => $id > 0);
if (empty($selectedIds)) {
    header('Location: coach-panel.php?error=1');
    exit;
}

$db = SecureDatabase::getInstance();
$placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
$params = array_merge([$district], $selectedIds);

$sql = "SELECT r.*, GROUP_CONCAT(DISTINCT er.event_name, ', ') as events FROM registrations r LEFT JOIN event_registrations er ON er.registration_id = r.id WHERE r.district = ? AND r.id IN ($placeholders) GROUP BY r.id ORDER BY r.name ASC";
$stmt = $db->execute($sql, $params);
$registrations = $stmt->fetchAll();

if (empty($registrations)) {
    header('Location: coach-panel.php?error=1');
    exit;
}

$reportData = [
    'coach_id' => $coachId,
    'district' => $district,
    'event_name' => $eventName,
    'report_text' => $reportText,
    'selected_registrations' => json_encode(array_column($registrations, 'id')),
];
$db->insert('coach_reports', $reportData);

function pdfEncode($string) {
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $string);
}

$filename = 'TCAM_Coach_Report_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $district) . '_' . date('Ymd_His') . '.pdf';

$lines = [];
$lines[] = "TCAM Coach Report";
$lines[] = "District: $district";
$lines[] = "Coach: " . ($_SESSION['coach_username'] ?? '');
if ($eventName !== '') {
    $lines[] = "Event: $eventName";
}
$lines[] = "Generated: " . date('Y-m-d H:i:s');
$lines[] = "";
if ($reportText !== '') {
    $lines[] = "Coach Notes:";
    foreach (preg_split('/\r?\n/', wordwrap($reportText, 90, "\n")) as $noteLine) {
        $lines[] = $noteLine;
    }
    $lines[] = "";
}
$lines[] = "Selected Candidates:";
$lines[] = str_repeat('=', 40);
foreach ($registrations as $registration) {
    $lines[] = sprintf("Name: %s", $registration['name']);
    $lines[] = sprintf("TCAM ID: %s", $registration['reg_id']);
    $lines[] = sprintf("Mobile: %s", $registration['mobile']);
    $lines[] = sprintf("District: %s", $registration['district']);
    $lines[] = sprintf("City: %s", $registration['city']);
    $lines[] = sprintf("Event(s): %s", $registration['events'] ?? '');
    $lines[] = str_repeat('-', 40);
}

$pdfContent = "%PDF-1.3\n";
$pdfContent .= "1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj\n";

$textParts = [];
foreach ($lines as $line) {
    $textParts[] = "(" . pdfEncode($line) . ") Tj T*";
}
$textStream = "BT /F1 12 Tf 40 760 Td " . implode(' ', $textParts) . " ET";
$streamLength = strlen($textStream);

$pdfContent .= "2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj\n";
$pdfContent .= "3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj\n";
$pdfContent .= "4 0 obj<< /Length $streamLength >>stream\n$textStream\nendstream\nendobj\n";
$pdfContent .= "5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj\n";

$xref = strlen($pdfContent);
$pdfContent .= "xref\n0 6\n0000000000 65535 f \n";
$offsets = [1 => 9, 2 => 52, 3 => 103, 4 => 213, 5 => 278];
foreach ($offsets as $offset) {
    $pdfContent .= sprintf('%010d 00000 n \n', $offset);
}
$pdfContent .= "trailer<< /Size 6 /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $pdfContent;
exit;
