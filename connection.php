<?php
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = ''; // default XAMPP MySQL password
$dbName = 'nullified_db';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

// Use this in PHP files:
// require_once 'connection.php';
// then use the $conn variable directly.
