<?php 
session_start();
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch recent products
$products_query = "SELECT * FROM products ORDER BY created_at DESC LIMIT 6";
$products_result = $conn->query($products_query);

// Check for query errors
if (!$products_result) {
    die("Error fetching products: " . $conn->error);
}

// Debug product count

// Fetch categories
$categories_query = "SELECT DISTINCT category FROM products LIMIT 6";
$categories_result = $conn->query($categories_query);
?>

<main>
    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h2>Discover Unique Handcrafted Treasures</h2>
            <p>Support independent artisans and find one-of-a-kind pieces that tell a story. Each item is crafted with passion and dedication.</p>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-products">
        <div class="container">
            <h2 class="section-title">Latest Artisan Pieces</h2>
            <div class="products-grid">
                <?php 
                if ($products_result->num_rows > 0):
                    while($product = $products_result->fetch_assoc()): 
                ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars(str_replace('/public', '', $product['image_url'] ?? '/assets/images/placeholder.jpg')); ?>" 
                         alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>" 
                         class="product-image"
                         onerror="this.src='/assets/images/placeholder.jpg'">
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                        <a href="/product.php?id=<?php echo $product['id']; ?>" class="cta-button">View Details</a>
                    </div>
                </div>
                <?php 
                    endwhile; 
                else:
                    echo "<p class='no-products'>No products found. Please check the database.</p>";
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Shop by Category</h2>
            <div class="categories-grid">
                <?php while($category = $categories_result->fetch_assoc()): ?>
                <div class="category-card">
                    <img src="/images/categories/<?php echo strtolower(str_replace(' ', '-', $category['category'])); ?>.jpg" 
                         alt="<?php echo htmlspecialchars($category['category']); ?>" 
                         class="category-image"
                         onerror="this.src='/assets/images/placeholder.jpg'">
                    <div class="category-overlay">
                        <h3><?php echo htmlspecialchars($category['category']); ?></h3>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
