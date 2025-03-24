<?php
// Database configuration

// Automatically detect if we're on the UBCO server
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_production = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

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
    if ($is_production) {
        // More detailed error for debugging on production
        error_log("Database connection failed: " . $conn->connect_error);
        
        // Show more user-friendly error in production with troubleshooting hints
        echo "<div style='margin: 50px auto; max-width: 800px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; font-family: Arial, sans-serif;'>";
        echo "<h2>Database Connection Error</h2>";
        echo "<p>There was a problem connecting to the database. This might be due to:</p>";
        echo "<ul>";
        echo "<li>Database password needs to be updated</li>";
        echo "<li>Database tables are not properly set up</li>";
        echo "</ul>";
        echo "<p>Please contact the system administrator or try accessing <a href='https://cosc360.ok.ubc.ca/phpmyadmin/'>phpMyAdmin</a> to check database status.</p>";
        echo "</div>";
    } else {
        // Regular error message for local development
        die("Connection failed: " . $conn->connect_error);
    }
}
?>
