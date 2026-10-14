<?php

require_once '../../session.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

start_app_session();
require_admin();

header('Content-Type: application/json');


/*
|--------------------------------------------------------------------------
| JSON Response Helper
|--------------------------------------------------------------------------
*/

function respond(array $data, int $status = 200): void
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Only POST is allowed
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    respond([
        'success' => false,
        'message' => 'Invalid request method.'
    ], 405);
}


/*
|--------------------------------------------------------------------------
| Check uploaded file
|--------------------------------------------------------------------------
*/

if (!isset($_FILES['service_credits_file'])) {

    respond([
        'success' => false,
        'message' => 'Please choose an Excel file.'
    ], 400);
}


$file = $_FILES['service_credits_file'];


if ($file['error'] !== UPLOAD_ERR_OK) {

    respond([
        'success' => false,
        'message' => 'The Excel file could not be uploaded.'
    ], 400);
}


/*
|--------------------------------------------------------------------------
| Check file extension
|--------------------------------------------------------------------------
*/

$extension = strtolower(
    pathinfo($file['name'], PATHINFO_EXTENSION)
);


$allowedExtensions = [
    'xlsx',
    'xls'
];


if (!in_array($extension, $allowedExtensions, true)) {

    respond([
        'success' => false,
        'message' => 'Please upload an .xlsx or .xls file.'
    ], 400);
}


/*
|--------------------------------------------------------------------------
| Read Excel file
|--------------------------------------------------------------------------
*/

try {

    $spreadsheet = IOFactory::load(
        $file['tmp_name']
    );

} catch (Throwable $e) {

    respond([
        'success' => false,
        'message' => 'The Excel file could not be read.'
    ], 400);
}


$worksheet = $spreadsheet->getActiveSheet();

$highestRow = $worksheet->getHighestRow();


/*
|--------------------------------------------------------------------------
| Expected Excel Headers
|--------------------------------------------------------------------------
*/

$expectedHeaders = [

    'EMPLOYEE NUMBER',
    'EMPLOYEE NAME',
    'NAME OF ACTIVITY',
    'DATE OF ACTIVITY',
    'NO. SERVICE CREDIT',
    'NATURE OF SERVICE CREDIT'

];


/*
|--------------------------------------------------------------------------
| Read Excel Headers
|--------------------------------------------------------------------------
*/

$headers = [];


for (
    $column = 1;
    $column <= count($expectedHeaders);
    $column++
) {

    $value = $worksheet
        ->getCell([$column, 1])
        ->getFormattedValue();

    $headers[] = strtoupper(
        trim((string) $value)
    );
}


/*
|--------------------------------------------------------------------------
| Validate Headers
|--------------------------------------------------------------------------
*/

if ($headers !== $expectedHeaders) {

    respond([

        'success' => false,

        'message' =>
            'The Excel headers do not match the required Service Credit format.',

        'expected_headers' => $expectedHeaders,

        'received_headers' => $headers

    ], 400);
}


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function cleanValue($value): string
{
    return trim((string) $value);
}

function normalizeEmployeeName(string $name): string
{
    $name = strtoupper(trim($name));

    // Remove periods, commas, and other punctuation.
    $name = preg_replace('/[^A-Z0-9\s-]/', '', $name);

    // Convert multiple spaces into one.
    $name = preg_replace('/\s+/', ' ', $name);

    return trim($name);
}

/*
|--------------------------------------------------------------------------
| Parse Activity Date Range
|
| Examples accepted:
|
| 09/25/2026
| 2026-09-25
| September 25, 2026
| September 25-26, 2026
| SEPTEMBER 25-26
|--------------------------------------------------------------------------
*/

