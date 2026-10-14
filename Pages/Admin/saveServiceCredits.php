<?php

require_once '../../session.php';

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

    echo json_encode($data);

    exit;
}


/*
|--------------------------------------------------------------------------
| Only POST Requests
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
| Read JSON Request
|--------------------------------------------------------------------------
*/

$rawInput = file_get_contents('php://input');


if ($rawInput === false || trim($rawInput) === '') {

    respond([
        'success' => false,
        'message' => 'No service credit data was received.'
    ], 400);

}


$data = json_decode(
    $rawInput,
    true
);


if (
    !is_array($data) ||
    !isset($data['rows']) ||
    !is_array($data['rows'])
) {

    respond([
        'success' => false,
        'message' => 'Invalid service credit data.'
    ], 400);

}


$rows = $data['rows'];


if (count($rows) === 0) {

    respond([
        'success' => false,
        'message' => 'There are no service credit records to post.'
    ], 400);

}


/*
|--------------------------------------------------------------------------
| Database
|--------------------------------------------------------------------------
*/

$pdo = db();


/*
|--------------------------------------------------------------------------
| Helper Functions
|--------------------------------------------------------------------------
*/

function cleanValue($value): string
{
    return trim((string) $value);
}


function validateDateValue($value): ?string
{
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat(
        'Y-m-d',
        $value
    );

    if (
        $date === false ||
        $date->format('Y-m-d') !== $value
    ) {
        return null;
    }

    return $value;
}


/*
|--------------------------------------------------------------------------
| Load Service Credit Types
|--------------------------------------------------------------------------
|
| We don't hard-code the IDs.
|
| Local    → database lookup
| National → database lookup
|
*/

