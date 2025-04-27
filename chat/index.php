<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Session check
if (!isset($_SESSION['user_id'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

$sessionManager = new SessionManager($pdo);
$logger = new AuditLogger($pdo);

// Get user details and roles
$stmt = $pdo->prepare("
    SELECT u.*, GROUP_CONCAT(ur.role_key) as roles 
    FROM users u 
    LEFT JOIN user_roles ur ON u.id = ur.user_id 
    WHERE u.id = ? 
    GROUP BY u.id
");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser || $currentUser['status'] !== 'active') {
    header('Location: ../index.php');
    exit();
}

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
$userRoles = explode(',', $currentUser['roles']);
$allowedRoles = [];
foreach ($userRoles as $role) {
    if (isset($roleHierarchy[$role])) {
        $allowedRoles = array_merge($allowedRoles, $roleHierarchy[$role]);
    }
}
$allowedRoles = array_unique($allowedRoles);

// Get available users for chat based on role hierarchy
$placeholders = str_repeat('?,', count($allowedRoles) - 1) . '?';
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, COALESCE(m.name, u.full_name) as name, GROUP_CONCAT(ur.role_key) as roles
    FROM users u 
    LEFT JOIN members m ON u.member_id = m.id
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    WHERE u.status = 'active' 
    AND u.id != ?
    AND EXISTS (
        SELECT 1 FROM user_roles ur2 
        WHERE ur2.user_id = u.id 
        AND ur2.role_key IN ($placeholders)
    )
    GROUP BY u.id
    ORDER BY name
");

$params = array_merge([$_SESSION['user_id']], $allowedRoles);
$stmt->execute($params);
$availableUsers = $stmt->fetchAll();

// Log page access
$logger->log(
    'page_access',
    'chat',
    null,
    'Accessed chat interface',
    $_SESSION['user_id']
);

$message = '';
$pageTitle = "Chat Messages";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title"><?php echo $pageTitle; ?></h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Communication</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#"><?php echo $pageTitle; ?></a>
                            </li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title"><?php echo $pageTitle; ?></h4>
                                        <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#newConversationModal">
                                            <i class="fa fa-plus"></i>
                                            New Chat
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <?php if($message) echo $message; ?>
                                    <div class="chat-container">
                                        <!-- Conversations List -->
                                        <div class="conversations-list">
                                            <div class="conversations-header">
                                                <h5>Conversations</h5>
                                            </div>
                                            <div class="conversations-body" id="conversations-list">
                                                <!-- Conversations will be loaded here -->
                                            </div>
                                        </div>

                                        <!-- Chat Messages -->
                                        <div class="chat-messages">
                                            <div class="chat-header">
                                                <h5 id="chat-title">Select a conversation</h5>
                                            </div>
                                            <div class="chat-body" id="chat-messages">
                                                <!-- Messages will be loaded here -->
                                                <div class="text-center p-4">
                                                    <div class="empty-img">
                                                        <i class="fas fa-comments fa-3x text-muted"></i>
                                                    </div>
                                                    <p class="mt-3">Select a conversation to start chatting</p>
                                                    <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#newConversationModal">
                                                        <i class="fas fa-plus"></i> Start a New Chat
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="chat-footer">
                                                <form id="message-form">
                                                    <div class="input-group">
                                                        <input type="text" class="form-control" id="message-input" placeholder="Type your message..." disabled>
                                                        <div class="input-group-append">
                                                            <button class="btn btn-primary" type="submit" disabled>
                                                                <i class="fa fa-paper-plane"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Chat History -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Chat History</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Participants</th>
                                                    <th>Last Message</th>
                                                    <th>Status</th>
                                                    <th>Last Activity</th>
                                                    <th>Created</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->prepare("
                                                    SELECT 
                                                        c.id,
                                                        GROUP_CONCAT(DISTINCT COALESCE(m.name, u.full_name)) as participants,
                                                        MAX(cm.message) as last_message,
                                                        MAX(cm.created_at) as last_activity,
                                                        c.created_at
                                                    FROM chat_conversations c
                                                    JOIN chat_participants cp ON c.id = cp.conversation_id
                                                    JOIN users u ON cp.user_id = u.id
                                                    LEFT JOIN members m ON u.member_id = m.id
                                                    LEFT JOIN chat_messages cm ON c.id = cm.conversation_id
                                                    WHERE EXISTS (
                                                        SELECT 1 FROM chat_participants 
                                                        WHERE conversation_id = c.id 
                                                        AND user_id = ?
                                                    )
                                                    GROUP BY c.id
                                                    ORDER BY last_activity DESC, c.created_at DESC
                                                    LIMIT 50
                                                ");
                                                $stmt->execute([$_SESSION['user_id']]);
                                                
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row['participants']) . "</td>";
                                                    echo "<td>" . ($row['last_message'] ? 
                                                         htmlspecialchars(substr($row['last_message'], 0, 50)) . "..." : 
                                                         "<em>No messages</em>") . "</td>";
                                                    echo "<td><span class='badge badge-success'>Active</span></td>";
                                                    echo "<td>" . ($row['last_activity'] ? 
                                                         date('M d, Y H:i', strtotime($row['last_activity'])) : '-') . "</td>";
                                                    echo "<td>" . date('M d, Y H:i', strtotime($row['created_at'])) . "</td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../res/footer.php'; ?>
        </div>
    </div>

    <!-- New Conversation Modal -->
    <div class="modal fade" id="newConversationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">New Conversation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="participant-select">Select Participants</label>
                        <select class="form-control" id="participant-select" multiple>
                            <?php foreach ($availableUsers as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['name']); ?> 
                                    (<?php echo str_replace(',', ', ', $user['roles']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">
                            You can only chat with users based on your role permissions.
                            <?php if (in_array('seer', $userRoles)): ?>
                                As a Seer, you can chat with all users.
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="create-conversation">Create</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <!-- Custom JS -->
    <script>
        // Pass PHP variables to JavaScript
        const CURRENT_USER_ID = <?php echo json_encode($_SESSION['user_id']); ?>;
        const CURRENT_USER_NAME = <?php echo json_encode($currentUser['full_name']); ?>;
        const USER_ROLES = <?php echo json_encode($userRoles); ?>;
        const ALLOWED_ROLES = <?php echo json_encode($allowedRoles); ?>;
    </script>
    <script src="chat.js"></script>
</body>
</html>