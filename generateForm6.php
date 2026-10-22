<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

use setasign\Fpdi\Fpdi;

// Require an authenticated admin.
start_app_session();
require_admin();

$pdo = db();

// 1. Validate the requested leave request ID.
$requestId = filter_input(INPUT_GET, 'request_id', FILTER_VALIDATE_INT);

if (!$requestId || $requestId < 1) {
    http_response_code(400);
    exit('Invalid leave request ID.');
}

// 2. Retrieve the leave request and employee information.
$sql = "
    SELECT
        lr.leave_request_id,
        lr.request_number,
        lr.date_filed,
        lr.reason_details,
        lr.start_datetime,
        lr.end_datetime,
        lr.total_leave_days,
        lr.status,
        lt.leave_type_name,
        e.last_name,
        e.first_name,
        e.middle_name,
        e.position
    FROM leave_requests AS lr
    INNER JOIN employees AS e
        ON e.employee_id = lr.employee_id
    INNER JOIN leave_types AS lt
        ON lt.leave_type_id = lr.leave_type_id
    WHERE lr.leave_request_id = :request_id
    LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute(['request_id' => $requestId]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$request) {
    http_response_code(404);
    exit('Leave request not found.');
}

// Do not generate Form 6 for rejected requests.
if ($request['status'] === 'Rejected') {
    http_response_code(403);
    exit('Form 6 is unavailable for rejected leave requests.');
}


// 3. Load the clean Form 6 template.
$templatePath = __DIR__ . '/Documents/Form6_Clean_Template.pdf';

if (!file_exists($templatePath)) {
    http_response_code(500);
    exit('Form 6 template not found.');
}

$pdf = new Fpdi();

$pdf->SetAutoPageBreak(false);
$pdf->SetMargins(0, 0, 0);

$pdf->setSourceFile($templatePath);
$templateId = $pdf->importPage(1);
$pageSize = $pdf->getTemplateSize($templateId);

$pdf->AddPage(
    $pageSize['orientation'],
    [$pageSize['width'], $pageSize['height']]
);

$pdf->useTemplate($templateId);

$pdf->SetTextColor(0, 0, 0);

// 4. Office / Department.
// Keep your confirmed coordinates unchanged.
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(25, 47);
$pdf->Write(5, 'BCSHS-SAC');

// 5. Employee name.
// Keep your confirmed coordinates unchanged.
$pdf->SetXY(93, 47);
$pdf->Write(5, strtoupper($request['last_name']));

$pdf->SetXY(130, 47);
$pdf->Write(5, strtoupper($request['first_name']));

$pdf->SetXY(165, 47);
$pdf->Write(5, strtoupper($request['middle_name'] ?? ''));

// 6. Date of Filing.
$pdf->SetXY(43, 60);
$pdf->Write(
    5,
    date('m/d/Y', strtotime($request['date_filed']))
);

// 7. Position.
$pdf->SetXY(105, 59);
$pdf->Write(5, strtoupper($request['position']));

// 8. Salary.
// The employees table has no actual salary amount.
// Leave this field blank until salary data is available.

// 6.A — Type of Leave
$leaveType = strtolower(trim($request['leave_type_name']));

$pdf->SetFont('Arial', 'B', 12);

// Leave type checkbox coordinates.
// Vacation Leave coordinates are already calibrated.
// The other coordinates must be calibrated against your PDF.
$checkboxPositions = [
    'vacation leave' => [15.5, 78],

    // TODO: Replace these placeholder coordinates
    // with the actual checkbox positions in Form 6.
    'mandatory/forced leave' => [15.5, 83],
    'sick leave' => [15.5, 88],
    'maternity leave' => [15.5, 94],
    'paternity leave' => [15.5, 99],
    'special privilege leave' => [15.5, 104],
    'solo parent leave' => [15.5, 109],
    'study leave' => [15.5, 114],
    '10-day vawc leave' => [15.5, 120],
    'rehabilitation privilege leave' => [15.5, 125],
    'special leave benefits for women' => [15.5, 130],
    'special emergency (calamity) leave' => [15.5, 135],
    'adoption leave' => [15.5, 140]
];

// Check only the selected leave type.
if (isset($checkboxPositions[$leaveType])) {
    [$checkboxX, $checkboxY] = $checkboxPositions[$leaveType];

    $pdf->SetXY($checkboxX, $checkboxY);
    $pdf->Write(5, 'X');
}

// Populate the "Others: ______" line ONLY when
// the selected leave type is Others.
if ($leaveType === 'others') {
    $otherDescription = trim($request['reason_details'] ?? '');

    if ($otherDescription !== '' &&
        strtolower($otherDescription) !== 'n/a') {

        $pdf->SetFont('Arial', '', 9);

        // Keep your calibrated coordinates for the Others line.
        $pdf->SetXY(20, 148);
        $pdf->Write(5, $otherDescription);
    }
}

// Do not print reason_details in the Details of Leave section.
// The "Others" line is populated only for the Others leave type.

// 11. Total Working Days.
$pdf->SetFont('Arial', '', 10);
$pdf->SetXY(43, 160);
$pdf->Write(5, rtrim(
    rtrim(number_format((float) $request['total_leave_days'], 2, '.', ''), '0'),
    '.'
));

// 12. Inclusive Dates.
// Use the dates from the leave request, not hardcoded sample dates.
$startDate = date('F j, Y', strtotime($request['start_datetime']));
$endDate = date('F j, Y', strtotime($request['end_datetime']));

$inclusiveDates = $startDate === $endDate
    ? $startDate
    : $startDate . ' - ' . $endDate;

$pdf->SetXY(40, 169);
$pdf->Write(5, $inclusiveDates);

// 13. Output PDF.
// This only generates a document. It does not change
// the leave request status or deduct service credits.
$safeRequestNumber = preg_replace(
    '/[^A-Za-z0-9_-]/',
    '_',
    $request['request_number']
);

$filename = 'Form6_' . $safeRequestNumber . '.pdf';

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');

$pdf->Output('I', $filename);
exit;
