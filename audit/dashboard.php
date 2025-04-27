<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['audit', 'admin', 'executive_admin_1', 'executive_admin_2','seer'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Initialize statistics
$stats = [
    'total_logs' => 0,
    'today_logs' => 0,
    'failed_logins' => 0,
    'system_changes' => 0
];

$recentActivities = [];
$userActivities = [];
$activityTypes = [];

try {
    // Get Audit Statistics
    $statsQuery = $pdo->query("
        SELECT 
            COUNT(*) as total_logs,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_logs,
            COUNT(CASE WHEN action = 'login' AND status = 'failed' THEN 1 END) as failed_logins,
            COUNT(CASE WHEN category = 'system' THEN 1 END) as system_changes
        FROM audit_logs
    ");
    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

    // Get Recent Activities
    $recentQuery = $pdo->query("
        SELECT 
            al.*,
            u.username,
            u.full_name
        FROM audit_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 15
    ");
    $recentActivities = $recentQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get User Activity Distribution
    $userActivityQuery = $pdo->query("
        SELECT 
            u.username,
            COUNT(al.id) as activity_count
        FROM users u
        LEFT JOIN audit_logs al ON u.id = al.user_id
        WHERE al.id IS NOT NULL
        GROUP BY u.id
        ORDER BY activity_count DESC
        LIMIT 10
    ");
    $userActivities = $userActivityQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get Activity Types Distribution
    $activityTypeQuery = $pdo->query("
        SELECT 
            action,
            COUNT(*) as count,
            COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_count
        FROM audit_logs
        GROUP BY action
        ORDER BY count DESC
    ");
    $activityTypes = $activityTypeQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Dashboard - OutpouringCRM</title>
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="panel-header bg-primary-gradient">
                    <div class="page-inner py-5">
                        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
                            <div>
                                <h2 class="text-white pb-2 fw-bold">Audit Dashboard</h2>
                                <h5 class="text-white op-7 mb-2">System Activity Monitoring</h5>
                            </div>
                            <div class="ml-md-auto py-2 py-md-0">
                                <a href="export_logs.php" class="btn btn-white btn-border btn-round mr-2">Export Logs</a>
                                <a href="audit_settings.php" class="btn btn-secondary btn-round">Settings</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-inner mt--5">
                    <!-- Statistics Cards -->
                    <div class="row mt--2">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center icon-primary">
                                                <i class="fas fa-history"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Logs</p>
                                                <h4 class="card-title"><?php echo $stats['total_logs']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-info card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Today's Logs</p>
                                                <h4 class="card-title"><?php echo $stats['today_logs']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-danger card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Failed Logins</p>
                                                <h4 class="card-title"><?php echo $stats['failed_logins']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-warning card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-cogs"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">System Changes</p>
                                                <h4 class="card-title"><?php echo $stats['system_changes']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Charts -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Recent Activities</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>User</th>
                                                    <th>Action</th>
                                                    <th>Category</th>
                                                    <th>Status</th>
                                                    <th>Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentActivities as $activity): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i:s', strtotime($activity['created_at'])); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['username']); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                                    <td><?php echo htmlspecialchars($activity['category']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $activity['status'] === 'success' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($activity['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-link" data-toggle="tooltip" data-placement="top" 
                                                                title="<?php echo htmlspecialchars($activity['details']); ?>">
                                                            <i class="fa fa-info-circle"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">User Activity Distribution</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="userActivityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Types -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Activity Types Overview</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="activityTypesChart"></canvas>
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

    <!-- Charts Initialization -->
    <script>
        // User Activity Chart
        const userData = <?php echo json_encode($userActivities); ?>;
        
        new Chart(document.getElementById('userActivityChart'), {
            type: 'pie',
            data: {
                labels: userData.map(item => item.username),
                datasets: [{
                    data: userData.map(item => item.activity_count),
                    backgroundColor: ['#1572E8', '#31CE36', '#F25961', '#FF9E27', '#48ABF7']
                }]
            },
            options: {
                responsive: true
            }
        });

        // Activity Types Chart
        const activityData = <?php echo json_encode($activityTypes); ?>;
        
        new Chart(document.getElementById('activityTypesChart'), {
            type: 'bar',
            data: {
                labels: activityData.map(item => item.action),
                datasets: [
                    {
                        label: 'Successful',
                        data: activityData.map(item => item.count - item.failed_count),
                        backgroundColor: '#31CE36'
                    },
                    {
                        label: 'Failed',
                        data: activityData.map(item => item.failed_count),
                        backgroundColor: '#F25961'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        stacked: true
                    },
                    y: {
                        stacked: true,
                        beginAtZero: true
                    }
                }
            }
        });

        // Initialize tooltips
        $(function () {
            $('[data-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>