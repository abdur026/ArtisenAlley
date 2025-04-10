<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (isset($_POST['csrf_token']) && isset($_POST['order_id'])) {
        $order_id = intval($_POST['order_id']);
        $form_id = 'admin_order_form_' . $order_id;
        
        if (validate_csrf_token($_POST['csrf_token'], $form_id)) {
            $new_status = $_POST['status'];
            
            $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (in_array($new_status, $valid_statuses)) {
                $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_status, $order_id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Order #$order_id status updated to " . ucfirst($new_status);
                } else {
                    $_SESSION['error'] = "Error updating order status: " . $conn->error;
                }
            } else {
                $_SESSION['error'] = "Invalid status provided";
            }
        } else {
            $_SESSION['error'] = "Invalid security token";
        }
    } else {
        $_SESSION['error'] = "Missing required parameters";
    }
    
    header("Location: admin_orders.php");
    exit;
}

$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$conditions = [];
$params = [];
$types = '';

if (!empty($status_filter)) {
    $conditions[] = "o.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if (!empty($search)) {
    $conditions[] = "(o.id LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

$where_clause = empty($conditions) ? "" : "WHERE " . implode(" AND ", $conditions);

$count_query = "
    SELECT COUNT(*) as total 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    $where_clause
";

if (!empty($params)) {
    $stmt = $conn->prepare($count_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $count_result = $stmt->get_result();
} else {
    $count_result = $conn->query($count_query);
}

$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

$order_query = "
    SELECT 
        o.id,
        o.created_at,
        o.status,
        o.total_price,
        o.payment_method,
        u.id as user_id,
        u.name as user_name,
        u.email as user_email,
        (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count,
        s.address,
        s.city,
        s.state,
        s.country
    FROM orders o
    JOIN users u ON o.user_id = u.id
    LEFT JOIN shipping_details s ON o.shipping_id = s.id
    $where_clause
    ORDER BY o.created_at DESC
    LIMIT ?, ?
";

$query_params = $params;
$query_params[] = $offset;
$query_params[] = $limit;
$query_types = $types . 'ii';

$stmt = $conn->prepare($order_query);
$stmt->bind_param($query_types, ...$query_params);
$stmt->execute();
$orders_result = $stmt->get_result();

$stats_query = "
    SELECT 
        COUNT(*) as total_orders,
        SUM(total_price) as total_revenue,
        AVG(total_price) as avg_order_value,
        COUNT(DISTINCT user_id) as unique_customers,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
        SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
        SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    FROM orders
";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

foreach (['total_orders', 'total_revenue', 'avg_order_value', 'unique_customers', 
         'pending_orders', 'processing_orders', 'shipped_orders', 'delivered_orders', 
         'cancelled_orders'] as $key) {
    if (!isset($stats[$key])) {
        $stats[$key] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Dashboard</title>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .stat-card.pending { border-top: 4px solid #f39c12; }
        .stat-card.processing { border-top: 4px solid #3498db; }
        .stat-card.shipped { border-top: 4px solid #9b59b6; }
        .stat-card.delivered { border-top: 4px solid #2ecc71; }
        .stat-card.cancelled { border-top: 4px solid #e74c3c; }
        .stat-card.revenue { border-top: 4px solid #1abc9c; }
        
        .filter-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
        }
        
        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
            flex: 1;
        }
        
        .filter-form input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .filter-form select {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            background-color: white;
        }
        
        .filter-form button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .filter-form button:hover {
            background-color: #2980b9;
        }
        
        .order-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 12px;
            text-align: center;
        }
        
        .status-pending {
            background-color: #f39c12;
            color: white;
        }
        
        .status-processing {
            background-color: #3498db;
            color: white;
        }
        
        .status-shipped {
            background-color: #9b59b6;
            color: white;
        }
        
        .status-delivered {
            background-color: #2ecc71;
            color: white;
        }
        
        .status-cancelled {
            background-color: #e74c3c;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .orders-table th, .orders-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .orders-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .orders-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin: 30px 0;
        }
        
        .page-item {
            margin: 0 5px;
        }
        
        .page-link {
            display: block;
            padding: 8px 12px;
            border-radius: 5px;
            background-color: white;
            border: 1px solid #e0e0e0;
            color: #3498db;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background-color: #f8f9fa;
        }
        
        .page-item.active .page-link {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .page-item.disabled .page-link {
            color: #7f8c8d;
            pointer-events: none;
            background-color: #f8f9fa;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .payment-icon {
            font-size: 18px;
            margin-right: 5px;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .orders-table {
                min-width: 1000px;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Order Management</h1>
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

    <!-- Order Statistics -->
    <div class="stats-grid">
        <div class="stat-card revenue">
            <div class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?php echo number_format($stats['avg_order_value'], 2); ?></div>
            <div class="stat-label">Avg. Order Value</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['unique_customers']); ?></div>
            <div class="stat-label">Customers</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
            <div class="stat-label">Pending</div>
        </div>
        <div class="stat-card processing">
            <div class="stat-value"><?php echo number_format($stats['processing_orders']); ?></div>
            <div class="stat-label">Processing</div>
        </div>
        <div class="stat-card shipped">
            <div class="stat-value"><?php echo number_format($stats['shipped_orders']); ?></div>
            <div class="stat-label">Shipped</div>
        </div>
        <div class="stat-card delivered">
            <div class="stat-value"><?php echo number_format($stats['delivered_orders']); ?></div>
            <div class="stat-label">Delivered</div>
        </div>
        <div class="stat-card cancelled">
            <div class="stat-value"><?php echo number_format($stats['cancelled_orders']); ?></div>
            <div class="stat-label">Cancelled</div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
        <form class="filter-form" action="admin_orders.php" method="GET">
            <input type="text" name="search" placeholder="Search by order #, name, or email" value="<?php echo htmlspecialchars($search); ?>">
            <select name="status">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
            <button type="submit">
                <i class="fas fa-filter"></i> Filter
            </button>
            <?php if (!empty($search) || !empty($status_filter)): ?>
                <a href="admin_orders.php" style="padding: 10px; color: #7f8c8d;">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="table-responsive">
        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Shipping To</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders_result->num_rows > 0): ?>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['id']; ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($order['user_name']); ?></div>
                                <div style="font-size: 12px; color: #7f8c8d;"><?php echo htmlspecialchars($order['user_email']); ?></div>
                            </td>
                            <td><?php echo $order['item_count']; ?></td>
                            <td><strong>$<?php echo number_format($order['total_price'], 2); ?></strong></td>
                            <td>
                                <?php if ($order['payment_method'] === 'credit_card'): ?>
                                    <i class="fas fa-credit-card payment-icon"></i>
                                <?php elseif ($order['payment_method'] === 'paypal'): ?>
                                    <i class="fab fa-paypal payment-icon"></i>
                                <?php elseif ($order['payment_method'] === 'bank_transfer'): ?>
                                    <i class="fas fa-university payment-icon"></i>
                                <?php endif; ?>
                                <?php echo ucwords(str_replace('_', ' ', $order['payment_method'])); ?>
                            </td>
                            <td>
                                <?php if (!empty($order['city'])): ?>
                                    <?php echo htmlspecialchars($order['city']); ?>, 
                                    <?php echo htmlspecialchars($order['state']); ?><br>
                                    <?php echo htmlspecialchars($order['country']); ?>
                                <?php else: ?>
                                    <em>No shipping info</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="order-status status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <form action="admin_orders.php" method="POST" style="display: inline-block;">
                                    <?php 
                                    $form_id = 'admin_order_form_' . $order['id'];
                                    echo csrf_token_field($form_id); 
                                    ?>
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" style="padding: 5px; border-radius: 5px; border: 1px solid #ccc;">
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" style="background: #3498db; color: white; border: none; padding: 5px 10px; border-radius: 5px; cursor: pointer;">
                                        Update
                                    </button>
                                </form>
                                <a href="order_confirmation.php?order_id=<?php echo $order['id']; ?>" style="margin-left: 5px; padding: 5px 10px; background: #2c3e50; color: white; border-radius: 5px; text-decoration: none; display: inline-block; margin-top: 5px;">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 30px;">
                            <i class="fas fa-shopping-cart" style="font-size: 48px; color: #e0e0e0; margin-bottom: 15px;"></i>
                            <p>No orders found. <?php echo !empty($search) || !empty($status_filter) ? 'Try changing your filters.' : ''; ?></p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <ul class="pagination">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            </li>
            
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <?php 
                if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)) :
                ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                    <li class="page-item disabled">
                        <span class="page-link">...</span>
                    </li>
                <?php endif; ?>
            <?php endfor; ?>
            
            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    <?php endif; ?>
    
    <script>
        // JavaScript code was here
    </script>
</body>
</html> 