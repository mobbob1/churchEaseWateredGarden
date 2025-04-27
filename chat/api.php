<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

$sessionManager = new SessionManager($pdo);
$logger = new AuditLogger($pdo);

header('Content-Type: application/json');

// Check session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

// Get user roles
$stmt = $pdo->prepare("
    SELECT GROUP_CONCAT(ur.role_key) as roles 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    WHERE u.id = ? 
    GROUP BY u.id
");
$stmt->execute([$user_id]);
$userRoles = explode(',', $stmt->fetchColumn() ?: '');

// Define role hierarchy for chat permissions
$roleHierarchy = [
    'seer' => ['seer', 'pastorate', 'executive_admin_1', 'executive_admin_2', 'finance', 'hr', 'comms', 'audit', 'reports', 'admin_assistant', 'general'],
    'pastorate' => ['pastorate', 'executive_admin_1', 'executive_admin_2', 'finance', 'hr', 'comms', 'admin_assistant', 'general'],
    'executive_admin_1' => ['executive_admin_1', 'executive_admin_2', 'finance', 'hr', 'comms', 'admin_assistant', 'general'],
    'executive_admin_2' => ['executive_admin_2', 'finance', 'hr', 'comms', 'admin_assistant', 'general'],
    'finance' => ['finance', 'admin_assistant', 'general'],
    'hr' => ['hr', 'admin_assistant', 'general'],
    'comms' => ['comms', 'admin_assistant', 'general'],
    'audit' => ['audit', 'general'],
    'reports' => ['reports', 'general'],
    'admin_assistant' => ['admin_assistant', 'general'],
    'general' => ['general']
];

// Get allowed roles based on user's roles
$allowedRoles = [];
foreach ($userRoles as $role) {
    if (isset($roleHierarchy[$role])) {
        $allowedRoles = array_merge($allowedRoles, $roleHierarchy[$role]);
    }
}
$allowedRoles = array_unique($allowedRoles);

// Check if user has any valid roles
if (empty($allowedRoles)) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

// Helper function to check if user can chat with target roles
function canChatWithRoles($userRoles, $targetRoles) {
    global $roleHierarchy;
    $allowedRoles = [];
    foreach ($userRoles as $role) {
        if (isset($roleHierarchy[$role])) {
            $allowedRoles = array_merge($allowedRoles, $roleHierarchy[$role]);
        }
    }
    $allowedRoles = array_unique($allowedRoles);
    
    foreach ($targetRoles as $role) {
        if (!in_array($role, $allowedRoles)) {
            return false;
        }
    }
    return true;
}

switch ($action) {
    case 'get_conversations':
        $stmt = $pdo->prepare("
            SELECT DISTINCT c.*,
                   MAX(m.created_at) as last_message_time,
                   GROUP_CONCAT(DISTINCT COALESCE(mem.name, u.full_name)) as participant_names,
                   GROUP_CONCAT(DISTINCT ur.role_key) as participant_roles
            FROM chat_conversations c
            JOIN chat_participants p ON c.id = p.conversation_id
            LEFT JOIN chat_messages m ON c.id = m.conversation_id
            JOIN chat_participants p2 ON c.id = p2.conversation_id
            JOIN users u ON p2.user_id = u.id
            LEFT JOIN members mem ON u.member_id = mem.id
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            WHERE p.user_id = ?
            GROUP BY c.id
            ORDER BY COALESCE(MAX(m.created_at), c.created_at) DESC
        ");
        $stmt->execute([$user_id]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'get_messages':
        $conv_id = $_GET['conversation_id'] ?? 0;
        
        // Verify user is part of the conversation and has permission
        $stmt = $pdo->prepare("
            SELECT GROUP_CONCAT(DISTINCT ur.role_key) as roles
            FROM chat_participants cp
            JOIN users u ON cp.user_id = u.id
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            WHERE cp.conversation_id = ? AND cp.user_id != ?
            GROUP BY cp.user_id
        ");
        $stmt->execute([$conv_id, $user_id]);
        $participantRoles = [];
        while ($row = $stmt->fetch()) {
            $participantRoles = array_merge($participantRoles, explode(',', $row['roles']));
        }
        
        // Check if user has permission to chat with all participants
        if (!canChatWithRoles($userRoles, array_unique($participantRoles))) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }

        // Update last read timestamp
        $stmt = $pdo->prepare("
            UPDATE chat_participants 
            SET last_read_at = NOW() 
            WHERE conversation_id = ? AND user_id = ?
        ");
        $stmt->execute([$conv_id, $user_id]);

        // Get messages with sender information
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   COALESCE(mem.name, u.full_name) as sender_name,
                   GROUP_CONCAT(DISTINCT ur.role_key) as sender_roles
            FROM chat_messages m
            JOIN users u ON m.sender_id = u.id
            LEFT JOIN members mem ON u.member_id = mem.id
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            WHERE m.conversation_id = ?
            GROUP BY m.id
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$conv_id]);
        echo json_encode($stmt->fetchAll());
        break;

    case 'send_message':
        $conv_id = $_POST['conversation_id'] ?? 0;
        $message = $_POST['message'] ?? '';
        
        // Verify user is part of the conversation and has permission
        $stmt = $pdo->prepare("
            SELECT GROUP_CONCAT(DISTINCT ur.role_key) as roles
            FROM chat_participants cp
            JOIN users u ON cp.user_id = u.id
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            WHERE cp.conversation_id = ? AND cp.user_id != ?
            GROUP BY cp.user_id
        ");
        $stmt->execute([$conv_id, $user_id]);
        $participantRoles = [];
        while ($row = $stmt->fetch()) {
            $participantRoles = array_merge($participantRoles, explode(',', $row['roles']));
        }
        
        // Check if user has permission to chat with all participants
        if (!canChatWithRoles($userRoles, array_unique($participantRoles))) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied']);
            exit;
        }

        if (empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Message cannot be empty']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Insert message
            $stmt = $pdo->prepare("
                INSERT INTO chat_messages (conversation_id, sender_id, message)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$conv_id, $user_id, $message]);
            $message_id = $pdo->lastInsertId();

            // Update conversation timestamp
            $stmt = $pdo->prepare("
                UPDATE chat_conversations 
                SET updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$conv_id]);

            $pdo->commit();
            
            // Log the chat message
            $logger->log(
                'chat_message',
                'chat_messages',
                $message_id,
                "Message sent in conversation $conv_id",
                $user_id
            );
            
            echo json_encode(['success' => true, 'message_id' => $message_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to send message']);
        }
        break;

    case 'create_conversation':
        $participant_ids = $_POST['participant_ids'] ?? [];
        if (empty($participant_ids)) {
            http_response_code(400);
            echo json_encode(['error' => 'No participants selected']);
            exit;
        }

        // Verify all participants exist and check role permissions
        $stmt = $pdo->prepare("
            SELECT u.id, GROUP_CONCAT(ur.role_key) as roles
            FROM users u 
            LEFT JOIN user_roles ur ON u.id = ur.user_id
            WHERE u.id IN (" . str_repeat('?,', count($participant_ids) - 1) . "?)
            AND u.status = 'active'
            GROUP BY u.id
        ");
        $stmt->execute($participant_ids);
        $participants = $stmt->fetchAll();

        // Check if user has permission to chat with all participants
        $participantRoles = [];
        foreach ($participants as $participant) {
            $participantRoles = array_merge($participantRoles, explode(',', $participant['roles']));
        }
        
        if (!canChatWithRoles($userRoles, array_unique($participantRoles))) {
            http_response_code(403);
            echo json_encode(['error' => 'You do not have permission to chat with one or more selected users']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Create conversation
            $stmt = $pdo->prepare("
                INSERT INTO chat_conversations (created_at, updated_at)
                VALUES (NOW(), NOW())
            ");
            $stmt->execute();
            $conv_id = $pdo->lastInsertId();

            // Add all participants including the current user
            $stmt = $pdo->prepare("
                INSERT INTO chat_participants (conversation_id, user_id)
                VALUES (?, ?)
            ");
            $stmt->execute([$conv_id, $user_id]);
            foreach ($participant_ids as $participant_id) {
                $stmt->execute([$conv_id, $participant_id]);
            }

            $pdo->commit();
            
            // Log conversation creation
            $logger->log(
                'chat_conversation',
                'chat_conversations',
                $conv_id,
                "Created new conversation",
                $user_id
            );
            
            echo json_encode(['success' => true, 'conversation_id' => $conv_id]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to create conversation']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
}
?>