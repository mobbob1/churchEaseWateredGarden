<?php
require_once '../includes/auth.php';
require_once '../config.php';

$title = "Reports Dashboard";


// Get quick statistics
try {
    // Total Members
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM members");
    $totalMembers = $stmt->fetch()['total'];

    // New Members (Last 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM members WHERE date_joined >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute();
    $newMembers = $stmt->fetch()['total'];

    // Total Events (Last 30 days)
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM events WHERE event_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute();
    $totalEvents = $stmt->fetch()['total'];

    // Total Income (Last 30 days)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM financial_transactions 
                          WHERE type = 'income' AND transaction_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $stmt->execute();
    $totalIncome = $stmt->fetch()['total'];

} catch (PDOException $e) {
    // Log error and set default values
    error_log("Database Error: " . $e->getMessage());
    $totalMembers = $newMembers = $totalEvents = 0;
    $totalIncome = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Reports Dashboard - ChurchSuperb</title>
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

    

        <!-- Fonts and icons -->
        <script src="../res/assets/js/plugin/webfont/webfont.min.js"></script>
    <style>
        .report-card {
            transition: transform .2s;
        }
        .report-card:hover {
            transform: translateY(-5px);
            cursor: pointer;
        }
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
    </style>
       <!-- CSS Files -->
        <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
        <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
</head>
<body>
    <div class="wrapper">
         <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Reports Dashboard</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../index.php"><i class="flaticon-home"></i></a>
                            </li>
                            <li class="separator"><i class="flaticon-right-arrow"></i></li>
                            <li class="nav-item"><a href="#">Reports</a></li>
                            <li class="separator"><i class="flaticon-right-arrow"></i></li>
                            <li class="nav-item"><a href="#">Dashboard</a></li>
                        </ul>
                    </div>

                    <!-- Quick Statistics -->
                    <div class="row">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats stat-card card-primary">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 d-flex align-items-center">
                                            <div class="numbers">
                                                <p class="card-category">Total Members</p>
                                                <h4 class="card-title"><?php echo number_format($totalMembers); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats stat-card card-info">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 d-flex align-items-center">
                                            <div class="numbers">
                                                <p class="card-category">New Members (30d)</p>
                                                <h4 class="card-title"><?php echo number_format($newMembers); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats stat-card card-success">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 d-flex align-items-center">
                                            <div class="numbers">
                                                <p class="card-category">Events (30d)</p>
                                                <h4 class="card-title"><?php echo number_format($totalEvents); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats stat-card card-warning">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 d-flex align-items-center">
                                            <div class="numbers">
                                                <p class="card-category">Income (30d)</p>
                                                <h4 class="card-title">$<?php echo number_format($totalIncome, 2); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Cards -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card report-card" onclick="window.location='membership_growth_report.php'">
                                <div class="card-body">
                                    <h4><i class="fas fa-chart-line mr-2"></i>Membership Growth</h4>
                                    <p class="text-muted">Track membership trends and growth patterns over time</p>
                                    <div class="chart-container" style="height: 150px;">
                                        <canvas id="membershipPreview"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card report-card" onclick="window.location='attendance_patterns.php'">
                                <div class="card-body">
                                    <h4><i class="fas fa-users mr-2"></i>Attendance Patterns</h4>
                                    <p class="text-muted">Analyze attendance trends and participation rates</p>
                                    <div class="chart-container" style="height: 150px;">
                                        <canvas id="attendancePreview"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card report-card" onclick="window.location='financial_trends.php'">
                                <div class="card-body">
                                    <h4><i class="fas fa-chart-bar mr-2"></i>Financial Trends</h4>
                                    <p class="text-muted">Monitor financial performance and trends</p>
                                    <div class="chart-container" style="height: 150px;">
                                        <canvas id="financialPreview"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="card report-card" onclick="window.location='event_participation.php'">
                                <div class="card-body">
                                    <h4><i class="fas fa-calendar-alt mr-2"></i>Event Participation</h4>
                                    <p class="text-muted">Track event attendance and engagement</p>
                                    <div class="chart-container" style="height: 150px;">
                                        <canvas id="eventsPreview"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="card report-card" onclick="window.location='custom_report.php'">
                                <div class="card-body">
                                    <h4><i class="fas fa-tools mr-2"></i>Custom Report Generator</h4>
                                    <p class="text-muted">Create customized reports with specific metrics and filters</p>
                                    <div class="row mt-3">
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <i class="fas fa-chart-pie fa-2x mb-2"></i>
                                                <p>Multiple Chart Types</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <i class="fas fa-filter fa-2x mb-2"></i>
                                                <p>Custom Filters</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="text-center">
                                                <i class="fas fa-file-export fa-2x mb-2"></i>
                                                <p>Export Options</p>
                                            </div>
                                        </div>
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
    <!--   Core JS Files   -->
        <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
        <script src="../res/assets/js/core/popper.min.js"></script>
        <script src="../res/assets/js/core/bootstrap.min.js"></script>
        <!-- jQuery UI -->
        <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
        <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
        <!-- jQuery Scrollbar -->
        <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
        <!-- Atlantis JS -->
        <script src="../res/assets/js/atlantis.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
    $(document).ready(function() {
        // Load preview charts
        loadMembershipPreview();
        loadAttendancePreview();
        loadFinancialPreview();
        loadEventsPreview();
    });

    function loadMembershipPreview() {
        $.ajax({
            url: 'get_membership_data.php',
            type: 'POST',
            data: {
                preview: true
            },
            success: function(response) {
                const data = JSON.parse(response);
                const ctx = document.getElementById('membershipPreview').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels.slice(-6),
                        datasets: [{
                            label: 'Members',
                            data: data.values.slice(-6),
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    }

    function loadAttendancePreview() {
        $.ajax({
            url: 'get_attendance_data.php',
            type: 'POST',
            data: {
                preview: true
            },
            success: function(response) {
                const data = JSON.parse(response);
                const ctx = document.getElementById('attendancePreview').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels.slice(-6),
                        datasets: [{
                            label: 'Attendance',
                            data: data.values.slice(-6),
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgb(54, 162, 235)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    }

    function loadFinancialPreview() {
        $.ajax({
            url: 'get_financial_data.php',
            type: 'POST',
            data: {
                preview: true
            },
            success: function(response) {
                const data = JSON.parse(response);
                const ctx = document.getElementById('financialPreview').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels.slice(-6),
                        datasets: [{
                            label: 'Income',
                            data: data.income.slice(-6),
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    }

    function loadEventsPreview() {
        $.ajax({
            url: 'get_event_data.php',
            type: 'POST',
            data: {
                preview: true
            },
            success: function(response) {
                const data = JSON.parse(response);
                const ctx = document.getElementById('eventsPreview').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels.slice(-6),
                        datasets: [{
                            label: 'Participants',
                            data: data.participants.slice(-6),
                            backgroundColor: 'rgba(255, 159, 64, 0.5)',
                            borderColor: 'rgb(255, 159, 64)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });
            }
        });
    }
    </script>
</body>
</html>