<?php
session_start();
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);

   
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $_SESSION['error'] = "Failed to upload image.";
            header("Location: add_product.php");
            exit;
        }
    } else {
        $filename = 'default_product.png';
    }

  
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "You must be logged in to add a product.";
        header("Location: login.php");
        exit;
    }
    
    
    $stmt = $conn->prepare("INSERT INTO products (user_id, name, description, price, category, stock, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $user_id = $_SESSION['user_id'];
    $stmt->bind_param("issdiss", $user_id, $name, $description, $price, $category, $stock, $filename);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product added successfully!";
        header("Location: index.php");
        exit;
    } else {
        $_SESSION['error'] = "Error adding product: " . $stmt->error;
        header("Location: add_product.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Add Product</title>
  <link rel="stylesheet" href="/src/main.css">
</head>
<body>
  <h1>Add Product</h1>
  <?php
    if(isset($_SESSION['error'])){
        echo "<p style='color:red'>".$_SESSION['error']."</p>";
        unset($_SESSION['error']);
    }
    if(isset($_SESSION['success'])){
        echo "<p style='color:green'>".$_SESSION['success']."</p>";
        unset($_SESSION['success']);
    }
  ?>
  <form action="add_product.php" method="POST" enctype="multipart/form-data">
    <label for="name">Product Name:</label>
    <input type="text" name="name" id="name" required>

    <label for="description">Description:</label>
    <textarea name="description" id="description" required></textarea>

    <label for="price">Price ($):</label>
    <input type="number" step="0.01" name="price" id="price" required>

    <label for="category">Category:</label>
    <input type="text" name="category" id="category" required>

    <label for="stock">Stock:</label>
    <input type="number" name="stock" id="stock" required>

    <label for="image">Product Image:</label>
    <input type="file" name="image" id="image">

    <button type="submit">Add Product</button>
  </form>
</body>
</html>