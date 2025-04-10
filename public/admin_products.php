<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Handle product deletion
if (isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    
    // Get the image filename before deleting the product
    $stmt = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    
    // Delete the product
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    
    if ($stmt->execute()) {
        // Delete the image file if it's not the default
        if ($product['image'] && $product['image'] !== 'default_product.png' && $product['image'] !== 'placeholder.jpg') {
            $image_path = "../uploads/" . $product['image'];
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        $_SESSION['success'] = "Product deleted successfully!";
    } else {
        $_SESSION['error'] = "Error deleting product: " . $stmt->error;
    }
    
    header("Location: admin_products.php");
    exit;
}

// Get all products with pagination
$products_per_page = 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $products_per_page;

// Get total products count
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_pages = ceil($total_products / $products_per_page);

// Get products for current page
$products_query = "SELECT * FROM products ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("ii", $products_per_page, $offset);
$stmt->execute();
$products_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Product Management - Admin Dashboard</title>
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
        
        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .add-product-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .add-product-btn:hover {
            background-color: #27ae60;
            transform: translateY(-2px);
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .products-table th, .products-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .products-table th {
            background-color: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .products-table tr:last-child td {
            border-bottom: none;
        }
        
        .product-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
            text-decoration: none;
        }
        
        .product-name:hover {
            color: #3498db;
        }
        
        .product-actions {
            display: flex;
            gap: 10px;
        }
        
        .delete-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .delete-btn:hover {
            background-color: #c0392b;
        }
        
        .view-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .view-btn:hover {
            background-color: #2980b9;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 25px;
            gap: 5px;
        }
        
        .page-item {
            list-style: none;
        }
        
        .page-link {
            display: block;
            padding: 8px 12px;
            background-color: white;
            color: #3498db;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background-color: #e0e0e0;
        }
        
        .page-item.active .page-link {
            background-color: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Product Management</h1>
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

    <div class="action-bar">
        <h2>All Products (<?php echo $total_products; ?>)</h2>
        <a href="admin_add_product.php" class="add-product-btn">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>

    <div class="table-responsive">
        <table class="products-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Date Added</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($products_result && $products_result->num_rows > 0): ?>
                    <?php while ($product = $products_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-thumbnail"
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            </td>
                            <td>
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="product-name">
                                    <?php echo htmlspecialchars($product['name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($product['category']); ?></td>
                            <td>$<?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['stock']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($product['created_at'])); ?></td>
                            <td class="product-actions">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="view-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="delete-btn">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <span class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                    <a href="?page=<?php echo $i; ?>" class="page-link"><?php echo $i; ?></a>
                </span>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="page-link">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</body>
</html> 