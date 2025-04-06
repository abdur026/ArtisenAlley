<?php
// Add security headers
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdnjs.cloudflare.com 'unsafe-inline'; style-src 'self' https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data:;");
header("Referrer-Policy: strict-origin-when-cross-origin");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Artisan Alley</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <?php if (isset($page_specific_css)): ?>
        <?php echo $page_specific_css; ?>
    <?php endif; ?>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <nav class="main-nav">
                <div class="logo">
                    <a href="/index.php">Artisan Alley</a>
                </div>
                <div class="nav-links">
                    <a href="/index.php">Home</a>
                    <a href="/products.php">Shop</a>
                    <a href="/forum.php">Community</a>
                    <a href="/about.php">About</a>
                    <a href="/contact.php">Contact</a>
                </div>
                <div class="nav-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/profile.php" class="nav-link">My Account</a>
                        <a href="/logout.php" class="nav-link">Logout</a>
                    <?php else: ?>
                        <a href="/login.php" class="nav-link">Login</a>
                        <a href="/register.php" class="nav-link">Register</a>
                    <?php endif; ?>
                    <a href="/cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (isset($_SESSION['cart']) && count($_SESSION['cart']) > 0): ?>
                            <span class="cart-count"><?php echo array_sum($_SESSION['cart']); ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </nav>
        </div>
    </header>
    <main class="site-main">
        <!-- Main content starts here -->
