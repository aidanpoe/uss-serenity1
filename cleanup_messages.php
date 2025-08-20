<?php
/**
 * Message Cleanup Script
 * This script removes expired messages from the database
 * Run this via cron job or manually to clean up old messages
 */

require_once __DIR__ . '/includes/config.php';

try {
    $pdo = getConnection();
    
    // Log the cleanup attempt
    $logMessage = "[" . date('Y-m-d H:i:s') . "] Starting message cleanup...\n";
    
    // Delete expired messages and their reactions
    $stmt = $pdo->prepare("
        DELETE mr FROM message_reactions mr
        INNER JOIN crew_messages cm ON mr.message_id = cm.id
        WHERE cm.expires_at < NOW()
    ");
    $stmt->execute();
    $deletedReactions = $stmt->rowCount();
    
    // Delete expired messages
    $stmt = $pdo->prepare("DELETE FROM crew_messages WHERE expires_at < NOW()");
    $stmt->execute();
    $deletedMessages = $stmt->rowCount();
    
    $logMessage .= "[" . date('Y-m-d H:i:s') . "] Cleanup completed. Deleted {$deletedMessages} messages and {$deletedReactions} reactions.\n";
    
    // If running from command line, output to console
    if (php_sapi_name() === 'cli') {
        echo $logMessage;
    }
    
    // Log to file if desired
    // file_put_contents(__DIR__ . '/logs/message_cleanup.log', $logMessage, FILE_APPEND | LOCK_EX);
    
    // Return JSON if accessed via web
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'deleted_messages' => $deletedMessages,
            'deleted_reactions' => $deletedReactions,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
} catch (Exception $e) {
    $errorMessage = "[" . date('Y-m-d H:i:s') . "] Error during cleanup: " . $e->getMessage() . "\n";
    
    if (php_sapi_name() === 'cli') {
        echo $errorMessage;
        exit(1);
    } else {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
?>
