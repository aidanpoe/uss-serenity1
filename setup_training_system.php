<?php
require_once 'includes/config.php';

try {
    $pdo = getConnection();
    
    echo "<h2>Setting up Enhanced Training System Database</h2>";
    
    // Create training_files table for file uploads
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        department ENUM('MED/SCI', 'ENG/OPS', 'SEC/TAC', 'Command') NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        filename VARCHAR(255) NOT NULL,
        original_filename VARCHAR(255) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        uploaded_by INT NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        is_deleted TINYINT(1) DEFAULT 0,
        deleted_by INT NULL,
        deleted_date TIMESTAMP NULL,
        scheduled_deletion TIMESTAMP NULL,
        download_count INT DEFAULT 0,
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_department (department),
        INDEX idx_deleted (is_deleted),
        INDEX idx_deletion_date (scheduled_deletion)
    )");
    
    // Create training_audit table for comprehensive audit trail
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_audit (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_id INT NOT NULL,
        action ENUM('upload', 'download', 'delete', 'restore', 'permanent_delete') NOT NULL,
        performed_by INT NOT NULL,
        character_name VARCHAR(100),
        user_rank VARCHAR(50),
        user_department VARCHAR(50),
        user_agent TEXT,
        action_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        additional_notes TEXT,
        FOREIGN KEY (file_id) REFERENCES training_files(id) ON DELETE CASCADE,
        FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_file_id (file_id),
        INDEX idx_action (action),
        INDEX idx_date (action_date)
    )");
    
    // Create training_access_log for detailed access tracking
    $pdo->exec("CREATE TABLE IF NOT EXISTS training_access_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        file_id INT NOT NULL,
        accessed_by INT NOT NULL,
        access_type ENUM('view', 'download') NOT NULL,
        access_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        user_agent TEXT,
        FOREIGN KEY (file_id) REFERENCES training_files(id) ON DELETE CASCADE,
        FOREIGN KEY (accessed_by) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_file_access (file_id, accessed_by),
        INDEX idx_access_date (access_date)
    )");
    
    // Update existing training_documents table to work with new system
    $pdo->exec("ALTER TABLE training_documents 
               ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0,
               ADD COLUMN IF NOT EXISTS deleted_by INT NULL,
               ADD COLUMN IF NOT EXISTS deleted_date TIMESTAMP NULL,
               ADD COLUMN IF NOT EXISTS scheduled_deletion TIMESTAMP NULL,
               ADD FOREIGN KEY IF NOT EXISTS (deleted_by) REFERENCES users(id) ON DELETE SET NULL");
    
    // Create upload directories if they don't exist
    $upload_dirs = [
        'training_files',
        'training_files/deleted',
        'training_files/MED-SCI',
        'training_files/ENG-OPS', 
        'training_files/SEC-TAC',
        'training_files/Command'
    ];
    
    foreach ($upload_dirs as $dir) {
        $full_path = __DIR__ . '/' . $dir;
        if (!file_exists($full_path)) {
            if (mkdir($full_path, 0755, true)) {
                echo "<p>✅ Created directory: $dir</p>";
            } else {
                echo "<p>❌ Failed to create directory: $dir</p>";
            }
        } else {
            echo "<p>ℹ️ Directory already exists: $dir</p>";
        }
    }
    
    // Create .htaccess for training files security
    $htaccess_content = "# Training Files Security
Options -Indexes
<FilesMatch \"\\.(php|php3|php4|php5|phtml)$\">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Allow only authorized file types
<FilesMatch \"\\.(pdf|doc|docx|txt|rtf|odt|xls|xlsx|ppt|pptx|mp4|avi|mov|wmv|jpg|jpeg|png|gif|zip|rar)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>
";
    
    file_put_contents(__DIR__ . '/training_files/.htaccess', $htaccess_content);
    echo "<p>✅ Created security .htaccess file</p>";
    
    echo "<div style='background: #1a4a2e; padding: 15px; border: 2px solid #4caf50; margin: 20px 0; text-align: center;'>";
    echo "<h3>🎉 Enhanced Training System Database Setup Complete!</h3>";
    echo "<p>The training system is now ready with:</p>";
    echo "<ul style='text-align: left; display: inline-block;'>";
    echo "<li>✅ File upload and management tables</li>";
    echo "<li>✅ Comprehensive audit trail system</li>";
    echo "<li>✅ Access logging and tracking</li>";
    echo "<li>✅ Automated 90-day deletion system</li>";
    echo "<li>✅ Secure file storage directories</li>";
    echo "<li>✅ Department-based access control</li>";
    echo "</ul>";
    echo "</div>";
    
} catch(PDOException $e) {
    echo "<div style='background: #4a1a1a; padding: 15px; border-left: 4px solid #f44336;'>";
    echo "<h3>❌ Database Setup Error</h3>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    background: #000;
    color: #fff;
    margin: 20px;
}
h2, h3 {
    color: #ffcc00;
}
p, li {
    margin: 5px 0;
}
</style>
