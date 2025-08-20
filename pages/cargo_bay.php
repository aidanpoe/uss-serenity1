<?php
session_start();
require_once '../includes/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$user_department = $_SESSION['department'] ?? '';
$user_name = ($_SESSION['rank'] ?? '') . ' ' . ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

// Function to check if user can access area
function canAccessArea($area_access, $user_dept) {
    $allowed_depts = explode(',', $area_access);
    return in_array($user_dept, $allowed_depts) || $user_dept === 'Command';
}

// Function to check if user can modify area
function canModifyArea($area_access, $user_dept) {
    $allowed_depts = explode(',', $area_access);
    return in_array($user_dept, $allowed_depts) || $user_dept === 'Command' || $user_dept === 'ENG/OPS';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'modify_inventory':
                $inventory_id = $_POST['inventory_id'];
                $modify_action = $_POST['modify_action']; // 'add' or 'remove'
                $quantity = $_POST['quantity'];
                $reason = $_POST['reason'] ?? '';
                
                try {
                    // Get current item details
                    $item_stmt = $pdo->prepare("SELECT ci.*, ca.department_access FROM cargo_inventory ci JOIN cargo_areas ca ON ci.area_id = ca.id WHERE ci.id = ?");
                    $item_stmt->execute([$inventory_id]);
                    $item = $item_stmt->fetch();
                    
                    if (!$item) {
                        $error_message = "Item not found.";
                        break;
                    }
                    
                    // Check permissions
                    if (!canModifyArea($item['department_access'], $user_department)) {
                        $error_message = "Access denied to modify this storage area.";
                        break;
                    }
                    
                    $previous_quantity = $item['quantity'];
                    
                    if ($modify_action === 'add') {
                        $new_quantity = $previous_quantity + $quantity;
                        $quantity_change = $quantity;
                        $action_name = 'ADD';
                    } else { // remove
                        if ($quantity > $previous_quantity) {
                            $error_message = "Cannot remove more items than available in stock ({$previous_quantity}).";
                            break;
                        }
                        $new_quantity = $previous_quantity - $quantity;
                        $quantity_change = -$quantity;
                        $action_name = 'REMOVE';
                    }
                    
                    // Update inventory
                    if ($new_quantity > 0) {
                        $update_stmt = $pdo->prepare("UPDATE cargo_inventory SET quantity = ?, last_modified = CURRENT_TIMESTAMP WHERE id = ?");
                        $update_stmt->execute([$new_quantity, $inventory_id]);
                    } else {
                        // Remove item completely if quantity reaches 0
                        $delete_stmt = $pdo->prepare("DELETE FROM cargo_inventory WHERE id = ?");
                        $delete_stmt->execute([$inventory_id]);
                        $new_quantity = 0;
                    }
                    
                    // Log the action
                    $log_stmt = $pdo->prepare("INSERT INTO cargo_logs (inventory_id, action, quantity_change, previous_quantity, new_quantity, performed_by, performer_department, reason) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $log_stmt->execute([$inventory_id, $action_name, $quantity_change, $previous_quantity, $new_quantity, $user_name, $user_department, $reason]);
                    
                    $success_message = ucfirst($modify_action) . " operation completed successfully!";
                } catch (Exception $e) {
                    $error_message = "Error modifying inventory: " . $e->getMessage();
                }
                break;

            case 'add_item':
                $area_id = $_POST['area_id'];
                $item_name = $_POST['item_name'];
                $item_description = $_POST['description'] ?? '';
                $quantity = $_POST['quantity'];
                $min_quantity = $_POST['min_quantity'] ?? 5;
                $unit_type = $_POST['unit_type'] ?? 'pieces';
                
                try {
                    // Check if user can modify this area
                    $area_stmt = $pdo->prepare("SELECT department_access FROM cargo_areas WHERE id = ?");
                    $area_stmt->execute([$area_id]);
                    $area = $area_stmt->fetch();
                    
                    if (canModifyArea($area['department_access'], $user_department)) {
                        // Check if item already exists in this area
                        $check_stmt = $pdo->prepare("SELECT id, quantity FROM cargo_inventory WHERE area_id = ? AND item_name = ?");
                        $check_stmt->execute([$area_id, $item_name]);
                        $existing = $check_stmt->fetch();
                        
                        if ($existing) {
                            // Update existing item quantity
                            $new_quantity = $existing['quantity'] + $quantity;
                            
                            $update_stmt = $pdo->prepare("UPDATE cargo_inventory SET quantity = ?, last_modified = CURRENT_TIMESTAMP WHERE id = ?");
                            $update_stmt->execute([$new_quantity, $existing['id']]);
                            
                            // Log the addition
                            $log_stmt = $pdo->prepare("INSERT INTO cargo_logs (inventory_id, action, quantity_change, previous_quantity, new_quantity, performed_by, performer_department) VALUES (?, 'ADD', ?, ?, ?, ?, ?)");
                            $log_stmt->execute([$existing['id'], $quantity, $existing['quantity'], $new_quantity, $user_name, $user_department]);
                        } else {
                            // Add new item with all fields
                            try {
                                $insert_stmt = $pdo->prepare("INSERT INTO cargo_inventory (area_id, item_name, item_description, quantity, min_quantity, unit_type, added_by, added_department) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                $insert_stmt->execute([$area_id, $item_name, $item_description, $quantity, $min_quantity, $unit_type, $user_name, $user_department]);
                            } catch (PDOException $e) {
                                if (strpos($e->getMessage(), "Unknown column 'unit_type'") !== false) {
                                    // Fallback for databases without unit_type column
                                    $insert_stmt = $pdo->prepare("INSERT INTO cargo_inventory (area_id, item_name, item_description, quantity, min_quantity, added_by, added_department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                    $insert_stmt->execute([$area_id, $item_name, $item_description, $quantity, $min_quantity, $user_name, $user_department]);
                                } else {
                                    throw $e;
                                }
                            }
                            
                            $inventory_id = $pdo->lastInsertId();
                            
                            // Log the addition
                            $log_stmt = $pdo->prepare("INSERT INTO cargo_logs (inventory_id, action, quantity_change, previous_quantity, new_quantity, performed_by, performer_department) VALUES (?, 'ADD', ?, 0, ?, ?, ?)");
                            $log_stmt->execute([$inventory_id, $quantity, $quantity, $user_name, $user_department]);
                        }
                        
                        $success_message = "Item added successfully!";
                    } else {
                        $error_message = "Access denied to this storage area.";
                    }
                } catch (Exception $e) {
                    $error_message = "Error adding item: " . $e->getMessage();
                }
                break;
                
            case 'remove_item':
                $inventory_id = $_POST['inventory_id'];
                $remove_quantity = $_POST['remove_quantity'];
                $reason = $_POST['reason'] ?? '';
                
                try {
                    // Get item details and check permissions
                    $item_stmt = $pdo->prepare("
                        SELECT ci.*, ca.department_access 
                        FROM cargo_inventory ci 
                        JOIN cargo_areas ca ON ci.area_id = ca.id 
                        WHERE ci.id = ?
                    ");
                    $item_stmt->execute([$inventory_id]);
                    $item = $item_stmt->fetch();
                    
                    if (canModifyArea($item['department_access'], $user_department)) {
                        if ($remove_quantity <= $item['quantity']) {
                            $new_quantity = $item['quantity'] - $remove_quantity;
                            
                            if ($new_quantity > 0) {
                                $update_stmt = $pdo->prepare("UPDATE cargo_inventory SET quantity = ?, last_modified = CURRENT_TIMESTAMP WHERE id = ?");
                                $update_stmt->execute([$new_quantity, $inventory_id]);
                            } else {
                                $delete_stmt = $pdo->prepare("DELETE FROM cargo_inventory WHERE id = ?");
                                $delete_stmt->execute([$inventory_id]);
                            }
                            
                            // Log the removal
                            $log_stmt = $pdo->prepare("INSERT INTO cargo_logs (inventory_id, action, quantity_change, previous_quantity, new_quantity, performed_by, performer_department, reason) VALUES (?, 'REMOVE', ?, ?, ?, ?, ?, ?)");
                            $negative_quantity = -$remove_quantity;
                            $log_stmt->execute([$inventory_id, $negative_quantity, $item['quantity'], $new_quantity, $user_name, $user_department, $reason]);
                            
                            $success_message = "Item removed successfully!";
                        } else {
                            $error_message = "Cannot remove more items than available in stock.";
                        }
                    } else {
                        $error_message = "Access denied to this storage area.";
                    }
                } catch (Exception $e) {
                    $error_message = "Error removing item: " . $e->getMessage();
                }
                break;
                
            case 'bulk_delivery':
                if ($user_department === 'ENG/OPS' || $user_department === 'Command') {
                    $area_id = $_POST['area_id'];
                    $item_name = $_POST['item_name'];
                    $item_description = $_POST['description'] ?? '';
                    $quantity = $_POST['quantity'];
                    $unit_type = $_POST['unit_type'] ?? 'pieces';
                    
                    try {
                        // Check if item exists
                        $check_stmt = $pdo->prepare("SELECT id, quantity FROM cargo_inventory WHERE area_id = ? AND item_name = ?");
                        $check_stmt->execute([$area_id, $item_name]);
                        $existing = $check_stmt->fetch();
                        
                        if ($existing) {
                            // Update existing
                            $new_quantity = $existing['quantity'] + $quantity;
                            
                            $update_stmt = $pdo->prepare("UPDATE cargo_inventory SET quantity = ?, last_modified = CURRENT_TIMESTAMP WHERE id = ?");
                            $update_stmt->execute([$new_quantity, $existing['id']]);
                            
                            // Log bulk delivery
                            $log_stmt = $pdo->prepare("INSERT INTO cargo_logs (inventory_id, action, quantity_change, previous_quantity, new_quantity, performed_by, performer_department, reason) VALUES (?, 'BULK_DELIVERY', ?, ?, ?, ?, ?, 'Bulk delivery operation')");
                            $log_stmt->execute([$existing['id'], $quantity, $existing['quantity'], $new_quantity, $user_name, $user_department]);
                        } else {
                            // Add new item
                            try {
                                $insert_stmt = $pdo->prepare("INSERT INTO cargo_inventory (area_id, item_name, item_description, quantity, unit_type, added_by, added_department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                                $insert_stmt->execute([$area_id, $item_name, $item_description, $quantity, $unit_type, $user_name, $user_department]);
                            } catch (PDOException $e) {
                                if (strpos($e->getMessage(), "Unknown column 'unit_type'") !== false) {
                                    // Fallback for databases without unit_type column
                                    $insert_stmt = $pdo->prepare("INSERT INTO cargo_inventory (area_id, item_name, item_description, quantity, added_by, added_department) VALUES (?, ?, ?, ?, ?, ?)");
                                    $insert_stmt->execute([$area_id, $item_name, $item_description, $quantity, $user_name, $user_department]);
                                } else {
                                    throw $e;
                                }
                            }
                            
                            $inventory_id = $pdo->lastInsertId();
                            
                            // Log bulk delivery
                            $log_stmt = $pdo->prepare("INSERT INTO cargo_logs (inventory_id, action, quantity_change, previous_quantity, new_quantity, performed_by, performer_department, reason) VALUES (?, 'BULK_DELIVERY', ?, 0, ?, ?, ?, 'Bulk delivery operation')");
                            $log_stmt->execute([$inventory_id, $quantity, $quantity, $user_name, $user_department]);
                        }
                        
                        $success_message = "Bulk delivery completed successfully!";
                    } catch (Exception $e) {
                        $error_message = "Error with bulk delivery: " . $e->getMessage();
                    }
                } else {
                    $error_message = "Only ENG/OPS and COMMAND can perform bulk deliveries.";
                }
                break;
        }
    }
}

// Get all cargo areas
$areas_stmt = $pdo->query("SELECT * FROM cargo_areas ORDER BY area_name");
$areas_result = $areas_stmt->fetchAll();

// Get low stock warnings for user's department
$warnings = [];
if ($user_department) {
    $warning_stmt = $pdo->prepare("
        SELECT ci.item_name, ci.quantity, ci.min_quantity, ca.area_name 
        FROM cargo_inventory ci 
        JOIN cargo_areas ca ON ci.area_id = ca.id 
        WHERE ci.quantity <= ci.min_quantity 
        AND (ca.department_access LIKE ? OR ca.department_access LIKE '%Command%')
    ");
    $dept_search = "%$user_department%";
    $warning_stmt->execute([$dept_search]);
    $warnings = $warning_stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>USS-Serenity 74714 - Cargo Bay Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="stylesheet" type="text/css" href="../assets/classic.css">
    <style>
        /* LCARS Cargo Bay Styling */
        .cargo-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .cargo-sidebar {
            background: #000;
            border: 2px solid var(--blue);
            border-radius: 20px;
            padding: 1rem;
        }
        
        .sidebar-section {
            margin-bottom: 1.5rem;
        }
        
        .sidebar-title {
            background: var(--blue);
            color: #000;
            padding: 0.5rem 1rem;
            margin: 0 0 1rem 0;
            border-radius: 15px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9rem;
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.5rem;
        }
        
        .stat-card {
            background: var(--african-violet);
            color: #000;
            padding: 0.8rem;
            border-radius: 10px;
            text-align: center;
            font-weight: bold;
        }
        
        .stat-number {
            font-size: 1.4rem;
            display: block;
        }
        
        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
        }
        
        .cargo-main {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .cargo-area {
            background: #000;
            border: 2px solid;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .cargo-area.medsci { border-color: var(--blue); }
        .cargo-area.engops { border-color: var(--orange); }
        .cargo-area.sectac { border-color: var(--gold); }
        .cargo-area.misc { border-color: var(--red); }
        
        .area-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin: 0;
        }
        
        .cargo-area.medsci .area-header { background: var(--blue); }
        .cargo-area.engops .area-header { background: var(--orange); }
        .cargo-area.sectac .area-header { background: var(--gold); }
        .cargo-area.misc .area-header { background: var(--red); }
        
        .area-title {
            color: #000;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        
        .permission-badge {
            background: rgba(0,0,0,0.3);
            color: #000;
            padding: 0.3rem 0.8rem;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .cargo-content {
            padding: 1rem;
        }
        
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .inventory-card {
            background: var(--african-violet);
            color: #000;
            border-radius: 10px;
            padding: 1rem;
            border: 2px solid transparent;
        }
        
        .inventory-card.low-stock {
            border-color: var(--red);
            animation: pulse-warning 2s infinite;
        }
        
        @keyframes pulse-warning {
            0%, 100% { border-color: var(--red); }
            50% { border-color: transparent; }
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .item-name {
            font-weight: bold;
            font-size: 1rem;
            margin: 0;
        }
        
        .item-quantity {
            background: #000;
            color: var(--blue);
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .item-description {
            color: #000;
            font-size: 0.85rem;
            margin: 0.5rem 0;
            opacity: 0.8;
        }
        
        .item-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn {
            background: var(--blue);
            color: #000;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 15px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.8rem;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            opacity: 0.8;
        }
        
        .btn.orange { background: var(--orange); }
        .btn.red { background: var(--red); }
        .btn.gold { background: var(--gold); }
        .btn.small { padding: 0.3rem 0.6rem; font-size: 0.75rem; }
        
        .add-item-form {
            background: var(--african-violet);
            color: #000;
            border-radius: 15px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .add-item-form h4 {
            background: #000;
            color: var(--blue);
            margin: -1rem -1rem 1rem -1rem;
            padding: 0.5rem 1rem;
            border-radius: 15px 15px 0 0;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        
        .form-full {
            grid-column: 1 / -1;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }
        
        .form-label {
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        input, select, textarea {
            background: #000;
            border: 2px solid var(--blue);
            color: var(--blue);
            padding: 0.5rem;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--orange);
            color: var(--orange);
        }
        
        .empty-state {
            text-align: center;
            color: var(--blue);
            padding: 2rem;
            font-style: italic;
        }
        
        .warning-panel {
            background: var(--red);
            color: #000;
            border-radius: 15px;
            padding: 1rem;
        }
        
        .warning-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.2);
        }
        
        .warning-item:last-child {
            border-bottom: none;
        }
        
        .warning-item .item-quantity {
            background: #000;
            color: var(--red);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }
        
        .modal-content {
            background: #000;
            border: 2px solid var(--blue);
            margin: 10% auto;
            padding: 0;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            color: var(--blue);
            overflow: hidden;
        }
        
        .modal-content h3 {
            background: var(--blue);
            color: #000;
            margin: 0;
            padding: 1rem;
            text-transform: uppercase;
            font-size: 1rem;
        }
        
        .modal-content form {
            padding: 1rem;
        }
        
        .debug-panel {
            background: var(--african-violet);
            color: #000;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .cargo-container {
                grid-template-columns: 1fr;
            }
            
            .cargo-sidebar {
                order: 2;
            }
            
            .inventory-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <audio id="audio1" src="../assets/beep1.mp3" preload="auto"></audio>
    <audio id="audio2" src="../assets/beep2.mp3" preload="auto"></audio>
    
    <section class="wrap-standard" id="column-3">
        <div class="wrap">
            <div class="left-frame-top">
                <button onclick="playSoundAndRedirect('audio2', '../index.php')" class="panel-1-button">LCARS</button>
                <div class="panel-2">CARGO<span class="hop">-BAY</span></div>
            </div>
            <div class="right-frame-top">
                <div class="banner">USS-SERENITY &#149; CARGO BAY MANAGEMENT</div>
                <div class="data-cascade-button-group">
                    <nav> 
                        <button onclick="playSoundAndRedirect('audio2', '../index.php')">HOME</button>
                        <button onclick="playSoundAndRedirect('audio2', 'roster.php')">ROSTER</button>
                        <button onclick="playSoundAndRedirect('audio2', 'command.php')">COMMAND</button>
                        <button onclick="playSoundAndRedirect('audio2', '#')" style="background-color: var(--orange);">CARGO-BAY</button>
                    </nav>
                </div>
                <div class="bar-panel first-bar-panel">
                    <div class="bar-1"></div>
                    <div class="bar-2"></div>
                    <div class="bar-3"></div>
                    <div class="bar-4"></div>
                    <div class="bar-5"></div>
                    <div class="bar-6"></div>
                    <div class="bar-7"></div>
                    <div class="bar-8"></div>
                    <div class="bar-9"></div>
                    <div class="bar-10"></div>
                </div>
            </div>
        </div>
        
        <div class="wrap" id="gap">
            <div class="left-frame">
                <button onclick="playSoundAndRedirect('audio2', '../index.php')" id="topBtn"><span class="hop">main</span> menu</button>
                <div>
                    <div class="panel-3">CARGO<span class="hop">-STATUS</span></div>
                    <div class="panel-4">SYS<span class="hop">-ONLINE</span></div>
                    <div class="panel-5">INV<span class="hop">-ACTIVE</span></div>
                    <div class="panel-6">LOG<span class="hop">-READY</span></div>
                </div>
            </div>
            
            <div class="right-frame">
                <main>
                    <h1>Cargo Bay Management System</h1>
                    <h3>Department: <?php echo htmlspecialchars($user_department); ?></h3>
                    
                    <!-- Permission Debug (remove this later) -->
                    <?php if ($user_department): ?>
                        <div style="background: rgba(0,100,200,0.2); border: 1px solid var(--blue); padding: 0.5rem; border-radius: 5px; margin: 1rem 0; font-size: 0.9rem;">
                            <strong>Permission Debug:</strong> 
                            <?php if ($user_department === 'ENG/OPS'): ?>
                                ✅ ENG/OPS - Can modify ALL areas
                            <?php elseif ($user_department === 'Command'): ?>
                                ✅ COMMAND - Can modify ALL areas
                            <?php else: ?>
                                ⚠️ <?php echo $user_department; ?> - Limited to own department areas
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($success_message)): ?>
                        <div style="background: rgba(0,255,0,0.2); border: 1px solid var(--blue); padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                            ✅ <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div style="background: rgba(255,0,0,0.2); border: 1px solid var(--red); padding: 1rem; border-radius: 5px; margin: 1rem 0;">
                            ❌ <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="cargo-container">
                        <!-- Sidebar with Quick Stats and Controls -->
                        <div class="cargo-sidebar">
                            <div class="sidebar-section">
                                <h3 class="sidebar-title">System Status</h3>
                                <div class="quick-stats">
                                    <div class="stat-card">
                                        <span class="stat-number"><?php echo count($areas_result); ?></span>
                                        <span class="stat-label">Areas</span>
                                    </div>
                                    <div class="stat-card">
                                        <span class="stat-number"><?php echo count($warnings); ?></span>
                                        <span class="stat-label">Alerts</span>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($warnings)): ?>
                            <div class="sidebar-section">
                                <h3 class="sidebar-title">Low Stock Alert</h3>
                                <div class="warning-panel">
                                    <?php foreach ($warnings as $warning): ?>
                                    <div class="warning-item">
                                        <strong><?php echo htmlspecialchars($warning['item_name']); ?></strong>
                                        <span class="item-quantity"><?php echo $warning['quantity']; ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if ($user_department === 'Command' || $user_department === 'ENG/OPS'): ?>
                            <div class="sidebar-section">
                                <h3 class="sidebar-title">Operations</h3>
                                <button class="btn orange" onclick="showBulkModal()" style="width: 100%; margin-bottom: 0.5rem;">
                                    Bulk Delivery
                                </button>
                                <button class="btn gold" onclick="showActivityLog()" style="width: 100%;">
                                    Activity Log
                                </button>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Main Content Area -->
                        <div class="cargo-main">
                            <?php if (!empty($areas_result)): ?>
                                <?php foreach ($areas_result as $area): ?>
                                    <?php
                                    $area_class = 'misc';
                                    if (strpos($area['area_code'], 'MEDSCI') !== false) $area_class = 'medsci';
                                    elseif (strpos($area['area_code'], 'ENGOPS') !== false) $area_class = 'engops';
                                    elseif (strpos($area['area_code'], 'SECTAC') !== false) $area_class = 'sectac';
                                    
                                    // Get inventory for this area
                                    $inventory_stmt = $pdo->prepare("SELECT * FROM cargo_inventory WHERE area_id = ? ORDER BY item_name");
                                    $inventory_stmt->execute([$area['id']]);
                                    $inventory_items = $inventory_stmt->fetchAll();
                                    
                                    $canModify = canModifyArea($area['department_access'], $user_department);
                                    ?>
                                    
                                    <div class="cargo-area <?php echo $area_class; ?>">
                                        <div class="area-header">
                                            <h2 class="area-title"><?php echo htmlspecialchars($area['area_name']); ?></h2>
                                            <div class="permission-badge">
                                                <?php echo $canModify ? 'Modify Access' : 'View Only'; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="cargo-content">
                                            <p class="item-description" style="margin-bottom: 1rem; color: var(--blue);">
                                                <?php echo htmlspecialchars($area['description']); ?>
                                            </p>

                                            <?php if (!empty($inventory_items)): ?>
                                            <div class="inventory-grid">
                                                <?php foreach ($inventory_items as $item): ?>
                                                <div class="inventory-card <?php echo ($item['quantity'] <= $item['min_quantity']) ? 'low-stock' : ''; ?>">
                                                    <div class="item-header">
                                                        <h4 class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                                        <span class="item-quantity"><?php echo $item['quantity']; ?><?php echo isset($item['unit_type']) ? ' ' . htmlspecialchars($item['unit_type']) : ''; ?></span>
                                                    </div>
                                                    
                                                    <?php if (!empty($item['item_description'])): ?>
                                                    <p class="item-description"><?php echo htmlspecialchars($item['item_description']); ?></p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($canModify): ?>
                                                    <div class="item-actions">
                                                        <button class="btn small" onclick="modifyInventory(<?php echo $item['id']; ?>, 'add', '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                                            Add
                                                        </button>
                                                        <button class="btn red small" onclick="modifyInventory(<?php echo $item['id']; ?>, 'remove', '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                                            Remove
                                                        </button>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                            <?php else: ?>
                                            <div class="empty-state">
                                                <p>No items currently stored in this area</p>
                                            </div>
                                            <?php endif; ?>

                                            <?php if ($canModify): ?>
                                            <div class="add-item-form">
                                                <h4>Add New Item</h4>
                                                <form method="post" action="">
                                                    <div class="form-grid">
                                                        <div class="form-group">
                                                            <label class="form-label">Item Name</label>
                                                            <input type="text" name="item_name" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="form-label">Quantity</label>
                                                            <input type="number" name="quantity" min="1" required>
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="form-label">Min Threshold</label>
                                                            <input type="number" name="min_quantity" min="0" value="5">
                                                        </div>
                                                        <div class="form-group">
                                                            <label class="form-label">Unit Type</label>
                                                            <select name="unit_type">
                                                                <option value="pieces">Pieces</option>
                                                                <option value="kg">Kilograms</option>
                                                                <option value="liters">Liters</option>
                                                                <option value="boxes">Boxes</option>
                                                                <option value="pallets">Pallets</option>
                                                                <option value="cases">Cases</option>
                                                                <option value="units">Units</option>
                                                            </select>
                                                        </div>
                                                        <div class="form-group form-full">
                                                            <label class="form-label">Description</label>
                                                            <textarea name="description" rows="3" placeholder="Optional item description..."></textarea>
                                                        </div>
                                                    </div>
                                                    <input type="hidden" name="area_id" value="<?php echo $area['id']; ?>">
                                                    <input type="hidden" name="action" value="add_item">
                                                    <input type="hidden" name="submitted_by" value="<?php echo htmlspecialchars($_SESSION['character_name']); ?>">
                                                    <button type="submit" class="btn" style="margin-top: 1rem;">
                                                        Add Item
                                                    </button>
                                                </form>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <p>No cargo areas found. Please run the setup script to initialize the cargo bay system.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                </main>
            </div>
        </div>
    </section>

    <!-- Modals -->
    <div id="modifyModal" class="modal">
        <div class="modal-content">
            <h3>Modify Inventory</h3>
            <form id="modifyForm" method="post" action="">
                <input type="hidden" name="action" value="modify_inventory">
                <input type="hidden" name="inventory_id" id="modifyInventoryId">
                <input type="hidden" name="modify_action" id="modifyAction">
                
                <div class="form-group">
                    <label class="form-label">Item:</label>
                    <span id="modifyItemName" style="color: var(--blue); font-weight: bold;"></span>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="modifyQuantity">Quantity to <span id="actionLabel">add</span>:</label>
                    <input type="number" name="quantity" id="modifyQuantity" min="1" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="modifyReason">Reason:</label>
                    <textarea name="reason" id="modifyReason" rows="3" placeholder="Enter reason for inventory change..."></textarea>
                </div>
                
                <input type="hidden" name="submitted_by" value="<?php echo htmlspecialchars($_SESSION['character_name']); ?>">
                
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn" id="modifySubmitBtn">Confirm</button>
                    <button type="button" class="btn red" onclick="closeModal('modifyModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="bulkModal" class="modal">
        <div class="modal-content">
            <h3>🚚 Bulk Delivery System</h3>
            <form method="post" action="">
                <input type="hidden" name="action" value="bulk_delivery">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Storage Area:</label>
                        <select name="area_id" required>
                            <option value="">Select Area</option>
                            <?php foreach ($areas_result as $area): ?>
                                <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['area_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Item Name:</label>
                        <input type="text" name="item_name" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity:</label>
                        <input type="number" name="quantity" min="1" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Type:</label>
                        <select name="unit_type">
                            <option value="pieces">Pieces</option>
                            <option value="kg">Kilograms</option>
                            <option value="liters">Liters</option>
                            <option value="boxes">Boxes</option>
                            <option value="pallets">Pallets</option>
                            <option value="cases">Cases</option>
                        </select>
                    </div>
                    <div class="form-group form-full">
                        <label class="form-label">Description:</label>
                        <textarea name="description" rows="3"></textarea>
                    </div>
                </div>
                
                <input type="hidden" name="submitted_by" value="<?php echo htmlspecialchars($_SESSION['character_name']); ?>">
                
                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn orange">🚚 Execute Bulk Delivery</button>
                    <button type="button" class="btn red" onclick="closeModal('bulkModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function modifyInventory(inventoryId, action, itemName) {
            document.getElementById('modifyInventoryId').value = inventoryId;
            document.getElementById('modifyAction').value = action;
            document.getElementById('modifyItemName').textContent = itemName;
            document.getElementById('actionLabel').textContent = action;
            document.getElementById('modifySubmitBtn').textContent = action === 'add' ? 'Add Items' : 'Remove Items';
            document.getElementById('modifySubmitBtn').className = action === 'add' ? 'btn' : 'btn red';
            document.getElementById('modifyModal').style.display = 'block';
        }

        function showBulkModal() {
            document.getElementById('bulkModal').style.display = 'block';
        }

        function showActivityLog() {
            // This could open a modal with detailed activity log
            window.location.href = '#activity-log';
            alert('Activity log feature - would show detailed logs in a modal');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }

        // Add some nice animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.inventory-card');
            cards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'slideInUp 0.5s ease forwards';
            });
        });
    </script>

    <style>
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
    
    <!-- Remove Item Modal -->
    <div id="removeModal" class="modal">
        <div class="modal-content">
            <h3>Remove Item from Cargo</h3>
            <form method="POST" id="removeForm">
                <input type="hidden" name="action" value="remove_item">
                <input type="hidden" name="inventory_id" id="removeItemId">
                
                <p>Item: <span id="removeItemName"></span></p>
                <p>Available: <span id="removeItemQuantity"></span></p>
                
                <label>Quantity to Remove:</label>
                <input type="number" name="remove_quantity" id="removeQuantityInput" min="1" required>
                
                <label>Reason (Optional):</label>
                <textarea name="reason" placeholder="Explain why you're removing this item..." rows="3"></textarea>
                
                <div style="margin-top: 1rem;">
                    <button type="submit" class="btn red">REMOVE ITEM</button>
                    <button type="button" class="btn" onclick="closeRemoveModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function playSoundAndRedirect(audioId, url) {
            document.getElementById(audioId).play();
            setTimeout(() => window.location.href = url, 200);
        }
        
        function openRemoveModal(itemId, itemName, quantity) {
            document.getElementById('removeItemId').value = itemId;
            document.getElementById('removeItemName').textContent = itemName;
            document.getElementById('removeItemQuantity').textContent = quantity;
            document.getElementById('removeQuantityInput').max = quantity;
            document.getElementById('removeModal').style.display = 'block';
        }
        
        function closeRemoveModal() {
            document.getElementById('removeModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('removeModal');
            if (event.target === modal) {
                closeRemoveModal();
            }
        }
    </script>
                </main>
            </div>
        </div>
    </section>
</body>
</html>
