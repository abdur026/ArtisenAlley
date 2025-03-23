<?php
// Database configuration
$is_production = false; // Set to true when deployed to cosc360.ok.ubc.ca

if ($is_production) {
    // UBCO server configuration
    $servername = "localhost"; // Database can only be connected to from localhost on the server
    $username = "qrehman"; // Your CWL
    $password = "qrehman"; // Initially your CWL, should be changed for security
    $dbname = "qrehman"; // Your database name is your CWL
} else {
    // Local database configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "handmade_store";
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
