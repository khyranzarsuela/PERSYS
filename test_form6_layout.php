<?php
/**
 * PERSYS - Civil Service Form No. 6 layout test (standalone demo only)
 *
 * Requirements from the project root:
 *   composer require setasign/fpdf setasign/fpdi
 *
 * Place this script and Leave_Request_Form.pdf in the same folder for this test.
 * It creates form6_layout_test.pdf in the same folder.
 * Do not use this demo output path for production documents.
 */

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

$templatePath = __DIR__ . '/Leave_Request_Form.pdf';
$outputPath   = __DIR__ . '/form6_layout_test.pdf';

if (!is_file($templatePath)) {
    exit("Template not found: {$templatePath}\nPlace Leave_Request_Form.pdf beside this script.\n");
}

// DEMO DATA ONLY. Later, replace this array with values loaded from the approved
// leave request and its employee record using PDO and a prepared query.
$sample = [
    'office_department' => '', // Keep blank if PERSYS has no reliable value.
    'last_name'         => 'DELA CRUZ',
    'first_name'        => 'JUAN',
    'middle_name'       => 'SANTOS',
    'date_filed'        => 'October 9, 2026',
    'position'          => 'TEACHER I',
    'salary'            => '', // Keep blank if salary is not stored/confirmed.
    'leave_type'        => 'Others',
    'reason_details'    => 'Attendance at a special government training',
    'total_leave_days'  => '3 Days',
    'start_date'        => 'September 27, 2026',
    'end_date'          => 'September 30, 2026',
];

function putText(Fpdi $pdf, float $x, float $y, string $value, float $fontSize = 9): void
{
    $value = trim($value);
    if ($value === '') {
        return;
    }

    $pdf->SetFont('Arial', '', $fontSize);
    $pdf->SetXY($x, $y);
    $pdf->Cell(0, 4, iconv('UTF-8', 'Windows-1252//TRANSLIT', $value), 0, 0, 'L');
}

/** Draw a checkmark inside an existing checkbox. Coordinates are millimetres. */
function drawCheck(Fpdi $pdf, float $x, float $y): void
{
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.55);
    $pdf->Line($x + 0.8, $y + 2.3, $x + 2.0, $y + 3.5);
    $pdf->Line($x + 2.0, $y + 3.5, $x + 4.4, $y + 0.8);
}

/** Fit a single-line value into a known width without changing the form itself. */
function putFittedText(
    Fpdi $pdf,
    float $x,
    float $y,
    float $maxWidth,
    string $value,
    float $preferredSize = 9,
    float $minimumSize = 6.5
): void {
    $value = trim($value);
    if ($value === '') {
        return;
    }

    $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
    $size = $preferredSize;
    $pdf->SetFont('Arial', '', $size);

    while ($size > $minimumSize && $pdf->GetStringWidth($encoded) > $maxWidth) {
        $size -= 0.25;
        $pdf->SetFont('Arial', '', $size);
    }

    if ($pdf->GetStringWidth($encoded) > $maxWidth) {
        // Do not silently print text outside the official form's line.
        // In production, enforce a reasonable reason_details length in the form
        // and show validation to the user if it cannot fit on this line.
        throw new RuntimeException(
            'The value is too long for this Form 6 line. Shorten it or design a wrapped-field layout.'
        );
    }

    $pdf->SetXY($x, $y);
    $pdf->Cell($maxWidth, 4, $encoded, 0, 0, 'L');
}

$pdf = new Fpdi('P', 'mm', 'A4');
$pdf->SetAutoPageBreak(false);
$pageCount = $pdf->setSourceFile($templatePath);
if ($pageCount < 1) {
    exit("The Form 6 template has no pages.\n");
}

$tpl = $pdf->importPage(1);
$pageSize = $pdf->getTemplateSize($tpl);
$pdf->AddPage($pageSize['orientation'], [$pageSize['width'], $pageSize['height']]);
// Preserve the source PDF's native page dimensions instead of forcing A4 scaling.
$pdf->useTemplate($tpl, 0, 0, $pageSize['width'], $pageSize['height']);

// Section 1-5: employee information. These coordinates are a revised first
// calibration pass based on the screenshot supplied by the user. Coordinates
// are in millimetres from the top-left corner of the original PDF page.
putFittedText($pdf, 19, 27.0, 45, $sample['office_department'], 8.5, 6.5);
putFittedText($pdf, 100, 27.0, 27, $sample['last_name'], 8.5, 6.5);
putFittedText($pdf, 136, 27.0, 25, $sample['first_name'], 8.5, 6.5);
putFittedText($pdf, 172, 27.0, 25, $sample['middle_name'], 8.5, 6.5);
putFittedText($pdf, 42, 40.0, 40, $sample['date_filed'], 8, 6.5);
putFittedText($pdf, 110, 40.0, 49, $sample['position'], 8, 6.5);
putFittedText($pdf, 179, 40.0, 20, $sample['salary'], 8, 6.5);

// Section 6.A: checkbox positions for the listed Form 6 leave types.
// Values are the top edge of each existing checkbox, based on the screenshot.
$leaveTypeCheckboxY = [
    'Vacation Leave'                         => 57.4,
    'Mandatory/Forced Leave'                 => 63.0,
    'Sick Leave'                             => 68.6,
    'Maternity Leave'                        => 74.2,
    'Paternity Leave'                        => 79.8,
    'Special Privilege Leave'                => 85.4,
    'Solo Parent Leave'                      => 91.0,
    'Study Leave'                            => 96.6,
    '10-Day VAWC Leave'                      => 102.2,
    'Rehabilitation Privilege'               => 107.8,
    'Special Leave Benefits for Women'       => 113.4,
    'Special Emergency (Calamity) Leave'     => 119.0,
    'Adoption Leave'                         => 124.6,
];
$leaveType = trim($sample['leave_type']);
if (strcasecmp($leaveType, 'Others') === 0) {
    // The official form has no square checkbox beside Others. Mark the start
    // of the Others line and print the user's reason on that line.
    drawCheck($pdf, 10.2, 135.2);
    putFittedText($pdf, 18.0, 136.8, 83.0, $sample['reason_details'], 8, 6.0);
} else {
    $matchedY = null;
    foreach ($leaveTypeCheckboxY as $label => $y) {
        if (strcasecmp($leaveType, $label) === 0) {
            $matchedY = $y;
            break;
        }
    }

    if ($matchedY !== null) {
        drawCheck($pdf, 11.5, $matchedY);
    } elseif ($leaveType !== '') {
        throw new RuntimeException('Unrecognized leave type in the demo data: ' . $leaveType);
    }
}

// Section 6.C: working days and inclusive dates.
putFittedText($pdf, 49, 143.5, 45, $sample['total_leave_days'], 9.5, 7);
$inclusiveDates = trim($sample['start_date']);
if (trim($sample['end_date']) !== '' && trim($sample['end_date']) !== trim($sample['start_date'])) {
    $inclusiveDates .= ' - ' . trim($sample['end_date']);
}
putFittedText($pdf, 30, 153.4, 92, $inclusiveDates, 8.5, 6.0);

// Do not add text or marks to the signature, certification, recommendation,
// approved/disapproved, or authorized-officer signature sections.
$pdf->Output('F', $outputPath);
echo "Created layout test: {$outputPath}\n";
echo "This is a visual prototype using sample data, not a production leave form.\n";
