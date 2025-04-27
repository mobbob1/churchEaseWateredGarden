<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'pastorate'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Get date filters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');
$counselor_id = isset($_GET['counselor_id']) ? $_GET['counselor_id'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query
$query = "
    SELECT 
        cr.*,
        m.first_name,
        m.surname,
        m.contact_number_1,
        u.full_name as counselor_name,
        CASE 
            WHEN cr.status = 'pending' THEN 'warning'
            WHEN cr.status = 'approved' THEN 'success'
            WHEN cr.status = 'completed' THEN 'info'
            ELSE 'danger'
        END as status_class
    FROM counselling_requests cr
    JOIN members m ON cr.member_id = m.id
    LEFT JOIN users u ON cr.assigned_to = u.id
    WHERE cr.created_at BETWEEN ? AND ?
";

$params = [$start_date . ' 00:00:00', $end_date . ' 23:59:59'];

if ($counselor_id) {
    $query .= " AND cr.assigned_to = ?";
    $params[] = $counselor_id;
}

if ($status) {
    $query .= " AND cr.status = ?";
    $params[] = $status;
}

$query .= " ORDER BY cr.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counselors for filter
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name 
    FROM users u 
    JOIN counselling_requests cr ON u.id = cr.assigned_to 
    WHERE u.status = 'active'
    ORDER BY u.full_name
");
$stmt->execute();
$counselors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Counselling History - ChurchEaseSuperb</title>
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
    <link rel="stylesheet" href="../res/assets/css/dataTables.bootstrap4.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Counselling History</h4>
                    </div>
                    
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="get" class="form-inline">
                                        <div class="form-group mx-sm-3">
                                            <label class="mr-2">Start Date</label>
                                            <input type="date" name="start_date" class="form-control" 
                                                   value="<?= htmlspecialchars($start_date) ?>">
                                        </div>
                                        <div class="form-group mx-sm-3">
                                            <label class="mr-2">End Date</label>
                                            <input type="date" name="end_date" class="form-control" 
                                                   value="<?= htmlspecialchars($end_date) ?>">
                                        </div>
                                        <div class="form-group mx-sm-3">
                                            <label class="mr-2">Counselor</label>
                                            <select name="counselor_id" class="form-control">
                                                <option value="">All Counselors</option>
                                                <?php foreach ($counselors as $counselor): ?>
                                                    <option value="<?= $counselor['id'] ?>" 
                                                            <?= $counselor_id == $counselor['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($counselor['full_name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group mx-sm-3">
                                            <label class="mr-2">Status</label>
                                            <select name="status" class="form-control">
                                                <option value="">All Status</option>
                                                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                                                <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- History Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="history-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Contact</th>
                                                    <th>Type</th>
                                                    <th>Counselor</th>
                                                    <th>Appointment</th>
                                                    <th>Status</th>
                                                    <th>Notes</th>
                                                    <th>Created</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($history as $record): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($record['first_name'] . ' ' . $record['surname']) ?></td>
                                                    <td><?= htmlspecialchars($record['contact_number_1']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($record['counselling_type'])) ?></td>
                                                    <td><?= htmlspecialchars($record['counselor_name'] ?? 'Not Assigned') ?></td>
                                                    <td>
                                                        <?php if ($record['appointment_date']): ?>
                                                            <?= date('F j, Y', strtotime($record['appointment_date'])) ?>
                                                            <br>
                                                            <?= date('g:i A', strtotime($record['appointment_time'])) ?>
                                                        <?php else: ?>
                                                            Not Scheduled
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= $record['status_class'] ?>">
                                                            <?= htmlspecialchars(ucfirst($record['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($record['notes']): ?>
                                                            <button class="btn btn-info btn-sm" 
                                                                    onclick="showNotes('<?= htmlspecialchars(addslashes($record['notes'])) ?>')">
                                                                View Notes
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?= date('M j, Y', strtotime($record['created_at'])) ?></td>
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
        </div>
    </div>

    <!-- Notes Modal -->
    <div class="modal fade" id="notesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Counselling Notes</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p id="notesContent"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#history-table').DataTable({
                "order": [[7, "desc"]],
                "pageLength": 25
            });
        });

        function showNotes(notes) {
            $('#notesContent').text(notes);
            $('#notesModal').modal('show');
        }
    </script>
</body>
</html>