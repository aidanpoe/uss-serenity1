<?php
// Department Training Handler
// This file handles training assignment functionality for department pages

function handleDepartmentTraining($current_department) {
    global $pdo, $success, $error;
    
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'assign_training') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
            return;
        }
        
        try {
            // Check if assignment already exists
            $checkStmt = $pdo->prepare("
                SELECT id FROM crew_competencies 
                WHERE roster_id = ? AND module_id = ?
            ");
            $checkStmt->execute([$_POST['roster_id'], $_POST['module_id']]);
            
            if ($checkStmt->fetch()) {
                $error = "This training is already assigned to the selected crew member.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO crew_competencies 
                    (roster_id, module_id, assigned_by, assigned_date, status, notes) 
                    VALUES (?, ?, ?, NOW(), 'completed', ?)
                ");
                
                $stmt->execute([
                    $_POST['roster_id'],
                    $_POST['module_id'],
                    $_SESSION['user_id'],
                    "Training completed by " . $_POST['trained_by_name']
                ]);
                
                $success = "Training record added successfully!";
            }
        } catch (Exception $e) {
            $error = "Error adding training: " . $e->getMessage();
        }
    }
}

function getDepartmentModules($department) {
    global $pdo;
    
    // Get modules for this department plus universal modules
    $stmt = $pdo->prepare("
        SELECT id, module_name, module_code, certification_level, description
        FROM training_modules 
        WHERE (department = ? OR department = 'Universal') AND is_active = 1
        ORDER BY department, certification_level, module_name
    ");
    $stmt->execute([$department]);
    return $stmt->fetchAll();
}

function getDepartmentStaff($department) {
    global $pdo;
    
    // Get all staff from this department plus Command staff
    $stmt = $pdo->prepare("
        SELECT r.id as roster_id, r.first_name, r.last_name, r.rank, r.department
        FROM roster r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE (r.department = ? OR r.department = 'Command') 
        AND r.is_active = 1 
        AND (u.active = 1 OR u.active IS NULL)
        ORDER BY r.department, r.rank, r.last_name, r.first_name
    ");
    $stmt->execute([$department]);
    return $stmt->fetchAll();
}

function getDepartmentTrainable($department) {
    global $pdo;
    
    // Get all staff from this department (not including Command for training targets)
    $stmt = $pdo->prepare("
        SELECT r.id as roster_id, r.first_name, r.last_name, r.rank, r.department
        FROM roster r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.department = ?
        AND r.is_active = 1 
        AND (u.active = 1 OR u.active IS NULL)
        ORDER BY r.rank, r.last_name, r.first_name
    ");
    $stmt->execute([$department]);
    return $stmt->fetchAll();
}

function renderDepartmentTrainingSection($department, $department_display_name) {
    $modules = getDepartmentModules($department);
    $trainers = getDepartmentStaff($department);
    $trainees = getDepartmentTrainable($department);
    
    echo '<div class="training-section" style="background: rgba(0,0,0,0.7); padding: 2rem; border-radius: 15px; margin: 2rem 0; border: 2px solid var(--orange);">';
    echo '<h2 style="color: var(--orange); margin-bottom: 1rem;">🎓 ' . htmlspecialchars($department_display_name) . ' Training Management</h2>';
    echo '<p style="color: var(--orange); margin-bottom: 2rem;"><em>Department Staff & Command Access Only</em></p>';
    echo '<p style="color: white; margin-bottom: 1.5rem;">Add completed training for department personnel</p>';
    
    echo '<div class="training-container" style="background: rgba(0,0,0,0.3); padding: 1.5rem; border-radius: 10px; border: 1px solid var(--orange);">';
    echo '<form method="POST" class="training-form">';
    echo '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
    echo '<input type="hidden" name="action" value="assign_training">';
    
    echo '<div class="form-grid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">';
    
    // Module selection
    echo '<div class="form-group">';
    echo '<label for="module_id" style="color: var(--orange); display: block; margin-bottom: 0.5rem;">Training Module:</label>';
    echo '<select name="module_id" id="module_id" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange); border-radius: 3px;">';
    echo '<option value="">Select Training Module</option>';
    
    $current_dept = '';
    foreach ($modules as $module) {
        $module_dept = $module['department'] === 'Universal' ? 'Universal' : $department_display_name;
        if ($module_dept !== $current_dept) {
            if ($current_dept !== '') echo '</optgroup>';
            echo '<optgroup label="' . htmlspecialchars($module_dept) . '">';
            $current_dept = $module_dept;
        }
        echo '<option value="' . $module['id'] . '">';
        echo htmlspecialchars($module['module_name']) . ' (' . htmlspecialchars($module['certification_level']) . ')';
        echo '</option>';
    }
    if ($current_dept !== '') echo '</optgroup>';
    
    echo '</select>';
    echo '</div>';
    
    // Trainee selection
    echo '<div class="form-group">';
    echo '<label for="roster_id" style="color: var(--orange); display: block; margin-bottom: 0.5rem;">Crew Member Trained:</label>';
    echo '<select name="roster_id" id="roster_id" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange); border-radius: 3px;">';
    echo '<option value="">Select Crew Member</option>';
    foreach ($trainees as $trainee) {
        echo '<option value="' . $trainee['roster_id'] . '">';
        echo htmlspecialchars($trainee['rank'] . ' ' . $trainee['first_name'] . ' ' . $trainee['last_name']);
        echo '</option>';
    }
    echo '</select>';
    echo '</div>';
    
    // Trainer selection
    echo '<div class="form-group">';
    echo '<label for="trained_by" style="color: var(--orange); display: block; margin-bottom: 0.5rem;">Trained By:</label>';
    echo '<select name="trained_by" id="trained_by" required style="width: 100%; padding: 0.5rem; background: black; color: white; border: 1px solid var(--orange); border-radius: 3px;">';
    echo '<option value="">Select Trainer</option>';
    
    $current_dept = '';
    foreach ($trainers as $trainer) {
        if ($trainer['department'] !== $current_dept) {
            if ($current_dept !== '') echo '</optgroup>';
            echo '<optgroup label="' . htmlspecialchars($trainer['department']) . '">';
            $current_dept = $trainer['department'];
        }
        echo '<option value="' . $trainer['roster_id'] . '" data-name="' . htmlspecialchars($trainer['rank'] . ' ' . $trainer['first_name'] . ' ' . $trainer['last_name']) . '">';
        echo htmlspecialchars($trainer['rank'] . ' ' . $trainer['first_name'] . ' ' . $trainer['last_name']);
        echo '</option>';
    }
    if ($current_dept !== '') echo '</optgroup>';
    
    echo '</select>';
    echo '<input type="hidden" name="trained_by_name" id="trained_by_name">';
    echo '</div>';
    
    echo '</div>'; // form-grid
    
    echo '<button type="submit" class="action-button" style="background-color: var(--orange); color: black; border: none; padding: 1rem 2rem; border-radius: 5px; width: 100%; font-weight: bold; cursor: pointer;">Add Training Record</button>';
    echo '</form>';
    echo '</div>'; // training-container
    echo '</div>'; // training-section
    
    // JavaScript to handle trainer name
    echo '<script>';
    echo 'document.getElementById("trained_by").addEventListener("change", function() {';
    echo '    var selectedOption = this.options[this.selectedIndex];';
    echo '    document.getElementById("trained_by_name").value = selectedOption.getAttribute("data-name") || "";';
    echo '});';
    echo '</script>';
}
?>
