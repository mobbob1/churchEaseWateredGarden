<?php
require_once '../includes/auth.php';
require_once '../config.php';


// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'finance'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Get the year from query parameters or use current year
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Initialize arrays for totals
$categoryTotals = [];
$monthlyTotals = [];
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];

// Fetch all expense categories
$expenseCategories = $pdo->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll();

foreach ($currencies as $currency) {
    $monthlyTotals[$currency] = array_fill_keys(range(1, 12), 0);
    foreach ($expenseCategories as $category) {
        $categoryTotals[$category['name']][$currency] = array_fill_keys(range(1, 12), 0);
    }
}

// Fetch expense data for the year
$expenseData = [];
foreach ($expenseCategories as $category) {
    $expenseData[$category['name']] = [
        'name' => $category['name'],
        'description' => $category['description'],
        'months' => array_fill_keys(range(1, 12), []),
        'totals' => array_fill_keys($currencies, array_fill_keys(range(1, 12), 0))
    ];
}

// Fetch all expenses for the year
$stmt = $pdo->prepare("
    SELECT e.*, ec.name as category_name, ec.description as category_description, 
           MONTH(e.expense_date) as month
    FROM expenses e
    JOIN expense_categories ec ON e.category = ec.name
    WHERE YEAR(e.expense_date) = ?
    ORDER BY e.expense_date, ec.name
");
$stmt->execute([$year]);
$allExpenses = $stmt->fetchAll();

// Organize expense data
foreach ($allExpenses as $expense) {
    $month = $expense['month'];
    $categoryName = $expense['category_name'];
    
    if (isset($expenseData[$categoryName])) {
        $expenseData[$categoryName]['months'][$month][] = $expense;
        $expenseData[$categoryName]['totals'][$expense['currency']][$month] += $expense['amount'];
        $monthlyTotals[$expense['currency']][$month] += $expense['amount'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Yearly Expense Report - Church Management System</title>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.bootstrap4.min.css">
    
    <style>
        .currency-total { font-weight: bold; color: #2980b9; }
        .month-total { background-color: #f8f9fa; }
        .grand-total { font-weight: bold; background-color: #e9ecef; }
        .sticky-header { position: sticky; top: 0; background: white; z-index: 1; }
        .trend-chart { height: 300px; margin-bottom: 30px; }
        .category-description { font-size: 0.85em; color: #666; }
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
                        <h4 class="page-title">Yearly Expense Report</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Financial</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Yearly Expense Report</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">
                                            Expenses for Year <?php echo $year; ?>
                                        </h4>
                                        <div class="ml-auto">
                                            <form class="form-inline">
                                                <select name="year" class="form-control mr-2">
                                                    <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                                            <?php echo $y; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($currencies as $currency): ?>
                                        <h5 class="mt-4 mb-3">Expenses in <?php echo $currency; ?></h5>
                                        <div class="table-responsive">
                                            <table class="expense-table display table table-striped table-hover">
                                                <thead class="sticky-header">
                                                    <tr>
                                                        <th>Expense Category</th>
                                                        <?php foreach(range(1, 12) as $month): ?>
                                                            <th class="text-center"><?php echo date('F', mktime(0, 0, 0, $month, 1)); ?></th>
                                                        <?php endforeach; ?>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($expenseData as $categoryName => $data): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($data['name']); ?></strong>
                                                                <?php if (!empty($data['description'])): ?>
                                                                    <br><span class="category-description">
                                                                        <?php echo htmlspecialchars($data['description']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <?php 
                                                            $yearTotal = 0;
                                                            foreach(range(1, 12) as $month): 
                                                                $monthTotal = $data['totals'][$currency][$month];
                                                                $yearTotal += $monthTotal;
                                                            ?>
                                                                <td class="text-right">
                                                                    <?php echo $monthTotal > 0 ? number_format($monthTotal, 2) : '-'; ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($yearTotal, 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <!-- Monthly Totals Row -->
                                                    <tr class="month-total">
                                                        <td><strong>Monthly Total</strong></td>
                                                        <?php 
                                                        $yearTotal = 0;
                                                        foreach(range(1, 12) as $month): 
                                                            $yearTotal += $monthlyTotals[$currency][$month];
                                                        ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($monthlyTotals[$currency][$month], 2); ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                        <td class="text-right grand-total">
                                                            <?php echo number_format($yearTotal, 2); ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Yearly Trend Charts -->
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="trend-chart">
                                                    <canvas id="monthlyTrendChart<?php echo $currency; ?>"></canvas>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="trend-chart">
                                                    <canvas id="categoryDistributionChart<?php echo $currency; ?>"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            $('.expense-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 
                    'csv', 
                    {
                        extend: 'excel',
                        title: 'Yearly Expense Report - ' + <?php echo $year; ?>,
                        messageTop: 'Category-wise Expenses by Month'
                    },
                    {
                        extend: 'pdf',
                        title: 'Yearly Expense Report - ' + <?php echo $year; ?>,
                        messageTop: 'Category-wise Expenses by Month'
                    },
                    'print'
                ],
                pageLength: 25,
                order: [[0, 'asc']]
            });

            <?php foreach ($currencies as $currency): ?>
            // Create monthly trend chart
            new Chart(document.getElementById('monthlyTrendChart<?php echo $currency; ?>'), {
                type: 'line',
                data: {
                    labels: <?php 
                        echo json_encode(array_map(function($m) { 
                            return date('F', mktime(0, 0, 0, $m, 1)); 
                        }, range(1, 12))); 
                    ?>,
                    datasets: [{
                        label: 'Monthly Expenses (<?php echo $currency; ?>)',
                        data: <?php echo json_encode(array_values($monthlyTotals[$currency])); ?>,
                        borderColor: '#2980b9',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Expense Trend - ' + <?php echo $year; ?> + ' (<?php echo $currency; ?>)'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Create category distribution chart
            new Chart(document.getElementById('categoryDistributionChart<?php echo $currency; ?>'), {
                type: 'pie',
                data: {
                    labels: <?php 
                        echo json_encode(array_map(function($data) { 
                            return $data['name']; 
                        }, $expenseData)); 
                    ?>,
                    datasets: [{
                        data: <?php 
                            $yearTotals = array_map(function($data) use ($currency) {
                                return array_sum($data['totals'][$currency]);
                            }, $expenseData);
                            echo json_encode(array_values($yearTotals));
                        ?>,
                        backgroundColor: [
                            '#2ecc71', '#3498db', '#9b59b6', '#f1c40f', '#e74c3c',
                            '#1abc9c', '#34495e', '#16a085', '#27ae60', '#2980b9'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Expense Distribution by Category - ' + <?php echo $year; ?> + ' (<?php echo $currency; ?>)'
                        },
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            <?php endforeach; ?>
        });
    </script>
</body>
</html>