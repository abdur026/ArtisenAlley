<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to cancel an order.";
    header("Location: login.php");
    exit;
}

// Check if order ID is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    $_SESSION['error'] = "Invalid order ID.";
    header("Location: orders.php");
    exit;
}

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verify the order belongs to the user and is in pending status
    $stmt = $conn->prepare("
        SELECT * FROM orders 
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ii", $order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = "You cannot cancel this order. It may not exist, may not belong to you, or is already being processed.";
        header("Location: orders.php");
        exit;
    }

    // Update order status to cancelled
    $update_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $update_stmt->bind_param("i", $order_id);

    if ($update_stmt->execute()) {
        $_SESSION['success'] = "Order #" . $order_id . " has been cancelled successfully.";
    } else {
        $_SESSION['error'] = "Failed to cancel order: " . $update_stmt->error;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Error processing your request: " . $e->getMessage();
}

header("Location: orders.php");
exit; 