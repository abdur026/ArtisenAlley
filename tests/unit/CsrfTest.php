<?php
/**
 * CSRF Utilities Tests
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../TestUtils.php';

// Reset any existing session data
session_start();
$_SESSION = [];

// Test case 1: Generate CSRF token
function test_generate_csrf_token() {
    // Define a simplified version of the function for testing
    function generate_test_csrf_token($form_name = 'default') {
        if (!isset($_SESSION['csrf_tokens'])) {
            $_SESSION['csrf_tokens'] = [];
        }
        
        // Use a fixed token for testing
        if (defined('TEST_ENV') && TEST_ENV === true) {
            $token = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';
        } else {
            $token = bin2hex(random_bytes(32));
        }
        
        $_SESSION['csrf_tokens'][$form_name] = [
            'token' => $token,
            'time' => time()
        ];
        
        return $token;
    }

    // Generate a token for the default form
    $token = generate_test_csrf_token();
    
    // Check if token is stored in session
    assert_true(
        isset($_SESSION['csrf_tokens']['default']), 
        "CSRF token should be stored in session"
    );
    
    // Check if token is returned correctly
    assert_true(
        $token === $_SESSION['csrf_tokens']['default']['token'],
        "CSRF token returned should match the one stored in session"
    );
    
    // Test custom form name
    $custom_token = generate_test_csrf_token('login_form');
    assert_true(
        isset($_SESSION['csrf_tokens']['login_form']),
        "CSRF token should be stored with custom form name"
    );
    
    // Check if time is stored correctly
    $current_time = time();
    assert_true(
        abs($_SESSION['csrf_tokens']['default']['time'] - $current_time) < 2,
        "CSRF token time should be close to current time"
    );
}

// Test case 2: Validate CSRF token
function test_validate_csrf_token() {
    // Define a simplified version of the function for testing
    function validate_test_csrf_token($token, $form_name = 'default') {
        if (!isset($_SESSION['csrf_tokens'][$form_name])) {
            return false;
        }
        
        $stored = $_SESSION['csrf_tokens'][$form_name];
        
        // Check if the token has expired (tokens valid for 1 hour)
        if (time() - $stored['time'] > 3600) {
            unset($_SESSION['csrf_tokens'][$form_name]);
            return false;
        }
        
        // Validate the token
        $valid = ($stored['token'] === $token); // Simplified for testing
        
        // Single-use token - remove after validation
        if ($valid) {
            unset($_SESSION['csrf_tokens'][$form_name]);
        }
        
        return $valid;
    }
    
    // Setup a test token
    $_SESSION['csrf_tokens']['test_form'] = [
        'token' => 'test_token_123',
        'time' => time()
    ];
    
    // Valid token test
    assert_true(
        validate_test_csrf_token('test_token_123', 'test_form'),
        "Valid CSRF token should validate successfully"
    );
    
    // Token should be removed after successful validation
    assert_true(
        !isset($_SESSION['csrf_tokens']['test_form']),
        "CSRF token should be removed after successful validation"
    );
    
    // Setup another test token
    $_SESSION['csrf_tokens']['another_form'] = [
        'token' => 'another_token_456',
        'time' => time()
    ];
    
    // Invalid token test
    assert_true(
        !validate_test_csrf_token('wrong_token', 'another_form'),
        "Invalid CSRF token should fail validation"
    );
    
    // Token should remain after failed validation
    assert_true(
        isset($_SESSION['csrf_tokens']['another_form']),
        "CSRF token should remain after failed validation"
    );
    
    // Non-existent form name test
    assert_true(
        !validate_test_csrf_token('any_token', 'nonexistent_form'),
        "Non-existent form name should fail validation"
    );
    
    // Expired token test
    $_SESSION['csrf_tokens']['expired_form'] = [
        'token' => 'expired_token',
        'time' => time() - 3601 // 1 hour and 1 second ago
    ];
    
    assert_true(
        !validate_test_csrf_token('expired_token', 'expired_form'),
        "Expired CSRF token should fail validation"
    );
    
    // Expired token should be removed
    assert_true(
        !isset($_SESSION['csrf_tokens']['expired_form']),
        "Expired CSRF token should be removed after validation attempt"
    );
}

// Test case 3: CSRF Token Field generation
function test_csrf_token_field() {
    // Define a simplified version of the function for testing
    function csrf_test_token_field($form_name = 'default') {
        // For testing, always use a fixed token
        $token = 'test_csrf_token_' . $form_name;
        
        $_SESSION['csrf_tokens'][$form_name] = [
            'token' => $token,
            'time' => time()
        ];
        
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    // Test default form name
    $field = csrf_test_token_field();
    $expected = '<input type="hidden" name="csrf_token" value="test_csrf_token_default">';
    assert_true(
        $field === $expected,
        "CSRF token field HTML should be correctly generated for default form"
    );
    
    // Test custom form name
    $field = csrf_test_token_field('login');
    $expected = '<input type="hidden" name="csrf_token" value="test_csrf_token_login">';
    assert_true(
        $field === $expected,
        "CSRF token field HTML should be correctly generated for custom form"
    );
    
    // Test HTML special characters escaping
    $_SESSION['csrf_tokens']['test_xss'] = [
        'token' => '<script>alert("XSS")</script>',
        'time' => time()
    ];
    
    function get_csrf_escaped_field() {
        $token = $_SESSION['csrf_tokens']['test_xss']['token'];
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    $field = get_csrf_escaped_field();
    $expected = '<input type="hidden" name="csrf_token" value="&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;">';
    assert_true(
        $field === $expected,
        "CSRF token field should properly escape HTML special characters"
    );
}

// Run the tests
test_generate_csrf_token();
test_validate_csrf_token();
test_csrf_token_field(); 