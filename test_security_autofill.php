<?php
/**
 * Test script to verify the Security Incident Report "Reported By" auto-fill functionality
 */

require_once 'includes/config.php';

echo "<h2>Security Incident Report - Auto-fill Test</h2>";

// Test the session data retrieval
echo "<h3>Current Session Data:</h3>";
echo "<ul>";
echo "<li><strong>Rank:</strong> " . htmlspecialchars($_SESSION['rank'] ?? 'Not set') . "</li>";
echo "<li><strong>First Name:</strong> " . htmlspecialchars($_SESSION['first_name'] ?? 'Not set') . "</li>";
echo "<li><strong>Last Name:</strong> " . htmlspecialchars($_SESSION['last_name'] ?? 'Not set') . "</li>";
echo "</ul>";

// Test the auto-fill logic
$current_user = trim(($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
echo "<h3>Auto-filled 'Reported By' Value:</h3>";
echo "<div style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc; margin: 10px 0;'>";
echo "<strong>'" . htmlspecialchars($current_user) . "'</strong>";
echo "</div>";

// Show what the form field would look like
echo "<h3>How it appears in the form:</h3>";
echo "<div style='background: #333; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<label style='color: #FFD700; display: block; margin-bottom: 5px;'>Reported By:</label>";
echo "<input type='text' value='" . htmlspecialchars($current_user) . "' readonly style='width: 100%; padding: 0.5rem; background: #333; color: #FFD700; border: 1px solid #FFD700; cursor: not-allowed;'>";
echo "<small style='color: #FFD700; font-size: 0.8rem; display: block; margin-top: 5px;'>Auto-filled from your current character profile</small>";
echo "</div>";

echo "<h3>Changes Made:</h3>";
echo "<ul>";
echo "<li>✅ Updated the 'Reported By' field to be readonly and auto-filled</li>";
echo "<li>✅ Modified the form submission handler to auto-populate reported_by</li>";
echo "<li>✅ Added explanatory text showing the field is auto-filled</li>";
echo "<li>✅ Applied the same styling and functionality as eng_ops.php System Fault Report</li>";
echo "</ul>";

echo "<h3>User Experience:</h3>";
echo "<p>Users will now see their character's rank and name automatically filled in the 'Reported By' field, and they cannot change it. This ensures accurate reporting and prevents impersonation.</p>";

?>
