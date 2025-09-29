<?php
// Include secure configuration
require_once __DIR__ . '/secure_config.php';

// Include showcase configuration (must be loaded before other functions)
require_once __DIR__ . '/showcase_config.php';

// Start session with secure settings
if (session_status() == PHP_SESSION_NONE) {
    // Secure session configuration - adjust for development/production
    ini_set('session.cookie_httponly', 1);
    
    // Only use secure cookies if HTTPS is available
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
        ini_set('session.cookie_samesite', 'Strict');
    } else {
        ini_set('session.cookie_secure', 0);
        ini_set('session.cookie_samesite', 'Lax');
    }
    
    ini_set('session.use_only_cookies', 1);
    
    session_start();
    
    // Session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
    
    // Regenerate session ID periodically
    if (!isset($_SESSION['created'])) {
        $_SESSION['created'] = time();
    } else if (time() - $_SESSION['created'] > 1800) { // 30 minutes
        session_regenerate_id(true);
        $_SESSION['created'] = time();
    }
}

// Create connection with secure error handling
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
                PDO::ATTR_STRINGIFY_FETCHES => false,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        // Log error securely, don't expose details to user
        error_log("Database connection failed: " . $e->getMessage());
        die("Connection failed. Please try again later.");
    }
}

// Create global PDO connection with error handling
try {
    $pdo = getConnection();
} catch (Exception $e) {
    error_log("Failed to establish database connection: " . $e->getMessage());
    die("Database connection error. Please contact administrator.");
}

// Check if user is logged in (Steam only)
function isLoggedIn() {
    // In showcase mode, always return true
    if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
        return true;
    }
    return isset($_SESSION['user_id']) && isset($_SESSION['steamid']);
}

