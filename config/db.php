<?php
// Basic config
$servername = "localhost"; // Always use localhost for MySQL connections
$username = "qrehman";
$password = "qrehman";
$dbname = "qrehman";

// Connect to database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}
?>
