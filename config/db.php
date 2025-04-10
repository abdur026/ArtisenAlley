<?php
// Database configuration
$servername = "localhost";
$username = "hali07"; // Local development user
$password = "hali07"; // Empty password for local development
$dbname = "hali07";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

