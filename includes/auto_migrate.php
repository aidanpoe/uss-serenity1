<?php
/**
 * Quick Database Migration Check
 * Include this in your main pages to automatically ensure database is up to date
 */

function checkAndRunMigrations() {
    if (!function_exists('getConnection')) {
        return false; // Can't run without database connection
    }
    
    try {
        $pdo = getConnection();
        
        // Check if last_active field exists
        $stmt = $pdo->query("SHOW COLUMNS FROM roster LIKE 'last_active'");
        $has_last_active = $stmt->rowCount() > 0;
        
        if (!$has_last_active) {
            // Auto-run the critical migration
            $pdo->exec("ALTER TABLE roster ADD COLUMN last_active TIMESTAMP NULL DEFAULT NULL");
            error_log("USS Serenity: Auto-migrated last_active field");
        }
        
        return true;
    } catch (Exception $e) {
        error_log("USS Serenity Migration Error: " . $e->getMessage());
        return false;
    }
}

// Uncomment the line below to enable auto-migration
// checkAndRunMigrations();
?>
