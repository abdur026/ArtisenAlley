<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Time range filter
$range = isset($_GET['range']) ? $_GET['range'] : 'week';
$valid_ranges = ['day', 'week', 'month', 'year'];
if (!in_array($range, $valid_ranges)) {
    $range = 'week';
}

// Range conditions
switch ($range) {
    case 'day':
        $time_condition = "AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
        $range_display = "Last 24 Hours";
        break;
    case 'week':
        $time_condition = "AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $range_display = "Last 7 Days";
        break;
    case 'month':
        $time_condition = "AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $range_display = "Last 30 Days";
        break;
    case 'year':
        $time_condition = "AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $range_display = "Last 12 Months";
        break;
    default:
        $time_condition = "AND r.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $range_display = "Last 7 Days";
}

// Get hot products (most reviewed in the selected time period)
$hot_products_query = "
    SELECT 
        p.id,
        p.name,
        p.image,
        COUNT(r.id) as review_count,
        AVG(r.rating) as avg_rating,
        MAX(r.created_at) as last_review
    FROM products p
    JOIN reviews r ON p.id = r.product_id
    WHERE 1=1 $time_condition
    GROUP BY p.id
    HAVING review_count > 0
    ORDER BY review_count DESC, last_review DESC
    LIMIT 10
";
$hot_products_result = $conn->query($hot_products_query);

// Get most active discussions (products with most recent reviews)
$active_discussions_query = "
    SELECT 
        p.id,
        p.name,
        p.image,
        COUNT(r.id) as total_reviews,
        COUNT(DISTINCT u.id) as unique_users,
        MAX(r.created_at) as last_activity
    FROM products p
    JOIN reviews r ON p.id = r.product_id
    JOIN users u ON r.user_id = u.id
    WHERE 1=1 $time_condition
    GROUP BY p.id
    ORDER BY last_activity DESC, total_reviews DESC
    LIMIT 10
";
$active_discussions_result = $conn->query($active_discussions_query);

// Get users with most reviews
$active_users_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.profile_image,
        COUNT(r.id) as review_count,
        MAX(r.created_at) as last_review
    FROM users u
    JOIN reviews r ON u.id = r.user_id
    WHERE 1=1 $time_condition
    GROUP BY u.id
    HAVING review_count > 0
    ORDER BY review_count DESC, last_review DESC
    LIMIT 10
";
$active_users_result = $conn->query($active_users_query);

// Recent reviews for quick moderation
$recent_reviews_query = "
    SELECT 
        r.id,
        r.comment,
        r.rating,
        r.created_at,
        u.id as user_id,
        u.name as user_name,
        p.id as product_id,
        p.name as product_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    ORDER BY r.created_at DESC
    LIMIT 15
