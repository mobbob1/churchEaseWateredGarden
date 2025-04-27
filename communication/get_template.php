<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

// Check if template ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    exit('Template ID is required');
}

try {
    // Get template details
    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $template = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$template) {
        http_response_code(404);
        exit('Template not found');
    }
    
    // Return template data as JSON
    header('Content-Type: application/json');
    echo json_encode($template);
    
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database error: ' . $e->getMessage());
}