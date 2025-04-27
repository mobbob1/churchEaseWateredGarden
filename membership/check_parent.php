<?php
require_once '../includes/auth.php';
header('Content-Type: application/json');

if (isset($_POST['phone'])) {
    $phone = $_POST['phone'];
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE contact_number_1 = ?");
        $stmt->execute([$phone]);
        $exists = (bool)$stmt->fetchColumn();
        
        echo json_encode(['exists' => $exists]);
    } catch (PDOException $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No phone number provided']);
}