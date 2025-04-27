<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';

// Check if user is logged in and is a member
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'member') {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Get member ID and details
$stmt = $pdo->prepare("
    SELECT m.*, u.email 
    FROM members m 
    JOIN users u ON m.id = u.member_id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle counselling request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_counselling'])) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO counselling_requests (
                member_id, 
                preferred_date, 
                preferred_time,
                counselling_type,
                description,
                status
            ) VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $member['id'],
            $_POST['preferred_date'],
            $_POST['preferred_time'],
            $_POST['counselling_type'],
            $_POST['description']
        ]);
        
        $success_message = "Your counselling request has been submitted successfully.";
        
        // Send notification to admin
        $admin_notification = "New counselling request from " . $member['first_name'] . " " . $member['surname'];
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                member_id, 
                notification_text, 
                notification_date
            ) VALUES (?, ?, NOW())
        ");
        $stmt->execute([1, $admin_notification]); // Assuming admin has member_id 1
        
    } catch (PDOException $e) {
        $error_message = "Failed to submit counselling request. Please try again.";
    }
}

// Get existing counselling requests
$stmt = $pdo->prepare("
    SELECT 
        cr.*,
        CASE 
            WHEN cr.status = 'pending' THEN 'warning'
            WHEN cr.status = 'approved' THEN 'success'
            WHEN cr.status = 'completed' THEN 'info'
            ELSE 'danger'
        END as status_class
    FROM counselling_requests cr
    WHERE cr.member_id = ?
    ORDER BY cr.created_at DESC
");
$stmt->execute([$member['id']]);
$counselling_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Counselling Services - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link rel="stylesheet" href="../res/assets/css/dataTables.bootstrap4.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/memberMenu.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Counselling Services</h4>
                    </div>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>
                    
                    <!-- Request Counselling Form -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Request Counselling Session</h4>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="form-group">
                                            <label>Counselling Type</label>
                                            <select name="counselling_type" class="form-control" required>
                                                <option value="personal">Personal Counselling</option>
                                                <option value="marriage">Marriage Counselling</option>
                                                <option value="family">Family Counselling</option>
                                                <option value="spiritual">Spiritual Counselling</option>
                                                <option value="career">Career Counselling</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Preferred Date</label>
                                            <input type="date" name="preferred_date" class="form-control" required
                                                   min="<?= date('Y-m-d') ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Preferred Time</label>
                                            <input type="time" name="preferred_time" class="form-control" required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Description</label>
                                            <textarea name="description" class="form-control" rows="4" required
                                                      placeholder="Please describe your reason for seeking counselling..."></textarea>
                                        </div>
                                        
                                        <button type="submit" name="request_counselling" class="btn btn-primary">
                                            Submit Request
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Counselling History -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Your Counselling History</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="counselling-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Preferred Date</th>
                                                    <th>Preferred Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($counselling_requests as $request): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars(ucfirst($request['counselling_type'])) ?></td>
                                                    <td><?= date('F j, Y', strtotime($request['preferred_date'])) ?></td>
                                                    <td><?= date('g:i A', strtotime($request['preferred_time'])) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $request['status_class'] ?>">
                                                            <?= htmlspecialchars(ucfirst($request['status'])) ?>
                                                        </span>
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

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#counselling-table').DataTable({
                "order": [[1, "desc"]],
                "pageLength": 10
            });
        });
    </script>
</body>
</html>