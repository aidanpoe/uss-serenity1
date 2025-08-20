<?php
// Security headers for all pages
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data: https:; font-src \'self\'; connect-src \'self\' https://api.steampowered.com; frame-ancestors \'none\';');

// Disable server information disclosure
header_remove('X-Powered-By');
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', '1');
}
?>
