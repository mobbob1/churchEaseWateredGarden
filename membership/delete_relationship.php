<?php
require_once '../includes/auth.php';
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $relationship_id = $_POST['relationship_id'] ?? '';

    if (!$relationship_id) {
        echo json_encode(['success' => false, 'message' => 'Missing relationship ID']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM member_relationships WHERE id = ?");
        $stmt->execute([$relationship_id]);
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}