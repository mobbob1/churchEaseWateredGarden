<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'pastorate'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Get counselling statistics
$stats = [
    'total_requests' => 0,
    'pending_requests' => 0,
    'completed_sessions' => 0,
    'type_breakdown' => [],
    'counselor_breakdown' => [],
    'monthly_trend' => []
];

// Total and status breakdown
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
    FROM counselling_requests
    WHERE created_at BETWEEN ? AND ?
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

$stats['total_requests'] = $result['total'];
$stats['pending_requests'] = $result['pending'];
$stats['completed_sessions'] = $result['completed'];

// Type breakdown
$stmt = $pdo->prepare("
    SELECT 
        counselling_type,
        COUNT(*) as count
    FROM counselling_requests
    WHERE created_at BETWEEN ? AND ?
    GROUP BY counselling_type
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$stats['type_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counselor breakdown
$stmt = $pdo->prepare("
    SELECT 
        u.full_name as counselor_name,
        COUNT(*) as total_sessions,
        SUM(CASE WHEN cr.status = 'completed' THEN 1 ELSE 0 END) as completed_sessions
    FROM counselling_requests cr
    JOIN users u ON cr.assigned_to = u.id
    WHERE cr.created_at BETWEEN ? AND ?
    GROUP BY cr.assigned_to, u.full_name
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$stats['counselor_breakdown'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly trend
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as total_requests
    FROM counselling_requests
    WHERE created_at BETWEEN ? AND ?
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month
");
$stmt->execute([$start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$stats['monthly_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Counselling Reports - ChurchEaseSuperb</title>
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Counselling Reports</h4>
                    </div>
                    
                    <!-- Date Filter -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="get" class="form-inline">
                                        <div class="form-group mx-sm-3">
                                            <label class="mr-2">Start Date</label>
                                            <input type="date" name="start_date" class="form-control" 
                                                   value="<?= htmlspecialchars($start_date) ?>">
                                        </div>
                                        <div class="form-group mx-sm-3">
                                            <label class="mr-2">End Date</label>
                                            <input type="date" name="end_date" class="form-control" 
                                                   value="<?= htmlspecialchars($end_date) ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary">Generate Report</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="numbers">
                                        <h4 class="card-title">Total Requests</h4>
                                        <div class="stats"><?= $stats['total_requests'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="numbers">
                                        <h4 class="card-title">Pending</h4>
                                        <div class="stats"><?= $stats['pending_requests'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="numbers">
                                        <h4 class="card-title">Completed</h4>
                                        <div class="stats"><?= $stats['completed_sessions'] ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="numbers">
                                        <h4 class="card-title">Completion Rate</h4>
                                        <div class="stats">
                                            <?= $stats['total_requests'] ? 
                                                round(($stats['completed_sessions'] / $stats['total_requests']) * 100) : 0 ?>%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row">
                        <!-- Type Breakdown -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Counselling Types</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="typeChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Monthly Trend -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Monthly Trend</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="trendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Counselor Performance -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Counselor Performance</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Counselor</th>
                                                    <th>Total Sessions</th>
                                                    <th>Completed</th>
                                                    <th>Completion Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($stats['counselor_breakdown'] as $counselor): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($counselor['counselor_name']) ?></td>
                                                    <td><?= $counselor['total_sessions'] ?></td>
                                                    <td><?= $counselor['completed_sessions'] ?></td>
                                                    <td>
                                                        <?= round(($counselor['completed_sessions'] / $counselor['total_sessions']) * 100) ?>%
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
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        // Type Breakdown Chart
        new Chart(document.getElementById('typeChart'), {
            type: 'pie',
            data: {
                labels: <?= json_encode(array_column($stats['type_breakdown'], 'counselling_type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stats['type_breakdown'], 'count')) ?>,
                    backgroundColor: [
                        '#36a2eb',
                        '#ff6384',
                        '#4bc0c0',
                        '#ff9f40',
                        '#9966ff'
                    ]
                }]
            }
        });

        // Monthly Trend Chart
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($stats['monthly_trend'], 'month')) ?>,
                datasets: [{
                    label: 'Requests',
                    data: <?= json_encode(array_column($stats['monthly_trend'], 'total_requests')) ?>,
                    borderColor: '#36a2eb',
                    tension: 0.1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>