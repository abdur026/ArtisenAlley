<?php
// Basic PHP test file
echo "<h1>PHP Test File</h1>";
echo "<p>PHP version: " . phpversion() . "</p>";
echo "<pre>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'Not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "\n";
echo "</pre>";
?> 