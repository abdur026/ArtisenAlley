<?php
session_start();
require_once '../config/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access.";
    header("Location: index.php");
    exit;
}


if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No user specified.";
    header("Location: admin_dashboard.php");
    exit;
}

$user_id = intval($_GET['id']);


$stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND role <> 'admin'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    $_SESSION['error'] = "User not found or cannot be toggled.";
    header("Location: admin_dashboard.php");
    exit;
}

$user = $result->fetch_assoc();
$current_role = $user['role'];


$new_role = ($current_role === 'disabled') ? 'user' : 'disabled';


$stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
$stmt->bind_param("si", $new_role, $user_id);
if ($stmt->execute()) {
    $_SESSION['success'] = "User role updated successfully.";
} else {
    $_SESSION['error'] = "Error updating user role: " . $stmt->error;
}

header("Location: admin_dashboard.php");
exit;
?>
