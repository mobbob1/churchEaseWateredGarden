<?php
require_once '../includes/auth.php';
require_once '../config.php';

$title = "Attendance Patterns Report";


// Handle export
if (isset($_POST['export'])) {
    $type = $_POST['export'];
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-3 months'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_patterns_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Week', 'Total Attendance', 'Male', 'Female', 'Average Age']);
    
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(a.attendance_date, '%Y-%U') as week,
            COUNT(DISTINCT a.member_id) as total_attendance,
            SUM(CASE WHEN m.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN m.gender = 'Female' THEN 1 ELSE 0 END) as female_count,
            AVG(TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE())) as avg_age
        FROM attendance a
        LEFT JOIN members m ON a.member_id = m.id
        WHERE a.attendance_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(a.attendance_date, '%Y-%U')
        ORDER BY week
    ");
    
    $stmt->execute([$startDate, $endDate]);
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['week'],
            $row['total_attendance'],
            $row['male_count'],
            $row['female_count'],
            round($row['avg_age'], 1)
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
    <title>Attendance Patterns - ChurchSuperb</title>
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
                        <h4 class="page-title">Attendance Patterns</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../index.php"><i class="flaticon-home"></i></a>
                            </li>
                            <li class="separator"><i class="flaticon-right-arrow"></i></li>
                            <li class="nav-item"><a href="#">Reports</a></li>
                            <li class="separator"><i class="flaticon-right-arrow"></i></li>
                            <li class="nav-item"><a href="#">Attendance Patterns</a></li>
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
                                                           value="<?php echo date('Y-m-d', strtotime('-3 months')); ?>">
                                                    <input type="date" class="form-control" id="endDate" 
                                                           value="<?php echo date('Y-m-d'); ?>">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-primary" onclick="updateChart()">Update</button>
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
                                    <div class="chart-container">
                                        <canvas id="attendanceChart"></canvas>
                                    </div>
                                    <div class="table-responsive mt-4">
                                        <table id="attendanceTable" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Week</th>
                                                    <th>Total Attendance</th>
                                                    <th>Male</th>
                                                    <th>Female</th>
                                                    <th>Average Age</th>
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
    let attendanceChart;

    $(document).ready(function() {
        initializeChart();
        initializeTable();
        
        $('#exportStartDate').val($('#startDate').val());
        $('#exportEndDate').val($('#endDate').val());
    });

    function initializeChart() {
        const ctx = document.getElementById('attendanceChart').getContext('2d');
        attendanceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Total Attendance',
                    data: [],
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Weekly Attendance Pattern'
                    }
                }
            }
        });
        
        updateChart();
    }

    function updateChart() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        $('#exportStartDate').val(startDate);
        $('#exportEndDate').val(endDate);
        
        $.ajax({
            url: 'get_attendance_data.php',
            type: 'POST',
            data: {
                start_date: startDate,
                end_date: endDate
            },
            success: function(response) {
                const data = JSON.parse(response);
                attendanceChart.data.labels = data.labels;
                attendanceChart.data.datasets[0].data = data.values;
                attendanceChart.update();
                
                // Update table
                $('#attendanceTable').DataTable().clear().rows.add(data.tableData).draw();
            }
        });
    }

    function initializeTable() {
        $('#attendanceTable').DataTable({
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