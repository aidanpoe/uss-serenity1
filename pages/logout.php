<?php
require_once '../includes/config.php';

// Check if this is a Steam user and redirect to Steam logout
if (isset($_SESSION['steamid'])) {
    header('Location: ../steamauth/steamauth.php?logout');
    exit();
}

// For non-Steam users (fallback), destroy session normally
session_destroy();

// Redirect to home page
header('Location: ../index.php');
exit();
?>
