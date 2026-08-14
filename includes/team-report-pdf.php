<?php
/**
 * TCAM Team Entry Report PDF
 * Renders a bordered roster table (Sr No / Reg No / Name / Father's Name /
 * DOB / Address / City / State / Photo) matching the printed team-entry
 * sheets used at tournaments. Used by both coach-team-report.php and
 * admin-team-report.php.
 */

require_once __DIR__ . '/../lib/fpdf/fpdf.php';

class TeamEntryReportPdf extends FPDF
{
    public $reportTitle = '';
    public $reportVenue = '';
    public $reportDates = '';
    public $teamLabel = '';
    public $coachName = '';

    private $colWidths = [];
    private $headers = ['Sr.', 'Reg. No.', 'Name', "Father's Name", 'DOB', 'Address', 'City', 'State', 'Photo'];
    private $photoColIndex = 8;
    private $lineHeight = 4.2;
    private $tempFiles = [];

    public function __construct()
    {
        parent::__construct('L', 'mm', 'A4');
        $this->SetMargins(8, 10, 8);
        $this->SetAutoPageBreak(true, 12);

        $usable = $this->GetPageWidth() - 16;
        $ratios = [5, 12, 15, 15, 9, 24, 8, 6, 10];
        $sum = array_sum($ratios);
        foreach ($ratios as $r) {
            $this->colWidths[] = $usable * $r / $sum;
        }
    }

    public function Header()
    {
        $this->SetFont('Helvetica', 'B', 12);
        if ($this->reportTitle !== '') {
            $this->Cell(0, 6, $this->safe($this->reportTitle), 0, 1, 'C');
        }
        $this->SetFont('Helvetica', '', 9);
        $line2 = trim($this->reportVenue . ($this->reportDates !== '' ? '   Date: ' . $this->reportDates : ''));
        if ($line2 !== '') {
            $this->Cell(0, 5, $this->safe($line2), 0, 1, 'C');
        }
        if ($this->teamLabel !== '') {
            $this->SetFont('Helvetica', 'B', 9);
            $this->Cell(0, 5, $this->safe('TEAM: ' . $this->teamLabel), 0, 1, 'C');
        }
        if ($this->coachName !== '') {
            $this->SetFont('Helvetica', '', 8);
            $this->Cell(0, 5, $this->safe('Coach: ' . $this->coachName), 0, 1, 'C');
        }
        $this->Ln(2);
        $this->tableHeaderRow();
    }

    public function Footer()
    {
        $this->SetY(-12);
        $this->SetFont('Helvetica', 'I', 7);
        $this->Cell(0, 8, 'Page ' . $this->PageNo() . ' - Generated ' . date('Y-m-d H:i'), 0, 0, 'C');
    }

    public function addRow($index, array $reg, $photoAbsPath)
    {
        $cells = [
            (string)$index,
            $this->safe($reg['reg_id'] ?? ''),
            $this->safe($reg['name'] ?? ''),
            $this->safe($reg['parent_name'] ?? ''),
            $this->safe($this->formatDob($reg['dob'] ?? ($reg['date_of_birth'] ?? ''))),
            $this->safe(trim($reg['address'] ?? '')),
            $this->safe($reg['city'] ?? ''),
            $this->safe($reg['state'] ?? ''),
        ];

        $lines = 1;
        foreach ($cells as $i => $txt) {
            $lines = max($lines, $this->nbLines($this->colWidths[$i], $txt));
        }
        $photoH = $this->colWidths[$this->photoColIndex] + 2;
        $rowH = max($lines * $this->lineHeight + 2, $photoH);

        if ($this->GetY() + $rowH > $this->GetPageHeight() - $this->bMargin) {
            $this->AddPage($this->CurOrientation);
        }

        $x = $this->GetX();
        $y = $this->GetY();
        $align = ['L', 'L', 'L', 'L', 'C', 'L', 'C', 'C'];

        foreach ($cells as $i => $txt) {
            $this->Rect($x, $y, $this->colWidths[$i], $rowH);
            $this->SetXY($x, $y + 1);
            $this->MultiCell($this->colWidths[$i], $this->lineHeight, $txt, 0, $align[$i]);
            $x += $this->colWidths[$i];
        }

        $photoW = $this->colWidths[$this->photoColIndex];
        $this->Rect($x, $y, $photoW, $rowH);
        $this->placePhoto($photoAbsPath, $x, $y, $photoW, $rowH);

        $this->SetXY($this->lMargin, $y + $rowH);
    }

    public function cleanupTempFiles()
    {
        foreach ($this->tempFiles as $f) {
            @unlink($f);
        }
        $this->tempFiles = [];
    }

