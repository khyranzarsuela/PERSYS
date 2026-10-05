<?php
declare(strict_types=1);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

start_app_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ./index.php');
    exit;
}

$email = trim((string) ($_POST['depedEmail'] ?? ''));
$password = (string) ($_POST['password'] ?? '');

$stmt = db()->prepare(
    'SELECT 
        accounts.account_id,
        accounts.employee_id,
        accounts.role_id,
        roles.role_name,
        employees.deped_email,
        accounts.username,
        accounts.password_hash
     FROM accounts
     INNER JOIN employees
        ON accounts.employee_id = employees.employee_id
        INNER JOIN roles
        On accounts.role_id = roles.role_id
     WHERE employees.deped_email = ?
       AND accounts.is_active = 1'
);

$stmt->execute([$email]);

$user = $stmt->fetch();

if (
    !$user ||
    !password_verify($password, $user['password_hash'])
) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: ./index.php');
    exit;
}

session_regenerate_id(true);

$_SESSION['account_id']  = (int) $user['account_id'];
$_SESSION['employee_id'] = (int) $user['employee_id'];
$_SESSION['role_id']     = (int) $user['role_id'];
$_SESSION['role_name']    = $user['role_name'];
$_SESSION['username']    = $user['username'];

if ($_SESSION['role_id'] === 1) {
    header('Location: Pages/Admin/adminDashboard.php');
} else {
    header('Location: Pages/Users/userDashboard.php');
}

exit;