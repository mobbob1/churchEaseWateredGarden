<?php
require_once '../includes/auth.php';
require_once '../config.php';

$title = "Financial Trends Report";


// Handle export
if (isset($_POST['export'])) {
    $type = $_POST['export'];
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-1 year'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="financial_trends_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Month', 'Income', 'Expenses', 'Net', 'Tithes', 'Offerings', 'Other Income']);
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses,
            SUM(CASE WHEN category = 'tithe' THEN amount ELSE 0 END) as tithes,
            SUM(CASE WHEN category = 'offering' THEN amount ELSE 0 END) as offerings,
            SUM(CASE WHEN category = 'other' AND type = 'income' THEN amount ELSE 0 END) as other_income
        FROM financial_transactions
        WHERE transaction_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month
    ");
    
    $stmt->execute([$startDate, $endDate]);
    while ($row = $stmt->fetch()) {
        $net = $row['income'] - $row['expenses'];
        fputcsv($output, [
            $row['month'],
            $row['income'],
            $row['expenses'],
            $net,
            $row['tithes'],
            $row['offerings'],
            $row['other_income']
        ]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Financial Trends - ChurchSuperb</title>
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.css"/>
</head>
<body>
    <div class="wrapper">
         <?php include '../res/main_header.php'; ?>
            <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Financial Trends</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../index.php"><i class="flaticon-home"></i></a>
                            </li>
                            <li class="separator"><i class="flaticon-right-arrow"></i></li>
                            <li class="nav-item"><a href="#">Reports</a></li>
                            <li class="separator"><i class="flaticon-right-arrow"></i></li>
                            <li class="nav-item"><a href="#">Financial Trends</a></li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Date Range</label>
                                                <div class="input-group">
                                                    <input type="date" class="form-control" id="startDate" 
                                                           value="<?php echo date('Y-m-d', strtotime('-1 year')); ?>">
                                                    <input type="date" class="form-control" id="endDate" 
                                                           value="<?php echo date('Y-m-d'); ?>">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-primary" onclick="updateCharts()">Update</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Export</label>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle w-100" 
                                                            type="button" data-toggle="dropdown">
                                                        Export Data
                                                    </button>
                                                    <div class="dropdown-menu w-100">
                                                        <form method="POST" class="export-buttons">
                                                            <input type="hidden" id="exportStartDate" name="start_date">
                                                            <input type="hidden" id="exportEndDate" name="end_date">
                                                            <button type="submit" name="export" value="csv" 
                                                                    class="dropdown-item">Export to CSV</button>
                                                        </form>
                                                        <a class="dropdown-item" onclick="exportToExcel()">Export to Excel</a>
                                                        <a class="dropdown-item" onclick="exportToPDF()">Export to PDF</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <canvas id="incomeExpenseChart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="chart-container">
                                                <canvas id="incomeBreakdownChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="table-responsive mt-4">
                                        <table id="financialTable" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Month</th>
                                                    <th>Income</th>
                                                    <th>Expenses</th>
                                                    <th>Net</th>
                                                    <th>Tithes</th>
                                                    <th>Offerings</th>
                                                    <th>Other Income</th>
                                                </tr>
                                            </thead>
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- DataTables -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
    
    <script>
    let incomeExpenseChart, incomeBreakdownChart;

    $(document).ready(function() {
        initializeCharts();
        initializeTable();
        
        $('#exportStartDate').val($('#startDate').val());
        $('#exportEndDate').val($('#endDate').val());
    });

    function initializeCharts() {
        // Income vs Expense Chart
        const ctx1 = document.getElementById('incomeExpenseChart').getContext('2d');
        incomeExpenseChart = new Chart(ctx1, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Income',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }, {
                    label: 'Expenses',
                    data: [],
                    borderColor: 'rgb(255, 99, 132)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Income vs Expenses'
                    }
                }
            }
        });

        // Income Breakdown Chart
        const ctx2 = document.getElementById('incomeBreakdownChart').getContext('2d');
        incomeBreakdownChart = new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Tithes', 'Offerings', 'Other'],
                datasets: [{
                    data: [],
                    backgroundColor: [
                        'rgb(255, 99, 132)',
                        'rgb(54, 162, 235)',
                        'rgb(255, 205, 86)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Income Breakdown'
                    }
                }
            }
        });
        
        updateCharts();
    }

    function updateCharts() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        $('#exportStartDate').val(startDate);
        $('#exportEndDate').val(endDate);
        
        $.ajax({
            url: 'get_financial_data.php',
            type: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                const data = JSON.parse(response);
                
                // Update Income vs Expense Chart
                incomeExpenseChart.data.labels = data.labels;
                incomeExpenseChart.data.datasets[0].data = data.income;
                incomeExpenseChart.data.datasets[1].data = data.expenses;
                incomeExpenseChart.update();
                
                // Update Income Breakdown Chart
                incomeBreakdownChart.data.datasets[0].data = [
                    data.totalTithes,
                    data.totalOfferings,
                    data.totalOtherIncome
                ];
                incomeBreakdownChart.update();
                
                // Update table
                $('#financialTable').DataTable().clear().rows.add(data.tableData).draw();
            }
        });
    }

    function initializeTable() {
        $('#financialTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 12
        });
    }

    function exportToExcel() {
        // Implement Excel export
    }

    function exportToPDF() {
        // Implement PDF export
    }
    </script>
</body>
</html>