<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Process order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    validateCSRFToken($_POST['csrf_token']);
    
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($status, $allowed_statuses)) {
        $_SESSION['error'] = "Invalid status";
        header("Location: admin_orders.php");
        exit;
    }
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Order #$order_id status updated to " . ucfirst($status);
    } else {
        $_SESSION['error'] = "Error updating order status: " . $stmt->error;
    }
    
    header("Location: admin_orders.php");
    exit;
}

// Get order ID for viewing details
$view_order_id = null;
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view_order_id = intval($_GET['view']);
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filtering
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_customer = isset($_GET['customer']) ? $_GET['customer'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build WHERE clause for filtering
$where_clauses = ["1=1"]; // Always true condition
$params = [];
$types = "";

if (!empty($filter_status)) {
    $where_clauses[] = "o.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if (!empty($filter_customer)) {
    $where_clauses[] = "o.user_id = ?";
    $params[] = intval($filter_customer);
    $types .= "i";
}

if (!empty($search)) {
    $search_param = "%$search%";
    $where_clauses[] = "(o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = implode(" AND ", $where_clauses);

// Count total orders for pagination
$count_query = "SELECT COUNT(*) as total FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$count_row = $count_result->fetch_assoc();
$total_orders = $count_row['total'];
$total_pages = ceil($total_orders / $limit);

// Get orders
$query = "SELECT o.*, 
          u.name as customer_name, 
          u.email as customer_email,
          COUNT(oi.id) as item_count,
          sa.full_name, 
          sa.address, 
          sa.city, 
          sa.state, 
          sa.zip_code, 
          sa.country, 
          sa.phone
          FROM orders o
          JOIN users u ON o.user_id = u.id
          LEFT JOIN shipping_addresses sa ON o.shipping_address_id = sa.id
          LEFT JOIN order_items oi ON o.id = oi.order_id
          WHERE $where_clause
          GROUP BY o.id
          ORDER BY o.created_at DESC
          LIMIT ?, ?";

$stmt = $conn->prepare($query);
$bind_params = $params;
$bind_params[] = $offset;
$bind_params[] = $limit;
$stmt->bind_param($types . "ii", ...$bind_params);
$stmt->execute();
$orders_result = $stmt->get_result();

// Get order details if viewing a specific order
$order_details = null;
$order_items = null;
if ($view_order_id) {
    $stmt = $conn->prepare("
        SELECT o.*, 
        u.name as customer_name, 
        u.email as customer_email,
        sa.full_name, 
        sa.address, 
        sa.city, 
        sa.state, 
        sa.zip_code, 
        sa.country, 
        sa.phone
        FROM orders o
        JOIN users u ON o.user_id = u.id
        LEFT JOIN shipping_addresses sa ON o.shipping_address_id = sa.id
        WHERE o.id = ?
    ");
    $stmt->bind_param("i", $view_order_id);
    $stmt->execute();
    $order_details = $stmt->get_result()->fetch_assoc();
    
    if ($order_details) {
        $stmt = $conn->prepare("
            SELECT oi.*, p.name as product_name, p.image
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->bind_param("i", $view_order_id);
        $stmt->execute();
        $order_items = $stmt->get_result();
    }
}

// Get customers for filter dropdown
$stmt = $conn->prepare("
    SELECT DISTINCT u.id, u.name, u.email
    FROM users u
    JOIN orders o ON u.id = o.user_id
    ORDER BY u.name
");
$stmt->execute();
$customers_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Manage Orders - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="/src/main.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 1rem;
        }
        
        .sidebar-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .nav-menu {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .nav-item {
            margin-bottom: 0.5rem;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #ecf0f1;
            padding: 0.75rem 1rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: #34495e;
        }
        
        .nav-link.active {
            background: #3498db;
            color: white;
        }
        
        .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }
        
        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            margin: 0;
            font-size: 1.75rem;
            color: #2c3e50;
        }
        
        .filter-bar {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-label {
            font-weight: 500;
            white-space: nowrap;
        }
        
        .filter-select, .search-input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f9f9f9;
        }
        
        .search-input {
            flex: 1;
            min-width: 200px;
        }
        
        .apply-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .orders-grid {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .data-table th {
            background: #f9f9f9;
            font-weight: 600;
            color: #555;
        }
        
        .data-table tbody tr:hover {
            background: #f5f9ff;
        }
        
        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .badge-processing {
            background: #e0f2fe;
            color: #075985;
        }
        
        .badge-shipped {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-delivered {
            background: #d1fae5;
            color: #047857;
        }
        
        .badge-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .action-btn {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }
        
        .action-btn:hover {
            background: #2980b9;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }
        
        .page-link {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 4px;
            text-decoration: none;
            color: #555;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background: #f5f9ff;
            color: #3498db;
        }
        
        .page-link.active {
            background: #3498db;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        
        .order-details {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }
        
        .detail-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .detail-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
        }
        
        .back-link {
            color: #3498db;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .detail-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .detail-section {
            padding: 1.5rem;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .detail-section h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: #2c3e50;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.75rem;
        }
        
        .detail-label {
            font-weight: 600;
            width: 40%;
            color: #555;
        }
        
        .detail-value {
            width: 60%;
        }
        
        .status-form {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th, .items-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            background: #f9f9f9;
            font-weight: 600;
            color: #555;
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }
        
        .placeholder-message {
            text-align: center;
            padding: 2rem;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Dashboard</h2>
            </div>
            
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="admin_dashboard.php" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_products.php" class="nav-link">
                        <i class="fas fa-box"></i> Products
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_orders.php" class="nav-link active">
                        <i class="fas fa-shopping-cart"></i> Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_users.php" class="nav-link">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a href="admin_reports.php" class="nav-link">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i> Back to Store
                    </a>
                </li>
                <li class="nav-item">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <main class="main-content">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($view_order_id && $order_details): ?>
                <!-- Order Details View -->
                <div class="order-details">
                    <div class="detail-header">
                        <h2>Order #<?php echo $order_details['id']; ?></h2>
                        <a href="admin_orders.php" class="back-link">
                            <i class="fas fa-arrow-left"></i> Back to Orders
                        </a>
                    </div>
                    
                    <div class="detail-sections">
                        <div class="detail-section">
                            <h3><i class="fas fa-info-circle"></i> Order Information</h3>
                            <div class="detail-row">
                                <div class="detail-label">Order ID:</div>
                                <div class="detail-value">#<?php echo $order_details['id']; ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Date:</div>
                                <div class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($order_details['created_at'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Total Amount:</div>
                                <div class="detail-value">$<?php echo number_format($order_details['total_amount'], 2); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Payment Method:</div>
                                <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $order_details['payment_method'])); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Status:</div>
                                <div class="detail-value">
                                    <form method="POST" action="admin_orders.php" class="status-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                                        
                                        <select name="status" class="filter-select">
                                            <option value="pending" <?php echo $order_details['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order_details['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                            <option value="shipped" <?php echo $order_details['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                            <option value="delivered" <?php echo $order_details['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                            <option value="cancelled" <?php echo $order_details['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        
                                        <button type="submit" class="apply-btn">Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h3><i class="fas fa-user"></i> Customer Information</h3>
                            <div class="detail-row">
                                <div class="detail-label">Name:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['customer_name']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Email:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['customer_email']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Phone:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['phone'] ?? 'N/A'); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Shipping Address:</div>
                                <div class="detail-value">
                                    <?php if ($order_details['full_name']): ?>
                                        <?php echo htmlspecialchars($order_details['full_name']); ?><br>
                                        <?php echo htmlspecialchars($order_details['address']); ?><br>
                                        <?php echo htmlspecialchars($order_details['city']); ?>, 
                                        <?php echo htmlspecialchars($order_details['state']); ?> 
                                        <?php echo htmlspecialchars($order_details['zip_code']); ?><br>
                                        <?php echo htmlspecialchars($order_details['country']); ?>
                                    <?php else: ?>
                                        No shipping address provided
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <h3><i class="fas fa-box"></i> Order Items</h3>
                    <?php if ($order_items && $order_items->num_rows > 0): ?>
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $item_total = 0;
                                while ($item = $order_items->fetch_assoc()): 
                                    $total = $item['price'] * $item['quantity'];
                                    $item_total += $total;
                                ?>
                                    <tr>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                                     class="product-image"
                                                     onerror="this.src='assets/images/placeholder.jpg'">
                                                <div><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            </div>
                                        </td>
                                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td>$<?php echo number_format($total, 2); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: right; font-weight: bold;">Total:</td>
                                    <td style="font-weight: bold;">$<?php echo number_format($item_total, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    <?php else: ?>
                        <div class="placeholder-message">No items found for this order.</div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Orders List View -->
                <div class="page-header">
                    <h1 class="page-title">Manage Orders</h1>
                </div>
                
                <form action="admin_orders.php" method="GET" class="filter-bar">
                    <div class="filter-group">
                        <label for="status" class="filter-label">Status:</label>
                        <select name="status" id="status" class="filter-select">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $filter_status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="shipped" <?php echo $filter_status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="delivered" <?php echo $filter_status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="customer" class="filter-label">Customer:</label>
                        <select name="customer" id="customer" class="filter-select">
                            <option value="">All Customers</option>
                            <?php while ($customer = $customers_result->fetch_assoc()): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo $filter_customer == $customer['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?> (<?php echo htmlspecialchars($customer['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <input type="search" name="search" placeholder="Search orders..." class="search-input" value="<?php echo htmlspecialchars($search); ?>">
                    
                    <button type="submit" class="apply-btn">Apply Filters</button>
                </form>
                
                <div class="orders-grid">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($orders_result->num_rows > 0): ?>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo $order['item_count']; ?></td>
                                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="admin_orders.php?view=<?php echo $order['id']; ?>" class="action-btn" title="View Order">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="placeholder-message">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?>&status=<?php echo $filter_status; ?>&customer=<?php echo $filter_customer; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        
                        if ($start_page > 1): ?>
                            <a href="?page=1&status=<?php echo $filter_status; ?>&customer=<?php echo $filter_customer; ?>&search=<?php echo urlencode($search); ?>" class="page-link">1</a>
                            <?php if ($start_page > 2): ?>
                                <span>...</span>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&status=<?php echo $filter_status; ?>&customer=<?php echo $filter_customer; ?>&search=<?php echo urlencode($search); ?>" 
                               class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span>...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?>&status=<?php echo $filter_status; ?>&customer=<?php echo $filter_customer; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                <?php echo $total_pages; ?>
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo ($page + 1); ?>&status=<?php echo $filter_status; ?>&customer=<?php echo $filter_customer; ?>&search=<?php echo urlencode($search); ?>" class="page-link">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html> 