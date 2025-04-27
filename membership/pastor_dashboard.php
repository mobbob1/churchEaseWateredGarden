<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}


$allowedRoles = ['seer', 'pastorate'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Initialize statistics
$stats = [
    'total_members' => 0,
    'new_members' => 0,
    'active_members' => 0,
    'pending_counseling' => 0
];

try {
    // Get Member Statistics
    $statsQuery = $pdo->query("
        SELECT 
            COUNT(*) as total_members,
            COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_members,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_members
        FROM members
    ");
    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

    // Get Pending Counseling Sessions
    $counselingQuery = $pdo->prepare("
        SELECT COUNT(*) as pending_counseling
        FROM counseling_sessions
        WHERE pastor_id = ? AND status = 'pending'
    ");
    $counselingQuery->execute([$_SESSION['user_id']]);
    $counselingStats = $counselingQuery->fetch(PDO::FETCH_ASSOC);
    $stats['pending_counseling'] = $counselingStats['pending_counseling'];

    // Get Recent Members
    $recentMembersQuery = $pdo->query("
        SELECT * FROM members 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentMembers = $recentMembersQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get Upcoming Events
    $eventsQuery = $pdo->query("
        SELECT * FROM events 
        WHERE date >= CURDATE() 
        ORDER BY date ASC 
        LIMIT 5
    ");
    $upcomingEvents = $eventsQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pastor Dashboard - OutpouringCRM</title>
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
                                <h2 class="text-white pb-2 fw-bold">Pastor Dashboard</h2>
                                <h5 class="text-white op-7 mb-2">Welcome, Pastor <?php echo htmlspecialchars($_SESSION['full_name']); ?></h5>
                            </div>
                            <div class="ml-md-auto py-2 py-md-0">
                                <a href="counseling_schedule.php" class="btn btn-white btn-border btn-round mr-2">Schedule Counseling</a>
                                <a href="member_management.php" class="btn btn-secondary btn-round">Manage Members</a>
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
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Total Members</p>
                                                <h4 class="card-title"><?php echo $stats['total_members']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Add more statistics cards -->
                    </div>

                    <!-- Recent Members and Events -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Recent Members</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Contact</th>
                                                    <th>Joined Date</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentMembers as $member): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></td>
                                                    <td>
                                                        <a href="view_member.php?id=<?php echo $member['id']; ?>" class="btn btn-link btn-primary btn-lg">
                                                            <i class="fa fa-eye"></i>
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
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Upcoming Events</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Event</th>
                                                    <th>Date</th>
                                                    <th>Location</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($upcomingEvents as $event): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($event['date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                                                    <td>
                                                        <a href="view_event.php?id=<?php echo $event['id']; ?>" class="btn btn-link btn-primary btn-lg">
                                                            <i class="fa fa-eye"></i>
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
</body>
</html>