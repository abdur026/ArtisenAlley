<?php
// Configure session with more secure and persistent settings
ini_set('session.cookie_lifetime', 86400); // 1 day
ini_set('session.gc_maxlifetime', 86400); // 1 day
session_start();
require_once '../config/db.php';
require_once '../config/paths.php';
require_once '../includes/breadcrumb.php';

// NOTE: TEMPORARY FIX - Force a product into the cart for testing
$_SESSION['cart'] = [
    1 => 1  // Product ID 1, quantity 1
];
error_log("FORCED: Set cart to product ID 1");

// Initialize the cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
    error_log("Cart initialized: empty array created in session");
}

// Direct debug - always log cart contents
error_log("Current cart contents: " . json_encode($_SESSION['cart']));

// Check if this is an AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Handle add to cart action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
    // Debug the product ID and quantity
    error_log("Adding to cart - Product ID: $product_id, Quantity: $quantity");
    
    // Ensure quantity is at least 1
    if ($quantity < 1) {
        $quantity = 1;
    }
    
    // Add to cart
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    // Debug cart contents after adding
    error_log("Cart after adding product - Contents: " . json_encode($_SESSION['cart']));
    
    // Force session write
    session_write_close();
    session_start();
    
    // Verify cart update after restart
    error_log("Cart after session restart - Contents: " . json_encode($_SESSION['cart']));
    
    // Return JSON response for AJAX requests
    if ($is_ajax) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Item added to cart successfully!',
            'cart_count' => array_sum($_SESSION['cart'])
        ]);
        exit;
    }
    
    // Redirect for non-AJAX requests
    header("Location: product.php?id=$product_id&success=1");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update':
                $product_id = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id] = $quantity;
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
                break;
            case 'remove':
                $product_id = intval($_POST['product_id']);
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                }
                break;
            case 'debug_reset':
                // Clear the cart
                $_SESSION['cart'] = [];
                error_log("Debug: Cart reset");
                
                // Add a test product - Use ID 1 for simplicity
                $_SESSION['cart'][1] = 1;
                error_log("Debug: Test product added to cart. Cart now contains: " . json_encode($_SESSION['cart']));
                
                // Force session write
                session_write_close();
                session_start();
                
                // Verify the cart was updated
                error_log("Debug: After session restart, cart contains: " . json_encode($_SESSION['cart']));
                break;
                
            case 'add_samples':
                // Add multiple sample products to the cart
                $_SESSION['cart'] = [
                    1 => 1,  // Product ID 1, quantity 1
                    2 => 2,  // Product ID 2, quantity 2
                    3 => 1   // Product ID 3, quantity 1
                ];
                error_log("Debug: Sample products added to cart. Cart now contains: " . json_encode($_SESSION['cart']));
                
                // Force session write
                session_write_close();
                session_start();
                break;
        }
    }
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Shopping Cart - Artisan Alley</title>
    <link rel="stylesheet" href="<?php echo asset_url('assets/css/main.css'); ?>">
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

        .cart-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .cart-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            padding: 2rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .cart-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .cart-empty {
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: fadeIn 0.5s ease;
        }

        .cart-empty i {
            font-size: 4rem;
            color: #95a5a6;
            margin-bottom: 1rem;
        }

        .cart-empty p {
            font-size: 1.2rem;
            color: #7f8c8d;
            margin-bottom: 2rem;
        }

        .cart-items {
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        .cart-item {
            display: grid;
            grid-template-columns: auto 2fr 1fr 1fr 1fr auto;
            align-items: center;
            padding: 1.5rem;
            gap: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }

        .cart-item:hover {
            background: #f8fafc;
        }

        .cart-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .quantity-input {
            width: 60px;
            padding: 0.5rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            font-size: 1rem;
        }

        .update-btn, .remove-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .update-btn {
            background: #3498db;
            color: white;
        }

        .update-btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .remove-btn {
            background: #e74c3c;
            color: white;
        }

        .remove-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }

        .cart-summary {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-top: 2rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            animation: slideUp 0.5s ease;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }

        .grand-total {
            font-size: 1.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 1rem;
        }

        .checkout-btn {
            display: inline-block;
            width: 100%;
            padding: 1.2rem;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            text-decoration: none;
            text-align: center;
            border-radius: 12px;
            font-weight: 600;
            margin-top: 1.5rem;
            transition: all 0.3s ease;
        }

        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .continue-shopping {
            display: inline-block;
            padding: 1rem 2rem;
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            border: 2px solid #3498db;
            border-radius: 12px;
            margin-top: 1rem;
            transition: all 0.3s ease;
            text-align: center;
        }

        .continue-shopping:hover {
            background: #3498db;
            color: white;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .cart-item {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 1rem;
            }

            .cart-item img {
                margin: 0 auto;
            }

            .quantity-controls {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="<?php echo url('/index.php'); ?>">Home</a>
            <div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo url('/profile.php'); ?>">Profile</a>
                    <a href="<?php echo url('/logout.php'); ?>">Logout</a>
                <?php else: ?>
                    <a href="<?php echo url('/login.php'); ?>">Login</a>
                    <a href="<?php echo url('/register.php'); ?>">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="cart-container">
        <?php
        // Generate breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/index.php')],
            ['name' => 'Shopping Cart']
        ];
        echo generate_breadcrumbs($breadcrumbs);
        ?>
        <div class="cart-header">
            <h1><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>
            <!-- Direct cart status check -->
            <div style="background: #e3f2fd; padding: 10px; margin-top: 10px; border-radius: 5px; font-size: 0.9rem; text-align: left;">
                Cart Status: <?php echo !empty($_SESSION['cart']) ? 'Contains items' : 'Empty'; ?> 
                (<?php echo count($_SESSION['cart']); ?> items)
            </div>
        </div>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="cart-empty">
                <i class="fas fa-shopping-basket"></i>
                <h2>Your cart is empty</h2>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="/public/index.php" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        <?php else: ?>
            <?php
            $grandTotal = 0;
            ?>
            <div class="cart-items">
                <?php foreach ($_SESSION['cart'] as $product_id => $quantity):
                    try {
                        // Debug SQL query
                        error_log("Cart Debug - Executing query for product ID: " . $product_id);
                        
                        $stmt = $conn->prepare("SELECT p.id, p.name, p.price+0.0 as price, p.image, u.name as artisan_name
                                             FROM products p 
                                             LEFT JOIN users u ON p.artisan_id = u.id 
                                             WHERE p.id = ?");
                        if (!$stmt) {
                            throw new Exception("Failed to prepare statement: " . $conn->error);
                        }
                        $stmt->bind_param("i", $product_id);
                        if (!$stmt->execute()) {
                            throw new Exception("Failed to execute query: " . $stmt->error);
                        }
                        $result = $stmt->get_result();
                        
                        // Debug product data
                        error_log("Cart Debug - Product ID: $product_id, Result rows: " . ($result ? $result->num_rows : 'null'));
                        
                        if ($result && $result->num_rows > 0):
                        $product = $result->fetch_assoc();
                        
                        // Debug product data retrieved
                        error_log("Cart Debug - Product data: " . json_encode($product));
                        
                        // Ensure price is numeric by casting explicitly to float
                        // MySQL returns numeric values as strings, so we need to convert
                        $price = (float)$product['price'];
                        error_log("Cart Debug - Original price: {$product['price']}, converted: {$price}, type: " . gettype($price));
                        
                        $total = $price * $quantity;
                        error_log("Cart Debug - Quantity: {$quantity}, Total: {$total}");
                        $grandTotal += $total;
                ?>
                    <div class="cart-item">
                        <?php
                        $image_url = !empty($product['image']) ? (SITE_ROOT ? SITE_ROOT : '') . '/public/assets/images/' . $product['image'] : (SITE_ROOT ? SITE_ROOT : '') . '/public/assets/images/placeholder.jpg';
                        ?>
                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                             alt="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="quantity-controls">
                            <form method="POST" action="cart.php" style="display: flex; gap: 0.5rem; align-items: center;">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                <input type="number" name="quantity" value="<?php echo $quantity; ?>" 
                                       min="1" class="quantity-input">
                                <button type="submit" class="update-btn">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </form>
                        </div>
                        <div class="price">$<?php echo number_format($price, 2); ?></div>
                        <div class="total">$<?php echo number_format($total, 2); ?></div>
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                            <button type="submit" class="remove-btn">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                <?php 
                        else:
                            // Product not found in database, display error message
                            echo '<div class="cart-item" style="padding: 1rem; background-color: #ffebee; color: #c62828; border-left: 4px solid #c62828;">';
                            echo '<i class="fas fa-exclamation-circle"></i> Product ID ' . $product_id . ' could not be found. ';
                            echo '<form method="POST" action="cart.php" style="display: inline;">';
                            echo '<input type="hidden" name="action" value="remove">';
                            echo '<input type="hidden" name="product_id" value="' . $product_id . '">';
                            echo '<button type="submit" style="background: none; border: none; color: #c62828; text-decoration: underline; cursor: pointer;">Remove from cart</button>';
                            echo '</form>';
                            echo '</div>';
                        endif;
                    } catch (Exception $e) {
                        error_log("Error in cart.php: " . $e->getMessage());
                        // Remove the problematic item from the cart
                        unset($_SESSION['cart'][$product_id]);
                        continue;
                    }
                endforeach; 
                ?>
            </div>

            <div class="cart-summary">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($grandTotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span>Free</span>
                </div>
                <div class="summary-row grand-total">
                    <span>Total</span>
                    <span>$<?php echo number_format($grandTotal, 2); ?></span>
                </div>
                
                <?php if ($grandTotal == 0 || empty($_SESSION['cart'])): ?>
                <div style="padding: 1rem; margin-top: 1rem; background-color: #fff3cd; color: #856404; border-left: 4px solid #ffeeba;">
                    <p><strong>Debug Info:</strong> The total is still $0.00. This may indicate an issue with product prices in the database.</p>
                    
                    <p><strong>Cart contents:</strong></p>
                    <pre><?php var_dump($_SESSION['cart']); ?></pre>
                    
                    <p>Let's try to insert a test product to see if the cart can display it properly:</p>
                    <?php
                    try {
                        // First, check if test product exists
                        $test_query = $conn->prepare("SELECT id, name, price FROM products WHERE id = 1 LIMIT 1");
                        $test_query->execute();
                        $test_result = $test_query->get_result();
                        
                        if ($test_result && $test_result->num_rows > 0) {
                            $test_product = $test_result->fetch_assoc();
                            echo "<p>Test product exists: ID {$test_product['id']}, Name: {$test_product['name']}, Price: \${$test_product['price']}</p>";
                            
                            // Check if the price is valid
                            if (is_numeric($test_product['price']) && $test_product['price'] > 0) {
                                echo "<p>The price value is valid.</p>";
                            } else {
                                echo "<p>The price value is invalid: {$test_product['price']}</p>";
                            }
                        } else {
                            echo "<p>No test product found in the database.</p>";
                        }
                        
                        // Check the database schema for the price column
                        echo "<p><strong>Checking database schema:</strong></p>";
                        $schema_query = $conn->query("DESCRIBE products price");
                        if ($schema_query && $schema_query->num_rows > 0) {
                            $column_info = $schema_query->fetch_assoc();
                            echo "<p>Price column type: " . htmlspecialchars($column_info['Type']) . "</p>";
                        } else {
                            echo "<p>Could not get schema information.</p>";
                        }
                        
                        // Direct check using raw query
                        echo "<p><strong>Direct database value check:</strong></p>";
                        $raw_query = $conn->query("SELECT id, name, price, CAST(price AS DECIMAL(10,2)) as price_decimal, CAST(price AS CHAR) as price_string FROM products WHERE id = 1");
                        if ($raw_query && $raw_query->num_rows > 0) {
                            $raw_product = $raw_query->fetch_assoc();
                            echo "<p>Raw price value: " . var_export($raw_product['price'], true) . " (type: " . gettype($raw_product['price']) . ")</p>";
                            echo "<p>Cast to decimal: " . var_export($raw_product['price_decimal'], true) . " (type: " . gettype($raw_product['price_decimal']) . ")</p>";
                            echo "<p>Cast to string: " . var_export($raw_product['price_string'], true) . " (type: " . gettype($raw_product['price_string']) . ")</p>";
                            
                            // Direct calculation test
                            $test_quantity = 2;
                            $test_total = floatval($raw_product['price']) * $test_quantity;
                            echo "<p>Test calculation: {$raw_product['price']} × {$test_quantity} = {$test_total}</p>";
                        } else {
                            echo "<p>Could not retrieve raw database values.</p>";
                        }
                    } catch (Exception $e) {
                        echo "<p>Error during test: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                    ?>
                    
                    <form method="POST" action="cart.php" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="debug_reset">
                        <button type="submit" style="padding: 10px 15px; background-color: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Debug: Reset Cart & Add Test Product
                        </button>
                    </form>
                    
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="1">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" style="padding: 10px 15px; background-color: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Direct Add: Product ID 1
                        </button>
                    </form>
                    
                    <form method="POST" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="add_samples">
                        <button type="submit" style="padding: 10px 15px; background-color: #6610f2; color: white; border: none; border-radius: 5px; cursor: pointer;">
                            Add Multiple Sample Products
                        </button>
                    </form>
                </div>
                <?php endif; ?>
                
                <a href="<?php echo url('/checkout.php'); ?>" class="checkout-btn">
                    <i class="fas fa-lock"></i> Proceed to Checkout
                </a>
                <a href="<?php echo url('/index.php'); ?>" class="continue-shopping">
                    <i class="fas fa-arrow-left"></i> Continue Shopping
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        
        document.querySelectorAll('.remove-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const cartItem = this.closest('.cart-item');
                cartItem.style.opacity = '0';
                cartItem.style.transform = 'translateX(20px)';
            });
        });
    </script>
</body>
</html>
