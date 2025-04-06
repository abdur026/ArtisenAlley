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
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    
   
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedType = finfo_file($fileInfo, $_FILES['profile_image']['tmp_name']);
        finfo_close($fileInfo);
        
        if (!in_array($detectedType, $allowedTypes)) {
            $_SESSION['error'] = "Invalid file type. Only JPG, PNG and GIF are allowed.";
            header("Location: profile.php");
            exit;
        }
        
        // Check file size (limit to 5MB)
        if ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
            $_SESSION['error'] = "File is too large. Maximum size is 5MB.";
            header("Location: profile.php");
            exit;
        }
        
        // Generate secure filename
        $fileExtension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $newFilename = 'profile_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $targetFile = $uploadDir . $newFilename;
        
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
            $_SESSION['error'] = "Failed to upload profile image.";
            header("Location: profile.php");
            exit;
        }
        $filename = $newFilename;
    } else {
        // No new image uploaded
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
