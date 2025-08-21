<?php
/**
 * GDPR Data Retention Cleanup Script
 * This script enforces data retention policies as defined in the privacy policy
 * Should be run as a scheduled task (cron job) daily
 */

require_once __DIR__ . '/includes/config.php';

// Log cleanup activity
function logCleanup($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] GDPR Cleanup: $message\n";
    error_log($logMessage);
    echo $logMessage;
}

try {
    $pdo = getConnection();
    logCleanup("Starting GDPR data retention cleanup...");
    
    // 1. Clean up old login logs (12 months retention)
    $stmt = $pdo->prepare("
        UPDATE users 
        SET last_login = NULL 
        WHERE last_login < DATE_SUB(NOW(), INTERVAL 12 MONTH)
    ");
    $stmt->execute();
    $cleaned_logins = $stmt->rowCount();
    logCleanup("Cleaned $cleaned_logins old login timestamps (12+ months old)");
    
    // 2. Clean up old training audit logs (24 months retention)
    $stmt = $pdo->prepare("
        DELETE FROM training_audit 
        WHERE action_date < DATE_SUB(NOW(), INTERVAL 24 MONTH)
    ");
    $stmt->execute();
    $cleaned_training = $stmt->rowCount();
    logCleanup("Deleted $cleaned_training old training audit entries (24+ months old)");
    
    // 3. Clean up old training access logs (24 months retention)
    $stmt = $pdo->prepare("
        DELETE FROM training_access_log 
        WHERE access_date < DATE_SUB(NOW(), INTERVAL 24 MONTH)
    ");
    $stmt->execute();
    $cleaned_access = $stmt->rowCount();
    logCleanup("Deleted $cleaned_access old training access logs (24+ months old)");
    
    // 4. Permanently delete training files that have been in deleted folder for 90+ days
    $stmt = $pdo->prepare("
        SELECT id, filename, department FROM training_files 
        WHERE is_deleted = 1 
        AND scheduled_deletion < NOW()
    ");
    $stmt->execute();
    $files_to_delete = $stmt->fetchAll();
    
    foreach ($files_to_delete as $file) {
        // Delete physical file
        $dept_folder = str_replace('/', '-', $file['department']);
        $deleted_path = __DIR__ . "/training_files/deleted/" . $file['filename'];
        
        if (file_exists($deleted_path)) {
            unlink($deleted_path);
            logCleanup("Permanently deleted file: " . $file['filename']);
        }
        
        // Delete database record
        $deleteStmt = $pdo->prepare("DELETE FROM training_files WHERE id = ?");
        $deleteStmt->execute([$file['id']]);
    }
    logCleanup("Permanently deleted " . count($files_to_delete) . " training files (90+ days in deleted folder)");
    
    // 5. Identify and handle inactive accounts (24 months without login)
    $stmt = $pdo->prepare("
        SELECT id, username, steam_id, created_at, last_login 
        FROM users 
        WHERE (last_login IS NULL AND created_at < DATE_SUB(NOW(), INTERVAL 24 MONTH))
           OR (last_login < DATE_SUB(NOW(), INTERVAL 24 MONTH))
    ");
    $stmt->execute();
    $inactive_accounts = $stmt->fetchAll();
    
    foreach ($inactive_accounts as $account) {
        logCleanup("INACTIVE ACCOUNT DETECTED: User ID {$account['id']} ({$account['username']}, Steam: {$account['steam_id']}) - Last login: " . ($account['last_login'] ?: 'Never'));
        
        // For now, just log - actual deletion should be carefully considered
        // Uncomment below to enable automatic deletion after thorough testing
        /*
        try {
            $pdo->beginTransaction();
            
            // Anonymize character data instead of deleting (preserve roleplay continuity)
            $stmt = $pdo->prepare("UPDATE roster SET user_id = NULL WHERE user_id = ?");
            $stmt->execute([$account['id']]);
            
            // Anonymize training audit logs
            $stmt = $pdo->prepare("
                UPDATE training_audit 
                SET performed_by = NULL, character_name = 'Inactive User', additional_notes = 'Account auto-deleted due to inactivity' 
                WHERE performed_by = ?
            ");
            $stmt->execute([$account['id']]);
            
            // Delete user account
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$account['id']]);
            
            $pdo->commit();
            logCleanup("Auto-deleted inactive account: User ID {$account['id']}");
            
        } catch (Exception $e) {
            $pdo->rollback();
            logCleanup("ERROR deleting inactive account {$account['id']}: " . $e->getMessage());
        }
        */
    }
    
    if (count($inactive_accounts) > 0) {
        logCleanup("WARNING: " . count($inactive_accounts) . " inactive accounts detected. Enable auto-deletion in script after review.");
    }
    
    // 6. Clean up expired sessions (already handled by PHP session garbage collection, but log it)
    logCleanup("Session cleanup handled by PHP session.gc_maxlifetime (1 hour)");
    
    // 7. Anonymize old IP address logs if stored separately (6 months retention)
    // Note: Current implementation doesn't store IP addresses separately, but this is where you would clean them
    logCleanup("IP address log cleanup: Not applicable (no separate IP log table)");
    
    // 8. Clean up old message expiration (from messaging system)
    $stmt = $pdo->prepare("DELETE FROM crew_messages WHERE expires_at < NOW()");
    $stmt->execute();
    $cleaned_messages = $stmt->rowCount();
    logCleanup("Deleted $cleaned_messages expired messages");
    
    // 9. Clean up message reactions for deleted messages
    $stmt = $pdo->prepare("
        DELETE mr FROM message_reactions mr
        LEFT JOIN crew_messages cm ON mr.message_id = cm.id
        WHERE cm.id IS NULL
    ");
    $stmt->execute();
    $cleaned_reactions = $stmt->rowCount();
    logCleanup("Deleted $cleaned_reactions orphaned message reactions");
    
    // Summary
    $total_cleaned = $cleaned_logins + $cleaned_training + $cleaned_access + count($files_to_delete) + $cleaned_messages + $cleaned_reactions;
    logCleanup("GDPR cleanup completed successfully. Total items processed: $total_cleaned");
    
    // Generate compliance report
    $report = [
        'cleanup_date' => date('Y-m-d H:i:s'),
        'items_cleaned' => [
            'login_logs' => $cleaned_logins,
            'training_audit' => $cleaned_training,
            'training_access' => $cleaned_access,
            'deleted_files' => count($files_to_delete),
            'expired_messages' => $cleaned_messages,
            'orphaned_reactions' => $cleaned_reactions
        ],
        'inactive_accounts_detected' => count($inactive_accounts),
        'total_items_processed' => $total_cleaned,
        'next_cleanup' => date('Y-m-d H:i:s', strtotime('+1 day'))
    ];
    
    // Save compliance report
    file_put_contents(__DIR__ . '/gdpr_cleanup_report.json', json_encode($report, JSON_PRETTY_PRINT));
    logCleanup("Compliance report saved to gdpr_cleanup_report.json");
    
} catch (Exception $e) {
    logCleanup("ERROR: " . $e->getMessage());
    exit(1);
}

// If running from web interface, show status
if (isset($_SERVER['HTTP_HOST'])) {
    echo "<h2>GDPR Data Retention Cleanup Complete</h2>";
    echo "<p>Check server logs for detailed information.</p>";
    echo "<p><a href='index.php'>Return to USS Serenity</a></p>";
}
?>
