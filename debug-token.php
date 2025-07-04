<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

// Token from your Postman screenshot
$token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJzdWIiOjEsInVzZXJuYW1lIjoidGVzdHVzZXIiLCJpYXQiOjE3NTE2NDYzNzksImV4cCI6MTc1MTY0OTk3OSwianRpIjoiand0XzY4NjdmZjZiM2ZiNzc0LjY4NjI0MjM5XzFfMTc1MTY0NjM3OSJ9.gwLjRJhYZOT4dXsFHtcJHTY0NjQlNyJ9.gwLjRJhYZOT4dXsFHtcJHTY0NjQlNyJ9";

try {
    echo "=== TOKEN ANALYSIS ===\n";
    echo "Token: " . substr($token, 0, 50) . "...\n\n";

    // Decode token
    $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
    echo "Decoded token:\n";
    echo "- Subject (user ID): " . $decoded->sub . "\n";
    echo "- Username: " . $decoded->username . "\n";
    echo "- Issued at: " . $decoded->iat . " (" . date('Y-m-d H:i:s', $decoded->iat) . ")\n";
    echo "- Expires at: " . $decoded->exp . " (" . date('Y-m-d H:i:s', $decoded->exp) . ")\n";
    echo "- JWT ID: " . $decoded->jti . "\n\n";

    // Check current time
    $currentTime = time();
    echo "Current timestamp: $currentTime (" . date('Y-m-d H:i:s', $currentTime) . ")\n";
    echo "Token valid: " . ($decoded->exp > $currentTime ? "YES" : "NO") . "\n\n";

    // Check if token is blacklisted
    $host = $_ENV['DB_HOST'];
    $db = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tokenHash = hash('sha256', $token);
    echo "Token hash: " . substr($tokenHash, 0, 20) . "...\n";

    // Check blacklist by hash
    $stmt = $pdo->prepare("SELECT * FROM token_blacklist WHERE token_hash = ? AND expires_timestamp > ?");
    $stmt->execute([$tokenHash, $currentTime]);
    $blacklistedByHash = $stmt->fetch();

    // Check blacklist by JTI
    $stmt = $pdo->prepare("SELECT * FROM token_blacklist WHERE token_jti = ? AND expires_timestamp > ?");
    $stmt->execute([$decoded->jti, $currentTime]);
    $blacklistedByJti = $stmt->fetch();

    echo "\n=== BLACKLIST CHECK ===\n";
    echo "Blacklisted by hash: " . ($blacklistedByHash ? "YES" : "NO") . "\n";
    echo "Blacklisted by JTI: " . ($blacklistedByJti ? "YES" : "NO") . "\n";

    if ($blacklistedByHash) {
        echo "\nBlacklist entry (by hash):\n";
        print_r($blacklistedByHash);
    }

    if ($blacklistedByJti) {
        echo "\nBlacklist entry (by JTI):\n";
        print_r($blacklistedByJti);
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}