$typeStatement = $pdo->query("
    SELECT
        credit_type_id,
        credit_type_name
    FROM service_credit_types
");


$creditTypes = $typeStatement->fetchAll();


$creditTypeMap = [];


foreach ($creditTypes as $type) {

    $creditTypeMap[
        strtoupper(trim($type['credit_type_name']))
    ] = (int) $type['credit_type_id'];

}


/*
|--------------------------------------------------------------------------
| Make sure Local and National exist
|--------------------------------------------------------------------------
*/

if (
    !isset($creditTypeMap['LOCAL']) ||
    !isset($creditTypeMap['NATIONAL'])
) {

    respond([
        'success' => false,
        'message' => 'Local and National service credit types could not be found.'
    ], 500);

}


/*
|--------------------------------------------------------------------------
| Prepare Employee Lookup
|--------------------------------------------------------------------------
*/

$employeeStatement = $pdo->prepare("
    SELECT
        employee_id,
        employee_number,
        CONCAT(
            last_name,
            ', ',
            first_name,
            CASE
                WHEN middle_name IS NOT NULL
                     AND middle_name <> ''
                THEN CONCAT(' ', middle_name)
                ELSE ''
            END
        ) AS employee_name
    FROM employees
    WHERE employee_number = ?
");


/*
|--------------------------------------------------------------------------
| Validate Every Row
|--------------------------------------------------------------------------
*/

$validatedRows = [];

$errors = [];


foreach ($rows as $index => $row) {

    $rowNumber = $index + 1;


    /*
     * Get values
     */

    $employeeNumber = cleanValue(
        $row['employee_number'] ?? ''
    );

    $employeeName = cleanValue(
        $row['employee_name'] ?? ''
    );

    $activityName = cleanValue(
        $row['name_of_activity'] ?? ''
    );

    $activityStartDate = validateDateValue(
        $row['activity_start_date'] ?? ''
    );

    $activityEndDate = validateDateValue(
        $row['activity_end_date'] ?? ''
    );

    $creditAmount = $row[
        'number_of_service_credits'
    ] ?? '';

    $nature = strtoupper(
        cleanValue(
            $row['nature_of_service_credits'] ?? ''
        )
    );


    $rowErrors = [];


    /*
     * Employee Number
     */

    if ($employeeNumber === '') {

        $rowErrors[] =
            'Employee Number is required.';

    }


    /*
     * Employee
     */

    $employeeStatement->execute([
        $employeeNumber
    ]);

    $employee = $employeeStatement->fetch();


    if (!$employee) {

        $rowErrors[] =
            'Employee Number does not exist in the employee records.';

    }


    /*
     * Activity
     */

    if ($activityName === '') {

        $rowErrors[] =
            'Activity name is required.';

    }


    /*
     * Start Date
     */

    if ($activityStartDate === null) {

        $rowErrors[] =
            'Activity start date is invalid or missing.';

    }


    /*
     * End Date
     */

    if ($activityEndDate === null) {

        $rowErrors[] =
            'Activity end date is invalid or missing.';

    }


    /*
     * Date relationship
     */

    if (
        $activityStartDate !== null &&
        $activityEndDate !== null &&
        $activityEndDate < $activityStartDate
    ) {

        $rowErrors[] =
            'Activity end date cannot be earlier than the start date.';

    }


    /*
     * Service Credit Amount
     */

    if (
        $creditAmount === '' ||
        !is_numeric($creditAmount)
    ) {

        $rowErrors[] =
            'Number of service credits must be a valid number.';

    } else {

        $creditAmount = (float) $creditAmount;


        if ($creditAmount <= 0) {

            $rowErrors[] =
                'Number of service credits must be greater than 0.';

        }

    }


    /*
     * Nature
     */

    if (
        $nature !== 'LOCAL' &&
        $nature !== 'NATIONAL'
    ) {

        $rowErrors[] =
            'Nature of service credit must be LOCAL or NATIONAL.';

    }


    /*
     * If this row has errors
     */

    if (count($rowErrors) > 0) {

        $errors[] = [

            'row' => $rowNumber,

            'employee_number' =>
                $employeeNumber,

            'errors' =>
                $rowErrors

        ];

        continue;

    }


    /*
     * Store validated row
     */

    $validatedRows[] = [

        'employee_id' =>
            (int) $employee['employee_id'],

        'employee_number' =>
            $employee['employee_number'],

        'employee_name' =>
            $employee['employee_name'],

        'activity_name' =>
            $activityName,

        'activity_start_date' =>
            $activityStartDate,

        'activity_end_date' =>
            $activityEndDate,

        'credit_amount' =>
            $creditAmount,

        'credit_type_id' =>
            $creditTypeMap[$nature]

    ];

}


/*
|--------------------------------------------------------------------------
| STOP IF ANY ROW IS INVALID
|--------------------------------------------------------------------------
*/

if (count($errors) > 0) {

    respond([

        'success' => false,

        'message' =>
            'Some service credit records are invalid. No records were posted.',

        'invalid_count' =>
            count($errors),

        'errors' =>
            $errors

    ], 422);

}


/*
|--------------------------------------------------------------------------
| Begin Database Transaction
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();


    /*
     * Create Upload Batch
     */

    $batchStatement = $pdo->prepare("
        INSERT INTO credit_upload_batches
        (
            uploaded_by,
            file_name,
            status,
            uploaded_at,
            posted_at
        )
        VALUES
        (
            ?,
            ?,
            ?,
            NOW(),
            NOW()
        )
    ");


    $batchStatement->execute([

        $_SESSION['account_id'],

        $data['file_name']
            ?? 'service_credits.xlsx',

        'POSTED'

    ]);


    $uploadBatchId =
        (int) $pdo->lastInsertId();


    /*
     * Prepare transaction insert
     */

    $transactionStatement = $pdo->prepare("
        INSERT INTO service_credit_transactions
        (
            employee_id,
            credit_type_id,
            upload_batch_id,
            activity_name,
            activity_start_date,
            activity_end_date,
            credit_amount,
            transaction_type,
            created_by
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");


    /*
     * Insert every validated record
     */

   foreach ($validatedRows as $row) {

    $transactionStatement->execute([

        $row['employee_id'],

        $row['credit_type_id'],

        $uploadBatchId,

        $row['activity_name'],

        $row['activity_start_date'],

        $row['activity_end_date'],

        $row['credit_amount'],

        'EARNED',

        $_SESSION['account_id']

    ]);

}


    /*
     * Everything succeeded
     */

    $pdo->commit();


    respond([

        'success' => true,

        'message' =>
            'Service credits were successfully posted.',

        'upload_batch_id' =>
            $uploadBatchId,

        'posted_count' =>
            count($validatedRows)

    ]);


} catch (Throwable $e) {

    /*
     * Undo everything if anything fails.
     */

    if ($pdo->inTransaction()) {

        $pdo->rollBack();

    }


    respond([

        'success' => false,

        'message' =>
            'The service credits could not be posted.',

        'error' =>
            $e->getMessage()

    ], 500);

}