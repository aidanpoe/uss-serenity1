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

// Create global PDO connection
$pdo = getConnection();

// Check if user is logged in (Steam only)
function isLoggedIn() {
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
                $user_department = 'MED/SCI';
                break;
            case 'Engineering':
            case 'Operations':
                $user_department = 'ENG/OPS';
                break;
            case 'Security':
            case 'Tactical':
                $user_department = 'SEC/TAC';
                break;
            case 'Command':
                $user_department = 'Command';
                break;
            default:
                $user_department = 'SEC/TAC'; // Default fallback
                break;
        }
        
        // Update active character and user permissions in database
        $stmt = $pdo->prepare("UPDATE users SET active_character_id = ?, department = ? WHERE id = ?");
        $stmt->execute([$character_id, $user_department, $_SESSION['user_id']]);
        
        // Update session variables with new character data
        $_SESSION['first_name'] = $character['first_name'];
        $_SESSION['last_name'] = $character['last_name'];
        $_SESSION['rank'] = $character['rank'];
        $_SESSION['position'] = $character['position'];
        $_SESSION['department'] = $user_department; // Use the mapped permission group
        $_SESSION['roster_department'] = $character['department']; // Store original department too
        
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

// Check if user has specific permission
function hasPermission($required_department) {
    if (!isLoggedIn()) return false;
    
    $user_dept = getUserDepartment();
    $user_rank = $_SESSION['rank'] ?? '';
    $roster_dept = $_SESSION['roster_department'] ?? '';
    
    // Captain and Command ranks have access to everything
    if ($user_rank === 'Captain' || $user_rank === 'Commander' || $user_dept === 'Command' || $roster_dept === 'Command') {
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

// Garry's Mod Server Query Functions
function queryGmodServer($ip, $port, $timeout = 5) {
    // First try: Standard Source Engine player query
    $result = querySourceEngine($ip, $port, $timeout);
    if ($result !== false) {
        return $result;
    }
    
    // Second try: Basic server info query
    $result = queryServerInfo($ip, $port, $timeout);
    if ($result !== false) {
        return $result;
    }
    
    // Third try: Simple socket connection test
    $result = testServerConnection($ip, $port, $timeout);
    return $result;
}

function querySourceEngine($ip, $port, $timeout) {
    try {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return false;
        }
        
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));
        
        // A2S_PLAYER query
        $challenge = "\xFF\xFF\xFF\xFF\x55\xFF\xFF\xFF\xFF";
        socket_sendto($socket, $challenge, strlen($challenge), 0, $ip, $port);
        
        $response = '';
        $from = '';
        $fromPort = 0;
        $bytes = socket_recvfrom($socket, $response, 4096, 0, $from, $fromPort);
        
        socket_close($socket);
        
        if ($bytes > 4) {
            return parseGmodResponse($response);
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

function queryServerInfo($ip, $port, $timeout) {
    try {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return false;
        }
        
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));
        
        // A2S_INFO query
        $infoQuery = "\xFF\xFF\xFF\xFF\x54Source Engine Query\x00";
        socket_sendto($socket, $infoQuery, strlen($infoQuery), 0, $ip, $port);
        
        $response = '';
        $from = '';
        $fromPort = 0;
        $bytes = socket_recvfrom($socket, $response, 4096, 0, $from, $fromPort);
        
        socket_close($socket);
        
        if ($bytes > 4) {
            return parseServerInfo($response);
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

function testServerConnection($ip, $port, $timeout) {
    try {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return false;
        }
        
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));
        
        // Simple ping
        $ping = "ping";
        $result = socket_sendto($socket, $ping, strlen($ping), 0, $ip, $port);
        
        socket_close($socket);
        
        if ($result !== false) {
            // Server is reachable but doesn't respond to queries
            return ['server_online' => true, 'query_supported' => false];
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

function parseServerInfo($response) {
    // Parse A2S_INFO response to get player count
    if (strlen($response) < 6) {
        return false;
    }
    
    // Skip header and check response type
    if (ord($response[4]) !== 0x49) { // 'I' for info
        return false;
    }
    
    try {
        $offset = 6; // Skip header and protocol
        
        // Skip server name (null-terminated string)
        while ($offset < strlen($response) && ord($response[$offset]) !== 0) {
            $offset++;
        }
        $offset++; // Skip null terminator
        
        // Skip map name (null-terminated string)
        while ($offset < strlen($response) && ord($response[$offset]) !== 0) {
            $offset++;
        }
        $offset++; // Skip null terminator
        
        // Skip folder name (null-terminated string)
        while ($offset < strlen($response) && ord($response[$offset]) !== 0) {
            $offset++;
        }
        $offset++; // Skip null terminator
        
        // Skip game name (null-terminated string)
        while ($offset < strlen($response) && ord($response[$offset]) !== 0) {
            $offset++;
        }
        $offset++; // Skip null terminator
        
        // Skip app ID (2 bytes)
        $offset += 2;
        
        // Get player count (1 byte)
        if ($offset < strlen($response)) {
            $playerCount = ord($response[$offset]);
            return ['count' => $playerCount, 'players' => [], 'info_only' => true];
        }
        
        return false;
        
    } catch (Exception $e) {
        return false;
    }
}

function parseGmodResponse($response) {
    // Skip the header (first 4 bytes are 0xFF)
    $data = substr($response, 4);
    
    if (strlen($data) < 2) {
        return false;
    }
    
    // Check if this is a player info response (0x44)
    if (ord($data[0]) !== 0x44) {
        return false;
    }
    
    $players = [];
    $offset = 2; // Skip header and player count
    
    try {
        $playerCount = ord($data[1]);
        
        for ($i = 0; $i < $playerCount && $offset < strlen($data); $i++) {
            // Skip index (1 byte)
            $offset++;
            
            // Read player name (null-terminated string)
            $nameStart = $offset;
            while ($offset < strlen($data) && ord($data[$offset]) !== 0) {
                $offset++;
            }
            
            if ($offset >= strlen($data)) break;
            
            $name = substr($data, $nameStart, $offset - $nameStart);
            $offset++; // Skip null terminator
            
            // Skip score (4 bytes) and duration (4 bytes)
            $offset += 8;
            
            if ($name && strlen($name) > 0) {
                $players[] = $name;
            }
        }
        
        return $players;
        
    } catch (Exception $e) {
        return false;
    }
}

function getGmodPlayersOnline() {
    $serverIP = '46.4.12.78';
    $serverPort = 27015;
    
    // Try to get data from cache first
    $cachedData = getServerStatusFromCache();
    if ($cachedData !== false) {
        return $cachedData;
    }
    
    $result = queryGmodServer($serverIP, $serverPort);
    
    if ($result === false) {
        // Server unreachable - check if we can at least connect
        $isReachable = testBasicConnection($serverIP, $serverPort);
        if ($isReachable) {
            return [
                'status' => 'online_queries_disabled',
                'message' => 'Server online - Player queries disabled for security',
                'server' => $serverIP . ':' . $serverPort,
                'can_connect' => true
            ];
        } else {
            return [
                'status' => 'unreachable',
                'message' => 'Server unreachable or offline',
                'server' => $serverIP . ':' . $serverPort,
                'can_connect' => false
            ];
        }
    }
    
    // Handle successful query responses
    if (isset($result['server_online']) && $result['server_online'] && !$result['query_supported']) {
        return [
            'status' => 'online_queries_disabled',
            'message' => 'Server online but queries disabled',
            'server' => $serverIP . ':' . $serverPort,
            'can_connect' => true
        ];
    }
    
    if (isset($result['info_only']) && $result['info_only']) {
        return [
            'players' => [],
            'count' => $result['count'],
            'server' => $serverIP . ':' . $serverPort,
            'status' => 'online_count_only',
            'info_only' => true
        ];
    }
    
    if (isset($result['count'])) {
        return [
            'players' => $result['players'] ?? [],
            'count' => $result['count'],
            'server' => $serverIP . ':' . $serverPort,
            'status' => 'online_full_data'
        ];
    }
    
    return [
        'status' => 'unknown',
        'message' => 'Unable to determine server status',
        'server' => $serverIP . ':' . $serverPort
    ];
}

function testBasicConnection($ip, $port, $timeout = 3) {
    try {
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        if (!$socket) {
            return false;
        }
        
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => $timeout, 'usec' => 0));
        
        // Send a simple UDP packet
        $testPacket = "ping";
        $result = socket_sendto($socket, $testPacket, strlen($testPacket), 0, $ip, $port);
        
        socket_close($socket);
        
        // If we can send to the port, assume server is running
        return $result !== false;
        
    } catch (Exception $e) {
        return false;
    }
}

