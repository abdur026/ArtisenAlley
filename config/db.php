<?php
// Load database configuration from a file outside the web root
$config_path = dirname(__DIR__) . '/config.php';

// Check if the config file exists
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    // Fallback configuration (for development only)
    $DB_CONFIG = [
        'servername' => 'localhost',
        'username' => 'root',
        'password' => '',
        'dbname' => 'handmade_store'
    ];
}

// Create connection using the loaded configuration
$conn = new mysqli(
    $DB_CONFIG['servername'], 
    $DB_CONFIG['username'], 
    $DB_CONFIG['password'], 
    $DB_CONFIG['dbname']
);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>

