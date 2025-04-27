<?php
session_start();

// Function to handle the redirect
function redirectToIndex() {
    // Clear all session variables
    $_SESSION = array();

    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time()-3600, '/');
    }

    // Destroy the session
    session_destroy();

    // Redirect to index page
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Handle the redirect when button is clicked
if (isset($_POST['go_back'])) {
    redirectToIndex();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - ChurchEase</title>
    <link rel="stylesheet" href="/outpouringcrm/res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/outpouringcrm/res/assets/css/atlantis.min.css">
    <style>
        .access-denied {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .access-denied-content {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        .access-denied-icon {
            font-size: 4rem;
            color: #f25961;
            margin-bottom: 1rem;
        }
        .access-denied h1 {
            color: #1a2035;
            margin-bottom: 1rem;
        }
        .access-denied p {
            color: #575962;
            margin-bottom: 2rem;
        }
        .btn-go-back {
            background: #1572E8;
            color: white;
            padding: 0.5rem 2rem;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-go-back:hover {
            background: #1a2035;
            color: white;
        }
    </style>
</head>
<body>
    <div class="access-denied">
        <div class="access-denied-content">
            <div class="access-denied-icon">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <h1>Access Denied</h1>
            <p>You do not have permission to access this page.<br>
               If you believe this is an error, please contact your system administrator.</p>
            <!-- Changed to form submission for proper session handling -->
            <form method="POST">
                <button type="submit" name="go_back" class="btn btn-go-back">
                    <i class="fas fa-arrow-left mr-2"></i>Go Back
                </button>
            </form>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="/churcheasesuperb/res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="/churcheasesuperb/res/assets/js/core/popper.min.js"></script>
    <script src="/churcheasesuperb/res/assets/js/core/bootstrap.min.js"></script>
    <script src="/churcheasesuperb/res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html>