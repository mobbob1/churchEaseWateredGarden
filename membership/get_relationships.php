<?php
require_once '../includes/auth.php';
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'] ?? '';

    try {
        // Get all relationships for the member
        $stmt = $pdo->prepare("
            SELECT r.id, r.relationship_type, 
                   name as related_member_name
            FROM member_relationships r
            JOIN members m ON (
                CASE 
                    WHEN r.member_id = ? THEN m.id = r.related_member_id
                    WHEN r.related_member_id = ? THEN m.id = r.member_id
                END
            )
            WHERE r.member_id = ? OR r.related_member_id = ?
            ORDER BY m.name
        ");
        $stmt->execute([$member_id, $member_id, $member_id, $member_id]);
        $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'relationships' => $relationships]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}