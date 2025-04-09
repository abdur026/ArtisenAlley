<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Get user registration stats by month (last 6 months)
$user_stats_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
";
$user_stats_result = $conn->query($user_stats_query);
$user_stats = [];
$user_labels = [];
$user_data = [];

if ($user_stats_result) {
    while ($row = $user_stats_result->fetch_assoc()) {
        $user_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $user_data[] = $row['count'];
    }
}

// Get review stats by month (last 6 months)
$review_stats_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as count,
        AVG(rating) as avg_rating
    FROM reviews
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
";
$review_stats_result = $conn->query($review_stats_query);
$review_labels = [];
$review_data = [];
$rating_data = [];

if ($review_stats_result) {
    while ($row = $review_stats_result->fetch_assoc()) {
        $review_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $review_data[] = $row['count'];
        $rating_data[] = round($row['avg_rating'], 1);
    }
}

// Get top 5 products by review count
$top_products_query = "
    SELECT 
        p.id,
        p.name,
        COUNT(r.id) as review_count,
        AVG(r.rating) as avg_rating
    FROM products p
    LEFT JOIN reviews r ON p.id = r.product_id
    GROUP BY p.id
    ORDER BY review_count DESC, avg_rating DESC
    LIMIT 5
";
$top_products_result = $conn->query($top_products_query);

// Get top 5 users by activity (reviews + orders)
$top_users_query = "
    SELECT 
        u.id,
        u.name,
        u.email,
        COUNT(DISTINCT r.id) as review_count,
        COUNT(DISTINCT o.id) as order_count,
        (COUNT(DISTINCT r.id) + COUNT(DISTINCT o.id)) as activity_score
    FROM users u
    LEFT JOIN reviews r ON u.id = r.user_id
    LEFT JOIN orders o ON u.id = o.user_id
    WHERE u.role != 'admin'
    GROUP BY u.id
    ORDER BY activity_score DESC
    LIMIT 5
";
$top_users_result = $conn->query($top_users_query);

// Get Sales Data
$sales_query = "
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(total_price) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
";
$sales_result = $conn->query($sales_query);
$sales_labels = [];
$sales_data = [];

if ($sales_result) {
    while ($row = $sales_result->fetch_assoc()) {
        $sales_labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $sales_data[] = $row['revenue'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Analytics Dashboard - Admin</title>
    <link rel="stylesheet" href="../src/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .stat-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #3498db;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #7f8c8d;
            text-transform: uppercase;
            font-weight: 600;
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
        
        .activity-bar {
            height: 6px;
            background-color: #ecf0f1;
            border-radius: 3px;
            margin-top: 5px;
        }
        
        .activity-fill {
            height: 100%;
            background-color: #3498db;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Analytics Dashboard</h1>
        <nav class="admin-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="admin_dashboard.php"><i class="fas fa-users"></i> Users</a>
            <a href="admin_orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div>

    <!-- Quick Stats -->
    <?php
    // Get total counts
    $total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
    $total_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews")->fetch_assoc()['count'];
    $total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
    
    // Get today's counts
    $today_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
    $today_reviews = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
    $today_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['count'];
    $today_revenue = $conn->query("SELECT SUM(total_price) as sum FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['sum'] ?? 0;
    ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($total_users); ?></div>
            <div class="stat-label">Total Users</div>
            <div class="stat-badge">+<?php echo $today_users; ?> today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($total_products); ?></div>
            <div class="stat-label">Total Products</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($total_reviews); ?></div>
            <div class="stat-label">Total Reviews</div>
            <div class="stat-badge">+<?php echo $today_reviews; ?> today</div>
        </div>
        <div class="stat-card">
            <div class="stat-value">$<?php echo number_format($today_revenue, 2); ?></div>
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-badge"><?php echo $today_orders; ?> orders</div>
        </div>
    </div>

    <!-- Charts -->
    <div class="dashboard-grid">
        <!-- User Growth Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">User Growth</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Review Activity Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Review Activity</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="reviewActivityChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Sales Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Revenue Trends</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Average Rating Chart -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Average Ratings</h2>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="ratingChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tables -->
    <div class="dashboard-grid">
        <!-- Top Products -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Top Products</h2>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Reviews</th>
                            <th>Avg. Rating</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_products_result && $top_products_result->num_rows > 0): ?>
                            <?php while ($product = $top_products_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="product.php?id=<?php echo $product['id']; ?>">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge badge-primary"><?php echo $product['review_count']; ?></span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center;">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star" style="color: <?php echo ($i <= round($product['avg_rating'])) ? '#f1c40f' : '#ecf0f1'; ?>; font-size: 14px; margin-right: 2px;"></i>
                                            <?php endfor; ?>
                                            <span style="margin-left: 5px;"><?php echo number_format($product['avg_rating'], 1); ?></span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" style="text-align: center;">No products found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Most Active Users -->
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Most Active Users</h2>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Reviews</th>
                            <th>Orders</th>
                            <th>Activity</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($top_users_result && $top_users_result->num_rows > 0): ?>
                            <?php while ($user = $top_users_result->fetch_assoc()): ?>
                                <?php 
                                    $max_activity = 100; // Benchmark for 100%
                                    $activity_percentage = min(100, ($user['activity_score'] / $max_activity) * 100);
                                ?>
                                <tr>
                                    <td>
                                        <a href="admin_user_view.php?id=<?php echo $user['id']; ?>">
                                            <?php echo htmlspecialchars($user['name']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $user['review_count']; ?></td>
                                    <td><?php echo $user['order_count']; ?></td>
                                    <td>
                                        <div class="activity-bar">
                                            <div class="activity-fill" style="width: <?php echo $activity_percentage; ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">No active users found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        console.log('Chart.js script running');
        console.log('User data:', <?php echo json_encode($user_data); ?>);
        console.log('Review data:', <?php echo json_encode($review_data); ?>);
        
        // User Growth Chart
        const userCtx = document.getElementById('userGrowthChart').getContext('2d');
        const userGrowthChart = new Chart(userCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($user_labels); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode($user_data); ?>,
                    backgroundColor: 'rgba(52, 152, 219, 0.2)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(52, 152, 219, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Review Activity Chart
        const reviewCtx = document.getElementById('reviewActivityChart').getContext('2d');
        const reviewActivityChart = new Chart(reviewCtx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($review_labels); ?>,
                datasets: [{
                    label: 'Reviews',
                    data: <?php echo json_encode($review_data); ?>,
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($sales_labels); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode($sales_data); ?>,
                    backgroundColor: 'rgba(155, 89, 182, 0.2)',
                    borderColor: 'rgba(155, 89, 182, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(155, 89, 182, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: $' + context.parsed.y;
                            }
                        }
                    }
                }
            }
        });

        // Rating Chart
        const ratingCtx = document.getElementById('ratingChart').getContext('2d');
        const ratingChart = new Chart(ratingCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($review_labels); ?>,
                datasets: [{
                    label: 'Average Rating',
                    data: <?php echo json_encode($rating_data); ?>,
                    backgroundColor: 'rgba(241, 196, 15, 0.2)',
                    borderColor: 'rgba(241, 196, 15, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointBackgroundColor: 'rgba(241, 196, 15, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        min: 0,
                        max: 5,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html> 