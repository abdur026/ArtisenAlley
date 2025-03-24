<?php
// Simplified path configuration for troubleshooting

// Collect server information
$server_name = $_SERVER['SERVER_NAME'] ?? '';

// Simply detect if on UBCO server
$is_ubco_server = (strpos($server_name, 'cosc360.ok.ubc.ca') !== false);

// Set paths based on server detection
if ($is_ubco_server) {
    // UBCO server paths
    define('BASE_URL', '/kaamir01/ArtisenAlley/public');
    define('SITE_ROOT', '/kaamir01/ArtisenAlley');
} else {
    // Local development paths
    define('BASE_URL', '');
    define('SITE_ROOT', '');
}

/**
 * Generate URL
 */
function url($path) {
    $path = ltrim($path, '/');
    
    if (empty($path)) {
        return BASE_URL ?: '/';
    }
    
    return BASE_URL . '/' . $path;
}

/**
 * Generate asset URL
 */
function asset_url($path) {
    $path = ltrim($path, '/');
    
    if (empty($path)) {
        return SITE_ROOT ?: '/';
    }
    
    return BASE_URL . '/' . $path;
}

/**
 * Redirect
 */
function redirect($path) {
    $url = url($path);
    header("Location: {$url}");
    exit;
}
?>
