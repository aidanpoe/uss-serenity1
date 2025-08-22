<?php
echo "Testing handleDepartmentTraining...<br>";

session_start();
echo "Session started<br>";

// Test config include
include '../includes/config.php';
echo "Config included successfully<br>";

// Test department_training include
include '../includes/department_training.php';
echo "Department training included successfully<br>";

echo "About to call updateLastActive()...<br>";
updateLastActive();
echo "updateLastActive() called successfully<br>";

echo "About to test handleDepartmentTraining()...<br>";

try {
    // This is the problematic call from command.php
    if (hasPermission('Command')) {
        echo "User has Command permission, calling handleDepartmentTraining...<br>";
        handleDepartmentTraining('Command');
        echo "handleDepartmentTraining('Command') called successfully<br>";
    } else {
        echo "User does not have Command permission<br>";
    }
} catch (Exception $e) {
    echo "Error in handleDepartmentTraining(): " . $e->getMessage() . "<br>";
}

echo "handleDepartmentTraining test complete!";
?>
