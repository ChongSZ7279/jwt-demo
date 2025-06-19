CREATE DATABASE jwt_demo;
USE jwt_demo;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

-- Insert a test user (password: testpass)
INSERT INTO users (username, password)
VALUES ('testuser', 'password'); -- Use password_hash in PHP

-- Inventory table
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    quantity INT NOT NULL DEFAULT 0,
    price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    category VARCHAR(100),
    sku VARCHAR(100) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert sample inventory items
INSERT INTO inventory (name, description, quantity, price, category, sku) VALUES
('Laptop Dell XPS 13', '13-inch premium laptop with Intel i7 processor', 15, 1299.99, 'Electronics', 'LAP-DELL-XPS13'),
('Wireless Mouse Logitech MX Master', 'Ergonomic wireless mouse with precision tracking', 45, 79.99, 'Accessories', 'MOU-LOG-MXMASTER'),
('Mechanical Keyboard Cherry MX', 'RGB mechanical keyboard with blue switches', 30, 149.99, 'Accessories', 'KEY-CHERRY-MX'),
('4K Monitor Samsung 32"', '32-inch 4K UHD monitor with HDR support', 12, 599.99, 'Electronics', 'MON-SAM-32-4K'),
('USB-C Hub 7-in-1', 'Multi-port USB-C hub with HDMI and SD card reader', 60, 39.99, 'Accessories', 'HUB-USB-7IN1');
