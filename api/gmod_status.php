<?php
header('Content-Type: application/json');
require_once '../includes/config.php';

try {
    // Get current server status
    $gmodData = getGmodPlayersOnline();
    
    // Ensure we have a status field
    if (!isset($gmodData['status'])) {
        // Handle legacy format
        if (isset($gmodData['error'])) {
            $gmodData['status'] = $gmodData['server_reachable'] ? 'online_queries_disabled' : 'unreachable';
        } elseif (isset($gmodData['count'])) {
            $gmodData['status'] = $gmodData['count'] > 0 ? 'online_full_data' : 'online_full_data';
        } else {
            $gmodData['status'] = 'unreachable';
        }
    }
    
    // Return JSON response
    echo json_encode($gmodData);
    
} catch (Exception $e) {
    // Error handling
    echo json_encode([
        'status' => 'unreachable',
        'error' => 'Failed to get server status',
        'message' => $e->getMessage(),
        'count' => 0,
        'server' => '46.4.12.78:27015'
    ]);
}
?>
