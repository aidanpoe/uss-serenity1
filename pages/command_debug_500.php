<?php
echo "Testing command.php step by step...<br>";

try {
    echo "1. Starting session...<br>";
    session_start();
    echo "2. Session started successfully<br>";
    
    echo "3. Including config...<br>";
    require_once '../includes/config.php';
    echo "4. Config included successfully<br>";
    
    echo "5. Testing database connection...<br>";
    $pdo = getConnection();
    echo "6. Database connection working<br>";
    
    echo "7. Including department_training...<br>";
    require_once '../includes/department_training.php';
    echo "8. Department training included successfully<br>";
    
    echo "9. Testing updateLastActive...<br>";
    updateLastActive();
    echo "10. updateLastActive() completed<br>";
    
    echo "11. Testing hasPermission...<br>";
    $hasCommandPerm = hasPermission('Command');
    echo "12. hasPermission('Command') returned: " . ($hasCommandPerm ? 'true' : 'false') . "<br>";
    
    if ($hasCommandPerm) {
        echo "13. User has Command permission, testing handleDepartmentTraining...<br>";
        handleDepartmentTraining('Command');
        echo "14. handleDepartmentTraining completed<br>";
    } else {
        echo "13. User does not have Command permission, skipping handleDepartmentTraining<br>";
    }
    
    echo "<br><strong style='color: green;'>All tests passed! The issue is likely in the HTML or later PHP sections.</strong>";
    
} catch (Exception $e) {
    echo "<br><strong style='color: red;'>Error caught: " . $e->getMessage() . "</strong><br>";
    echo "Error file: " . $e->getFile() . "<br>";
    echo "Error line: " . $e->getLine() . "<br>";
}
?>
