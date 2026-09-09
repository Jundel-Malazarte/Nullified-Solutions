<?php

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = ''; // set as empty string for no password
$dbName = 'nullified_db'; // database name

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);

if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');

return $conn;
