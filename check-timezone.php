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

    echo "=== TIMEZONE DEBUGGING ===\n";
    
    // Check PHP timezone
    echo "PHP Timezone: " . date_default_timezone_get() . "\n";
    echo "PHP Time: " . date('Y-m-d H:i:s') . "\n";
    echo "PHP UTC Time: " . gmdate('Y-m-d H:i:s') . "\n";
    
    // Check database timezone
    $stmt = $pdo->query("SELECT NOW() as db_time, @@session.time_zone as db_timezone, UNIX_TIMESTAMP() as db_timestamp");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "DB Time: " . $result['db_time'] . "\n";
    echo "DB Timezone: " . $result['db_timezone'] . "\n";
    echo "DB Timestamp: " . $result['db_timestamp'] . "\n";
    
    // Check the latest blacklisted token
    $stmt = $pdo->query("SELECT token_jti, expires_at, blacklisted_at, UNIX_TIMESTAMP(expires_at) as exp_timestamp, UNIX_TIMESTAMP() as now_timestamp FROM token_blacklist ORDER BY blacklisted_at DESC LIMIT 1");
    $token = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($token) {
        echo "\n=== LATEST BLACKLISTED TOKEN ===\n";
        echo "JTI: " . $token['token_jti'] . "\n";
        echo "Expires At: " . $token['expires_at'] . "\n";
        echo "Blacklisted At: " . $token['blacklisted_at'] . "\n";
        echo "Expires Timestamp: " . $token['exp_timestamp'] . "\n";
        echo "Current Timestamp: " . $token['now_timestamp'] . "\n";
        echo "Time Difference: " . ($token['exp_timestamp'] - $token['now_timestamp']) . " seconds\n";
        echo "Is Expired: " . ($token['exp_timestamp'] <= $token['now_timestamp'] ? 'YES' : 'NO') . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
