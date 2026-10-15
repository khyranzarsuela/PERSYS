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

$employee_id = trim($_POST['employee_id'] ?? '');
$role_id     = trim($_POST['role_id']     ?? '');
$username    = trim($_POST['username']    ?? '');
$password    = trim($_POST['password']    ?? '');

if ($employee_id === '' || $role_id === '' || $username === '' || $password === '') {
    ob_end_clean();
    header('Location: adminEmployees.php?error=empty_fields');
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo  = db();
    $stmt = $pdo->prepare(
        'INSERT INTO accounts (employee_id, role_id, username, password_hash) VALUES (:employee_id, :role_id, :username, :password_hash)'
    );
    $stmt->execute([
        ':employee_id'   => (int) $employee_id,
        ':role_id'       => (int) $role_id,
        ':username'      => $username,
        ':password_hash' => $hashed_password,
    ]);

    ob_end_clean();
    header('Location: adminEmployees.php?registered=1');
    exit;

} catch (PDOException $e) {
    $code = $e->getCode();
    ob_end_clean();

    if ($code == 23000) {
        // Parse the driver-specific message to distinguish duplicate username vs duplicate employee
        $msg = $e->getMessage();
        if (stripos($msg, 'username') !== false) {
            header('Location: adminEmployees.php?error=duplicate_username');
        } elseif (stripos($msg, 'uq_accounts_employee') !== false || stripos($msg, 'employee_id') !== false) {
            header('Location: adminEmployees.php?error=already_has_account');
        } else {
            header('Location: adminEmployees.php?error=duplicate_username');
        }
    } else {
        header('Location: adminEmployees.php?error=db_error');
    }
    exit;
}
