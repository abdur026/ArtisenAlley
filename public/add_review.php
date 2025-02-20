<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to submit a review.";
        header("Location: login.php");
        exit;
    }

    // Retrieve and sanitize form data
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $comment = filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING);

    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $_SESSION['error'] = "Rating must be between 1 and 5.";
        header("Location: product.php?id=" . $product_id);
        exit;
    }

    // Insert the review into the database
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Review submitted successfully!";
    } else {
        $_SESSION['error'] = "Error submitting review: " . $stmt->error;
    }

    header("Location: product.php?id=" . $product_id);
    exit;
} else {
    header("Location: index.php");
    exit;
}
?>