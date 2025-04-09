<?php
// Determine if we're running on the UBC server or locally
$is_ubc_server = (strpos($_SERVER['HTTP_HOST'], 'cosc360.ok.ubc.ca') !== false);

// Database configuration
$servername = "localhost";
if ($is_ubc_server) {
    // UBC server credentials
    $username = "qrehman";
    $password = "qrehman";
    $dbname = "qrehman";
} else {
    // Local development credentials
    $username = "root";  // Your local MySQL username
    $password = "";      // Your local MySQL password
    $dbname = "artisenalley";  // Your local database name
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