    private function tableHeaderRow()
    {
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetFillColor(230, 230, 230);
        foreach ($this->headers as $i => $h) {
            $this->Cell($this->colWidths[$i], 7, $h, 1, 0, 'C', true);
        }
        $this->Ln();
        $this->SetFont('Helvetica', '', 8);
    }

    private function safe($text)
    {
        $text = (string)$text;
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : $text;
    }

    private function formatDob($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        return $ts ? date('d-M-Y', $ts) : $value;
    }

    // Counts wrapped lines for a cell, mirroring FPDF's own MultiCell logic.
    private function nbLines($w, $txt)
    {
        $cw = $this->CurrentFont['cw'];
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', (string)$txt);
        $nb = strlen($s);
        if ($nb > 0 && $s[$nb - 1] === "\n") {
            $nb--;
        }
        $sep = -1;
        $i = 0;
        $j = 0;
        $l = 0;
        $nl = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c === "\n") {
                $i++;
                $sep = -1;
                $j = $i;
                $l = 0;
                $nl++;
                continue;
            }
            if ($c === ' ') {
                $sep = $i;
            }
            $l += $cw[$c] ?? 0;
            if ($l > $wmax) {
                if ($sep === -1) {
                    if ($i === $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $nl++;
                $j = $i;
                $l = 0;
                $sep = -1;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    private function placePhoto($absPath, $x, $y, $w, $h)
    {
        $pad = 1.5;
        $boxW = $w - 2 * $pad;
        $boxH = $h - 2 * $pad;

        $src = $absPath ? $this->normalizeImage($absPath) : null;
        if ($src) {
            $size = @getimagesize($src);
            if ($size) {
                [$imgW, $imgH] = $size;
                $ratio = $imgW / $imgH;
                $drawW = $boxW;
                $drawH = $drawW / $ratio;
                if ($drawH > $boxH) {
                    $drawH = $boxH;
                    $drawW = $drawH * $ratio;
                }
                $ix = $x + $pad + ($boxW - $drawW) / 2;
                $iy = $y + $pad + ($boxH - $drawH) / 2;
                $this->Image($src, $ix, $iy, $drawW, $drawH);
                return;
            }
        }

        $this->SetFont('Helvetica', 'I', 6);
        $this->SetXY($x, $y + $h / 2 - 2);
        $this->Cell($w, 4, 'No Photo', 0, 0, 'C');
        $this->SetFont('Helvetica', '', 8);
    }

    // FPDF only natively decodes JPEG/PNG; convert anything else (webp, gif) via GD.
    private function normalizeImage($absPath)
    {
        $ext = strtolower(pathinfo($absPath, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
            return $absPath;
        }

        $img = null;
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) {
            $img = @imagecreatefromwebp($absPath);
        } elseif ($ext === 'gif') {
            $img = @imagecreatefromgif($absPath);
        } else {
            $data = @file_get_contents($absPath);
            $img = $data !== false ? @imagecreatefromstring($data) : null;
        }
        if (!$img) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'tcam_photo_') . '.png';
        imagesavealpha($img, true);
        imagepng($img, $tmp);
        imagedestroy($img);
        $this->tempFiles[] = $tmp;
        return $tmp;
    }
}

/**
 * Builds the PDF binary for a set of registration rows.
 *
 * @param array $registrations Rows from the `registrations` table.
 * @param array $meta ['title' => .., 'venue' => .., 'dates' => .., 'team_label' => ..]
 * @param string $uploadDir Absolute path to the uploads directory (photos are resolved relative to it).
 * @return string Raw PDF bytes.
 */
function renderTeamEntryReportPdf(array $registrations, array $meta, string $uploadDir)
{
    $pdf = new TeamEntryReportPdf();
    $pdf->reportTitle = $meta['title'] ?? '';
    $pdf->reportVenue = $meta['venue'] ?? '';
    $pdf->reportDates = $meta['dates'] ?? '';
    $pdf->teamLabel = $meta['team_label'] ?? '';
    $pdf->coachName = $meta['coach_name'] ?? '';
    $pdf->AddPage();

    $realUploadDir = realpath($uploadDir);

    $index = 1;
    foreach ($registrations as $reg) {
        $photoAbs = null;
        if (!empty($reg['photo']) && $realUploadDir) {
            $candidate = $realUploadDir . '/' . ltrim($reg['photo'], '/');
            $real = realpath($candidate);
            if ($real && strpos($real, $realUploadDir . DIRECTORY_SEPARATOR) === 0) {
                $photoAbs = $real;
            }
        }
        $pdf->addRow($index, $reg, $photoAbs);
        $index++;
    }

    $pdfString = $pdf->Output('S');
    $pdf->cleanupTempFiles();
    return $pdfString;
}
