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
    // Check database connection first
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not established"));
    }

    $user_id = $_SESSION['user_id'];
    
    // Prepare statement with error checking
    $stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, profile_image FROM users WHERE id = ?");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }
    
    // Bind parameter with error checking
    if (!$stmt->bind_param("i", $user_id)) {
        throw new Exception("Failed to bind parameters: " . $stmt->error);
    }
    
    // Execute with error checking
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    if (!$result) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }
    
    $user = $result->fetch_assoc();
    if (!$user) {
        throw new Exception("User not found in database");
    }

    // Combine first and last name for display
    $user['name'] = trim($user['first_name'] . ' ' . $user['last_name']);
    if (empty($user['name'])) {
        $user['name'] = $user['username']; // Fallback to username if name is empty
    }

    // Handle profile image
    if (empty($user['profile_image'])) {
        $user['profile_image'] = 'default-avatar.jpg';
        $profile_image_data = null;
    } else {
        $image_path = __DIR__ . "/../uploads/" . $user['profile_image'];
        if (file_exists($image_path)) {
            $profile_image_data = base64_encode(file_get_contents($image_path));
        } else {
            error_log("Profile image not found: " . $image_path);
            $user['profile_image'] = 'default-avatar.jpg';
            $profile_image_data = null;
        }
    }

    // Close statement
    $stmt->close();
    
} catch (Exception $e) {
    error_log("Profile error: " . $e->getMessage());
    $error = "User information could not be retrieved. Please try again later.";
    $user = null;
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
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --text-color: #2c3e50;
            --light-gray: #f8f9fa;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
        }

        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease-out;
        }

        .alert i {
            font-size: 1.5rem;
        }

        .alert-error {
            background-color: #fde8e8;
            color: var(--error-color);
            border: 1px solid #fbd5d5;
        }

        .alert-success {
            background-color: #def7ec;
            color: var(--success-color);
            border: 1px solid #bcf0da;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 20px;
            padding: 3rem 2rem;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            margin-bottom: 1.5rem;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
            height: fit-content;
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

        .profile-menu a.active,
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
            border-bottom: 2px solid var(--light-gray);
            padding-bottom: 1rem;
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
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(52, 152, 219, 0.1);
        }

        @keyframes slideIn {
            from {
                transform: translateY(-10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="profile-container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($user) && $user): ?>
            <div class="profile-header">
                <img 
                    src="<?php echo $profile_image_data ? 'data:image/jpeg;base64,' . $profile_image_data : asset_url('assets/images/default-avatar.jpg'); ?>" 
                    alt="Profile Picture" 
                    class="profile-avatar"
                >
                <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <div class="profile-content">
                <aside class="profile-sidebar">
                    <ul class="profile-menu">
                        <li>
                            <a href="#profile-info" class="active">
                                <i class="fas fa-user"></i>
                                Profile Information
                            </a>
                        </li>
                        <li>
                            <a href="#my-orders">
                                <i class="fas fa-shopping-bag"></i>
                                My Orders
                            </a>
                        </li>
                        <li>
                            <a href="#my-reviews">
                                <i class="fas fa-star"></i>
                                My Reviews
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo url('/logout.php'); ?>">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </aside>

                <main class="profile-main">
                    <section id="profile-info" class="profile-section">
                        <h2 class="section-title">Profile Information</h2>
                        <form action="<?php echo url('/update_profile.php'); ?>" method="POST" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
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
                                <label for="email">Email</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="profile_image">Profile Image</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </section>
                </main>
            </div>
        <?php else: ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <p>Please log in to view your profile.</p>
                <a href="<?php echo url('/login.php'); ?>" class="btn btn-primary">Log In</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
