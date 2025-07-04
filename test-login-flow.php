<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

try {
    echo "=== TESTING LOGIN FLOW ===\n\n";
    
    // Step 1: Login
    echo "1. Attempting login...\n";
    $loginData = json_encode(['username' => 'testuser', 'password' => 'password']);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $loginData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $loginResponse = curl_exec($ch);
    $loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Login HTTP Code: $loginHttpCode\n";
    echo "Login Response: $loginResponse\n\n";
    
    if ($loginHttpCode !== 200) {
        echo "Login failed, stopping test.\n";
        exit(1);
    }
    
    $loginData = json_decode($loginResponse, true);
    $token = $loginData['token'];
    
    echo "2. Token received: " . substr($token, 0, 50) . "...\n\n";
    
    // Step 2: Immediately check if token is in blacklist
    echo "3. Checking if token is immediately blacklisted...\n";
    
    $host = $_ENV['DB_HOST'];
    $db = $_ENV['DB_NAME'];
    $user = $_ENV['DB_USER'];
    $pass = $_ENV['DB_PASS'];

    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $tokenHash = hash('sha256', $token);
    $currentTime = time();
    
    $stmt = $pdo->prepare("SELECT * FROM token_blacklist WHERE token_hash = ?");
    $stmt->execute([$tokenHash]);
    $blacklistEntry = $stmt->fetch();
    
    if ($blacklistEntry) {
        echo "❌ TOKEN IS ALREADY BLACKLISTED!\n";
        echo "Blacklist entry:\n";
        print_r($blacklistEntry);
    } else {
        echo "✅ Token is not blacklisted (good)\n";
    }
    
    echo "\n4. Testing inventory access...\n";
    
    // Step 3: Try to access inventory
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/inventory');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $inventoryResponse = curl_exec($ch);
    $inventoryHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Inventory HTTP Code: $inventoryHttpCode\n";
    echo "Inventory Response: " . substr($inventoryResponse, 0, 200) . "...\n\n";
    
    if ($inventoryHttpCode === 401) {
        echo "❌ INVENTORY ACCESS DENIED - This confirms the issue\n";
        
        // Let's check what the blacklist looks like now
        echo "\n5. Checking blacklist after inventory request...\n";
        $stmt = $pdo->prepare("SELECT * FROM token_blacklist WHERE token_hash = ?");
        $stmt->execute([$tokenHash]);
        $blacklistEntry = $stmt->fetch();
        
        if ($blacklistEntry) {
            echo "Token is now blacklisted:\n";
            print_r($blacklistEntry);
        } else {
            echo "Token is still not blacklisted - there might be another issue\n";
        }
    } else {
        echo "✅ Inventory access successful\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
