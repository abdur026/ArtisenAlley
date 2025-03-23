<?php
session_start();
require_once '../config/db.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "You don't have permission to access this page.";
    header("Location: index.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No product ID specified.";
    header("Location: admin_dashboard.php?section=products");
    exit;
}

$product_id = intval($_GET['id']);

// Get product info before deleting (for image removal)
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Product not found.";
    header("Location: admin_dashboard.php?section=products");
    exit;
}

$product = $result->fetch_assoc();

// Delete the product from database
$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    // Remove the product image if it exists and not the default
    if ($product['image'] && $product['image'] !== 'default_product.png') {
        $image_path = '../uploads/' . $product['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    $_SESSION['success'] = "Product deleted successfully!";
} else {
    $_SESSION['error'] = "Error deleting product: " . $stmt->error;
}

header("Location: admin_dashboard.php?section=products");
exit;
?> 