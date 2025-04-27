<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_request'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE counselling_requests 
                SET 
                    status = ?,
                    assigned_to = ?,
                    appointment_date = ?,
                    appointment_time = ?,
                    venue = ?,
                    notes = ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $_POST['status'],
                $_POST['assigned_to'] ?: null,
                $_POST['appointment_date'] ?: null,
                $_POST['appointment_time'] ?: null,
                $_POST['venue'],
                $_POST['notes'],
                $_POST['request_id']
            ]);

            // Log the action
            $logger->log(
                'update',
                'counselling',
                $_POST['request_id'],
                "Updated counselling request status to {$_POST['status']}"
            );

            // Send notification to member
            $stmt = $pdo->prepare("
                INSERT INTO notifications (
                    member_id,
                    notification_text,
                    notification_date
                ) VALUES (
                    (SELECT member_id FROM counselling_requests WHERE id = ?),
                    ?,
                    NOW()
                )
            ");

            $notification_text = "Your counselling request has been {$_POST['status']}";
            if ($_POST['status'] === 'approved') {
                $notification_text .= ". Appointment scheduled for " . 
                    date('F j, Y g:i A', strtotime($_POST['appointment_date'] . ' ' . $_POST['appointment_time'])) .
                    " at {$_POST['venue']}";
            }

            $stmt->execute([$_POST['request_id'], $notification_text]);
            
            $success_message = "Counselling request updated successfully.";
        } catch (PDOException $e) {
            $error_message = "Failed to update request: " . $e->getMessage();
        }
    }
}

// Get all counselling requests with member details
$stmt = $pdo->prepare("
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
    ORDER BY 
        CASE cr.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'completed' THEN 3
            ELSE 4
        END,
        cr.created_at DESC
");
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all available counselors
$stmt = $pdo->prepare("
    SELECT id, role_key 
    FROM roles 
    WHERE role_key IN ('class_leader', 'secretary') 
    ORDER BY name
");
$stmt->execute();
$counselors = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Manage Counselling - ChurchEaseSuperb</title>
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
    <!-- Chart.js -->
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
                        <h4 class="page-title">Manage Counselling Requests</h4>
                    </div>
                    
                    <?php if (isset($success_message)): ?>
                        <div class="alert alert-success"><?= $success_message ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?= $error_message ?></div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Counselling Requests</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="counselling-requests" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Contact</th>
                                                    <th>Type</th>
                                                    <th>Preferred Date</th>
                                                    <th>Status</th>
                                                    <th>Assigned To</th>
                                                    <th>Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($requests as $request): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($request['first_name'] . ' ' . $request['surname']) ?></td>
                                                    <td><?= htmlspecialchars($request['contact_number_1']) ?></td>
                                                    <td><?= htmlspecialchars(ucfirst($request['counselling_type'])) ?></td>
                                                    <td>
                                                        <?= date('F j, Y', strtotime($request['preferred_date'])) ?>
                                                        <br>
                                                        <?= date('g:i A', strtotime($request['preferred_time'])) ?>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= $request['status_class'] ?>">
                                                            <?= htmlspecialchars(ucfirst($request['status'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($request['counselor_name'] ?? 'Not Assigned') ?></td>
                                                    <td><?= date('M j, Y', strtotime($request['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-primary btn-sm" 
                                                                data-toggle="modal" 
                                                                data-target="#updateModal"
                                                                data-request='<?= json_encode($request) ?>'>
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-info btn-sm view-details"
                                                                data-request='<?= json_encode($request) ?>'>
                                                            <i class="fa fa-eye"></i>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Modal -->
    <div class="modal fade" id="updateModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Counselling Request</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="post">
                    <div class="modal-body">
                        <input type="hidden" name="request_id" id="request_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" class="form-control" required>
                                        <option value="pending">Pending</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Assign To</label>
                                    <select name="assigned_to" class="form-control">
                                        <option value="">Select Counselor</option>
                                        <?php foreach ($counselors as $counselor): ?>
                                            <option value="<?= $counselor['id'] ?>">
                                                <?= htmlspecialchars($counselor['full_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Appointment Date</label>
                                    <input type="date" name="appointment_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Appointment Time</label>
                                    <input type="time" name="appointment_time" class="form-control">
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Venue</label>
                            <input type="text" name="venue" class="form-control">
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="request-details mt-3">
                            <h6>Request Details:</h6>
                            <p id="request-description"></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" name="update_request" class="btn btn-primary">Update Request</button>
                    </div>
                </form>
            </div>
               <?php include '../res/footer.php'; ?>
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
            // Initialize DataTable
            $('#counselling-requests').DataTable({
                "order": [[6, "desc"]],
                "pageLength": 25
            });

            // Handle Update Modal
            $('#updateModal').on('show.bs.modal', function(e) {
                const button = $(e.relatedTarget);
                const request = button.data('request');
                
                $('#request_id').val(request.id);
                $('select[name="status"]').val(request.status);
                $('select[name="assigned_to"]').val(request.assigned_to);
                $('input[name="appointment_date"]').val(request.appointment_date);
                $('input[name="appointment_time"]').val(request.appointment_time);
                $('input[name="venue"]').val(request.venue);
                $('textarea[name="notes"]').val(request.notes);
                $('#request-description').text(request.description);
            });

            // Handle View Details
            $('.view-details').click(function() {
                const request = $(this).data('request');
                // You can implement a detailed view modal or redirect to a details page
                alert(
                    `Request Details:\n\n` +
                    `Member: ${request.first_name} ${request.surname}\n` +
                    `Type: ${request.counselling_type}\n` +
                    `Description: ${request.description}\n` +
                    `Preferred Date: ${request.preferred_date}\n` +
                    `Preferred Time: ${request.preferred_time}\n` +
                    `Status: ${request.status}\n` +
                    `Notes: ${request.notes || 'No notes'}`
                );
            });
        });
    </script>
</body>
</html>