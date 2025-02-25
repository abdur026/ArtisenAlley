<?php
require_once '../config/db.php';

// Function to calculate average rating
function calculateAverageRating($reviews) {
    if ($reviews->num_rows === 0) return 0;
    $total = 0;
    $count = 0;
    while ($review = $reviews->fetch_assoc()) {
        $total += $review['rating'];
        $count++;
    }
    $reviews->data_seek(0); // Reset pointer
    return round($total / $count, 1);
}

// Check if product ID is provided in URL
if (!isset($_GET['id'])) {
    echo "No product specified.";
    exit;
}

$productId = intval($_GET['id']);

// Fetch product details
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$productResult = $stmt->get_result();

if ($productResult->num_rows === 0) {
    echo "Product not found.";
    exit;
}

$product = $productResult->fetch_assoc();

// Fetch reviews for the product
$stmtReviews = $conn->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmtReviews->bind_param("i", $productId);
$stmtReviews->execute();
$reviewsResult = $stmtReviews->get_result();
?>
<?php include '../includes/header.php'; ?>

<main class="product-page">
    <div class="container">
        <div class="product-details">
            <div class="product-gallery">
                <div class="main-image">
                    <img src="<?php echo ($product['image_url'] !== null && $product['image_url'] !== '') 
                        ? htmlspecialchars($product['image_url']) 
                        : '/assets/images/default-product.png'; ?>" 
                         alt="<?php echo htmlspecialchars($product['name'] ?? 'Product Image'); ?>"
                         class="product-image">
                </div>
            </div>
            
            <div class="product-info">
                <nav class="breadcrumb">
                    <a href="/index.php">Home</a> >
                    <a href="/search.php?category=<?php echo urlencode($product['category']); ?>">
                        <?php echo htmlspecialchars($product['category']); ?>
                    </a> >
                    <span><?php echo htmlspecialchars($product['name']); ?></span>
                </nav>

                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-meta">
                    <div class="price">$<?php echo number_format($product['price'], 2); ?></div>
                    <?php 
                    $averageRating = calculateAverageRating($reviewsResult);
                    if ($averageRating > 0): 
                    ?>
                    <div class="rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star<?php echo $i <= $averageRating ? '' : '-o'; ?>"></i>
                        <?php endfor; ?>
                        <span>(<?php echo $reviewsResult->num_rows; ?> reviews)</span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="product-description">
                    <?php echo htmlspecialchars($product['description']); ?>
                </div>

                <div class="product-actions">
                    <?php if ($product['stock'] > 0): ?>
                        <div class="stock-status in-stock">
                            <i class="fas fa-check-circle"></i> In Stock (<?php echo intval($product['stock']); ?> available)
                        </div>
                        <form action="/cart.php" method="POST" class="add-to-cart-form">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <div class="quantity-selector">
                                <button type="button" class="quantity-btn minus">-</button>
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                                <button type="button" class="quantity-btn plus">+</button>
                            </div>
                            <button type="submit" class="add-to-cart-btn">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="stock-status out-of-stock">
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <section class="reviews-section">
            <div class="container">
                <h2>Customer Reviews</h2>
                <div class="reviews-summary">
                    <div class="average-rating">
                        <div class="rating-number"><?php echo $averageRating; ?></div>
                        <div class="rating-stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $averageRating ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-count"><?php echo $reviewsResult->num_rows; ?> reviews</div>
                    </div>
                </div>

                <div class="reviews-list">
                    <?php if ($reviewsResult->num_rows > 0): ?>
                        <?php while ($review = $reviewsResult->fetch_assoc()): ?>
                            <div class="review-card">
                                <div class="review-header">
                                    <div class="reviewer-info">
                                        <i class="fas fa-user-circle"></i>
                                        <span class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                    </div>
                                    <div class="review-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="review-content">
                                    <?php echo htmlspecialchars($review['comment']); ?>
                                </div>
                                <div class="review-footer">
                                    <span class="review-date">
                                        <i class="far fa-calendar-alt"></i>
                                        <?php echo date('F j, Y', strtotime($review['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-reviews">
                            <i class="far fa-comment-alt"></i>
                            <p>No reviews yet. Be the first to review this product!</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="add-review-section">
                        <h3>Write a Review</h3>
                        <form action="add_review.php" method="POST" class="review-form">
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                            
                            <div class="rating-input">
                                <label>Your Rating:</label>
                                <div class="star-rating">
                                    <?php for ($i = 5; $i >= 1; $i--): ?>
                                        <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                        <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="comment">Your Review:</label>
                                <textarea id="comment" name="comment" required 
                                          placeholder="Share your thoughts about this product..."></textarea>
                            </div>

                            <button type="submit" class="submit-review-btn">
                                <i class="fas fa-paper-plane"></i> Submit Review
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="login-prompt">
                        <p><i class="fas fa-lock"></i> Please <a href="login.php">log in</a> to leave a review.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</main>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Quantity selector functionality
    const quantityInput = document.querySelector('.quantity-selector input');
    const minusBtn = document.querySelector('.quantity-btn.minus');
    const plusBtn = document.querySelector('.quantity-btn.plus');
    const maxStock = <?php echo $product['stock']; ?>;

    if (minusBtn && plusBtn && quantityInput) {
        minusBtn.addEventListener('click', () => {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue > 1) {
                quantityInput.value = currentValue - 1;
            }
        });

        plusBtn.addEventListener('click', () => {
            const currentValue = parseInt(quantityInput.value);
            if (currentValue < maxStock) {
                quantityInput.value = currentValue + 1;
            }
        });
    }
});
</script>