<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Set default report type if not specified
$report_type = isset($_GET['type']) ? $_GET['type'] : 'users';
$valid_types = ['users', 'products', 'reviews', 'sales'];
if (!in_array($report_type, $valid_types)) {
    $report_type = 'users';
}

// Time range filter
$range = isset($_GET['range']) ? $_GET['range'] : 'month';
$valid_ranges = ['week', 'month', 'quarter', 'year', 'all'];
if (!in_array($range, $valid_ranges)) {
    $range = 'month';
}

// Define time condition based on selected range
switch ($range) {
    case 'week':
        $date_clause = "DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        $range_display = "Last 7 Days";
        break;
    case 'month':
        $date_clause = "DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $range_display = "Last 30 Days";
        break;
    case 'quarter':
        $date_clause = "DATE_SUB(NOW(), INTERVAL 3 MONTH)";
        $range_display = "Last 3 Months";
        break;
    case 'year':
        $date_clause = "DATE_SUB(NOW(), INTERVAL 1 YEAR)";
        $range_display = "Last 12 Months";
        break;
    case 'all':
        $date_clause = "";
        $range_display = "All Time";
        break;
    default:
        $date_clause = "DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $range_display = "Last 30 Days";
}

// Set time condition based on report type and date clause
if (!empty($date_clause)) {
    switch ($report_type) {
        case 'users':
            $time_condition = "AND u.created_at >= $date_clause";
            break;
        case 'products': 
            $time_condition = "AND p.created_at >= $date_clause";
            break;
        case 'reviews':
            $time_condition = "AND r.created_at >= $date_clause";
            break;
        case 'sales':
            $time_condition = "AND o.created_at >= $date_clause";
            break;
        default:
            $time_condition = "";
    }
} else {
    $time_condition = "";
}

// Additional filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Initialize query variables
$query = "";
$count_query = "";
$search_condition = "";

// Add search condition if search term is provided
if (!empty($search)) {
    switch ($report_type) {
        case 'users':
            $search_condition = "AND (name LIKE '%$search%' OR email LIKE '%$search%')";
            break;
        case 'products':
            $search_condition = "AND (name LIKE '%$search%' OR description LIKE '%$search%')";
            break;
        case 'reviews':
            $search_condition = "AND (r.comment LIKE '%$search%' OR u.name LIKE '%$search%' OR p.name LIKE '%$search%')";
            break;
        case 'sales':
            $search_condition = "AND (o.id LIKE '%$search%' OR u.name LIKE '%$search%')";
            break;
    }
}

// Sort order
$sort_order = "";
switch ($sort) {
    case 'oldest':
        $sort_order = "ORDER BY created_at ASC";
        break;
    case 'name_asc':
        $sort_order = "ORDER BY name ASC";
        break;
    case 'name_desc':
        $sort_order = "ORDER BY name DESC";
        break;
    case 'price_asc':
        $sort_order = "ORDER BY price ASC";
        break;
    case 'price_desc':
        $sort_order = "ORDER BY price DESC";
        break;
    case 'rating_asc':
        $sort_order = "ORDER BY avg_rating ASC";
        break;
    case 'rating_desc':
        $sort_order = "ORDER BY avg_rating DESC";
        break;
    case 'amount_asc':
        $sort_order = "ORDER BY total_price ASC";
        break;
    case 'amount_desc':
        $sort_order = "ORDER BY total_price DESC";
        break;
    default:
        $sort_order = "ORDER BY created_at DESC";
}

