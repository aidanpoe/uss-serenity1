<?php
require_once 'includes/config.php';
require_once 'includes/promotion_system.php';

echo "<h2>Promotion System Test</h2>";

if (!isLoggedIn()) {
    echo "Please log in first.<br>";
    echo "<a href='index.php'>Go to Login</a>";
    exit;
}

echo "<h3>Current User Info:</h3>";
echo "Character ID: " . ($_SESSION['character_id'] ?? 'Not set') . "<br>";
echo "Roster Department: " . ($_SESSION['roster_department'] ?? 'Not set') . "<br>";
echo "User Department: " . (getUserDepartment() ?? 'Not set') . "<br>";

echo "<h3>Permission Tests:</h3>";
echo "Has Command permission: " . (hasPermission('Command') ? 'Yes' : 'No') . "<br>";
echo "Is Head of MED/SCI: " . (isDepartmentHead('MED/SCI') ? 'Yes' : 'No') . "<br>";
echo "Is Head of ENG/OPS: " . (isDepartmentHead('ENG/OPS') ? 'Yes' : 'No') . "<br>";
echo "Is Head of SEC/TAC: " . (isDepartmentHead('SEC/TAC') ? 'Yes' : 'No') . "<br>";

echo "Can promote MED/SCI: " . (canPromoteDemote('MED/SCI') ? 'Yes' : 'No') . "<br>";
echo "Can promote ENG/OPS: " . (canPromoteDemote('ENG/OPS') ? 'Yes' : 'No') . "<br>";
echo "Can promote SEC/TAC: " . (canPromoteDemote('SEC/TAC') ? 'Yes' : 'No') . "<br>";

if (isset($_SESSION['character_id'])) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT first_name, last_name, rank, department, position FROM roster WHERE id = ?");
        $stmt->execute([$_SESSION['character_id']]);
        $character = $stmt->fetch();
        
        if ($character) {
            echo "<h3>Your Character:</h3>";
            echo "Name: " . htmlspecialchars($character['first_name'] . ' ' . $character['last_name']) . "<br>";
            echo "Rank: " . htmlspecialchars($character['rank']) . "<br>";
            echo "Department: " . htmlspecialchars($character['department']) . "<br>";
            echo "Position: " . htmlspecialchars($character['position']) . "<br>";
        }
    } catch (Exception $e) {
        echo "Error getting character info: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";
echo "<h3>Test Promotion Forms:</h3>";

echo "<h4>MED/SCI Promotion Form:</h4>";
renderPromotionForm('MED/SCI');

echo "<h4>ENG/OPS Promotion Form:</h4>";
renderPromotionForm('ENG/OPS');

echo "<h4>SEC/TAC Promotion Form:</h4>";
renderPromotionForm('SEC/TAC');

echo "<br><a href='pages/med_sci.php'>Back to MED/SCI</a> | ";
echo "<a href='pages/eng_ops.php'>Back to ENG/OPS</a> | ";
echo "<a href='pages/sec_tac.php'>Back to SEC/TAC</a>";
?>
