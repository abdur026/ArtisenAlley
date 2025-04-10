<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID provided.";
    header("Location: admin_dashboard.php");
    exit;
}

$user_id = intval($_GET['id']);

// Get user details
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header("Location: admin_dashboard.php");
    exit;
}

// Get user's reviews
$stmt = $conn->prepare("SELECT r.*, p.name as product_name, p.image as product_image 
                        FROM reviews r 
                        JOIN products p ON r.product_id = p.id 
                        WHERE r.user_id = ? 
                        ORDER BY r.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$reviews_result = $stmt->get_result();

// Get user's orders
$stmt = $conn->prepare("SELECT o.*, COUNT(oi.id) as items_count, SUM(oi.price * oi.quantity) as total_value 
                       FROM orders o 
                       LEFT JOIN order_items oi ON o.id = oi.order_id 
                       WHERE o.user_id = ? 
                       GROUP BY o.id 
                       ORDER BY o.created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>User Details - Admin Dashboard</title>
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
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            color: #3498db;
            text-decoration: none;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .user-profile {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .user-sidebar {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
        }
        
        .user-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            display: block;
            border: 5px solid #f0f0f0;
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .user-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        
        .user-email {
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        
        .badge-admin {
            background-color: #3498db;
            color: white;
        }
        
        .badge-user {
            background-color: #2ecc71;
            color: white;
        }
        
        .badge-disabled {
            background-color: #e74c3c;
            color: white;
        }
        
        .user-stats {
            list-style: none;
            padding: 0;
            margin: 0;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
        }
        
        .user-stats li {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .user-actions {
            margin-top: 20px;
        }
        
        .action-btn {
            display: block;
            width: 100%;
            padding: 10px;
            text-align: center;
            background-color: #3498db;
            color: white;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 10px;
            transition: background-color 0.3s ease;
        }
        
        .action-btn:hover {
            background-color: #2980b9;
        }
        
        .action-btn.danger {
            background-color: #e74c3c;
        }
        
        .action-btn.danger:hover {
            background-color: #c0392b;
        }
        
        .content-section {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }
        
        .section-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .section-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .section-body {
            padding: 20px;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th, .data-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .data-table th {
            font-weight: 600;
            color: #2c3e50;
            background-color: #f8f9fa;
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .empty-state {
            text-align: center;
            padding: 30px;
            color: #7f8c8d;
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #e0e0e0;
        }
        
        .review-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
        }
        
        .review-header {
            padding: 15px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .review-product {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-image {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
        }
        
        .product-name {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .review-rating {
            display: flex;
            gap: 2px;
        }
        
        .review-rating i {
            color: #f1c40f;
        }
        
        .review-body {
            padding: 15px;
        }
        
        .review-text {
            margin: 0;
            color: #333;
        }
        
        .review-date {
            margin-top: 10px;
            font-size: 14px;
            color: #7f8c8d;
            text-align: right;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <nav class="admin-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin_dashboard.php"><i class="fas fa-users"></i> Users</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <a href="admin_dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to User List
    </a>

    <div class="user-profile">
        <div class="user-sidebar">
            <img src="<?php echo $user['profile_image'] ? '/uploads/' . htmlspecialchars($user['profile_image']) : '/assets/images/default-avatar.png'; ?>" 
                 alt="<?php echo htmlspecialchars($user['name']); ?>" 
                 class="user-avatar">
            
            <div class="user-info">
                <h1 class="user-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                <span class="badge badge-<?php echo $user['role']; ?>">
                    <?php echo htmlspecialchars(ucfirst($user['role'])); ?>
                </span>
            </div>
            
            <ul class="user-stats">
                <li>
                    <span>Member since</span>
                    <span><?php echo date('M j, Y', strtotime($user['created_at'])); ?></span>
                </li>
                <li>
                    <span>Reviews</span>
                    <span><?php echo $reviews_result->num_rows; ?></span>
                </li>
                <li>
                    <span>Orders</span>
                    <span><?php echo $orders_result->num_rows; ?></span>
                </li>
            </ul>
            
            <div class="user-actions">
            </div>
        </div>
        
        <div class="user-content">
            <!-- Reviews Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Reviews</h2>
                    <span><?php echo $reviews_result->num_rows; ?> total</span>
                </div>
                <div class="section-body">
                    <?php if ($reviews_result->num_rows > 0): ?>
                        <?php while ($review = $reviews_result->fetch_assoc()): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="review-product">
                                        <img src="/assets/images/<?php echo htmlspecialchars($review['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($review['product_name']); ?>" 
                                             class="product-image"
                                             onerror="this.src='/assets/images/placeholder.jpg'">
                                        <span class="product-name"><?php echo htmlspecialchars($review['product_name']); ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'filled' : 'empty'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-body">
                                    <p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                    <p class="review-date"><?php echo date('F j, Y, g:i a', strtotime($review['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-comment-slash"></i>
                            <p>This user hasn't written any reviews yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Orders Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2 class="section-title">Orders</h2>
                    <span><?php echo $orders_result->num_rows; ?> total</span>
                </div>
                <div class="section-body">
                    <?php if ($orders_result->num_rows > 0): ?>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo date('M j, Y', strtotime($order['created_at'])); ?></td>
                                        <td><?php echo $order['items_count']; ?></td>
                                        <td>$<?php echo number_format($order['total_value'], 2); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($order['status']); ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <p>This user hasn't placed any orders yet.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 