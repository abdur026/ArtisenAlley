<?php
// Database configuration
$servername = "localhost";
$username = "hali07"; // Change this if needed
$password = "hali07"; // Change this if needed
$dbname = "hali07";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

