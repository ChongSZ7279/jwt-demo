-- Migration script to add token blacklist table to existing jwt_demo database
-- Run this script if you already have the database set up

USE jwt_demo;

-- Create token blacklist table for JWT logout functionality
CREATE TABLE IF NOT EXISTS token_blacklist (
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

-- Verify the table was created
SELECT 'Token blacklist table created successfully!' as status;
DESCRIBE token_blacklist;
