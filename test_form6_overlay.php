<?php

require_once __DIR__ . '/vendor/autoload.php';

use setasign\Fpdi\Fpdi;

$templatePath = __DIR__ . '/Documents/Form6_Clean_Template.pdf';
$outputDirectory = __DIR__ . '/generated';
$outputPath = $outputDirectory . '/Form6_Overlay_Test.pdf';

if (!file_exists($templatePath)) {
    exit('Error: Form 6 template not found.');
}

if (!is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0775, true);
}

$pdf = new Fpdi();

$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(0, 0, 0);

$pageCount = $pdf->setSourceFile($templatePath);
$templateId = $pdf->importPage(1);
$pageSize = $pdf->getTemplateSize($templateId);

$pdf->AddPage(
    $pageSize['orientation'],
    [$pageSize['width'], $pageSize['height']]
);

$pdf->useTemplate($templateId);

// Sample values for positioning tests

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);

// Office / Department — provisional position
$pdf->SetXY(25, 47);
$pdf->Write(5, 'BCSHS-SAC');

// Employee name — moved upward into the NAME row
$pdf->SetXY(93, 47);
$pdf->Write(5, 'DELA CRUZ');

$pdf->SetXY(130, 47);
$pdf->Write(5, 'JUAN');

$pdf->SetXY(165, 47);
$pdf->Write(5, 'SANTOS');


/*
 * Additional Form 6 fields
 * These coordinates are provisional.
 * Keep the existing office and name coordinates unchanged.
 */

// 3. Date of Filing
$pdf->SetXY(43, 60);
$pdf->Write(5, '10/15/2026');

// 4. Position
$pdf->SetXY(105, 59);
$pdf->Write(5, 'TEACHER I');

// 5. Salary
$pdf->SetXY(176, 60);
$pdf->Write(5, '30000');


/*
 * 6.A — Type of Leave
 * Test only: Vacation Leave
 * Coordinates are provisional.
 */

$pdf->SetFont('Arial', 'B', 12);
$pdf->SetXY(15.5, 109);
$pdf->Write(5, 'X');


/*
 * 6.B — Details of Leave
 * Test only: Reason for leave
 * Coordinates are provisional.
 */

$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(20, 148);
$pdf->Write(5, 'Personal reasons for leave');



/*
 * 6.C — Total Working Days
 * Test value only.
 * Coordinates are provisional.
 */

$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(43, 160);
$pdf->Write(5, '3');

/*
 * 6.D — Inclusive Dates
 * Test value only.
 * Coordinates are provisional.
 */

$pdf->SetXY(40, 169);
$pdf->Write(5, 'October 20-22, 2026');


$pdf->Output('F', $outputPath);

echo 'Test PDF generated successfully.<br>';
echo 'Saved to: ' . htmlspecialchars($outputPath, ENT_QUOTES, 'UTF-8');
echo '<br><a href="generated/Form6_Overlay_Test.pdf" target="_blank">'
    . 'Open Test PDF</a>';
