<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Artisan Alley - Home</title>
    <link rel="stylesheet" href="../src/main.css">
</head>
<body>
    <header>
        <h1>Welcome to Artisan Alley!</h1>
        <nav>
            <?php if (isset($_SESSION['user_id'])): ?>
                <span>Hello, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                <a href="logout.php">Logout</a>
                <!-- Add more links for logged-in users (e.g., Profile, My Products) -->
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <p>This is your e-commerce platform for handmade goods. Browse products and enjoy!</p>
        <!-- Future content: product listing, search bar, etc. -->
    </main>
</body>
</html>