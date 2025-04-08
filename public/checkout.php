<?php
session_start();
require_once '../config/db.php';
require_once '../includes/breadcrumb.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Please log in to checkout.";
    header("Location: login.php");
    exit;
}

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    header("Location: cart.php");
    exit;
}

// Get user information
    $user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Calculate cart total
    $grandTotal = 0;
$items = [];
  
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
        $total = $product['price'] * $quantity;
        $grandTotal += $total;
        
        $items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'total' => $total,
            'image' => $product['image']
        ];
    }
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['full_name', 'email', 'address', 'city', 'state', 'zip', 'country', 'payment_method'];
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }
    
    // If no errors, process the order
    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create shipping record
            $stmt = $conn->prepare("INSERT INTO shipping_details (user_id, full_name, address, city, state, zip, country, phone) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", 
                $user_id, 
                $_POST['full_name'], 
                $_POST['address'], 
                $_POST['city'], 
                $_POST['state'], 
                $_POST['zip'], 
                $_POST['country'], 
                $_POST['phone']
            );
            $stmt->execute();
            $shipping_id = $stmt->insert_id;
            
            // Create order record
            $payment_method = $_POST['payment_method'];
            $stmt = $conn->prepare("INSERT INTO orders (user_id, shipping_id, total_price, payment_method, status) 
                                    VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iids", $user_id, $shipping_id, $grandTotal, $payment_method);
            $stmt->execute();
            $order_id = $stmt->insert_id;
            
            // Create order items
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
                $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                $price = $product['price'];
                
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) 
                                        VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $price);
                $stmt->execute();
                
                // Update product stock (decrement)
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart and redirect to confirmation
        $_SESSION['cart'] = [];
        $_SESSION['success'] = "Order placed successfully!";
        header("Location: order_confirmation.php?order_id=" . $order_id);
        exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errors[] = "Error processing order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ArtisenAlley</title>
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

        .checkout-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .checkout-header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            padding: 2rem;
            border-radius: 20px;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .checkout-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }

        .checkout-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .form-section, .order-summary {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2c3e50;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group select:focus {
            border-color: #3498db;
            outline: none;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .payment-method:hover {
            border-color: #3498db;
        }

        .payment-method.selected {
            border-color: #3498db;
            background-color: #ebf5fb;
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
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-details {
            flex-grow: 1;
            padding: 0 1rem;
        }

        .item-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .item-price, .item-quantity {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .item-total {
            font-weight: 600;
            color: #2c3e50;
        }

        .order-totals .row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-totals .row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.2rem;
            color: #2c3e50;
            padding-top: 1rem;
        }

        .place-order-btn {
            display: block;
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-top: 1.5rem;
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .errors {
            background-color: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .errors ul {
            margin: 0.5rem 0;
            padding-left: 1.5rem;
        }

        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .payment-methods {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="checkout-container">
    <?php
        // Generate breadcrumbs
        $breadcrumbs = [
            ['name' => 'Home', 'url' => 'index.php'],
            ['name' => 'Cart', 'url' => 'cart.php'],
            ['name' => 'Checkout']
        ];
        echo generate_breadcrumbs($breadcrumbs);
        ?>
        
        <div class="checkout-header">
            <h1><i class="fas fa-credit-card"></i> Checkout</h1>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="errors">
                <strong>Please correct the following errors:</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="checkout.php">
            <div class="checkout-grid">
                <!-- Left Column - Checkout Form -->
                <div class="form-section">
                    <!-- Contact Information -->
                    <h2 class="form-title">Contact Information</h2>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number (optional)</label>
                            <input type="tel" id="phone" name="phone">
                        </div>
                    </div>
                    
                    <!-- Shipping Information -->
                    <h2 class="form-title">Shipping Address</h2>
                    <div class="form-group">
                        <label for="address">Street Address</label>
                        <input type="text" id="address" name="address" required>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" required>
                        </div>
                        <div class="form-group">
                            <label for="state">State/Province</label>
                            <input type="text" id="state" name="state" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip">Postal/ZIP Code</label>
                            <input type="text" id="zip" name="zip" required>
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <select id="country" name="country" required>
                                <option value="">Select Country</option>
                                <option value="USA">United States</option>
                                <option value="CAN">Canada</option>
                                <option value="GBR">United Kingdom</option>
                                <option value="AUS">Australia</option>
                                <!-- Add more countries as needed -->
                            </select>
                        </div>
                    </div>
                    
                    <!-- Payment Method -->
                    <h2 class="form-title">Payment Method</h2>
                    <div class="payment-methods">
                        <div class="payment-method" data-method="credit_card">
                            <i class="fas fa-credit-card"></i>
                            <div>Credit Card</div>
                        </div>
                        <div class="payment-method" data-method="paypal">
                            <i class="fab fa-paypal"></i>
                            <div>PayPal</div>
                        </div>
                        <div class="payment-method" data-method="bank_transfer">
                            <i class="fas fa-university"></i>
                            <div>Bank Transfer</div>
                        </div>
                    </div>
                    <input type="hidden" id="payment_method" name="payment_method" value="">
                </div>
                
                <!-- Right Column - Order Summary -->
                <div class="order-summary">
                    <h2 class="form-title">Order Summary</h2>
                    
                    <div class="order-items">
                        <?php foreach ($items as $item): ?>
                            <div class="order-item">
                                <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" 
                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                    onerror="this.src='assets/images/placeholder.jpg'">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <div class="item-price">$<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="item-quantity">Qty: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="item-total">$<?php echo number_format($item['total'], 2); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-totals">
                        <div class="row">
                            <div>Subtotal</div>
                            <div>$<?php echo number_format($grandTotal, 2); ?></div>
                        </div>
                        <div class="row">
                            <div>Shipping</div>
                            <div>Free</div>
                        </div>
                        <div class="row">
                            <div>Tax</div>
                            <div>$0.00</div>
                        </div>
                        <div class="row">
                            <div>Total</div>
                            <div>$<?php echo number_format($grandTotal, 2); ?></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="place-order-btn">
                        <i class="fas fa-lock"></i> Place Order
                    </button>
                </div>
            </div>
    </form>
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
                
                // Update hidden input value
                document.getElementById('payment_method').value = this.dataset.method;
            });
        });
    </script>
</body>
</html>