function parseActivityDateRange($value): array
{
    if ($value === null || $value === '') {

        return [
            'start' => null,
            'end' => null
        ];
    }


    /*
     * Excel numeric date.
     */

    if (is_numeric($value)) {

        try {

            $date = Date::excelToDateTimeObject($value)
                ->format('Y-m-d');

            return [
                'start' => $date,
                'end' => $date
            ];

        } catch (Throwable $e) {

            return [
                'start' => null,
                'end' => null
            ];
        }
    }


    $text = trim((string) $value);


    /*
     * Normalize whitespace.
     */

    $text = preg_replace(
        '/\s+/',
        ' ',
        $text
    );


    /*
     * Convert different dash characters
     * to a normal hyphen.
     */

    $text = str_replace(
        ['–', '—'],
        '-',
        $text
    );


    /*
     * Try single-date formats first.
     */

    $singleDateFormats = [

        'm/d/Y',
        'm/d/y',

        'n/j/Y',
        'n/j/y',

        'Y-m-d',

        'F j, Y',
        'F j Y',

        'M j, Y',
        'M j Y'

    ];


    foreach ($singleDateFormats as $format) {

        $date = DateTime::createFromFormat(
            $format,
            $text
        );

        if ($date !== false) {

            return [

                'start' =>
                    $date->format('Y-m-d'),

                'end' =>
                    $date->format('Y-m-d')

            ];
        }
    }


    /*
     * Try ranges such as:
     *
     * September 25-26, 2026
     * September 25 - 26, 2026
     */

    if (
        preg_match(
            '/^([A-Za-z]+)\s+(\d{1,2})\s*-\s*(\d{1,2})(?:,\s*|\s+)(\d{4})$/',
            $text,
            $matches
        )
    ) {

        $month = $matches[1];

        $startDay = (int) $matches[2];

        $endDay = (int) $matches[3];

        $year = (int) $matches[4];


        $startDate = DateTime::createFromFormat(
            'F j Y',
            "$month $startDay $year"
        );

        $endDate = DateTime::createFromFormat(
            'F j Y',
            "$month $endDay $year"
        );


        if (
            $startDate !== false &&
            $endDate !== false
        ) {

            return [

                'start' =>
                    $startDate->format('Y-m-d'),

                'end' =>
                    $endDate->format('Y-m-d')

            ];
        }
    }


    /*
     * Try ranges without a year.
     *
     * Example:
     *
     * September 25-26
     *
     * The current year is used.
     */

    if (
        preg_match(
            '/^([A-Za-z]+)\s+(\d{1,2})\s*-\s*(\d{1,2})$/',
            $text,
            $matches
        )
    ) {

        $month = $matches[1];

        $startDay = (int) $matches[2];

        $endDay = (int) $matches[3];

        $year = (int) date('Y');


        $startDate = DateTime::createFromFormat(
            'F j Y',
            "$month $startDay $year"
        );

        $endDate = DateTime::createFromFormat(
            'F j Y',
            "$month $endDay $year"
        );


        if (
            $startDate !== false &&
            $endDate !== false
        ) {

            return [

                'start' =>
                    $startDate->format('Y-m-d'),

                'end' =>
                    $endDate->format('Y-m-d')

            ];
        }
    }


    /*
     * Invalid date.
     */

    return [
        'start' => null,
        'end' => null
    ];
}


/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

$pdo = db();


/*
|--------------------------------------------------------------------------
| Load Existing Employees
|
| We use employee_number to find the employee_id.
|--------------------------------------------------------------------------
*/

