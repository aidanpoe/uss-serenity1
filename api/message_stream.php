<?php
/**
 * Real-time message streaming endpoint using Server-Sent Events (SSE)
 * Provides live updates for the messaging system
 */

require_once '../includes/config.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo "event: error\n";
    echo "data: Authentication required\n\n";
    exit;
}

// Set SSE headers
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Cache-Control');

// Disable output buffering for real-time streaming
if (ob_get_level()) ob_end_clean();

// Function to send SSE data
function sendSSE($event, $data) {
    echo "event: $event\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level()) ob_flush();
    flush();
}

// Initialize connection
sendSSE('connected', ['status' => 'Connected to message stream', 'timestamp' => time()]);

$pdo = getConnection();
$lastMessageId = intval($_GET['lastMessageId'] ?? 0);
$lastOnlineCheck = time();

// Main streaming loop
$startTime = time();
$maxRunTime = 300; // 5 minutes max connection time

while (time() - $startTime < $maxRunTime) {
    try {
        // Check for new messages
        $stmt = $pdo->prepare("
            SELECT id, sender_name, sender_rank, sender_department, message, timestamp, expires_at,
                   CASE WHEN sender_id = ? THEN 1 ELSE 0 END as is_own_message
            FROM crew_messages 
            WHERE id > ? AND is_deleted = 0 AND expires_at > NOW()
            ORDER BY timestamp ASC
            LIMIT 10
        ");
        $stmt->execute([$_SESSION['user_id'], $lastMessageId]);
        $newMessages = $stmt->fetchAll();
        
        if (!empty($newMessages)) {
            // Format messages
            foreach ($newMessages as &$msg) {
                $msg['timestamp'] = date('M j, H:i', strtotime($msg['timestamp']));
                $msg['expires_at'] = date('M j, H:i', strtotime($msg['expires_at']));
                $msg['is_own_message'] = (bool)$msg['is_own_message'];
                
                // Calculate time until expiration
                $expiresTimestamp = strtotime($msg['expires_at']);
                $daysUntilExpiry = ceil(($expiresTimestamp - time()) / (24 * 60 * 60));
                $msg['days_until_expiry'] = max(0, $daysUntilExpiry);
                
                $lastMessageId = max($lastMessageId, $msg['id']);
            }
            
            sendSSE('new_messages', $newMessages);
        }
        
        // Check for online users every 30 seconds
        if (time() - $lastOnlineCheck >= 30) {
            // Update current user's online status
            updateOnlineStatus($pdo);
            
            // Get online users
            $stmt = $pdo->prepare("
                SELECT character_name, rank_name, department, last_seen
                FROM crew_online_status 
                WHERE last_seen > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                AND user_id != ?
                ORDER BY last_seen DESC
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $onlineUsers = $stmt->fetchAll();
            
            foreach ($onlineUsers as &$user) {
                $user['last_seen'] = date('H:i', strtotime($user['last_seen']));
            }
            
            sendSSE('online_users', $onlineUsers);
            $lastOnlineCheck = time();
        }
        
        // Send heartbeat every minute
        static $lastHeartbeat = 0;
        if (time() - $lastHeartbeat >= 60) {
            sendSSE('heartbeat', ['timestamp' => time(), 'last_message_id' => $lastMessageId]);
            $lastHeartbeat = time();
        }
        
        // Check if client disconnected
        if (connection_aborted()) {
            break;
        }
        
        // Sleep for 2 seconds before next check
        sleep(2);
        
    } catch (Exception $e) {
        sendSSE('error', ['message' => $e->getMessage()]);
        break;
    }
}

sendSSE('disconnected', ['reason' => 'Stream ended']);

function updateOnlineStatus($pdo) {
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
