<?php
// Security headers for all pages - with error checking
try {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Simplified CSP that should work on most servers
    header('Content-Security-Policy: default-src \'self\' \'unsafe-inline\' \'unsafe-eval\' data: https:;');

    // Disable server information disclosure
    @header_remove('X-Powered-By');
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
} catch (Exception $e) {
    // Silent fail - don't break the site if headers fail
    error_log("Security headers error: " . $e->getMessage());
}
?>