$employeeStatement = $pdo->query("
    SELECT
        employee_id,
        employee_number,
        last_name,
        first_name,
        middle_name
    FROM employees
");


$employees = [];


foreach (
    $employeeStatement->fetchAll()
    as $employee
) {

    $employeeNumber = strtolower(
        trim($employee['employee_number'])
    );


    $employees[$employeeNumber] = $employee;
}


/*
|--------------------------------------------------------------------------
| Load Service Credit Types
|--------------------------------------------------------------------------
*/

$creditTypeStatement = $pdo->query("
    SELECT
        credit_type_id,
        credit_type_name
    FROM service_credit_types
");


$creditTypes = [];


foreach (
    $creditTypeStatement->fetchAll()
    as $creditType
) {

    $creditTypeName = strtolower(
        trim($creditType['credit_type_name'])
    );


    $creditTypes[$creditTypeName] = $creditType;
}


/*
|--------------------------------------------------------------------------
| Process Excel Rows
|--------------------------------------------------------------------------
*/

$rows = [];

$validCount = 0;

$invalidCount = 0;

$pdo = db();


for (
    $rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {

    /*
     * IMPORTANT:
     * Reset errors for EVERY Excel row.
     */

    $errors = [];


    /*
     * Read Excel values.
     */

    $employeeNumber = cleanValue(
        $worksheet
            ->getCell([1, $rowNumber])
            ->getFormattedValue()
    );


    $employeeName = cleanValue(
        $worksheet
            ->getCell([2, $rowNumber])
            ->getFormattedValue()
    );


    $nameOfActivity = cleanValue(
        $worksheet
            ->getCell([3, $rowNumber])
            ->getFormattedValue()
    );


    /*
     * For dates, use the RAW Excel value.
     *
     * This allows us to correctly process
     * actual Excel dates and text dates.
     */

    $activityDateRaw =
        $worksheet
            ->getCell([4, $rowNumber])
            ->getValue();


    $numberOfServiceCredits = cleanValue(
        $worksheet
            ->getCell([5, $rowNumber])
            ->getFormattedValue()
    );


    $natureOfServiceCredits = cleanValue(
        $worksheet
            ->getCell([6, $rowNumber])
            ->getFormattedValue()
    );


    /*
     * Check whether row is completely empty.
     */

    $allValues = [

        $employeeNumber,

        $employeeName,

        $nameOfActivity,

        $activityDateRaw,

        $numberOfServiceCredits,

        $natureOfServiceCredits

    ];


    $hasAnyValue = false;


    foreach ($allValues as $value) {

        if (
            $value !== null &&
            $value !== ''
        ) {

            $hasAnyValue = true;

            break;
        }
    }


    if (!$hasAnyValue) {

        continue;
    }


    /*
     * Parse activity date.
     */

    $activityDates =
        parseActivityDateRange(
            $activityDateRaw
        );


    $activityStartDate =
        $activityDates['start'];


    $activityEndDate =
        $activityDates['end'];



   /*
|--------------------------------------------------------------------------
| Required field validation
|--------------------------------------------------------------------------
*/

if ($employeeNumber === '') {
    $errors[] = 'Employee number is required.';
}

if ($employeeName === '') {
    $errors[] = 'Employee name is required.';
}

if ($nameOfActivity === '') {
    $errors[] = 'Name of activity is required.';
}

if ($activityStartDate === '') {
    $errors[] = 'Date of activity is required.';
}

if ($numberOfServiceCredits === '') {
    $errors[] = 'Number of service credits is required.';
}

if ($natureOfServiceCredits === '') {
    $errors[] = 'Nature of service credit is required.';
}


/*
|--------------------------------------------------------------------------
| Service credit amount validation
|--------------------------------------------------------------------------
*/

if ($numberOfServiceCredits  !== '') {

    if (
        !is_numeric($numberOfServiceCredits) ||
        (float) $numberOfServiceCredits <= 0
    ) {
        $errors[] =
            'Number of service credits must be greater than 0.';
    }
}


/*
|--------------------------------------------------------------------------
| Nature of service credit validation
|--------------------------------------------------------------------------
*/

$natureOfServiceCredits = strtoupper(
    trim($natureOfServiceCredits)
);

$creditTypeId = null;

if ($natureOfServiceCredits !== '') {

    if (
        $natureOfServiceCredits !== 'LOCAL' &&
        $natureOfServiceCredits !== 'NATIONAL'
    ) {

        $errors[] =
            'Nature of service credit must be LOCAL or NATIONAL.';

    } else {

        /*
         * Find the corresponding service credit type.
         */

        $creditTypeStmt = $pdo->prepare("
            SELECT credit_type_id
            FROM service_credit_types
            WHERE UPPER(credit_type_name) = ?
            LIMIT 1
        ");

        $creditTypeStmt->execute([
            $natureOfServiceCredits
        ]);

        $creditTypeId = $creditTypeStmt->fetchColumn();

        if ($creditTypeId === false) {

            $creditTypeId = null;

            $errors[] =
                'The service credit type does not exist in the system.';
        }
    }
}


/*
|--------------------------------------------------------------------------
| Employee number validation
|--------------------------------------------------------------------------
*/

$employeeId = null;
$databaseEmployeeName = null;

if ($employeeNumber !== '') {

    $employeeStmt = $pdo->prepare("
        SELECT
            employee_id,
            last_name,
            first_name,
            middle_name
        FROM employees
        WHERE employee_number = ?
        LIMIT 1
    ");

    $employeeStmt->execute([
        $employeeNumber
    ]);

    $employee = $employeeStmt->fetch();

    if (!$employee) {

        $errors[] =
            'Employee number does not exist in the system.';

    } else {

        $employeeId = (int) $employee['employee_id'];

        /*
         * Reconstruct the employee's official name
         * from the employees table.
         */

        $databaseEmployeeName = trim(
            $employee['last_name'] . ', ' .
            $employee['first_name'] . ' ' .
            ($employee['middle_name'] ?? '')
        );
    }
}


/*
|--------------------------------------------------------------------------
| Employee name validation
|--------------------------------------------------------------------------
*/

if (
    $employeeName !== '' &&
    $databaseEmployeeName !== null
) {

    $excelName = normalizeEmployeeName(
        $employeeName
    );

    $databaseName = normalizeEmployeeName(
        $databaseEmployeeName
    );

    if ($excelName !== $databaseName) {

        $errors[] =
            'Employee name does not match the employee number.';
    }
}

    /*
     * Check employee number.
     */

    $employeeKey = strtolower(
        $employeeNumber
    );


    $employee = null;


    if (
        $employeeNumber !== '' &&
        isset($employees[$employeeKey])
    ) {

        $employee =
            $employees[$employeeKey];

    } elseif ($employeeNumber !== '') {

        $errors[] =
            'Employee number does not exist in the system.';
    }


    /*
     * Check service credit type.
     */

    $creditTypeKey = strtolower(
        $natureOfServiceCredits
    );


    $creditType = null;


    if (
        $natureOfServiceCredits !== '' &&
        isset($creditTypes[$creditTypeKey])
    ) {

        $creditType =
            $creditTypes[$creditTypeKey];

    } elseif ($natureOfServiceCredits !== '') {

        $errors[] =
            'Nature of service credit must match a valid service credit type.';
    }


    /*
     * Validate service credit amount.
     */

    if ($numberOfServiceCredits !== '') {

        if (
            !is_numeric(
                $numberOfServiceCredits
            )
        ) {

            $errors[] =
                'Service credit must be a number.';

        } elseif (
            (float) $numberOfServiceCredits <= 0
        ) {

            $errors[] =
                'Service credit must be greater than zero.';
        }
    }


    /*
     * Prepare preview status.
     */

    $status = empty($errors)
        ? 'valid'
        : 'invalid';


    if ($status === 'valid') {

        $validCount++;

    } else {

        $invalidCount++;
    }


    /*
     * Add row to preview.
     */

   $rows[] = [

    'excel_row' => $rowNumber,

    'employee_number' => $employeeNumber,
    'employee_name' => $employeeName,

    'employee_id' => $employeeId,
    'database_employee_name' => $databaseEmployeeName,

    'name_of_activity' => $nameOfActivity,

    'activity_start_date' => $activityStartDate,
    'activity_end_date' => $activityEndDate,

    'number_of_service_credits' => $numberOfServiceCredits,

    'nature_of_service_credits' =>
        $natureOfServiceCredits,

    'credit_type_id' => $creditTypeId,

    'status' => $status,
    'errors' => $errors
];
}


/*
|--------------------------------------------------------------------------
| Return Preview Data
|--------------------------------------------------------------------------
*/

respond([

    'success' => true,

    'file_name' =>
        $file['name'],

    'total_records' =>
        count($rows),

    'valid_count' =>
        $validCount,

    'invalid_count' =>
        $invalidCount,

    'rows' =>
        $rows

]);