<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['hr', 'admin', 'executive_admin_1', 'executive_admin_2', 'pastorate','seer'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Initialize statistics
$stats = [
    'total_events' => 0,
    'upcoming_events' => 0,
    'today_events' => 0,
    'total_attendees' => 0
];

$upcomingEvents = [];
$recentEvents = [];
$eventAttendance = [];

try {
    // Get Event Statistics
    $statsQuery = $pdo->query("
        SELECT 
            COUNT(*) as total_events,
            COUNT(CASE WHEN date >= CURDATE() THEN 1 END) as upcoming_events,
            COUNT(CASE WHEN DATE(date) = CURDATE() THEN 1 END) as today_events,
            (SELECT COUNT(*) FROM event_attendance) as total_attendees
        FROM events
    ");
    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

    // Get Upcoming Events
    $upcomingQuery = $pdo->query("
        SELECT 
            e.*,
            COUNT(ea.id) as registered_attendees,
            et.type_name
        FROM events e
        LEFT JOIN event_attendance ea ON e.id = ea.event_id
        LEFT JOIN event_types et ON e.type_id = et.id
        WHERE e.date >= CURDATE()
        GROUP BY e.id
        ORDER BY e.date ASC
        LIMIT 5
    ");
    $upcomingEvents = $upcomingQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get Recent Events
    $recentQuery = $pdo->query("
        SELECT 
            e.*,
            COUNT(ea.id) as actual_attendees,
            et.type_name
        FROM events e
        LEFT JOIN event_attendance ea ON e.id = ea.event_id
        LEFT JOIN event_types et ON e.type_id = et.id
        WHERE e.date < CURDATE()
        GROUP BY e.id
        ORDER BY e.date DESC
        LIMIT 5
    ");
    $recentEvents = $recentQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get Event Attendance Data for Chart
    $attendanceQuery = $pdo->query("
        SELECT 
            e.title,
            COUNT(ea.id) as attendee_count
        FROM events e
        LEFT JOIN event_attendance ea ON e.id = ea.event_id
        WHERE e.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY e.id
        ORDER BY e.date DESC
        LIMIT 10
    ");
    $eventAttendance = $attendanceQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Dashboard - OutpouringCRM</title>
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
                                <h2 class="text-white pb-2 fw-bold">Events Dashboard</h2>
                                <h5 class="text-white op-7 mb-2">Event Management and Tracking</h5>
                            </div>
                            <div class="ml-md-auto py-2 py-md-0">
                                <a href="create_event.php" class="btn btn-white btn-border btn-round mr-2">Create Event</a>
                                <a href="event_calendar.php" class="btn btn-secondary btn-round">View Calendar</a>
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
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Events</p>
                                                <h4 class="card-title"><?php echo $stats['total_events']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center icon-success">
                                                <i class="fas fa-clock"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Upcoming Events</p>
                                                <h4 class="card-title"><?php echo $stats['upcoming_events']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center icon-warning">
                                                <i class="fas fa-calendar-day"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Today's Events</p>
                                                <h4 class="card-title"><?php echo $stats['today_events']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center icon-info">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Attendees</p>
                                                <h4 class="card-title"><?php echo $stats['total_attendees']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Upcoming Events and Attendance Chart -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Upcoming Events</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Event</th>
                                                    <th>Type</th>
                                                    <th>Date</th>
                                                    <th>Location</th>
                                                    <th>Registered</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($upcomingEvents as $event): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($event['type_name']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($event['date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                                    <td><?php echo $event['registered_attendees']; ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <a href="edit_event.php?id=<?php echo $event['id']; ?>" class="btn btn-warning btn-sm">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                        </div>
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
                                    <div class="card-title">Event Attendance</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="attendanceChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Events -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Recent Events</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Event</th>
                                                    <th>Type</th>
                                                    <th>Date</th>
                                                    <th>Location</th>
                                                    <th>Attendance</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentEvents as $event): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($event['type_name']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($event['date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                                    <td><?php echo $event['actual_attendees']; ?></td>
                                                    <td>
                                                        <a href="event_report.php?id=<?php echo $event['id']; ?>" class="btn btn-info btn-sm">
                                                            <i class="fa fa-file-alt"></i> Report
                                                        </a>
                                                    </td>
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

    <!-- Chart Initialization -->
    <script>
        // Attendance Chart
        const attendanceData = <?php echo json_encode($eventAttendance); ?>;
        
        new Chart(document.getElementById('attendanceChart'), {
            type: 'bar',
            data: {
                labels: attendanceData.map(item => item.title),
                datasets: [{
                    label: 'Attendance',
                    data: attendanceData.map(item => item.attendee_count),
                    backgroundColor: '#1572E8'
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
    </script>
</body>
</html>