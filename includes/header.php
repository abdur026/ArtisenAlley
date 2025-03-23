<?php
session_start();
require_once __DIR__ . "/../config/paths.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Alley - Handcrafted with Love</title>
    <link rel="stylesheet" href="<?php echo url('/assets/css/main.css'); ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header>
        <div class="header-content">
            <div class="logo">
                <h1><a href="<?php echo url('/index.php'); ?>">Artisan Alley</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo url('/index.php'); ?>">Home</a></li>
                    <li><a href="<?php echo url('/search.php?keyword='); ?>"><i class="fas fa-search"></i> Explore</a></li>
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li><a href="<?php echo url('/profile.php'); ?>"><i class="fas fa-user"></i> Profile</a></li>
                        <li><a href="<?php echo url('/cart.php'); ?>"><i class="fas fa-shopping-cart"></i> Cart</a></li>
                        <li><a href="<?php echo url('/logout.php'); ?>"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                            <li><a href="<?php echo url('/admin_dashboard.php'); ?>"><i class="fas fa-cog"></i> Admin</a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="<?php echo url('/login.php'); ?>"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                        <li><a href="<?php echo url('/register.php'); ?>"><i class="fas fa-user-plus"></i> Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
