<?php
// Debug script to identify server issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ArtisenAlley Debug Page</h1>";

// 1. Check server environment
echo "<h2>Server Environment</h2>";
echo "<pre>";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'undefined') . "\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'undefined') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'undefined') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'undefined') . "\n";
echo "</pre>";

// 2. Check paths configuration
echo "<h2>Paths Configuration</h2>";
require_once __DIR__ . '/../config/paths.php';
echo "<pre>";
echo "BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'undefined') . "\n";
echo "URL test: " . url('/index.php') . "\n";
echo "URL test (assets): " . url('/assets/images/placeholder.jpg') . "\n";
echo "</pre>";

// 3. Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once __DIR__ . '/../config/db.php';
    if (isset($conn) && !$conn->connect_error) {
        echo "<p style='color:green'>✓ Database connected successfully</p>";
        
        // Test if tables exist
        echo "<h3>Database Tables</h3>";
        $tables_query = "SHOW TABLES";
        $tables_result = $conn->query($tables_query);
        
        if ($tables_result) {
            echo "<ul>";
            if ($tables_result->num_rows > 0) {
                while($table = $tables_result->fetch_array()) {
                    echo "<li>" . htmlspecialchars($table[0]) . "</li>";
                }
            } else {
                echo "<li style='color:red'>No tables found in database!</li>";
            }
            echo "</ul>";
            
            // Check products table specifically
            $check_products = $conn->query("SELECT 1 FROM products LIMIT 1");
            if ($check_products) {
                echo "<p style='color:green'>✓ Products table exists</p>";
                // Count products
                $count_result = $conn->query("SELECT COUNT(*) as count FROM products");
                if ($count_result) {
                    $count = $count_result->fetch_assoc()['count'];
                    echo "<p>Product count: $count</p>";
                }
            } else {
                echo "<p style='color:red'>✗ Products table does not exist or cannot be queried: " . $conn->error . "</p>";
            }
        } else {
            echo "<p style='color:red'>Error listing tables: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red'>✗ Database connection failed: " . ($conn->connect_error ?? 'Unknown error') . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error: " . $e->getMessage() . "</p>";
}

// 4. Check file permissions and paths
echo "<h2>File System</h2>";
echo "<pre>";
echo "Current directory: " . __DIR__ . "\n";
echo "Parent directory readable: " . (is_readable(__DIR__ . '/..') ? 'Yes' : 'No') . "\n";
echo "Assets directory exists: " . (is_dir(__DIR__ . '/assets') ? 'Yes' : 'No') . "\n";
if (is_dir(__DIR__ . '/assets')) {
    echo "Assets/images directory exists: " . (is_dir(__DIR__ . '/assets/images') ? 'Yes' : 'No') . "\n";
    if (is_dir(__DIR__ . '/assets/images')) {
        echo "Sample image files in assets/images:\n";
        $images = scandir(__DIR__ . '/assets/images');
        $count = 0;
        foreach ($images as $image) {
            if ($image != '.' && $image != '..' && $count < 5) {
                echo "- " . $image . "\n";
                $count++;
            }
        }
        if (count($images) > 5) {
            echo "... and " . (count($images) - 5 - 2) . " more\n"; // -2 for . and ..
        }
    }
}
echo "</pre>";

// 5. Session testing
echo "<h2>Session Test</h2>";
echo "<pre>";
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "Session is active\n";
    echo "Session ID: " . session_id() . "\n";
} else {
    echo "Session is not active\n";
}
echo "</pre>";
?>
