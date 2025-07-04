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

    echo "Fixing timezone issues in token_blacklist table...\n";

    // Add a new column for UNIX timestamp
    try {
        $pdo->exec("ALTER TABLE token_blacklist ADD COLUMN expires_timestamp INT(11) AFTER expires_at");
        echo "Added expires_timestamp column\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "expires_timestamp column already exists\n";
        } else {
            throw $e;
        }
    }

    // Update existing records to populate the timestamp column
    $stmt = $pdo->prepare("UPDATE token_blacklist SET expires_timestamp = UNIX_TIMESTAMP(expires_at) WHERE expires_timestamp IS NULL");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "Updated $updated existing records with timestamp values\n";

    // Add index for the new column
    try {
        $pdo->exec("ALTER TABLE token_blacklist ADD INDEX idx_expires_timestamp (expires_timestamp)");
        echo "Added index for expires_timestamp column\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "Index for expires_timestamp already exists\n";
        } else {
            throw $e;
        }
    }

    echo "\nTimezone fix completed successfully!\n";

    // Show current statistics
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_blacklisted,
            COUNT(CASE WHEN expires_timestamp > UNIX_TIMESTAMP() THEN 1 END) as active_blacklisted,
            COUNT(CASE WHEN expires_timestamp <= UNIX_TIMESTAMP() THEN 1 END) as expired_blacklisted
        FROM token_blacklist
    ");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "\nUpdated Statistics:\n";
    echo "Total blacklisted: " . $stats['total_blacklisted'] . "\n";
    echo "Active blacklisted: " . $stats['active_blacklisted'] . "\n";
    echo "Expired blacklisted: " . $stats['expired_blacklisted'] . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
