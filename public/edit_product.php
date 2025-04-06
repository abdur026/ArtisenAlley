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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8');
    $description = htmlspecialchars(trim($_POST['description']), ENT_QUOTES, 'UTF-8');
    $price = floatval($_POST['price']);
    $category = htmlspecialchars(trim($_POST['category']), ENT_QUOTES, 'UTF-8');
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    
    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../uploads/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Update with new image
            $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ?, image = ? WHERE id = ?");
            $stmt->bind_param("ssdssii", $name, $description, $price, $category, $stock, $filename, $product_id);
        } else {
            $_SESSION['error'] = "Failed to upload image.";
            header("Location: edit_product.php?id=" . $product_id);
            exit;
        }
    } else {
        // Update without changing image
        $stmt = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ? WHERE id = ?");
        $stmt->bind_param("ssdsii", $name, $description, $price, $category, $stock, $product_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product updated successfully!";
        header("Location: admin_dashboard.php?section=products");
        exit;
    } else {
        $_SESSION['error'] = "Error updating product: " . $stmt->error;
    }
}

// Get product data
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Product not found.";
    header("Location: admin_dashboard.php?section=products");
    exit;
}

$product = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin</title>
    <link rel="stylesheet" href="/src/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
        }
        
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .header-links a {
            margin-left: 15px;
            text-decoration: none;
            color: #3498db;
        }
        
        .form-container {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        input[type="file"] {
            padding: 0.5rem 0;
        }
        
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .current-image {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .current-image img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
            margin-right: 1rem;
        }
        
        .btn-container {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        
        .btn-secondary:hover {
            background: #d0d0d0;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    <div class="header-container">
        <h1><i class="fas fa-edit"></i> Edit Product</h1>
        <div class="header-links">
            <a href="admin_dashboard.php?section=products"><i class="fas fa-arrow-left"></i> Back to Products</a>
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
        </div>
    </div>
    
    <?php
    if (isset($_SESSION['error'])) {
        echo "<div class='alert alert-error'><i class='fas fa-exclamation-circle'></i> " . $_SESSION['error'] . "</div>";
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> " . $_SESSION['success'] . "</div>";
        unset($_SESSION['success']);
    }
    ?>
    
    <div class="form-container">
        <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="price">Price ($)</label>
                <input type="number" id="price" name="price" step="0.01" value="<?php echo htmlspecialchars($product['price']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="category">Category</label>
                <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" value="<?php echo htmlspecialchars($product['stock']); ?>" required>
            </div>
            
            <div class="form-group">
                <label>Current Image</label>
                <div class="current-image">
                    <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='/assets/images/placeholder.jpg'">
                    <span><?php echo htmlspecialchars($product['image']); ?></span>
                </div>
                
                <label for="image">Change Image (optional)</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <div class="btn-container">
                <a href="admin_dashboard.php?section=products" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</body>
</html> 