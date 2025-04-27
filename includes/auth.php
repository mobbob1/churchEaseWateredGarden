<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/SessionManager.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Get user data for the session
$stmt = $pdo->prepare("SELECT u.*, GROUP_CONCAT(ur.role_key) as roles 
                       FROM users u 
                       LEFT JOIN user_roles ur ON u.id = ur.user_id 
                       WHERE u.id = ?
                       GROUP BY u.id");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['status'] !== 'active') {
    // Clear session
    session_unset();
    session_destroy();
    
    header('Location: /churcheasesuperb/index.php?error=invalid_session');
    exit();
}

// Update roles in session if they've changed
$currentRoles = explode(',', $user['roles']);
if ($currentRoles != $_SESSION['all_roles']) {
    $_SESSION['all_roles'] = $currentRoles;
    $_SESSION['user_role'] = $currentRoles[0] ?? 'general';
}

/**
 * Get permissions for a role from config
 * @param string $role The role key
 * @return array Array of permissions for the role
 */
function getRolePermissions($role) {
    global $ROLE_PERMISSIONS;
    if (isset($ROLE_PERMISSIONS[$role])) {
        return $ROLE_PERMISSIONS[$role]['permissions'] ?? [];
    }
    return [];
}

/**
 * Check if a role has a specific permission
 * @param string $role The role key
 * @param string $permission The permission to check
 * @return bool True if the role has the permission
 */
function hasPermission($role, $permission) {
    $permissions = getRolePermissions($role);
    
    // Check for wildcard permission
    if (in_array('*', $permissions)) {
        return true;
    }
    
    return in_array($permission, $permissions);
}

/**
 * Check if the current user has the specified permission
 * @param string $permission The permission to check
 * @return bool True if the user has the permission, false otherwise
 */
function checkPermission($permission) {
    if (!isset($_SESSION['all_roles'])) {
        return false;
    }
    
    // Special handling for seer role
    if (in_array('seer', $_SESSION['all_roles'])) {
        return true;
    }
    
    // Check each role the user has
    foreach ($_SESSION['all_roles'] as $role) {
        if (hasPermission($role, $permission)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Check if a user has any of the specified permissions
 * @param array $permissions Array of permissions to check
 * @return bool True if the user has any of the permissions
 */
function hasAnyPermission($permissions) {
    foreach ($permissions as $permission) {
        if (checkPermission($permission)) {
            return true;
        }
    }
    return false;
}

/**
 * Get all roles for a user
 * @param int $userId The user ID
 * @return array Array of role keys
 */
function getUserRoles($userId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT role_key FROM user_roles WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Require specific permission to access a page
 * @param string $permission The required permission
 */
function requirePermission($permission) {
    if (!checkPermission($permission)) {
        header('Location: /churcheasesuperb/access_denied.php');
        exit();
    }
}

/**
 * Require any of the specified permissions to access a page
 * @param array $permissions Array of permissions, any of which grant access
 */
function requireAnyPermission($permissions) {
    if (!hasAnyPermission($permissions)) {
        header('Location: /churcheasesuperb/access_denied.php');
        exit();
    }
}

// Update last activity
$stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);