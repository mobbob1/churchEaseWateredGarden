<?php
require_once '../includes/auth.php';
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'] ?? '';

    try {
        // Get members who are not already related
        $stmt = $pdo->prepare("
            SELECT id, name
            FROM members 
            WHERE id != ? 
            AND id NOT IN (
                SELECT related_member_id FROM member_relationships WHERE member_id = ?
                UNION
                SELECT member_id FROM member_relationships WHERE related_member_id = ?
            )
            ORDER BY name
        ");
        $stmt->execute([$member_id, $member_id, $member_id]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'members' => $members]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}