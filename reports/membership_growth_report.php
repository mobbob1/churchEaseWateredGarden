<?php
require_once '../includes/auth.php';
require_once '../includes/AuditLogger.php';
require_once '../config.php';

$logger = new AuditLogger($pdo);

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has appropriate role
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: ../index.php?error=unauthorized');
    exit();
}

// Handle export
if (isset($_POST['export'])) {
    $type = $_POST['export'];
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-1 year'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="membership_growth_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Month', 'New Members', 'Total Members', 'Growth Rate']);
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_joined, '%Y-%m') as month,
            COUNT(*) as new_members,
            (
                SELECT COUNT(*) 
                FROM members 
                WHERE date_joined <= LAST_DAY(m.date_joined)
            ) as total_members
        FROM members m
        WHERE date_joined BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(date_joined, '%Y-%m')
        ORDER BY month
    ");
    
    $stmt->execute([$startDate, $endDate]);
    
    while ($row = $stmt->fetch()) {
        $growthRate = $row['total_members'] > 0 ? 
            ($row['new_members'] / $row['total_members'] * 100) : 0;
            
        fputcsv($output, [
            $row['month'],
            $row['new_members'],
            $row['total_members'],
            number_format($growthRate, 2) . '%'
        ]);
    }
    
    fclose($output);
    exit;
}

$title = "Membership Growth Report";
include '../res/main_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Membership Growth Report - ChurchSuperb</title>
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
                        <h4 class="page-title">Membership Growth Report</h4>
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
                                <a href="#">Reports</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Membership Growth</a>
                            </li>
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
                                                        <button class="btn btn-primary" onclick="updateChart()">
                                                            Update
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Export</label>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle w-100" 
                                                            type="button" id="exportDropdown" 
                                                            data-toggle="dropdown">
                                                        Export Data
                                                    </button>
                                                    <div class="dropdown-menu w-100">
                                                        <form method="POST" class="export-buttons">
                                                            <input type="hidden" id="exportStartDate" name="start_date">
                                                            <input type="hidden" id="exportEndDate" name="end_date">
                                                            <button type="submit" name="export" value="csv" 
                                                                    class="dropdown-item">Export to CSV</button>
                                                        </form>
                                                        <a class="dropdown-item" onclick="exportToExcel()">
                                                            Export to Excel
                                                        </a>
                                                        <a class="dropdown-item" onclick="exportToPDF()">
                                                            Export to PDF
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="membershipGrowthChart"></canvas>
                                    </div>
                                    <div class="table-responsive mt-4">
                                        <table id="growthTable" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Month</th>
                                                    <th>New Members</th>
                                                    <th>Total Members</th>
                                                    <th>Growth Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("
                                                    SELECT 
                                                        DATE_FORMAT(date_joined, '%Y-%m') as month,
                                                        COUNT(*) as new_members,
                                                        (
                                                            SELECT COUNT(*) 
                                                            FROM members 
                                                            WHERE date_joined <= LAST_DAY(m.date_joined)
                                                        ) as total_members
                                                    FROM members m
                                                    GROUP BY DATE_FORMAT(date_joined, '%Y-%m')
                                                    ORDER BY month DESC
                                                ");
                                                
                                                while ($row = $stmt->fetch()) {
                                                    $growthRate = $row['total_members'] > 0 ? 
                                                        ($row['new_members'] / $row['total_members'] * 100) : 0;
                                                        
                                                    echo "<tr>";
                                                    echo "<td>" . $row['month'] . "</td>";
                                                    echo "<td>" . $row['new_members'] . "</td>";
                                                    echo "<td>" . $row['total_members'] . "</td>";
                                                    echo "<td>" . number_format($growthRate, 2) . "%</td>";
                                                    echo "</tr>";
                                                }
                                                ?>
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

    <!--   Core JS Files   -->
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
    $(document).ready(function() {
        initializeChart();
        initializeTable();
        
        // Set export date values
        $('#exportStartDate').val($('#startDate').val());
        $('#exportEndDate').val($('#endDate').val());
    });

    let growthChart;

    function initializeChart() {
        const ctx = document.getElementById('membershipGrowthChart').getContext('2d');
        growthChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'New Members',
                    data: [],
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Membership Growth Over Time'
                    }
                }
            }
        });
        
        updateChart();
    }

    function initializeTable() {
        $('#growthTable').DataTable({
            "order": [[0, "desc"]],
            "pageLength": 12
        });
    }

    function updateChart() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        $('#exportStartDate').val(startDate);
        $('#exportEndDate').val(endDate);
        
        $.ajax({
            url: 'get_membership_data.php',
            type: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                const data = JSON.parse(response);
                growthChart.data.labels = data.labels;
                growthChart.data.datasets[0].data = data.values;
                growthChart.update();
            }
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