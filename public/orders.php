<?php
session_start();
require_once '../config/db.php';
require_once '../includes/breadcrumb.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user information
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// View specific order if order_id is provided
$view_order_id = null;
$order_details = null;
$order_items = null;

if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $view_order_id = intval($_GET['view']);
    
    // Fetch order details
    $stmt = $conn->prepare("SELECT o.*, 
                            o.total_price as order_total,
                            sa.full_name, 
                            sa.address, 
                            sa.city, 
                            sa.state, 
                            sa.zip_code, 
                            sa.country, 
                            sa.phone
                            FROM orders o
                            LEFT JOIN shipping_addresses sa ON o.shipping_address_id = sa.id
                            WHERE o.id = ? AND o.user_id = ?");
    $stmt->bind_param("ii", $view_order_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
        
        // Fetch order items
        $stmt = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                                JOIN products p ON oi.product_id = p.id 
                                WHERE oi.order_id = ?");
        $stmt->bind_param("i", $view_order_id);
        $stmt->execute();
        $order_items = $stmt->get_result();
    }
} else {
    // Fetch all orders for this user
    $stmt = $conn->prepare("SELECT o.*, 
                           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
                           FROM orders o 
                           WHERE o.user_id = ? 
                           ORDER BY o.created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders_result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Artisan Alley</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --text-color: #2c3e50;
            --light-gray: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .navbar {
            background-color: #2c3e50;
            color: white;
            padding: 1rem 0;
        }
        
        .navbar-content {
            display: flex;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .navbar a:first-child {
            margin-left: 0;
        }
        
        .navbar a:hover {
            color: #3498db;
        }
        
        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 2rem;
            color: var(--text-color);
            margin: 0;
        }
        
        .back-link {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .back-link:hover {
            color: var(--secondary-color);
        }
        
        .order-box {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .order-box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            background: var(--light-gray);
            border-bottom: 1px solid #eee;
        }
        
        .order-id {
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-color);
        }
        
        .order-date {
            color: #777;
            font-size: 0.9rem;
        }
        
        .order-details {
            padding: 1.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .detail-group {
            display: flex;
            flex-direction: column;
        }
        
        .detail-label {
            font-size: 0.9rem;
            color: #777;
            margin-bottom: 0.5rem;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-processing {
            background: #e0f2fe;
            color: #075985;
        }
        
        .status-shipped {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-delivered {
            background: #d1fae5;
            color: #047857;
        }
        
        .status-cancelled {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        .order-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .action-btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .action-btn:hover {
            background: var(--secondary-color);
        }
        
        .action-btn i {
            margin-right: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: #666;
            margin-bottom: 1.5rem;
        }
        
        /* Order detail view styles */
        .order-view {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .order-view-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 1.5rem;
            background: var(--light-gray);
            border-bottom: 1px solid #eee;
        }
        
        .order-view-id {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--text-color);
            margin: 0;
        }
        
        .order-sections {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
            padding: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .order-sections {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        .order-section {
            background: var(--light-gray);
            border-radius: 8px;
            padding: 1.5rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--text-color);
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 0.75rem;
        }
        
        .detail-label {
            font-weight: 500;
            width: 40%;
            color: #777;
        }
        
        .detail-value {
            width: 60%;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th,
        .items-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .items-table th {
            background-color: var(--light-gray);
            font-weight: 600;
            color: var(--text-color);
        }
        
        .product-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .product-name {
            color: var(--text-color);
            font-weight: 500;
        }
        
        .table-footer {
            margin-top: 1rem;
            text-align: right;
            font-weight: 700;
            font-size: 1.1rem;
            color: var(--text-color);
        }
        
        .breadcrumb {
            display: flex;
            margin-bottom: 1.5rem;
            align-items: center;
        }
        
        .breadcrumb-item {
            font-size: 0.9rem;
            color: #777;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb-separator {
            margin: 0 0.5rem;
            color: #ccc;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php">Home</a>
            <div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php">Profile</a>
                    <a href="cart.php">
                        <i class="fas fa-shopping-cart"></i> Cart
                    </a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="orders-container">
        <?php
        // Generate breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'index.php'],
            ['name' => 'Profile', 'url' => 'profile.php'],
            ['name' => $view_order_id ? "Order #$view_order_id" : 'My Orders']
        ];
        echo generate_breadcrumbs($breadcrumbs);
        ?>
        
        <div class="page-header">
            <?php if ($view_order_id): ?>
                <h1 class="page-title">Order #<?php echo $view_order_id; ?></h1>
                <a href="orders.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            <?php else: ?>
                <h1 class="page-title">My Orders</h1>
            <?php endif; ?>
        </div>
        
        <?php if ($view_order_id && $order_details): ?>
            <!-- Order detail view -->
            <div class="order-view">
                <div class="order-view-header">
                    <div>
                        <h2 class="order-view-id">Order #<?php echo $view_order_id; ?></h2>
                        <p>Placed on <?php echo date('F j, Y, g:i a', strtotime($order_details['created_at'])); ?></p>
                    </div>
                    <span class="status-badge status-<?php echo strtolower($order_details['status']); ?>">
                        <?php echo ucfirst($order_details['status']); ?>
                    </span>
                </div>
                
                <div class="order-sections">
                    <div class="order-section">
                        <h3 class="section-title">Order Information</h3>
                        <div class="detail-row">
                            <div class="detail-label">Order Date:</div>
                            <div class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($order_details['created_at'])); ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value">
                                <span class="status-badge status-<?php echo strtolower($order_details['status']); ?>">
                                    <?php echo ucfirst($order_details['status']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Payment Method:</div>
                            <div class="detail-value">
                                <?php echo isset($order_details['payment_method']) ? ucfirst(str_replace('_', ' ', $order_details['payment_method'])) : 'Credit Card'; ?>
                            </div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Total Amount:</div>
                            <div class="detail-value">$<?php echo number_format($order_details['order_total'], 2); ?></div>
                        </div>
                    </div>
                    
                    <div class="order-section">
                        <h3 class="section-title">Shipping Information</h3>
                        <?php if (isset($order_details['full_name']) && $order_details['full_name']): ?>
                            <div class="detail-row">
                                <div class="detail-label">Full Name:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['full_name']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Address:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['address']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">City:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['city']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">State/Province:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['state']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">ZIP/Postal Code:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['zip_code']); ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Country:</div>
                                <div class="detail-value"><?php echo htmlspecialchars($order_details['country']); ?></div>
                            </div>
                            <?php if (isset($order_details['phone']) && $order_details['phone']): ?>
                                <div class="detail-row">
                                    <div class="detail-label">Phone:</div>
                                    <div class="detail-value"><?php echo htmlspecialchars($order_details['phone']); ?></div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>No shipping information available.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div style="padding: 1.5rem;">
                    <h3 class="section-title">Order Items</h3>
                    
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
                                $total = 0;
                                while ($item = $order_items->fetch_assoc()): 
                                    $itemTotal = $item['price'] * $item['quantity'];
                                    $total += $itemTotal;
                                ?>
                                <tr>
                                    <td>
                                        <div class="product-info">
                                            <img src="assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" 
                                                 class="product-image" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 onerror="this.src='assets/images/placeholder.jpg'">
                                            <div class="product-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        </div>
                                    </td>
                                    <td>$<?php echo number_format($item['price'], 2); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td>$<?php echo number_format($itemTotal, 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        
                        <div class="table-footer">
                            <div>Grand Total: $<?php echo number_format($total, 2); ?></div>
                        </div>
                    <?php else: ?>
                        <p>No items found for this order.</p>
                    <?php endif; ?>
                </div>
            </div>
            
        <?php elseif (!$view_order_id): ?>
            <!-- Orders list view -->
            <?php if ($orders_result && $orders_result->num_rows > 0): ?>
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="order-box">
                        <div class="order-header">
                            <div class="order-id">Order #<?php echo $order['id']; ?></div>
                            <div class="order-date"><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></div>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-group">
                                <div class="detail-label">Status</div>
                                <div class="detail-value">
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="detail-group">
                                <div class="detail-label">Items</div>
                                <div class="detail-value"><?php echo $order['item_count']; ?> item(s)</div>
                            </div>
                            
                            <div class="detail-group">
                                <div class="detail-label">Total</div>
                                <div class="detail-value">$<?php echo number_format($order['total_price'], 2); ?></div>
                            </div>
                            
                            <div class="detail-group">
                                <div class="detail-label">Payment Method</div>
                                <div class="detail-value">
                                    <?php echo isset($order['payment_method']) ? ucfirst(str_replace('_', ' ', $order['payment_method'])) : 'Credit Card'; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="order-footer">
                            <a href="orders.php?view=<?php echo $order['id']; ?>" class="action-btn">
                                <i class="fas fa-eye"></i> View Order Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <h2>No Orders Yet</h2>
                    <p>You haven't placed any orders yet. Start shopping to find unique handcrafted items!</p>
                    <a href="index.php" class="action-btn">
                        <i class="fas fa-shopping-cart"></i> Shop Now
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-exclamation-circle"></i>
                <h2>Order Not Found</h2>
                <p>The order you're looking for doesn't exist or you don't have permission to view it.</p>
                <a href="orders.php" class="action-btn">
                    <i class="fas fa-arrow-left"></i> Back to My Orders
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 