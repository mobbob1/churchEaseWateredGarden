<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['secretary', 'admin', 'executive_admin_1', 'executive_admin_2', 'pastorate','admin_assistant'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Initialize statistics
$stats = [
    'total_members' => 0,
    'pending_appointments' => 0,
    'today_appointments' => 0,
    'pending_documents' => 0
];

$upcomingAppointments = [];
$recentDocuments = [];
$todayTasks = [];
$memberRequests = [];

try {
    // Get Secretary Statistics
    $statsQuery = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM members) as total_members,
            (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_appointments,
            (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()) as today_appointments,
            (SELECT COUNT(*) FROM documents WHERE status = 'pending') as pending_documents
    ");
    $stats = $statsQuery->fetch(PDO::FETCH_ASSOC);

    // Get Upcoming Appointments
    $appointmentQuery = $pdo->query("
        SELECT 
            a.*,
            m.name as member_name,
            m.phone as member_phone,
            u.full_name as staff_name
        FROM appointments a
        LEFT JOIN members m ON a.member_id = m.id
        LEFT JOIN users u ON a.staff_id = u.id
        WHERE a.appointment_date >= CURDATE()
        ORDER BY a.appointment_date ASC
        LIMIT 5
    ");
    $upcomingAppointments = $appointmentQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get Recent Documents
    $documentQuery = $pdo->query("
        SELECT 
            d.*,
            m.name as member_name,
            dt.type_name
        FROM documents d
        LEFT JOIN members m ON d.member_id = m.id
        LEFT JOIN document_types dt ON d.type_id = dt.id
        ORDER BY d.created_at DESC
        LIMIT 5
    ");
    $recentDocuments = $documentQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get Today's Tasks
    $taskQuery = $pdo->query("
        SELECT 
            t.*,
            u.full_name as assigned_by
        FROM tasks t
        LEFT JOIN users u ON t.assigned_by = u.id
        WHERE DATE(t.due_date) = CURDATE()
        ORDER BY t.priority DESC
        LIMIT 5
    ");
    $todayTasks = $taskQuery->fetchAll(PDO::FETCH_ASSOC);

    // Get Member Requests
    $requestQuery = $pdo->query("
        SELECT 
            mr.*,
            m.name as member_name,
            m.phone as member_phone,
            rt.type_name as request_type
        FROM member_requests mr
        LEFT JOIN members m ON mr.member_id = m.id
        LEFT JOIN request_types rt ON mr.type_id = rt.id
        WHERE mr.status = 'pending'
        ORDER BY mr.created_at DESC
        LIMIT 5
    ");
    $memberRequests = $requestQuery->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard - OutpouringCRM</title>
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
                                <h2 class="text-white pb-2 fw-bold">Secretary Dashboard</h2>
                                <h5 class="text-white op-7 mb-2">Administrative Management</h5>
                            </div>
                            <div class="ml-md-auto py-2 py-md-0">
                                <a href="schedule_appointment.php" class="btn btn-white btn-border btn-round mr-2">Schedule Appointment</a>
                                <a href="manage_documents.php" class="btn btn-secondary btn-round">Manage Documents</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-inner mt--5">
                    <!-- Statistics Cards -->
                    <div class="row mt--2">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-primary card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
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
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-info card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="far fa-calendar-check"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Today's Appointments</p>
                                                <h4 class="card-title"><?php echo $stats['today_appointments']; ?></h4>
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
                                                <i class="fas fa-clock"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Pending Appointments</p>
                                                <h4 class="card-title"><?php echo $stats['pending_appointments']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-success card-round">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Pending Documents</p>
                                                <h4 class="card-title"><?php echo $stats['pending_documents']; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Tasks and Upcoming Appointments -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Today's Tasks</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Task</th>
                                                    <th>Priority</th>
                                                    <th>Due Time</th>
                                                    <th>Assigned By</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($todayTasks as $task): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            echo $task['priority'] === 'high' ? 'danger' : 
                                                                ($task['priority'] === 'medium' ? 'warning' : 'info'); 
                                                        ?>">
                                                            <?php echo ucfirst($task['priority']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('H:i', strtotime($task['due_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($task['assigned_by']); ?></td>
                                                    <td>
                                                        <button onclick="completeTask(<?php echo $task['id']; ?>)" 
                                                                class="btn btn-success btn-sm">
                                                            <i class="fa fa-check"></i>
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
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Upcoming Appointments</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Member</th>
                                                    <th>Purpose</th>
                                                    <th>Staff</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($upcomingAppointments as $appointment): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($appointment['appointment_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['member_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['purpose']); ?></td>
                                                    <td><?php echo htmlspecialchars($appointment['staff_name']); ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="edit_appointment.php?id=<?php echo $appointment['id']; ?>" 
                                                               class="btn btn-warning btn-sm">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            <button onclick="confirmAppointment(<?php echo $appointment['id']; ?>)" 
                                                                    class="btn btn-success btn-sm">
                                                                <i class="fa fa-check"></i>
                                                            </button>
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
                    </div>

                    <!-- Recent Documents and Member Requests -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Recent Documents</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Document</th>
                                                    <th>Type</th>
                                                    <th>Member</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentDocuments as $document): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($document['title']); ?></td>
                                                    <td><?php echo htmlspecialchars($document['type_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($document['member_name']); ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php 
                                                            echo $document['status'] === 'approved' ? 'success' : 
                                                                ($document['status'] === 'pending' ? 'warning' : 'danger'); 
                                                        ?>">
                                                            <?php echo ucfirst($document['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view_document.php?id=<?php echo $document['id']; ?>" 
                                                               class="btn btn-primary btn-sm">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <a href="process_document.php?id=<?php echo $document['id']; ?>" 
                                                               class="btn btn-info btn-sm">
                                                                <i class="fa fa-cog"></i>
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
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Member Requests</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Request Type</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($memberRequests as $request): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($request['member_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($request['request_type']); ?></td>
                                                    <td><?php echo date('Y-m-d', strtotime($request['created_at'])); ?></td>
                                                    <td>
                                                        <span class="badge badge-warning">Pending</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="view_request.php?id=<?php echo $request['id']; ?>" 
                                                               class="btn btn-primary btn-sm">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <button onclick="processRequest(<?php echo $request['id']; ?>)" 
                                                                    class="btn btn-success btn-sm">
                                                                <i class="fa fa-check"></i>
                                                            </button>
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

    <!-- Page Specific Scripts -->
    <script>
        // Complete Task
        function completeTask(taskId) {
            if (confirm('Mark this task as complete?')) {
                window.location.href = `complete_task.php?id=${taskId}`;
            }
        }

        // Confirm Appointment
        function confirmAppointment(appointmentId) {
            if (confirm('Confirm this appointment?')) {
                window.location.href = `confirm_appointment.php?id=${appointmentId}`;
            }
        }

        // Process Member Request
        function processRequest(requestId) {
            if (confirm('Process this member request?')) {
                window.location.href = `process_request.php?id=${requestId}`;
            }
        }
    </script>
</body>
</html>