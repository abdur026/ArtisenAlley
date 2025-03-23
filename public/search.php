<?php
session_start();
require_once __DIR__ . '/../config/paths.php';
require_once __DIR__ . '/../includes/breadcrumb.php';

// Debug information
$debug = isset($_GET['debug']) ? true : false;

// Set up default values in case database fails
$products = [];
$categories = [];
$featuredProducts = [];

try {
    require_once __DIR__ . '/../config/db.php';
    
    $keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    
    $sql = "SELECT * FROM products WHERE 1=1";
    $params = [];
    $types = "";
    
    if (!empty($keyword)) {
        $sql .= " AND (name LIKE ? OR description LIKE ?)";
        $searchParam = "%" . $keyword . "%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= "ss";
    }
    
    if (!empty($category)) {
        $sql .= " AND category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    
    // Get all unique categories for the sidebar
    $category_sql = "SELECT DISTINCT category FROM products ORDER BY category";
    $category_result = $conn->query($category_sql);
    $categories = $category_result ? $category_result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Get featured products
    $featuredQuery = "SELECT * FROM products ORDER BY created_at DESC LIMIT 4";
    $featuredResult = $conn->query($featuredQuery);
    $featuredProducts = $featuredResult ? $featuredResult->fetch_all(MYSQLI_ASSOC) : [];
    
} catch (Exception $e) {
    if ($debug) {
        echo '<div style="background: #f8d7da; padding: 20px; margin: 20px; border-radius: 5px;">';
        echo '<h3>Debug Error:</h3>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    // Set up some fallback categories 
    $categories = [
        ['category' => 'Woodwork'],
        ['category' => 'Ceramics'],
        ['category' => 'Textile Arts'],
        ['category' => 'Leather Goods'],
        ['category' => 'Glass Art'],
        ['category' => 'Home Decor']
    ];
    
    // Create some sample products
    $products = [
        ['id' => 1, 'name' => 'Handcrafted Wooden Bowl', 'price' => 45.00, 'image' => 'placeholder.jpg', 'artisan_name' => 'Wood Craftsman', 'description' => 'Beautiful hand-carved wooden bowl'],
        ['id' => 2, 'name' => 'Ceramic Vase', 'price' => 32.00, 'image' => 'placeholder.jpg', 'artisan_name' => 'Pottery Artist', 'description' => 'Hand-painted ceramic vase'],
        ['id' => 3, 'name' => 'Macramé Wall Hanging', 'price' => 28.50, 'image' => 'placeholder.jpg', 'artisan_name' => 'Textile Artist', 'description' => 'Handmade macramé wall hanging']
    ];
    
    $featuredProducts = $products;
}

// Include header after processing so we can set proper page title
$pageTitle = !empty($keyword) ? 'Search Results for "' . htmlspecialchars($keyword) . '"' : 'Explore Artisan Treasures';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Artisan Alley</title>
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/main.css'); ?>">
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
            background: url('/assets/images/pattern.png') repeat;
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
    <?php 
    include __DIR__ . '/../includes/header.php';
    
    $breadcrumbs = [
        ['name' => 'Home', 'url' => 'index.php']
    ];
    
    if (!empty($keyword)) {
        $breadcrumbs[] = ['name' => 'Search Results: "' . htmlspecialchars($keyword) . '"'];
    } elseif (!empty($category)) {
        $breadcrumbs[] = ['name' => 'Category: ' . htmlspecialchars($category)];
    } else {
        $breadcrumbs[] = ['name' => 'Explore'];
    }
    
    echo generate_breadcrumbs($breadcrumbs);
    ?>
    
    <div class="search-container">
        <div class="search-header">
            <h1>Explore Handcrafted Treasures</h1>
            <p>Discover unique artisan products made with passion and skill</p>
            
            <form action="<?php echo url('/search.php'); ?>" method="GET" class="search-form">
                <div class="search-input-group">
                    <input type="text" name="keyword" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Search for handcrafted items...">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-search"><i class="fas fa-search"></i> Search</button>
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
                                <img src="<?php echo asset_url('assets/images/' . (isset($product['image']) ? $product['image'] : 'placeholder.jpg')); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.src='<?php echo asset_url('assets/images/placeholder.jpg'); ?>'">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars($product['category'] ?? ''); ?></p>
                                <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                                <div class="product-actions">
                                    <a href="<?php echo url('/product.php?id=' . $product['id']); ?>" class="view-btn">View Details</a>
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
                        $icon = isset($categoryIcons[$cat['category']]) ? $categoryIcons[$cat['category']] : 'fas fa-tag';
                    ?>
                        <a href="<?php echo url('/search.php?category=' . urlencode($cat['category'])); ?>" class="category-tile">
                            <div class="category-icon"><i class="<?php echo $icon; ?>"></i></div>
                            <div class="category-name"><?php echo htmlspecialchars($cat['category']); ?></div>
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
                        Category: <?php echo htmlspecialchars($category); ?>
                        <a href="<?php echo url('/search.php' . (!empty($keyword) ? '?keyword=' . urlencode($keyword) : '')); ?>" class="remove-filter">✕</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="product-grid">
                <?php if (count($products) > 0): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <img src="<?php echo asset_url('assets/images/' . (isset($product['image']) ? $product['image'] : 'placeholder.jpg')); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     onerror="this.src='<?php echo asset_url('assets/images/placeholder.jpg'); ?>'">
                            </div>
                            <div class="product-info">
                                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p class="product-category"><?php echo htmlspecialchars($product['category'] ?? ''); ?></p>
                                <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                                <div class="product-actions">
                                    <a href="<?php echo url('/product.php?id=' . $product['id']); ?>" class="view-btn">View Details</a>
                                    <form action="add_to_cart.php" method="POST" class="add-to-cart-form">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <input type="hidden" name="quantity" value="1">
                                        <button type="submit" class="add-to-cart-btn"><i class="fas fa-shopping-cart"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>No products found</h3>
                        <p>Try adjusting your search or browse all our categories</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
