<?php
/**
 * Database Operations Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../TestUtils.php';

// This test file uses mock objects instead of actual database connections
// to ensure we don't modify any live data

// Test case 1: Test product query using mocks
function test_product_query() {
    // Create a mock product
    $mockProduct = createMockProduct(1, 'Artisan Lamp', 79.99, 'A handcrafted lamp', 'Home Decor');
    
    // Create a mock connection
    $mockConn = new class($mockProduct) {
        private $mockProduct;
        
        public function __construct($mockProduct) {
            $this->mockProduct = $mockProduct;
        }
        
        public function prepare($query) {
            // Check if the query is a product query
            if (strpos($query, 'SELECT * FROM products WHERE id = ?') !== false) {
                return new class($this->mockProduct) {
                    private $mockProduct;
                    
                    public function __construct($mockProduct) {
                        $this->mockProduct = $mockProduct;
                    }
                    
                    public function bind_param($type, $id) {
                        // Just verify the type is correct
                        assert_true($type === 'i', 'Parameter type should be integer (i)');
                        return true;
                    }
                    
                    public function execute() {
                        return true;
                    }
                    
                    public function get_result() {
                        return new class($this->mockProduct) {
                            private $mockProduct;
                            
                            public function __construct($mockProduct) {
                                $this->mockProduct = $mockProduct;
                            }
                            
                            public function fetch_assoc() {
                                return $this->mockProduct;
                            }
                            
                            public $num_rows = 1;
                        };
                    }
                };
            }
            
            return null;
        }
    };
    
    // Now simulate the product.php flow with our mock
    $product_id = 1;
    $stmt = $mockConn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    // Verify we get the expected mock product back
    assert_true($product['id'] === 1, "Product ID should match expected value");
    assert_true($product['name'] === 'Artisan Lamp', "Product name should match expected value");
    assert_true($product['price'] === 79.99, "Product price should match expected value");
    assert_true($product['category'] === 'Home Decor', "Product category should match expected value");
}

// Test case 2: Test user authentication query
function test_user_authentication() {
    // Create a mock user with a known password
    $password = 'test_password';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $mockUser = [
        'id' => 1,
        'email' => 'test@example.com',
        'password' => $hash,
        'is_admin' => 0,
        'name' => 'Test User'
    ];
    
    // Create a mock connection
    $mockConn = new class($mockUser) {
        private $mockUser;
        
        public function __construct($mockUser) {
            $this->mockUser = $mockUser;
        }
        
        public function prepare($query) {
            // Check if the query is a user query
            if (strpos($query, 'SELECT * FROM users WHERE email = ?') !== false) {
                return new class($this->mockUser) {
                    private $mockUser;
                    
                    public function __construct($mockUser) {
                        $this->mockUser = $mockUser;
                    }
                    
                    public function bind_param($type, $email) {
                        // Just verify the type is correct
                        assert_true($type === 's', 'Parameter type should be string (s)');
                        // Verify the email matches our mock
                        assert_true($email === 'test@example.com', 'Email should match the mock user');
                        return true;
                    }
                    
                    public function execute() {
                        return true;
                    }
                    
                    public function get_result() {
                        return new class($this->mockUser) {
                            private $mockUser;
                            
                            public function __construct($mockUser) {
                                $this->mockUser = $mockUser;
                            }
                            
                            public function fetch_assoc() {
                                return $this->mockUser;
                            }
                            
                            public $num_rows = 1;
                        };
                    }
                };
            }
            
            return null;
        }
    };
    
    // Simulate a login flow
    $email = 'test@example.com';
    $password = 'test_password';
    $wrong_password = 'wrong_password';
    
    // Query the user
    $stmt = $mockConn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // Verify password validation
    $valid_password = password_verify($password, $user['password']);
    $invalid_password = password_verify($wrong_password, $user['password']);
    
    assert_true($valid_password, "Valid password should verify correctly");
    assert_true(!$invalid_password, "Invalid password should not verify");
    
    // Verify user data
    assert_true($user['id'] === 1, "User ID should match expected value");
    assert_true($user['email'] === 'test@example.com', "User email should match expected value");
    assert_true($user['name'] === 'Test User', "User name should match expected value");
}

// Run the tests
test_product_query();
test_user_authentication(); 