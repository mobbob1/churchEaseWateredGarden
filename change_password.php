<?php
// Start the session at the very beginning
session_start();

require_once 'config.php';
require_once 'includes/SessionManager.php';
require_once 'includes/AuditLogger.php';

$logger = new AuditLogger($pdo);

// Debug logging
error_log("Session variables: " . print_r($_SESSION, true));

// Check if user is required to change password
if (!isset($_SESSION['require_password_change']) || !isset($_SESSION['temp_user_id'])) {
    error_log("Missing required session variables. require_password_change: " . 
              (isset($_SESSION['require_password_change']) ? 'set' : 'not set') . 
              ", temp_user_id: " . (isset($_SESSION['temp_user_id']) ? 'set' : 'not set'));
    header('Location: index.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($new_password) < 8) {
        $message = "<div class='alert alert-danger'>Password must be at least 8 characters long.</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger'>Passwords do not match.</div>";
    } else {
        try {
            // Update password and set password_changed flag
            $stmt = $pdo->prepare("UPDATE users SET password = ?, password_changed = 1 WHERE id = ?");
            $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $_SESSION['temp_user_id']]);
            
            // Log password change
            $logger->log(
                'password_change',
                'authentication',
                $_SESSION['temp_user_id'],
                "First-time password change completed",
                null,
                null,
                'success'
            );
            
            // Clear temporary session variables
            unset($_SESSION['require_password_change']);
            unset($_SESSION['temp_user_id']);
            
            // Redirect to login
            header('Location: index.php?msg=password_changed');
            exit();
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Error updating password. Please try again.</div>";
            error_log($e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - OutpouringCRM</title>
    <link rel="stylesheet" href="res/assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .password-change-container {
            margin-top: 5%;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .password-requirements {
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container password-change-container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Change Your Password</h3>
                        <p class="text-center text-muted">You must change your password before continuing</p>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <form method="POST" action="" id="passwordForm">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                <small class="password-requirements">
                                    Password must be at least 8 characters long
                                </small>
                            </div>
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="res/assets/js/jquery.min.js"></script>
    <script src="res/assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Real-time password validation
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            var password = document.getElementById('new_password').value;
            var confirm = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return false;
            }
            
            if (password !== confirm) {
                e.preventDefault();
                alert('Passwords do not match');
                return false;
            }
        });
    </script>
</body>
</html>