// Get current active character data
function getCurrentCharacter() {
    if (!isLoggedIn()) return null;
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT u.*, r.id as character_id, r.rank, r.first_name, r.last_name, r.species, 
                   r.department as roster_department, r.position, r.image_path, r.character_name,
                   r.is_active, r.created_at
            FROM users u 
            LEFT JOIN roster r ON u.active_character_id = r.id 
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

// Get all characters for a user
function getUserCharacters($user_id = null) {
    if (!$user_id) {
        if (!isLoggedIn()) return [];
        $user_id = $_SESSION['user_id'];
    }
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*, 
                   CASE WHEN u.active_character_id = r.id THEN 1 ELSE 0 END as is_current_character
            FROM roster r 
            JOIN users u ON r.user_id = u.id
            WHERE r.user_id = ? AND r.is_active = 1
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

// Switch active character
function switchCharacter($character_id) {
    if (!isLoggedIn()) return false;
    
    try {
        $pdo = getConnection();
        
        // Verify the character belongs to the current user
        $stmt = $pdo->prepare("SELECT r.*, r.department as roster_department FROM roster r WHERE r.id = ? AND r.user_id = ? AND r.is_active = 1");
        $stmt->execute([$character_id, $_SESSION['user_id']]);
        $character = $stmt->fetch();
        
        if (!$character) return false;
        
        // Map roster department to user department permissions
        $user_department = '';
        switch($character['department']) {
            case 'Medical':
            case 'Science':
            case 'MED/SCI':
                $user_department = 'MED/SCI';
                break;
            case 'Engineering':
            case 'Operations':
            case 'ENG/OPS':
                $user_department = 'ENG/OPS';
                break;
            case 'Security':
            case 'Tactical':
            case 'SEC/TAC':
                $user_department = 'SEC/TAC';
                break;
            case 'Command':
                $user_department = 'Command';
                break;
            case 'Starfleet Auditor':
                $user_department = 'Starfleet Auditor';
                break;
            default:
                $user_department = 'SEC/TAC'; // Default fallback
                break;
        }
        
        // Update active character and user permissions in database
        $stmt = $pdo->prepare("UPDATE users SET active_character_id = ?, department = ? WHERE id = ?");
        $stmt->execute([$character_id, $user_department, $_SESSION['user_id']]);
        
        // If character is Starfleet Auditor, ensure they are invisible
        if ($character['department'] === 'Starfleet Auditor') {
            $stmt = $pdo->prepare("UPDATE roster SET is_invisible = 1 WHERE id = ?");
            $stmt->execute([$character_id]);
            $character['is_invisible'] = 1; // Update local data
        }
        
        // Update session variables with new character data
        $_SESSION['first_name'] = $character['first_name'];
        $_SESSION['last_name'] = $character['last_name'];
        $_SESSION['rank'] = $character['rank'];
        $_SESSION['position'] = $character['position'];
        $_SESSION['department'] = $user_department; // Use the mapped permission group
        $_SESSION['roster_department'] = $character['department']; // Store original department too
        $_SESSION['character_id'] = $character_id; // Store character ID for tracking
        $_SESSION['is_invisible'] = $character['is_invisible'] ?? 0; // Store invisibility status
        
        // Update last_active timestamp for the new character
        $stmt = $pdo->prepare("UPDATE roster SET last_active = NOW() WHERE id = ?");
        $stmt->execute([$character_id]);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Check if user can create more characters (max 5)
function canCreateCharacter($user_id = null) {
    if (!$user_id) {
        if (!isLoggedIn()) return false;
        $user_id = $_SESSION['user_id'];
    }
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as character_count FROM roster WHERE user_id = ? AND is_active = 1");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        return ($result['character_count'] < 5);
    } catch (Exception $e) {
        return false;
    }
}

// Check user department (from current active character)
function getUserDepartment() {
    // Return the permission group from session (MED/SCI, ENG/OPS, SEC/TAC, Command)
    return $_SESSION['department'] ?? null;
}

// Update last_active timestamp for current character and user session
function updateLastActive() {
    // In showcase mode, do nothing
    if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
        return;
    }
    if (!isLoggedIn()) return;
    
    try {
        $pdo = getConnection();
        
        // If we have a character_id, update the character's last_active
        if (isset($_SESSION['character_id']) && $_SESSION['character_id']) {
            $stmt = $pdo->prepare("UPDATE roster SET last_active = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['character_id']]);
        } else {
            // If no character_id in session, try to get it from the database
            if (isset($_SESSION['user_id'])) {
                $stmt = $pdo->prepare("SELECT active_character_id FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if ($user && $user['active_character_id']) {
                    $_SESSION['character_id'] = $user['active_character_id']; // Set it in session for future use
                    
                    // Update the character's last_active
                    $stmt = $pdo->prepare("UPDATE roster SET last_active = NOW() WHERE id = ?");
                    $stmt->execute([$user['active_character_id']]);
                }
            }
        }
        
        // Always update user's last_login to track active session
        if (isset($_SESSION['user_id'])) {
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        }
        
    } catch (Exception $e) {
        // Silent fail - don't break the page if this fails
        error_log("Failed to update last_active: " . $e->getMessage());
    }
}

// Check if user has specific permission
function hasPermission($required_department) {
    // In showcase mode, always return true
    if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
        return true;
    }
    if (!isLoggedIn()) return false;
    
    $user_dept = getUserDepartment();
    $user_rank = $_SESSION['rank'] ?? '';
    $roster_dept = $_SESSION['roster_department'] ?? '';
    
    // Priority 1: Check if current character is a Starfleet Auditor (character-based)
    if ($roster_dept === 'Starfleet Auditor') {
        return true;
    }
    
    // Priority 2: Starfleet Auditors at user level have access to everything (legacy support)
    if ($user_dept === 'Starfleet Auditor') {
        return true;
    }
    
    // Priority 3: Captain and Command ranks have access to everything
    if ($user_rank === 'Captain' || $user_rank === 'Commander' || $user_dept === 'Command' || $roster_dept === 'Command') {
        return true;
    }
    
    // Priority 4: Check specific department access (use character department first, fallback to user department)
    $active_department = $roster_dept ?: $user_dept;
    return $active_department === $required_department;
}

// Check if user can edit personnel files (Heads of departments, Command, Captain, Starfleet Auditor)
function canEditPersonnelFiles() {
    // In showcase mode, always return true
    if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
        return true;
    }
    if (!isLoggedIn()) return false;
    
    $user_dept = getUserDepartment();
    $roster_dept = $_SESSION['roster_department'] ?? '';
    
    // Priority 1: Check if current character is a Starfleet Auditor (character-based)
    if ($roster_dept === 'Starfleet Auditor') {
        return true;
    }
    
    // Priority 2: Starfleet Auditors at user level have full access (legacy support)
    if ($user_dept === 'Starfleet Auditor') {
        return true;
    }
    
    // Priority 3: Command staff (both user and character level)
    if ($user_dept === 'Command' || $user_dept === 'Captain' || $roster_dept === 'Command') {
        return true;
    }
    
    // Priority 4: Check if user has Command permission through character rank
    if (hasPermission('Command')) {
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

// Check if user is a specific department head
function isDepartmentHead($department) {
    if (!isLoggedIn()) return false;
    
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT position FROM roster WHERE id = ?");
        $stmt->execute([$_SESSION['character_id']]);
        $result = $stmt->fetch();
        
        if ($result && $result['position']) {
            $head_position = "Head of " . $department;
            return $result['position'] === $head_position;
        }
    } catch (Exception $e) {
        return false;
    }
    
    return false;
}

// Check if user can promote/demote members of a specific department
function canPromoteDemote($department = null) {
    if (!isLoggedIn()) return false;
    
    $roster_dept = $_SESSION['roster_department'] ?? '';
    
    // Command and Starfleet Auditors can promote/demote anyone
    if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor') {
        return true;
    }
    
    // Department heads can only promote/demote within their department
    if ($department && isDepartmentHead($department)) {
        return true;
    }
    
    return false;
}

// Get promotable ranks (up to Lieutenant for department heads)
function getPromotableRanks($isDepartmentHead = false) {
    $ranks = [
        'Crewman 3rd Class',
        'Crewman 2nd Class', 
        'Crewman 1st Class',
        'Petty Officer 3rd class',
        'Petty Officer 1st class',
        'Chief Petter Officer',
        'Senior Chief Petty Officer',
        'Master Chief Petty Officer',
        'Command Master Chief Petty Officer',
        'Warrant officer',
        'Ensign',
        'Lieutenant Junior Grade',
        'Lieutenant'
    ];
    
    // Department heads can promote up to Lieutenant
    // Command can promote higher (add more ranks as needed)
    if (!$isDepartmentHead && hasPermission('Command')) {
        $command_ranks = [
            'Lieutenant Commander',
            'Commander',
            'Captain'
        ];
        $ranks = array_merge($ranks, $command_ranks);
    }
    
    return $ranks;
}

// Check if current user is invisible (Starfleet Auditor or marked invisible)
function isInvisibleUser() {
    if (!isLoggedIn()) return false;
    
    $user_dept = getUserDepartment();
    
    // Starfleet Auditors are always invisible
    if ($user_dept === 'Starfleet Auditor') {
        return true;
    }
    
    // Check if user is manually marked as invisible
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT is_invisible FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $result = $stmt->fetch();
        return $result && $result['is_invisible'] == 1;
    } catch (Exception $e) {
        return false;
    }
}

