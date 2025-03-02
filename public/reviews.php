<?php
session_start();
require_once '../config/db.php';

// Fetch all reviews with user and product information
$reviews_query = "SELECT r.*, p.name as product_name, p.image as product_image, u.name as reviewer_name 
                FROM reviews r 
                JOIN products p ON r.product_id = p.id 
                JOIN users u ON r.user_id = u.id 
                ORDER BY r.created_at DESC";
$reviews_result = $conn->query($reviews_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Reviews - Artisan Alley</title>
    <link rel="stylesheet" href="/assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .reviews-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
        }

        .reviews-header {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .reviews-header h1 {
            font-size: 2.5rem;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .reviews-header p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .reviews-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .review-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-5px);
        }

        .review-product {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }

        .review-product img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .reviewer-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .reviewer-name {
            font-weight: 600;
            color: #3498db;
        }

        .rating {
            display: flex;
            gap: 0.25rem;
        }

        .rating .fas.fa-star.filled {
            color: #f1c40f;
        }

        .rating .fas.fa-star {
            color: #e0e0e0;
        }

        .review-text {
            color: #666;
            line-height: 1.6;
            margin: 1rem 0;
        }

        .review-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .review-date {
            color: #888;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-product {
            color: #3498db;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .view-product:hover {
            color: #2980b9;
        }

        .no-reviews {
            grid-column: 1 / -1;
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .no-reviews i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-reviews p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .filter-button {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            background: white;
            color: #2c3e50;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-button:hover,
        .filter-button.active {
            background: #3498db;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="reviews-container">
        <div class="reviews-header">
            <h1>Community Reviews</h1>
            <p>See what our community thinks about our artisan products</p>
        </div>

        <div class="filters">
            <button class="filter-button active" data-rating="all">All Reviews</button>
            <button class="filter-button" data-rating="5">5 Stars</button>
            <button class="filter-button" data-rating="4">4 Stars</button>
            <button class="filter-button" data-rating="3">3 Stars</button>
            <button class="filter-button" data-rating="2">2 Stars</button>
            <button class="filter-button" data-rating="1">1 Star</button>
        </div>

        <div class="reviews-grid">
            <?php if ($reviews_result->num_rows > 0):
                while($review = $reviews_result->fetch_assoc()): ?>
                <div class="review-card" data-rating="<?php echo $review['rating']; ?>">
                    <div class="review-product">
                        <img src="assets/images/<?php echo htmlspecialchars($review['product_image']); ?>" 
                             alt="<?php echo htmlspecialchars($review['product_name']); ?>"
                             class="product-image"
                             onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($review['product_name']); ?></h3>
                            <div class="reviewer-info">
                                <span class="reviewer-name">By <?php echo htmlspecialchars($review['reviewer_name']); ?></span>
                            </div>
                            <div class="rating">
                                <?php for($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                    <p class="review-text"><?php echo htmlspecialchars($review['comment']); ?></p>
                    <div class="review-meta">
                        <span class="review-date">
                            <i class="fas fa-calendar"></i>
                            <?php echo date('M d, Y', strtotime($review['created_at'])); ?>
                        </span>
                        <a href="product.php?id=<?php echo $review['product_id']; ?>" class="view-product">
                            View Product <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php 
                endwhile;
            else: ?>
                <div class="no-reviews">
                    <i class="fas fa-comment-alt"></i>
                    <p>No reviews have been written yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        // Filter reviews by rating
        document.querySelectorAll('.filter-button').forEach(button => {
            button.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-button').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                const rating = this.dataset.rating;
                const reviews = document.querySelectorAll('.review-card');

                reviews.forEach(review => {
                    if (rating === 'all' || review.dataset.rating === rating) {
                        review.style.display = 'block';
                    } else {
                        review.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>
</html> 