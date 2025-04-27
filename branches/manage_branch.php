<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check role-based access
$user_id = $_SESSION['user_id'];
$access_query = "
    SELECT 
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM user_roles ur
                WHERE ur.user_id = :user_id 
                AND ur.role_key = 'admin'
            ) THEN 'super_admin'
            WHEN EXISTS (
                SELECT 1 FROM user_roles ur
                WHERE ur.user_id = :user_id 
                AND ur.role_key = 'executive_admin_1'
            ) THEN 'head_office'
            ELSE 'no_access'
        END as access_level";

$stmt = $pdo->prepare($access_query);
$stmt->execute(['user_id' => $user_id]);
$access = $stmt->fetch(PDO::FETCH_ASSOC);

if ($access['access_level'] === 'no_access') {
    $_SESSION['error'] = "You don't have permission to manage branches.";
    header("Location: ../index.php");
    exit();
}

// Set permissions based on role level
$can_create_branch = ($access['access_level'] === 'super_admin');
$can_edit_branch = ($access['access_level'] === 'super_admin' || $access['access_level'] === 'head_office');
$can_toggle_status = ($access['access_level'] === 'super_admin' || $access['access_level'] === 'head_office');

// Check permissions based on role hierarchy
$userRole = $_SESSION['user_role'] ?? '';
$canManageBranches = checkPermission('manage_branches');
$canAddBranches = checkPermission('add_branches');
$canViewBranches = checkPermission('view_branches');

if (!$canManageBranches && !$canAddBranches && !$canViewBranches) {
    header('Location: ../access_denied.php');
    exit();
}

// Set page mode based on permissions
$readOnly = !$canManageBranches && !$canAddBranches;

// Get branch data if editing
$branch = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT b.*, u.full_name as head_pastor_name 
        FROM branches b 
        LEFT JOIN users u ON b.head_pastor_id = u.id 
        WHERE b.id = ?");
    $stmt->execute([$_GET['id']]);
    $branch = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$branch) {
        header('Location: dashboard.php');
        exit();
    }
}

// Get available pastors for head pastor selection
$pastor_query = "
    SELECT DISTINCT u.id, u.full_name 
    FROM users u 
    INNER JOIN user_roles ur ON u.id = ur.user_id 
    WHERE ur.role_key = 'pastor'
    AND NOT EXISTS (
        /* Exclude users who are already head pastors of other branches */
        SELECT 1 FROM branches b 
        WHERE b.head_pastor_id = u.id 
        AND b.status = 'active'
    )
    ORDER BY u.full_name ASC";

