<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';
require_once '../includes/breadcrumb.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to view your orders.";
    header("Location: login.php");
    exit;
}

$error_message = "";
$orders_result = null;

try {
    // Get user's orders with improved error handling
    $stmt = $conn->prepare("
        SELECT o.id, o.total_price, o.status, o.created_at
        FROM orders o 
        WHERE o.user_id = ? 
        ORDER BY o.created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    
    if (!$stmt->execute()) {
        throw new Exception("Database execute error: " . $stmt->error);
    }
    
    $orders_result = $stmt->get_result();
    
    if (!$orders_result) {
        throw new Exception("Failed to get result set: " . $stmt->error);
    }
} catch (Exception $e) {
    $error_message = "Database error: " . $e->getMessage();
    // Log the actual error for debugging
    error_log("Orders query error for user ID " . $_SESSION['user_id'] . ": " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - Artisan Alley</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .orders-header {
            margin-bottom: 2rem;
        }

        .orders-header h1 {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .order-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .order-id {
            font-weight: 600;
            color: var(--primary-color);
        }

        .order-date {
            color: var(--text-muted);
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipped { background: #d4edda; color: #155724; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .order-details {
            display: grid;
            gap: 1rem;
        }

        .order-items {
            color: var(--text-color);
            line-height: 1.6;
        }

        .order-total {
            font-weight: 600;
            color: var(--primary-color);
            text-align: right;
            margin-top: 1rem;
        }

        .order-actions {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-color-dark);
        }

        .btn-secondary {
            background: var(--light-gray);
            color: var(--text-color);
        }

        .btn-secondary:hover {
            background: var(--gray);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--light-gray);
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            .order-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php 
    try {
        include '../includes/header.php'; 
    ?>

    <div class="orders-container">
        <?php
        // Generate breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'index.php'],
            ['name' => 'Profile', 'url' => 'profile.php'],
            ['name' => 'My Orders']
        ];
        try {
            echo generate_breadcrumbs($breadcrumbs);
        } catch (Exception $e) {
            error_log("Breadcrumb error: " . $e->getMessage());
            // No need to display this error to the user
        }
        ?>

        <div class="orders-header">
            <h1><i class="fas fa-shopping-bag"></i> My Orders</h1>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="error-message">
                <p><strong>Error:</strong> <?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($orders_result && $orders_result->num_rows > 0): ?>
            <?php while ($order = $orders_result->fetch_assoc()): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">Order #<?php echo $order['id']; ?></span>
                            <span class="order-date"><?php echo date('F j, Y', strtotime($order['created_at'])); ?></span>
                        </div>
                        <span class="order-status status-<?php echo $order['status']; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                    </div>
                    <div class="order-details">
                        <div class="order-items">
                            <?php 
                            try {
                                // Get items for this order
                                $items_stmt = $conn->prepare("
                                    SELECT p.name, oi.quantity 
                                    FROM order_items oi 
                                    LEFT JOIN products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ?
                                ");
                                $items_stmt->bind_param("i", $order['id']);
                                $items_stmt->execute();
                                $items_result = $items_stmt->get_result();
                                
                                $items_list = [];
                                while ($item = $items_result->fetch_assoc()) {
                                    // Add null check to prevent undefined index errors
                                    $name = isset($item['name']) ? $item['name'] : 'Unknown Product';
                                    $quantity = isset($item['quantity']) ? $item['quantity'] : 0;
                                    $items_list[] = htmlspecialchars($name . ' (' . $quantity . ')');
                                }
                                
                                if (empty($items_list)) {
                                    echo "<span>No items found for this order</span>";
                                } else {
                                    echo implode(', ', $items_list);
                                }
                            } catch (Exception $e) {
                                echo "<span class='text-danger'>Error loading order items</span>";
                                // Log the actual error for debugging
                                error_log("Order items error for order ID " . $order['id'] . ": " . $e->getMessage());
                            }
                            ?>
                        </div>
                        <div class="order-total">
                            Total: $<?php echo number_format($order['total_price'], 2); ?>
                        </div>
                        <div class="order-actions">
                            <a href="order_confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                            <?php if ($order['status'] === 'pending'): ?>
                                <a href="cancel_order.php?order_id=<?php echo $order['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel Order
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <?php if (empty($error_message)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-bag"></i>
                    <p>You haven't placed any orders yet.</p>
                    <a href="index.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php 
        include '../includes/footer.php';
    } catch (Exception $e) {
        // Log the error but show a user-friendly message
        error_log("Unexpected error in orders.php: " . $e->getMessage());
    ?>
        <div class="orders-container" style="text-align: center; padding: 50px;">
            <h2>We're sorry!</h2>
            <p>Something went wrong while loading your orders. Please try again later.</p>
            <a href="index.php" class="btn btn-primary">Return to Home Page</a>
        </div>
    <?php
        // Still try to include the footer
        try {
            include '../includes/footer.php';
        } catch (Exception $e) {
            // Just silently log this error
            error_log("Footer inclusion error: " . $e->getMessage());
        }
    }
    ?>
</body>
</html> 