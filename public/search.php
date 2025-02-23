<?php
session_start();
require_once '../config/db.php';

// Check if a keyword is provided via GET parameter
if (!isset($_GET['keyword']) || empty(trim($_GET['keyword']))) {
    header("Location: index.php");
    exit;
}

$keyword = trim($_GET['keyword']);
$searchTerm = "%" . $keyword . "%";

// Prepare and execute the search query on the products table
$stmt = $conn->prepare("SELECT id, name, description, price, image FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY created_at DESC");
$stmt->bind_param("ss", $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Search Results for "<?php echo htmlspecialchars($keyword); ?>"</title>
    <link rel="stylesheet" href="/src/main.css">
</head>
<body>
    <header>
        <a href="index.php">Home</a> |
        <a href="cart.php">Cart</a>
    </header>
    <h1>Search Results for "<?php echo htmlspecialchars($keyword); ?>"</h1>
    <?php if ($result && $result->num_rows > 0): ?>
        <div class="product-list">
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="product-item">
                    <a href="product.php?id=<?php echo $row['id']; ?>">
                        <img src="../uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" width="150">
                        <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                    </a>
                    <p><?php echo htmlspecialchars($row['description']); ?></p>
                    <p>Price: $<?php echo number_format($row['price'], 2); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No products found matching your search.</p>
    <?php endif; ?>
</body>
</html>
