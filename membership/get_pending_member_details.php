<?php
require_once __DIR__ . '/../includes/auth.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    die('Unauthorized access');
}

// Check if user has appropriate role
$allowedRoles = ['class_leader', 'seer', 'admin'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    die('Access denied');
}

// Validate input
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid member ID');
}

$id = (int)$_GET['id'];

try {
    // Get the class leader's assigned class
    $stmt = $pdo->prepare("SELECT bible_class_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $classLeaderData = $stmt->fetch();
    $bible_class_id = $classLeaderData['bible_class_id'];

    // Get member details with additional approval-specific information
    $stmt = $pdo->prepare("
        SELECT m.*,
               mt.name as type_name,
               bc.class_name,
               o.name as organization_name,
               CONCAT(r.first_name, ' ', r.surname) as referred_by_name,
               r.contact_number_1 as referrer_contact,
               r.email as referrer_email,
               m.created_at as request_date,
               CONCAT(m.address, 
                     CASE 
                         WHEN m.work_address IS NOT NULL AND m.work_address != '' 
                         THEN CONCAT('\nWork/School: ', m.work_address) 
                         ELSE '' 
                     END) as full_address
        FROM members m 
        LEFT JOIN member_types mt ON m.member_type_id = mt.id
        LEFT JOIN bible_classes bc ON m.bible_class_id = bc.id
        LEFT JOIN organizations o ON m.organization_id = o.id
        LEFT JOIN members r ON m.referred_by = r.id
        WHERE m.id = ? 
        AND m.bible_class_id = ?
        AND m.status = 'pending'
    ");
    
    $stmt->execute([$id, $bible_class_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        die('Member not found or not pending approval');
    }

    // Format contact information
    $contact = $member['contact_number_1'];
    if (!empty($member['contact_number_2'])) {
        $contact .= ", " . $member['contact_number_2'];
    }

    // Get member's attendance history if any (as visitor)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as visit_count,
               MAX(attendance_date) as last_visit
        FROM attendance
        WHERE member_id = ?
    ");
    $stmt->execute([$id]);
    $attendance = $stmt->fetch();
    
    // Prepare the HTML response
    $html = "
        <div class='row'>
            <div class='col-md-6'>
                <h5>Personal Information</h5>
                <p><strong>Name:</strong> " . htmlspecialchars($member['first_name'] . ' ' . $member['surname']) . "</p>
                <p><strong>Gender:</strong> " . htmlspecialchars($member['gender']) . "</p>
                <p><strong>Date of Birth:</strong> " . htmlspecialchars($member['date_of_birth']) . "</p>
                <p><strong>Contact:</strong> " . htmlspecialchars($contact) . "</p>
                <p><strong>Email:</strong> " . htmlspecialchars($member['email']) . "</p>
                <p><strong>Address:</strong> " . nl2br(htmlspecialchars($member['full_address'])) . "</p>
            </div>
            <div class='col-md-6'>
                <h5>Membership Information</h5>
                <p><strong>Member Type:</strong> " . htmlspecialchars($member['type_name']) . "</p>
                <p><strong>Bible Class:</strong> " . htmlspecialchars($member['class_name']) . "</p>
                <p><strong>Organization:</strong> " . htmlspecialchars($member['organization_name'] ?? 'None') . "</p>
                <p><strong>Request Date:</strong> " . date('Y-m-d', strtotime($member['request_date'])) . "</p>
                " . ($attendance['visit_count'] > 0 ? "
                <p><strong>Previous Visits:</strong> " . $attendance['visit_count'] . "</p>
                <p><strong>Last Visit:</strong> " . date('Y-m-d', strtotime($attendance['last_visit'])) . "</p>
                " : "") . "
            </div>
        </div>
        
        <div class='row mt-3'>
            <div class='col-md-12'>
                <h5>Referral Information</h5>
                " . ($member['referred_by_name'] ? "
                <p><strong>Referred By:</strong> " . htmlspecialchars($member['referred_by_name']) . "</p>
                <p><strong>Referrer Contact:</strong> " . htmlspecialchars($member['referrer_contact']) . "</p>
                <p><strong>Referrer Email:</strong> " . htmlspecialchars($member['referrer_email']) . "</p>
                " : "<p>No referral information available</p>") . "
            </div>
        </div>
        
        " . (!empty($member['notes']) ? "
        <div class='row mt-3'>
            <div class='col-md-12'>
                <h5>Additional Notes</h5>
                <p>" . nl2br(htmlspecialchars($member['notes'])) . "</p>
            </div>
        </div>
        " : "");

    echo $html;
    
} catch (PDOException $e) {
    die('Error fetching member details: ' . $e->getMessage());
}
?>