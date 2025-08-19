<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'serenity');
define('DB_PASSWORD', 'Os~886go4');
define('DB_NAME', 'serenity');
define('DB_PORT', 3306);

// Create connection
function getConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
            DB_USERNAME, 
            DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user department
function getUserDepartment() {
    return $_SESSION['department'] ?? null;
}

// Check if user has specific permission
function hasPermission($required_department) {
    if (!isLoggedIn()) return false;
    
    $user_dept = getUserDepartment();
    
    // Captain and Command have access to everything
    if ($user_dept === 'Command' || $user_dept === 'Captain') {
        return true;
    }
    
    // Check specific department access
    return $user_dept === $required_department;
}

// Redirect if not authorized
function requirePermission($required_department) {
    if (!hasPermission($required_department)) {
        header('Location: login.php');
        exit();
    }
}
?>
