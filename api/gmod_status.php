<?php
header('Content-Type: application/json');
require_once 'includes/config.php';

// Get current server status
$gmodData = getGmodPlayersOnline();

// Return JSON response
echo json_encode($gmodData);
?>