// Build query based on report type
switch ($report_type) {
    case 'users':
        $count_query = "SELECT COUNT(*) as total FROM users u WHERE 1=1 $time_condition $search_condition";
        $query = "
            SELECT 
                u.id, 
                u.name, 
                u.email, 
                u.role, 
                u.created_at,
                (SELECT COUNT(*) FROM reviews WHERE user_id = u.id) as review_count,
                (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count
            FROM users u
            WHERE 1=1 $time_condition $search_condition
            $sort_order
            LIMIT $offset, $limit
        ";
        break;
        
    case 'products':
        $count_query = "SELECT COUNT(*) as total FROM products p WHERE 1=1 $time_condition $search_condition";
        $query = "
            SELECT 
                p.id, 
                p.name, 
                p.price, 
                p.created_at, 
                p.image, 
                (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id) as review_count,
                (SELECT AVG(rating) FROM reviews r WHERE r.product_id = p.id) as avg_rating,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.product_id = p.id) as order_count
            FROM products p
            WHERE 1=1 $time_condition $search_condition
            $sort_order
            LIMIT $offset, $limit
        ";
        break;
        
    case 'reviews':
        $count_query = "
            SELECT COUNT(*) as total 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN products p ON r.product_id = p.id
            WHERE 1=1 $time_condition $search_condition
        ";
        $query = "
            SELECT 
                r.id,
                r.rating,
                r.comment,
                r.created_at,
                u.id as user_id,
                u.name as user_name,
                p.id as product_id,
                p.name as product_name,
                p.image as product_image
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            JOIN products p ON r.product_id = p.id
            WHERE 1=1 $time_condition $search_condition
            $sort_order
            LIMIT $offset, $limit
        ";
        break;
        
    case 'sales':
        $count_query = "
            SELECT COUNT(*) as total 
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE 1=1 $time_condition $search_condition
        ";
        $query = "
            SELECT 
                o.id,
                o.created_at,
                o.status,
                o.total_price,
                u.id as user_id,
                u.name as user_name,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE 1=1 $time_condition $search_condition
            $sort_order
            LIMIT $offset, $limit
        ";
        break;
}

// Execute count query
$count_result = $conn->query($count_query);
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// Execute data query
$result = $conn->query($query);

