<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Local development user
$password = ""; // Empty password for local development
$dbname = "artisenalley";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

