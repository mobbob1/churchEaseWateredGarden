<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Fetch user details from database
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT u.*, m.first_name, m.last_name, m.phone, m.address, m.email as member_email 
                       FROM users u 
                       LEFT JOIN members m ON u.member_id = m.id 
                       WHERE u.id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    $update_fields = array();
    $types = "";
    $params = array();
    
    // Check password change
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $update_fields[] = "password = ?";
            $types .= "s";
            $params[] = password_hash($new_password, PASSWORD_DEFAULT);
        } else {
            $error_msg = "Passwords do not match!";
        }
    }
    
    // Update profile if there are changes
    if (!empty($update_fields) && empty($error_msg)) {
        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $types .= "i";
        $params[] = $user_id;
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            $success_msg = "Password updated successfully!";
        } else {
            $error_msg = "Error updating password!";
        }
    }
}

// Check if user has associated member record
$has_member_record = !empty($user_data['member_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Profile - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>
    
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
                        <h4 class="page-title">My Profile</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../dashboard/">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <span>Profile</span>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if (!$has_member_record): ?>
                    <div class="alert alert-warning">
                        <h4 class="alert-heading">Member Profile Not Found</h4>
                        <p>Your user account is not linked to a member profile. Please contact an administrator to complete your profile setup.</p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_msg)): ?>
                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success_msg)): ?>
                    <div class="alert alert-success"><?php echo $success_msg; ?></div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card card-profile">
                                <div class="card-header" style="background-image: url('../res/assets/img/blogpost.jpg')">
                                    <div class="profile-picture">
                                        <div class="avatar avatar-xl">
                                            <img src="../res/assets/img/profile.jpg" alt="..." class="avatar-img rounded-circle">
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="user-profile text-center">
                                        <div class="name">
                                            <?php if ($has_member_record): ?>
                                                <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($user_data['username']); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="job"><?php echo ucfirst(htmlspecialchars($user_data['role'])); ?></div>
                                        <div class="desc">Status: <?php echo ucfirst(htmlspecialchars($user_data['status'])); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Profile Information</div>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Username</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
                                                    <small class="form-text text-muted">Username cannot be changed</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Role</label>
                                                    <input type="text" class="form-control" value="<?php echo ucfirst(htmlspecialchars($user_data['role'])); ?>" readonly>
                                                    <small class="form-text text-muted">Role changes require administrator action</small>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($has_member_record): ?>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>First Name</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['first_name']); ?>" readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Last Name</label>
                                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['last_name']); ?>" readonly>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user_data['member_email']); ?>" readonly>
                                            <small class="form-text text-muted">Email changes must be done through member management</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['phone']); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label>Address</label>
                                            <textarea class="form-control" readonly rows="3"><?php echo htmlspecialchars($user_data['address']); ?></textarea>
                                        </div>
                                        <?php endif; ?>

                                        <hr>
                                        <div class="card-title">Change Password</div>

                                        <div class="form-group">
                                            <label>New Password</label>
                                            <input type="password" class="form-control" name="new_password">
                                            <small class="form-text text-muted">Leave blank to keep current password</small>
                                        </div>

                                        <div class="form-group">
                                            <label>Confirm New Password</label>
                                            <input type="password" class="form-control" name="confirm_password">
                                        </div>

                                        <button type="submit" class="btn btn-primary">Update Password</button>
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
    
    <!--   Core JS Files   -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>