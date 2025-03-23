<?php
// Path configuration

// Detect if we're on the UBCO server
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_ubco_server = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

// Get the request URI for debugging
$request_uri = $_SERVER['REQUEST_URI'] ?? '';

// Set the base URL accordingly
if ($is_ubco_server) {
    // URL structure on UBCO server
    define('BASE_URL', '/qrehman/ArtisenAlley/public');
} else {
    // Localhost or other environment
    define('BASE_URL', '');
}

// Debug information (will be removed in production)
if (isset($_GET['debug_paths'])) {
    echo "<pre>";
    echo "Server Name: " . htmlspecialchars($server_name) . "\n";
    echo "Request URI: " . htmlspecialchars($request_uri) . "\n";
    echo "Is UBCO Server: " . ($is_ubco_server ? 'Yes' : 'No') . "\n";
    echo "BASE_URL: " . BASE_URL . "\n";
    echo "</pre>";
}

// Helper function to generate correct URLs
function url($path) {
    // Remove any leading slashes from the path
    $path = ltrim($path, '/');
    
    // Return the complete URL
    if (!empty($path)) {
        return BASE_URL . '/' . $path;
    } else {
        return BASE_URL;
    }
}
?>
