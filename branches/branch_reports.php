<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Check session and authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'executive_admin_1'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// Get branch ID if specific branch is selected
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Get all branches for selection
$branches_query = "SELECT id, name FROM branches ORDER BY name";
$stmt = $pdo->query($branches_query);
$branches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Function to get branch statistics
function getBranchStats($pdo, $branch_id = null, $date_from, $date_to) {
    $where_clause = $branch_id ? "AND branch_id = :branch_id" : "";
    
    // Member statistics
    $member_query = "SELECT 
        COUNT(*) as total_members,
        SUM(CASE WHEN created_at BETWEEN :date_from1 AND :date_to1 THEN 1 ELSE 0 END) as new_members
        FROM members 
        WHERE 1=1 " . $where_clause;
    $stmt = $pdo->prepare($member_query);
    $stmt->bindParam(':date_from1', $date_from);
    $stmt->bindParam(':date_to1', $date_to);
    if ($branch_id) {
        $stmt->bindParam(':branch_id', $branch_id);
    }
    $stmt->execute();
    $member_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Event statistics
    $event_query = "SELECT 
        COUNT(*) as total_events,
        SUM(CASE WHEN event_date BETWEEN :date_from2 AND :date_to2 THEN 1 ELSE 0 END) as period_events
        FROM events 
        WHERE 1=1 " . $where_clause;
    $stmt = $pdo->prepare($event_query);
    $stmt->bindParam(':date_from2', $date_from);
    $stmt->bindParam(':date_to2', $date_to);
    if ($branch_id) {
        $stmt->bindParam(':branch_id', $branch_id);
    }
    $stmt->execute();
    $event_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Financial statistics - Donations
    $donations_query = "SELECT 
        SUM(amount) as donations_income,
        SUM(CASE WHEN donation_date BETWEEN :date_from3 AND :date_to3 THEN amount ELSE 0 END) as period_donations
        FROM donations 
        WHERE 1=1 " . $where_clause;
    $stmt = $pdo->prepare($donations_query);
    $stmt->bindParam(':date_from3', $date_from);
    $stmt->bindParam(':date_to3', $date_to);
    if ($branch_id) {
        $stmt->bindParam(':branch_id', $branch_id);
    }
    $stmt->execute();
    $donations_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Financial statistics - Tithes
    $tithes_query = "SELECT 
        SUM(amount) as tithes_income,
        SUM(CASE WHEN tithe_date BETWEEN :date_from4 AND :date_to4 THEN amount ELSE 0 END) as period_tithes
        FROM tithes 
        WHERE 1=1 " . $where_clause;
    $stmt = $pdo->prepare($tithes_query);
    $stmt->bindParam(':date_from4', $date_from);
    $stmt->bindParam(':date_to4', $date_to);
    if ($branch_id) {
        $stmt->bindParam(':branch_id', $branch_id);
    }
    $stmt->execute();
    $tithes_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Financial statistics - Offerings
    $offerings_query = "SELECT 
        SUM(amount) as offerings_income,
        SUM(CASE WHEN offering_date BETWEEN :date_from5 AND :date_to5 THEN amount ELSE 0 END) as period_offerings
        FROM offerings 
        WHERE 1=1 " . $where_clause;
    $stmt = $pdo->prepare($offerings_query);
    $stmt->bindParam(':date_from5', $date_from);
    $stmt->bindParam(':date_to5', $date_to);
    if ($branch_id) {
        $stmt->bindParam(':branch_id', $branch_id);
    }
    $stmt->execute();
    $offerings_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Financial statistics - Expenses
    $expenses_query = "SELECT 
        SUM(amount) as total_expenses,
        SUM(CASE WHEN expense_date BETWEEN :date_from6 AND :date_to6 THEN amount ELSE 0 END) as period_expenses
        FROM expenses 
        WHERE 1=1 " . $where_clause;
    $stmt = $pdo->prepare($expenses_query);
    $stmt->bindParam(':date_from6', $date_from);
    $stmt->bindParam(':date_to6', $date_to);
    if ($branch_id) {
        $stmt->bindParam(':branch_id', $branch_id);
    }
    $stmt->execute();
    $expenses_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calculate total income (donations + tithes + offerings)
    $total_income = ($donations_stats['donations_income'] ?? 0) + 
                    ($tithes_stats['tithes_income'] ?? 0) + 
                    ($offerings_stats['offerings_income'] ?? 0);
                    
    $period_income = ($donations_stats['period_donations'] ?? 0) + 
                     ($tithes_stats['period_tithes'] ?? 0) + 
                     ($offerings_stats['period_offerings'] ?? 0);

    return array_merge(
        $member_stats ?: ['total_members' => 0, 'new_members' => 0],
        $event_stats ?: ['total_events' => 0, 'period_events' => 0],
        [
            'total_income' => $total_income ?: 0, 
            'period_income' => $period_income ?: 0,
            'total_expenses' => $expenses_stats['total_expenses'] ?: 0, 
            'period_expenses' => $expenses_stats['period_expenses'] ?: 0
        ]
    );
}

