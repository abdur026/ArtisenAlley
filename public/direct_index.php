<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Direct database connection without using the problematic db.php
$servername = "localhost";
$username = "qrehman";
$password = "qrehman";
$dbname = "qrehman";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<h1>Connection failed</h1><p>" . $conn->connect_error . "</p>");
}

// Define BASE_URL for the UBCO server
define('BASE_URL', '/qrehman/ArtisenAlley/public');

// Define url function
function url($path) {
    $path = ltrim($path, '/');
    if (empty($path)) {
        return BASE_URL ?: '/';
    }
    if (!empty(BASE_URL)) {
        return BASE_URL . '/' . $path;
    } else {
        return '/' . $path;
    }
}

// Start the session if needed
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Output a basic page to show that PHP is working
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Alley - Direct Access</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #3a6ea5;
        }
        .success {
            color: green;
            background: #e8f5e9;
            padding: 10px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .links {
            margin-top: 30px;
        }
        .links a {
            display: inline-block;
            margin-right: 15px;
            padding: 8px 15px;
            background: #3a6ea5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .debug {
            margin-top: 30px;
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Artisan Alley - Direct Access</h1>
        
        <div class="success">
            <strong>Success!</strong> PHP is working and the database connection is established.
        </div>
        
        <p>This page bypasses the problematic db.php file and establishes a direct connection to the database.</p>
        
        <div class="links">
            <h3>Try these links:</h3>
            <a href="<?php echo url('/basic_connection.php'); ?>">Basic Connection Test</a>
            <a href="<?php echo url('/serverdiag.php'); ?>">Server Diagnostic</a>
        </div>
        
        <div class="debug">
            <h3>Server Information:</h3>
            <p>Server: <?php echo $_SERVER['SERVER_NAME']; ?></p>
            <p>PHP Version: <?php echo phpversion(); ?></p>
            <p>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
            <p>Script Path: <?php echo $_SERVER['SCRIPT_FILENAME']; ?></p>
            <p>BASE_URL: <?php echo BASE_URL; ?></p>
            
            <h3>Database Connection:</h3>
            <?php
            // Test query
            $test_query = "SHOW TABLES";
            $result = $conn->query($test_query);
            if ($result) {
                echo "<p>Tables in database:</p><ul>";
                if ($result->num_rows > 0) {
                    while($row = $result->fetch_array()) {
                        echo "<li>" . htmlspecialchars($row[0]) . "</li>";
                    }
                } else {
                    echo "<li>No tables found</li>";
                }
                echo "</ul>";
            } else {
                echo "<p>Error querying database: " . $conn->error . "</p>";
            }
            ?>
        </div>
    </div>
</body>
</html>
