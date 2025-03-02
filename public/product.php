<?php
session_start();
require_once '../config/db.php';

function calculateAverageRating($reviews) {
    if ($reviews->num_rows === 0) return 0;
    $total = 0;
    $count = 0;
    while ($review = $reviews->fetch_assoc()) {
        $total += $review['rating'];
        $count++;
    }
    $reviews->data_seek(0); 
    return round($total / $count, 1);
}


$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;


$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();


if (!$product) {
    header("Location: index.php");
    exit;
}


$stmtReviews = $conn->prepare("SELECT r.*, u.name as reviewer_name FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
$stmtReviews->bind_param("i", $product_id);
$stmtReviews->execute();
$reviewsResult = $stmtReviews->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Artisan Alley</title>
    <link rel="stylesheet" href="/src/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #2c3e50;
        }

        .navbar {
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar a {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .navbar a:hover {
            background: #f8f9fa;
            color: #3498db;
        }

        .product-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .product-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .product-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .product-info {
            display: flex;
            flex-direction: column;
        }

        .product-category {
            color: #3498db;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1rem;
        }

        .product-name {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .product-price {
            font-size: 2rem;
            font-weight: 700;
            color: #e74c3c;
            margin-bottom: 1.5rem;
        }

        .product-description {
            color: #7f8c8d;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .quantity-label {
            font-weight: 600;
            color: #2c3e50;
        }

        .quantity-input {
            width: 100px;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1.1rem;
            text-align: center;
        }

        .add-to-cart {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 1.2rem 2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: auto;
        }

        .add-to-cart:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .product-meta {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
        }

        .success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #dcfce7;
            color: #16a34a;
            padding: 1rem 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
            z-index: 1000;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .product-details {
                grid-template-columns: 1fr;
                gap: 2rem;
            }

            .product-image {
                height: 300px;
            }

            .product-name {
                font-size: 2rem;
            }
        }

        .reviews-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 2rem;
        }

        .review-form-container {
            background: #f8fafc;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .review-form-container h3 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: #2c3e50;
        }

        .rating-input {
            margin-bottom: 1.5rem;
        }

        .stars {
            display: flex;
            flex-direction: row-reverse;
            gap: 0.5rem;
        }

        .stars input {
            display: none;
        }

        .stars label {
            cursor: pointer;
            color: #e0e0e0;
            font-size: 1.5rem;
            transition: all 0.2s ease;
        }

        .stars label:hover,
        .stars label:hover ~ label,
        .stars input:checked ~ label {
            color: #f1c40f;
        }

        .review-input textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            resize: vertical;
            transition: all 0.3s ease;
        }

        .review-input textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
        }

        .submit-review {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .submit-review:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .login-prompt {
            text-align: center;
            padding: 2rem;
            background: #f8fafc;
            border-radius: 15px;
            margin-bottom: 2rem;
        }

        .login-prompt a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
        }

        .reviews-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .average-rating {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 1px solid #e0e0e0;
        }

        .rating-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
        }

        .total-reviews {
            color: #7f8c8d;
        }

        .review-card {
            background: #f8fafc;
            border-radius: 15px;
            padding: 1.5rem;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .reviewer-info {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .reviewer-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .review-rating {
            display: flex;
            gap: 0.25rem;
        }

        .review-rating .fas.fa-star.filled {
            color: #f1c40f;
        }

        .review-rating .fas.fa-star {
            color: #e0e0e0;
        }

        .review-date {
            color: #7f8c8d;
            font-size: 0.9rem;
        }

        .review-comment {
            color: #2c3e50;
            line-height: 1.6;
            margin: 0;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="/public/index.php">Home</a>
            <div>
                <a href="/public/cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                    <?php if (!empty($_SESSION['cart'])): ?>
                        <span>(<?php echo array_sum($_SESSION['cart']); ?>)</span>
                    <?php endif; ?>
                </a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="/public/profile.php">Profile</a>
                    <a href="/public/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/public/login.php">Login</a>
                    <a href="/public/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="product-container">
        <div class="product-details">
            <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 class="product-image"
                 onerror="this.src='assets/images/placeholder.jpg'">
            
            <div class="product-info">
                <div class="product-category">
                    <i class="fas fa-tag"></i> <?php echo htmlspecialchars($product['category']); ?>
                </div>
                <h1 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                <p class="product-description"><?php echo htmlspecialchars($product['description']); ?></p>
                
                <form action="cart.php" method="POST" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <div class="quantity-selector">
                        <label for="quantity" class="quantity-label">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" value="1" min="1" class="quantity-input">
                    </div>

                    <button type="submit" class="add-to-cart">
                        <i class="fas fa-shopping-cart"></i>
                        Add to Cart
                    </button>
                </form>

                <div class="product-meta">
                    <div class="meta-item">
                        <i class="fas fa-truck"></i>
                        Free shipping on orders over $50
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-undo"></i>
                        30-day return policy
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-shield-alt"></i>
                        Secure checkout
                    </div>
                </div>
            </div>
        </div>

        <!-- Reviews Section -->
        <div class="reviews-section">
            <h2 class="section-title">Customer Reviews</h2>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <div class="review-form-container">
                    <h3>Write a Review</h3>
                    <form action="add_review.php" method="POST" class="review-form">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        
                        <div class="rating-input">
                            <label>Your Rating:</label>
                            <div class="stars">
                                <?php for($i = 5; $i >= 1; $i--): ?>
                                    <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
                                    <label for="star<?php echo $i; ?>"><i class="fas fa-star"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>

                        <div class="review-input">
                            <label for="comment">Your Review:</label>
                            <textarea id="comment" name="comment" required rows="4" placeholder="Share your thoughts about this product..."></textarea>
                        </div>

                        <button type="submit" class="submit-review">
                            <i class="fas fa-paper-plane"></i> Submit Review
                        </button>
                    </form>
                </div>
            <?php else: ?>
                <div class="login-prompt">
                    <p>Please <a href="login.php">log in</a> to write a review.</p>
                </div>
            <?php endif; ?>

            <div class="reviews-list">
                <?php 
                $average_rating = calculateAverageRating($reviewsResult);
                if($average_rating > 0):
                ?>
                    <div class="average-rating">
                        <span class="rating-number"><?php echo $average_rating; ?></span>
                        <div class="stars">
                            <?php
                            for($i = 1; $i <= 5; $i++) {
                                if($i <= $average_rating) {
                                    echo '<i class="fas fa-star"></i>';
                                } elseif($i - 0.5 <= $average_rating) {
                                    echo '<i class="fas fa-star-half-alt"></i>';
                                } else {
                                    echo '<i class="far fa-star"></i>';
                                }
                            }
                            ?>
                        </div>
                        <span class="total-reviews">(<?php echo $reviewsResult->num_rows; ?> reviews)</span>
                    </div>
                <?php endif; ?>

                <?php while($review = $reviewsResult->fetch_assoc()): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="reviewer-info">
                                <span class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                            </div>
                            <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['cart_message']; unset($_SESSION['cart_message']); ?>
        </div>
    <?php endif; ?>

    <script>
        
        const successMessage = document.querySelector('.success-message');
        if (successMessage) {
            setTimeout(() => {
                successMessage.style.opacity = '0';
                setTimeout(() => successMessage.remove(), 300);
            }, 3000);
        }

        
        document.querySelector('.add-to-cart-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('cart.php', {
                method: 'POST',
                body: formData
            }).then(response => {
                if (response.ok) {
                    const message = document.createElement('div');
                    message.className = 'success-message';
                    message.innerHTML = `
                        <i class="fas fa-check-circle"></i>
                        Item added to cart successfully!
                    `;
                    document.body.appendChild(message);
                    
                    setTimeout(() => {
                        message.style.opacity = '0';
                        setTimeout(() => message.remove(), 300);
                    }, 3000);
                }
            });
        });
    </script>
</body>
</html>