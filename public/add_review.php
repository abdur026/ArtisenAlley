<?php
session_start();
require_once '../config/db.php';

// Check if request is AJAX
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'You must be logged in to submit a review.']);
            exit;
        } else {
            $_SESSION['error'] = "You must be logged in to submit a review.";
            header("Location: login.php");
            exit;
        }
    }

    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $comment = htmlspecialchars(trim($_POST['comment']), ENT_QUOTES, 'UTF-8');

    if ($rating < 1 || $rating > 5) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Rating must be between 1 and 5.']);
            exit;
        } else {
            $_SESSION['error'] = "Rating must be between 1 and 5.";
            header("Location: product.php?id=" . $product_id);
            exit;
        }
    }

    $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiis", $user_id, $product_id, $rating, $comment);

    if ($stmt->execute()) {
        // Get the inserted review id
        $review_id = $conn->insert_id;
        
        // Fetch the new review with user details
        $review_stmt = $conn->prepare("SELECT r.*, u.name as reviewer_name, u.profile_image 
                                      FROM reviews r 
                                      JOIN users u ON r.user_id = u.id 
                                      WHERE r.id = ?");
        $review_stmt->bind_param("i", $review_id);
        $review_stmt->execute();
        $result = $review_stmt->get_result();
        $review = $result->fetch_assoc();
        
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'review' => [
                    'id' => $review['id'],
                    'rating' => $review['rating'],
                    'comment' => $review['comment'],
                    'created_at' => $review['created_at'],
                    'reviewer_name' => $review['reviewer_name'],
                    'profile_image' => $review['profile_image']
                ]
            ]);
            exit;
        } else {
            $_SESSION['success'] = "Review submitted successfully!";
            header("Location: product.php?id=" . $product_id);
            exit;
        }
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Error submitting review: ' . $stmt->error]);
            exit;
        } else {
            $_SESSION['error'] = "Error submitting review: " . $stmt->error;
            header("Location: product.php?id=" . $product_id);
            exit;
        }
    }
} else {
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid request method.']);
        exit;
    } else {
        header("Location: index.php");
        exit;
    }
}
?>