// Check if a specific user ID is invisible
function isUserInvisible($user_id) {
    try {
        $pdo = getConnection();
        $stmt = $pdo->prepare("SELECT department, is_invisible FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        
        if (!$result) return false;
        
        // Starfleet Auditors are always invisible
        if ($result['department'] === 'Starfleet Auditor') {
            return true;
        }
        
        // Check manual invisibility flag
        return $result['is_invisible'] == 1;
    } catch (Exception $e) {
        return false;
    }
}

// Redirect if not authorized
function requirePermission($required_department) {
    // In showcase mode, do nothing
    if (defined('SHOWCASE_MODE') && SHOWCASE_MODE) {
        return;
    }
    if (!hasPermission($required_department)) {
        header('Location: login.php');
        exit();
    }
}

// Log auditor actions for accountability
function logAuditorAction($character_id, $action_type, $table_name, $record_id, $additional_data = null) {
    try {
        $pdo = getConnection();
        
        // Check if audit trail table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'character_audit_trail'");
        if ($stmt->rowCount() == 0) {
            return false; // Table doesn't exist yet
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO character_audit_trail 
            (character_id, action_type, table_name, record_id, additional_data) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $character_id,
            $action_type,
            $table_name,
            $record_id,
            $additional_data ? json_encode($additional_data) : null
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Failed to log auditor action: " . $e->getMessage());
        return false;
    }
}

// Get auditor activity log (viewable by Command and Starfleet Auditors)
function getAuditorActivityLog($limit = 50) {
    try {
        $pdo = getConnection();
        
        // Check if audit trail table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'character_audit_trail'");
        if ($stmt->rowCount() == 0) {
            return []; // Table doesn't exist yet
        }
        
        $stmt = $pdo->prepare("
            SELECT cat.*, r.first_name, r.last_name, r.rank
            FROM character_audit_trail cat
            JOIN roster r ON cat.auditor_roster_id = r.id
            ORDER BY cat.performed_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to get auditor activity log: " . $e->getMessage());
        return [];
    }
}

/**
 * Get the current user's full name for tracking updates
 */
function getCurrentUserFullName() {
    $first_name = $_SESSION['first_name'] ?? 'Unknown';
    $last_name = $_SESSION['last_name'] ?? 'User';
    return trim($first_name . ' ' . $last_name);
}

/**
 * Format a date/time for in-character display (adds 360 years)
 * This function should be used for all IC timestamps except last_active
 */
function formatICDate($datetime, $format = 'Y-m-d H:i') {
    if (empty($datetime)) {
        return '';
    }
    
    // Convert to timestamp
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    if ($timestamp === false) {
        return $datetime; // Return original if parsing fails
    }
    
    // Add 360 years (360 * 365.25 * 24 * 60 * 60 seconds)
    $ic_timestamp = $timestamp + (360 * 365.25 * 24 * 60 * 60);
    
    return date($format, $ic_timestamp);
}

/**
 * Format a date for in-character display (date only, adds 360 years)
 */
function formatICDateOnly($datetime) {
    return formatICDate($datetime, 'Y-m-d');
}

/**
 * Format a datetime for in-character display (full datetime, adds 360 years)
 */
function formatICDateTime($datetime, $format = 'Y-m-d H:i') {
    return formatICDate($datetime, $format);
}
?>
