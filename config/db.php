<?php
// Database configuration with improved error handling

// Automatically detect if we're on the UBCO server
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_production = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

// Full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

// Create connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        $error_message = "Database connection failed: " . $conn->connect_error;
        error_log($error_message);
        
        if ($is_production) {
            // Add a comment to the error for easy identification
            echo "<!-- DB Connection Error: " . htmlspecialchars($conn->connect_error) . " -->";
            
            // Show user-friendly error in production
            echo "<div style='margin: 50px auto; max-width: 800px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; font-family: Arial, sans-serif;'>";
            echo "<h2>Database Connection Error</h2>";
            echo "<p>There was a problem connecting to the database. This might be due to:</p>";
            echo "<ul>";
            echo "<li>Database password needs to be updated</li>";
            echo "<li>Database tables are not properly set up</li>";
            echo "</ul>";
            echo "<p>Please try visiting <a href='/qrehman/ArtisenAlley/public/debug.php'>the debug page</a> for more information.</p>";
            echo "</div>";
        } else {
            // Regular error message for local development
            die("Connection failed: " . $conn->connect_error);
        }
    }
} catch (Exception $e) {
    $error_message = "Exception caught while connecting to database: " . $e->getMessage();
    error_log($error_message);
    
    echo "<div style='margin: 50px auto; max-width: 800px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; font-family: Arial, sans-serif;'>";
    echo "<h2>Database Connection Error</h2>";
    echo "<p>An unexpected error occurred: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please try visiting <a href='/qrehman/ArtisenAlley/public/debug.php'>the debug page</a> for more information.</p>";
    echo "</div>";
}
?>
