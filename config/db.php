<?php
// Database configuration
$servername = "localhost";
$username = "qrehman"; // UBC server username
$password = "qrehman"; // UBC server password
$dbname = "qrehman"; // UBC server database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

