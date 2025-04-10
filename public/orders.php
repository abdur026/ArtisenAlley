<?php
session_start();
require_once '../config/db.php';
require_once '../includes/breadcrumb.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to view your orders.";
    header("Location: login.php");
    exit;
}

// Get user details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get user orders with pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) as total FROM orders WHERE user_id = ?";
$stmt = $conn->prepare($count_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$count_result = $stmt->get_result();
$total_orders = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $limit);

// Get the orders for the current page
$orders_query = "
    SELECT o.*, COUNT(oi.id) as item_count 
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id 
    WHERE o.user_id = ? 
    GROUP BY o.id 
    ORDER BY o.created_at DESC 
    LIMIT ?, ?
";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("iii", $user_id, $offset, $limit);
$stmt->execute();
$orders_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Orders - ArtisenAlley</title>
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

        .orders-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .orders-header {
            background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
            padding: 2rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .orders-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .orders-subtitle {
            margin-top: 0.5rem;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .orders-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .card-header {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .card-title {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .card-body {
            padding: 2rem;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
        }

        .table th, .table td {
            padding: 1rem;
            text-align: left;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .table tr {
            border-bottom: 1px solid #e0e0e0;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
        }

        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.85rem;
            font-weight: 600;
            text-align: center;
        }

        .bg-warning {
            background: #f1c40f;
            color: #fff;
        }

        .bg-info {
            background: #3498db;
            color: #fff;
        }

        .bg-primary {
            background: #9b59b6;
            color: #fff;
        }

        .bg-success {
            background: #2ecc71;
            color: #fff;
        }

        .bg-danger {
            background: #e74c3c;
            color: #fff;
        }

        .bg-secondary {
            background: #95a5a6;
            color: #fff;
        }

        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-sm {
            padding: 0.35rem 0.75rem;
            font-size: 0.85rem;
        }

        .alert {
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .alert i {
            margin-right: 0.5rem;
        }

        .alert-link {
            font-weight: 600;
            color: inherit;
            text-decoration: underline;
        }

        .pagination {
            display: flex;
            justify-content: center;
            list-style: none;
            padding: 0;
            margin: 2rem 0 0;
        }

        .page-item {
            margin: 0 0.25rem;
        }

        .page-link {
            display: block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: white;
            color: #3498db;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: #f8f9fa;
        }

        .page-item.active .page-link {
            background: #3498db;
            color: white;
        }

        .page-item.disabled .page-link {
            color: #95a5a6;
            pointer-events: none;
        }

        .back-to-profile {
            display: inline-block;
            margin-top: 1rem;
            color: #3498db;
            text-decoration: none;
        }

        .back-to-profile i {
            margin-right: 0.5rem;
        }

        @media (max-width: 768px) {
            .table {
                min-width: 650px;
            }
        }
    </style>
</head>
<body>
    <div class="orders-container">
        <?php
        // Generate breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'index.php'],
            ['name' => 'Profile', 'url' => 'profile.php'],
            ['name' => 'Orders']
        ];
        echo generate_breadcrumbs($breadcrumbs);
        ?>
        
        <div class="orders-header">
            <h1>Your Orders</h1>
            <div class="orders-subtitle">Track, view, and manage your purchases</div>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <div class="orders-card">
            <div class="card-header">
                <h2 class="card-title">Order History</h2>
            </div>
            <div class="card-body">
                <?php if ($orders_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $order['id']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo $order['item_count']; ?></td>
                                        <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                switch($order['status']) {
                                                    case 'pending': echo 'bg-warning'; break;
                                                    case 'processing': echo 'bg-info'; break;
                                                    case 'shipped': echo 'bg-primary'; break;
                                                    case 'delivered': echo 'bg-success'; break;
                                                    case 'cancelled': echo 'bg-danger'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                            ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="order_confirmation.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">View Details</a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($total_pages > 1): ?>
                        <ul class="pagination">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page-1; ?>" aria-label="Previous">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $page+1; ?>" aria-label="Next">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> You haven't placed any orders yet.
                        <a href="index.php" class="alert-link">Start shopping</a> to see your orders here.
                    </div>
                <?php endif; ?>
                
                <a href="profile.php" class="back-to-profile">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
        </div>
    </div>
</body>
</html> 