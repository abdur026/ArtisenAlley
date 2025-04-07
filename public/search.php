<?php
session_start();
require_once '../config/db.php';
require_once '../includes/breadcrumb.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

try {
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];

    if (!empty($keyword)) {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $searchParam = "%" . $keyword . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
    }

    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Get categories
    $categoryQuery = "SELECT DISTINCT category FROM products ORDER BY category";
    $categoryStmt = $pdo->query($categoryQuery);
    $categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

    // Get featured products
    $featuredQuery = "SELECT * FROM products ORDER BY created_at DESC LIMIT 4";
    $featuredStmt = $pdo->query($featuredQuery);
    $featuredProducts = $featuredStmt->fetchAll();

} catch(PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $products = [];
    $categories = [];
    $featuredProducts = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Artisan Treasures - Artisan Alley</title>
    <link rel="stylesheet" href="/qrehman/ArtisenAlley/public/assets/css/main.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .search-header {
            text-align: center;
            margin-bottom: 3rem;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 3rem 2rem;
            border-radius: 15px;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .search-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('/qrehman/ArtisenAlley/public/assets/images/pattern.png') repeat;
            opacity: 0.1;
        }

        .search-header h1 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            position: relative;
        }

        .search-header p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
        }

        .search-form {
            max-width: 800px;
            margin: 0 auto;
            position: relative;
        }

        .search-input-group {
            display: flex;
            gap: 0.5rem;
            background: white;
            padding: 0.5rem;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .search-input-group input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
        }

        .search-input-group select {
            padding: 1rem;
            border: none;
            border-radius: 5px;
            background-color: #f5f5f5;
            font-size: 1rem;
            min-width: 150px;
        }

        .search-input-group button {
            padding: 1rem 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-input-group button:hover {
            background-color: var(--secondary-color);
        }

        .featured-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-color);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-color);
        }

        .category-tiles {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .category-tile {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 1.5rem;
        }

        .category-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .category-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .category-name {
            font-weight: 600;
            text-align: center;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-header h2 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .active-filter {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: var(--light-gray);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }

        .remove-filter {
            color: #666;
            transition: color 0.3s ease;
        }

        .remove-filter:hover {
            color: #e74c3c;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
        }

        .product-card {
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            height: 200px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-info h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .product-category {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
        }

        .product-price {
            font-weight: 700;
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .product-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .view-btn {
            padding: 0.6rem 1rem;
            background-color: var(--light-gray);
            color: var(--text-color);
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .add-to-cart-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .add-to-cart-btn:hover {
            background-color: var(--secondary-color);
            transform: scale(1.1);
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .no-results i {
            font-size: 3rem;
            color: #ccc;
            margin-bottom: 1rem;
        }

        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .no-results p {
            color: #666;
        }

        .featured-products {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .search-input-group {
                flex-direction: column;
            }
            
            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            
            .featured-products {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <header>
        <h1>Search Results</h1>
    </header>

    <?php
    // Generate breadcrumbs
    $breadcrumbs = [
        ['name' => 'Home', 'url' => 'index.php']
    ];
    
    if (!empty($search_query)) {
        $breadcrumbs[] = ['name' => 'Search Results: "' . htmlspecialchars($search_query) . '"'];
    } elseif (!empty($category)) {
        $breadcrumbs[] = ['name' => 'Category: ' . htmlspecialchars($category)];
    } else {
        $breadcrumbs[] = ['name' => 'All Products'];
    }
    
    echo generate_breadcrumbs($breadcrumbs);
    ?>

    <div class="search-container">
        <div class="search-header">
            <h1>Explore Handcrafted Treasures</h1>
            <p>Discover unique artisan products made with passion and skill</p>
            
            <form action="search.php" method="GET" class="search-form">
                <div class="search-input-group">
                    <input type="text" name="keyword" placeholder="Search for handcrafted items..." value="<?php echo htmlspecialchars($keyword); ?>">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php if (empty($keyword) && empty($category)): ?>
            <!-- Featured Products Section -->
            <section class="featured-section">
                <h2 class="section-title"><i class="fas fa-star"></i> Featured Products</h2>
                <div class="featured-products">
                    <?php foreach ($featuredProducts as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
                                    <form action="add_to_cart.php" method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn"><i class="fas fa-shopping-cart"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Browse by Category Section -->
            <section class="category-section">
                <h2 class="section-title"><i class="fas fa-th-large"></i> Browse by Category</h2>
                <div class="category-tiles">
                    <?php 
                    // Define category icons
                    $categoryIcons = [
                        'Home Decor' => 'fas fa-home',
                        'Kitchen' => 'fas fa-utensils',
                        'Bath & Body' => 'fas fa-bath',
                        'Accessories' => 'fas fa-tshirt',
                        'Stationery' => 'fas fa-pen',
                        'Home Fragrance' => 'fas fa-air-freshener'
                    ];
                    
                    foreach ($categories as $cat): 
                        $icon = isset($categoryIcons[$cat]) ? $categoryIcons[$cat] : 'fas fa-tag';
                    ?>
                        <a href="search.php?category=<?php echo urlencode($cat); ?>" class="category-tile">
                            <div class="category-icon"><i class="<?php echo $icon; ?>"></i></div>
                            <div class="category-name"><?php echo htmlspecialchars($cat); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <div class="search-results">
            <div class="results-header">
                <h2><?php echo count($products); ?> Results <?php echo !empty($keyword) ? 'for "' . htmlspecialchars($keyword) . '"' : ''; ?></h2>
                <?php if (!empty($category)): ?>
                    <div class="active-filter">
                        <span>Category: <?php echo htmlspecialchars($category); ?></span>
                        <a href="search.php?keyword=<?php echo urlencode($keyword); ?>" class="remove-filter"><i class="fas fa-times"></i></a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (empty($products)): ?>
                <div class="no-results">
                    <i class="fas fa-search"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your search or browse all our categories</p>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.src='assets/images/placeholder.jpg'">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                                <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                                <div class="product-actions">
                                    <a href="product.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
                                    <form action="add_to_cart.php" method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn"><i class="fas fa-shopping-cart"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
