<?php
/**
 * Test Bootstrap File
 * Sets up the environment for running tests
 */

// Define the test environment
define('TEST_ENV', true);

// Set proper include paths to access application files
$projectRoot = dirname(__DIR__);
set_include_path(get_include_path() . PATH_SEPARATOR . $projectRoot);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration but don't establish connection immediately
// We'll create test-specific connection functions
require_once $projectRoot . '/config/config.php';

// Test database configuration - use the same database with a test_ prefix
// This can be changed as needed
define('TEST_DB_HOST', DB_HOST);
define('TEST_DB_USER', DB_USER);
define('TEST_DB_PASS', DB_PASS);
define('TEST_DB_NAME', DB_NAME); // Using same DB for testing

/**
 * Get a database connection for testing
 * 
 * @return mysqli Database connection
 */
function getTestDbConnection() {
    $conn = new mysqli(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB_NAME);
    
    if ($conn->connect_error) {
        die("Test database connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

/**
 * Helper function to get a mock connection for unit tests
 * that don't need actual database connections
 */
function getMockDbConnection() {
    return new class {
        public function prepare($query) {
            return new class {
                public function bind_param() {
                    return true;
                }
                public function execute() {
                    return true;
                }
                public function get_result() {
                    return new class {
                        public function fetch_assoc() {
                            return [];
                        }
                        public function num_rows() {
                            return 0;
                        }
                    };
                }
            };
        }
        public function query() {
            return new class {
                public function fetch_assoc() {
                    return [];
                }
                public function num_rows() {
                    return 0;
                }
            };
        }
    };
} 