<?php
// Path configuration

// Detect if we're on the UBCO server
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$is_ubco_server = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

// Set the base URL accordingly
if ($is_ubco_server) {
    // URL structure on UBCO server: /qrehman/ArtisenAlley/public/
    define('BASE_URL', '/qrehman/ArtisenAlley/public');
} else {
    // Localhost or other environment
    define('BASE_URL', '');
}

// Helper function to generate correct URLs
function url($path) {
    // Remove any leading slashes from the path
    $path = ltrim($path, '/');
    
    // Return the complete URL
    return BASE_URL . '/' . $path;
}
?>
