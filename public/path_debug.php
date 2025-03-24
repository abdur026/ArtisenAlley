<?php
require_once __DIR__ . '/../config/paths.php';
echo "<h1>Path Debug Information</h1>";
echo "<pre>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "\n";
echo "Is UBCO Server: " . ($is_ubco_server ? 'Yes' : 'No') . "\n";
echo "BASE_URL: " . BASE_URL . "\n";
echo "SITE_ROOT: " . SITE_ROOT . "\n";
echo "Example URL: " . url('index.php') . "\n";
echo "Example Asset URL: " . asset_url('assets/css/main.css') . "\n";
echo "</pre>"; 