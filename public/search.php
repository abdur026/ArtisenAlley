<?php
session_start();
require_once '../config/db.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : PHP_FLOAT_MAX;

// Base query
$query = "SELECT * FROM products WHERE 1=1";
$params = [];
$types = "";

// Add search conditions
if (!empty($keyword)) {
    $query .= " AND (name LIKE ? OR description LIKE ?)";
    $searchTerm = "%" . $keyword . "%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

$query .= " AND price BETWEEN ? AND ?";
$params[] = $min_price;
$params[] = $max_price;
$types .= "dd";

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY created_at ASC";
        break;
    default:
        $query .= " ORDER BY created_at DESC";
}

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Artisan Products<?php echo !empty($keyword) ? ' - ' . htmlspecialchars($keyword) : ''; ?></title>
    <link rel="stylesheet" href="/src/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .search-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .search-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            padding: 3rem 2rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .search-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .search-input-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .search-input {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .search-btn {
            padding: 1rem 2rem;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .search-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .filters {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 2rem;
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-info {
            padding: 1.5rem;
        }

        .product-name {
            font-size: 1.2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .product-price {
            color: #e74c3c;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .product-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .view-btn {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .view-btn:hover {
            background: #2980b9;
        }

        .no-results {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .search-input-group {
                flex-direction: column;
            }
            
            .search-btn {
                width: 100%;
            }

            .filters {
                flex-direction: column;
            }

            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="/public/index.php">Home</a>
            <div>
                <a href="/public/cart.php">Cart</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="/public/profile.php">Profile</a>
                    <a href="/public/logout.php">Logout</a>
                <?php else: ?>
                    <a href="/public/login.php">Login</a>
                    <a href="/public/register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="search-container">
        <div class="search-header">
            <h1>Discover Artisan Treasures</h1>
            <form class="search-form" method="GET" action="/public/search.php">
                <div class="search-input-group">
                    <input type="text" name="keyword" class="search-input" 
                           placeholder="Search for handcrafted items..." 
                           value="<?php echo htmlspecialchars($keyword); ?>">
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label for="sort">Sort By</label>
                        <select name="sort" id="sort">
                            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="min_price">Min Price</label>
                        <input type="number" name="min_price" id="min_price" 
                               value="<?php echo $min_price; ?>" min="0" step="0.01">
                    </div>

                    <div class="filter-group">
                        <label for="max_price">Max Price</label>
                        <input type="number" name="max_price" id="max_price" 
                               value="<?php echo $max_price !== PHP_FLOAT_MAX ? $max_price : ''; ?>" min="0" step="0.01">
                    </div>
                </div>
            </form>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
            <div class="products-grid">
                <?php while ($product = $result->fetch_assoc()): ?>
                    <div class="product-card">
                        <img src="/public/assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image"
                             onerror="this.src='/public/assets/images/placeholder.jpg'">
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            <p class="product-description">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?>
                            </p>
                            <a href="/public/product.php?id=<?php echo $product['id']; ?>" class="view-btn">View Details</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search" style="font-size: 3rem; color: #95a5a6; margin-bottom: 1rem;"></i>
                <h2>No products found</h2>
                <p>Try adjusting your search criteria or browse our categories.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('select, input[type="number"]').forEach(element => {
            element.addEventListener('change', function() {
                this.form.submit();
            });
        });
    </script>
</body>
</html>