";
$recent_reviews_result = $conn->query($recent_reviews_query);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hot Threads - Admin Dashboard</title>
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
        
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            background-color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filter-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        
        .filter-options {
            display: flex;
            gap: 10px;
        }
        
        .filter-link {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            color: #7f8c8d;
            background-color: #f8f9fa;
            transition: all 0.3s ease;
        }
        
        .filter-link:hover {
            background-color: #e9ecef;
        }
        
        .filter-link.active {
            background-color: #3498db;
            color: white;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .card-header {
            padding: 15px 20px;
            background-color: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .card-body {
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
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-primary {
            background-color: #3498db;
            color: white;
        }
        
        .badge-success {
            background-color: #2ecc71;
            color: white;
        }
        
        .badge-warning {
            background-color: #f39c12;
            color: white;
        }
        
        .badge-danger {
            background-color: #e74c3c;
            color: white;
        }
        
        .thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
        }
        
        .product-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .product-name {
            font-weight: 500;
            color: #2c3e50;
        }
        
        .rating-stars {
            display: flex;
            gap: 2px;
        }
        
        .user-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 500;
            color: #2c3e50;
            line-height: 1.2;
        }
        
        .user-email {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .timestamp {
            color: #7f8c8d;
            font-size: 12px;
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
        
        .review-body {
            padding: 15px;
        }
        
        .review-text {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .review-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
            margin-top: 10px;
        }
        
        .flex-row {
            display: flex;
            align-items: center;
            gap: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Hot Threads & Active Discussions</h1>
        <nav class="admin-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin_dashboard.php"><i class="fas fa-users"></i> Users</a>
            <a href="admin_analytics.php"><i class="fas fa-chart-line"></i> Analytics</a>
            <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="admin_hot_threads.php"><i class="fas fa-fire"></i> Hot Threads</a>
            <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <div class="filter-bar">
        <h2 class="filter-title">Showing Hot Threads: <?php echo $range_display; ?></h2>
        <div class="filter-options">
            <a href="?range=day" class="filter-link <?php echo ($range === 'day') ? 'active' : ''; ?>">
                <i class="far fa-clock"></i> 24 Hours
            </a>
            <a href="?range=week" class="filter-link <?php echo ($range === 'week') ? 'active' : ''; ?>">
                <i class="far fa-calendar-alt"></i> 7 Days
            </a>
            <a href="?range=month" class="filter-link <?php echo ($range === 'month') ? 'active' : ''; ?>">
                <i class="far fa-calendar-check"></i> 30 Days
            </a>
            <a href="?range=year" class="filter-link <?php echo ($range === 'year') ? 'active' : ''; ?>">
                <i class="far fa-calendar-plus"></i> 12 Months
            </a>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Hot Products -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Hot Products</h2>
                <span class="badge badge-primary"><?php echo $hot_products_result->num_rows; ?> products</span>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Reviews</th>
                            <th>Rating</th>
                            <th>Last Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($hot_products_result && $hot_products_result->num_rows > 0): ?>
                            <?php while ($product = $hot_products_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="product-item">
                                            <img src="/assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                                                alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                class="thumbnail"
                                                onerror="this.src='/assets/images/placeholder.jpg'">
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="product-name">
                                                <?php echo htmlspecialchars($product['name']); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?php echo $product['review_count']; ?></span>
                                    </td>
                                    <td>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star" style="color: <?php echo ($i <= round($product['avg_rating'])) ? '#f1c40f' : '#ecf0f1'; ?>"></i>
                                            <?php endfor; ?>
                                            <span style="margin-left: 5px;"><?php echo number_format($product['avg_rating'], 1); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="timestamp"><?php echo date('M j, Y, g:i a', strtotime($product['last_review'])); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No hot products found in this time period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Active Discussions -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Active Discussions</h2>
                <span class="badge badge-success"><?php echo $active_discussions_result->num_rows; ?> threads</span>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Reviews</th>
                            <th>Participants</th>
                            <th>Last Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($active_discussions_result && $active_discussions_result->num_rows > 0): ?>
                            <?php while ($discussion = $active_discussions_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="product-item">
                                            <img src="/assets/images/<?php echo htmlspecialchars($discussion['image']); ?>" 
                                                alt="<?php echo htmlspecialchars($discussion['name']); ?>" 
                                                class="thumbnail"
                                                onerror="this.src='/assets/images/placeholder.jpg'">
                                            <a href="product.php?id=<?php echo $discussion['id']; ?>" class="product-name">
                                                <?php echo htmlspecialchars($discussion['name']); ?>
                                            </a>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?php echo $discussion['total_reviews']; ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-success"><?php echo $discussion['unique_users']; ?> users</span>
                                    </td>
                                    <td>
                                        <span class="timestamp"><?php echo date('M j, Y, g:i a', strtotime($discussion['last_activity'])); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No active discussions found in this time period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Active Users -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Most Active Users</h2>
                <span class="badge badge-warning"><?php echo $active_users_result->num_rows; ?> users</span>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Reviews</th>
                            <th>Last Review</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($active_users_result && $active_users_result->num_rows > 0): ?>
                            <?php while ($user = $active_users_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="user-item">
                                            <img src="<?php echo $user['profile_image'] ? '../uploads/' . htmlspecialchars($user['profile_image']) : '/assets/images/default-avatar.png'; ?>" 
                                                alt="<?php echo htmlspecialchars($user['name']); ?>" 
                                                class="user-avatar">
                                            <div class="user-info">
                                                <a href="admin_user_view.php?id=<?php echo $user['id']; ?>" class="user-name">
                                                    <?php echo htmlspecialchars($user['name']); ?>
                                                </a>
                                                <span class="user-email"><?php echo htmlspecialchars($user['email']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-warning"><?php echo $user['review_count']; ?> reviews</span>
                                    </td>
                                    <td>
                                        <span class="timestamp"><?php echo date('M j, Y, g:i a', strtotime($user['last_review'])); ?></span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">No active users found in this time period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Reviews -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Recent Reviews</h2>
                <span class="badge badge-danger"><?php echo $recent_reviews_result->num_rows; ?> reviews</span>
            </div>
            <div class="card-body">
                <?php if ($recent_reviews_result && $recent_reviews_result->num_rows > 0): ?>
                    <?php while ($review = $recent_reviews_result->fetch_assoc()): ?>
                        <div class="review-card">
                            <div class="review-header">
                                <div class="flex-row">
                                    <a href="admin_user_view.php?id=<?php echo $review['user_id']; ?>" class="user-name">
                                        <?php echo htmlspecialchars($review['user_name']); ?>
                                    </a>
                                    on
                                    <a href="product.php?id=<?php echo $review['product_id']; ?>" class="product-name">
                                        <?php echo htmlspecialchars($review['product_name']); ?>
                                    </a>
                                </div>
                                <div class="rating-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star" style="color: <?php echo ($i <= $review['rating']) ? '#f1c40f' : '#ecf0f1'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <div class="review-body">
                                <p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                                <div class="review-footer">
                                    <span class="timestamp"><?php echo date('F j, Y, g:i a', strtotime($review['created_at'])); ?></span>
                                    <div class="flex-row">
                                        <a href="#" class="badge badge-warning" onclick="return confirm('Are you sure you want to flag this review?')">
                                            <i class="fas fa-flag"></i> Flag
                                        </a>
                                        <a href="#" class="badge badge-danger" onclick="return confirm('Are you sure you want to delete this review?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 30px;">
                        <i class="fas fa-comment-slash" style="font-size: 48px; color: #e0e0e0; margin-bottom: 15px;"></i>
                        <p style="color: #7f8c8d;">No reviews found.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 