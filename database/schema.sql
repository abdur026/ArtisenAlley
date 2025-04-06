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
    profile_image VARCHAR(255) DEFAULT 'default.png',
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

-- Forum Categories table
CREATE TABLE IF NOT EXISTS forum_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(50) DEFAULT 'fa-comments',
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Forum Threads table
CREATE TABLE IF NOT EXISTS forum_threads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    views INT DEFAULT 0,
    is_sticky BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES forum_categories(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Forum Replies table
CREATE TABLE IF NOT EXISTS forum_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (thread_id) REFERENCES forum_threads(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample data
INSERT INTO users (name, email, password, role) VALUES
('Admin User', 'admin@artisanalley.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Insert sample forum categories
INSERT INTO forum_categories (name, description, icon, display_order) VALUES
('General Discussion', 'Talk about anything related to arts and crafts', 'fa-comments', 1),
('Tips & Techniques', 'Share your expertise and learn from others', 'fa-lightbulb', 2),
('Projects Showcase', 'Show off your latest creations', 'fa-images', 3),
('Materials & Supplies', 'Discuss the best materials and where to find them', 'fa-tools', 4);

-- Insert sample threads
INSERT INTO forum_threads (category_id, user_id, title, content) VALUES
(1, 1, 'Welcome to the Artisan Alley Community!', 'Welcome to our community of artisans and craft lovers! Feel free to introduce yourself and share your passion for handmade items.'),
(2, 2, 'Best techniques for beginner potters', 'I just started pottery and would love some advice on basic techniques that helped you when you were starting out.');

-- Insert sample replies
INSERT INTO forum_replies (thread_id, user_id, content) VALUES
(1, 2, 'Thanks for creating this community! I''m John, and I love working with leather. Looking forward to connecting with fellow artisans!'),
(2, 1, 'As a potter, I''d recommend starting with basic pinch pots and coil techniques before moving to the wheel. It helps you understand the clay better.');

INSERT INTO products (name, description, price, category, image_url, stock, featured, artisan_id) VALUES
('Handmade Ceramic Vase', 'Beautiful hand-crafted ceramic vase with unique glazing', 79.99, 'Ceramics', '/public/images/products/vase1.jpg', 10, TRUE, 1),
('Wooden Cutting Board', 'Artisan-made wooden cutting board from sustainable materials', 45.99, 'Woodwork', '/public/images/products/cutting-board1.jpg', 15, TRUE, 1),
('Macrame Wall Hanging', 'Hand-knotted macrame wall decoration', 89.99, 'Textile Arts', '/public/images/products/macrame1.jpg', 5, TRUE, 2),
('Leather Messenger Bag', 'Handcrafted leather messenger bag', 159.99, 'Leather Goods', '/public/images/products/bag1.jpg', 8, TRUE, 2),
('Handwoven Wool Scarf', 'Soft and warm handwoven wool scarf', 69.99, 'Textile Arts', '/public/images/products/scarf1.jpg', 12, TRUE, 1),
('Stained Glass Suncatcher', 'Beautiful stained glass suncatcher', 49.99, 'Glass Art', '/public/images/products/suncatcher1.jpg', 7, TRUE, 2);