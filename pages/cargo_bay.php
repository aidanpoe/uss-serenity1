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
                $description = $_POST['description'] ?? '';
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
                            $insert_stmt = $pdo->prepare("INSERT INTO cargo_inventory (area_id, item_name, description, quantity, min_quantity, unit_type, added_by, added_department) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                            $insert_stmt->execute([$area_id, $item_name, $description, $quantity, $min_quantity, $unit_type, $user_name, $user_department]);
                            
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
                    $description = $_POST['description'] ?? '';
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
                            $insert_stmt = $pdo->prepare("INSERT INTO cargo_inventory (area_id, item_name, description, quantity, unit_type, added_by, added_department) VALUES (?, ?, ?, ?, ?, ?, ?)");
                            $insert_stmt->execute([$area_id, $item_name, $description, $quantity, $unit_type, $user_name, $user_department]);
                            
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
$areas_result = $pdo->query("SELECT * FROM cargo_areas ORDER BY area_name");

// Get low stock warnings for user's department
$warnings = [];
if ($user_department) {
    $warning_stmt = $pdo->prepare("
        SELECT ci.item_name, ci.quantity, ci.min_quantity, ca.area_name 
        FROM cargo_inventory ci 
        JOIN cargo_areas ca ON ci.area_id = ca.id 
        WHERE ci.quantity <= ci.min_quantity 
        AND (ca.department_access LIKE ? OR ca.department_access LIKE '%COMMAND%')
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
        .cargo-container {
            display: grid;
            grid-template-columns: 1fr 3fr;
            gap: 2rem;
            margin: 2rem 0;
        }
        
        .cargo-sidebar {
            background: rgba(0,0,0,0.4);
            border: 2px solid var(--blue);
            border-radius: 10px;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        
        .cargo-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .cargo-area {
            background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(0,0,0,0.3));
            border: 2px solid;
            border-radius: 15px;
            padding: 1.5rem;
            margin: 0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .cargo-area::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: inherit;
            border-radius: 15px 15px 0 0;
        }
        
        .cargo-area.medsci { 
            border-color: var(--blue);
            background: linear-gradient(135deg, rgba(85,102,255,0.2), rgba(85,102,255,0.05));
        }
        .cargo-area.engops { 
            border-color: var(--orange);
            background: linear-gradient(135deg, rgba(255,136,0,0.2), rgba(255,136,0,0.05));
        }
        .cargo-area.sectac { 
            border-color: var(--gold);
            background: linear-gradient(135deg, rgba(255,170,0,0.2), rgba(255,170,0,0.05));
        }
        .cargo-area.misc { 
            border-color: var(--red);
            background: linear-gradient(135deg, rgba(204,68,68,0.2), rgba(204,68,68,0.05));
        }
        
        .cargo-area:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .area-header {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .area-title {
            margin: 0;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .area-icon {
            font-size: 1.2rem;
            padding: 0.3rem;
            border-radius: 5px;
            background: rgba(255,255,255,0.1);
        }
        
        .permission-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .permission-modify {
            background: rgba(0,255,0,0.2);
            color: var(--blue);
            border: 1px solid var(--blue);
        }
        
        .permission-view {
            background: rgba(255,0,0,0.2);
            color: var(--red);
            border: 1px solid var(--red);
        }
        
        .inventory-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .inventory-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 1rem;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .inventory-card:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-1px);
        }
        
        .inventory-card.low-stock {
            border-color: var(--red);
            background: rgba(255,0,0,0.1);
            animation: pulse-warning 2s infinite;
        }
        
        @keyframes pulse-warning {
            0%, 100% { box-shadow: 0 0 5px rgba(255,0,0,0.3); }
            50% { box-shadow: 0 0 15px rgba(255,0,0,0.6); }
        }
        
        .item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }
        
        .item-name {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--bluey);
            margin: 0;
        }
        
        .item-quantity {
            background: rgba(255,255,255,0.1);
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .item-description {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            line-height: 1.4;
            margin: 0.5rem 0;
        }
        
        .item-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .btn {
            background: var(--blue);
            color: black;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            text-transform: uppercase;
        }
        
        .btn:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
        }
        
        .btn.orange { background: var(--orange); }
        .btn.red { background: var(--red); color: white; }
        .btn.gold { background: var(--gold); }
        .btn.small { padding: 0.3rem 0.6rem; font-size: 0.8rem; }
        
        .add-item-form {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 1rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
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
            font-size: 0.9rem;
            font-weight: bold;
            color: var(--bluey);
        }
        
        input, select, textarea {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 0.8rem;
            border-radius: 6px;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--blue);
            background: rgba(255,255,255,0.15);
            box-shadow: 0 0 0 2px rgba(85,102,255,0.3);
        }
        
        .sidebar-section {
            margin-bottom: 2rem;
        }
        
        .sidebar-title {
            color: var(--blue);
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.2);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            padding: 1rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--bluey);
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
            text-transform: uppercase;
        }
        
        .warning-panel {
            background: linear-gradient(135deg, rgba(255,0,0,0.3), rgba(255,0,0,0.1));
            border: 2px solid var(--red);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .warning-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .warning-item:last-child {
            border-bottom: none;
        }
        
        .empty-state {
            text-align: center;
            color: rgba(255,255,255,0.6);
            padding: 2rem;
            font-style: italic;
        }
        
        .bulk-delivery-panel {
            background: linear-gradient(135deg, rgba(255,136,0,0.3), rgba(255,136,0,0.1));
            border: 2px solid var(--orange);
            border-radius: 15px;
            padding: 2rem;
            margin: 2rem 0;
        }
        
        .activity-log {
            background: rgba(0,0,0,0.4);
            border: 2px solid var(--african-violet);
            border-radius: 15px;
            padding: 1.5rem;
            margin: 2rem 0;
        }
        
        .log-item {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 1rem;
            align-items: center;
            padding: 0.8rem;
            background: rgba(255,255,255,0.05);
            border-radius: 8px;
            margin: 0.5rem 0;
        }
        
        .log-action {
            background: var(--blue);
            color: black;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .log-details {
            flex: 1;
        }
        
        .log-time {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.6);
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
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: linear-gradient(135deg, #000 0%, #001122 100%);
            margin: 10% auto;
            padding: 2rem;
            border: 2px solid var(--blue);
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            color: white;
            animation: modalSlideIn 0.3s ease;
        }
        
        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .debug-panel {
            background: rgba(0,100,200,0.2);
            border: 1px solid var(--blue);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .cargo-container {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .cargo-sidebar {
                position: static;
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
                                <h3 class="sidebar-title">📊 Quick Stats</h3>
                                <div class="quick-stats">
                                    <div class="stat-card">
                                        <div class="stat-number"><?php echo count($areas_result); ?></div>
                                        <div class="stat-label">Storage Areas</div>
                                    </div>
                                    <div class="stat-card">
                                        <div class="stat-number"><?php echo count($warnings); ?></div>
                                        <div class="stat-label">Low Stock Items</div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($warnings)): ?>
                            <div class="sidebar-section">
                                <h3 class="sidebar-title">⚠️ Low Stock Warnings</h3>
                                <div class="warning-panel">
                                    <?php foreach ($warnings as $warning): ?>
                                    <div class="warning-item">
                                        <span><strong><?php echo htmlspecialchars($warning['item_name']); ?></strong></span>
                                        <span class="item-quantity"><?php echo $warning['quantity']; ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="sidebar-section">
                                <h3 class="sidebar-title">🚚 Bulk Operations</h3>
                                <button class="btn orange" onclick="showBulkModal()" style="width: 100%; margin-bottom: 0.5rem;">
                                    Bulk Delivery
                                </button>
                                <button class="btn gold" onclick="showActivityLog()" style="width: 100%;">
                                    Activity Log
                                </button>
                            </div>
                        </div>

                        <!-- Main Content Area -->
                        <div class="cargo-main">
                            <?php foreach ($areas_result as $area): ?>
                                <?php
                                $area_class = '';
                                if (strpos($area['area_code'], 'MEDSCI') !== false) $area_class = 'medsci';
                                elseif (strpos($area['area_code'], 'ENGOPS') !== false) $area_class = 'engops';
                                elseif (strpos($area['area_code'], 'SECTAC') !== false) $area_class = 'sectac';
                                else $area_class = 'misc';
                                
                                // Get inventory for this area
                                $inventory_stmt = $pdo->prepare("SELECT * FROM cargo_inventory WHERE area_id = ? ORDER BY item_name");
                                $inventory_stmt->execute([$area['id']]);
                                $inventory_items = $inventory_stmt->fetchAll();
                                
                                $canModify = canModifyArea($area['department_access'], $user_department);
                                
                                // Get area icon
                                $icon = '📦';
                                if ($area_class == 'medsci') $icon = '🏥';
                                elseif ($area_class == 'engops') $icon = '⚙️';
                                elseif ($area_class == 'sectac') $icon = '🛡️';
                                elseif ($area_class == 'misc') $icon = '📋';
                                ?>
                                
                                <div class="cargo-area <?php echo $area_class; ?>">
                                    <div class="area-header">
                                        <h2 class="area-title">
                                            <span class="area-icon"><?php echo $icon; ?></span>
                                            <?php echo htmlspecialchars($area['area_name']); ?>
                                        </h2>
                                        <div class="permission-badge <?php echo $canModify ? 'permission-modify' : 'permission-view'; ?>">
                                            <?php echo $canModify ? '✏️ Modify' : '👁️ View Only'; ?>
                                        </div>
                                    </div>

                                    <p class="item-description" style="margin-bottom: 1rem;">
                                        <?php echo htmlspecialchars($area['description']); ?>
                                    </p>

                                    <?php if (!empty($inventory_items)): ?>
                                    <div class="inventory-grid">
                                        <?php foreach ($inventory_items as $item): ?>
                                        <div class="inventory-card <?php echo ($item['quantity'] <= $item['min_quantity']) ? 'low-stock' : ''; ?>">
                                            <div class="item-header">
                                                <h4 class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                                <span class="item-quantity"><?php echo $item['quantity']; ?> <?php echo htmlspecialchars($item['unit_type']); ?></span>
                                            </div>
                                            
                                            <?php if (!empty($item['description'])): ?>
                                            <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                            <?php endif; ?>
                                            
                                            <?php if ($canModify): ?>
                                            <div class="item-actions">
                                                <button class="btn small" onclick="modifyInventory(<?php echo $item['id']; ?>, 'add', '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                                    + Add
                                                </button>
                                                <button class="btn red small" onclick="modifyInventory(<?php echo $item['id']; ?>, 'remove', '<?php echo htmlspecialchars($item['item_name']); ?>')">
                                                    - Remove
                                                </button>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="empty-state">
                                        <p>📦 No items currently stored in this area</p>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($canModify): ?>
                                    <div class="add-item-form">
                                        <h4 style="margin-top: 0; color: var(--blue);">➕ Add New Item</h4>
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
                                                    <label class="form-label">Minimum Threshold</label>
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
                                                ➕ Add Item to Inventory
                                            </button>
                                        </form>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
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
</body>
</html>
