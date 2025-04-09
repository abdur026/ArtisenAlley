<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to checkout.";
    header("Location: login.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: cart.php");
    exit;
}

// Check if shipping_addresses table exists, if not create it
$check_table = $conn->query("SHOW TABLES LIKE 'shipping_addresses'");
if ($check_table->num_rows == 0) {
    // Table doesn't exist, create it
    $create_shipping_table = "
        CREATE TABLE shipping_addresses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            address VARCHAR(255) NOT NULL,
            city VARCHAR(100) NOT NULL,
            state VARCHAR(100) NOT NULL,
            zip_code VARCHAR(20) NOT NULL,
            country VARCHAR(100) NOT NULL,
            phone VARCHAR(20),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )
    ";
    $conn->query($create_shipping_table);
    
    // Check if orders table needs updating
    $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'shipping_address_id'");
    if ($check_column->num_rows == 0) {
        // Add shipping_address_id and payment_method columns
        $conn->query("ALTER TABLE orders ADD COLUMN shipping_address_id INT");
        $conn->query("ALTER TABLE orders ADD COLUMN payment_method VARCHAR(50) DEFAULT 'credit_card'");
        $conn->query("ALTER TABLE orders ADD FOREIGN KEY (shipping_address_id) REFERENCES shipping_addresses(id)");
    }
    
    // Update status enum to include 'cancelled'
    $conn->query("ALTER TABLE orders MODIFY COLUMN status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending'");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $required_fields = ['full_name', 'email', 'address', 'city', 'state', 'zip_code', 'country', 'payment_method'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $_SESSION['error'] = "Please fill in all required fields: " . implode(", ", $missing_fields);
        $_SESSION['form_data'] = $_POST;
        header("Location: checkout.php");
        exit;
    }
    
    // Process the order
    $user_id = $_SESSION['user_id'];
    $grandTotal = 0;
    
    // Calculate total price
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $grandTotal += $product['price'] * $quantity;
        }
    }
    
    // Create shipping address in database
    $stmt = $conn->prepare("INSERT INTO shipping_addresses 
                            (user_id, full_name, address, city, state, zip_code, country, phone) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssss", 
                      $user_id, 
                      $_POST['full_name'],
                      $_POST['address'],
                      $_POST['city'],
                      $_POST['state'],
                      $_POST['zip_code'],
                      $_POST['country'],
                      $_POST['phone']);
    $stmt->execute();
    $shipping_id = $stmt->insert_id;
    
    // Check the actual column name in the orders table
    $check_column = $conn->query("SHOW COLUMNS FROM orders LIKE 'total_amount'");
    if ($check_column->num_rows > 0) {
        // Use total_amount column
        $stmt = $conn->prepare("INSERT INTO orders 
                                (user_id, total_amount, status, shipping_address_id, payment_method) 
                                VALUES (?, ?, 'pending', ?, ?)");
    } else {
        // Use total_price column
        $stmt = $conn->prepare("INSERT INTO orders 
                                (user_id, total_price, status, shipping_address_id, payment_method) 
                                VALUES (?, ?, 'pending', ?, ?)");
    }
    
    $stmt->bind_param("idis", $user_id, $grandTotal, $shipping_id, $_POST['payment_method']);
    
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        
        // Add order items
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            $stmtProd = $conn->prepare("SELECT price FROM products WHERE id = ?");
            $stmtProd->bind_param("i", $product_id);
            $stmtProd->execute();
            $resultProd = $stmtProd->get_result();
            if ($resultProd->num_rows > 0) {
                $product = $resultProd->fetch_assoc();
                $price = $product['price'];
                
                $stmtItem = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmtItem->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                $stmtItem->execute();
            }
        }
        
        // Clear the cart
        $_SESSION['cart'] = [];
        $_SESSION['success'] = "Order placed successfully!";
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit;
    } else {
        $_SESSION['error'] = "Error placing order: " . $stmt->error;
        header("Location: checkout.php");
        exit;
    }
}

