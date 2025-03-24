<?php
session_start();
require_once '../config/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Initialize search variables
$search_term = '';
$search_type = '';
$search_where = '';
$params = [];
$types = '';

// Process search request
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = $_GET['search'];
    $search_type = isset($_GET['search_type']) ? $_GET['search_type'] : 'name';
    
    // Build search query based on search type
    switch ($search_type) {
        case 'name':
            $search_where = "WHERE name LIKE ?";
            $params[] = "%$search_term%";
            $types .= 's';
            break;
        case 'email':
            $search_where = "WHERE email LIKE ?";
            $params[] = "%$search_term%";
            $types .= 's';
            break;
        case 'post':
            // Join with reviews table to search by post content
            $query = "SELECT DISTINCT u.id, u.name, u.email, u.role 
                     FROM users u 
                     JOIN reviews r ON u.id = r.user_id 
                     WHERE r.comment LIKE ?
                     ORDER BY u.created_at DESC";
            $stmt = $conn->prepare($query);
            $param = "%$search_term%";
            $stmt->bind_param('s', $param);
            $stmt->execute();
            $result = $stmt->get_result();
            break;
        default:
            $search_where = "WHERE name LIKE ?";
            $params[] = "%$search_term%";
            $types .= 's';
    }
}

// If result is not already set by a post search
if (!isset($result)) {
    // Build and execute the main query
    $query = "SELECT id, name, email, role FROM users " . 
             ($search_where ? $search_where : '') . 
             " ORDER BY created_at DESC";
    
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/src/main.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        h1, h2 {
            color: #2c3e50;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .admin-nav a {
            margin-left: 15px;
            text-decoration: none;
            color: #3498db;
            font-weight: 500;
        }
        
        .admin-search {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .search-form input, .search-form select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        .search-form input[type="text"] {
            flex-grow: 1;
            min-width: 200px;
        }
        
        .search-form button {
            background: #3498db;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .search-form button:hover {
            background: #2980b9;
        }
        
        .user-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }
        
        .user-table th, .user-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .user-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        
        .user-table tr:hover {
            background-color: #f5f5f5;
        }
        
        .action-link {
            text-decoration: none;
            color: #3498db;
            font-weight: 500;
        }
        
        .action-link:hover {
            text-decoration: underline;
        }
        
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-weight: 500;
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
        
        .admin-nav a.active {
            color: #2c3e50;
            font-weight: 700;
        }
        
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .product-thumbnail {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .product-table td {
            vertical-align: middle;
        }
        
        .delete-link {
            color: #e74c3c;
            margin-left: 10px;
        }
        
        .delete-link:hover {
            color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <nav class="admin-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin_dashboard.php?section=users" class="<?php echo !isset($_GET['section']) || $_GET['section'] === 'users' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="admin_dashboard.php?section=products" class="<?php echo isset($_GET['section']) && $_GET['section'] === 'products' ? 'active' : ''; ?>">
                <i class="fas fa-box"></i> Products
            </a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
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
    
    // Determine which section to display
    $section = isset($_GET['section']) ? $_GET['section'] : 'users';
    
    if ($section === 'users'):
    ?>
    
    <div class="admin-search">
        <h2><i class="fas fa-search"></i> Search Users</h2>
        <form class="search-form" method="GET" action="admin_dashboard.php">
            <input type="text" name="search" placeholder="Search term..." value="<?php echo htmlspecialchars($search_term); ?>">
            <select name="search_type">
                <option value="name" <?php echo $search_type === 'name' ? 'selected' : ''; ?>>By Name</option>
                <option value="email" <?php echo $search_type === 'email' ? 'selected' : ''; ?>>By Email</option>
                <option value="post" <?php echo $search_type === 'post' ? 'selected' : ''; ?>>By Post Content</option>
            </select>
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
    
    <h2><i class="fas fa-users"></i> Manage Users</h2>
    <table class="user-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($user = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <a href="toggle_user.php?id=<?php echo $user['id']; ?>" class="action-link">
                                    <?php echo ($user['role'] === 'disabled' ? 'Enable' : 'Disable'); ?>
                                </a>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php elseif ($section === 'products'): 
        // Get products from database
        $search_term = isset($_GET['product_search']) ? $_GET['product_search'] : '';
        
        if (!empty($search_term)) {
            $query = "SELECT * FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $search_param = "%$search_term%";
            $stmt->bind_param('ss', $search_param, $search_param);
            $stmt->execute();
            $products_result = $stmt->get_result();
        } else {
            $query = "SELECT * FROM products ORDER BY created_at DESC";
            $products_result = $conn->query($query);
        }
    ?>
    
    <div class="admin-search">
        <h2><i class="fas fa-search"></i> Search Products</h2>
        <form class="search-form" method="GET" action="admin_dashboard.php">
            <input type="hidden" name="section" value="products">
            <input type="text" name="product_search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>
    </div>
    
    <div class="actions-bar">
        <h2><i class="fas fa-box"></i> Manage Products</h2>
        <a href="add_product.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Product</a>
    </div>
    
    <table class="user-table product-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($products_result && $products_result->num_rows > 0): ?>
                <?php while ($product = $products_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['id']); ?></td>
                        <td>
                            <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" 
                                alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                class="product-thumbnail"
                                onerror="this.src='/assets/images/placeholder.jpg'">
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($product['stock']); ?></td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="action-link">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="delete_product.php?id=<?php echo $product['id']; ?>" 
                               class="action-link delete-link" 
                               onclick="return confirm('Are you sure you want to delete this product?');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No products found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php endif; ?>
</body>
</html>
