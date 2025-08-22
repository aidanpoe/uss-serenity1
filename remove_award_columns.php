<?php
// Remove minimum_rank and awarding_authority columns from awards table
require_once 'includes/config.php';

echo "<!DOCTYPE html><html><head><title>Remove Award Columns</title></head><body>";
echo "<h1>Removing minimum_rank and awarding_authority from awards table</h1>";

try {
    $pdo = getConnection();
    
    // Check if columns exist before trying to drop them
    $stmt = $pdo->prepare("SHOW COLUMNS FROM awards LIKE 'minimum_rank'");
    $stmt->execute();
    $min_rank_exists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->prepare("SHOW COLUMNS FROM awards LIKE 'awarding_authority'");
    $stmt->execute();
    $awarding_auth_exists = $stmt->rowCount() > 0;
    
    if ($min_rank_exists) {
        echo "<p>Removing minimum_rank column...</p>";
        $pdo->exec("ALTER TABLE awards DROP COLUMN minimum_rank");
        echo "✓ minimum_rank column removed successfully.<br>";
    } else {
        echo "ℹ minimum_rank column does not exist.<br>";
    }
    
    if ($awarding_auth_exists) {
        echo "<p>Removing awarding_authority column...</p>";
        $pdo->exec("ALTER TABLE awards DROP COLUMN awarding_authority");
        echo "✓ awarding_authority column removed successfully.<br>";
    } else {
        echo "ℹ awarding_authority column does not exist.<br>";
    }
    
    echo "<h2>✅ Database migration completed successfully!</h2>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>Update all PHP files to remove references to minimum_rank and awarding_authority</li>";
    echo "<li>Test award recommendation and management systems</li>";
    echo "<li>Verify awards display properly without rank restrictions</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
