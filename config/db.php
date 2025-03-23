<?php
// Automatically detect if we're on the UBCO server
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_production = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

// Database configuration
$host = $is_production ? 'production_host' : 'localhost';
$username = $is_production ? 'prod_user' : 'qrehman';
$password = $is_production ? 'prod_password' : 'qrehman';
$database = 'qrehman';  // Replace this with the actual database name

// Connect to database
$conn = new mysqli($host, $username, $password, $database);

// Improved error handling
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("Connection failed: " . $conn->connect_error);
}
?>









