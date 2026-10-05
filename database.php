<?php
// database.php

// Define the SESSION_LIFETIME constant since session.php relies on it
if (!defined('SESSION_LIFETIME')) {
    define('SESSION_LIFETIME', 86400); // 24 hours in seconds
}

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = "localhost";
        $database = "db_persys";
        $username = "root";
        $password = "";
        
        try {
            $pdo = new PDO(
                "mysql:host=$host;dbname=$database;charset=utf8mb4",
                $username,
                $password,
                [
                    // FIX IS HERE: Change ATTR_ERRORS to ATTR_ERRMODE
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, 
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    return $pdo;
}


// Keep your old variable temporarily if other legacy files rely on it
$connection = mysqli_connect("localhost", "root", "", "db_persys");
