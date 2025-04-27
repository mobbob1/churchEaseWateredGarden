<?php
require_once '../includes/auth.php';
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'] ?? '';
    $related_member_id = $_POST['related_member_id'] ?? '';
    $relationship_type = $_POST['relationship_type'] ?? '';

    if (!$member_id || !$related_member_id || !$relationship_type) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        // Check if relationship already exists
        $stmt = $pdo->prepare("SELECT id FROM member_relationships WHERE 
            (member_id = ? AND related_member_id = ?) OR 
            (member_id = ? AND related_member_id = ?)");
        $stmt->execute([$member_id, $related_member_id, $related_member_id, $member_id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Relationship already exists']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO member_relationships (member_id, related_member_id, relationship_type) VALUES (?, ?, ?)");
        $stmt->execute([$member_id, $related_member_id, $relationship_type]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}