<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Check session and authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['admin', 'executive_admin_1'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// For admin users, they can manage all branches, so branch_id might not be required
// For other roles, branch_id is required
$isAdmin = ($_SESSION['user_role'] === 'admin');

// Get branch ID from request
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;

// If not admin and no valid branch_id, redirect to dashboard
if (!$isAdmin && !$branch_id) {
    $_SESSION['error'] = "Invalid branch ID!";
    header('Location: dashboard.php');
    exit();
}

// Get branch details if branch_id is provided
$branch = null;
if ($branch_id) {
    $query = "SELECT * FROM branches WHERE id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$branch_id]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$branch) {
        $_SESSION['error'] = "Branch not found!";
        header('Location: dashboard.php');
        exit();
    }
}

// If admin and no branch selected, show branch selection interface
if ($isAdmin && !$branch_id) {
    // Get all branches for admin to select
    $branches_query = "SELECT id, name FROM branches ORDER BY name";
    $stmt = $pdo->query($branches_query);
    $all_branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title>Branch Users - ChurchEaseSuperb</title>
        <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
        <link rel="icon" href="../loginres/images/favicon/favicon.ico" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
       <!-- Fonts and icons -->
    <script src="../res/assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {"families":["Lato:300,400,700,900"]},
            custom: {"families":["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['../res/assets/css/fonts.min.css']},
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>
        <!-- CSS Files -->
        <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
        <link rel="stylesheet" href="../res/assets/css/select2.min.css">
    </head>
    <body>
        <div class="wrapper">
           <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

            <div class="main-panel">
                <div class="content">
                    <div class="page-inner">
                        <div class="page-header">
                            <h4 class="page-title">Select Branch</h4>
                            <ul class="breadcrumbs">
                                <li class="nav-home">
                                    <a href="../index.php">
                                        <i class="flaticon-home"></i>
                                    </a>
                                </li>
                                <li class="separator">
                                    <i class="flaticon-right-arrow"></i>
                                </li>
                                <li class="nav-item">
                                    <a href="dashboard.php">Branches</a>
                                </li>
                                <li class="separator">
                                    <i class="flaticon-right-arrow"></i>
                                </li>
                                <li class="nav-item">
                                    <a href="#">Select Branch</a>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title">Select a Branch to Manage Users</div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table id="branchTable" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Branch Name</th>
                                                        <th>Location</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($all_branches as $branch): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($branch['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($branch['location'] ?? 'N/A'); ?></td>
                                                        <td>
                                                            <a href="branch_users.php?branch_id=<?php echo $branch['id']; ?>" 
                                                               class="btn btn-primary btn-sm">
                                                                <i class="fa fa-users"></i> Manage Users
                                                            </a>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
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

        <!-- Core JS Files -->
        <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
        <script src="../res/assets/js/core/popper.min.js"></script>
        <script src="../res/assets/js/core/bootstrap.min.js"></script>
        <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
        <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
        <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
        <script src="../res/assets/js/atlantis.min.js"></script>

        <script>
            $(document).ready(function() {
                $('#branchTable').DataTable({
                    "pageLength": 25,
                    "order": [[0, "asc"]]
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit();
}

// Handle user assignment/removal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['assign_users']) && !empty($_POST['users'])) {
            $success = true;
            foreach ($_POST['users'] as $user_id) {
                $update_query = "UPDATE users SET branch_id = ? WHERE id = ?";
                $stmt = $pdo->prepare($update_query);
                if (!$stmt->execute([$branch_id, $user_id])) {
                    $success = false;
                    break;
                }
            }
            
            if ($success) {
                $logger->log('branch_user_assign', 'branches', $branch_id, 
                    "Users assigned to branch: " . $branch['name'], $_SESSION['user_id']);
                $_SESSION['success'] = "Users assigned to branch successfully!";
            } else {
                throw new Exception("Error assigning users to branch.");
            }
        } elseif (isset($_POST['remove_user'])) {
            $user_id = intval($_POST['user_id']);
            $update_query = "UPDATE users SET branch_id = NULL WHERE id = ? AND branch_id = ?";
            $stmt = $pdo->prepare($update_query);
            
            if ($stmt->execute([$user_id, $branch_id])) {
                $logger->log('branch_user_remove', 'branches', $branch_id, 
                    "User removed from branch: " . $branch['name'], $_SESSION['user_id']);
                $_SESSION['success'] = "User removed from branch successfully!";
            } else {
                throw new Exception("Error removing user from branch.");
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        $logger->log('branch_user_error', 'branches', $branch_id, 
            "Error in branch user operation: " . $e->getMessage(), $_SESSION['user_id']);
    }
    
    header("Location: branch_users.php?branch_id=" . $branch_id);
    exit();
}

// Get current branch users
$users_query = "SELECT u.*, ur.role_key, r.name as role_name
                FROM users u 
                JOIN user_roles ur ON u.id = ur.user_id
                JOIN roles r ON ur.role_key = r.role_key
                WHERE u.branch_id = ?
                ORDER BY ur.role_key";
$stmt = $pdo->prepare($users_query);
$stmt->execute([$branch_id]);
$current_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available users (not assigned to any branch)
$available_query = "SELECT u.*, ur.role_key, r.name as role_name
                   FROM users u 
                   JOIN user_roles ur ON u.id = ur.user_id
                   JOIN roles r ON ur.role_key = r.role_key
                   WHERE u.branch_id IS NULL 
                   AND ur.role_key IN ('pastor', 'secretary', 'financial', 'member', 'user')
                   ORDER BY ur.role_key";
$stmt = $pdo->prepare($available_query);
$stmt->execute();
$available_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Manage Branch Users - <?php echo htmlspecialchars($branch['name']); ?> - ChurchEaseSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link rel="stylesheet" href="../res/assets/css/select2.min.css">
    <link rel="stylesheet" href="../res/assets/css/dataTables.bootstrap4.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Manage Branch Users</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../index.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="dashboard.php">Branches</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="branch_profile.php?branch_id=<?php echo $branch_id; ?>">
                                    <?php echo htmlspecialchars($branch['name']); ?>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Manage Users</a>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success">
                        <?php 
                        echo $_SESSION['success']; 
                        unset($_SESSION['success']);
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php 
                        echo $_SESSION['error']; 
                        unset($_SESSION['error']);
                        ?>
                    </div>
                    <?php endif; ?>

                    <!-- Branch Info -->
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div>
                                            <h2 class="mb-0"><?php echo htmlspecialchars($branch['name']); ?></h2>
                                            <p class="text-muted mb-0">
                                                <i class="fa fa-map-marker-alt"></i> 
                                                <?php echo htmlspecialchars($branch['location'] ?? 'No location specified'); ?>
                                            </p>
                                        </div>
                                        <div class="ml-auto">
                                            <a href="branch_profile.php?branch_id=<?php echo $branch_id; ?>" class="btn btn-primary btn-sm">
                                                <i class="fa fa-arrow-left"></i> Back to Branch Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Current Users -->
                        <div class="col-xl-8 col-lg-7">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Current Branch Users</div>
                                    <div class="card-tools">
                                        <button type="button" class="btn btn-primary btn-sm" id="addUserButton">
                                            <i class="fa fa-user-plus"></i> Add New User
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="currentUsersTable">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Role</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($current_users)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center">
                                                        No users assigned to this branch
                                                        <button type="button" class="btn btn-primary btn-sm ml-3" onclick="$('#addUserModal').modal('show');">
                                                            <i class="fa fa-user-plus"></i> Add User Now
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach ($current_users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($user['role_name']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <?php if ($user['role_key'] != 'admin'): ?>
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                            <button type="submit" name="remove_user" class="btn btn-link btn-danger btn-sm" 
                                                                    data-toggle="tooltip" title="Remove from branch"
                                                                    onclick="return confirm('Are you sure you want to remove this user from the branch?');">
                                                                <i class="fa fa-user-minus"></i>
                                                            </button>
                                                        </form>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Assign New Users -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Assign New Users</div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($available_users)): ?>
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No available users to assign.
                                    </div>
                                    <?php else: ?>
                                    <form method="POST">
                                        <div class="form-group">
                                            <label for="users">Select Users to Assign</label>
                                            <select class="form-control select2" id="users" name="users[]" multiple>
                                                <?php foreach ($available_users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['full_name']) . ' (' . ucfirst($user['role_name']) . ')'; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <small class="form-text text-muted">You can select multiple users</small>
                                        </div>
                                        <button type="submit" name="assign_users" class="btn btn-primary">
                                            <i class="fa fa-user-plus"></i> Assign Selected Users
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../res/footer.php'; ?>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addUserModalLabel">Add New User to Branch</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm" method="POST" action="process_add_user.php">
                        <input type="hidden" name="branch_id" value="<?php echo $branch_id; ?>">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="username">Username <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email">Email <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password">Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <small class="form-text text-muted">
                                        Minimum 8 characters, must include uppercase, lowercase, 
                                        numbers and special characters (!@#$%^&*)
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="confirm_password">Confirm Password <span class="text-danger">*</span></label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="full_name">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="role">Role <span class="text-danger">*</span></label>
                                    <select class="form-control" id="role" name="role" required>
                                        <option value="">Select Role</option>
                                        <?php
                                        // Get available roles based on user's role
                                        $role_query = "SELECT r.role_key, r.name 
                                                      FROM roles r 
                                                      WHERE r.id > (SELECT id FROM roles WHERE role_key = ?) 
                                                      ORDER BY r.name";
                                        $stmt = $pdo->prepare($role_query);
                                        $stmt->execute([$_SESSION['user_role']]);
                                        $available_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($available_roles as $role): ?>
                                            <option value="<?php echo $role['role_key']; ?>">
                                                <?php echo htmlspecialchars($role['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="phone">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="department">Department</label>
                                    <input type="text" class="form-control" id="department" name="department">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="position">Position</label>
                                    <input type="text" class="form-control" id="position" name="position">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="member_id">Link to Existing Member</label>
                                    <select class="form-control select2" id="member_id" name="member_id">
                                        <option value="">Select Member (Optional)</option>
                                        <?php
                                        // Get members not already linked to users
                                        $members_query = "SELECT m.id, CONCAT(m.first_name, ' ', m.surname) as name, m.contact_number_1 
                                                         FROM members m 
                                                         LEFT JOIN users u ON m.id = u.member_id 
                                                         WHERE u.id IS NULL AND m.branch_id = ?
                                                         ORDER BY m.first_name, m.surname";
                                        $stmt = $pdo->prepare($members_query);
                                        $stmt->execute([$branch_id]);
                                        $available_members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach ($available_members as $member): ?>
                                            <option value="<?php echo $member['id']; ?>">
                                                <?php echo htmlspecialchars($member['name']) . ' (' . $member['contact_number_1'] . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitAddUser">Add User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Process Add User PHP Script -->
    <?php
    // Create process_add_user.php file with the following content
    $process_file = __DIR__ . '/process_add_user.php';
    if (!file_exists($process_file)) {
        $process_content = '<?php
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/AuditLogger.php";

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Check session and authentication
if (!isset($_SESSION["user_id"]) || !isset($_SESSION["user_role"])) {
    header("Location: ../index.php");
    exit();
}

// Check if user has appropriate role
$allowedRoles = ["admin", "executive_admin_1"];
if (!in_array($_SESSION["user_role"], $allowedRoles)) {
    header("Location: /churcheasesuperb/access_denied.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Get and validate form data
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";
        $full_name = trim($_POST["full_name"] ?? "");
        $role = trim($_POST["role"] ?? "");
        $department = trim($_POST["department"] ?? "");
        $position = trim($_POST["position"] ?? "");
        $phone = trim($_POST["phone"] ?? "");
        $branch_id = intval($_POST["branch_id"] ?? 0);
        $member_id = !empty($_POST["member_id"]) ? $_POST["member_id"] : null;

        // Basic validation
        if (empty($username) || empty($email) || empty($full_name) || empty($role) || empty($password)) {
            throw new Exception("All required fields must be filled out.");
        }

        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long.");
        }

        // Password strength validation
        $has_uppercase = preg_match("/[A-Z]/", $password);
        $has_lowercase = preg_match("/[a-z]/", $password);
        $has_number = preg_match("/[0-9]/", $password);
        $has_special = preg_match("/[!@#$%^&*]/", $password);

        if (!$has_uppercase || !$has_lowercase || !$has_number || !$has_special) {
            throw new Exception("Password must include uppercase, lowercase, numbers, and special characters.");
        }

        // Start transaction
        $pdo->beginTransaction();

        try {
            // Check if username or email exists
            $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                if ($existing["username"] === $username) {
                    throw new Exception("This username is already taken.");
                }
                if ($existing["email"] === $email) {
                    throw new Exception("This email is already registered.");
                }
            }

            // Check if member is already linked
            if ($member_id) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE member_id = ?");
                $stmt->execute([$member_id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Selected member is already linked to another user.");
                }
            }

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    username, email, password, full_name,
                    department, position, phone, status, 
                    profile_image, created_by, branch_id, member_id
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, \'active\',
                    NULL, ?, ?, ?
                )
            ");

            $success = $stmt->execute([
                $username, $email, $hashed_password, $full_name,
                $department ?: null, $position ?: null, $phone ?: null,
                $_SESSION["user_id"], $branch_id, $member_id
            ]);

            if (!$success) {
                throw new Exception("Failed to create user.");
            }

            $userId = $pdo->lastInsertId();

            // Assign role
            $stmt = $pdo->prepare("
                INSERT INTO user_roles (
                    user_id, role_key, created_by
                ) VALUES (
                    ?, ?, ?
                )
            ");

            $success = $stmt->execute([
                $userId,
                $role,
                $_SESSION["user_id"]
            ]);

            if (!$success) {
                throw new Exception("Failed to assign role.");
            }

            // Log the action
            $logger->log(
                "user_create", 
                "users", 
                $userId, 
                "Created new user: $full_name with role: $role for branch ID: $branch_id", 
                $_SESSION["user_id"]);

            $pdo->commit();
            $_SESSION["success"] = "User created successfully!";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    } catch (Exception $e) {
        $_SESSION["error"] = $e->getMessage();
    }

    // Redirect back to branch_users.php
    header("Location: branch_users.php?branch_id=" . $branch_id);
    exit();
}

// If not POST request, redirect
header("Location: branch_users.php");
exit();
?>';
        file_put_contents($process_file, $process_content);
    }
    ?>

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    
    <!-- jQuery UI -->
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    
    <!-- jQuery Scrollbar -->
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- DataTables -->
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    
    <!-- Select2 -->
    <script src="../res/assets/js/plugin/select2/select2.full.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#currentUsersTable').DataTable({
                "pageLength": 10,
                "order": [[1, "asc"]],
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                }
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap',
                placeholder: 'Select users to assign',
                width: '100%'
            });
            
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Add User button to open modal
            $('#addUserButton').on('click', function() {
                $('#addUserModal').modal('show');
            });

            // Auto-fill user information when selecting a member
            $('#member_id').on('select2:select', function(e) {
                var data = e.params.data;
                var memberInfo = data.text.match(/(.+) \((.+)\)/);
                if (memberInfo) {
                    $('#full_name').val(memberInfo[1].trim());
                    $('#phone').val(memberInfo[2].trim());
                }
            });

            // Password validation
            $('#password, #confirm_password').on('input', function() {
                validatePassword();
            });

            function validateAddUserForm() {
                // Check required fields
                var requiredFields = ['username', 'email', 'password', 'confirm_password', 'full_name', 'role'];
                var valid = true;
                
                requiredFields.forEach(function(field) {
                    if (!$('#' + field).val()) {
                        $('#' + field).addClass('is-invalid');
                        valid = false;
                    } else {
                        $('#' + field).removeClass('is-invalid');
                    }
                });
                
                // Check password match
                if ($('#password').val() !== $('#confirm_password').val()) {
                    $('#confirm_password').addClass('is-invalid');
                    alert('Passwords do not match');
                    valid = false;
                }
                
                // Check password strength
                if (!validatePassword()) {
                    valid = false;
                }
                
                return valid;
            }
            
            function validatePassword() {
                var password = $('#password').val();
                if (!password) return true; // Skip validation if empty
                
                var hasUpperCase = /[A-Z]/.test(password);
                var hasLowerCase = /[a-z]/.test(password);
                var hasNumbers = /\d/.test(password);
                var hasSpecialChar = /[!@#$%^&*]/.test(password);
                var isLongEnough = password.length >= 8;
                
                var valid = hasUpperCase && hasLowerCase && hasNumbers && hasSpecialChar && isLongEnough;
                
                if (!valid) {
                    $('#password').addClass('is-invalid');
                    return false;
                } else {
                    $('#password').removeClass('is-invalid');
                    return true;
                }
            }
        });
    </script>
</body>
</html>