<?php
require_once '../includes/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if roster_id is provided
if (!isset($_GET['roster_id']) || !is_numeric($_GET['roster_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid roster ID']);
    exit;
}

$roster_id = (int)$_GET['roster_id'];

try {
    $pdo = getConnection();
    
    // Get all training competencies for this crew member
    $stmt = $pdo->prepare("
        SELECT 
            cc.id,
            cc.status,
            cc.assigned_date,
            cc.completion_date,
            cc.notes,
            cc.completion_notes,
            tm.module_name,
            tm.module_code,
            tm.department as module_department,
            tm.certification_level,
            tm.description,
            assigner.username as assigned_by_name
        FROM crew_competencies cc
        JOIN training_modules tm ON cc.module_id = tm.id
        LEFT JOIN users assigner ON cc.assigned_by = assigner.id
        WHERE cc.roster_id = ?
        ORDER BY tm.department, tm.certification_level, tm.module_name
    ");
    
    $stmt->execute([$roster_id]);
    $competencies = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data for JSON response
    $formatted_competencies = [];
    foreach ($competencies as $comp) {
        $formatted_competencies[] = [
            'id' => $comp['id'],
            'module_name' => $comp['module_name'],
            'module_code' => $comp['module_code'],
            'module_department' => $comp['module_department'],
            'certification_level' => $comp['certification_level'],
            'description' => $comp['description'],
            'status' => $comp['status'],
            'assigned_date' => $comp['assigned_date'],
            'completion_date' => $comp['completion_date'],
            'notes' => $comp['notes'],
            'completion_notes' => $comp['completion_notes'],
            'assigned_by_name' => $comp['assigned_by_name']
        ];
    }
    
    echo json_encode($formatted_competencies);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
