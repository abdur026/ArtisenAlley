<?php
session_start();
require_once '../config/db.php';

if (!isset($_GET['order_id']) || !isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "No order specified or you're not logged in.";
    header("Location: index.php");
    exit;
}

$order_id = intval($_GET['order_id']);

// Use only total_price column since total_amount doesn't exist
$stmt = $conn->prepare("SELECT o.*, 
                      o.total_price as order_total
                    FROM orders o
                    WHERE o.id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    $_SESSION['error'] = "Order not found.";
    header("Location: index.php");
    exit;
}
$order = $orderResult->fetch_assoc();

$stmtItems = $conn->prepare("SELECT oi.*, p.name, p.image FROM order_items oi 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE oi.order_id = ?");
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
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
        
        .confirmation-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .success-message {
            background-color: #d4edda;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .success-icon {
            font-size: 3rem;
            color: #28a745;
            margin-right: 1.5rem;
        }
        
        .success-content h1 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.8rem;
        }
        
        .success-content p {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        
        .order-details {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .order-details h2 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.5rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-row {
            display: flex;
            border-bottom: 1px solid #f5f5f5;
            padding: 0.75rem 0;
        }
        
        .detail-label {
            font-weight: 600;
            width: 30%;
            color: #555;
        }
        
        .detail-value {
            width: 70%;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
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
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
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
        
        .items-table tr:last-child td {
            border-bottom: none;
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
        }
        
        .product-name {
            margin-left: 1rem;
        }
        
        .table-footer {
            margin-top: 1rem;
            text-align: right;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .continue-btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 1rem;
            transition: all 0.3s ease;
        }
        
        .continue-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .continue-btn i {
            margin-right: 0.5rem;
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

    <div class="confirmation-container">
        <div class="success-message">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="success-content">
                <h1>Thank You for Your Order!</h1>
                <p>Your order has been placed successfully. We'll start processing it right away.</p>
                <p>Order ID: <strong>#<?php echo htmlspecialchars($order['id']); ?></strong></p>
            </div>
        </div>
        
        <div class="order-details">
            <h2><i class="fas fa-info-circle"></i> Order Details</h2>
            
            <div class="detail-row">
                <div class="detail-label">Order Date:</div>
                <div class="detail-value"><?php echo date('F j, Y, g:i a', strtotime($order['created_at'])); ?></div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Order Status:</div>
                <div class="detail-value">
                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Payment Method:</div>
                <div class="detail-value">
                    <?php echo isset($order['payment_method']) ? ucfirst(str_replace('_', ' ', $order['payment_method'])) : 'Credit Card'; ?>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Total Amount:</div>
                <div class="detail-value">$<?php echo number_format($order['order_total'], 2); ?></div>
            </div>
        </div>
        
        <div class="order-details">
            <h2><i class="fas fa-box"></i> Order Items</h2>
            
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
                    while ($item = $itemsResult->fetch_assoc()): 
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
        </div>
        
        <a href="index.php" class="continue-btn">
            <i class="fas fa-arrow-left"></i> Continue Shopping
        </a>
    </div>
</body>
</html>