// Get statistics based on filter
$stats = getBranchStats($pdo, $branch_id, $date_from, $date_to);

// Get branch comparison data if no specific branch is selected
$branch_comparison = [];
if (!$branch_id) {
    foreach ($branches as $branch) {
        $branch_comparison[$branch['id']] = array_merge(
            ['name' => $branch['name']],
            getBranchStats($pdo, $branch['id'], $date_from, $date_to)
        );
    }
}

// Log report generation
$logger->log(
    'branch_report', 
    'branches', 
    $branch_id, 
    "Generated branch report" . ($branch_id ? " for specific branch" : " for all branches"), 
    $_SESSION['user_id'],
    [
        'date_from' => $date_from,
        'date_to' => $date_to
    ]
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Branch Reports - ChurchEaseSuperb</title>
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
    <link rel="stylesheet" href="../res/assets/css/dataTables.bootstrap4.min.css">
    
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Branch Reports</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../index.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="dashboard.php">Branches</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Reports</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="d-flex align-items-center mb-4 no-print">
                        <button onclick="window.print()" class="btn btn-primary btn-round ml-auto">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                    </div>

                    <!-- Filters -->
                    <div class="card no-print">
                        <div class="card-header">
                            <div class="card-title">Report Filters</div>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row align-items-end">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="branch_id">Select Branch</label>
                                        <select class="form-control select2" id="branch_id" name="branch_id">
                                            <option value="">All Branches</option>
                                            <?php foreach ($branches as $branch): ?>
                                            <option value="<?php echo $branch['id']; ?>" <?php echo ($branch_id == $branch['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($branch['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="date_from">From Date</label>
                                        <input type="date" class="form-control" id="date_from" name="date_from" 
                                               value="<?php echo $date_from; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="date_to">To Date</label>
                                        <input type="date" class="form-control" id="date_to" name="date_to" 
                                               value="<?php echo $date_to; ?>">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Statistics Overview -->
                    <div class="row">
                        <!-- Members Card -->
                        <div class="col-sm-6 col-md-3">
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
                                                <p class="card-category">Total Members</p>
                                                <h4 class="card-title"><?php echo number_format($stats['total_members']); ?></h4>
                                                <p class="card-category">New: <?php echo number_format($stats['new_members']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Events Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Events</p>
                                                <h4 class="card-title"><?php echo number_format($stats['total_events']); ?></h4>
                                                <p class="card-category">Period: <?php echo number_format($stats['period_events']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Income Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="fas fa-dollar-sign"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Income</p>
                                                <h4 class="card-title">$<?php echo number_format($stats['total_income'], 2); ?></h4>
                                                <p class="card-category">Period: $<?php echo number_format($stats['period_income'], 2); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Expenses Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-warning bubble-shadow-small">
                                                <i class="fas fa-credit-card"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Expenses</p>
                                                <h4 class="card-title">$<?php echo number_format($stats['total_expenses'], 2); ?></h4>
                                                <p class="card-category">Period: $<?php echo number_format($stats['period_expenses'], 2); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (!$branch_id): ?>
                    <!-- Branch Comparison -->
                    <div class="row page-break">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Branch Comparison</div>
                                    <div class="card-category">
                                        Period: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover" id="branchComparisonTable">
                                            <thead>
                                                <tr>
                                                    <th>Branch Name</th>
                                                    <th>Total Members</th>
                                                    <th>New Members</th>
                                                    <th>Total Events</th>
                                                    <th>Period Events</th>
                                                    <th>Total Income</th>
                                                    <th>Period Income</th>
                                                    <th>Total Expenses</th>
                                                    <th>Period Expenses</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($branch_comparison as $branch): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($branch['name']); ?></td>
                                                    <td><?php echo number_format($branch['total_members']); ?></td>
                                                    <td><?php echo number_format($branch['new_members']); ?></td>
                                                    <td><?php echo number_format($branch['total_events']); ?></td>
                                                    <td><?php echo number_format($branch['period_events']); ?></td>
                                                    <td>$<?php echo number_format($branch['total_income'], 2); ?></td>
                                                    <td>$<?php echo number_format($branch['period_income'], 2); ?></td>
                                                    <td>$<?php echo number_format($branch['total_expenses'], 2); ?></td>
                                                    <td>$<?php echo number_format($branch['period_expenses'], 2); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Single Branch Details -->
                    <div class="row page-break">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">
                                        <?php 
                                        $branch_name = '';
                                        foreach ($branches as $branch) {
                                            if ($branch['id'] == $branch_id) {
                                                $branch_name = $branch['name'];
                                                break;
                                            }
                                        }
                                        echo htmlspecialchars($branch_name); 
                                        ?> - Detailed Report
                                    </div>
                                    <div class="card-category">
                                        Period: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h4>Membership Statistics</h4>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th>Total Members</th>
                                                        <td><?php echo number_format($stats['total_members']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>New Members (Period)</th>
                                                        <td><?php echo number_format($stats['new_members']); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Growth Rate</th>
                                                        <td>
                                                            <?php 
                                                            $growth_rate = ($stats['total_members'] > 0) 
                                                                ? ($stats['new_members'] / $stats['total_members'] * 100) 
                                                                : 0;
                                                            echo number_format($growth_rate, 2) . '%'; 
                                                            ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <h4>Financial Summary</h4>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <tr>
                                                        <th>Total Income</th>
                                                        <td>$<?php echo number_format($stats['total_income'], 2); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Total Expenses</th>
                                                        <td>$<?php echo number_format($stats['total_expenses'], 2); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Net Balance</th>
                                                        <td>
                                                            $<?php echo number_format($stats['total_income'] - $stats['total_expenses'], 2); ?>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Period Income</th>
                                                        <td>$<?php echo number_format($stats['period_income'], 2); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Period Expenses</th>
                                                        <td>$<?php echo number_format($stats['period_expenses'], 2); ?></td>
                                                    </tr>
                                                    <tr>
                                                        <th>Period Net</th>
                                                        <td>
                                                            $<?php echo number_format($stats['period_income'] - $stats['period_expenses'], 2); ?>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
    
    <!-- jQuery Scrollbar -->
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- DataTables -->
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="../res/assets/js/plugin/datatables/buttons.html5.min.js"></script>
    <script src="../res/assets/js/plugin/datatables/buttons.print.min.js"></script>
    
    <!-- Select2 -->
    <script src="../res/assets/js/plugin/select2/select2.full.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: "bootstrap"
            });

            // Initialize DataTable
            $('#branchComparisonTable').DataTable({
                "pageLength": 25,
                "order": [[0, "asc"]],
                "responsive": true,
                "autoWidth": false,
                "dom": 'Bfrtip',
                "buttons": [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Date range validation
            $('#date_from, #date_to').change(function() {
                var dateFrom = $('#date_from').val();
                var dateTo = $('#date_to').val();
                
                if (dateFrom && dateTo && dateFrom > dateTo) {
                    alert('From date cannot be later than To date');
                    $(this).val('');
                }
            });
        });
    </script>
</body>
</html>