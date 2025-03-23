<?php
// Emergency db.php fixer - this will directly fix your database configuration
// Created by Cascade

// The correct db.php content
$correct_db_content = '<?php
// Simple database configuration - DIRECT FIX
$servername = "localhost";
$username = "qrehman";
$password = "qrehman";
$dbname = "qrehman";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>';

// Path to the db.php file (going up one directory to config)
$db_file_path = __DIR__ . '/../config/db.php';

echo "<h2>Database Configuration Fixer</h2>";
echo "<p>Attempting to fix: " . htmlspecialchars($db_file_path) . "</p>";

// Check if we can write to the file
if (is_writable($db_file_path) || is_writable(dirname($db_file_path))) {
    // Write the correct content to the file
    if (file_put_contents($db_file_path, $correct_db_content)) {
        echo "<p style='color:green;'>SUCCESS! The db.php file has been fixed.</p>";
        echo "<p>Please try your website again now.</p>";
    } else {
        echo "<p style='color:red;'>ERROR: Could not write to the db.php file.</p>";
        echo "<p>Please check file permissions.</p>";
    }
} else {
    echo "<p style='color:red;'>ERROR: The db.php file is not writable.</p>";
    
    // Alternative: Create a temporary file with instructions
    $temp_file = __DIR__ . '/db_fixed.php';
    file_put_contents($temp_file, $correct_db_content);
    
    echo "<h3>MANUAL FIX REQUIRED</h3>";
    echo "<p>I've created a fixed version at: " . htmlspecialchars($temp_file) . "</p>";
    echo "<p>Please copy this file to replace your config/db.php file manually using SFTP or the hosting control panel.</p>";
    
    echo "<h3>File Content to Copy:</h3>";
    echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ddd;'>";
    echo htmlspecialchars($correct_db_content);
    echo "</pre>";
}

// Display the current db.php content for debugging
echo "<h3>Current db.php Content:</h3>";
echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ddd;'>";
if (file_exists($db_file_path)) {
    echo htmlspecialchars(file_get_contents($db_file_path));
} else {
    echo "File does not exist.";
}
echo "</pre>";
?>
