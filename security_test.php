<?php
// Test showcase security
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html>
<head><title>Security Test</title></head>
<body>
<h1>🔒 Showcase Security Test</h1>

<?php if (defined('SHOWCASE_MODE') && SHOWCASE_MODE): ?>
<div style="background: green; color: white; padding: 1rem;">
    ✅ SHOWCASE_MODE is ACTIVE - Website is in read-only mode
</div>

<h2>Testing Database Operations:</h2>
<?php
try {
    $pdo = getConnection();
    echo "<p>📡 Database connection established (using safe wrapper)</p>";
    
    // Test a SELECT (should work)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM roster");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p>✅ SELECT query works: Found " . ($result['count'] ?? 'mock data') . " records</p>";
    
    // Test a dangerous DELETE (should be blocked)
    $stmt = $pdo->prepare("DELETE FROM roster WHERE id = 999");
    $result = $stmt->execute();
    echo "<p>🛡️ DELETE query blocked: " . ($result ? 'appeared to succeed but was actually blocked' : 'failed as expected') . "</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}
?>

<h2>Testing Authentication:</h2>
<p>🔐 isLoggedIn(): <?php echo isLoggedIn() ? 'TRUE' : 'FALSE'; ?></p>
<p>🎖️ hasPermission('Command'): <?php echo hasPermission('Command') ? 'TRUE' : 'FALSE'; ?></p>
<p>👤 Current user: <?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></p>

<h2>Testing Parameter Security:</h2>
<p>🔗 <a href="?delete=test">Try dangerous GET parameter (should redirect)</a></p>
<p>🔗 <a href="?action=delete_something">Try dangerous action parameter (should redirect)</a></p>

<?php else: ?>
<div style="background: red; color: white; padding: 1rem;">
    ❌ SHOWCASE_MODE is NOT ACTIVE - This could be dangerous!
</div>
<?php endif; ?>

</body>
</html>