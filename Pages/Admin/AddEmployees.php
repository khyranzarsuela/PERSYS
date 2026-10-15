<?php
ob_start();

require_once '../../session.php';
require_once '../../database.php';

start_app_session();
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    header('Location: adminEmployees.php');
    exit;
}

// ── Collect & trim inputs ─────────────────────────────────────────────────────
$employee_number          = trim($_POST['employee_number']          ?? '');
$last_name                = trim($_POST['last_name']                ?? '');
$first_name               = trim($_POST['first_name']               ?? '');
$middle_name              = trim($_POST['middle_name']              ?? '');
$plantilla_item_number    = trim($_POST['plantilla_item_number']    ?? '');
$position                 = trim($_POST['position']                 ?? '');
$salary_grade_raw         = trim($_POST['salary_grade']             ?? '');
$step_increment_raw       = trim($_POST['step_increment']           ?? '');
$date_original_raw        = trim($_POST['date_original_appointment'] ?? '');
$date_promotion_raw       = trim($_POST['date_last_promotion']       ?? '');
$deped_email              = trim($_POST['deped_email']              ?? '');
$personnel_type           = trim($_POST['personnel_type']           ?? '');

// ── Helper: redirect with validation error ────────────────────────────────────
function validationError(string $msg): never {
    ob_end_clean();
    header('Location: adminEmployees.php?error=validation&msg=' . urlencode($msg));
    exit;
}

// ── 1. Required field checks ──────────────────────────────────────────────────
if ($employee_number       === '') validationError('Employee number is required.');
if ($last_name             === '') validationError('Last name is required.');
if ($first_name            === '') validationError('First name is required.');
if ($plantilla_item_number === '') validationError('Plantilla item number is required.');
if ($position              === '') validationError('Position is required.');
if ($salary_grade_raw      === '') validationError('Salary grade is required.');
if ($step_increment_raw    === '') validationError('Step increment is required.');
if ($date_original_raw     === '') validationError('Date of original appointment is required.');
if ($deped_email           === '') validationError('DepEd email is required.');
if ($personnel_type        === '') validationError('Personnel type is required.');

// ── 2. Numeric validation ─────────────────────────────────────────────────────
if (!ctype_digit($salary_grade_raw) || (int)$salary_grade_raw < 1) {
    validationError('Salary grade must be a positive integer.');
}
if (!ctype_digit($step_increment_raw) || (int)$step_increment_raw < 1) {
    validationError('Step increment must be a positive integer.');
}
$salary_grade   = (int) $salary_grade_raw;
$step_increment = (int) $step_increment_raw;

// ── 3. Date validation ────────────────────────────────────────────────────────
$parsed_orig = DateTime::createFromFormat('Y-m-d', $date_original_raw);
if (!$parsed_orig || $parsed_orig->format('Y-m-d') !== $date_original_raw) {
    validationError('Date of original appointment must be a valid date (YYYY-MM-DD).');
}
$date_original_appointment = $date_original_raw;

$date_last_promotion = null;
if ($date_promotion_raw !== '') {
    $parsed_promo = DateTime::createFromFormat('Y-m-d', $date_promotion_raw);
    if (!$parsed_promo || $parsed_promo->format('Y-m-d') !== $date_promotion_raw) {
        validationError('Date of last promotion must be a valid date (YYYY-MM-DD).');
    }
    $date_last_promotion = $date_promotion_raw;
}

// ── 4. Email validation ───────────────────────────────────────────────────────
if (!filter_var($deped_email, FILTER_VALIDATE_EMAIL)) {
    validationError('DepEd email is not a valid email address.');
}

// ── 5. Personnel type validation ─────────────────────────────────────────────
if ($personnel_type !== 'Teaching' && $personnel_type !== 'Non-Teaching') {
    validationError('Personnel type must be Teaching or Non-Teaching.');
}

// ── 6 & 7. Duplicate checks ───────────────────────────────────────────────────
try {
    $pdo = db();

    $chk = $pdo->prepare('SELECT 1 FROM employees WHERE employee_number = :en LIMIT 1');
    $chk->execute([':en' => $employee_number]);
    if ($chk->fetch()) {
        ob_end_clean();
        header('Location: adminEmployees.php?error=duplicate_employee_number');
        exit;
    }

    $chk = $pdo->prepare('SELECT 1 FROM employees WHERE deped_email = :em LIMIT 1');
    $chk->execute([':em' => $deped_email]);
    if ($chk->fetch()) {
        ob_end_clean();
        header('Location: adminEmployees.php?error=duplicate_email');
        exit;
    }

    // ── 8. INSERT ─────────────────────────────────────────────────────────────
    $ins = $pdo->prepare(
        'INSERT INTO employees
            (employee_number, last_name, first_name, middle_name,
             plantilla_item_number, position, salary_grade, step_increment,
             date_original_appointment, date_last_promotion, deped_email, personnel_type)
         VALUES
            (:employee_number, :last_name, :first_name, :middle_name,
             :plantilla_item_number, :position, :salary_grade, :step_increment,
             :date_original_appointment, :date_last_promotion, :deped_email, :personnel_type)'
    );
    $ins->execute([
        ':employee_number'          => $employee_number,
        ':last_name'                => $last_name,
        ':first_name'               => $first_name,
        ':middle_name'              => $middle_name !== '' ? $middle_name : null,
        ':plantilla_item_number'    => $plantilla_item_number,
        ':position'                 => $position,
        ':salary_grade'             => $salary_grade,
        ':step_increment'           => $step_increment,
        ':date_original_appointment'=> $date_original_appointment,
        ':date_last_promotion'      => $date_last_promotion,
        ':deped_email'              => $deped_email,
        ':personnel_type'           => $personnel_type,
    ]);

    ob_end_clean();
    header('Location: adminEmployees.php?added=1');
    exit;

} catch (PDOException $e) {
    ob_end_clean();
    header('Location: adminEmployees.php?error=db_error');
    exit;
}
