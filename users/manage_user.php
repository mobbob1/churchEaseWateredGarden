<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role for user management
$allowedRoles = [
    'admin',           // Super user with full access
    'audit'
];

if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Check specific permissions
$canManageUsers = checkPermission('manage_users');
if (!$canManageUsers) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

$message = '';
$editUser = null;
$logger = new AuditLogger($pdo);

// Fetch available roles based on user's role
try {
    if ($_SESSION['user_role'] === 'admin') {
        // Seer can assign any role
        $stmt = $pdo->query("SELECT role_key, name FROM roles ORDER BY name");
    } else {
        // Other admins can only assign roles with less privileges
        $stmt = $pdo->prepare("SELECT r.role_key, r.name 
                              FROM roles r 
                              WHERE r.id > (SELECT id FROM roles WHERE role_key = ?) 
                              ORDER BY r.name");
        $stmt->execute([$_SESSION['user_role']]);
    }
    $available_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $available_roles = [];
    $message = "<div class='alert alert-danger'>Error loading roles. Please try again later.</div>";
}

// Check if this is an edit request
if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Get user with member information
    $stmt = $pdo->prepare("SELECT u.*, 
                                 CONCAT(m.first_name, ' ', m.surname) as member_name,
                                 m.contact_number_1 as member_phone 
                          FROM users u 
                          LEFT JOIN members m ON u.member_id = m.id 
                          WHERE u.id = ?");
    $stmt->execute([$id]);
    $editUser = $stmt->fetch();
    
    if (!$editUser) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Invalid user.</div>";
        header('Location: view_users.php');
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and validate form data
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $role = trim($_POST['role'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $status = trim($_POST['status'] ?? 'active');
        $member_id = !empty($_POST['member_id']) ? $_POST['member_id'] : null;

        // Basic validation
        if (empty($username) || empty($email) || empty($full_name) || empty($role)) {
            throw new Exception("All required fields must be filled out.");
        }

        if (!isset($_POST['id'])) {
            // New user validation
            if (empty($_POST['password'])) {
                throw new Exception("Password is required for new users.");
            }
            if (strlen($_POST['password']) < 8) {
                throw new Exception("Password must be at least 8 characters long.");
            }

            // Start transaction
            $pdo->beginTransaction();

            try {
                // Check if username or email exists
                $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                $existing = $stmt->fetch();
                
                if ($existing) {
                    if ($existing['username'] === $username) {
                        throw new Exception("This username is already taken.");
                    }
                    if ($existing['email'] === $email) {
                        throw new Exception("This email is already registered.");
                    }
                }

                // If member_id is provided, verify it exists and isn't already linked
                if ($member_id) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE id = ?");
                    $stmt->execute([$member_id]);
                    if ($stmt->fetchColumn() == 0) {
                        throw new Exception("Selected member does not exist.");
                    }

                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE member_id = ? AND id != ?");
                    $stmt->execute([$member_id, isset($_POST['id']) ? (int)$_POST['id'] : 0]);
                    if ($stmt->fetchColumn() > 0) {
                        throw new Exception("Selected member is already linked to another user.");
                    }
                }

                // Insert new user
                $stmt = $pdo->prepare("
                    INSERT INTO users (
                        username, email, password, full_name,
                        department, position, phone, status, 
                        created_by, member_id, password_changed
                    ) VALUES (
                        ?, ?, ?, ?,
                        ?, ?, ?, ?, 
                        ?, ?, 0
                    )
                ");

                $success = $stmt->execute([
                    $username,
                    $email,
                    password_hash($_POST['password'], PASSWORD_DEFAULT),
                    $full_name,
                    $department ?: null,
                    $position ?: null,
                    $phone ?: null,
                    $status,
                    $_SESSION['user_id'],
                    $member_id
                ]);

                if (!$success) {
                    throw new Exception("Failed to create user record.");
                }

                $userId = $pdo->lastInsertId();

                // Insert into user_roles table
                $stmt = $pdo->prepare("
                    INSERT INTO user_roles (
                        user_id, role_key, created_by
                    ) VALUES (
                        ?, ?, ?
                    )
                ");

                $success = $stmt->execute([
                    $userId,
                    $role, // Using the same role as role_key
                    $_SESSION['user_id']
                ]);

                if (!$success) {
                    throw new Exception("Failed to create user role assignment.");
                }
                
                // Log success
                $logger->log('user_create', 'users', $userId, 
                            "Created new user: $full_name with role: $role", 
                            $_SESSION['user_id']);

                $_SESSION['message'] = "<div class='alert alert-success'>User created successfully!</div>";
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }

            // If we get here, commit the transaction
            $pdo->commit();

            // Only redirect after successful commit
            header('Location: view_users.php');
            exit();

        } else {
            // Update existing user
            $pdo->beginTransaction();
            
            try {
                $updateFields = [
                    'email' => $email,
                    'full_name' => $full_name,
                    'department' => $department ?: null,
                    'position' => $position ?: null,
                    'phone' => $phone ?: null,
                    'status' => $status,
                    'member_id' => $member_id
                ];

                // Only update role if editing another user and has permission
                if ($_POST['id'] != $_SESSION['user_id']) {
                    // Get current user's role
                    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
                    $stmt->execute([$_POST['id']]);
                    $currentRole = $stmt->fetchColumn();

                    // Check if role is being changed and if user has permission
                    if ($currentRole !== $role && !canAssignRole($_SESSION['user_role'], $role)) {
                        throw new Exception("You do not have permission to change this user's role.");
                    }
                    
                    $updateFields['role'] = $role;

                    // Update user_roles table
                    // First delete existing role
                    $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
                    $stmt->execute([$_POST['id']]);

                    // Then insert new role
                    $stmt = $pdo->prepare("
                        INSERT INTO user_roles (
                            user_id, role_key, created_by
                        ) VALUES (
                            ?, ?, ?
                        )
                    ");
                    $stmt->execute([
                        $_POST['id'],
                        $role,
                        $_SESSION['user_id']
                    ]);
                }

                // Update user details
                $sql = "UPDATE users SET " . 
                       implode(", ", array_map(fn($field) => "$field = ?", array_keys($updateFields))) .
                       " WHERE id = ?";
                
                $stmt = $pdo->prepare($sql);
                $values = array_values($updateFields);
                $values[] = $_POST['id'];
                
                if (!$stmt->execute($values)) {
                    throw new Exception("Failed to update user details.");
                }

                $pdo->commit();
                $_SESSION['message'] = "<div class='alert alert-success'>User updated successfully!</div>";
                header('Location: view_users.php');
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
        }
        
    } catch (PDOException $e) {
        error_log("PDO Error: " . $e->getMessage());
        error_log("Error Code: " . $e->getCode());
        error_log("SQL State: " . $e->errorInfo[0]);
        
        if ($e->getCode() == 23000) {
            if (strpos($e->getMessage(), 'username') !== false) {
                $message = "<div class='alert alert-danger'>Username already exists.</div>";
            } else if (strpos($e->getMessage(), 'email') !== false) {
                $message = "<div class='alert alert-danger'>Email already exists.</div>";
            } else {
                $message = "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            $message = "<div class='alert alert-danger'>Database error occurred. Please try again.</div>";
        }
        $logger->log('user_error', 'users', null, $e->getMessage(), $_SESSION['user_id'], null, 'failed');
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $message = "<div class='alert alert-danger'>" . htmlspecialchars($e->getMessage()) . "</div>";
        $logger->log('user_error', 'users', null, $e->getMessage(), $_SESSION['user_id'], null, 'failed');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Manage Users - ChurchEaseSuperb</title>
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
    
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title"><?php echo isset($editUser) ? 'Edit' : 'Add'; ?> User</h4>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <?php echo isset($editUser) ? 'Edit User Details' : 'Add New User'; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if($message) echo $message; ?>
                                    
                                    <form method="POST" enctype="multipart/form-data">
                                        <?php if(isset($editUser)): ?>
                                            <input type="hidden" name="id" value="<?php echo $editUser['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Username</label>
                                                    <input type="text" name="username" class="form-control" 
                                                           value="<?php echo isset($editUser) ? $editUser['username'] : ''; ?>"
                                                           <?php echo isset($editUser) ? 'readonly' : 'required'; ?>>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" name="email" class="form-control" required
                                                           value="<?php echo isset($editUser) ? $editUser['email'] : ''; ?>">
                                                </div>
                                            </div>
                                            
                                            <!-- Member Selection -->
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Link to Member</label>
                                                    <select name="member_id" class="form-control select2" style="width: 100%">
                                                        <option value="">Select Member (Optional)</option>
                                                        <?php
                                                        // Check if 'name' column exists in members table
                                                        $checkColumnStmt = $pdo->query("SHOW COLUMNS FROM members LIKE 'name'");
                                                        $nameColumnExists = $checkColumnStmt->rowCount() > 0;
                                                        
                                                        if ($nameColumnExists) {
                                                            // Use the name column if it exists
                                                            $memberStmt = $pdo->query("SELECT id, name, contact_number_1 
                                                                                  FROM members 
                                                                                  WHERE id NOT IN (
                                                                                      SELECT member_id 
                                                                                      FROM users 
                                                                                      WHERE member_id IS NOT NULL" . 
                                                                                      (isset($editUser) ? 
                                                                                       " AND id != " . $editUser['id'] : "") . 
                                                                                  ") ORDER BY name");
                                                        } else {
                                                            // Concatenate first_name and surname if name column doesn't exist
                                                            $memberStmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', surname) as name, contact_number_1 
                                                                                  FROM members 
                                                                                  WHERE id NOT IN (
                                                                                      SELECT member_id 
                                                                                      FROM users 
                                                                                      WHERE member_id IS NOT NULL" . 
                                                                                      (isset($editUser) ? 
                                                                                       " AND id != " . $editUser['id'] : "") . 
                                                                                  ") ORDER BY first_name, surname");
                                                        }
                                                        
                                                        while ($member = $memberStmt->fetch()) {
                                                            $selected = (isset($editUser) && 
                                                                       $editUser['member_id'] == $member['id']) ? 
                                                                       'selected' : '';
                                                            echo "<option value='{$member['id']}' {$selected}>" . 
                                                                 htmlspecialchars($member['name'] . ' ' . 
                                                                  ' (' . $member['contact_number_1'] . ')') . 
                                                                 "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        Link this user account to an existing member
                                                    </small>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Full Name</label>
                                                    <input type="text" name="full_name" class="form-control" required
                                                           value="<?php echo isset($editUser) ? 
                                                                 $editUser['full_name'] : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Phone</label>
                                                    <input type="tel" name="phone" class="form-control"
                                                           value="<?php echo isset($editUser) ? 
                                                                 $editUser['phone'] : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Department</label>
                                                    <input type="text" name="department" class="form-control"
                                                           value="<?php echo isset($editUser) ? 
                                                                 $editUser['department'] : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Position</label>
                                                    <input type="text" name="position" class="form-control"
                                                           value="<?php echo isset($editUser) ? 
                                                                 $editUser['position'] : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Role</label>
                                                    <select name="role" class="form-control" required>
                                                        <?php if (!empty($available_roles)): ?>
                                                            <?php foreach ($available_roles as $role): ?>
                                                                <option value="<?php echo htmlspecialchars($role['role_key']); ?>"
                                                                    <?php echo (isset($editUser) && $editUser['role'] === $role['role_key']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($role['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <option value="">No roles available</option>
                                                        <?php endif; ?>
                                                    </select>
                                                    <?php if (empty($available_roles)): ?>
                                                        <small class="text-danger">Contact an administrator if you need to assign roles.</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control" required>
                                                        <option value="active" <?php echo (isset($editUser) && 
                                                                $editUser['status'] === 'active') ? 'selected' : ''; ?>>
                                                            Active
                                                        </option>
                                                        <option value="inactive" <?php echo (isset($editUser) && 
                                                                $editUser['status'] === 'inactive') ? 'selected' : ''; ?>>
                                                            Inactive
                                                        </option>
                                                        <option value="suspended" <?php echo (isset($editUser) && 
                                                                $editUser['status'] === 'suspended') ? 'selected' : ''; ?>>
                                                            Suspended
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>
                                                        Password 
                                                        <?php echo isset($editUser) ? '(Leave blank to keep current)' : ''; ?>
                                                    </label>
                                                    <input type="password" name="password" class="form-control"
                                                           <?php echo isset($editUser) ? '' : 'required'; ?>>
                                                    <small class="form-text text-muted">
                                                        Minimum 8 characters, must include uppercase, lowercase, 
                                                        numbers and special characters (!@#$%^&*) (only for new users)
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-save"></i> 
                                                <?php echo isset($editUser) ? 'Update' : 'Create'; ?> User
                                            </button>
                                            <a href="view_users.php" class="btn btn-danger">
                                                <i class="fa fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
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
    <!--   Core JS Files   -->
        <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
        <script src="../res/assets/js/core/popper.min.js"></script>
        <script src="../res/assets/js/core/bootstrap.min.js"></script>
        <!-- jQuery UI -->
        <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
        <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
        <!-- jQuery Scrollbar -->
        <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
        <!-- Atlantis JS -->
        <script src="../res/assets/js/atlantis.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: "Select Member (Optional)",
                allowClear: true
            });

            // Auto-fill user information when selecting a member
            $('.select2').on('select2:select', function(e) {
                var data = e.params.data;
                var memberInfo = data.text.match(/(.+) \((.+)\)/);
                if (memberInfo) {
                    $('input[name="full_name"]').val(memberInfo[1].trim());
                    $('input[name="phone"]').val(memberInfo[2].trim());
                }
            });

            // Password strength validation
            $('input[name="password"]').on('input', function(e) {
                const password = e.target.value;
                if (password) {
                    const hasUpperCase = /[A-Z]/.test(password);
                    const hasLowerCase = /[a-z]/.test(password);
                    const hasNumbers = /\d/.test(password);
                    const hasSpecialChar = /[!@#$%^&*]/.test(password);
                    const isLongEnough = password.length >= 8;
                    
                    if (!hasUpperCase || !hasLowerCase || !hasNumbers || 
                        !hasSpecialChar || !isLongEnough) {
                        e.target.setCustomValidity(
                            'Password must contain at least 8 characters, including uppercase, ' +
                            'lowercase, numbers, and special characters (!@#$%^&*)'
                        );
                    } else {
                        e.target.setCustomValidity('');
                    }
                } else {
                    e.target.setCustomValidity('');
                }
            });
        });
    </script>
</body>
</html>