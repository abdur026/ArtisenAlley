<?php
// Database configuration for the ArtisenAlley project

// Automatically detect if we're on the UBCO server
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_production = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

// Set the database parameters based on environment
if ($is_production) {
    // UBCO server configuration
    $servername = "localhost";
    $username = "qrehman";
    $password = "qrehman";
    $dbname = "qrehman";
} else {
    // Local development configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "handmade_store";
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Log the error
    error_log("Database connection failed: " . $conn->connect_error);
    
    // Display an appropriate error message
    if ($is_production) {
        // User-friendly error message for production
        echo "<div style='margin: 50px auto; max-width: 800px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; font-family: Arial, sans-serif;'>";
        echo "<h2>Database Connection Error</h2>";
        echo "<p>There was a problem connecting to the database. Please try again later or contact the administrator.</p>";
        echo "</div>";
    } else {
        // More detailed error for development
        die("Connection failed: " . $conn->connect_error);
    }
}
?>
