<?php
// Handle promotion/demotion form submissions
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'promote_demote') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $promotion_error = "Invalid security token. Please try again.";
    } else {
        $character_id = filter_var($_POST['character_id'], FILTER_VALIDATE_INT);
        $new_rank = sanitizeInput($_POST['new_rank']);
        $department = sanitizeInput($_POST['department']);
        
        // Permission checks
        $roster_dept = $_SESSION['roster_department'] ?? '';
        $can_promote = false;
        
        if (hasPermission('Command') || $roster_dept === 'Starfleet Auditor') {
            $can_promote = true;
        } elseif (isDepartmentHead($department)) {
            $can_promote = true;
        }
        
        if (!$can_promote) {
            $promotion_error = "You do not have permission to promote/demote members of this department.";
        } else {
            try {
                $pdo = getConnection();
                
                // Get current character info for logging
                $stmt = $pdo->prepare("SELECT first_name, last_name, rank, department FROM roster WHERE id = ?");
                $stmt->execute([$character_id]);
                $character = $stmt->fetch();
                
                if (!$character) {
                    $promotion_error = "Character not found.";
                } else {
                    // Verify the character is in the correct department (unless Command/Auditor)
                    if (!hasPermission('Command') && $roster_dept !== 'Starfleet Auditor') {
                        if ($character['department'] !== $department) {
                            $promotion_error = "You can only promote/demote members of your own department.";
                        }
                    }
                    
                    if (!isset($promotion_error)) {
                        // Validate rank choice for department heads
                        $valid_ranks = getPromotableRanks(isDepartmentHead($department));
                        if (!in_array($new_rank, $valid_ranks)) {
                            $promotion_error = "Invalid rank selection.";
                        } else {
                            $old_rank = $character['rank'];
                            
                            // Update the rank
                            $stmt = $pdo->prepare("UPDATE roster SET rank = ? WHERE id = ?");
                            $stmt->execute([$new_rank, $character_id]);
                            
                            // Log the promotion/demotion
                            if (isset($_SESSION['character_id'])) {
                                $action_type = 'promotion_demotion';
                                $user_type = $roster_dept === 'Starfleet Auditor' ? 'Starfleet Auditor' : 
                                           (hasPermission('Command') ? 'Command Staff' : 'Department Head');
                                
                                logAuditorAction($_SESSION['character_id'], $action_type, 'roster', $character_id, [
                                    'character_name' => $character['first_name'] . ' ' . $character['last_name'],
                                    'old_rank' => $old_rank,
                                    'new_rank' => $new_rank,
                                    'department' => $character['department'],
                                    'user_type' => $user_type
                                ]);
                            }
                            
                            $promotion_success = "Successfully updated " . htmlspecialchars($character['first_name'] . ' ' . $character['last_name']) . 
                                               " from " . htmlspecialchars($old_rank) . " to " . htmlspecialchars($new_rank) . ".";
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error promoting/demoting character: " . $e->getMessage());
                $promotion_error = "Error updating rank. Please try again.";
            }
        }
    }
}

// Function to render the promotion/demotion form
function renderPromotionForm($department) {
    $roster_dept = $_SESSION['roster_department'] ?? '';
    
    // Check if user has permission to see the form
    $can_see_form = hasPermission('Command') || 
                    $roster_dept === 'Starfleet Auditor' || 
                    isDepartmentHead($department);
    
    if (!$can_see_form) {
        return;
    }
    
    try {
        $pdo = getConnection();
        
        // Get department members
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, rank 
            FROM roster 
            WHERE department = ? AND (is_invisible IS NULL OR is_invisible = 0)
            ORDER BY last_name, first_name
        ");
        $stmt->execute([$department]);
        $dept_members = $stmt->fetchAll();
        
        $valid_ranks = getPromotableRanks(isDepartmentHead($department));
        
        // Determine department color
        $dept_colors = [
            'MED/SCI' => 'blue',
            'ENG/OPS' => 'orange', 
            'SEC/TAC' => 'red'
        ];
        $color = $dept_colors[$department] ?? 'gold';
        
        echo '<div style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--' . $color . ');">';
        echo '<h3 style="color: var(--' . $color . '); text-align: center; margin-bottom: 1.5rem;">🎖️ ' . htmlspecialchars($department) . ' Promotion/Demotion</h3>';
        
        // Display any messages
        global $promotion_success, $promotion_error;
        if (isset($promotion_success)) {
            echo '<div style="background: rgba(85, 102, 255, 0.3); border: 2px solid var(--blue); padding: 1rem; border-radius: 10px; margin: 1rem 0;">';
            echo '<p style="color: var(--blue);">' . $promotion_success . '</p>';
            echo '</div>';
        }
        if (isset($promotion_error)) {
            echo '<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">';
            echo '<p style="color: var(--red);">' . $promotion_error . '</p>';
            echo '</div>';
        }
        
        echo '<form method="POST" action="">';
        echo '<input type="hidden" name="action" value="promote_demote">';
        echo '<input type="hidden" name="department" value="' . htmlspecialchars($department) . '">';
        echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
        
        echo '<div style="display: grid; grid-template-columns: 1fr 1fr 150px; gap: 1rem; align-items: end;">';
        
        // Character selection
        echo '<div>';
        echo '<label style="color: var(--' . $color . '); font-weight: bold;">Select Character:</label>';
        echo '<select name="character_id" required style="width: 100%; padding: 0.75rem; background: black; color: white; border: 1px solid var(--' . $color . '); border-radius: 5px;">';
        echo '<option value="">Choose character...</option>';
        foreach ($dept_members as $member) {
            echo '<option value="' . $member['id'] . '">' . 
                 htmlspecialchars($member['rank'] . ' ' . $member['first_name'] . ' ' . $member['last_name']) . 
                 '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Rank selection
        echo '<div>';
        echo '<label style="color: var(--' . $color . '); font-weight: bold;">New Rank:</label>';
        echo '<select name="new_rank" required style="width: 100%; padding: 0.75rem; background: black; color: white; border: 1px solid var(--' . $color . '); border-radius: 5px;">';
        echo '<option value="">Choose new rank...</option>';
        foreach ($valid_ranks as $rank) {
            echo '<option value="' . htmlspecialchars($rank) . '">' . htmlspecialchars($rank) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Submit button
        echo '<div>';
        echo '<button type="submit" style="background-color: var(--' . $color . '); color: black; border: none; padding: 0.75rem 1rem; border-radius: 5px; font-weight: bold; width: 100%; cursor: pointer;">';
        echo '🎖️ UPDATE RANK';
        echo '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</form>';
        
        // Permission info
        echo '<div style="background: rgba(255, 255, 255, 0.1); padding: 1rem; border-radius: 5px; margin-top: 1.5rem;">';
        echo '<h5 style="color: var(--' . $color . '); margin: 0 0 0.5rem 0;">Permission Information:</h5>';
        echo '<p style="font-size: 0.9rem; color: var(--gray); margin: 0;">';
        
        if (hasPermission('Command')) {
            echo '• As Command staff, you can promote/demote any crew member to any rank.<br>';
        } elseif ($roster_dept === 'Starfleet Auditor') {
            echo '• As a Starfleet Auditor, you can promote/demote any crew member to any rank.<br>';
        } elseif (isDepartmentHead($department)) {
            echo '• As Head of ' . htmlspecialchars($department) . ', you can promote/demote department members up to Lieutenant.<br>';
        }
        
        echo '• All promotions and demotions are logged to the audit trail for accountability.';
        echo '</p>';
        echo '</div>';
        
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div style="background: rgba(204, 68, 68, 0.3); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">';
        echo '<p style="color: var(--red);">Error loading promotion form: ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
}
?>
