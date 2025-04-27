<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';

// Check if user is logged in and is a member
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'member') {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Get member ID
$stmt = $pdo->prepare("SELECT member_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member_id = $stmt->fetchColumn();

// Get tithes history
$stmt = $pdo->prepare("
    SELECT 
        amount,
        currency,
        payment_method,
        tithe_date,
        tithe_month,
        created_at
    FROM tithes 
    WHERE member_id = ?
    ORDER BY tithe_date DESC
");
$stmt->execute([$member_id]);
$tithes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total tithes
$stmt = $pdo->prepare("
    SELECT SUM(amount) as total_tithes
    FROM tithes 
    WHERE member_id = ?
");
$stmt->execute([$member_id]);
$total_tithes = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Your Tithes - OutpouringCRM</title>
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
                        <h4 class="page-title">Your Tithes History</h4>
                    </div>
                    
                    <!-- Tithes Summary -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Total Tithes: <?= number_format($total_tithes, 2) ?> GHS</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="tithes-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Amount</th>
                                                    <th>Currency</th>
                                                    <th>Payment Method</th>
                                                    <th>Date Paid</th>
                                                    <th>For Month</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tithes as $tithe): ?>
                                                <tr>
                                                    <td><?= number_format($tithe['amount'], 2) ?></td>
                                                    <td><?= htmlspecialchars($tithe['currency']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($tithe['payment_method'])) ?></td>
                                                    <td><?= date('F j, Y', strtotime($tithe['tithe_date'])) ?></td>
                                                    <td><?= date('F Y', strtotime($tithe['tithe_month'])) ?></td>
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
            $('#tithes-table').DataTable({
                "order": [[3, "desc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>