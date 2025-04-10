<?php
session_start();
require_once '../config/db.php';
require_once '../includes/breadcrumb.php';

// Check if order ID is provided and user is logged in
if (!isset($_GET['order_id']) || !isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Invalid order request or you're not logged in.";
    header("Location: index.php");
    exit;
}

$order_id = intval($_GET['order_id']);

// Get order details including shipping information
$stmt = $conn->prepare("
    SELECT o.*, s.*, u.name as customer_name, u.email as customer_email 
    FROM orders o
    JOIN shipping_details s ON o.shipping_id = s.id
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$orderResult = $stmt->get_result();

if ($orderResult->num_rows === 0) {
    $_SESSION['error'] = "Order not found.";
    header("Location: index.php");
    exit;
}

$order = $orderResult->fetch_assoc();

// Get order items with product details
$stmtItems = $conn->prepare("
    SELECT oi.*, p.name, p.image 
    FROM order_items oi 
    LEFT JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$items = $stmtItems->get_result();

// Format payment method for display
$paymentMethods = [
    'credit_card' => 'Credit Card',
    'paypal' => 'PayPal',
    'bank_transfer' => 'Bank Transfer'
];

$paymentMethod = $paymentMethods[$order['payment_method']] ?? $order['payment_method'];

// Format order status for display
$statusClasses = [
    'pending' => 'status-pending',
    'processing' => 'status-processing',
    'shipped' => 'status-shipped',
    'delivered' => 'status-delivered',
    'cancelled' => 'status-cancelled'
];

$statusClass = $statusClasses[$order['status']] ?? 'status-pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - ArtisenAlley</title>
    <link rel="stylesheet" href="/src/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
        }

        .confirmation-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .confirmation-header {
            background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
            padding: 2rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .confirmation-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .confirmation-success {
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .order-id {
            font-size: 1.2rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 50px;
            display: inline-block;
            margin-top: 1rem;
        }

        .confirmation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .info-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .info-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }

        .info-row {
            margin-bottom: 1rem;
        }

        .info-label {
            font-weight: 600;
            color: #7f8c8d;
            margin-bottom: 0.25rem;
        }

        .info-value {
            color: #2c3e50;
        }

        .order-status {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            margin-top: 0.5rem;
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

        .order-items-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-details {
            flex-grow: 1;
            padding: 0 1rem;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }

        .item-price, .item-quantity {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .item-total {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }

        .order-summary {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
            color: #2c3e50;
            padding-top: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn {
            display: inline-block;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-secondary {
            background: transparent;
            border: 2px solid #3498db;
            color: #3498db;
        }

        .btn-secondary:hover {
            background: #f0f9ff;
        }

        @media (max-width: 768px) {
            .confirmation-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <?php
        // Generate breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'index.php'],
            ['name' => 'Order Confirmation']
        ];
        echo generate_breadcrumbs($breadcrumbs);
        ?>
        
        <div class="confirmation-header">
            <i class="fas fa-check-circle fa-3x"></i>
            <div class="confirmation-success">Thank you for your order!</div>
            <h1>Order Confirmed</h1>
            <div class="order-id">Order #<?php echo htmlspecialchars($order['id']); ?></div>
        </div>
        
        <div class="confirmation-grid">
            <!-- Order Details -->
            <div class="info-card">
                <h2 class="info-title">Order Information</h2>
                
                <div class="info-row">
                    <div class="info-label">Order Date</div>
                    <div class="info-value"><?php echo date('F j, Y g:i A', strtotime($order['created_at'])); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Order Status</div>
                    <div class="info-value">
                        <span class="order-status <?php echo $statusClass; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">
                        <?php if ($order['payment_method'] === 'credit_card'): ?>
                            <i class="fas fa-credit-card"></i>
                        <?php elseif ($order['payment_method'] === 'paypal'): ?>
                            <i class="fab fa-paypal"></i>
                        <?php elseif ($order['payment_method'] === 'bank_transfer'): ?>
                            <i class="fas fa-university"></i>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($paymentMethod); ?>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Information -->
            <div class="info-card">
                <h2 class="info-title">Shipping Information</h2>
                
                <div class="info-row">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['full_name']); ?></div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Address</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($order['address']); ?><br>
                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['state']); ?> <?php echo htmlspecialchars($order['zip']); ?><br>
                        <?php echo htmlspecialchars($order['country']); ?>
                    </div>
                </div>
                
                <?php if (!empty($order['phone'])): ?>
                <div class="info-row">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['phone']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Order Items -->
        <div class="order-items-card">
            <h2 class="info-title">Order Items</h2>
            
            <?php while ($item = $items->fetch_assoc()): ?>
                <div class="order-item">
                    <img src="assets/images/<?php echo htmlspecialchars($item['image'] ?? 'placeholder.jpg'); ?>" 
                         alt="<?php echo htmlspecialchars($item['name'] ?? 'Product Removed'); ?>"
                         onerror="this.src='assets/images/placeholder.jpg'">
                    <div class="item-details">
                        <div class="item-name">
                            <?php echo htmlspecialchars($item['name'] ?? 'Product no longer available'); ?>
                            <?php if (!isset($item['name'])): ?>
                                <span style="color: #e74c3c; font-size: 0.9em;">(Product has been removed from catalog)</span>
                            <?php endif; ?>
                        </div>
                        <div class="item-price">$<?php echo number_format($item['price'], 2); ?> each</div>
                        <div class="item-quantity">Quantity: <?php echo htmlspecialchars($item['quantity']); ?></div>
                    </div>
                    <div class="item-total">$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Order Summary -->
        <div class="order-summary">
            <h2 class="info-title">Order Summary</h2>
            
            <div class="summary-row">
                <div>Subtotal</div>
                <div>$<?php echo number_format($order['total_price'], 2); ?></div>
            </div>
            <div class="summary-row">
                <div>Shipping</div>
                <div>Free</div>
            </div>
            <div class="summary-row">
                <div>Tax</div>
                <div>$0.00</div>
            </div>
            <div class="summary-row">
                <div>Total</div>
                <div>$<?php echo number_format($order['total_price'], 2); ?></div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Continue Shopping
            </a>
            <a href="profile.php" class="btn btn-secondary">
                <i class="fas fa-user"></i> View Profile
            </a>
        </div>
    </div>
</body>
</html>
