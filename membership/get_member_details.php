<?php
require_once '../includes/auth.php';
require_once '../config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Member ID is required']);
    exit;
}

$id = $_GET['id'];

try {
    $stmt = $pdo->prepare("
        SELECT m.*, mt.type_name,
               bc.class_name as bible_class_name,
               o.organization_name,
               CONCAT(m.home_address, 
                     CASE 
                         WHEN m.work_address IS NOT NULL AND m.work_address != '' 
                         THEN CONCAT('\nWork/School: ', m.work_address) 
                         ELSE '' 
                     END) as full_address
        FROM members m 
        LEFT JOIN member_types mt ON m.member_type_id = mt.id
        LEFT JOIN bible_classes bc ON m.bible_class_id = bc.id
        LEFT JOIN organizations o ON m.organization_id = o.id
        WHERE m.id = ?
    ");
    
    $stmt->execute([$id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($member) {
        // Format contact information
        $contact = $member['contact_number_1'];
        if (!empty($member['contact_number_2'])) {
            $contact .= ", " . $member['contact_number_2'];
        }
        
        $html = "
            <div class='row'>
                <div class='col-md-6'>
                    <p><strong>Name:</strong> " . htmlspecialchars($member['name']) . "</p>
                    <p><strong>Gender:</strong> " . htmlspecialchars($member['gender']) . "</p>
                    <p><strong>Member Type:</strong> " . htmlspecialchars($member['type_name']) . "</p>
                    <p><strong>Contact:</strong> " . htmlspecialchars($contact) . "</p>
                    <p><strong>Email:</strong> " . htmlspecialchars($member['email']) . "</p>
                </div>
                <div class='col-md-6'>
                    <p><strong>Address:</strong> " . nl2br(htmlspecialchars($member['full_address'])) . "</p>
                    <p><strong>GPS Number:</strong> " . htmlspecialchars($member['gps_number']) . "</p>
                    <p><strong>Bible Class:</strong> " . htmlspecialchars($member['bible_class_name'] ?? 'Not Assigned') . "</p>
                    <p><strong>Organization:</strong> " . htmlspecialchars($member['organization_name'] ?? 'Not Assigned') . "</p>
                </div>
            </div>
            <div class='row mt-3'>
                <div class='col-md-12'>
                    <h6>Next of Kin Information</h6>
                    <p><strong>Name:</strong> " . htmlspecialchars($member['next_of_kin_name']) . "</p>
                    <p><strong>Contact:</strong> " . htmlspecialchars($member['next_of_kin_contact_number']) . "</p>
                </div>
            </div>
        ";
        
        echo json_encode([
            'success' => true,
            'html' => $html
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Member not found'
        ]);
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}