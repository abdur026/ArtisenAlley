<?php
/**
 * Test Runner
 * Executes all tests in the test directory
 */

// Include bootstrap
require_once __DIR__ . '/bootstrap.php';

// Colors for terminal output
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_RESET', "\033[0m");

// Test results tracking
$totalTests = 0;
$passedTests = 0;
$failedTests = [];

/**
 * Simple assertion function
 */
function assert_true($condition, $message) {
    global $totalTests, $passedTests, $failedTests, $currentTest;
    
    $totalTests++;
    
    if ($condition) {
        $passedTests++;
        echo ".";
    } else {
        $failedTests[] = [
            'test' => $currentTest,
            'message' => $message
        ];
        echo "F";
    }
}

/**
 * Float equality assertion with epsilon
 */
function assert_float_equals($expected, $actual, $message, $epsilon = 0.0001) {
    global $totalTests, $passedTests, $failedTests, $currentTest;
    
    $totalTests++;
    
    if (abs($expected - $actual) < $epsilon) {
        $passedTests++;
        echo ".";
    } else {
        $failedTests[] = [
            'test' => $currentTest,
            'message' => $message . " (Expected: $expected, Got: $actual)"
        ];
        echo "F";
    }
}

/**
 * Recursively find and run all test files
 */
function runTestsInDirectory($directory) {
    global $currentTest;
    
    $files = scandir($directory);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $path = $directory . '/' . $file;
        
        if (is_dir($path)) {
            runTestsInDirectory($path);
        } else if (preg_match('/Test\.php$/', $file)) {
            $currentTest = $path;
            echo "\nRunning $path: ";
            require_once $path;
        }
    }
}

// Run all tests
echo "Starting tests...\n";
runTestsInDirectory(__DIR__ . '/unit');

// Display results
echo "\n\n=== Test Results ===\n";
echo "Total tests: $totalTests\n";
echo COLOR_GREEN . "Passed: $passedTests" . COLOR_RESET . "\n";

if (count($failedTests) > 0) {
    echo COLOR_RED . "Failed: " . count($failedTests) . COLOR_RESET . "\n\n";
    
    echo "=== Failed Tests ===\n";
    foreach ($failedTests as $failure) {
        echo COLOR_RED . $failure['test'] . COLOR_RESET . "\n";
        echo "  " . $failure['message'] . "\n";
    }
    
    exit(1);
} else {
    echo "\nAll tests passed!\n";
    exit(0);
} 