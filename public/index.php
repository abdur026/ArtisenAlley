<?php 
include __DIR__ . '/../includes/header.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default values in case database connection fails
$products_result = null;
$products_available = false;
$categories_result = null;
$categories_available = false;
$db_error = null;

// Only attempt database operations if it's needed
try {
    require_once __DIR__ . '/../config/db.php';
    
    // Debug database connection
    if (!$conn || $conn->connect_error) {
        throw new Exception("Database connection failed: " . ($conn ? $conn->connect_error : "Connection not established"));
    }

    // Fetch recent products
    $products_query = "SELECT p.*, u.username as artisan_name 
                      FROM products p 
                      LEFT JOIN users u ON p.seller_id = u.id 
                      ORDER BY p.created_at DESC LIMIT 6";
    $products_result = $conn->query($products_query);
    $products_available = ($products_result && $products_result->num_rows > 0);
    
    // Fetch categories - using the categories table instead of products table
    $categories_query = "SELECT * FROM categories LIMIT 6";
    $categories_result = $conn->query($categories_query);
    $categories_available = ($categories_result && $categories_result->num_rows > 0);
    
} catch (Exception $e) {
    $db_error = $e->getMessage();
    // Log the error but don't display to user
    error_log("Database error on index page: " . $db_error);
}

// Define some sample products for fallback display
$sample_products = [
    ['id' => 1, 'name' => 'Handcrafted Wooden Bowl', 'price' => 45.00, 'image' => 'placeholder.jpg', 'artisan_name' => 'Wood Craftsman'],
    ['id' => 2, 'name' => 'Hand-painted Ceramic Vase', 'price' => 65.00, 'image' => 'vase.jpg', 'artisan_name' => 'Ceramic Artist'],
    ['id' => 3, 'name' => 'Macramé Wall Hanging', 'price' => 38.50, 'image' => 'macrame.jpg', 'artisan_name' => 'Textile Designer'],
    ['id' => 4, 'name' => 'Leather Journal', 'price' => 28.00, 'image' => 'placeholder.jpg', 'artisan_name' => 'Leatherworker'],
];

// Predefined categories for fallback
$predefined_categories = [
    'Woodwork', 'Ceramics', 'Textile Arts', 'Leather Goods', 'Glass Art', 'Home Decor'
];
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
                // Use database products if available
                if ($products_available):
                    while($product = $products_result->fetch_assoc()): 
                        $image_url = !empty($product['image']) ? asset_url('assets/images/' . $product['image']) : asset_url('assets/images/placeholder.jpg');
                ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                         alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>" 
                         class="product-image">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name'] ?? 'Product Name'); ?></h3>
                        <p class="product-price">$<?php echo number_format($product['price'] ?? 0, 2); ?></p>
                        <p class="product-seller">By <?php echo htmlspecialchars($product['artisan_name'] ?? 'Artisan'); ?></p>
                    </div>
                    <a href="<?php echo url('/product.php?id=' . ($product['id'] ?? '')); ?>" class="btn btn-primary">View Details</a>
                </div>
                <?php 
                    endwhile;
                // Otherwise use sample products 
                else:
                    foreach ($sample_products as $product):
                        $image_url = !empty($product['image']) ? asset_url('assets/images/' . $product['image']) : asset_url('assets/images/placeholder.jpg');
                ?>
                <div class="product-card">
                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                         alt="<?php echo htmlspecialchars($product['name'] ?? 'Product'); ?>" 
                         class="product-image">
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <p class="product-seller">By <?php echo htmlspecialchars($product['artisan_name']); ?></p>
                    </div>
                    <a href="<?php echo url('/product.php?id=' . $product['id']); ?>" class="btn btn-primary">View Details</a>
                </div>
                <?php 
                    endforeach;
                endif; 
                ?>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories">
        <div class="container">
            <h2 class="section-title">Explore Categories</h2>
            <div class="categories-grid">
                <?php 
                // Use database categories if available
                if ($categories_available):
                    while($category = $categories_result->fetch_assoc()): 
                        $cat_name = htmlspecialchars($category['name'] ?? '');
                        $image_name = strtolower(str_replace(' ', '-', $cat_name)) . '.jpg';
                        $image_url = file_exists(__DIR__ . '/assets/images/categories/' . $image_name) ? 
                            asset_url('assets/images/categories/' . $image_name) : 
                            asset_url('assets/images/placeholder.jpg');
                ?>
                <div class="category-card">
                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                         alt="<?php echo htmlspecialchars($cat_name); ?>" 
                         class="category-image">
                    <h3><?php echo $cat_name; ?></h3>
                    <a href="<?php echo url('/category.php?name=' . urlencode($cat_name)); ?>" class="btn btn-secondary">Explore</a>
                </div>
                <?php 
                    endwhile;
                // Otherwise use predefined categories
                else:
                    foreach ($predefined_categories as $cat_name):
                        $image_name = strtolower(str_replace(' ', '-', $cat_name)) . '.jpg';
                        $image_url = file_exists(__DIR__ . '/assets/images/categories/' . $image_name) ? 
                            asset_url('assets/images/categories/' . $image_name) : 
                            asset_url('assets/images/placeholder.jpg');
                ?>
                <div class="category-card">
                    <img src="<?php echo htmlspecialchars($image_url); ?>" 
                         alt="<?php echo htmlspecialchars($cat_name); ?>" 
                         class="category-image">
                    <h3><?php echo $cat_name; ?></h3>
                    <a href="<?php echo url('/category.php?name=' . urlencode($cat_name)); ?>" class="btn btn-secondary">Explore</a>
                </div>
                <?php 
                    endforeach;
                endif;
                ?>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about">
        <div class="container">
            <h2 class="section-title">Our Story</h2>
            <div class="about-content">
                <div class="about-text">
                    <p>Artisan Alley is a marketplace dedicated to celebrating and promoting the work of independent craftspeople and artisans. We believe in the value of handmade goods and the stories behind them.</p>
                    <p>Each piece in our collection represents hours of skill, creativity, and passion. By shopping with us, you're not just buying a product - you're supporting a creator's dream and taking home a unique piece with character and soul.</p>
                </div>
                <a href="<?php echo url('/about.php'); ?>" class="btn btn-primary">Learn More About Us</a>
            </div>
        </div>
    </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