function getServerStatusFromCache() {
    $cacheFile = __DIR__ . '/../cache/server_status.json';
    $cacheMaxAge = 60; // 1 minute cache
    
    if (!file_exists($cacheFile)) {
        return false;
    }
    
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    if (!$cacheData || !isset($cacheData['timestamp'])) {
        return false;
    }
    
    // Check if cache is still valid
    if ((time() - $cacheData['timestamp']) > $cacheMaxAge) {
        return false;
    }
    
    return $cacheData['data'];
}

function saveServerStatusToCache($data) {
    $cacheDir = __DIR__ . '/../cache';
    $cacheFile = $cacheDir . '/server_status.json';
    
    // Create cache directory if it doesn't exist
    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
    }
    
    $cacheData = [
        'timestamp' => time(),
        'data' => $data
    ];
    
    file_put_contents($cacheFile, json_encode($cacheData));
}

// Manual status update function (for when you know server status)
function updateServerStatusManually($playerCount = null, $playerList = null, $isOnline = true) {
    $serverIP = '46.4.12.78';
    $serverPort = 27015;
    
    if (!$isOnline) {
        $data = [
            'status' => 'offline',
            'message' => 'Server offline',
            'server' => $serverIP . ':' . $serverPort,
            'manual_update' => true,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    } else if ($playerCount !== null) {
        $data = [
            'players' => $playerList ?? [],
            'count' => $playerCount,
            'server' => $serverIP . ':' . $serverPort,
            'status' => 'online_manual_update',
            'manual_update' => true,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    } else {
        $data = [
            'status' => 'online_no_details',
            'message' => 'Server online - No player details available',
            'server' => $serverIP . ':' . $serverPort,
            'manual_update' => true,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    saveServerStatusToCache($data);
    return $data;
}

// Alternative function using cURL for HTTP-based server APIs (if available)
function getGmodPlayersViaCurl() {
    $serverIP = '46.4.12.78';
    $serverPort = 27015;
    
    // Some servers provide HTTP APIs on different ports
    $httpPorts = [80, 8080, 27016, 27017];
    
    foreach ($httpPorts as $port) {
        $url = "http://{$serverIP}:{$port}/api/players";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $response) {
            $data = json_decode($response, true);
            if ($data && isset($data['players'])) {
                return [
                    'players' => $data['players'],
                    'count' => count($data['players']),
                    'server' => $serverIP . ':' . $serverPort,
                    'method' => 'http_api'
                ];
            }
        }
    }
    
    return false;
}
?>
