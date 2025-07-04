<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

try {
    $host = $_ENV['DB_HOST'];
    $db = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Clearing all blacklisted tokens...\n";
    $stmt = $pdo->prepare("DELETE FROM token_blacklist");
    $deletedCount = $stmt->execute();
    
    echo "Cleared blacklist. Deleted entries: " . $stmt->rowCount() . "\n";
    echo "You can now login fresh and test again.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
