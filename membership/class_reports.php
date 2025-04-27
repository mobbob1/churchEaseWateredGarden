<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['class_leader', 'seer', 'admin'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: ../access_denied.php');
    exit();
}

// Initialize audit logger
$logger = new AuditLogger($pdo);

// Get the class leader's assigned class
$stmt = $pdo->prepare("SELECT bible_class_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classLeaderData = $stmt->fetch();
$bible_class_id = $classLeaderData['bible_class_id'];

if (!$bible_class_id && $_SESSION['user_role'] === 'class_leader') {
    $_SESSION['message'] = "<div class='alert alert-danger'>You are not assigned to any bible class.</div>";
    header('Location: ../dashboard.php');
    exit();
}

// Handle report generation
if (isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    try {
        switch ($report_type) {
            case 'attendance':
                $stmt = $pdo->prepare("
                    SELECT a.attendance_date, COUNT(a.member_id) as total_attendance,
                           COUNT(CASE WHEN m.gender = 'Male' THEN 1 END) as male_count,
                           COUNT(CASE WHEN m.gender = 'Female' THEN 1 END) as female_count
                    FROM attendance a
                    JOIN members m ON a.member_id = m.id
                    WHERE m.bible_class_id = ? 
                    AND a.attendance_date BETWEEN ? AND ?
                    GROUP BY a.attendance_date
                    ORDER BY a.attendance_date DESC
                ");
                break;
                
            case 'growth':
                $stmt = $pdo->prepare("
                    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
                           COUNT(*) as new_members,
                           COUNT(CASE WHEN status = 'active' THEN 1 END) as active_members
                    FROM members
                    WHERE bible_class_id = ?
                    AND created_at BETWEEN ? AND ?
                    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                    ORDER BY month DESC
                ");
                break;
                
            case 'demographics':
                $stmt = $pdo->prepare("
                    SELECT 
                        gender,
                        COUNT(*) as total,
                        AVG(YEAR(CURRENT_DATE) - YEAR(date_of_birth)) as avg_age
                    FROM members
                    WHERE bible_class_id = ?
                    AND created_at BETWEEN ? AND ?
                    GROUP BY gender
                ");
                break;
        }
        
        $stmt->execute([$bible_class_id, $start_date, $end_date]);
        $report_data = $stmt->fetchAll();
        
        // Log report generation
        $logger->log(
            'report_generation',
            'class_reports',
            $_SESSION['user_id'],
            "Generated $report_type report for class ID: $bible_class_id",
            null,
            ['report_type' => $report_type, 'start_date' => $start_date, 'end_date' => $end_date]
        );
        
    } catch (PDOException $e) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Error generating report: " . $e->getMessage() . "</div>";
    }
}

// Get class details
if ($bible_class_id) {
    $stmt = $pdo->prepare("SELECT class_name FROM bible_classes WHERE id = ?");
    $stmt->execute([$bible_class_id]);
    $class_details = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Reports - ChurchEase</title>
    <?php include '../res/assets.php'; ?>
    <!-- Add Chart.js -->
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
                        <h4 class="page-title">Class Reports</h4>
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
                                <a href="#">Reports</a>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if(isset($class_details)): ?>
                    <div class="alert alert-info">
                        Viewing reports for class: <?php echo htmlspecialchars($class_details['class_name']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Generate Report</h4>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="form-horizontal">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Report Type</label>
                                                    <select name="report_type" class="form-control" required>
                                                        <option value="attendance">Attendance Report</option>
                                                        <option value="growth">Growth Report</option>
                                                        <option value="demographics">Demographics Report</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Start Date</label>
                                                    <input type="date" name="start_date" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>End Date</label>
                                                    <input type="date" name="end_date" class="form-control" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <button type="submit" name="generate_report" class="btn btn-primary btn-block">
                                                        Generate Report
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($report_data) && !empty($report_data)): ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Report Results</h4>
                                    <div class="card-category">
                                        <?php echo ucfirst($report_type); ?> Report from <?php echo $start_date; ?> to <?php echo $end_date; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if ($report_type === 'attendance'): ?>
                                        <canvas id="attendanceChart"></canvas>
                                        <div class="table-responsive mt-3">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Total Attendance</th>
                                                        <th>Male</th>
                                                        <th>Female</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($report_data as $row): ?>
                                                    <tr>
                                                        <td><?php echo $row['attendance_date']; ?></td>
                                                        <td><?php echo $row['total_attendance']; ?></td>
                                                        <td><?php echo $row['male_count']; ?></td>
                                                        <td><?php echo $row['female_count']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                    <?php elseif ($report_type === 'growth'): ?>
                                        <canvas id="growthChart"></canvas>
                                        <div class="table-responsive mt-3">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Month</th>
                                                        <th>New Members</th>
                                                        <th>Active Members</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($report_data as $row): ?>
                                                    <tr>
                                                        <td><?php echo $row['month']; ?></td>
                                                        <td><?php echo $row['new_members']; ?></td>
                                                        <td><?php echo $row['active_members']; ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                    <?php elseif ($report_type === 'demographics'): ?>
                                        <canvas id="demographicsChart"></canvas>
                                        <div class="table-responsive mt-3">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Gender</th>
                                                        <th>Total</th>
                                                        <th>Average Age</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($report_data as $row): ?>
                                                    <tr>
                                                        <td><?php echo $row['gender']; ?></td>
                                                        <td><?php echo $row['total']; ?></td>
                                                        <td><?php echo round($row['avg_age'], 1); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <?php include '../res/scripts.php'; ?>
    
    <?php if (isset($report_data) && !empty($report_data)): ?>
    <script>
        $(document).ready(function() {
            <?php if ($report_type === 'attendance'): ?>
            // Attendance Chart
            var ctx = document.getElementById('attendanceChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode(array_column($report_data, 'attendance_date')); ?>,
                    datasets: [{
                        label: 'Total Attendance',
                        data: <?php echo json_encode(array_column($report_data, 'total_attendance')); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
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
            
            <?php elseif ($report_type === 'growth'): ?>
            // Growth Chart
            var ctx = document.getElementById('growthChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($report_data, 'month')); ?>,
                    datasets: [{
                        label: 'New Members',
                        data: <?php echo json_encode(array_column($report_data, 'new_members')); ?>,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgb(75, 192, 192)',
                        borderWidth: 1
                    }]
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
            
            <?php elseif ($report_type === 'demographics'): ?>
            // Demographics Chart
            var ctx = document.getElementById('demographicsChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: <?php echo json_encode(array_column($report_data, 'gender')); ?>,
                    datasets: [{
                        data: <?php echo json_encode(array_column($report_data, 'total')); ?>,
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 99, 132, 0.2)'
                        ],
                        borderColor: [
                            'rgb(54, 162, 235)',
                            'rgb(255, 99, 132)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true
                }
            });
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>
</body>
</html>