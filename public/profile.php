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
    $stmt = $conn->prepare("SELECT username, first_name, last_name, email, profile_image FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found in database");
    }

    // Combine first and last name for display
    $user['name'] = $user['first_name'] . ' ' . $user['last_name'];

    if (!$user['profile_image']) {
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

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert i {
            margin-right: 1rem;
            font-size: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <?php if (isset($user) && $user): ?>
                <img src="data:image/jpeg;base64,<?php echo $profile_image_data; ?>" alt="Profile Picture" class="profile-avatar">
                <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
            <?php else: ?>
                <p class="error">User information could not be retrieved.</p>
            <?php endif; ?>
        </div>

        <div class="profile-content">
            <aside class="profile-sidebar">
                <ul class="profile-menu">
                    <li>
                        <a href="#profile-info">
                            <i class="fas fa-user"></i> Profile Info
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('orders.php'); ?>">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('wishlist.php'); ?>">
                            <i class="fas fa-heart"></i> Wishlist
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('reviews.php'); ?>">
                            <i class="fas fa-star"></i> Community Reviews
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo url('settings.php'); ?>">
                            <i class="fas fa-cog"></i> Settings
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

                <section id="profile-info">
                    <h2 class="section-title">Profile Information</h2>
                    <?php if (isset($user) && $user): ?>
                        <form action="<?php echo url('update_profile.php'); ?>" method="POST" enctype="multipart/form-data">
                            <div class="image-upload">
                                <?php if ($profile_image_data): ?>
                                    <img src="data:image/jpeg;base64,<?php echo $profile_image_data; ?>" 
                                         alt="Current Profile Picture" 
                                         class="current-image">
                                <?php else: ?>
                                    <img src="<?php echo asset_url('assets/images/default-avatar.png'); ?>" 
                                         alt="Current Profile Picture" 
                                         class="current-image">
                                <?php endif; ?>
                                <input type="file" name="profile_image" id="profile_image">
                            </div>

                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                            </div>

                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    <?php else: ?>
                        <p>User information could not be retrieved.</p>
                    <?php endif; ?>
                </section>

                <section id="my-reviews" class="profile-section">
                    <h2 class="section-title">
                        My Reviews
                        <a href="<?php echo url('reviews.php'); ?>" class="view-all-reviews">View All Community Reviews</a>
                    </h2>
                    <div class="reviews-grid">
                        <?php
                        // Example reviews loop
                        $reviews = [];
                        if (!empty($reviews)):
                            foreach ($reviews as $review):
                        ?>
                            <div class="review-card">
                                <div class="review-product">
                                    <img src="<?php echo asset_url('assets/images/' . htmlspecialchars($review['product_image'])); ?>" 
                                         alt="<?php echo htmlspecialchars($review['product_name']); ?>"
                                         class="product-image"
                                         onerror="this.src='<?php echo asset_url('assets/images/placeholder.jpg'); ?>'">
                                    <div class="product-info">
                                        <h3><?php echo htmlspecialchars($review['product_name']); ?></h3>
                                        <div class="rating">
                                            <?php for ($i = 0; $i < 5; $i++): ?>
                                                <i class="fas fa-star<?php echo $i < $review['rating'] ? '' : '-o'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="review-content">
                                    <p><?php echo htmlspecialchars($review['content']); ?></p>
                                    <span class="review-date">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                    <a href="<?php echo url('product.php?id=' . $review['product_id']); ?>" class="view-product">
                                        View Product <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php
                            endforeach;
                        else:
                        ?>
                            <div class="no-reviews">
                                <i class="fas fa-comment-alt"></i>
                                <p>You haven't written any reviews yet.</p>
                                <a href="<?php echo url('index.php'); ?>" class="browse-products">Browse Products</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
