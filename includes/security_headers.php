<?php
// Enhanced Security Headers - USS-Voyager NCC-74656
// Comprehensive security headers for all pages
try {
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Enable browser XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Control referrer information
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Permissions Policy (formerly Feature-Policy)
    header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
    
    // Content Security Policy - balanced security and functionality
    header("Content-Security-Policy: default-src 'self' 'unsafe-inline' 'unsafe-eval' data: https:; frame-ancestors 'none'; base-uri 'self'; form-action 'self';");
    
    // Prevent browsers from performing DNS prefetching
    header('X-DNS-Prefetch-Control: off');
    
    // Download options for IE
    header('X-Download-Options: noopen');
    
    // Disable server information disclosure
    @header_remove('X-Powered-By');
    @header_remove('Server');
    
    // Set secure cookie attributes for session cookies
    if (session_status() === PHP_SESSION_ACTIVE) {
        $cookieParams = session_get_cookie_params();
        session_set_cookie_params([
            'lifetime' => $cookieParams['lifetime'],
            'path' => $cookieParams['path'],
            'domain' => $cookieParams['domain'],
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', // Enable in production with HTTPS
            'httponly' => true, // Prevent JavaScript access to session cookie
            'samesite' => 'Lax' // CSRF protection
        ]);
    }
    
    // Disable gzip compression to prevent BREACH attack (in Apache environments)
    if (function_exists('apache_setenv')) {
        @apache_setenv('no-gzip', '1');
    }
    
} catch (Exception $e) {
    // Silent fail - don't break the site if headers fail
    // Log error for debugging but don't expose to users
    error_log("Security headers error: " . $e->getMessage());
}

// Additional security measures
// Disable error display in production (should also be set in php.ini)
if (!defined('DEVELOPMENT_MODE') || DEVELOPMENT_MODE === false) {
    @ini_set('display_errors', '0');
    @ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
    @ini_set('log_errors', '1');
}

// Prevent information disclosure
@ini_set('expose_php', '0');
?>
