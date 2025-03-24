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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $grandTotal = 0;
    
  
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
    
 
    $stmt = $conn->prepare("INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, 'pending')");
    $stmt->bind_param("id", $user_id, $grandTotal);
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        
    
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
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="/src/main.css">
</head>
<body>
    <header>
        <a href="index.php">Home</a> |
        <a href="cart.php">Back to Cart</a>
    </header>
    <h1>Checkout</h1>
    <?php
    if (isset($_SESSION['error'])) {
        echo "<p style='color:red'>" . $_SESSION['error'] . "</p>";
        unset($_SESSION['error']);
    }
    ?>
    <p>Total: $<?php 
    $total = 0;
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $product = $result->fetch_assoc();
            $total += $product['price'] * $quantity;
        }
    }
    echo number_format($total, 2);
    ?></p>
    <form action="checkout.php" method="POST">
        <button type="submit">Place Order</button>
    </form>
</body>
</html>
