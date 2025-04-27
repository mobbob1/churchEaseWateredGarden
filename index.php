<?php
require_once 'config.php';
require_once 'includes/SessionManager.php';
require_once 'includes/AuditLogger.php';

$sessionManager = new SessionManager($pdo);
$logger = new AuditLogger($pdo);

// Initialize message variable
$message = '';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: home/dashboard.php');
    exit();
}

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    if (empty($username) || empty($password)) {
        $message = "<div class='alert alert-danger'>Please enter both username and password.</div>";
    } else {
        try {
            // Get user details
            $stmt = $pdo->prepare("
                SELECT u.*, GROUP_CONCAT(ur.role_key) as roles 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                WHERE u.username = ?
                GROUP BY u.id
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            // Log attempt details for audit
            $attemptDetails = [
                'username' => $username,
                'ip_address' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'timestamp' => date('Y-m-d H:i:s'),
                'user_exists' => $user ? 'yes' : 'no',
                'user_status' => $user ? $user['status'] : 'n/a',
                'user_role' => $user ? $user['role'] : 'n/a'
            ];

            // Log the login attempt
            $logger->log(
                'login_attempt',
                'authentication',
                $user ? $user['id'] : null,
                "Login attempt for username: {$username}",
                null,
                $attemptDetails,
                'info'
            );

            if ($user) {
                $passwordVerified = password_verify($password, $user['password']);
                
                // Log password verification
                $logger->log(
                    'password_verify',
                    'authentication',
                    $user['id'],
                    "Password verification for user: {$username}",
                    null,
                    ['password_verified' => $passwordVerified ? 'yes' : 'no'],
                    $passwordVerified ? 'success' : 'failed'
                );

                if ($user['status'] === 'active' && $passwordVerified) {
                    // Reset login attempts
                    $stmt = $pdo->prepare("UPDATE users SET login_attempts = 0, last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // Handle password change requirement
                    if ($user['password_changed'] == 0) {
                        session_start();
                        session_unset();
                        $_SESSION['temp_user_id'] = $user['id'];
                        $_SESSION['require_password_change'] = true;
                        $_SESSION['original_role'] = $user['role'];
                        
                        header("Location: change_password.php");
                        exit();
                    }

                    // Get user roles
                    $roles = explode(',', $user['roles'] ?? 'general');

                    // Create session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $roles[0] ?? 'general';
                    $_SESSION['all_roles'] = $roles;
                    $_SESSION['full_name'] = $user['full_name'];

                    // Log successful login
                    $logger->log(
                        'login_success',
                        'authentication',
                        $user['id'],
                        "Successful login for user: {$username}",
                        null,
                        ['roles' => $roles],
                        'success'
                    );

                    // Role-based redirect
                    switch($_SESSION['user_role']) {
                        case 'admin':
                        case 'executive_admin_1':
                        case 'executive_admin_2':
                            header('Location: home/dashboard.php');
                            break;
                        case 'pastorate':
                            header('Location: membership/pastor_dashboard.php');
                            break;
                        case 'finance':
                            header('Location: financial/dashboard.php');
                            break;
                        case 'hr':
                            header('Location: events/dashboard.php');
                            break;
                        case 'comms':
                            header('Location: communication/dashboard.php');
                            break;
                        case 'audit':
                            header('Location: audit/dashboard.php');
                            break;
                        case 'reports':
                            header('Location: reports/dashboard.php');
                            break;
                        case 'admin_assistant':
                            header('Location: membership/secretary_dashboard.php');
                            break;
                        case 'general':
                            header('Location: members/dashboard.php');
                            break;
                        default:
                            header('Location: access_denied.php');
                    }
                    exit();
                } else {
                    $errorMessage = $user['status'] !== 'active' ? 
                        "Account is not active" : "Invalid credentials";

                    // Increment login attempts
                    $stmt = $pdo->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE id = ?");
                    $stmt->execute([$user['id']]);

                    // Check for account suspension
                    if ($user['login_attempts'] >= 4) {
                        $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        $errorMessage = "Account has been suspended due to multiple failed attempts.";
                        
                        $logger->log(
                            'account_suspended',
                            'authentication',
                            $user['id'],
                            "Account suspended due to multiple failed attempts",
                            ['status' => 'active'],
                            ['status' => 'suspended'],
                            'warning'
                        );
                    }
                    $message = "<div class='alert alert-danger'>{$errorMessage}</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>Invalid credentials</div>";
            }
        } catch (PDOException $e) {
            $logger->log(
                'login',
                'authentication',
                null,
                "System error during login attempt",
                null,
                $attemptDetails,
                'failed',
                $e->getMessage()
            );
            $message = "<div class='alert alert-danger'>System error. Please try again later.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Watered Garden Church | ChurchEase</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="loginres/css/style.css">
    <link rel="apple-touch-icon" sizes="180x180" href="loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="loginres/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="loginres/images/favicon/site.webmanifest">
</head>
<body>
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 text-center mb-5">
                    <h2 class="heading-section">Watered Garden Church</h2>
                </div>
            </div>
            <?php if($message) echo $message; ?>
            
            <div class="row justify-content-center">
                <div class="col-md-12 col-lg-10">
                    <div class="wrap d-md-flex">
                        <div class="img" style="background-image: url(loginres/images/wateredgardenchurch.jpg);">
                        </div>
                        <div class="login-wrap p-4 p-md-5">
                            <div class="d-flex">
                                <div class="w-100">
                                    <h3 class="mb-4">Sign In</h3>
                                </div>
                                <div class="w-100">
                                    <p class="social-media d-flex justify-content-end">
                                        <a href="#" class="social-icon d-flex align-items-center justify-content-center"><span class="fa fa-facebook"></span></a>
                                        <a href="#" class="social-icon d-flex align-items-center justify-content-center"><span class="fa fa-twitter"></span></a>
                                    </p>
                                </div>
                            </div>
                            <form method="POST" id="loginForm" class="signin-form">
                                <div class="form-group mb-3">
                                    <label class="label" for="username">Username</label>
                                    <input type="text" class="form-control" name="username" id="username" 
                                           placeholder="Username" required 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                                </div>
                                <div class="form-group mb-3">
                                    <label class="label" for="password">Password</label>
                                    <input type="password" class="form-control" name="password" id="password" 
                                           placeholder="Password" required>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="form-control btn btn-primary rounded submit px-3">Sign In</button>
                                </div>
                                <div class="form-group d-md-flex">
                                    <div class="w-50 text-left">
                                        <label class="checkbox-wrap checkbox-primary mb-0">Remember Me
                                            <input type="checkbox" name="remember" checked>
                                            <span class="checkmark"></span>
                                        </label>
                                    </div>
                                    <div class="w-50 text-md-right">
                                        <a href="forgot_password.php">Forgot Password</a>
                                    </div>
                                </div>
                            </form>
                            <p class="text-center">Not a member? <a href="register.php" target="_blank">Sign Up</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="loginres/js/jquery.min.js"></script>
    <script src="loginres/js/popper.js"></script>
    <script src="loginres/js/bootstrap.min.js"></script>
    <script src="loginres/js/main.js"></script>
</body>
</html>