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

// Check if user can edit personnel files (Heads of departments, Command, Captain)
function canEditPersonnelFiles() {
    if (!isLoggedIn()) return false;
    
    $user_dept = getUserDepartment();
    
    // Captain and Command have access
    if ($user_dept === 'Command' || $user_dept === 'Captain') {
        return true;
    }
    
    // Check if user is a department head by checking their position in roster
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT position FROM roster r JOIN users u ON r.first_name = u.first_name AND r.last_name = u.last_name WHERE u.id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        
        if ($result && $result['position']) {
            $head_positions = [
                'Head of ENG/OPS', 'Head of MED/SCI', 'Head of SEC/TAC',
                'Chief Engineer', 'Chief Medical Officer', 'Security Chief',
                'Operations Officer', 'Chief Science Officer', 'Tactical Officer',
                'Helm Officer', 'Intelligence Officer', 'S.R.T. Leader'
            ];
            return in_array($result['position'], $head_positions);
        }
    } catch (Exception $e) {
        return false;
    }
    
    return false;
}

// Redirect if not authorized
function requirePermission($required_department) {
    if (!hasPermission($required_department)) {
        header('Location: login.php');
        exit();
    }
}
?>
