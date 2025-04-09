<?php
// Get the absolute path to the project root
define('PROJECT_ROOT', dirname(__DIR__));

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

