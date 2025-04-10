<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Server Configuration Check</h2>";

// Directory structure
echo "<h3>Directory Structure:</h3>";
echo "<pre>";
echo "Current directory: " . __DIR__ . "\n";
echo "Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "</pre>";

// Database connection
echo "<h3>Database Connection:</h3>";
try {
    require_once __DIR__ . '/config/db.php';
    
    if (!$conn) {
        echo "<p style='color: red;'>Database connection failed!</p>";
    } else {
        echo "<p style='color: green;'>Database connection successful!</p>";
        
        // Check if tables exist
        $tables_result = $conn->query("SHOW TABLES");
        if ($tables_result) {
            echo "<p>Tables in database:</p>";
            echo "<ul>";
            while ($table = $tables_result->fetch_array()) {
                echo "<li>" . $table[0] . "</li>";
            }
            echo "</ul>";
        }
        
        // Check products table
        $products_result = $conn->query("SELECT COUNT(*) as count FROM products");
        if ($products_result) {
            $row = $products_result->fetch_assoc();
            echo "<p>Number of products in database: " . $row['count'] . "</p>";
        } else {
            echo "<p style='color: red;'>Error checking products table: " . $conn->error . "</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// PHP Info
echo "<h3>PHP Information:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Loaded Extensions: " . implode(', ', get_loaded_extensions()) . "</p>";

// File permissions
echo "<h3>File Permissions:</h3>";
$folders_to_check = [
    '.' => 'Root',
    './public' => 'Public',
    './config' => 'Config',
    './includes' => 'Includes'
];

echo "<ul>";
foreach ($folders_to_check as $folder => $label) {
    if (file_exists($folder)) {
        $perms = substr(sprintf('%o', fileperms($folder)), -4);
        echo "<li>$label folder ($folder): $perms</li>";
    } else {
        echo "<li>$label folder ($folder): Not found</li>";
    }
}
echo "</ul>";
?> 