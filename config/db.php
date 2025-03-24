<?php
// Database configuration
$servername = "cosc360.ok.ubc.ca";
$username = "qrehman";
$password = "qrehman";
$dbname = "db_qrehman";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
