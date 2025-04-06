<?php
// Database configuration
$servername = "localhost";
$username = "root"; // Change this if needed
$password = ""; // Change this if needed
$dbname = "handmade_store";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to prevent XSS
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    return true;
}

?>

