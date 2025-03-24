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
    
    // Get column information from users table
    $columns_result = $conn->query("SHOW COLUMNS FROM users");
    $columns = [];
    while ($column = $columns_result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    
    // Check if necessary columns exist
    $has_name_column = in_array('name', $columns);
    $has_profile_image = in_array('profile_image', $columns);
    
    error_log("Update profile - name column: " . ($has_name_column ? 'Yes' : 'No') . 
             ", profile_image column: " . ($has_profile_image ? 'Yes' : 'No'));
    
    // Only process image if the profile_image column exists
    $filename = null;
    if ($has_profile_image && isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
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
    }
    
    // Build update query based on available columns and provided data
    $query_parts = [];
    $types = "";
    $params = [];
    
    if ($has_name_column) {
        // Using the single name field
        $query_parts[] = "name = ?";
        $types .= "s";
        $params[] = $name;
    } else {
        // Using separate first_name and last_name fields
        // Split the full name into first and last
        $name_parts = explode(' ', $name, 2);
        $first_name = $name_parts[0];
        $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
        
        $query_parts[] = "first_name = ?";
        $query_parts[] = "last_name = ?";
        $types .= "ss";
        $params[] = $first_name;
        $params[] = $last_name;
    }
    
    // Add profile_image to query if the column exists and a file was uploaded
    if ($has_profile_image && $filename) {
        $query_parts[] = "profile_image = ?";
        $types .= "s";
        $params[] = $filename;
    }
    
    // Add user_id parameter
    $types .= "i";
    $params[] = $user_id;
    
    // Build the final query
    $query = "UPDATE users SET " . implode(", ", $query_parts) . " WHERE id = ?";
    error_log("Update query: $query with parameters: " . json_encode($params));
    
    // Execute the query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $_SESSION['error'] = "Error preparing update query: " . $conn->error;
        header("Location: profile.php");
        exit;
    }
    
    // Dynamically bind parameters 
    $bind_params = array($types);
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array(array($stmt, 'bind_param'), $bind_params);
    
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
