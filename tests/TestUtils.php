<?php
/**
 * Test Utilities
 * Helper functions for testing
 */

/**
 * Create a mock product with specified data
 */
function createMockProduct($id = 1, $name = 'Test Product', $price = 10.99, $description = 'Test Description', $category = 'Test Category') {
    return [
        'id' => $id,
        'name' => $name,
        'price' => $price,
        'description' => $description,
        'category' => $category,
        'image' => 'test.jpg',
        'stock' => 10,
        'artisan_id' => 1,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Create a mock user with specified data
 */
function createMockUser($id = 1, $name = 'Test User', $email = 'test@example.com', $isAdmin = false) {
    return [
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'is_admin' => $isAdmin ? 1 : 0,
        'profile_image' => null,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Create a mock review with specified data
 */
function createMockReview($id = 1, $productId = 1, $userId = 1, $rating = 5, $comment = 'Test Review') {
    return [
        'id' => $id,
        'product_id' => $productId,
        'user_id' => $userId,
        'rating' => $rating,
        'comment' => $comment,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Create a mock order with specified data
 */
function createMockOrder($id = 1, $userId = 1, $total = 100.00, $status = 'pending') {
    return [
        'id' => $id,
        'user_id' => $userId,
        'total' => $total,
        'status' => $status,
        'created_at' => date('Y-m-d H:i:s')
    ];
}

/**
 * Create a mock order item with specified data
 */
function createMockOrderItem($id = 1, $orderId = 1, $productId = 1, $quantity = 1, $price = 10.99) {
    return [
        'id' => $id,
        'order_id' => $orderId,
        'product_id' => $productId,
        'quantity' => $quantity,
        'price' => $price
    ];
}

/**
 * Mock $_SESSION global for testing
 */
function mockSession($data = []) {
    $_SESSION = array_merge($_SESSION, $data);
    return $_SESSION;
}

/**
 * Mock $_POST global for testing
 */
function mockPost($data = []) {
    $_POST = $data;
    return $_POST;
}

/**
 * Mock $_GET global for testing
 */
function mockGet($data = []) {
    $_GET = $data;
    return $_GET;
}

/**
 * Reset mocked globals
 */
function resetMocks() {
    $_SESSION = [];
    $_POST = [];
    $_GET = [];
} 