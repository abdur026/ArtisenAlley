<?php
/**
 * CSRF Protection Utilities
 * Provides functions for generating and validating CSRF tokens
 */

/**
 * Generate a CSRF token and store it in the session
 * 
 * @param string $form_name Optional form identifier for multiple forms
 * @return string The generated token
 */
function generate_csrf_token($form_name = 'default') {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_tokens'])) {
        $_SESSION['csrf_tokens'] = [];
    }
    
    // Generate a random token
    $token = bin2hex(random_bytes(32));
    
    // Store the token in the session
    $_SESSION['csrf_tokens'][$form_name] = [
        'token' => $token,
        'time' => time()
    ];
    
    return $token;
}

/**
 * Validate a CSRF token from a form submission
 * 
 * @param string $token The token to validate
 * @param string $form_name Optional form identifier
 * @return bool Whether the token is valid
 */
function validate_csrf_token($token, $form_name = 'default') {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    // Check if the token exists in session
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
    $valid = hash_equals($stored['token'], $token);
    
    // Single-use token - remove after validation
    if ($valid) {
        unset($_SESSION['csrf_tokens'][$form_name]);
    }
    
    return $valid;
}

/**
 * Output a CSRF token field for a form
 * 
 * @param string $form_name Optional form identifier
 * @return string HTML input field containing the token
 */
function csrf_token_field($form_name = 'default') {
    $token = generate_csrf_token($form_name);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?> 