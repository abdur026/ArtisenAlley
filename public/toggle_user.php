<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Check if user ID is provided and is numeric
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID provided.";
    header("Location: admin_dashboard.php");
    exit;
}

$user_id = intval($_GET['id']);

// Prevent admin from disabling themselves
if ($user_id == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot change your own role.";
    header("Location: admin_dashboard.php");
    exit;
}

// Get current role of the user
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "User not found.";
    header("Location: admin_dashboard.php");
    exit;
}

$user = $result->fetch_assoc();

// Prevent changing admin roles
if ($user['role'] === 'admin') {
    $_SESSION['error'] = "Admin roles cannot be changed.";
    header("Location: admin_dashboard.php");
    exit;
}

// Toggle between 'user' and 'disabled'
$new_role = ($user['role'] === 'disabled') ? 'user' : 'disabled';

// Update the user's role
$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->bind_param("si", $new_role, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "User status has been changed successfully.";
} else {
    $_SESSION['error'] = "Failed to change user status. " . $conn->error;
}

// Redirect back to admin dashboard or user view
if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'admin_user_view.php') !== false) {
    header("Location: admin_user_view.php?id=" . $user_id);
} else {
    header("Location: admin_dashboard.php");
}
exit;
?>
