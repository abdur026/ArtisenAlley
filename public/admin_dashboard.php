<?php
session_start();
require_once '../config/db.php';
require_once '../includes/utils/csrf.php';

// Redirect if not admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Initialize search variables
$search_term = '';
$search_by = 'name'; // Default search by name
$search_query = "SELECT DISTINCT u.id, u.name, u.email, u.role, u.created_at FROM users u";
$search_params = [];
$search_types = '';

// Handle search form submission
if (isset($_GET['search']) && !empty($_GET['search_term'])) {
    $search_term = trim($_GET['search_term']);
    $search_by = $_GET['search_by'] ?? 'name';
    
    // Build appropriate query based on search type
    if ($search_by === 'post') {
        // Search in reviews/posts
        $search_query = "SELECT DISTINCT u.id, u.name, u.email, u.role, u.created_at 
                        FROM users u 
                        JOIN reviews r ON u.id = r.user_id 
                        WHERE r.comment LIKE ?";
        $search_params[] = "%$search_term%";
        $search_types .= "s";
    } else {
        // Search by name or email
        $search_query = "SELECT DISTINCT u.id, u.name, u.email, u.role, u.created_at 
                        FROM users u 
                        WHERE u.$search_by LIKE ?";
        $search_params[] = "%$search_term%";
        $search_types .= "s";
    }
} else {
    // Default query to show all users sorted by creation date
    $search_query = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
}

// Prepare and execute search query
if (!empty($search_params)) {
    $stmt = $conn->prepare($search_query);
    $stmt->bind_param($search_types, ...$search_params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($search_query);
}

// Count user posts/reviews - useful for detailed info
function getUserPostsCount($conn, $user_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
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
        
        .search-section {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-form input[type="text"] {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .search-form select {
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            font-size: 16px;
            background-color: white;
        }
        
        .search-form button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .search-form button:hover {
            background-color: #2980b9;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .users-table th, .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .users-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .users-table tr:last-child td {
            border-bottom: none;
        }
        
        .users-table tr:hover {
            background-color: #f8f9fa;
        }
        
        .action-btn {
            display: inline-block;
            padding: 6px 12px;
            background-color: #3498db;
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 14px;
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
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
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
        
        .search-results-info {
            margin-bottom: 15px;
            color: #7f8c8d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <nav class="admin-nav">
            <a href="index.php"><i class="fa fa-home"></i> Home</a>
            <a href="admin_analytics.php"><i class="fa fa-chart-line"></i> Analytics</a>
            <a href="admin_orders.php"><i class="fa fa-shopping-cart"></i> Orders</a>
            <a href="admin_hot_threads.php"><i class="fa fa-fire"></i> Hot Threads</a>
            <a href="admin_reports.php"><i class="fa fa-file-alt"></i> Reports</a>
            <a href="logout.php"><i class="fa fa-sign-out"></i> Logout</a>
        </nav>
    </div>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<div style='background-color: #fee2e2; color: #dc2626; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>" . 
             "<i class='fas fa-exclamation-circle'></i> " . $_SESSION['error'] . "</div>";
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['success'])) {
        echo "<div style='background-color: #dcfce7; color: #16a34a; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>" . 
             "<i class='fas fa-check-circle'></i> " . $_SESSION['success'] . "</div>";
        unset($_SESSION['success']);
    }
    ?>

    <div class="search-section">
        <h2>Search Users</h2>
        <form action="admin_dashboard.php" method="GET" class="search-form">
            <input type="text" name="search_term" placeholder="Search term..." value="<?php echo htmlspecialchars($search_term); ?>">
            <select name="search_by">
                <option value="name" <?php echo $search_by === 'name' ? 'selected' : ''; ?>>Name</option>
                <option value="email" <?php echo $search_by === 'email' ? 'selected' : ''; ?>>Email</option>
                <option value="post" <?php echo $search_by === 'post' ? 'selected' : ''; ?>>Posts/Reviews</option>
            </select>
            <button type="submit" name="search" value="1">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>

    <?php if (!empty($search_term)): ?>
        <div class="search-results-info">
            <p>Showing results for: <strong><?php echo htmlspecialchars($search_term); ?></strong> 
               in <strong><?php echo htmlspecialchars($search_by); ?></strong> 
               (<?php echo $result->num_rows; ?> results found)</p>
        </div>
    <?php endif; ?>

    <h2>Manage Users</h2>
    <table class="users-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Posts/Reviews</th>
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
                            <span class="badge bg-<?php echo ($row['role'] == 'admin') ? 'primary' : (($row['role'] == 'disabled') ? 'danger' : 'success'); ?>">
                                <?php echo htmlspecialchars($row['role']); ?>
                            </span>
                        </td>
                        <td><?php echo getUserPostsCount($conn, $row['id']); ?></td>
                        <td>
                            <a href="admin_user_view.php?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                <i class="bi bi-eye"></i> View
                            </a>
                            <?php if ($row['role'] != 'admin'): ?>
                                <a href="toggle_user.php?id=<?php echo $row['id']; ?>" class="btn btn-<?php echo ($row['role'] == 'disabled') ? 'success' : 'warning'; ?> btn-sm">
                                    <?php echo ($row['role'] == 'disabled') ? 'Enable' : 'Disable'; ?>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align: center; padding: 30px;">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
