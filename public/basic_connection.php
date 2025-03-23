<?php
// This is a minimal test file
echo "PHP is working!";

// Try database connection with minimal code
$servername = "localhost";
$username = "qrehman";
$password = "qrehman";
$dbname = "qrehman";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
  echo "Connection failed: " . $conn->connect_error;
} else {
  echo "Connected successfully to database!";
}
?>
