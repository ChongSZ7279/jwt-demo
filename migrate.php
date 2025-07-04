<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

try {
    // Connect to database
    $host = $_ENV['DB_HOST'];
    $db = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Connected to database successfully!\n";

    // Create token blacklist table
    $sql = "
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
    )";

    $pdo->exec($sql);
    echo "Token blacklist table created successfully!\n";

    // Check if table exists and show structure
    $stmt = $pdo->query("DESCRIBE token_blacklist");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nTable structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
