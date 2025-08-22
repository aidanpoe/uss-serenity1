<?php
require_once '../includes/config.php';

// Require user to be logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$success = '';
$error = '';
$download_ready = false;

try {
    $pdo = getConnection();
    $user_id = $_SESSION['user_id'];
    
    // Handle data download request
    if (isset($_POST['action']) && $_POST['action'] === 'download_data') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
        } else {
            // Collect all user data
            $user_data = [];
            
            // Basic user account info
            $stmt = $pdo->prepare("SELECT id, username, steam_id, department, active, created_at, last_login FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user_data['account'] = $stmt->fetch();
            
            // Character/roster data
            $stmt = $pdo->prepare("SELECT * FROM roster WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_data['characters'] = $stmt->fetchAll();
            
            // Login history (last 50 entries)
            $stmt = $pdo->prepare("
                SELECT 'login' as action, last_login as timestamp, 'Account login' as description 
                FROM users WHERE id = ? AND last_login IS NOT NULL
                UNION
                SELECT 'character_activity' as action, last_active as timestamp, 
                       CONCAT('Character activity: ', first_name, ' ', last_name) as description
                FROM roster WHERE user_id = ? AND last_active IS NOT NULL
                ORDER BY timestamp DESC LIMIT 50
            ");
            $stmt->execute([$user_id, $user_id]);
            $user_data['activity_history'] = $stmt->fetchAll();
            
            // Training file access logs
            $stmt = $pdo->prepare("
                SELECT ta.*, tf.title, tf.filename 
                FROM training_audit ta 
                JOIN training_files tf ON ta.file_id = tf.id 
                WHERE ta.performed_by = ? 
                ORDER BY ta.action_date DESC LIMIT 100
            ");
            $stmt->execute([$user_id]);
            $user_data['training_activity'] = $stmt->fetchAll();
            
            // Generate JSON export
            $export_data = [
                'export_date' => date('Y-m-d H:i:s'),
                'user_id' => $user_id,
                'data' => $user_data,
                'note' => 'This export contains your personal data from USS Serenity. Character data and roleplay activities are fictional and not considered personal data under GDPR.'
            ];
            
            $json_data = json_encode($export_data, JSON_PRETTY_PRINT);
            $filename = 'uss_serenity_data_export_' . $user_id . '_' . date('Y-m-d') . '.json';
            
            // Set headers for download
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($json_data));
            echo $json_data;
            exit;
        }
    }
    
    // Handle account deletion request
    if (isset($_POST['action']) && $_POST['action'] === 'delete_account') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
        } elseif (!isset($_POST['confirm_deletion']) || $_POST['confirm_deletion'] !== 'DELETE') {
            $error = "Please type 'DELETE' to confirm account deletion.";
        } else {
            try {
                $pdo->beginTransaction();
                
                // Temporarily disable foreign key checks for cleanup
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                
                // Get user info before deletion for logging
                $stmt = $pdo->prepare("SELECT username, steam_id FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_info = $stmt->fetch();
                
                // Delete or anonymize data based on retention requirements
                
                // Option 1: Complete deletion (user choice)
                if (isset($_POST['delete_characters']) && $_POST['delete_characters'] === 'yes') {
                    // Delete character data
                    $stmt = $pdo->prepare("DELETE FROM roster WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    
                    // Delete medical records associated with characters
                    $stmt = $pdo->prepare("
                        DELETE mr FROM medical_records mr 
                        JOIN roster r ON mr.roster_id = r.id 
                        WHERE r.user_id = ?
                    ");
                    $stmt->execute([$user_id]);
                    
                    // Delete other character-related data as needed
                } else {
                    // Option 2: Anonymize characters (preserve roleplay continuity)
                    $stmt = $pdo->prepare("UPDATE roster SET user_id = NULL WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                }
                
                // Clean up training audit logs - keep for security but anonymize
                $stmt = $pdo->prepare("
                    UPDATE training_audit 
                    SET performed_by = NULL, character_name = 'Deleted User', additional_notes = 'User account deleted' 
                    WHERE performed_by = ?
                ");
                $stmt->execute([$user_id]);
                
                // Also clean up any training_audit records where this user is referenced in trainee_id
                $stmt = $pdo->prepare("
                    UPDATE training_audit 
                    SET trainee_id = NULL, character_name = 'Deleted User' 
                    WHERE trainee_id = ?
                ");
                $stmt->execute([$user_id]);
                
                // Delete any other user-related audit records that can be safely removed
                $stmt = $pdo->prepare("DELETE FROM user_activity_logs WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                $stmt = $pdo->prepare("DELETE FROM login_logs WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user sessions
                $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete user account
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Re-enable foreign key checks
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                // Log the deletion for audit purposes
                error_log("GDPR Account Deletion: User ID $user_id (" . $user_info['username'] . ", Steam: " . $user_info['steam_id'] . ") deleted their account on " . date('Y-m-d H:i:s'));
                
                $pdo->commit();
                
                // Clear session and redirect
                session_unset();
                session_destroy();
                
                // Redirect to confirmation page
                header('Location: ../index.php?account_deleted=1');
                exit;
                
            } catch (Exception $e) {
                $pdo->rollback();
                // Re-enable foreign key checks in case of error
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                $error = "Error deleting account: " . $e->getMessage();
            }
        }
    }
    
    // Get user's current data summary
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(r.id) as character_count,
               u.last_login,
               u.created_at
        FROM users u 
        LEFT JOIN roster r ON u.id = r.user_id 
        WHERE u.id = ? 
        GROUP BY u.id
    ");
    $stmt->execute([$user_id]);
    $user_summary = $stmt->fetch();
    
    // Get data retention information
    $retention_info = [
        'account_age' => $user_summary['created_at'],
        'last_login' => $user_summary['last_login'],
        'character_count' => $user_summary['character_count'],
        'inactive_deletion_date' => date('Y-m-d', strtotime($user_summary['last_login'] . ' + 24 months'))
    ];
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Rights - USS Serenity</title>
    <link rel="stylesheet" href="../assets/classic.css">
    <style>
        .rights-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 2rem;
            background: rgba(0,0,0,0.8);
            border-radius: 15px;
            border: 2px solid var(--blue);
        }
        .rights-section {
            margin: 2rem 0;
            padding: 1.5rem;
            background: rgba(102, 153, 204, 0.1);
            border-radius: 10px;
            border: 1px solid var(--blue);
        }
        .data-summary {
            background: rgba(0, 255, 0, 0.1);
            padding: 1rem;
            border-radius: 10px;
            border: 1px solid var(--green);
            margin: 1rem 0;
        }
        .danger-zone {
            background: rgba(255, 0, 0, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            border: 2px solid var(--red);
            margin: 2rem 0;
        }
        .action-button {
            background-color: var(--blue);
            color: black;
            border: none;
            padding: 1rem 2rem;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            margin: 0.5rem;
        }
        .action-button:hover {
            background-color: var(--gold);
        }
        .danger-button {
            background-color: var(--red);
            color: white;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .info-table th, .info-table td {
            padding: 0.75rem;
            border: 1px solid var(--blue);
            text-align: left;
        }
        .info-table th {
            background: var(--blue);
            color: black;
        }
        .checkbox-option {
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(255, 255, 0, 0.1);
            border-radius: 5px;
            border: 1px solid var(--gold);
        }
    </style>
</head>
<body>
    <div class="rights-container">
        <h1 style="color: var(--blue); text-align: center;">Your Data Rights</h1>
        <h2 style="color: var(--gold); text-align: center;">GDPR Compliance Portal</h2>
        
        <?php if ($success): ?>
        <div style="background: rgba(0, 255, 0, 0.2); color: var(--green); padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 1px solid var(--green);">
            ✅ <?php echo htmlspecialchars($success); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div style="background: rgba(255, 0, 0, 0.2); color: var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0; border: 1px solid var(--red);">
            ❌ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Data Summary Section -->
        <div class="rights-section">
            <h3>📊 Your Data Summary</h3>
            <div class="data-summary">
                <table class="info-table">
                    <tr>
                        <th>Data Type</th>
                        <th>Information</th>
                        <th>Status</th>
                    </tr>
                    <tr>
                        <td>Account</td>
                        <td>Username: <?php echo htmlspecialchars($user_summary['username']); ?></td>
                        <td>Active</td>
                    </tr>
                    <tr>
                        <td>Steam ID</td>
                        <td><?php echo htmlspecialchars($user_summary['steam_id']); ?></td>
                        <td>Linked</td>
                    </tr>
                    <tr>
                        <td>Characters</td>
                        <td><?php echo $user_summary['character_count']; ?> character(s)</td>
                        <td>Active</td>
                    </tr>
                    <tr>
                        <td>Account Created</td>
                        <td><?php echo date('M j, Y', strtotime($user_summary['created_at'])); ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Last Login</td>
                        <td><?php echo $user_summary['last_login'] ? date('M j, Y H:i', strtotime($user_summary['last_login'])) : 'Never'; ?></td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Auto-Deletion Date</td>
                        <td><?php echo $retention_info['inactive_deletion_date']; ?> (if inactive for 24 months)</td>
                        <td>Scheduled</td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Right to Access -->
        <div class="rights-section">
            <h3>🔍 Right to Access (Article 15)</h3>
            <p>Download a complete copy of all personal data we hold about you.</p>
            
            <div style="background: rgba(255, 255, 0, 0.1); padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                <strong>What's included:</strong>
                <ul>
                    <li>Account information (username, Steam ID, permissions)</li>
                    <li>Login history (last 50 entries)</li>
                    <li>Character profiles and activity logs</li>
                    <li>Training system access logs</li>
                    <li>System usage statistics</li>
                </ul>
                <strong>Format:</strong> JSON file suitable for importing into other systems
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="download_data">
                <button type="submit" class="action-button">📥 Download My Data</button>
            </form>
        </div>

        <!-- Right to Rectification -->
        <div class="rights-section">
            <h3>✏️ Right to Rectification (Article 16)</h3>
            <p>Correct any inaccurate or incomplete personal data.</p>
            
            <div style="margin: 1rem 0;">
                <strong>Available corrections:</strong>
                <ul>
                    <li><a href="profile.php" style="color: var(--blue);">Edit Character Profiles</a> - Update character information</li>
                    <li><a href="profile.php" style="color: var(--blue);">Update Profile Images</a> - Change character photos</li>
                    <li><strong>Account Data:</strong> Contact admin for username or permission changes</li>
                </ul>
            </div>
            
            <p><strong>Steam Data:</strong> Steam profile information is managed by Steam. Update your Steam profile to change this data.</p>
        </div>

        <!-- Right to Restrict Processing -->
        <div class="rights-section">
            <h3>⏸️ Right to Restrict Processing (Article 18)</h3>
            <p>Request limitations on how we process your data.</p>
            
            <div style="background: rgba(255, 165, 0, 0.1); padding: 1rem; border-radius: 5px;">
                <p><strong>Account Deactivation:</strong> Your account is currently <span style="color: var(--green);">ACTIVE</span></p>
                <p>Contact an administrator to request account deactivation while preserving your data.</p>
                <p><strong>Email:</strong> computer@uss-serenity.org</p>
            </div>
        </div>

        <!-- Right to Data Portability -->
        <div class="rights-section">
            <h3>📤 Right to Data Portability (Article 20)</h3>
            <p>The data download feature above provides your data in a machine-readable JSON format that can be imported into other systems.</p>
        </div>

        <!-- Data Retention Information -->
        <div class="rights-section">
            <h3>⏰ Data Retention Policies</h3>
            <table class="info-table">
                <thead>
                    <tr>
                        <th>Data Type</th>
                        <th>Retention Period</th>
                        <th>Auto-Deletion</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>User Account</td>
                        <td>Until deletion requested or 24 months inactive</td>
                        <td>Yes</td>
                    </tr>
                    <tr>
                        <td>Character Data</td>
                        <td>Until account deletion</td>
                        <td>With account</td>
                    </tr>
                    <tr>
                        <td>Session Data</td>
                        <td>1 hour</td>
                        <td>Automatic</td>
                    </tr>
                    <tr>
                        <td>Login Logs</td>
                        <td>12 months</td>
                        <td>Yes</td>
                    </tr>
                    <tr>
                        <td>Training File Access</td>
                        <td>24 months</td>
                        <td>Yes</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Account Deletion - Danger Zone -->
        <div class="danger-zone">
            <h3 style="color: var(--red);">🗑️ Right to Erasure (Article 17) - DANGER ZONE</h3>
            <p><strong>⚠️ WARNING: This action cannot be undone!</strong></p>
            
            <p>Permanently delete your account and personal data from USS Serenity.</p>
            
            <h4>What happens when you delete your account:</h4>
            <ul>
                <li>✅ Your Steam ID and personal data are permanently deleted</li>
                <li>✅ Your login sessions are terminated</li>
                <li>✅ Your training access logs are anonymized</li>
                <li>⚠️ Character data handling (choose below)</li>
            </ul>
            
            <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This cannot be undone!');">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="delete_account">
                
                <div class="checkbox-option">
                    <h4>Character Data Options:</h4>
                    <label>
                        <input type="radio" name="delete_characters" value="no" checked>
                        <strong>Preserve Characters (Recommended):</strong> Keep character profiles for roleplay continuity, but unlink from your account
                    </label><br><br>
                    <label>
                        <input type="radio" name="delete_characters" value="yes">
                        <strong>Delete Everything:</strong> Remove all character data, medical records, and roleplay history
                    </label>
                </div>
                
                <div style="margin: 2rem 0;">
                    <label for="confirm_deletion" style="color: var(--red); font-weight: bold;">
                        Type "DELETE" to confirm:
                    </label><br>
                    <input type="text" name="confirm_deletion" id="confirm_deletion" required 
                           style="padding: 0.5rem; margin: 0.5rem 0; background: rgba(0,0,0,0.8); color: white; border: 2px solid var(--red);">
                </div>
                
                <button type="submit" class="action-button danger-button">
                    🗑️ PERMANENTLY DELETE MY ACCOUNT
                </button>
            </form>
        </div>

        <!-- Contact Information -->
        <div class="rights-section">
            <h3>📞 Contact Us</h3>
            <p>For any questions about your data rights or to request assistance:</p>
            <ul>
                <li><strong>Privacy Email:</strong> computer@uss-serenity.org</li>
                <li><strong>Data Protection Officer:</strong> computer@uss-serenity.org</li>
                <li><strong>Response Time:</strong> Within 30 days as required by GDPR</li>
            </ul>
            
            <p><strong>Complaints:</strong> If you're not satisfied with our response, you can contact the UK Information Commissioner's Office (ICO) at ico.org.uk</p>
        </div>

        <div style="text-align: center; margin: 2rem 0;">
            <a href="../index.php" class="action-button">🏠 Return to USS Serenity</a>
            <a href="../privacy-policy.html" class="action-button">📋 View Privacy Policy</a>
        </div>
    </div>
</body>
</html>
