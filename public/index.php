<?php
require_once '../includes/functions.php';
require_once '../includes/header.php';
require_once '../config/db.php';  // This will give us $pdo connection

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug database connection
try {
    $pdo->query("SELECT 1");
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Fetch recent products
try {
    $products_query = "SELECT * FROM products ORDER BY created_at DESC LIMIT 6";
    $products_result = $pdo->query($products_query);
} catch(PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}
?>

<main class="homepage">
    <section class="hero">
        <h2>Discover Unique Handcrafted Items</h2>
        <p>Support local artisans and find one-of-a-kind pieces</p>
        <a href="<?php echo url('/search.php'); ?>" class="cta-button">Explore Now</a>
    </section>

    <section class="featured-products">
        <h2>Featured Products</h2>
        <div class="product-grid">
            <?php
            try {
                // Fetch featured products from database
                $stmt = $pdo->query("SELECT * FROM products WHERE featured = 1 LIMIT 4");
                while ($product = $stmt->fetch()) {
            ?>
                    <div class="product-card">
                        <img src="<?php echo image('/' . htmlspecialchars($product['image'] ?? '')); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="price">$<?php echo number_format($product['price'], 2); ?></p>
                        <a href="<?php echo url('/product.php?id=' . $product['id']); ?>" class="cta-button">View Details</a>
                    </div>
            <?php 
                }
            } catch(PDOException $e) {
                echo "<p class='error-message'>Error loading products: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
    </section>

    <section class="categories">
        <h2>Shop by Category</h2>
        <div class="category-grid">
            <?php
            try {
                // Fetch categories from database
                $stmt = $pdo->query("SELECT category, description FROM categories");
                while ($category = $stmt->fetch()) {
            ?>
                    <div class="category-card">
                        <img src="<?php echo image('/categories/' . strtolower(str_replace(' ', '-', $category['category'])) . '.jpg'); ?>" alt="<?php echo htmlspecialchars($category['category']); ?>">
                        <h3><?php echo htmlspecialchars($category['category']); ?></h3>
                        <a href="<?php echo url('/search.php?category=' . urlencode($category['category'])); ?>" class="cta-button">Browse</a>
                    </div>
            <?php 
                }
            } catch(PDOException $e) {
                echo "<p class='error-message'>Error loading categories: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?>
        </div>
    </section>

    <!-- Value Proposition Section -->
    <section class="value-props">
        <div class="container">
            <h2 class="section-title">Why Choose Artisan Alley?</h2>
            <div class="value-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <h3>Handcrafted with Love</h3>
                    <p>Every item is carefully crafted by skilled artisans who pour their heart into their work.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-globe"></i>
                    </div>
                    <h3>Support Local Artisans</h3>
                    <p>Your purchase directly supports independent craftspeople and their communities.</p>
                </div>
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3>Unique Pieces</h3>
                    <p>Find one-of-a-kind items that you won't see in regular stores.</p>
                </div>
            </div>
        </div>
    </section>
</main>

<?php require_once '../includes/footer.php'; ?>
