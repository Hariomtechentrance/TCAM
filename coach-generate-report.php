<?php
/**
 * TCAM Coach Selected Candidate PDF Export
 */
require_once 'coach-auth.php';
require_once 'secure-database.php';
require_once 'lib/fpdf/fpdf.php';

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

// Coaches can select students from any district, not just their own.
$sql = "SELECT * FROM bookings WHERE id IN ($placeholders) ORDER BY name ASC";
$stmt = $db->execute($sql, $selectedIds);
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

$filename = 'TCAM_Coach_Report_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $district) . '_' . date('Ymd_His') . '.pdf';
$coachName = $_SESSION['coach_name'] ?? $_SESSION['coach_username'] ?? '';

function pdfSafe($text) {
    $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', (string)$text);
    return $converted !== false ? $converted : (string)$text;
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

$pdf->SetFont('Helvetica', 'B', 16);
$pdf->Cell(0, 10, 'TCAM Coach Report', 0, 1);
$pdf->SetFont('Helvetica', '', 11);
$pdf->Cell(0, 7, pdfSafe('Coach: ' . $coachName), 0, 1);
if ($eventName !== '') {
    $pdf->Cell(0, 7, pdfSafe('Event: ' . $eventName), 0, 1);
}
$pdf->Cell(0, 7, 'Generated: ' . date('Y-m-d H:i:s'), 0, 1);
$pdf->Ln(3);

if ($reportText !== '') {
    $pdf->SetFont('Helvetica', 'B', 11);
    $pdf->Cell(0, 7, 'Coach Notes:', 0, 1);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->MultiCell(0, 6, pdfSafe($reportText));
    $pdf->Ln(3);
}

$pdf->SetFont('Helvetica', 'B', 12);
$pdf->Cell(0, 8, 'Selected Candidates (' . count($registrations) . ')', 0, 1);
$pdf->SetDrawColor(180, 180, 180);
$pdf->SetFont('Helvetica', '', 10);
foreach ($registrations as $registration) {
    $pdf->SetFont('Helvetica', 'B', 10);
    $pdf->Cell(0, 6, pdfSafe('Name: ' . $registration['name']), 0, 1);
    $pdf->SetFont('Helvetica', '', 10);
    $pdf->Cell(0, 6, pdfSafe('Booking ID: ' . $registration['bookingId']), 0, 1);
    $pdf->Cell(0, 6, pdfSafe('Mobile: ' . $registration['phone']), 0, 1);
    $pdf->Cell(0, 6, pdfSafe('DOB: ' . $registration['dob']), 0, 1);
    $pdf->Cell(0, 6, pdfSafe('District: ' . $registration['district']), 0, 1);
    $y = $pdf->GetY() + 2;
    $pdf->Line(10, $y, 200, $y);
    $pdf->Ln(6);
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
echo $pdf->Output('S');
exit;
