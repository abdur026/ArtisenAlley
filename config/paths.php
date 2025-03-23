<?php
// Path configuration with robust server detection and URL generation

// Collect server information for detection
$server_name = $_SERVER['SERVER_NAME'] ?? '';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';

// More robust UBCO server detection
$is_ubco_server = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

// Set the base URL based on environment
if ($is_ubco_server) {
    // UBCO server base path - ABSOLUTE path including domain prefix
    define('BASE_URL', '/qrehman/ArtisenAlley/public');
    
    // Enable full error reporting on the server for debugging
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    // Local development environment
    define('BASE_URL', '');
}

// Get dirname of the current script for context awareness
$script_dir = dirname($_SERVER['SCRIPT_NAME'] ?? '');

// Debug information (will be removed in production)
if (isset($_GET['debug_paths'])) {
    echo "<pre style='background:#f5f5f5;padding:10px;border:1px solid #ccc;'>";
    echo "===== URL PATH DEBUG INFO =====\n";
    echo "Server Name: " . htmlspecialchars($server_name) . "\n";
    echo "Request URI: " . htmlspecialchars($request_uri) . "\n";
    echo "Script Name: " . htmlspecialchars($script_name) . "\n";
    echo "Script Dir: " . htmlspecialchars($script_dir) . "\n";
    echo "Is UBCO Server: " . ($is_ubco_server ? 'Yes' : 'No') . "\n";
    echo "BASE_URL: " . BASE_URL . "\n";
    echo "============================\n";
    echo "</pre>";
}

/**
 * Generate a complete URL based on the current environment
 * 
 * @param string $path The relative path to generate a URL for
 * @return string The complete URL with base path
 */
function url($path) {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // For empty paths, just return the base URL
    if (empty($path)) {
        return BASE_URL ?: '/';
    }
    
    // This ensures we always have a single slash between base URL and path
    if (!empty(BASE_URL)) {
        // When base URL exists, ensure proper formatting
        return BASE_URL . '/' . $path;
    } else {
        // For local development, just prepend a slash
        return '/' . $path;
    }
}

/**
 * Force redirect to a specific URL
 * 
 * @param string $path The path to redirect to
 * @return void
 */
function redirect($path) {
    $url = url($path);
    header("Location: {$url}");
    exit;
}
?>
