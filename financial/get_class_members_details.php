<?php
require_once '../includes/auth.php';
require_once '../config.php';

// Check session and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$allowedRoles = ['tithe_manager', 'finance', 'seer', 'admin'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    http_response_code(403);
    exit('Access denied');
}

// Validate input
if (!isset($_GET['member_id']) || !is_numeric($_GET['member_id'])) {
    http_response_code(400);
    exit('Invalid member ID');
}

$member_id = (int)$_GET['member_id'];

try {
    // Get member details
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.first_name,
            m.surname,
            m.contact_number_1,
            m.email,
            bc.class_name,
            COALESCE(SUM(t.amount), 0) as total_tithes,
            COUNT(t.id) as tithe_count,
            MAX(t.payment_date) as last_tithe_date
        FROM members m
        LEFT JOIN bible_classes bc ON m.class_id = bc.id
        LEFT JOIN tithes t ON m.id = t.member_id
        WHERE m.id = ?
        GROUP BY m.id, m.first_name, m.surname, m.contact_number_1, m.email, bc.class_name
    ");
    
    $stmt->execute([$member_id]);
    $memberDetails = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$memberDetails) {
        http_response_code(404);
        exit('Member not found');
    }
    
    // Get recent tithe history
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.amount,
            t.currency,
            t.payment_date,
            t.payment_method,
            t.reference_number,
            t.notes,
            u.username as recorded_by
        FROM tithes t
        LEFT JOIN users u ON t.recorded_by = u.id
        WHERE t.member_id = ?
        ORDER BY t.payment_date DESC
        LIMIT 5
    ");
    
    $stmt->execute([$member_id]);
    $titheHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'member' => $memberDetails,
        'tithe_history' => $titheHistory
    ];
    
    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error');
}
?>