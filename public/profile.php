<?php
// Enable full error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/breadcrumb.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url('/login.php'));
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    error_log("Fetching profile for user ID: $user_id");
    
    // Debug connection
    if (!$conn) {
        error_log("Database connection is null");
        throw new Exception("Database connection failed");
    }
    
    // Check if users table exists
    $tables_result = $conn->query("SHOW TABLES LIKE 'users'");
    if (!$tables_result || $tables_result->num_rows === 0) {
        error_log("Users table not found in database");
        throw new Exception("Users table not found");
    }
    
    $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'name'");
    if (!$stmt) {
        error_log("Error preparing SHOW COLUMNS query: " . $conn->error);
        throw new Exception("Failed to check column structure: " . $conn->error);
    }
    
    $stmt->execute();
    $has_name_column = ($stmt->get_result()->num_rows > 0);
    error_log("Users table has 'name' column: " . ($has_name_column ? 'Yes' : 'No'));

    // Debug available columns
    $columns_result = $conn->query("SHOW COLUMNS FROM users");
    $columns = [];
    while ($column = $columns_result->fetch_assoc()) {
        $columns[] = $column['Field'];
    }
    error_log("Available columns in users table: " . implode(", ", $columns));
    
    if ($has_name_column) {
        // Using single name field
        $stmt = $conn->prepare("SELECT id, name, email, profile_image FROM users WHERE id = ?");
    } else {
        // Using first_name and last_name fields
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email, profile_image FROM users WHERE id = ?");
    }
    
    if (!$stmt) {
        error_log("Error preparing SELECT query: " . $conn->error);
        throw new Exception("Failed to prepare user query: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    
    if (!$stmt->execute()) {
        error_log("Error executing query: " . $stmt->error);
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if (!$result) {
        error_log("Query result is null");
        throw new Exception("Failed to get result from query");
    }
    
    $user = $result->fetch_assoc();
    
    if (!$user) {
        // Try direct query to debug
        $direct_result = $conn->query("SELECT * FROM users WHERE id = $user_id");
        if ($direct_result && $direct_result->num_rows > 0) {
            $user = $direct_result->fetch_assoc();
            error_log("Direct query succeeded where prepared statement failed");
        } else {
            error_log("User with ID $user_id not found in database");
            throw new Exception("User not found in database");
        }
    }
    
    error_log("Found user data: " . json_encode($user));

    // Combine name fields if using first_name/last_name
    if (!$has_name_column && isset($user['first_name']) && isset($user['last_name'])) {
        $user['name'] = $user['first_name'] . ' ' . $user['last_name'];
    }

    if (!isset($user['profile_image']) || empty($user['profile_image'])) {
        $user['profile_image'] = 'default-avatar.jpg';
        $profile_image_data = null;
    } else {
        $image_path = __DIR__ . "/../uploads/" . $user['profile_image'];
        if (file_exists($image_path)) {
            $profile_image_data = base64_encode(file_get_contents($image_path));
        } else {
            $profile_image_data = null;
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
    // Log the error
    error_log("Profile error: " . $e->getMessage());
    // Don't set default user values anymore - let's show the error instead
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Profile - Artisan Alley</title>
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/main.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 20px;
            padding: 3rem 2rem;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            opacity: 0.1;
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            margin-bottom: 1.5rem;
            background-color: #fff;
        }

        .profile-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-email {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 768px) {
            .profile-content {
                grid-template-columns: 300px 1fr;
            }
        }

        .profile-sidebar {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .profile-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .profile-menu li {
            margin-bottom: 0.5rem;
        }

        .profile-menu a {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: var(--text-color);
            text-decoration: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .profile-menu a:hover {
            background: var(--light-gray);
            color: var(--primary-color);
        }

        .profile-menu i {
            margin-right: 1rem;
            width: 20px;
            text-align: center;
        }

        .profile-main {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--light-gray);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--primary-color-rgb), 0.1);
        }

        .form-group input[readonly] {
            background-color: var(--light-gray);
            cursor: not-allowed;
        }

        .image-upload {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .current-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
        }

        .upload-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            background-color: var(--light-gray);
            border: none;
            border-radius: 8px;
            color: var(--text-color);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-button:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .update-button {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .update-button:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }

        .review-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-5px);
        }

        .review-product {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .review-product img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: var(--text-color);
        }

        .rating {
            display: flex;
            gap: 0.25rem;
        }

        .rating .fas.fa-star.filled {
            color: #f1c40f;
        }

        .rating .fas.fa-star {
            color: #e0e0e0;
        }

        .review-text {
            color: #666;
            line-height: 1.6;
            margin: 1rem 0;
        }

        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .review-date {
            color: #888;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-product {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .view-product:hover {
            color: var(--secondary-color);
        }

        .no-reviews {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .no-reviews i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-reviews p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .browse-products {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .browse-products:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .view-all-reviews {
            color: var(--primary-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .view-all-reviews:hover {
            color: var(--secondary-color);
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="profile-container">
        <?php
        // Generate breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/index.php')],
            ['name' => 'Profile']
        ];
        echo generate_breadcrumbs($breadcrumbs);
        ?>
        
        <?php if (isset($error)): ?>
        <div class="alert alert-error" style="margin-bottom: 2rem;">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>There was a problem loading your profile:</strong>
                <p><?php echo htmlspecialchars($error); ?></p>
                <p>Please try logging out and logging back in. If the issue persists, contact support.</p>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($user)): ?>
        <div class="profile-header">
            <?php if (isset($profile_image_data) && $profile_image_data): ?>
                <img src="data:image/jpeg;base64,<?php echo $profile_image_data; ?>" 
                     alt="Profile Picture" 
                     class="profile-avatar">
            <?php else: ?>
                <img src="<?php echo asset_url('assets/images/default-avatar.png'); ?>" 
                     alt="Profile Picture" 
                     class="profile-avatar">
            <?php endif; ?>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h1>
            <p class="profile-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
        </div>

        <div class="profile-content">
            <aside class="profile-sidebar">
                <ul class="profile-menu">
                    <li>
                        <a href="#profile-info">
                            <i class="fas fa-user"></i>
                            Profile Information
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('/orders.php'); ?>">
                            <i class="fas fa-shopping-bag"></i>
                            My Orders
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('/wishlist.php'); ?>">
                            <i class="fas fa-heart"></i>
                            Wishlist
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('/reviews.php'); ?>">
                            <i class="fas fa-star"></i>
                            Community Reviews
                        </a>
                    </li>
                    <li>
                        <a href="#my-reviews">
                            <i class="fas fa-comment"></i>
                            My Reviews
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('/settings.php'); ?>">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </li>
                </ul>
            </aside>

            <main class="profile-main">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
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

                <section id="profile-info">
                    <h2 class="section-title">Profile Information</h2>
                    <form action="<?php echo url('/update_profile.php'); ?>" method="POST" enctype="multipart/form-data">
                        <div class="image-upload">
                            <?php if (isset($profile_image_data) && $profile_image_data): ?>
                                <img src="data:image/jpeg;base64,<?php echo $profile_image_data; ?>" 
                                     alt="Current Profile Picture" 
                                     class="current-image">
                            <?php else: ?>
                                <img src="<?php echo asset_url('assets/images/default-avatar.png'); ?>" 
                                     alt="Current Profile Picture" 
                                     class="current-image">
                            <?php endif; ?>
                            <label class="upload-button">
                                <i class="fas fa-camera"></i>
                                Change Photo
                                <input type="file" id="profile_image" name="profile_image" style="display: none;" accept="image/*">
                            </label>
                        </div>

                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly>
                        </div>

                        <button type="submit" class="update-button">
                            <i class="fas fa-save"></i>
                            Update Profile
                        </button>
                    </form>
                </section>

                <section id="my-reviews" class="profile-section">
                    <h2 class="section-title">
                        My Reviews
                        <a href="<?php echo url('/reviews.php'); ?>" class="view-all-reviews">View All Community Reviews</a>
                    </h2>
                    <div class="reviews-grid">
                        <?php
                        try {
                            // Check if reviews table exists
                            $reviews_table_check = $conn->query("SHOW TABLES LIKE 'reviews'");
                            if (!$reviews_table_check || $reviews_table_check->num_rows === 0) {
                                throw new Exception("Reviews table not found");
                            }
                            
                            // Check if products table exists
                            $products_table_check = $conn->query("SHOW TABLES LIKE 'products'");
                            if (!$products_table_check || $products_table_check->num_rows === 0) {
                                throw new Exception("Products table not found");
                            }
                            
                            // Get column names for products table
                            $product_columns_result = $conn->query("SHOW COLUMNS FROM products");
                            $product_columns = [];
                            while ($column = $product_columns_result->fetch_assoc()) {
                                $product_columns[] = $column['Field'];
                            }
                            error_log("Product table columns: " . implode(", ", $product_columns));
                            
                            // Check for image column
                            $has_image = in_array('image', $product_columns);
                            $has_image_url = in_array('image_url', $product_columns);
                            
                            // Get column names for reviews table
                            $reviews_columns_result = $conn->query("SHOW COLUMNS FROM reviews");
                            $reviews_columns = [];
                            while ($column = $reviews_columns_result->fetch_assoc()) {
                                $reviews_columns[] = $column['Field'];
                            }
                            error_log("Reviews table columns: " . implode(", ", $reviews_columns));
                            
                            // Check for comment/content column
                            $comment_column = in_array('comment', $reviews_columns) ? 'comment' : 
                                            (in_array('content', $reviews_columns) ? 'content' : 'comment');
                            
                            // Determine the image column to use
                            $image_column = $has_image ? 'image' : ($has_image_url ? 'image_url' : null);
                            
                            if (!$image_column) {
                                $image_part = '';
                            } else {
                                $image_part = ", p.{$image_column} as product_image";
                            }
                            
                            // Build and execute the query
                            $reviews_query = "SELECT r.*, p.name as product_name {$image_part}
                                            FROM reviews r 
                                            JOIN products p ON r.product_id = p.id 
                                            WHERE r.user_id = ? 
                                            ORDER BY r.created_at DESC";
                                            
                            error_log("Reviews query: $reviews_query");
                            
                            $stmt = $conn->prepare($reviews_query);
                            if (!$stmt) {
                                throw new Exception("Failed to prepare reviews query: " . $conn->error);
                            }
                            
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $reviews_result = $stmt->get_result();

                            if ($reviews_result && $reviews_result->num_rows > 0):
                                while($review = $reviews_result->fetch_assoc()):
                                    $default_image = 'placeholder.jpg';
                                    $product_image = $image_column ? ($review['product_image'] ?? $default_image) : $default_image;
                        ?>
                                <div class="review-card">
                                    <div class="review-product">
                                        <img src="<?php echo asset_url('assets/images/' . htmlspecialchars($product_image)); ?>" 
                                             alt="<?php echo htmlspecialchars($review['product_name'] ?? 'Product'); ?>"
                                             class="product-image"
                                             onerror="this.src='<?php echo asset_url('assets/images/placeholder.jpg'); ?>'">
                                        <div class="product-info">
                                            <h3><?php echo htmlspecialchars($review['product_name'] ?? 'Unknown Product'); ?></h3>
                                            <div class="rating">
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star <?php echo $i <= ($review['rating'] ?? 0) ? 'filled' : ''; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="review-text"><?php echo htmlspecialchars($review[$comment_column] ?? ''); ?></p>
                                    <div class="review-meta">
                                        <span class="review-date">
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('M d, Y', strtotime($review['created_at'] ?? 'now')); ?>
                                        </span>
                                        <a href="<?php echo url('/product.php?id=' . ($review['product_id'] ?? 0)); ?>" class="view-product">
                                            View Product <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                        <?php 
                                endwhile;
                            else:
                        ?>
                                <div class="no-reviews">
                                    <i class="fas fa-comment-alt"></i>
                                    <p>You haven't written any reviews yet.</p>
                                    <a href="<?php echo url('/index.php'); ?>" class="browse-products">Browse Products</a>
                                </div>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            error_log("Reviews error: " . $e->getMessage());
                        ?>
                            <div class="no-reviews">
                                <i class="fas fa-exclamation-circle"></i>
                                <p>We couldn't load your reviews at this time.</p>
                                <p class="text-muted"><?php echo htmlspecialchars($e->getMessage()); ?></p>
                                <a href="<?php echo url('/index.php'); ?>" class="browse-products">Browse Products</a>
                            </div>
                        <?php
                        }
                        ?>
                    </div>
                </section>
            </main>
        </div>
        <?php else: ?>
        <div class="alert alert-error" style="margin: 3rem auto; max-width: 600px; text-align: center;">
            <i class="fas fa-user-slash" style="font-size: 3rem; margin-bottom: 1rem;"></i>
            <h2>Profile Unavailable</h2>
            <p>We couldn't load your profile information at this time.</p>
            <div style="margin-top: 1.5rem;">
                <a href="<?php echo url('/logout.php'); ?>" class="update-button" style="margin-right: 1rem;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <a href="<?php echo url('/index.php'); ?>" class="browse-products">
                    <i class="fas fa-home"></i> Go Home
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        document.getElementById('profile_image').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('.current-image').src = e.target.result;
                };
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        // Add smooth scrolling to section links
        document.querySelectorAll('.profile-menu a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const section = document.querySelector(this.getAttribute('href'));
                if (section) {
                    section.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
