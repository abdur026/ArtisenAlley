<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Common categories for the dropdown
$categories = ['Ceramics', 'Woodwork', 'Textile Arts', 'Leather Goods', 'Glass Art', 'Jewelry', 'Home Decor', 'Furniture', 'Paper Goods', 'Art Prints'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validate_csrf_token($_POST['csrf_token'], 'add_product_form')) {
        $_SESSION['error'] = "Invalid form submission. Please try again.";
        header("Location: admin_add_product.php");
        exit;
    }
   
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $category = filter_input(INPUT_POST, 'category', FILTER_SANITIZE_STRING);
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $featured = isset($_POST['featured']) ? 1 : 0;

    // Validate required fields
    if (!$name || !$description || !$price || !$category || !$stock) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: admin_add_product.php");
        exit;
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $file_type = $_FILES['image']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $_SESSION['error'] = "Invalid file type. Please upload a JPEG, PNG, GIF, or WEBP image.";
            header("Location: admin_add_product.php");
            exit;
        }
        
        $file_size = $_FILES['image']['size'];
        if ($file_size > 5242880) { // 5MB
            $_SESSION['error'] = "File is too large. Maximum file size is 5MB.";
            header("Location: admin_add_product.php");
            exit;
        }
        
        // Generate unique filename
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = 'product_' . time() . '_' . uniqid() . '.' . $file_extension;
        
        // First, make sure the uploads directory exists
        $uploads_dir = '../uploads/';
        if (!file_exists($uploads_dir)) {
            mkdir($uploads_dir, 0777, true);
        }
        
        // Now try to upload the image
        $upload_path = $uploads_dir . $filename;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $_SESSION['error'] = "Failed to upload image.";
            header("Location: admin_add_product.php");
            exit;
        }
        
        // Also copy the image to assets/images for frontend display
        $assets_dir = 'assets/images/';
        if (!file_exists($assets_dir)) {
            mkdir($assets_dir, 0777, true);
        }
        
        $assets_path = $assets_dir . $filename;
        copy($upload_path, $assets_path);
    } else {
        $filename = 'placeholder.jpg';
    }

    // Insert the product into the database
    $stmt = $conn->prepare("INSERT INTO products (user_id, name, description, price, category, stock, image, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $user_id = $_SESSION['user_id']; // Admin user ID
    $stmt->bind_param("issdsisi", $user_id, $name, $description, $price, $category, $stock, $filename, $featured);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product added successfully!";
        header("Location: admin_products.php");
        exit;
    } else {
        $_SESSION['error'] = "Error adding product: " . $stmt->error;
        header("Location: admin_add_product.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Add New Product - Admin</title>
    <link rel="stylesheet" href="/src/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f7f9fc;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .admin-header h1 {
            margin: 0;
            color: #2c3e50;
        }
        
        .admin-nav {
            display: flex;
            gap: 15px;
        }
        
        .admin-nav a {
            text-decoration: none;
            color: #3498db;
            font-weight: 600;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .admin-nav a:hover {
            background-color: #3498db;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 20px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .product-form-container {
            background-color: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            max-width: 800px;
            margin: 0 auto;
        }
        
        .form-title {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 25px;
            font-size: 1.8rem;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            display: block;
            margin-bottom: 8px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #3498db;
            outline: none;
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .checkbox-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-lg {
            padding: 15px 30px;
            font-size: 1.1rem;
        }
        
        .image-preview {
            width: 100%;
            height: 200px;
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .preview-text {
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Add New Product</h1>
        <nav class="admin-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin_dashboard.php"><i class="fas fa-users"></i> Users</a>
            <a href="admin_analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
            <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="admin_products.php"><i class="fas fa-box"></i> Products</a>
            <a href="admin_hot_threads.php"><i class="fas fa-fire"></i> Hot Threads</a>
            <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <a href="admin_products.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>

    <div class="product-form-container">
        <h2 class="form-title">Product Information</h2>
        <form action="admin_add_product.php" method="POST" enctype="multipart/form-data">
            <?php echo csrf_token_field('add_product_form'); ?>
            
            <div class="form-group">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" name="name" id="name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="price" class="form-label">Price ($)</label>
                <input type="number" step="0.01" name="price" id="price" class="form-control" min="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="category" class="form-label">Category</label>
                <select name="category" id="category" class="form-control" required>
                    <option value="">Select a category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                    <option value="Other">Other</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="stock" class="form-label">Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="image" class="form-label">Product Image</label>
                <div class="image-preview" id="imagePreview">
                    <span class="preview-text">Image preview will appear here</span>
                </div>
                <input type="file" name="image" id="image" class="form-control" accept="image/*">
            </div>
            
            <div class="checkbox-group">
                <input type="checkbox" name="featured" id="featured" value="1">
                <label for="featured" class="checkbox-label">Feature this product on the homepage</label>
            </div>
            
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Add Product
            </button>
        </form>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(e.target.files[0]);
            } else {
                preview.innerHTML = '<span class="preview-text">Image preview will appear here</span>';
            }
        });
    </script>
</body>
</html> 