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
    

    if ($filename) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, profile_image = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $filename, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $user_id);
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
