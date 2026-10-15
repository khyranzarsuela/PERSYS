<?php 
// 1. Start output buffering to prevent "headers already sent" white screens
ob_start(); 

require_once '../../session.php'; 
require_once '../../database.php'; 

start_app_session(); 
require_admin(); 

if ($_SERVER["REQUEST_METHOD"] === "POST") { 
    $employee_id = trim($_POST["employee_id"] ?? "");
    $role_id     = trim($_POST["role_id"] ?? ""); 
    $username    = trim($_POST["username"] ?? ""); 
    $password    = trim($_POST["password"] ?? ""); 

    if ($employee_id === "" || $role_id === "" || $username === "" || $password === ""){ 
        header("Location: adminEmployees.php?error=empty_fields");
        exit;
    } 

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        $stmt = $connection->prepare(
            "INSERT INTO accounts (employee_id, role_id, username, password_hash) VALUES (?, ?, ?, ?)"
        ); 
        
        $stmt->bind_param("iiss", $employee_id, $role_id, $username, $hashed_password); 

        // 2. Execute the insertion
        $stmt->execute();
        
        // Close statements safely
        $stmt->close(); 
        $connection->close(); 
        
        // 3. Clear buffer and force the browser to redirect immediately
        ob_end_clean();
        header("Location: adminEmployees.php?registered=1"); 
        exit; 

    } catch (mysqli_sql_exception $e) {
        // If it fails, clear buffer and redirect to an error state
        ob_end_clean();
        header("Location: adminEmployees.php?error=db_error"); 
        exit;
    }
} else {
    ob_end_clean();
    header("Location: adminEmployees.php");
    exit;
}
