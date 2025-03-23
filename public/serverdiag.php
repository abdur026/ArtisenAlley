<?php
// Set maximum error reporting for complete diagnostics
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ArtisenAlley Server Diagnostic</h1>";
echo "<p>This is a standalone diagnostic tool to help identify server issues.</p>";

// 1. Environment information
echo "<h2>Server Environment</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Not available') . "\n";
echo "Server Name: " . ($_SERVER['SERVER_NAME'] ?? 'Not available') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not available') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'Not available') . "\n";
echo "Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not available') . "\n";
echo "Is UBCO Server: " . (strpos($_SERVER['SERVER_NAME'] ?? '', 'cosc360.ok.ubc.ca') !== false ? 'Yes' : 'No') . "\n";
echo "</pre>";

// 2. File system checks
echo "<h2>File System</h2>";
echo "<pre>";
echo "Current script path: " . __FILE__ . "\n";
echo "Current directory: " . __DIR__ . "\n";
echo "Parent directory: " . dirname(__DIR__) . "\n";

// Check important directories
$directories = [
    'config' => dirname(__DIR__) . '/config',
    'includes' => dirname(__DIR__) . '/includes',
    'public' => __DIR__,
    'assets' => __DIR__ . '/assets',
    'assets/css' => __DIR__ . '/assets/css',
    'assets/images' => __DIR__ . '/assets/images',
    'assets/js' => __DIR__ . '/assets/js'
];

foreach ($directories as $name => $path) {
    echo "Directory '$name': " . (is_dir($path) ? "Exists" : "MISSING") . " [$path]\n";
    if (is_dir($path)) {
        echo "  Readable: " . (is_readable($path) ? "Yes" : "NO") . "\n";
    }
}

// 3. Critical files check
$files = [
    'config/paths.php' => dirname(__DIR__) . '/config/paths.php',
    'config/db.php' => dirname(__DIR__) . '/config/db.php',
    'includes/header.php' => dirname(__DIR__) . '/includes/header.php',
    'includes/footer.php' => dirname(__DIR__) . '/includes/footer.php',
    'assets/css/main.css' => __DIR__ . '/assets/css/main.css'
];

echo "\nCritical Files Check:\n";
foreach ($files as $name => $path) {
    echo "File '$name': " . (file_exists($path) ? "Exists" : "MISSING") . " [$path]\n";
    if (file_exists($path)) {
        echo "  Readable: " . (is_readable($path) ? "Yes" : "NO") . "\n";
    }
}
echo "</pre>";

// 4. Try accessing database without using config/db.php
echo "<h2>Direct Database Test</h2>";
echo "<pre>";
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_production = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

if ($is_production) {
    // UBCO server configuration
    $servername = "localhost";
    $username = "qrehman";
    $password = "qrehman";
    $dbname = "qrehman";
} else {
    // Local database configuration
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "handmade_store";
}

try {
    echo "Attempting direct database connection...\n";
    echo "Server: $servername\n";
    echo "Username: $username\n";
    echo "Database: $dbname\n";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        echo "CONNECTION ERROR: " . $conn->connect_error . "\n";
    } else {
        echo "SUCCESS: Connected to database directly!\n";
        
        // Test if required tables exist
        $tables = ['users', 'products', 'categories', 'orders'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            echo "Table '$table': " . ($result->num_rows > 0 ? "Exists" : "MISSING") . "\n";
        }
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
}
echo "</pre>";

// 5. Session test
echo "<h2>Session Test</h2>";
echo "<pre>";
echo "Session status: " . session_status() . " (1=Disabled, 2=Enabled but no session, 3=Active)\n";

// Try starting a session if not already started
if (session_status() == PHP_SESSION_NONE) {
    echo "Attempting to start session...\n";
    session_start();
    echo "Session started. New status: " . session_status() . "\n";
}

if (session_status() == PHP_SESSION_ACTIVE) {
    echo "Session ID: " . session_id() . "\n";
    echo "Session writable: ";
    try {
        $_SESSION['test'] = 'test';
        echo isset($_SESSION['test']) ? "Yes" : "No";
        unset($_SESSION['test']);
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage();
    }
    echo "\n";
}
echo "</pre>";

// 6. Simple path test
echo "<h2>Path Calculation Test</h2>";
echo "<pre>";

// Manually calculate what BASE_URL should be
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_ubco_server = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

echo "Using manual path calculation:\n";
if ($is_ubco_server) {
    $base_url = '/qrehman/ArtisenAlley/public';
} else {
    $base_url = '';
}
echo "Calculated BASE_URL: $base_url\n";

// Test URL generation for a few examples
$test_paths = [
    'index.php',
    'login.php',
    'assets/css/main.css',
    'assets/images/placeholder.jpg',
];

echo "\nURL generation test:\n";
foreach ($test_paths as $path) {
    $path = ltrim($path, '/');
    if (!empty($base_url)) {
        $url = $base_url . '/' . $path;
    } else {
        $url = '/' . $path;
    }
    echo "Path '$path' -> URL '$url'\n";
}
echo "</pre>";
?>
