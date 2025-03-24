<?php
// Server Test File for ArtisenAlley deployed on UBCO server
// Access at: https://cosc360.ok.ubc.ca/qrehman/ArtisenAlley/public/server_test.php

echo "<html><head><title>ArtisenAlley Server Test</title>";
echo "<style>body{font-family:Arial,sans-serif;max-width:800px;margin:0 auto;padding:20px;line-height:1.6} 
.success{color:green;font-weight:bold} .error{color:red;font-weight:bold} 
h1{color:#333} h2{color:#555;margin-top:30px;border-bottom:1px solid #ddd;padding-bottom:5px}
pre{background:#f5f5f5;padding:10px;border-radius:4px;overflow:auto}
.container{border:1px solid #ddd;padding:15px;margin-bottom:20px;border-radius:4px}</style>";
echo "</head><body>";

echo "<h1>ArtisenAlley Server Test</h1>";
echo "<p>Testing deployment on UBCO server (cosc360.ok.ubc.ca)</p>";

// Get configuration status
echo "<div class='container'>";
echo "<h2>Configuration Status</h2>";
require_once "../config/db.php";
echo "<p>Production Mode: <span class='" . ($is_production ? "success" : "error") . "'>" . ($is_production ? "Enabled" : "Disabled") . "</span></p>";
echo "<p>If this shows 'Disabled', run: <code>sed -i 's/\$is_production = false;/\$is_production = true;/' ../config/db.php</code></p>";
echo "</div>";

// Test database connection
echo "<div class='container'>";
echo "<h2>Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "<p class='error'>Database connection failed: " . $conn->connect_error . "</p>";
    echo "<p>Possible issues:</p>";
    echo "<ul>";
    echo "<li>MySQL password is incorrect (default is your CWL)</li>";
    echo "<li>Database name is incorrect (should be your CWL)</li>";
    echo "<li>MySQL server is not running</li>";
    echo "</ul>";
} else {
    echo "<p class='success'>Database connected successfully!</p>";
    
    // Try to get the tables
    $result = $conn->query("SHOW TABLES");
    if ($result) {
        echo "<h3>Tables in database:</h3>";
        if ($result->num_rows > 0) {
            echo "<ul>";
            while($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
            
            // Check if the expected tables exist
            $expected_tables = ['users', 'products', 'orders', 'order_items', 'reviews'];
            $existing_tables = [];
            $result->data_seek(0);
            while($row = $result->fetch_array()) {
                $existing_tables[] = $row[0];
            }
            
            $missing_tables = array_diff($expected_tables, $existing_tables);
            if (!empty($missing_tables)) {
                echo "<p class='error'>Missing tables: " . implode(', ', $missing_tables) . "</p>";
                echo "<p>Run the database setup script: <code>source ~/public_html/ArtisenAlley/db_setup.sql;</code></p>";
            } else {
                echo "<p class='success'>All expected tables exist!</p>";
            }
        } else {
            echo "<p class='error'>No tables found. Did you run the database setup script?</p>";
            echo "<p>Run: <code>mysql -u qrehman -p</code> and then <code>source ~/public_html/ArtisenAlley/db_setup.sql;</code></p>";
        }
    }
}
echo "</div>";

// File permissions check
echo "<div class='container'>";
echo "<h2>File Permissions Check</h2>";
$upload_dir = "../public/images/products/";
if (file_exists($upload_dir)) {
    $is_writable = is_writable($upload_dir);
    echo "<p>Upload directory ($upload_dir): <span class='" . ($is_writable ? "success" : "error") . "'>" . ($is_writable ? "Writable" : "Not Writable") . "</span></p>";
    if (!$is_writable) {
        echo "<p>Fix permissions with: <code>chmod -R 775 " . realpath($upload_dir) . "</code></p>";
    }
} else {
    echo "<p class='error'>Upload directory does not exist: $upload_dir</p>";
    echo "<p>Create it with: <code>mkdir -p $upload_dir && chmod -R 775 $upload_dir</code></p>";
}
echo "</div>";

// Server information
echo "<div class='container'>";
echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>URL Path: " . $_SERVER['REQUEST_URI'] . "</p>";

// Extensions check
echo "<h3>Required Extensions:</h3>";
$required_extensions = ['mysqli', 'gd', 'json', 'session'];
foreach ($required_extensions as $ext) {
    $loaded = extension_loaded($ext);
    echo "<p>$ext: <span class='" . ($loaded ? "success" : "error") . "'>" . ($loaded ? "Loaded" : "Not Loaded") . "</span></p>";
}
echo "</div>";

// Links to important pages
echo "<div class='container'>";
echo "<h2>Quick Links</h2>";
$base_url = dirname($_SERVER['REQUEST_URI']);
echo "<ul>";
echo "<li><a href='{$base_url}/index.php'>Home Page</a></li>";
echo "<li><a href='{$base_url}/login.php'>Login Page</a></li>";
echo "<li><a href='{$base_url}/admin_dashboard.php'>Admin Dashboard</a></li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='https://github.com/abdur026/ArtisenAlley'>View on GitHub</a></p>";
echo "</body></html>";
?>
