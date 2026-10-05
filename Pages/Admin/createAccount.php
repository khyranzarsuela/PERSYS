<?php
require_once '../../session.php';
require_once '../../database.php';

start_app_session();
require_admin();

$pdo = db();


$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username  = trim($_POST["username"] ?? "");
    $password = trim($_POST["password"] ?? "");

    if ($username === "" || $password === ""){
        $message = "Please complete all required fields.";
    } else {

        $stmt = $connection->prepare(
            "INSERT INTO accounts (username, password_hash) VALUES (?, ?)"
        );

        $stmt->bind_param("ss", $username, $password);

        if ($stmt->execute()) {
            $stmt->close();
            $connection->close();

            header("Location: adminEmployees.php?registered=1");
            exit;
        }

        $message = "Error creating account: " . $stmt->error;
        header("Location: index.php?error=400");
        $stmt->close();
    }
}