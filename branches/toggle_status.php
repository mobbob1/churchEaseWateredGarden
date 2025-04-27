<?php
require_once '../config.php';
require_once '../includes/auth.php';

// Set response header to JSON
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Check if user has appropriate role
    $user_id = $_SESSION['user_id'];
    $access_query = "
        SELECT COUNT(*) as has_access 
        FROM user_roles ur
        WHERE ur.user_id = :user_id 
        AND ur.role_key IN ('seer', 'executive_admin_1')";

    $stmt = $pdo->prepare($access_query);
    $stmt->execute(['user_id' => $user_id]);
    $access = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$access['has_access']) {
        throw new Exception("You don't have permission to perform this action.");
    }

    // Validate input parameters
    if (!isset($_POST['branch_id']) || !isset($_POST['new_status'])) {
        throw new Exception("Missing required parameters.");
    }

    $branch_id = intval($_POST['branch_id']);
    $new_status = $_POST['new_status'];

    // Validate status value
    if (!in_array($new_status, ['active', 'inactive'])) {
        throw new Exception("Invalid status value.");
    }

    // Check if branch exists
    $check_query = "SELECT id, name FROM branches WHERE id = :branch_id";
    $stmt = $pdo->prepare($check_query);
    $stmt->execute(['branch_id' => $branch_id]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$branch) {
        throw new Exception("Branch not found.");
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Update branch status
    $update_query = "
        UPDATE branches 
        SET status = :status,
            updated_at = NOW(),
            updated_by = :user_id
        WHERE id = :branch_id";

    $stmt = $pdo->prepare($update_query);
    $stmt->execute([
        'status' => $new_status,
        'user_id' => $user_id,
        'branch_id' => $branch_id
    ]);

    // Log the action
    $log_query = "
        INSERT INTO audit_logs (
            user_id, action, module, record_id,
            description, ip_address, user_agent, status
        ) VALUES (
            :user_id, 'status_update', 'branch', :branch_id,
            :description, :ip_address, :user_agent, 'success'
        )";

    $description = sprintf(
        "Changed branch '%s' status to %s",
        $branch['name'],
        $new_status
    );

    $stmt = $pdo->prepare($log_query);
    $stmt->execute([
        'user_id' => $user_id,
        'branch_id' => $branch_id,
        'description' => $description,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);

    // Commit transaction
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = "Branch status updated successfully.";

} catch (Exception $e) {
    // Rollback transaction if active
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // Log error
    $log_query = "
        INSERT INTO audit_logs (
            user_id, action, module, record_id,
            description, ip_address, user_agent, status,
            error_message
        ) VALUES (
            :user_id, 'status_update', 'branch', :branch_id,
            'Failed to update branch status', :ip_address,
            :user_agent, 'failed', :error
        )";

    $stmt = $pdo->prepare($log_query);
    $stmt->execute([
        'user_id' => $user_id,
        'branch_id' => $branch_id ?? null,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'error' => $e->getMessage()
    ]);

    $response['message'] = $e->getMessage();
}

// Send JSON response
echo json_encode($response);