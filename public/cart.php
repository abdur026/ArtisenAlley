<?php
session_start();

// Initialize shopping cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Process cart actions: add, update, remove
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $product_id = intval($_POST['product_id']);
                $quantity = intval($_POST['quantity']);
                // If product already exists in cart, update quantity
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                break;
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
        }
    }
    header("Location: cart.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Shopping Cart</title>
    <link rel="stylesheet" href="../src/main.css">
</head>
<body>
    <header>
        <a href="index.php">Home</a> |
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </header>
    <h1>Your Shopping Cart</h1>
    <?php
    if (empty($_SESSION['cart'])) {
        echo "<p>Your cart is empty.</p>";
    } else {
        require_once '../config/db.php';
        echo "<table border='1' cellspacing='0' cellpadding='8'>";
        echo "<tr><th>Product</th><th>Quantity</th><th>Price</th><th>Total</th><th>Actions</th></tr>";
        $grandTotal = 0;
        foreach ($_SESSION['cart'] as $product_id => $quantity) {
            // Query product details from the database
            $stmt = $conn->prepare("SELECT name, price FROM products WHERE id = ?");
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                $total = $product['price'] * $quantity;
                $grandTotal += $total;
                echo "<tr>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td>
                        <form method='POST' action='cart.php'>
                            <input type='hidden' name='action' value='update'>
                            <input type='hidden' name='product_id' value='$product_id'>
                            <input type='number' name='quantity' value='$quantity' min='1' style='width:50px;'>
                            <button type='submit'>Update</button>
                        </form>
                      </td>";
                echo "<td>$" . number_format($product['price'], 2) . "</td>";
                echo "<td>$" . number_format($total, 2) . "</td>";
                echo "<td>
                        <form method='POST' action='cart.php'>
                            <input type='hidden' name='action' value='remove'>
                            <input type='hidden' name='product_id' value='$product_id'>
                            <button type='submit'>Remove</button>
                        </form>
                      </td>";
                echo "</tr>";
            }
        }
        echo "<tr><td colspan='3'><strong>Grand Total</strong></td><td colspan='2'>$" . number_format($grandTotal, 2) . "</td></tr>";
        echo "</table>";
    }
    ?>
    <p><a href="checkout.php">Proceed to Checkout</a></p>
</body>
</html>
