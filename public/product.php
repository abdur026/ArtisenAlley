<?php
session_start();
require_once '../config/db.php';

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
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?></title>
    <link rel="stylesheet" href="../src/main.css">
</head>
<body>
    <header>
        <a href="index.php">Home</a>
    </header>
    <main>
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        <img src="../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="200">
        <p><?php echo htmlspecialchars($product['description']); ?></p>
        <p>Price: $<?php echo number_format($product['price'], 2); ?></p>
        <p>Category: <?php echo htmlspecialchars($product['category']); ?></p>
        <p>Stock: <?php echo intval($product['stock']); ?></p>

        <section id="reviews">
            <h2>Reviews</h2>
            <?php if ($reviewsResult->num_rows > 0): ?>
                <?php while ($review = $reviewsResult->fetch_assoc()): ?>
                    <div class="review">
                        <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                        <p>Rating: <?php echo intval($review['rating']); ?>/5</p>
                        <p><?php echo htmlspecialchars($review['comment']); ?></p>
                        <small><?php echo $review['created_at']; ?></small>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No reviews yet.</p>
            <?php endif; ?>
        </section>
    </main>
    <?php if (isset($_SESSION['user_id'])): ?>
    <section id="add-review">
        <h2>Leave a Review</h2>
        <form action="add_review.php" method="POST">
            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
            <label for="rating">Rating (1-5):</label>
            <input type="number" id="rating" name="rating" min="1" max="5" required>
            
            <label for="comment">Comment:</label>
            <textarea id="comment" name="comment" required></textarea>
            
            <button type="submit">Submit Review</button>
        </form>
    </section>
<?php else: ?>
    <p>Please <a href="login.php">log in</a> to leave a review.</p>
<?php endif; ?>
</body>
</html>