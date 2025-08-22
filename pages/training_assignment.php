<?php
require_once '../includes/config.php';

// Check if user has Command access
if (!hasPermission('Command') && !hasPermission('Captain')) {
    header('Location: ../index.php');
    exit();
}

$success = '';
$error = '';

try {
    $pdo = getConnection();
    
    // Handle assigning training
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'assign_training') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
        } else {
            // Check if assignment already exists
            $checkStmt = $pdo->prepare("
                SELECT id FROM crew_competencies 
                WHERE user_id = ? AND module_id = ? AND is_current = 1
            ");
            $checkStmt->execute([$_POST['user_id'], $_POST['module_id']]);
            
            if ($checkStmt->fetch()) {
                $error = "This training is already assigned to the selected crew member.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO crew_competencies 
                    (user_id, module_id, assigned_by, assigned_date, status, notes) 
                    VALUES (?, ?, ?, NOW(), 'assigned', ?)
                ");
                
                $stmt->execute([
                    $_POST['user_id'],
                    $_POST['module_id'],
                    $_SESSION['user_id'],
                    $_POST['notes'] ?? ''
                ]);
                
                $success = "Training assigned successfully!";
            }
        }
    }
    
    // Handle bulk assignment
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'bulk_assign') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
        } else {
            $assigned_count = 0;
            $skipped_count = 0;
            
            foreach ($_POST['selected_users'] as $user_id) {
                // Check if assignment already exists
                $checkStmt = $pdo->prepare("
                    SELECT id FROM crew_competencies 
                    WHERE user_id = ? AND module_id = ? AND is_current = 1
                ");
                $checkStmt->execute([$user_id, $_POST['module_id']]);
                
                if (!$checkStmt->fetch()) {
                    $stmt = $pdo->prepare("
                        INSERT INTO crew_competencies 
                        (user_id, module_id, assigned_by, assigned_date, status, notes) 
                        VALUES (?, ?, ?, NOW(), 'assigned', ?)
                    ");
                    
                    $stmt->execute([
                        $user_id,
                        $_POST['module_id'],
                        $_SESSION['user_id'],
                        $_POST['bulk_notes'] ?? ''
                    ]);
                    $assigned_count++;
                } else {
                    $skipped_count++;
                }
            }
            
            $success = "Bulk assignment complete: {$assigned_count} assigned, {$skipped_count} skipped (already assigned).";
        }
    }
    
    // Handle updating training status
    if ($_POST && isset($_POST['action']) && $_POST['action'] === 'update_status') {
        if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
            $error = "Invalid security token. Please try again.";
        } else {
            $updateData = [
                $_POST['status'],
                $_POST['competency_id']
            ];
            
            $query = "UPDATE crew_competencies SET status = ?, updated_at = NOW()";
            
            if ($_POST['status'] === 'completed') {
                $query .= ", completion_date = NOW(), completion_notes = ?";
                $updateData = [
                    $_POST['status'],
                    $_POST['completion_notes'] ?? '',
                    $_POST['competency_id']
                ];
            }
            
            $query .= " WHERE id = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($updateData);
            
            $success = "Training status updated successfully!";
        }
    }
    
    // Get filter parameters
    $selected_module = $_GET['module_id'] ?? '';
    $selected_department = $_GET['department'] ?? '';
    $selected_status = $_GET['status'] ?? '';
    
    // Get all active training modules
    $modulesStmt = $pdo->query("
        SELECT id, module_name, module_code, department, certification_level 
        FROM training_modules 
        WHERE is_active = 1 
        ORDER BY department, module_name
    ");
    $modules = $modulesStmt->fetchAll();
    
    // Get all crew members with their current character and department
    $crewStmt = $pdo->query("
        SELECT u.id, u.username, 
               COALESCE(r.first_name, 'No') as first_name,
               COALESCE(r.last_name, 'Character') as last_name,
               COALESCE(r.rank, 'Unassigned') as current_rank,
               COALESCE(r.department, 'Unassigned') as current_department
        FROM users u
        LEFT JOIN roster r ON u.id = r.user_id AND r.is_active = 1
        WHERE u.active = 1
        ORDER BY r.department, r.rank, r.last_name, r.first_name
    ");
    $crew = $crewStmt->fetchAll();
    
    // Get training assignments with filters
    $whereConditions = [];
    $params = [];
    
    if ($selected_module) {
        $whereConditions[] = "cc.module_id = ?";
        $params[] = $selected_module;
    }
    
    if ($selected_department && $selected_department !== 'All') {
        $whereConditions[] = "r.department = ?";
        $params[] = $selected_department;
    }
    
    if ($selected_status) {
        $whereConditions[] = "cc.status = ?";
        $params[] = $selected_status;
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    }
    
    $assignmentsStmt = $pdo->prepare("
        SELECT cc.*, 
               tm.module_name, tm.module_code, tm.department as module_department,
               tm.certification_level,
               u.username,
               COALESCE(r.first_name, 'No') as first_name,
               COALESCE(r.last_name, 'Character') as last_name,
               COALESCE(r.rank, 'Unassigned') as current_rank,
               COALESCE(r.department, 'Unassigned') as current_department,
               assigner.username as assigned_by_name
        FROM crew_competencies cc
        JOIN training_modules tm ON cc.module_id = tm.id
        JOIN users u ON cc.user_id = u.id
        LEFT JOIN roster r ON u.id = r.user_id AND r.is_active = 1
        LEFT JOIN users assigner ON cc.assigned_by = assigner.id
        {$whereClause}
        ORDER BY cc.assigned_date DESC, tm.department, r.department
    ");
    $assignmentsStmt->execute($params);
    $assignments = $assignmentsStmt->fetchAll();
    
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Assignment - USS Serenity</title>
    <link rel="stylesheet" href="../assets/lcars.css">
    <style>
        .assignment-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        .tab-container {
            display: flex;
            margin-bottom: 2rem;
        }
        .tab {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--blue);
            padding: 1rem 2rem;
            cursor: pointer;
            border-bottom: none;
            margin-right: 1rem;
        }
        .tab.active {
            background: var(--blue);
            color: black;
        }
        .tab-content {
            background: rgba(0, 255, 255, 0.1);
            border: 2px solid var(--blue);
            border-radius: 10px;
            padding: 2rem;
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            color: var(--blue);
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .form-group select,
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--blue);
            border-radius: 5px;
            color: var(--green);
            font-family: inherit;
        }
        .crew-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid var(--blue);
            padding: 1rem;
            border-radius: 5px;
        }
        .crew-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.6);
            border-radius: 5px;
        }
        .crew-checkbox input {
            width: auto;
        }
        .assignments-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        .assignments-table th,
        .assignments-table td {
            padding: 1rem;
            border: 1px solid var(--blue);
            text-align: left;
        }
        .assignments-table th {
            background: var(--blue);
            color: black;
            font-weight: bold;
        }
        .assignments-table tr:nth-child(even) {
            background: rgba(0, 255, 255, 0.05);
        }
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-assigned { background: var(--blue); color: black; }
        .status-in-progress { background: var(--gold); color: black; }
        .status-completed { background: var(--green); color: black; }
        .status-expired { background: var(--red); color: white; }
        .filters {
            display: flex;
            gap: 1rem;
            margin: 1rem 0;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .filter-group label {
            color: var(--blue);
            font-weight: bold;
            font-size: 0.9rem;
        }
        .filter-group select {
            padding: 0.5rem;
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--blue);
            border-radius: 5px;
            color: var(--green);
        }
        .quick-status {
            display: flex;
            gap: 0.5rem;
        }
        .quick-status button {
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .stats-bar {
            display: flex;
            gap: 2rem;
            margin: 1rem 0;
            padding: 1rem;
            background: rgba(255, 153, 0, 0.1);
            border: 2px solid var(--gold);
            border-radius: 10px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--gold);
        }
        .stat-label {
            font-size: 0.9rem;
            color: var(--blue);
        }
    </style>
