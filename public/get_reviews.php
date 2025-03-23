<?php
session_start();
require_once '../config/db.php';

header('Content-Type: application/json');

// Check if product_id is provided
if (!isset($_GET['product_id'])) {
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$product_id = intval($_GET['product_id']);

// Get the latest review timestamp if provided
$last_timestamp = isset($_GET['last_timestamp']) ? $_GET['last_timestamp'] : '0000-00-00 00:00:00';

// Fetch reviews for the product that are newer than the last timestamp
$stmt = $conn->prepare("SELECT r.*, u.name as reviewer_name, u.profile_image 
                       FROM reviews r 
                       JOIN users u ON r.user_id = u.id 
                       WHERE r.product_id = ? AND r.created_at > ? 
                       ORDER BY r.created_at DESC");
$stmt->bind_param("is", $product_id, $last_timestamp);
$stmt->execute();
$result = $stmt->get_result();

$new_reviews = [];
while ($review = $result->fetch_assoc()) {
    // Format the review data
    $new_reviews[] = [
        'id' => $review['id'],
        'rating' => $review['rating'],
        'comment' => $review['comment'],
        'created_at' => $review['created_at'],
        'reviewer_name' => $review['reviewer_name'],
        'profile_image' => $review['profile_image']
    ];
}

echo json_encode([
    'success' => true,
    'reviews' => $new_reviews
]);
?> 