// Get summary data for report type
switch ($report_type) {
    case 'users':
        $summary_query = "
            SELECT 
                COUNT(*) as total_users,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
                SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
                SUM(CASE WHEN role = 'disabled' THEN 1 ELSE 0 END) as disabled_count,
                COUNT(CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users
            FROM users u
        ";
        $summary_result = $conn->query($summary_query);
        $summary = $summary_result->fetch_assoc();
        break;
        
    case 'products':
        $summary_query = "
            SELECT 
                COUNT(*) as total_products,
                AVG(price) as avg_price,
                MIN(price) as min_price,
                MAX(price) as max_price,
                COUNT(CASE WHEN p.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_products
            FROM products p
        ";
        $summary_result = $conn->query($summary_query);
        $summary = $summary_result->fetch_assoc();
        
        // Get review stats
        $review_stats_query = "
            SELECT 
                AVG(r.rating) as avg_rating,
                COUNT(r.id) as total_reviews
            FROM reviews r
        ";
        $review_stats_result = $conn->query($review_stats_query);
        $review_stats = $review_stats_result->fetch_assoc();
        $summary = array_merge($summary, $review_stats);
        break;
        
    case 'reviews':
        $summary_query = "
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(DISTINCT product_id) as reviewed_products,
                COUNT(DISTINCT user_id) as reviewers,
                COUNT(CASE WHEN r.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_reviews
            FROM reviews r
        ";
        $summary_result = $conn->query($summary_query);
        $summary = $summary_result->fetch_assoc();
        
        // Get rating distribution
        $rating_query = "
            SELECT 
                rating,
                COUNT(*) as count
            FROM reviews
            GROUP BY rating
            ORDER BY rating
        ";
        $rating_result = $conn->query($rating_query);
        $rating_distribution = [];
        while ($row = $rating_result->fetch_assoc()) {
            $rating_distribution[$row['rating']] = $row['count'];
        }
        $summary['rating_distribution'] = $rating_distribution;
        break;
        
    case 'sales':
        $summary_query = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(total_price) as total_revenue,
                AVG(total_price) as avg_order_value,
                COUNT(DISTINCT user_id) as customers,
                COUNT(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_orders,
                SUM(CASE WHEN o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN total_price ELSE 0 END) as new_revenue
            FROM orders o
        ";
        $summary_result = $conn->query($summary_query);
        $summary = $summary_result->fetch_assoc();
        
        // Get status breakdown
        $status_query = "
            SELECT 
                status,
                COUNT(*) as count
            FROM orders
            GROUP BY status
        ";
        $status_result = $conn->query($status_query);
        $status_breakdown = [];
        while ($row = $status_result->fetch_assoc()) {
            $status_breakdown[$row['status']] = $row['count'];
        }
        $summary['status_breakdown'] = $status_breakdown;
        break;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Reports - ArtisenAlley</title>
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
        
        .report-tabs {
            display: flex;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            font-weight: 600;
            color: #7f8c8d;
            position: relative;
            text-decoration: none;
        }
        
        .tab:hover {
            color: #3498db;
        }
        
        .tab.active {
            color: #3498db;
        }
        
        .tab.active::after {
            content: '';
            display: block;
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background-color: #3498db;
        }
        
        .filter-bar {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .filter-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .filter-input {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
        }
        
        .filter-input:focus {
            border-color: #3498db;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            background-color: white;
        }
        
        .filter-button {
            padding: 8px 15px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .filter-button:hover {
            background-color: #2980b9;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .summary-card {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .card-value {
            font-size: 24px;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 5px;
        }
        
        .card-label {
            font-size: 14px;
            color: #7f8c8d;
            margin: 0;
        }
        
        .results-table {
            width: 100%;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
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
        
        .data-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        
        .page-link {
            display: inline-block;
            padding: 8px 12px;
            background-color: white;
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            color: #3498db;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background-color: #f8f9fa;
        }
        
        .page-link.active {
            background-color: #3498db;
            color: white;
            border-color: #3498db;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
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
        
        .badge-pending {
            background-color: #f39c12;
            color: white;
        }
        
        .badge-completed {
            background-color: #2ecc71;
            color: white;
        }
        
        .badge-shipped {
            background-color: #3498db;
            color: white;
        }
        
        .badge-cancelled {
            background-color: #e74c3c;
            color: white;
        }
        
        .user-item, .product-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .thumbnail {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            object-fit: cover;
        }
        
        .export-btn {
            padding: 8px 15px;
            background-color: #2ecc71;
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.3s ease;
        }
        
        .export-btn:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Admin Reports</h1>
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

    <div class="report-tabs">
        <a href="?type=users<?php echo !empty($range) ? "&range=$range" : ""; ?>" class="tab <?php echo ($report_type === 'users') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i> Users
        </a>
        <a href="?type=products<?php echo !empty($range) ? "&range=$range" : ""; ?>" class="tab <?php echo ($report_type === 'products') ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="?type=reviews<?php echo !empty($range) ? "&range=$range" : ""; ?>" class="tab <?php echo ($report_type === 'reviews') ? 'active' : ''; ?>">
            <i class="fas fa-comment"></i> Reviews
        </a>
        <a href="?type=sales<?php echo !empty($range) ? "&range=$range" : ""; ?>" class="tab <?php echo ($report_type === 'sales') ? 'active' : ''; ?>">
            <i class="fas fa-dollar-sign"></i> Sales
        </a>
    </div>

    <div class="filter-bar">
        <form class="filter-form" method="GET" action="">
            <input type="hidden" name="type" value="<?php echo $report_type; ?>">
            
            <input type="text" name="search" class="filter-input" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="range" class="filter-select">
                <option value="week" <?php echo ($range === 'week') ? 'selected' : ''; ?>>Last 7 Days</option>
                <option value="month" <?php echo ($range === 'month') ? 'selected' : ''; ?>>Last 30 Days</option>
                <option value="quarter" <?php echo ($range === 'quarter') ? 'selected' : ''; ?>>Last 3 Months</option>
                <option value="year" <?php echo ($range === 'year') ? 'selected' : ''; ?>>Last 12 Months</option>
                <option value="all" <?php echo ($range === 'all') ? 'selected' : ''; ?>>All Time</option>
            </select>
            
            <select name="sort" class="filter-select">
                <?php if ($report_type === 'users'): ?>
                    <option value="latest" <?php echo ($sort === 'latest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="name_asc" <?php echo ($sort === 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="name_desc" <?php echo ($sort === 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                <?php elseif ($report_type === 'products'): ?>
                    <option value="latest" <?php echo ($sort === 'latest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="name_asc" <?php echo ($sort === 'name_asc') ? 'selected' : ''; ?>>Name (A-Z)</option>
                    <option value="name_desc" <?php echo ($sort === 'name_desc') ? 'selected' : ''; ?>>Name (Z-A)</option>
                    <option value="price_asc" <?php echo ($sort === 'price_asc') ? 'selected' : ''; ?>>Price (Low to High)</option>
                    <option value="price_desc" <?php echo ($sort === 'price_desc') ? 'selected' : ''; ?>>Price (High to Low)</option>
                    <option value="rating_desc" <?php echo ($sort === 'rating_desc') ? 'selected' : ''; ?>>Rating (High to Low)</option>
                    <option value="rating_asc" <?php echo ($sort === 'rating_asc') ? 'selected' : ''; ?>>Rating (Low to High)</option>
                <?php elseif ($report_type === 'reviews'): ?>
                    <option value="latest" <?php echo ($sort === 'latest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="rating_desc" <?php echo ($sort === 'rating_desc') ? 'selected' : ''; ?>>Rating (High to Low)</option>
                    <option value="rating_asc" <?php echo ($sort === 'rating_asc') ? 'selected' : ''; ?>>Rating (Low to High)</option>
                <?php elseif ($report_type === 'sales'): ?>
                    <option value="latest" <?php echo ($sort === 'latest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?php echo ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="amount_desc" <?php echo ($sort === 'amount_desc') ? 'selected' : ''; ?>>Amount (High to Low)</option>
                    <option value="amount_asc" <?php echo ($sort === 'amount_asc') ? 'selected' : ''; ?>>Amount (Low to High)</option>
                <?php endif; ?>
            </select>
            
            <button type="submit" class="filter-button">
                <i class="fas fa-filter"></i> Filter
            </button>
        </form>
    </div>

    <!-- Summary Cards -->
    <div class="summary-cards">
        <?php if ($report_type === 'users'): ?>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['total_users']); ?></div>
                <p class="card-label">Total Users</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['new_users']); ?></div>
                <p class="card-label">New Users (30 days)</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['admin_count']); ?></div>
                <p class="card-label">Administrators</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['user_count']); ?></div>
                <p class="card-label">Active Users</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['disabled_count']); ?></div>
                <p class="card-label">Disabled Users</p>
            </div>
        <?php elseif ($report_type === 'products'): ?>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['total_products']); ?></div>
                <p class="card-label">Total Products</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['new_products']); ?></div>
                <p class="card-label">New Products (30 days)</p>
            </div>
            <div class="summary-card">
                <div class="card-value">$<?php echo number_format($summary['avg_price'], 2); ?></div>
                <p class="card-label">Average Price</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['total_reviews']); ?></div>
                <p class="card-label">Total Reviews</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['avg_rating'], 1); ?></div>
                <p class="card-label">Average Rating</p>
            </div>
        <?php elseif ($report_type === 'reviews'): ?>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['total_reviews']); ?></div>
                <p class="card-label">Total Reviews</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['new_reviews']); ?></div>
                <p class="card-label">New Reviews (30 days)</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['avg_rating'], 1); ?></div>
                <p class="card-label">Average Rating</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['reviewed_products']); ?></div>
                <p class="card-label">Products Reviewed</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['reviewers']); ?></div>
                <p class="card-label">Unique Reviewers</p>
            </div>
        <?php elseif ($report_type === 'sales'): ?>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['total_orders']); ?></div>
                <p class="card-label">Total Orders</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['new_orders']); ?></div>
                <p class="card-label">New Orders (30 days)</p>
            </div>
            <div class="summary-card">
                <div class="card-value">$<?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></div>
                <p class="card-label">Total Revenue</p>
            </div>
            <div class="summary-card">
                <div class="card-value">$<?php echo number_format($summary['avg_order_value'] ?? 0, 2); ?></div>
                <p class="card-label">Average Order Value</p>
            </div>
            <div class="summary-card">
                <div class="card-value"><?php echo number_format($summary['customers']); ?></div>
                <p class="card-label">Unique Customers</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Results Table -->
    <div class="results-table">
        <table class="data-table">
            <?php if ($report_type === 'users'): ?>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Join Date</th>
                        <th>Reviews</th>
                        <th>Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $row['role']; ?>">
                                        <?php echo ucfirst($row['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                <td><?php echo $row['review_count']; ?></td>
                                <td><?php echo $row['order_count']; ?></td>
                                <td>
                                    <a href="admin_user_view.php?id=<?php echo $row['id']; ?>" class="badge badge-admin">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            <?php elseif ($report_type === 'products'): ?>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Added Date</th>
                        <th>Reviews</th>
                        <th>Rating</th>
                        <th>Orders</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <div class="product-item">
                                        <img src="/assets/images/<?php echo htmlspecialchars($row['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                             class="thumbnail"
                                             onerror="this.src='/assets/images/placeholder.jpg'">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </div>
                                </td>
                                <td>$<?php echo number_format($row['price'], 2); ?></td>
                                <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                <td><?php echo $row['review_count']; ?></td>
                                <td>
                                    <?php if ($row['avg_rating']): ?>
                                        <div style="display: flex; align-items: center;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star" style="color: <?php echo ($i <= round($row['avg_rating'])) ? '#f1c40f' : '#ecf0f1'; ?>; font-size: 14px; margin-right: 2px;"></i>
                                            <?php endfor; ?>
                                            <span style="margin-left: 5px;"><?php echo number_format($row['avg_rating'], 1); ?></span>
                                        </div>
                                    <?php else: ?>
                                        No ratings
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['order_count']; ?></td>
                                <td>
                                    <a href="product.php?id=<?php echo $row['id']; ?>" class="badge badge-admin">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center;">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            <?php elseif ($report_type === 'reviews'): ?>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <div class="product-item">
                                        <img src="/assets/images/<?php echo htmlspecialchars($row['product_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($row['product_name']); ?>" 
                                             class="thumbnail"
                                             onerror="this.src='/assets/images/placeholder.jpg'">
                                        <a href="product.php?id=<?php echo $row['product_id']; ?>">
                                            <?php echo htmlspecialchars($row['product_name']); ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <a href="admin_user_view.php?id=<?php echo $row['user_id']; ?>">
                                        <?php echo htmlspecialchars($row['user_name']); ?>
                                    </a>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center;">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="color: <?php echo ($i <= $row['rating']) ? '#f1c40f' : '#ecf0f1'; ?>; font-size: 14px; margin-right: 2px;"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td><?php echo nl2br(htmlspecialchars(substr($row['comment'], 0, 100)) . (strlen($row['comment']) > 100 ? '...' : '')); ?></td>
                                <td><?php echo date('M j, Y g:i a', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="#" class="badge badge-warning" onclick="return confirm('Are you sure you want to flag this review?')">
                                        <i class="fas fa-flag"></i> Flag
                                    </a>
                                    <a href="#" class="badge badge-danger" onclick="return confirm('Are you sure you want to delete this review?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No reviews found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            <?php elseif ($report_type === 'sales'): ?>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td>
                                    <a href="admin_user_view.php?id=<?php echo $row['user_id']; ?>">
                                        <?php echo htmlspecialchars($row['user_name']); ?>
                                    </a>
                                </td>
                                <td><?php echo date('M j, Y g:i a', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($row['status']); ?>">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $row['item_count']; ?></td>
                                <td>$<?php echo number_format($row['total_price'], 2); ?></td>
                                <td>
                                    <a href="#" class="badge badge-admin">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center;">No orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            <?php endif; ?>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?type=<?php echo $report_type; ?>&page=<?php echo ($page - 1); ?>&range=<?php echo $range; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="page-link">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            if ($start_page > 1): ?>
                <a href="?type=<?php echo $report_type; ?>&page=1&range=<?php echo $range; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="page-link">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="page-link">...</span>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?type=<?php echo $report_type; ?>&page=<?php echo $i; ?>&range=<?php echo $range; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" 
                   class="page-link <?php echo ($i == $page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="page-link">...</span>
                <?php endif; ?>
                <a href="?type=<?php echo $report_type; ?>&page=<?php echo $total_pages; ?>&range=<?php echo $range; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="page-link"><?php echo $total_pages; ?></a>
            <?php endif; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?type=<?php echo $report_type; ?>&page=<?php echo ($page + 1); ?>&range=<?php echo $range; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo $sort; ?>" class="page-link">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</body>
</html> 