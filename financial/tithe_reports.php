<?php
require_once '../includes/auth.php';
require_once '../config.php';
require_once '../includes/AuditLogger.php';

// Check session and role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

$allowedRoles = ['tithe_manager', 'finance', 'seer', 'admin'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: ../access_denied.php');
    exit();
}

// Initialize variables
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$reportType = isset($_GET['report_type']) ? $_GET['report_type'] : 'monthly';
$classId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : null;

// Get bible classes for filter
$classes = $pdo->query("SELECT id, class_name FROM bible_classes WHERE status = 'active' ORDER BY class_name")->fetchAll();

// Prepare date ranges based on report type
switch ($reportType) {
    case 'weekly':
        $groupBy = "YEARWEEK(t.payment_date, 1)";
        $dateFormat = "DATE_FORMAT(t.payment_date, '%Y-Week %u')";
        break;
    case 'monthly':
        $groupBy = "DATE_FORMAT(t.payment_date, '%Y-%m')";
        $dateFormat = "DATE_FORMAT(t.payment_date, '%Y-%m')";
        break;
    case 'yearly':
        $groupBy = "YEAR(t.payment_date)";
        $dateFormat = "YEAR(t.payment_date)";
        break;
    default:
        $groupBy = "DATE_FORMAT(t.payment_date, '%Y-%m')";
        $dateFormat = "DATE_FORMAT(t.payment_date, '%Y-%m')";
}

// Build query
$query = "
    SELECT 
        $dateFormat as period,
        COUNT(DISTINCT t.member_id) as unique_givers,
        COUNT(t.id) as total_transactions,
        SUM(t.amount) as total_amount,
        bc.class_name,
        t.currency
    FROM tithes t
    LEFT JOIN members m ON t.member_id = m.id
    LEFT JOIN bible_classes bc ON m.class_id = bc.id
    WHERE t.payment_date BETWEEN ? AND ?
";

$params = [$startDate, $endDate];

if ($classId) {
    $query .= " AND m.class_id = ?";
    $params[] = $classId;
}

$query .= "
    GROUP BY $groupBy, bc.class_name, t.currency
    ORDER BY t.payment_date DESC
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reports = $stmt->fetchAll();

// Calculate totals
$totals = [
    'transactions' => 0,
    'amount' => 0,
    'givers' => 0
];

foreach ($reports as $report) {
    $totals['transactions'] += $report['total_transactions'];
    $totals['amount'] += $report['total_amount'];
    $totals['givers'] = max($totals['givers'], $report['unique_givers']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tithe Reports - ChurchEase</title>
    <?php include '../res/assets.php'; ?>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Tithe Reports</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../home/dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Tithe Reports</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Generate Report</div>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="form-horizontal">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Report Type</label>
                                                    <select name="report_type" class="form-control" onchange="this.form.submit()">
                                                        <option value="weekly" <?php echo $reportType == 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                        <option value="monthly" <?php echo $reportType == 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                                        <option value="yearly" <?php echo $reportType == 'yearly' ? 'selected' : ''; ?>>Yearly</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Start Date</label>
                                                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>End Date</label>
                                                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Bible Class</label>
                                                    <select name="class_id" class="form-control">
                                                        <option value="">All Classes</option>
                                                        <?php foreach ($classes as $class): ?>
                                                        <option value="<?php echo $class['id']; ?>" <?php echo $classId == $class['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-filter"></i> Filter
                                            </button>
                                            <button type="button" class="btn btn-success" onclick="exportToExcel()">
                                                <i class="fa fa-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            
                            <!-- Summary Cards -->
                            <div class="row">
                                <div class="col-sm-6 col-md-4">
                                    <div class="card card-stats card-round">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-icon">
                                                    <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                        <i class="fas fa-users"></i>
                                                    </div>
                                                </div>
                                                <div class="col col-stats ml-3 ml-sm-0">
                                                    <div class="numbers">
                                                        <p class="card-category">Unique Givers</p>
                                                        <h4 class="card-title"><?php echo number_format($totals['givers']); ?></h4>
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
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </div>
                                                </div>
                                                <div class="col col-stats ml-3 ml-sm-0">
                                                    <div class="numbers">
                                                        <p class="card-category">Total Transactions</p>
                                                        <h4 class="card-title"><?php echo number_format($totals['transactions']); ?></h4>
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
                                                    <div class="icon-big text-center icon-success bubble-shadow-small">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </div>
                                                </div>
                                                <div class="col col-stats ml-3 ml-sm-0">
                                                    <div class="numbers">
                                                        <p class="card-category">Total Amount</p>
                                                        <h4 class="card-title"><?php echo number_format($totals['amount'], 2); ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Report Table -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Tithe Report</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="report-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Period</th>
                                                    <th>Class</th>
                                                    <th>Unique Givers</th>
                                                    <th>Transactions</th>
                                                    <th>Total Amount</th>
                                                    <th>Currency</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($reports as $report): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($report['period']); ?></td>
                                                    <td><?php echo htmlspecialchars($report['class_name'] ?? 'All Classes'); ?></td>
                                                    <td><?php echo number_format($report['unique_givers']); ?></td>
                                                    <td><?php echo number_format($report['total_transactions']); ?></td>
                                                    <td><?php echo number_format($report['total_amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($report['currency']); ?></td>
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
    <?php include '../res/scripts.php'; ?>
    
    <script>
        $(document).ready(function() {
            $('#report-table').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]],
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });
        });
        
        function exportToExcel() {
            window.location.href = 'export_tithe_report.php?' + $('form').serialize();
        }
    </script>
</body>
</html>