// Get saved form data if available
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="/src/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
        }
        
        .checkout-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .checkout-form {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 2rem;
        }
        
        .order-summary {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            position: sticky;
            top: 2rem;
            max-height: calc(100vh - 4rem);
            overflow-y: auto;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .section-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .section-header i {
            margin-right: 0.5rem;
            color: #3498db;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .required::after {
            content: " *";
            color: #e74c3c;
        }
        
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        
        .payment-method {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method:hover {
            border-color: #3498db;
        }
        
        .payment-method.selected {
            border-color: #3498db;
            background-color: #f0f8ff;
        }
        
        .payment-method i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: #3498db;
        }
        
        .order-items {
            margin-bottom: 1.5rem;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
        }
        
        .order-item-image {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 1rem;
        }
        
        .order-item-details {
            flex: 1;
        }
        
        .order-item-name {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }
        
        .order-item-price {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .order-item-quantity {
            font-weight: 500;
            margin-left: 1rem;
        }
        
        .order-total {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .grand-total {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2c3e50;
            margin-top: 1rem;
        }
        
        .submit-btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }
        
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .back-link {
            display: inline-block;
            margin-bottom: 1.5rem;
            color: #3498db;
            text-decoration: none;
        }
        
        .back-link i {
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <a href="index.php">Home</a>
            <div>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="profile.php">Profile</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="checkout-container">
        <a href="cart.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Cart
        </a>
        
        <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <div class="checkout-grid">
            <div class="checkout-form">
                <form action="checkout.php" method="POST">
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-user"></i>
                            <h2>Contact Information</h2>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name" class="form-label required">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" 
                                       value="<?php echo isset($form_data['full_name']) ? htmlspecialchars($form_data['full_name']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email" class="form-label required">Email</label>
                                <input type="email" id="email" name="email" class="form-control" 
                                       value="<?php echo isset($form_data['email']) ? htmlspecialchars($form_data['email']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="form-control" 
                                   value="<?php echo isset($form_data['phone']) ? htmlspecialchars($form_data['phone']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-shipping-fast"></i>
                            <h2>Shipping Address</h2>
                        </div>
                        <div class="form-group">
                            <label for="address" class="form-label required">Street Address</label>
                            <input type="text" id="address" name="address" class="form-control" 
                                   value="<?php echo isset($form_data['address']) ? htmlspecialchars($form_data['address']) : ''; ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="city" class="form-label required">City</label>
                                <input type="text" id="city" name="city" class="form-control" 
                                       value="<?php echo isset($form_data['city']) ? htmlspecialchars($form_data['city']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="state" class="form-label required">State/Province</label>
                                <input type="text" id="state" name="state" class="form-control" 
                                       value="<?php echo isset($form_data['state']) ? htmlspecialchars($form_data['state']) : ''; ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="zip_code" class="form-label required">ZIP/Postal Code</label>
                                <input type="text" id="zip_code" name="zip_code" class="form-control" 
                                       value="<?php echo isset($form_data['zip_code']) ? htmlspecialchars($form_data['zip_code']) : ''; ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="country" class="form-label required">Country</label>
                                <input type="text" id="country" name="country" class="form-control" 
                                       value="<?php echo isset($form_data['country']) ? htmlspecialchars($form_data['country']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-header">
                            <i class="fas fa-money-check-alt"></i>
                            <h2>Payment Method</h2>
                        </div>
                        <div class="payment-methods">
                            <div class="payment-method" data-method="credit_card">
                                <i class="fas fa-credit-card"></i>
                                <p>Credit Card</p>
                            </div>
                            <div class="payment-method" data-method="paypal">
                                <i class="fab fa-paypal"></i>
                                <p>PayPal</p>
                            </div>
                            <div class="payment-method" data-method="cash_on_delivery">
                                <i class="fas fa-money-bill-wave"></i>
                                <p>Cash on Delivery</p>
                            </div>
                        </div>
                        <input type="hidden" id="payment_method" name="payment_method" value="<?php echo isset($form_data['payment_method']) ? htmlspecialchars($form_data['payment_method']) : 'credit_card'; ?>">
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-lock"></i> Complete Order
                    </button>
                </form>
            </div>
            
            <div class="order-summary">
                <div class="section-header">
                    <i class="fas fa-shopping-cart"></i>
                    <h2>Order Summary</h2>
                </div>
                
                <div class="order-items">
                    <?php 
                    $total = 0;
                    foreach ($_SESSION['cart'] as $product_id => $quantity): 
                        $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
                        $stmt->bind_param("i", $product_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        if ($result->num_rows > 0):
                            $product = $result->fetch_assoc();
                            $item_total = $product['price'] * $quantity;
                            $total += $item_total;
                    ?>
                    <div class="order-item">
                        <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="order-item-image"
                             onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="order-item-details">
                            <div class="order-item-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="order-item-price">$<?php echo number_format($product['price'], 2); ?></div>
                        </div>
                        <div class="order-item-quantity">x<?php echo $quantity; ?></div>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                
                <div class="order-total">
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Shipping</span>
                        <span>Free</span>
                    </div>
                    <div class="total-row grand-total">
                        <span>Total</span>
                        <span>$<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Payment method selection
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                // Remove selected class from all methods
                document.querySelectorAll('.payment-method').forEach(m => {
                    m.classList.remove('selected');
                });
                
                // Add selected class to clicked method
                this.classList.add('selected');
                
                // Update hidden input
                document.getElementById('payment_method').value = this.dataset.method;
            });
        });
        
        // Preselect payment method if set
        const savedMethod = document.getElementById('payment_method').value;
        if (savedMethod) {
            document.querySelector(`.payment-method[data-method="${savedMethod}"]`)?.classList.add('selected');
        } else {
            // Default to first method
            document.querySelector('.payment-method').classList.add('selected');
        }
    </script>
</body>
</html>
