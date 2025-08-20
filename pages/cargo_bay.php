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
    return in_array($user_dept, $allowed_depts) || $user_dept === 'COMMAND';
}

// Function to check if user can modify area
function canModifyArea($area_access, $user_dept) {
    $allowed_depts = explode(',', $area_access);
    return in_array($user_dept, $allowed_depts) || $user_dept === 'COMMAND' || $user_dept === 'ENG/OPS';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_item':
                $area_id = $_POST['area_id'];
                $item_name = $_POST['item_name'];
                $item_description = $_POST['item_description'];
                $quantity = $_POST['quantity'];
                
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
                            // Update existing item
                            $new_quantity = $existing['quantity'] + $quantity;
                            
                            $update_stmt = $pdo->prepare("UPDATE cargo_inventory SET quantity = ?, last_modified = CURRENT_TIMESTAMP WHERE id = ?");
                            $update_stmt->execute([$new_quantity, $existing['id']]);
                            
                            // Log the addition
                            $log_stmt = $pdo->prepare("INSERT INTO cargo_logs (inventory_id, action, quantity_change, previous_quantity, new_quantity, performed_by, performer_department) VALUES (?, 'ADD', ?, ?, ?, ?, ?)");
                            $log_stmt->execute([$existing['id'], $quantity, $existing['quantity'], $new_quantity, $user_name, $user_department]);
                        } else {
                            // Add new item
                            $insert_stmt = $pdo->prepare("INSERT INTO cargo_inventory (area_id, item_name, item_description, quantity, added_by, added_department) VALUES (?, ?, ?, ?, ?, ?)");
                            $insert_stmt->execute([$area_id, $item_name, $item_description, $quantity, $user_name, $user_department]);
                            
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
                if ($user_department === 'ENG/OPS' || $user_department === 'COMMAND') {
                    $area_id = $_POST['area_id'];
                    $item_name = $_POST['item_name'];
                    $item_description = $_POST['item_description'];
                    $quantity = $_POST['quantity'];
                    
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
                            $insert_stmt = $pdo->prepare("INSERT INTO cargo_inventory (area_id, item_name, item_description, quantity, added_by, added_department) VALUES (?, ?, ?, ?, ?, ?)");
                            $insert_stmt->execute([$area_id, $item_name, $item_description, $quantity, $user_name, $user_department]);
                            
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
        .cargo-area {
            background: rgba(0,0,0,0.3);
            border: 2px solid var(--blue);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .cargo-area.medsci { border-color: var(--blue); }
        .cargo-area.engops { border-color: var(--orange); }
        .cargo-area.sectac { border-color: var(--gold); }
        .cargo-area.misc { border-color: var(--red); }
        
        .inventory-item {
            background: rgba(255,255,255,0.1);
            padding: 0.8rem;
            margin: 0.5rem 0;
            border-radius: 5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .low-stock {
            background: rgba(255,0,0,0.3);
            border: 1px solid var(--red);
        }
        
        .warning-panel {
            background: rgba(255,0,0,0.2);
            border: 2px solid var(--red);
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .form-section {
            background: rgba(0,0,0,0.2);
            padding: 1rem;
            border-radius: 5px;
            margin: 1rem 0;
        }
        
        .btn {
            background-color: var(--blue);
            color: black;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .btn.orange { background-color: var(--orange); }
        .btn.red { background-color: var(--red); }
        .btn.gold { background-color: var(--gold); }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        input, select, textarea {
            background: rgba(255,255,255,0.1);
            border: 1px solid var(--blue);
            color: white;
            padding: 0.5rem;
            border-radius: 3px;
            width: 100%;
            margin: 0.25rem 0;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
        }
        
        .modal-content {
            background: linear-gradient(135deg, #000 0%, #001122 100%);
            margin: 15% auto;
            padding: 2rem;
            border: 2px solid var(--blue);
            border-radius: 10px;
            width: 50%;
            color: white;
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
                    
                    <?php if (!empty($warnings)): ?>
                        <div class="warning-panel">
                            <h3 style="color: var(--red);">⚠️ LOW STOCK WARNINGS</h3>
                            <?php foreach ($warnings as $warning): ?>
                                <div style="margin: 0.5rem 0;">
                                    <strong><?php echo htmlspecialchars($warning['item_name']); ?></strong> 
                                    in <?php echo htmlspecialchars($warning['area_name']); ?>
                                    - Only <?php echo $warning['quantity']; ?> remaining (Min: <?php echo $warning['min_quantity']; ?>)
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Storage Areas -->
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
                        ?>
                        
                        <div class="cargo-area <?php echo $area_class; ?>">
                            <h3><?php echo htmlspecialchars($area['area_name']); ?></h3>
                            <p><?php echo htmlspecialchars($area['description']); ?></p>
                            <p><strong>Access:</strong> <?php echo htmlspecialchars($area['department_access']); ?></p>
                            
                            <!-- Inventory Items -->
                            <?php if (!empty($inventory_items)): ?>
                                <?php foreach ($inventory_items as $item): ?>
                                    <div class="inventory-item <?php echo ($item['quantity'] <= $item['min_quantity']) ? 'low-stock' : ''; ?>">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                                            <br><small><?php echo htmlspecialchars($item['item_description']); ?></small>
                                            <br><em>Quantity: <?php echo $item['quantity']; ?></em>
                                            <?php if ($item['quantity'] <= $item['min_quantity']): ?>
                                                <span style="color: var(--red);"> ⚠️ LOW STOCK</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (canModifyArea($area['department_access'], $user_department)): ?>
                                            <div>
                                                <button class="btn red" onclick="openRemoveModal(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>', <?php echo $item['quantity']; ?>)">
                                                    REMOVE
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p><em>No items in storage</em></p>
                            <?php endif; ?>
                            
                            <!-- Add Item Form -->
                            <?php if (canModifyArea($area['department_access'], $user_department)): ?>
                                <div class="form-section">
                                    <h4>Add Item</h4>
                                    <form method="POST" style="display: grid; gap: 0.5rem;">
                                        <input type="hidden" name="action" value="add_item">
                                        <input type="hidden" name="area_id" value="<?php echo $area['id']; ?>">
                                        <input type="text" name="item_name" placeholder="Item Name" required>
                                        <textarea name="item_description" placeholder="Item Description" rows="2"></textarea>
                                        <input type="number" name="quantity" placeholder="Quantity" min="1" required>
                                        <button type="submit" class="btn">ADD ITEM</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Bulk Delivery Form -->
                    <?php if ($user_department === 'ENG/OPS' || $user_department === 'COMMAND'): ?>
                        <div class="cargo-area misc">
                            <h3>🚚 BULK DELIVERY OPERATIONS</h3>
                            <p>Mass delivery system for Engineering and Operations personnel</p>
                            
                            <div class="form-section">
                                <form method="POST" style="display: grid; gap: 0.5rem;">
                                    <input type="hidden" name="action" value="bulk_delivery">
                                    <select name="area_id" required>
                                        <option value="">Select Storage Area</option>
                                        <?php
                                        $areas_result2 = $pdo->query("SELECT * FROM cargo_areas ORDER BY area_name");
                                        foreach ($areas_result2 as $area):
                                        ?>
                                            <option value="<?php echo $area['id']; ?>"><?php echo htmlspecialchars($area['area_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="text" name="item_name" placeholder="Item Name" required>
                                    <textarea name="item_description" placeholder="Item Description" rows="2"></textarea>
                                    <input type="number" name="quantity" placeholder="Quantity" min="1" required>
                                    <button type="submit" class="btn orange">BULK DELIVER</button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Recent Activity Log -->
                    <div class="cargo-area misc">
                        <h3>📋 RECENT ACTIVITY LOG</h3>
                        <?php
                        $log_stmt = $pdo->prepare("
                            SELECT cl.*, ci.item_name, ca.area_name 
                            FROM cargo_logs cl 
                            LEFT JOIN cargo_inventory ci ON cl.inventory_id = ci.id 
                            LEFT JOIN cargo_areas ca ON ci.area_id = ca.id 
                            ORDER BY cl.timestamp DESC 
                            LIMIT 10
                        ");
                        $log_stmt->execute();
                        $log_items = $log_stmt->fetchAll();
                        ?>
                        
                        <?php foreach ($log_items as $log): ?>
                            <div class="inventory-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($log['action']); ?></strong>
                                    - <?php echo htmlspecialchars($log['item_name'] ?? 'Unknown Item'); ?>
                                    (<?php echo $log['quantity_change']; ?>)
                                    <br><small>
                                        <?php echo htmlspecialchars($log['performed_by']); ?> 
                                        (<?php echo htmlspecialchars($log['performer_department']); ?>)
                                        - <?php echo $log['timestamp']; ?>
                                    </small>
                                    <?php if ($log['reason']): ?>
                                        <br><em>Reason: <?php echo htmlspecialchars($log['reason']); ?></em>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </main>
            </div>
        </div>
    </section>
    
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
