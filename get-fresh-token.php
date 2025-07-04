<?php
require __DIR__ . '/backend/vendor/autoload.php';

use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/backend');
$dotenv->load();

try {
    echo "=== GETTING FRESH TOKEN FOR POSTMAN ===\n\n";
    
    // Login to get a fresh token
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
    
    if ($loginHttpCode !== 200) {
        echo "Login failed: $loginResponse\n";
        exit(1);
    }
    
    $loginData = json_decode($loginResponse, true);
    $token = $loginData['token'];
    
    echo "✅ Fresh token generated successfully!\n\n";
    echo "Copy this token to your Postman Authorization header:\n";
    echo "==========================================\n";
    echo $token . "\n";
    echo "==========================================\n\n";
    
    echo "In Postman:\n";
    echo "1. Go to the Authorization tab\n";
    echo "2. Select 'Bearer Token' as the type\n";
    echo "3. Paste the token above into the Token field\n";
    echo "4. Make sure there are no extra spaces or characters\n\n";
    
    // Test the token immediately
    echo "Testing the token...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/inventory');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $inventoryResponse = curl_exec($ch);
    $inventoryHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($inventoryHttpCode === 200) {
        echo "✅ Token works! You should be able to access inventory in Postman.\n";
    } else {
        echo "❌ Token test failed. HTTP Code: $inventoryHttpCode\n";
        echo "Response: $inventoryResponse\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
