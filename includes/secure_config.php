<?php
// Secure configuration file
// Move sensitive data to environment variables or a secure config file outside web root

// Load environment variables from .env file if available
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $_ENV[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
    }
}

// Database configuration - Use environment variables in production
define('DB_HOST', isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost');
define('DB_USERNAME', isset($_ENV['DB_USERNAME']) ? $_ENV['DB_USERNAME'] : 'mexico');
define('DB_PASSWORD', isset($_ENV['DB_PASSWORD']) ? $_ENV['DB_PASSWORD'] : 'pakistan'); // Change this password!
define('DB_NAME', isset($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'usss_serenity');
define('DB_PORT', isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : 3306);

// Steam API configuration - Use environment variables in production  
define('STEAM_API_KEY', isset($_ENV['STEAM_API_KEY']) ? $_ENV['STEAM_API_KEY'] : 'changemekeys'); // Change this key!
define('STEAM_DOMAIN', isset($_ENV['STEAM_DOMAIN']) ? $_ENV['STEAM_DOMAIN'] : 'uss-serenity.org');

// Security settings
define('SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_UPLOAD_SIZE', 5242880); // 5MB
define('UPLOAD_DIR', __DIR__ . '/../uploads/'); // Outside web root
define('ALLOWED_UPLOAD_TYPES', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf', 'application/msword', 
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'text/plain'
]);

// CSRF protection
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Secure file upload function
if (!function_exists('secureFileUpload')) {
    function secureFileUpload($file, $allowed_types = null, $max_size = null) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error");
        }
        
        $allowed_types = $allowed_types ?? ALLOWED_UPLOAD_TYPES;
        $max_size = $max_size ?? MAX_UPLOAD_SIZE;
        
        // Size check
        if ($file['size'] > $max_size) {
            throw new Exception("File too large. Maximum size: " . ($max_size / 1024 / 1024) . "MB");
        }
        
        // Extension check
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'txt'];
        if (!in_array($ext, $allowed_exts)) {
            throw new Exception("File type not allowed");
        }
        
        // MIME type check
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $allowed_types)) {
            throw new Exception("Invalid file content type");
        }
        
        // Additional validation for images
        if (strpos($mime_type, 'image/') === 0) {
            $image_info = getimagesize($file['tmp_name']);
            if ($image_info === false) {
                throw new Exception("Invalid image file");
            }
        }
        
        // Generate secure filename
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $upload_path = UPLOAD_DIR . $filename;
        
        // Create upload directory if it doesn't exist
        if (!is_dir(UPLOAD_DIR)) {
            if (!mkdir(UPLOAD_DIR, 0755, true)) {
                throw new Exception("Cannot create upload directory");
            }
        }
        
        // Move file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception("Failed to save file");
        }
        
        return $filename;
    }
}

// Input sanitization
if (!function_exists('sanitizeInput')) {
    function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
            case 'string':
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
}

// Output escaping
if (!function_exists('escapeOutput')) {
    function escapeOutput($output) {
        return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
    }
}
?>