</head>
<body>
    <div class="assignment-container">
        <h1>👥 Training Assignment Management</h1>
        <p>Assign training modules to crew members and track their progress</p>
        
        <?php if ($success): ?>
        <div style="background: rgba(0, 255, 0, 0.2); border: 2px solid var(--green); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
            <p style="color: var(--green);">✅ <?php echo htmlspecialchars($success); ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div style="background: rgba(255, 0, 0, 0.2); border: 2px solid var(--red); padding: 1rem; border-radius: 10px; margin: 1rem 0;">
            <p style="color: var(--red);">❌ <?php echo htmlspecialchars($error); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Statistics Bar -->
        <div class="stats-bar">
            <?php
            $stats = [
                'total_assignments' => count($assignments),
                'assigned' => count(array_filter($assignments, function($a) { return $a['status'] === 'assigned'; })),
                'in_progress' => count(array_filter($assignments, function($a) { return $a['status'] === 'in_progress'; })),
                'completed' => count(array_filter($assignments, function($a) { return $a['status'] === 'completed'; }))
            ];
            ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_assignments']; ?></div>
                <div class="stat-label">Total Assignments</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['assigned']; ?></div>
                <div class="stat-label">Assigned</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo number_format($stats['total_assignments'] > 0 ? ($stats['completed'] / $stats['total_assignments']) * 100 : 0, 1); ?>%</div>
                <div class="stat-label">Completion Rate</div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tab-container">
            <div class="tab active" onclick="showTab('individual')">Individual Assignment</div>
            <div class="tab" onclick="showTab('bulk')">Bulk Assignment</div>
            <div class="tab" onclick="showTab('manage')">Manage Assignments</div>
        </div>
        
        <!-- Individual Assignment Tab -->
        <div id="individual-tab" class="tab-content active">
            <h2>➕ Assign Training to Individual Crew Member</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="assign_training">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="module_id">Training Module:</label>
                        <select name="module_id" id="module_id" required>
                            <option value="">Select Training Module</option>
                            <?php foreach ($modules as $module): ?>
                            <option value="<?php echo $module['id']; ?>">
                                [<?php echo htmlspecialchars($module['department']); ?>] 
                                <?php echo htmlspecialchars($module['module_name']); ?>
                                (<?php echo htmlspecialchars($module['certification_level']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_id">Crew Member:</label>
                        <select name="user_id" id="user_id" required>
                            <option value="">Select Crew Member</option>
                            <?php foreach ($crew as $member): ?>
                            <option value="<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?> 
                                (<?php echo htmlspecialchars($member['current_rank']); ?> - <?php echo htmlspecialchars($member['current_department']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Assignment Notes (optional):</label>
                    <textarea name="notes" id="notes" placeholder="Any specific instructions or requirements for this training assignment..."></textarea>
                </div>
                
                <button type="submit" class="action-button">Assign Training</button>
            </form>
        </div>
        
        <!-- Bulk Assignment Tab -->
        <div id="bulk-tab" class="tab-content">
            <h2>👥 Bulk Assign Training to Multiple Crew</h2>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="bulk_assign">
                
                <div class="form-group">
                    <label for="bulk_module_id">Training Module:</label>
                    <select name="module_id" id="bulk_module_id" required>
                        <option value="">Select Training Module</option>
                        <?php foreach ($modules as $module): ?>
                        <option value="<?php echo $module['id']; ?>">
                            [<?php echo htmlspecialchars($module['department']); ?>] 
                            <?php echo htmlspecialchars($module['module_name']); ?>
                            (<?php echo htmlspecialchars($module['certification_level']); ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Select Crew Members:</label>
                    <div class="crew-grid">
                        <?php foreach ($crew as $member): ?>
                        <div class="crew-checkbox">
                            <input type="checkbox" name="selected_users[]" value="<?php echo $member['id']; ?>" id="crew_<?php echo $member['id']; ?>">
                            <label for="crew_<?php echo $member['id']; ?>">
                                <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?><br>
                                <small><?php echo htmlspecialchars($member['current_rank']); ?> - <?php echo htmlspecialchars($member['current_department']); ?></small>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bulk_notes">Bulk Assignment Notes (optional):</label>
                    <textarea name="bulk_notes" id="bulk_notes" placeholder="Notes that will apply to all selected crew members..."></textarea>
                </div>
                
                <button type="submit" class="action-button">Assign to Selected Crew</button>
            </form>
        </div>
        
        <!-- Manage Assignments Tab -->
        <div id="manage-tab" class="tab-content">
            <h2>📋 Manage Current Training Assignments</h2>
            
            <!-- Filters -->
            <form method="GET" class="filters">
                <div class="filter-group">
                    <label>Training Module:</label>
                    <select name="module_id">
                        <option value="">All Modules</option>
                        <?php foreach ($modules as $module): ?>
                        <option value="<?php echo $module['id']; ?>" <?php echo $selected_module == $module['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($module['module_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Department:</label>
                    <select name="department">
                        <option value="">All Departments</option>
                        <option value="MED/SCI" <?php echo $selected_department === 'MED/SCI' ? 'selected' : ''; ?>>MED/SCI</option>
                        <option value="ENG/OPS" <?php echo $selected_department === 'ENG/OPS' ? 'selected' : ''; ?>>ENG/OPS</option>
                        <option value="SEC/TAC" <?php echo $selected_department === 'SEC/TAC' ? 'selected' : ''; ?>>SEC/TAC</option>
                        <option value="Command" <?php echo $selected_department === 'Command' ? 'selected' : ''; ?>>Command</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="assigned" <?php echo $selected_status === 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="in_progress" <?php echo $selected_status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $selected_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="expired" <?php echo $selected_status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button type="submit" class="action-button">Apply Filters</button>
                </div>
            </form>
            
            <!-- Assignments Table -->
            <table class="assignments-table">
                <thead>
                    <tr>
                        <th>Crew Member</th>
                        <th>Training Module</th>
                        <th>Department</th>
                        <th>Level</th>
                        <th>Assigned Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($assignment['first_name'] . ' ' . $assignment['last_name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($assignment['current_rank']); ?> - <?php echo htmlspecialchars($assignment['current_department']); ?></small>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($assignment['module_name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($assignment['module_code']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($assignment['module_department']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['certification_level']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($assignment['assigned_date'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo str_replace('_', '-', $assignment['status']); ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $assignment['status'])); ?>
                            </span>
                            <?php if ($assignment['completion_date']): ?>
                            <br><small>Completed: <?php echo date('M j, Y', strtotime($assignment['completion_date'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="quick-status">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="competency_id" value="<?php echo $assignment['id']; ?>">
                                    
                                    <?php if ($assignment['status'] === 'assigned'): ?>
                                    <button type="submit" name="status" value="in_progress" class="action-button" style="background: var(--gold); color: black; padding: 0.25rem 0.5rem; font-size: 0.8rem;">Start</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($assignment['status'] === 'in_progress'): ?>
                                    <button type="submit" name="status" value="completed" class="action-button" style="background: var(--green); color: black; padding: 0.25rem 0.5rem; font-size: 0.8rem;">Complete</button>
                                    <?php endif; ?>
                                    
                                    <?php if ($assignment['status'] !== 'completed'): ?>
                                    <button type="submit" name="status" value="assigned" class="action-button" style="background: var(--blue); color: black; padding: 0.25rem 0.5rem; font-size: 0.8rem;">Reset</button>
                                    <?php endif; ?>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if (empty($assignments)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--gold); padding: 2rem;">
                            No training assignments found with current filters.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="text-align: center; margin: 2rem 0;">
            <a href="../index.php" class="action-button">← Back to Main Site</a>
            <a href="training_modules.php" class="action-button">📚 Manage Training Modules</a>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => content.classList.remove('active'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
