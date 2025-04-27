<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /churcheaseSuperb/index.php');
    exit();
}

// Check if user has appropriate role - only financial and admin roles can access
$allowedRoles = ['finance', 'admin'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// Initialize statistics
$stats = [
    'total_tithes' => 0,
    'total_offerings' => 0,
    'total_donations' => 0,
    'monthly_tithes' => 0,
    'monthly_offerings' => 0,
    'monthly_donations' => 0
];

$recentTransactions = [];
$monthlyComparison = [];

try {
    // Get Financial Statistics
    $statsQuery = $pdo->query("
        SELECT 
            (SELECT COALESCE(SUM(amount), 0) FROM tithes) as total_tithes,
            (SELECT COALESCE(SUM(amount), 0) FROM offerings) as total_offerings,
            (SELECT COALESCE(SUM(amount), 0) FROM donations) as total_donations,
            (SELECT COALESCE(SUM(amount), 0) FROM tithes WHERE MONTH(tithe_date) = MONTH(CURRENT_DATE)) as monthly_tithes,
            (SELECT COALESCE(SUM(amount), 0) FROM offerings WHERE MONTH(offering_date) = MONTH(CURRENT_DATE)) as monthly_offerings,
            (SELECT COALESCE(SUM(amount), 0) FROM donations WHERE MONTH(donation_date) = MONTH(CURRENT_DATE)) as monthly_donations
    ");
    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

    // Get Recent Transactions
    $transactionsQuery = $pdo->query("
        (SELECT 
            t.amount, 
            t.tithe_date as transaction_date,
            m.name as member_name,
            'Tithe' as type,
            t.payment_method,
            t.currency
        FROM tithes t
        LEFT JOIN members m ON t.member_id = m.id)
        UNION ALL
        (SELECT 
            o.amount,
            o.offering_date as transaction_date,
            m.name as member_name,
            'Offering' as type,
            o.payment_method,
            o.currency
        FROM offerings o
        LEFT JOIN members m ON o.member_id = m.id)
        UNION ALL
        (SELECT 
            d.amount,
            d.donation_date as transaction_date,
            m.name as member_name,
            'Donation' as type,
            d.payment_method,
            d.currency
        FROM donations d
        LEFT JOIN members m ON d.member_id = m.id)
        ORDER BY transaction_date DESC
        LIMIT 10
    ");
    $recentTransactions = $transactionsQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get Monthly Comparison Data
    $monthlyQuery = $pdo->query("
        SELECT 
            MONTH(transaction_date) as month,
            type,
            SUM(amount) as total
        FROM (
            SELECT amount, tithe_date as transaction_date, 'Tithe' as type FROM tithes
            UNION ALL
            SELECT amount, offering_date as transaction_date, 'Offering' as type FROM offerings
            UNION ALL
            SELECT amount, donation_date as transaction_date, 'Donation' as type FROM donations
        ) as combined
        WHERE transaction_date >= DATE_SUB(CURRENT_DATE, INTERVAL 6 MONTH)
        GROUP BY MONTH(transaction_date), type
        ORDER BY transaction_date DESC
    ");
    $monthlyComparison = $monthlyQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Dashboard - ChurchEaseSuperb</title>
         <link rel="icon" href="../loginres/images/favicon/favicon.ico" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="panel-header bg-primary-gradient">
                    <div class="page-inner py-5">
                        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
                            <div>
                                <h2 class="text-white pb-2 fw-bold">Financial Dashboard</h2>
                                <h5 class="text-white op-7 mb-2">Financial Overview and Management</h5>
                            </div>
                            <div class="ml-md-auto py-2 py-md-0">
                                <a href="add_transaction.php" class="btn btn-white btn-border btn-round mr-2">Add Transaction</a>
                                <a href="generate_report.php" class="btn btn-secondary btn-round">Generate Report</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-inner mt--5">
                    <!-- Statistics Cards -->
                    <div class="row mt--2">
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats card-primary card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-hand-holding-usd"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Tithes</p>
                                                <h4 class="card-title">GHS <?php echo number_format($stats['total_tithes'], 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats card-success card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-gift"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Offerings</p>
                                                <h4 class="card-title">GHS <?php echo number_format($stats['total_offerings'], 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats card-info card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-donate"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Donations</p>
                                                <h4 class="card-title">GHS <?php echo number_format($stats['total_donations'], 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Monthly Financial Overview</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="monthlyChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Distribution</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="distributionChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Transactions -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Recent Transactions</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Member</th>
                                                    <th>Amount</th>
                                                    <th>Payment Method</th>
                                                    <th>Currency</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentTransactions as $transaction): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d', strtotime($transaction['transaction_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['type']); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['member_name']); ?></td>
                                                    <td><?php echo number_format($transaction['amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['payment_method']); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['currency']); ?></td>
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
            <?php include '../res/footer.php'; ?>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <!-- Charts Initialization -->
    <script>
        // Monthly Overview Chart
        const monthlyData = <?php echo json_encode($monthlyComparison); ?>;
        const months = [...new Set(monthlyData.map(item => item.month))];
        const types = [...new Set(monthlyData.map(item => item.type))];
        
        const datasets = types.map(type => ({
            label: type,
            data: months.map(month => {
                const entry = monthlyData.find(item => item.month === month && item.type === type);
                return entry ? entry.total : 0;
            }),
            borderColor: type === 'Tithe' ? '#1572E8' : type === 'Offering' ? '#31CE36' : '#48ABF7',
            fill: false
        }));

        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: months.map(m => {
                    const date = new Date(2024, m - 1);
                    return date.toLocaleString('default', { month: 'short' });
                }),
                datasets: datasets
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Distribution Chart
        new Chart(document.getElementById('distributionChart'), {
            type: 'pie',
            data: {
                labels: ['Tithes', 'Offerings', 'Donations'],
                datasets: [{
                    data: [
                        <?php echo $stats['total_tithes']; ?>,
                        <?php echo $stats['total_offerings']; ?>,
                        <?php echo $stats['total_donations']; ?>
                    ],
                    backgroundColor: ['#1572E8', '#31CE36', '#48ABF7']
                }]
            },
            options: {
                responsive: true
            }
        });
    </script>
</body>
</html>