$stmt = $pdo->prepare($pastor_query);
$stmt->execute();
$pastors = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $name = $_POST['name'] ?? '';
        $location = $_POST['location'] ?? '';
        $contact_number = $_POST['contact_number'] ?? '';
        $head_pastor_id = $_POST['head_pastor_id'] ?? null;
        $email = $_POST['email'] ?? '';
        $address = $_POST['address'] ?? '';
        $status = $_POST['status'] ?? 'active';

        // Validate required fields
        if (empty($name) || empty($location)) {
            throw new Exception("Name and location are required fields.");
        }

        // Validate email format
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format.");
        }

        // Check if branch name already exists
        $check_name = $pdo->prepare("SELECT id FROM branches WHERE name = ? AND id != ?");
        $check_name->execute([$name, $_POST['id'] ?? 0]);
        if ($check_name->fetch()) {
            throw new Exception("A branch with this name already exists.");
        }

        if (isset($_POST['id'])) {
            // Update existing branch
            $sql = "UPDATE branches SET 
                    name = ?, location = ?, contact_number = ?, 
                    head_pastor_id = ?, email = ?, address = ?,
                    status = ?, updated_at = NOW() 
                    WHERE id = ?";
            $params = [$name, $location, $contact_number, $head_pastor_id, $email, $address, $status, $_POST['id']];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $logger->log('branch_update', 'branches', $_POST['id'], 
                "Updated branch: $name", $_SESSION['user_id'], $params);
            $message = "<div class='alert alert-success'>Branch updated successfully!</div>";
        } else {
            // Create new branch
            $sql = "INSERT INTO branches (
                    name, location, contact_number, head_pastor_id,
                    email, address, status, created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $params = [$name, $location, $contact_number, $head_pastor_id, $email, $address, $status, $_SESSION['user_id']];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $branchId = $pdo->lastInsertId();
            $logger->log('branch_create', 'branches', $branchId, 
                "Created new branch: $name", $_SESSION['user_id'], $params);
            $message = "<div class='alert alert-success'>Branch created successfully!</div>";
        }

        // Redirect to dashboard after successful operation
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        $logger->log('branch_error', 'branches', $_POST['id'] ?? null, 
            "Error in branch operation: " . $e->getMessage(), $_SESSION['user_id']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo isset($branch) ? 'Edit' : 'Add'; ?> Branch - ChurchEase</title>
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
                        <h4 class="page-title"><?php echo isset($branch) ? 'Edit' : 'Add'; ?> Branch</h4>
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
                                <a href="#"><?php echo isset($branch) ? 'Edit' : 'Add'; ?> Branch</a>
                            </li>
                        </ul>
                    </div>

                    <?php if (!empty($message)) echo $message; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title"><?php echo isset($branch) ? 'Edit Branch Details' : 'Add New Branch'; ?></div>
                                </div>
                                <form method="POST">
                                    <?php if (isset($branch)): ?>
                                        <input type="hidden" name="id" value="<?php echo $branch['id']; ?>">
                                    <?php endif; ?>
                                    
                                    <?php if ($readOnly): ?>
                                    <div class="alert alert-info m-3">
                                        <i class="fa fa-info-circle"></i>
                                        You are in view-only mode. Contact an administrator if you need to make changes.
                                    </div>
                                    <?php endif; ?>

                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Branch Name*</label>
                                                    <input type="text" class="form-control" name="name" 
                                                           value="<?php echo isset($branch) ? htmlspecialchars($branch['name']) : ''; ?>" 
                                                           <?php echo $readOnly ? 'readonly' : 'required'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label>Location*</label>
                                                    <input type="text" class="form-control" name="location" 
                                                           value="<?php echo isset($branch) ? htmlspecialchars($branch['location']) : ''; ?>" 
                                                           <?php echo $readOnly ? 'readonly' : 'required'; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label>Contact Number</label>
                                                    <input type="text" class="form-control" name="contact_number" 
                                                           value="<?php echo isset($branch) ? htmlspecialchars($branch['contact_number']) : ''; ?>" 
                                                           <?php echo $readOnly ? 'readonly' : ''; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control" name="email" 
                                                           value="<?php echo isset($branch) ? htmlspecialchars($branch['email']) : ''; ?>" 
                                                           <?php echo $readOnly ? 'readonly' : ''; ?>>
                                                </div>
                                                <div class="form-group">
                                                    <label>Head Pastor</label>
                                                    <select name="head_pastor_id" class="form-control select2" <?php echo $readOnly ? 'disabled' : ''; ?>>
                                                        <option value="">Select Head Pastor</option>
                                                        <?php 
                                                        // If editing, include current head pastor in options
                                                        if (isset($branch) && !empty($branch['head_pastor_id']) && !empty($branch['head_pastor_name'])) {
                                                            echo "<option value='" . $branch['head_pastor_id'] . "' selected>" . 
                                                                htmlspecialchars($branch['head_pastor_name']) . "</option>";
                                                        }
                                                        
                                                        // List available pastors
                                                        foreach ($pastors as $pastor) {
                                                            $selected = (isset($branch) && $branch['head_pastor_id'] == $pastor['id']) ? 'selected' : '';
                                                            echo "<option value='" . $pastor['id'] . "' $selected>" . 
                                                                htmlspecialchars($pastor['full_name']) . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control" <?php echo $readOnly ? 'disabled' : ''; ?>>
                                                        <option value="active" <?php echo (isset($branch) && $branch['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                        <option value="inactive" <?php echo (isset($branch) && $branch['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Address</label>
                                                    <textarea name="address" class="form-control" rows="3"
                                                              <?php echo $readOnly ? 'readonly' : ''; ?>><?php echo isset($branch) ? htmlspecialchars($branch['address']) : ''; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card-action">
                                        <?php if (!$readOnly): ?>
                                        <button type="submit" class="btn btn-success">Submit</button>
                                        <?php endif; ?>
                                        <a href="dashboard.php" class="btn btn-danger">Back</a>
                                    </div>
                                </form>
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
    <script src="../res/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    <script src="../res/assets/js/plugin/select2/select2.full.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap4'
            });

            // Form validation
            $('form').on('submit', function(e) {
                var name = $('input[name="name"]').val().trim();
                var location = $('input[name="location"]').val().trim();
                
                if (!name || !location) {
                    e.preventDefault();
                    swal({
                        title: "Error!",
                        text: "Name and location are required fields",
                        icon: "error",
                        buttons: {
                            confirm: {
                                text: "OK",
                                value: true,
                                visible: true,
                                className: "btn btn-danger",
                                closeModal: true
                            }
                        }
                    });
                    return false;
                }
            });
        });
    </script>
</body>
</html>