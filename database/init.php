<?php
// Include database connection
require_once dirname(__DIR__) . '/config/db.php';

// SQL to create products table if it doesn't exist
$sql_products = "CREATE TABLE IF NOT EXISTS products (
    id INT(11) NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    category VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
)";

// Execute query
if ($conn->query($sql_products) === TRUE) {
    echo "Products table created successfully or already exists<br>";
} else {
    echo "Error creating products table: " . $conn->error . "<br>";
}

// Check if products table is empty, add sample data if it is
$result = $conn->query("SELECT COUNT(*) as count FROM products");
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    // Insert sample products
    $sample_products = [
        [
            'name' => 'Handcrafted Wooden Bowl',
            'description' => 'Beautiful wooden bowl handcrafted from reclaimed oak. Each piece is unique with natural grain patterns.',
            'price' => 45.99,
            'image' => 'placeholder.jpg',
            'category' => 'Home Decor'
        ],
        [
            'name' => 'Hand-Knitted Wool Scarf',
            'description' => 'Warm and cozy scarf made from 100% merino wool. Perfect for cold winter days.',
            'price' => 35.50,
            'image' => 'placeholder.jpg',
            'category' => 'Apparel'
        ],
        [
            'name' => 'Ceramic Mug Set',
            'description' => 'Set of 4 handmade ceramic mugs, each with unique glazing pattern. Microwave and dishwasher safe.',
            'price' => 60.00,
            'image' => 'placeholder.jpg',
            'category' => 'Kitchenware'
        ],
        [
            'name' => 'Leather Journal',
            'description' => 'Handbound leather journal with 200 pages of recycled paper. Perfect for sketching or writing.',
            'price' => 28.99,
            'image' => 'placeholder.jpg',
            'category' => 'Stationery'
        ],
        [
            'name' => 'Stained Glass Suncatcher',
            'description' => 'Beautiful stained glass suncatcher handmade using traditional techniques. Creates amazing light patterns.',
            'price' => 39.95,
            'image' => 'placeholder.jpg',
            'category' => 'Home Decor'
        ],
        [
            'name' => 'Hand-Poured Soy Candle',
            'description' => 'Natural soy wax candle with essential oils. Burns for approximately 45 hours.',
            'price' => 22.50,
            'image' => 'placeholder.jpg',
            'category' => 'Home Fragrance'
        ]
    ];

    $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, category) VALUES (?, ?, ?, ?, ?)");
    
    foreach ($sample_products as $product) {
        $stmt->bind_param("ssdss", 
            $product['name'], 
            $product['description'], 
            $product['price'], 
            $product['image'], 
            $product['category']
        );
        
        if ($stmt->execute()) {
            echo "Added sample product: " . $product['name'] . "<br>";
        } else {
            echo "Error adding sample product: " . $stmt->error . "<br>";
        }
    }
    
    $stmt->close();
}

// Close connection
$conn->close();

echo "Database initialization complete!";
?> 