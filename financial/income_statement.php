<?php
require_once '../includes/auth.php';
require_once '../config.php';


// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'finance'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// Get date range from request or default to current month
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$currency = isset($_GET['currency']) ? $_GET['currency'] : 'GHS';

// Function to get total donations by category
function getTotalDonations($pdo, $start_date, $end_date, $currency) {
    $stmt = $pdo->prepare("
        SELECT 
            COALESCE(pc.name, 'General') as category,
            COUNT(d.id) as count,
            SUM(d.amount) as total
        FROM donations d
        LEFT JOIN project_categories pc ON d.project_category_id = pc.id
        WHERE d.donation_date BETWEEN ? AND ?
        AND d.currency = ?
        GROUP BY pc.name
        ORDER BY pc.name
    ");
    $stmt->execute([$start_date, $end_date, $currency]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get total other income by category
function getTotalOtherIncome($pdo, $start_date, $end_date, $currency) {
    $stmt = $pdo->prepare("
        SELECT 
            category,
            COUNT(id) as count,
            SUM(amount) as total
        FROM other_income
        WHERE income_date BETWEEN ? AND ?
        AND currency = ?
        GROUP BY category
        ORDER BY category
    ");
    $stmt->execute([$start_date, $end_date, $currency]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get total expenses by category
function getTotalExpenses($pdo, $start_date, $end_date, $currency) {
    $stmt = $pdo->prepare("
        SELECT 
            ec.name as category,
            COUNT(e.id) as count,
            SUM(e.amount) as total
        FROM expenses e
        JOIN expense_categories ec ON e.category = ec.name
        WHERE e.expense_date BETWEEN ? AND ?
        AND e.currency = ?
        GROUP BY ec.name
        ORDER BY ec.name
    ");
    $stmt->execute([$start_date, $end_date, $currency]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all financial data
$donations = getTotalDonations($pdo, $start_date, $end_date, $currency);
$other_income = getTotalOtherIncome($pdo, $start_date, $end_date, $currency);
$expenses = getTotalExpenses($pdo, $start_date, $end_date, $currency);

// Calculate totals
$total_donations = array_sum(array_column($donations, 'total')) ?: 0;
$total_other_income = array_sum(array_column($other_income, 'total')) ?: 0;
$total_income = $total_donations + $total_other_income;
$total_expenses = array_sum(array_column($expenses, 'total')) ?: 0;
$net_income = $total_income - $total_expenses;

// Get available currencies with fixed collation
try {
    $currencies = $pdo->query("
        SELECT DISTINCT currency 
        FROM (
            SELECT currency FROM donations WHERE currency IS NOT NULL
            UNION ALL
            SELECT currency FROM expenses WHERE currency IS NOT NULL
            UNION ALL
            SELECT currency FROM other_income WHERE currency IS NOT NULL
        ) as currencies 
        GROUP BY currency 
        ORDER BY currency
    ")->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Fallback to default currency if query fails
    $currencies = ['GHS'];
}

// Get monthly trends with fixed collation
try {
    $monthly_trends = $pdo->prepare("
        WITH combined_data AS (
            SELECT donation_date as date, amount, 'income' as type 
            FROM donations 
            WHERE currency = ? AND donation_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT income_date as date, amount, 'income' as type 
            FROM other_income 
            WHERE currency = ? AND income_date BETWEEN ? AND ?
            
            UNION ALL
            
            SELECT expense_date as date, amount, 'expense' as type 
            FROM expenses 
            WHERE currency = ? AND expense_date BETWEEN ? AND ?
        )
        SELECT 
            DATE_FORMAT(date, '%Y-%m') as month,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
        FROM combined_data
        GROUP BY DATE_FORMAT(date, '%Y-%m')
        ORDER BY month
    ");
    
    $monthly_trends->execute([
        $currency, $start_date, $end_date,
        $currency, $start_date, $end_date,
        $currency, $start_date, $end_date
    ]);
    $trends = $monthly_trends->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $trends = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Income Statement - ChurchEaseSuperb</title>
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
    <link rel="stylesheet" href="../res/assets/css/daterangepicker.css">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            .card {
                border: none !important;
                box-shadow: none !important;
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
                        <h4 class="page-title">Income Statement</h4>
                    </div>
                    
                    <!-- Filters -->
                    <div class="row no-print">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="form-inline">
                                        <div class="form-group mx-sm-3">
                                            <label for="daterange" class="mr-2">Date Range:</label>
                                            <input type="text" class="form-control" id="daterange" name="daterange" 
                                                value="<?php echo date('m/d/Y', strtotime($start_date)) . ' - ' . date('m/d/Y', strtotime($end_date)); ?>">
                                            <input type="hidden" name="start_date" id="start_date" value="<?php echo $start_date; ?>">
                                            <input type="hidden" name="end_date" id="end_date" value="<?php echo $end_date; ?>">
                                        </div>
                                        <div class="form-group mx-sm-3">
                                            <label for="currency" class="mr-2">Currency:</label>
                                            <select class="form-control" name="currency" id="currency">
                                                <?php foreach ($currencies as $curr): ?>
                                                    <option value="<?php echo htmlspecialchars($curr); ?>" 
                                                            <?php echo $curr === $currency ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($curr); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary Cards -->
                    <div class="row">
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats card-primary card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-donate"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Income</p>
                                                <h4 class="card-title"><?php echo number_format($total_income, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats card-danger card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Expenses</p>
                                                <h4 class="card-title"><?php echo number_format($total_expenses, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card card-stats <?php echo $net_income >= 0 ? 'card-success' : 'card-danger'; ?> card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Net Income</p>
                                                <h4 class="card-title"><?php echo number_format($net_income, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Income Statement Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Income Statement</h4>
                                        <button class="btn btn-primary btn-round ml-auto no-print" onclick="window.print()">
                                            <i class="fa fa-print"></i> Print Statement
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th colspan="3" class="text-center">
                                                        Income Statement<br>
                                                        For the period <?php echo date('F d, Y', strtotime($start_date)); ?> 
                                                        to <?php echo date('F d, Y', strtotime($end_date)); ?><br>
                                                        (In <?php echo htmlspecialchars($currency); ?>)
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Income Section -->
                                                <tr>
                                                    <th colspan="3">Income</th>
                                                </tr>
                                                <tr>
                                                    <th class="pl-4">Donations</th>
                                                    <th class="text-center">Count</th>
                                                    <th class="text-right">Amount</th>
                                                </tr>
                                                <?php foreach ($donations as $donation): ?>
                                                    <tr>
                                                        <td class="pl-5"><?php echo htmlspecialchars($donation['category']); ?></td>
                                                        <td class="text-center"><?php echo $donation['count']; ?></td>
                                                        <td class="text-right"><?php echo number_format($donation['total'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-info">
                                                    <td class="pl-4"><strong>Total Donations</strong></td>
                                                    <td></td>
                                                    <td class="text-right"><strong><?php echo number_format($total_donations, 2); ?></strong></td>
                                                </tr>

                                                <tr>
                                                    <th class="pl-4">Other Income</th>
                                                    <th class="text-center">Count</th>
                                                    <th class="text-right">Amount</th>
                                                </tr>
                                                <?php foreach ($other_income as $income): ?>
                                                    <tr>
                                                        <td class="pl-5"><?php echo htmlspecialchars($income['category']); ?></td>
                                                        <td class="text-center"><?php echo $income['count']; ?></td>
                                                        <td class="text-right"><?php echo number_format($income['total'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-info">
                                                    <td class="pl-4"><strong>Total Other Income</strong></td>
                                                    <td></td>
                                                    <td class="text-right"><strong><?php echo number_format($total_other_income, 2); ?></strong></td>
                                                </tr>

                                                <tr class="table-primary">
                                                    <th>Total Income</th>
                                                    <td></td>
                                                    <th class="text-right"><?php echo number_format($total_income, 2); ?></th>
                                                </tr>

                                                <!-- Expenses Section -->
                                                <tr>
                                                    <th colspan="3">Expenses</th>
                                                </tr>
                                                <?php foreach ($expenses as $expense): ?>
                                                    <tr>
                                                        <td class="pl-4"><?php echo htmlspecialchars($expense['category']); ?></td>
                                                        <td class="text-center"><?php echo $expense['count']; ?></td>
                                                        <td class="text-right"><?php echo number_format($expense['total'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-danger">
                                                    <th>Total Expenses</th>
                                                    <td></td>
                                                    <th class="text-right"><?php echo number_format($total_expenses, 2); ?></th>
                                                </tr>

                                                <!-- Net Income -->
                                                <tr class="<?php echo $net_income >= 0 ? 'table-success' : 'table-danger'; ?>">
                                                    <th>Net Income</th>
                                                    <td></td>
                                                    <th class="text-right"><?php echo number_format($net_income, 2); ?></th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row no-print">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Income Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="incomeChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Expense Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="expenseChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Monthly Trends -->
                    <div class="row no-print">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Monthly Trends</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="trendChart"></canvas>
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
    <script src="../res/assets/js/plugin/chart.js/chart.min.js"></script>
    <script src="../res/assets/js/plugin/moment/moment.min.js"></script>
    <script src="../res/assets/js/plugin/daterangepicker/daterangepicker.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize date range picker
            $('#daterange').daterangepicker({
                startDate: moment('<?php echo $start_date; ?>'),
                endDate: moment('<?php echo $end_date; ?>'),
                locale: {
                    format: 'MM/DD/YYYY'
                }
            }, function(start, end) {
                $('#start_date').val(start.format('YYYY-MM-DD'));
                $('#end_date').val(end.format('YYYY-MM-DD'));
            });

            // Initialize charts
            const incomeData = {
                donations: <?php echo json_encode($donations); ?>,
                otherIncome: <?php echo json_encode($other_income); ?>
            };

            const expenseData = <?php echo json_encode($expenses); ?>;
            const trendData = <?php echo json_encode($trends); ?>;

            // Income Distribution Chart
            new Chart(document.getElementById('incomeChart'), {
                type: 'pie',
                data: {
                    labels: [
                        ...incomeData.donations.map(item => 'Donations - ' + item.category),
                        ...incomeData.otherIncome.map(item => 'Other - ' + item.category)
                    ],
                    datasets: [{
                        data: [
                            ...incomeData.donations.map(item => item.total),
                            ...incomeData.otherIncome.map(item => item.total)
                        ],
                        backgroundColor: [
                            '#36a2eb',
                            '#ff6384',
                            '#4bc0c0',
                            '#ff9f40',
                            '#9966ff',
                            '#ffcd56',
                            '#c9cbcf'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    legend: {
                        position: 'right'
                    }
                }
            });

            // Expense Distribution Chart
            new Chart(document.getElementById('expenseChart'), {
                type: 'pie',
                data: {
                    labels: expenseData.map(item => item.category),
                    datasets: [{
                        data: expenseData.map(item => item.total),
                        backgroundColor: [
                            '#ff6384',
                            '#36a2eb',
                            '#4bc0c0',
                            '#ff9f40',
                            '#9966ff',
                            '#ffcd56',
                            '#c9cbcf'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    legend: {
                        position: 'right'
                    }
                }
            });

            // Monthly Trends Chart
            new Chart(document.getElementById('trendChart'), {
                type: 'line',
                data: {
                    labels: trendData.map(item => moment(item.month + '-01').format('MMM YYYY')),
                    datasets: [{
                        label: 'Income',
                        data: trendData.map(item => item.income),
                        borderColor: '#36a2eb',
                        fill: false
                    }, {
                        label: 'Expenses',
                        data: trendData.map(item => item.expense),
                        borderColor: '#ff6384',
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }]
                    }
                }
            });
        });
    </script>
</body>
</html>