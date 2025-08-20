<?php
require_once '../includes/config.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Set content type
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = getConnection();
    
    switch ($action) {
        case 'send_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $message = trim($_POST['message'] ?? '');
            if (empty($message)) {
                echo json_encode(['error' => 'Message cannot be empty']);
                exit;
            }
            
            if (strlen($message) > 500) {
                echo json_encode(['error' => 'Message too long (max 500 characters)']);
                exit;
            }
            
            // Get current character info
            $sender_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
            $sender_rank = $_SESSION['rank'] ?? '';
            $sender_department = $_SESSION['department'] ?? '';
            
            // Insert message
            $stmt = $pdo->prepare("
                INSERT INTO crew_messages (sender_id, sender_name, sender_rank, sender_department, message) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$_SESSION['user_id'], $sender_name, $sender_rank, $sender_department, $message]);
            
            // Update online status
            updateOnlineStatus();
            
            echo json_encode(['success' => true, 'message' => 'Message sent']);
            break;
            
        case 'get_messages':
            $limit = min(50, max(10, intval($_GET['limit'] ?? 25))); // Between 10-50 messages
            $before_id = intval($_GET['before_id'] ?? 0);
            
            $sql = "
                SELECT id, sender_name, sender_rank, sender_department, message, timestamp,
                       CASE WHEN sender_id = ? THEN 1 ELSE 0 END as is_own_message
                FROM crew_messages 
                WHERE is_deleted = 0
            ";
            $params = [$_SESSION['user_id']];
            
            if ($before_id > 0) {
                $sql .= " AND id < ?";
                $params[] = $before_id;
            }
            
            $sql .= " ORDER BY timestamp DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $messages = $stmt->fetchAll();
            
            // Reverse to show oldest first
            $messages = array_reverse($messages);
            
            // Format timestamps
            foreach ($messages as &$msg) {
                $msg['timestamp'] = date('M j, H:i', strtotime($msg['timestamp']));
                $msg['is_own_message'] = (bool)$msg['is_own_message'];
            }
            
            echo json_encode(['messages' => $messages]);
            break;
            
        case 'get_online_users':
            // Update current user's online status first
            updateOnlineStatus();
            
            // Get users who have been active in last 5 minutes
            $stmt = $pdo->prepare("
                SELECT character_name, rank_name, department, last_seen
                FROM crew_online_status 
                WHERE last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                AND user_id != ?
                ORDER BY last_seen DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $online_users = $stmt->fetchAll();
            
            foreach ($online_users as &$user) {
                $user['last_seen'] = date('H:i', strtotime($user['last_seen']));
            }
            
            echo json_encode(['online_users' => $online_users]);
            break;
            
        case 'delete_message':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            
            $message_id = intval($_POST['message_id'] ?? 0);
            if ($message_id <= 0) {
                echo json_encode(['error' => 'Invalid message ID']);
                exit;
            }
            
            // Check if user owns the message or has command privileges
            $user_dept = $_SESSION['department'] ?? '';
            $is_command = (strtolower($user_dept) === 'command');
            
            if ($is_command) {
                // Command department can delete any message
                $stmt = $pdo->prepare("UPDATE crew_messages SET is_deleted = 1 WHERE id = ?");
                $stmt->execute([$message_id]);
            } else {
                // Regular users can only delete their own messages
                $stmt = $pdo->prepare("UPDATE crew_messages SET is_deleted = 1 WHERE id = ? AND sender_id = ?");
                $stmt->execute([$message_id, $_SESSION['user_id']]);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

// Function to update online status
function updateOnlineStatus() {
    global $pdo;
    
    if (!isLoggedIn()) return;
    
    $character_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
    $rank_name = $_SESSION['rank'] ?? '';
    $department = $_SESSION['department'] ?? '';
    
    $stmt = $pdo->prepare("
        INSERT INTO crew_online_status (user_id, character_name, rank_name, department, last_seen, is_online) 
        VALUES (?, ?, ?, ?, NOW(), 1)
        ON DUPLICATE KEY UPDATE 
        character_name = VALUES(character_name),
        rank_name = VALUES(rank_name),
        department = VALUES(department),
        last_seen = NOW(),
        is_online = 1
    ");
    $stmt->execute([$_SESSION['user_id'], $character_name, $rank_name, $department]);
}
?>
