<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone'] ?? '';
    $fetch_details = isset($_POST['fetch_details']) && $_POST['fetch_details'] === 'true';
    
    if (empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Phone number is required']);
        exit;
    }

    try {
        // Look up member by phone number
        if ($fetch_details) {
            $stmt = $pdo->prepare("SELECT id, name, email, home_address, gps_number, 
                                        contact_number_1, contact_number_2, 
                                        next_of_kin_name, next_of_kin_contact_number 
                                 FROM members WHERE contact_number_1 = ?");
        } else {
            $stmt = $pdo->prepare("SELECT id, name FROM members WHERE contact_number_1 = ?");
        }
        
        $stmt->execute([$phone]);
        $parent = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($parent) {
            $response = [
                'success' => true,
                'id' => $parent['id'],
                'name' => $parent['name']
            ];
            
            if ($fetch_details) {
                $response = array_merge($response, [
                    'email' => $parent['email'],
                    'home_address' => $parent['home_address'],
                    'gps_number' => $parent['gps_number'],
                    'contact_number_1' => $parent['contact_number_1'],
                    'contact_number_2' => $parent['contact_number_2'],
                    'next_of_kin_name' => $parent['next_of_kin_name'],
                    'next_of_kin_contact_number' => $parent['next_of_kin_contact_number']
                ]);
            }
            
            echo json_encode($response);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No member found with this phone number'
            ]);
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error occurred'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}