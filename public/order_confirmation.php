<?php
session_start();
require_once '../config/db.php';

// Ensure an order ID is provided and the user is logged in
if (!isset($_GET['order_id']) || !isset($_SESSION['user_id'])) {
    echo "No order specified or you're not logged in.";
    exit;
}

$order_id = intval($_GET['order_id']);

// Retrieve order details ensuring it belongs to the logged-in user
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$orderResult = $stmt->get_result();
if ($orderResult->num_rows === 0) {
    echo "Order not found.";
    exit;
}
$order = $orderResult->fetch_assoc();

// Retrieve order items along with product names
$stmtItems = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="/src/main.css">
</head>
<body>
    <header>
        <a href="index.php">Home</a>
    </header>
    <h1>Order Confirmation</h1>
    <p>Thank you for your order! Your order ID is <strong><?php echo htmlspecialchars($order['id']); ?></strong>.</p>
    <p>Status: <strong><?php echo htmlspecialchars($order['status']); ?></strong></p>
    <p>Total: <strong>$<?php echo number_format($order['total_price'], 2); ?></strong></p>
    
    <h2>Order Items:</h2>
    <table border="1" cellspacing="0" cellpadding="8">
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $itemsResult->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?></td>
                <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                <td>$<?php echo number_format($item['price'], 2); ?></td>
                <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <p><a href="index.php">Continue Shopping</a></p>
</body>
</html>
