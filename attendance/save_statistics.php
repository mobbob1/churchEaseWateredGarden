<?php
require_once('../config.php');
require_once('../includes/auth.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $week = $_POST['week'];
    $statistics = $_POST['statistics'];
    
    try {
        $db->beginTransaction();
        
        // First, check if statistics exist for this week
        $stmt = $db->prepare("SELECT id FROM church_statistics WHERE week = ?");
        $stmt->execute([$week]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing statistics
            foreach ($statistics as $operation => $values) {
                $stmt = $db->prepare("UPDATE church_statistics_details 
                                    SET week1 = ?, week2 = ?, week3 = ?, week4 = ?, week5 = ?, total = ?
                                    WHERE statistics_id = ? AND operation = ?");
                $stmt->execute([
                    $values[1], $values[2], $values[3], $values[4], $values[5], $values[6],
                    $existing['id'], $operation
                ]);
            }
        } else {
            // Insert new statistics
            $stmt = $db->prepare("INSERT INTO church_statistics (week, created_at) VALUES (?, NOW())");
            $stmt->execute([$week]);
            $statisticsId = $db->lastInsertId();
            
            foreach ($statistics as $operation => $values) {
                $stmt = $db->prepare("INSERT INTO church_statistics_details 
                                    (statistics_id, operation, week1, week2, week3, week4, week5, total)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $statisticsId, $operation,
                    $values[1], $values[2], $values[3], $values[4], $values[5], $values[6]
                ]);
            }
        }
        
        $db->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $db->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}