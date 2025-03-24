<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please log in to update your profile.";
        header("Location: login.php");
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    
    // Check if the users table has a name field or first_name/last_name fields
    $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'name'");
    $stmt->execute();
    $has_name_column = ($stmt->get_result()->num_rows > 0);
    
    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = basename($_FILES['profile_image']['name']);
        $targetFile = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
            $_SESSION['error'] = "Failed to upload profile image.";
            header("Location: profile.php");
            exit;
        }
    } else {
        $filename = null;
    }
    
    // Update user profile with appropriate fields based on database structure
    if ($has_name_column) {
        // Using the single name field
        if ($filename) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, profile_image = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $filename, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
            $stmt->bind_param("si", $name, $user_id);
        }
    } else {
        // Using separate first_name and last_name fields
        // Split the full name into first and last
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        if ($filename) {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, profile_image = ? WHERE id = ?");
            $stmt->bind_param("sssi", $first_name, $last_name, $filename, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ? WHERE id = ?");
            $stmt->bind_param("ssi", $first_name, $last_name, $user_id);
        }
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Profile updated successfully!";
    } else {
        $_SESSION['error'] = "Error updating profile: " . $stmt->error;
    }
    
    header("Location: profile.php");
    exit;
} else {
    header("Location: profile.php");
    exit;
}
?>
