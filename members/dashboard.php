<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
if ($_SESSION['user_role'] !== 'member') {
    header('Location: /outpouringcrm/index.php?error=unauthorized');
    exit();
}

// Get member details
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = (SELECT member_id FROM users WHERE id = ?)");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$member) {
    header('Location: /outpouringcrm/index.php?error=invalid_member');
    exit();
}

// Initialize default values
$recentMessages = [];
$financialSummary = [
    'total_tithes' => 0,
    'total_offerings' => 0,
    'total_donations' => 0,
    'recent_transactions' => []
];

try {
    // Get Recent Messages
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name as sender_name 
        FROM messages m 
        JOIN users u ON m.sender_id = u.id 
        WHERE m.recipient_id = ? 
        ORDER BY m.sent_time DESC 
        LIMIT 5
    ");
    $stmt->execute([$member['id']]);
    $recentMessages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get Tithes Summary
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_tithes
        FROM tithes 
        WHERE member_id = ?
    ");
    $stmt->execute([$member['id']]);
    $tithesSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    $financialSummary['total_tithes'] = $tithesSummary['total_tithes'] ?? 0;

    // Get Offerings Summary
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_offerings
        FROM offerings o
        JOIN events e ON o.event_id = e.id
        JOIN attendance a ON e.id = a.event_id
        WHERE a.member_id = ?
    ");
    $stmt->execute([$member['id']]);
    $offeringsSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    $financialSummary['total_offerings'] = $offeringsSummary['total_offerings'] ?? 0;

    // Get Donations Summary
    $stmt = $pdo->prepare("
        SELECT SUM(amount) as total_donations
        FROM donations 
        WHERE member_id = ?
    ");
    $stmt->execute([$member['id']]);
    $donationsSummary = $stmt->fetch(PDO::FETCH_ASSOC);
    $financialSummary['total_donations'] = $donationsSummary['total_donations'] ?? 0;

    // Get Recent Transactions (combining tithes and donations)
    $stmt = $pdo->prepare("
        (SELECT 
            amount, 
            tithe_date as transaction_date, 
            'Tithe' as transaction_type,
            payment_method,
            currency
        FROM tithes 
        WHERE member_id = ?)
        UNION ALL
        (SELECT 
            amount, 
            donation_date as transaction_date,
            'Donation' as transaction_type,
            payment_method,
            currency
        FROM donations 
        WHERE member_id = ?)
        ORDER BY transaction_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$member['id'], $member['id']]);
    $financialSummary['recent_transactions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Member Dashboard - OutpouringCRM</title>
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
        <?php include '../res/memberMenu.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="panel-header bg-primary-gradient">
                    <div class="page-inner py-5">
                        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
                            <div>
                                <h2 class="text-white pb-2 fw-bold">Welcome, <?php echo htmlspecialchars($member['name']); ?>!</h2>
                                <h5 class="text-white op-7 mb-2">Member Dashboard</h5>
                            </div>
                        </div>
                    </div>
                </div>
    
                <div class="page-inner mt--5">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
    
                    <!-- Financial Summary Cards -->
                    <div class="row mt--2">
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Tithes</p>
                                                <h4 class="card-title">GHS <?php echo number_format($financialSummary['total_tithes'], 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="fas fa-gift"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Offerings</p>
                                                <h4 class="card-title">GHS <?php echo number_format($financialSummary['total_offerings'], 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-donate"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Donations</p>
                                                <h4 class="card-title">GHS <?php echo number_format($financialSummary['total_donations'], 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
    
                    <!-- Recent Messages and Financial Transactions -->
                    <div class="row">
                        <!-- Recent Messages -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Recent Messages</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>From</th>
                                                    <th>Message</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentMessages as $message): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($message['sent_time'])); ?></td>
                                                    <td><?php echo htmlspecialchars($message['sender_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($message['message']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($recentMessages)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No recent messages</td>
                                                </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
    
                        <!-- Recent Financial Transactions -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Recent Financial Transactions</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($financialSummary['recent_transactions'] as $transaction): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['transaction_type']); ?></td>
                                                    <td><?php echo $transaction['currency'] . ' ' . number_format($transaction['amount'], 2); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($financialSummary['recent_transactions'])): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center">No recent transactions</td>
                                                </tr>
                                                <?php endif; ?>
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
    
    <!-- jQuery UI -->
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
    
    <!-- jQuery Scrollbar -->
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- Chart JS -->
    <script src="../res/assets/js/plugin/chart.js/chart.min.js"></script>
    
    <!-- jQuery Sparkline -->
    <script src="../res/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>
    
    <!-- Chart Circle -->
    <script src="../res/assets/js/plugin/chart-circle/circles.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>