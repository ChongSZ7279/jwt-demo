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

    echo "Checking blacklisted tokens...\n\n";

    // Get blacklisted tokens
    $stmt = $pdo->query("SELECT * FROM token_blacklist ORDER BY blacklisted_at DESC LIMIT 10");
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tokens)) {
        echo "No blacklisted tokens found.\n";
    } else {
        echo "Found " . count($tokens) . " blacklisted tokens:\n";
        echo str_repeat("-", 80) . "\n";
        
        foreach ($tokens as $token) {
            echo "JTI: " . $token['token_jti'] . "\n";
            echo "User ID: " . $token['user_id'] . "\n";
            echo "Expires: " . $token['expires_at'] . "\n";
            echo "Blacklisted: " . $token['blacklisted_at'] . "\n";
            echo "Reason: " . $token['reason'] . "\n";
            echo "Hash: " . substr($token['token_hash'], 0, 20) . "...\n";
            echo str_repeat("-", 80) . "\n";
        }
    }

    // Get statistics
    $currentTimestamp = time();
    $stmt = $pdo->query("
        SELECT
            COUNT(*) as total_blacklisted,
            COUNT(CASE WHEN expires_timestamp > $currentTimestamp THEN 1 END) as active_blacklisted,
            COUNT(CASE WHEN expires_timestamp <= $currentTimestamp THEN 1 END) as expired_blacklisted
        FROM token_blacklist
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nStatistics:\n";
    echo "Total blacklisted: " . $stats['total_blacklisted'] . "\n";
    echo "Active blacklisted: " . $stats['active_blacklisted'] . "\n";
    echo "Expired blacklisted: " . $stats['expired_blacklisted'] . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
