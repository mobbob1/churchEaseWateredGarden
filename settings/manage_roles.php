<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['seer', 'executive_admin_1'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}


// Handle role assignments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $role_key = $_POST['role_key'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($user_id && $role_key && $action) {
        try {
            if ($action === 'assign') {
                // Check if user already has this role
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ? AND role_key = ?");
                $stmt->execute([$user_id, $role_key]);
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role_key) VALUES (?, ?)");
                    $stmt->execute([$user_id, $role_key]);
                    $message = "<div class='alert alert-success'>Role assigned successfully!</div>";
                } else {
                    $message = "<div class='alert alert-warning'>User already has this role.</div>";
                }
            } else if ($action === 'remove') {
                // Don't allow removing the last role
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_roles WHERE user_id = ?");
                $stmt->execute([$user_id]);
                if ($stmt->fetchColumn() > 1) {
                    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ? AND role_key = ?");
                    $stmt->execute([$user_id, $role_key]);
                    $message = "<div class='alert alert-success'>Role removed successfully!</div>";
                } else {
                    $message = "<div class='alert alert-danger'>Cannot remove the last role from a user.</div>";
                }
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Error updating role: " . $e->getMessage() . "</div>";
        }
    }
}

// Fetch all users and their roles with detailed information
$stmt = $pdo->query("
    SELECT 
        u.id, 
        u.username, 
        u.email, 
        u.status,
        GROUP_CONCAT(DISTINCT r.name) as role_names,
        GROUP_CONCAT(DISTINCT r.role_key) as role_keys
    FROM users u
    LEFT JOIN user_roles ur ON u.id = ur.user_id
    LEFT JOIN roles r ON ur.role_key = r.role_key
    GROUP BY u.id
    ORDER BY u.username
");
$users = $stmt->fetchAll();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Roles - ChurchEase</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>
    
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
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Role Management</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../home/dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Settings</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Role Management</a>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if (isset($message)) echo $message; ?>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">User Roles</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Status</th>
                                                    <th>Current Roles</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($user['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php 
                                                        $roleNames = explode(',', $user['role_names'] ?? '');
                                                        foreach ($roleNames as $roleName): 
                                                        ?>
                                                            <span class="badge badge-info"><?php echo htmlspecialchars($roleName); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-primary btn-sm" 
                                                                data-toggle="modal" 
                                                                data-target="#editRoleModal" 
                                                                data-userid="<?php echo $user['id']; ?>"
                                                                data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                                data-roles="<?php echo htmlspecialchars($user['role_keys'] ?? ''); ?>">
                                                            Edit Roles
                                                        </button>
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
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User Roles</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="roleForm" method="POST">
                        <input type="hidden" name="user_id" id="modalUserId">
                        <div class="form-group">
                            <label>Available Roles</label>
                            <select name="role_key" class="form-control">
                                <?php foreach ($roles as $role): ?>
                                <option value="<?php echo htmlspecialchars($role['role_key']); ?>">
                                    <?php echo htmlspecialchars($role['name']); ?> - <?php echo htmlspecialchars($role['description']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Current Roles</label>
                            <div id="currentRoles"></div>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="action" value="assign" class="btn btn-success">
                                <i class="fa fa-plus"></i> Assign Role
                            </button>
                            <button type="submit" name="action" value="remove" class="btn btn-danger">
                                <i class="fa fa-minus"></i> Remove Role
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $('#editRoleModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget);
            var userId = button.data('userid');
            var username = button.data('username');
            var roles = button.data('roles').split(',');
            var modal = $(this);
            
            modal.find('#modalUserId').val(userId);
            modal.find('.modal-title').text('Edit Roles - ' + username);
            
            // Update current roles display
            var currentRolesHtml = '';
            roles.forEach(function(role) {
                if (role) {
                    currentRolesHtml += '<span class="badge badge-info mr-1">' + role + '</span>';
                }
            });
            modal.find('#currentRoles').html(currentRolesHtml || '<em>No roles assigned</em>');
            
            // Disable roles in dropdown that are already assigned
            modal.find('select[name="role_key"] option').each(function() {
                $(this).prop('disabled', roles.includes($(this).val()));
            });
        });
        
        $('#editRoleModal').on('hidden.bs.modal', function () {
            // Re-enable all options when modal is closed
            $(this).find('select[name="role_key"] option').prop('disabled', false);
        });
    </script>
</body>
</html>