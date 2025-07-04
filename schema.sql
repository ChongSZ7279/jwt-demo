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

-- Token blacklist table for JWT logout functionality
CREATE TABLE token_blacklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    token_jti VARCHAR(255) NOT NULL UNIQUE COMMENT 'JWT ID (jti) claim for token identification',
    token_hash VARCHAR(64) NOT NULL UNIQUE COMMENT 'SHA256 hash of the full token for security',
    user_id INT NOT NULL COMMENT 'User ID who owns this token',
    expires_at TIMESTAMP NOT NULL COMMENT 'When the token expires (same as JWT exp claim)',
    blacklisted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the token was blacklisted',
    reason VARCHAR(100) DEFAULT 'logout' COMMENT 'Reason for blacklisting (logout, security, etc.)',
    INDEX idx_token_jti (token_jti),
    INDEX idx_token_hash (token_hash),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert sample inventory items
INSERT INTO inventory (name, description, quantity, price, category, sku) VALUES
('Laptop Dell XPS 13', '13-inch premium laptop with Intel i7 processor', 15, 1299.99, 'Electronics', 'LAP-DELL-XPS13'),
('Wireless Mouse Logitech MX Master', 'Ergonomic wireless mouse with precision tracking', 45, 79.99, 'Accessories', 'MOU-LOG-MXMASTER'),
('Mechanical Keyboard Cherry MX', 'RGB mechanical keyboard with blue switches', 30, 149.99, 'Accessories', 'KEY-CHERRY-MX'),
('4K Monitor Samsung 32"', '32-inch 4K UHD monitor with HDR support', 12, 599.99, 'Electronics', 'MON-SAM-32-4K'),
('USB-C Hub 7-in-1', 'Multi-port USB-C hub with HDMI and SD card reader', 60, 39.99, 'Accessories', 'HUB-USB-7IN1');
