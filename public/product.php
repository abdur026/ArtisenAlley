<?php
session_start();
require_once '../config/db.php';
require_once '../includes/breadcrumb.php';
require_once '../includes/utils/csrf.php';

// Enable debugging temporarily
$_SESSION['debug'] = true;

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


$stmtReviews = $conn->prepare("SELECT r.*, u.name as reviewer_name 
                              FROM reviews r 
                              JOIN users u ON r.user_id = u.id 
                              WHERE r.product_id = ? 
                              ORDER BY r.created_at DESC");
$stmtReviews->bind_param("i", $product_id);
$stmtReviews->execute();
$reviewsResult = $stmtReviews->get_result();

// Add debugging information
if (isset($_SESSION['debug'])) {
    echo "<pre>";
    echo "Product ID: " . $product_id . "\n";
    echo "Number of reviews: " . $reviewsResult->num_rows . "\n";
    while ($row = $reviewsResult->fetch_assoc()) {
        echo "Review by: " . htmlspecialchars($row['reviewer_name']) . 
             " (User ID: " . $row['user_id'] . ")\n";
    }
    echo "</pre>";
    $reviewsResult->data_seek(0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - Artisan Alley</title>
    <link rel="stylesheet" href="../src/main.css">
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

        .star-rating {
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
            gap: 0.5rem;
        }

        .star-rating input {
            display: none;
        }

        .star-rating label {
            cursor: pointer;
            color: #e0e0e0;
            font-size: 1.5rem;
            transition: all 0.2s ease;
        }

        .star-rating label:hover,
        .star-rating label:hover ~ label,
        .star-rating input:checked ~ label {
            color: #f1c40f;
        }

        .rating-select {
            margin-bottom: 1.5rem;
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
            <div>
                <a href="index.php">Home</a>
            </div>
            <div>
                <a href="cart.php">
                    <i class="fas fa-shopping-cart"></i> Cart
                </a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="/profile.php">Profile</a>
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="product-container">
        <?php
        // Generate breadcrumbs
        $category_url = "search.php?category=" . urlencode($product['category']);
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'index.php'],
            ['name' => $product['category'], 'url' => $category_url],
            ['name' => $product['name']]
        ];
        echo generate_breadcrumbs($breadcrumbs);
        ?>
        
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
                    <form id="review-form" class="review-form">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <?php echo csrf_token_field('review_form'); ?>
                        <div class="rating-select">
                            <label>Your Rating:</label>
                            <div class="star-rating">
                                <input type="radio" id="star5" name="rating" value="5" required>
                                <label for="star5" title="5 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star4" name="rating" value="4">
                                <label for="star4" title="4 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star3" name="rating" value="3">
                                <label for="star3" title="3 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star2" name="rating" value="2">
                                <label for="star2" title="2 stars"><i class="fas fa-star"></i></label>
                                <input type="radio" id="star1" name="rating" value="1">
                                <label for="star1" title="1 star"><i class="fas fa-star"></i></label>
                            </div>
                        </div>
                        <div class="review-comment-field">
                            <label for="comment">Your Review:</label>
                            <textarea id="comment" name="comment" required placeholder="Share your experience with this product..."></textarea>
                        </div>
                        <button type="submit" class="submit-review">Submit Review</button>
                        <p id="review-status-message" class="review-status"></p>
                    </form>
                </div>
            <?php else: ?>
                <div class="login-prompt">
                    <p><a href="login.php">Sign in</a> to leave a review.</p>
                </div>
            <?php endif; ?>

            <div id="reviews-list" class="reviews-list">
                <?php if($reviewsResult && $reviewsResult->num_rows > 0): ?>
                    <?php while($review = $reviewsResult->fetch_assoc()):
                        // Store the most recent review timestamp for AJAX polling
                        if (!isset($latest_review_time) || strtotime($review['created_at']) > strtotime($latest_review_time)) {
                            $latest_review_time = $review['created_at'];
                        }
                    ?>
                        <div class="review" data-review-id="<?php echo $review['id']; ?>">
                            <div class="review-header">
                                <div class="reviewer-info">
                                    <img src="<?php 
                                        if ($review['profile_image']) {
                                            echo '/uploads/' . htmlspecialchars($review['profile_image']);
                                        } else {
                                            echo '/assets/images/default-avatar.png';
                                        }
                                    ?>" 
                                        alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>" 
                                        class="reviewer-image">
                                    <div class="reviewer-details">
                                        <h4 class="reviewer-name"><?php echo htmlspecialchars($review['reviewer_name']); ?></h4>
                                        <div class="review-rating">
                                            <?php for($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo ($i <= $review['rating']) ? 'filled' : ''; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                                    </div>
                                </div>
                            </div>
                            <p class="review-comment"><?php echo htmlspecialchars($review['comment']); ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-reviews">No reviews yet. Be the first to review this product!</p>
                <?php endif; ?>
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
        // Initialize latest review timestamp from PHP
        let latestReviewTimestamp = '<?php echo isset($latest_review_time) ? $latest_review_time : '0000-00-00 00:00:00'; ?>';
        const productId = <?php echo $product['id']; ?>;
        
        // Function to add stars based on rating
        function getStarsHtml(rating) {
            let starsHtml = '';
            for (let i = 1; i <= 5; i++) {
                starsHtml += `<i class="fas fa-star ${i <= rating ? 'filled' : ''}"></i>`;
            }
            return starsHtml;
        }
        
        // Function to create review HTML
        function createReviewElement(review) {
            const reviewDate = new Date(review.created_at);
            const formattedDate = reviewDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            
            let imagePath = '';
            if (review.profile_image) {
                imagePath = '/uploads/' + review.profile_image;
            } else {
                imagePath = '/assets/images/default-avatar.png';
            }
            
            return `
                <div class="review" data-review-id="${review.id}">
                    <div class="review-header">
                        <div class="reviewer-info">
                            <img src="${imagePath}" 
                                alt="${review.reviewer_name}" 
                                class="reviewer-image">
                            <div class="reviewer-details">
                                <h4 class="reviewer-name">${review.reviewer_name}</h4>
                                <div class="review-rating">
                                    ${getStarsHtml(review.rating)}
                                </div>
                                <div class="review-date">${formattedDate}</div>
                            </div>
                        </div>
                    </div>
                    <p class="review-comment">${review.comment}</p>
                </div>
            `;
        }
        
        // Add event listener to the review form
        document.getElementById('review-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const statusElement = document.getElementById('review-status-message');
            
            // Set up the AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'add_review.php', true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('X-CSRF-TOKEN', formData.get('csrf_token'));
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            // Add the new review to the top of the list
                            const reviewsList = document.getElementById('reviews-list');
                            const noReviewsElement = document.querySelector('.no-reviews');
                            
                            // Remove "no reviews" message if it exists
                            if (noReviewsElement) {
                                noReviewsElement.remove();
                            }
                            
                            // Add the new review to the top of the list
                            const reviewElement = createReviewElement(response.review);
                            reviewsList.insertAdjacentHTML('afterbegin', reviewElement);
                            
                            // Update the latest timestamp
                            latestReviewTimestamp = response.review.created_at;
                            
                            // Reset the form
                            document.getElementById('review-form').reset();
                            statusElement.textContent = 'Review submitted successfully!';
                            statusElement.className = 'review-status success';
                            
                            // Clear the status message after 3 seconds
                            setTimeout(() => {
                                statusElement.textContent = '';
                                statusElement.className = 'review-status';
                            }, 3000);
                        } else {
                            statusElement.textContent = response.error || 'An error occurred while submitting your review.';
                            statusElement.className = 'review-status error';
                        }
                    } catch (e) {
                        statusElement.textContent = 'An error occurred while processing the server response.';
                        statusElement.className = 'review-status error';
                    }
                }
            };
            
            xhr.onerror = function() {
                statusElement.textContent = 'Network error occurred while submitting your review.';
                statusElement.className = 'review-status error';
            };
            
            xhr.send(formData);
        });
        
        // Function to check for new reviews
        function checkForNewReviews() {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', `get_reviews.php?product_id=${productId}&last_timestamp=${encodeURIComponent(latestReviewTimestamp)}`, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success && response.reviews.length > 0) {
                            const reviewsList = document.getElementById('reviews-list');
                            const noReviewsElement = document.querySelector('.no-reviews');
                            
                            // Remove "no reviews" message if it exists
                            if (noReviewsElement) {
                                noReviewsElement.remove();
                            }
                            
                            // Add new reviews to the list
                            response.reviews.forEach(review => {
                                // Skip if we already have this review displayed
                                const existingReview = document.querySelector(`.review[data-review-id="${review.id}"]`);
                                if (!existingReview) {
                                    const reviewElement = createReviewElement(review);
                                    reviewsList.insertAdjacentHTML('afterbegin', reviewElement);
                                    
                                    // Update latest timestamp if newer
                                    if (new Date(review.created_at) > new Date(latestReviewTimestamp)) {
                                        latestReviewTimestamp = review.created_at;
                                    }
                                }
                            });
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                }
            };
            
            xhr.send();
        }
        
        // Check for new reviews every 10 seconds
        setInterval(checkForNewReviews, 10000);
        
        // Initial check for any new reviews that might have been added since page load
        setTimeout(checkForNewReviews, 1000);
        
        // Add to cart functionality
        document.querySelector('.add-to-cart-form')?.addEventListener('submit', function(e) {
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