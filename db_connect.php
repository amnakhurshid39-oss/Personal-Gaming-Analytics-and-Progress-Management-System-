<?php
/*
    Database connection file
    Project: Personal Gaming Analytics & Progress Management System
    Purpose: Central reusable MySQL connection for all project pages.
*/

$host = "localhost";
$user = "root";
$password = "";
$database = "gaming_system";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
