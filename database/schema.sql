-- Create the database
CREATE DATABASE IF NOT EXISTS handmade_store;
USE handmade_store;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    image_url VARCHAR(255),
    stock INT NOT NULL DEFAULT 0,
    featured BOOLEAN DEFAULT FALSE,
    artisan_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (artisan_id) REFERENCES users(id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample data
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@artisanalley.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO products (name, description, price, category, image_url, stock, featured, artisan_id) VALUES
('Handmade Ceramic Vase', 'Beautiful hand-crafted ceramic vase with unique glazing', 79.99, 'Ceramics', '/public/images/products/vase1.jpg', 10, TRUE, 1),
('Wooden Cutting Board', 'Artisan-made wooden cutting board from sustainable materials', 45.99, 'Woodwork', '/public/images/products/cutting-board1.jpg', 15, TRUE, 1),
('Macrame Wall Hanging', 'Hand-knotted macrame wall decoration', 89.99, 'Textile Arts', '/public/images/products/macrame1.jpg', 5, TRUE, 2),
('Leather Messenger Bag', 'Handcrafted leather messenger bag', 159.99, 'Leather Goods', '/public/images/products/bag1.jpg', 8, TRUE, 2),
('Handwoven Wool Scarf', 'Soft and warm handwoven wool scarf', 69.99, 'Textile Arts', '/public/images/products/scarf1.jpg', 12, TRUE, 1),
('Stained Glass Suncatcher', 'Beautiful stained glass suncatcher', 49.99, 'Glass Art', '/public/images/products/suncatcher1.jpg', 7, TRUE, 2);