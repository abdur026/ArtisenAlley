<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Artisan Alley</title>
    <link rel="stylesheet" href="../src/main.css"> 
</head>
<body>
    <header>
        <div class="logo">
            <h1><a href="/public/index.php">Artisan Alley</a></h1>
        </div>
        <nav>
            <ul>
                <li><a href="/public/index.php">Home</a></li>
                <li><a href="/public/search.php?keyword=">Search</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li><a href="/public/profile.php">Profile</a></li>
                    <li><a href="/public/cart.php">Cart</a></li>
                    <li><a href="/public/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="/public/login.php">Login</a></li>
                    <li><a href="/public/register.php">Register</a></li>
                <?php endif; ?>
                <?php if(isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li><a href="/public/admin_dashboard.php">Admin</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
