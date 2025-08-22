<?php
// Awards API Endpoint
header('Content-Type: application/json');
require_once '../includes/config.php';

// Enable CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

try {
    if (!isset($_GET['roster_id'])) {
        throw new Exception('roster_id parameter is required');
    }
    
    $roster_id = (int)$_GET['roster_id'];
    
    // Get all awards for the specified crew member
    $stmt = $pdo->prepare("
        SELECT 
            ca.id as crew_award_id,
            ca.date_awarded,
            ca.citation,
            a.id as award_id,
            a.name as award_name,
            a.type as award_type,
            a.specialization,
            a.description,
            a.order_precedence,
            aw.rank as awarding_rank,
            aw.first_name as awarding_first_name,
            aw.last_name as awarding_last_name
        FROM crew_awards ca
        JOIN awards a ON ca.award_id = a.id
        LEFT JOIN roster aw ON ca.awarded_by_roster_id = aw.id
        WHERE ca.roster_id = ?
        ORDER BY a.order_precedence, ca.date_awarded DESC
    ");
    
    $stmt->execute([$roster_id]);
    $awards = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group awards by type for better organization
    $grouped_awards = [
        'medals' => [],
        'ribbons' => [],
        'badges' => [],
        'total_count' => count($awards)
    ];
    
    foreach ($awards as $award) {
        $award_data = [
            'crew_award_id' => $award['crew_award_id'],
            'award_id' => $award['award_id'],
            'name' => $award['award_name'],
            'type' => $award['award_type'],
            'specialization' => $award['specialization'],
            'description' => $award['description'],
            'date_awarded' => $award['date_awarded'],
            'citation' => $award['citation'],
            'awarded_by' => null
        ];
        
        // Add awarding officer info if available
        if ($award['awarding_first_name']) {
            $award_data['awarded_by'] = [
                'rank' => $award['awarding_rank'],
                'name' => $award['awarding_first_name'] . ' ' . $award['awarding_last_name']
            ];
        }
        
        // Group by type
        switch (strtolower($award['award_type'])) {
            case 'medal':
                $grouped_awards['medals'][] = $award_data;
                break;
            case 'ribbon':
                $grouped_awards['ribbons'][] = $award_data;
                break;
            case 'badge':
                $grouped_awards['badges'][] = $award_data;
                break;
        }
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $grouped_awards
    ]);
    
} catch (Exception $e) {
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
