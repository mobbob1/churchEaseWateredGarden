<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';

// Check if user is logged in and is a member
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'member') {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Get member ID
$stmt = $pdo->prepare("SELECT member_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member_id = $stmt->fetchColumn();

// Get upcoming events
$stmt = $pdo->prepare("
    SELECT e.*, 
           CASE WHEN a.id IS NOT NULL THEN 1 ELSE 0 END as has_registered
    FROM events e
    LEFT JOIN attendance a ON e.id = a.event_id AND a.member_id = ?
    WHERE e.event_date >= CURDATE()
    ORDER BY e.event_date ASC
");
$stmt->execute([$member_id]);
$upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get past events attended
$stmt = $pdo->prepare("
    SELECT e.*, a.checkin_time
    FROM events e
    JOIN attendance a ON e.id = a.event_id
    WHERE a.member_id = ? AND e.event_date < CURDATE()
    ORDER BY e.event_date DESC
");
$stmt->execute([$member_id]);
$past_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Events - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/memberMenu.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Church Events</h4>
                    </div>
                    
                    <!-- Upcoming Events -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Upcoming Events</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Event Name</th>
                                                    <th>Date & Time</th>
                                                    <th>Location</th>
                                                    <th>Description</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($upcoming_events as $event): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($event['event_name']) ?></td>
                                                    <td><?= date('F j, Y g:i A', strtotime($event['event_date'])) ?></td>
                                                    <td><?= htmlspecialchars($event['location']) ?></td>
                                                    <td><?= htmlspecialchars($event['description']) ?></td>
                                                    <td>
                                                        <?php if ($event['has_registered']): ?>
                                                            <span class="badge badge-success">Registered</span>
                                                        <?php else: ?>
                                                            <button class="btn btn-primary btn-sm register-btn" 
                                                                    data-event-id="<?= $event['id'] ?>">
                                                                Register
                                                            </button>
                                                        <?php endif; ?>
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

                    <!-- Past Events -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Past Events Attended</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Event Name</th>
                                                    <th>Date & Time</th>
                                                    <th>Location</th>
                                                    <th>Check-in Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($past_events as $event): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($event['event_name']) ?></td>
                                                    <td><?= date('F j, Y g:i A', strtotime($event['event_date'])) ?></td>
                                                    <td><?= htmlspecialchars($event['location']) ?></td>
                                                    <td><?= date('F j, Y g:i A', strtotime($event['checkin_time'])) ?></td>
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

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.register-btn').click(function() {
                const eventId = $(this).data('event-id');
                const btn = $(this);
                
                $.post('../ajax/register_event.php', {
                    event_id: eventId
                }, function(response) {
                    if (response.success) {
                        btn.replaceWith('<span class="badge badge-success">Registered</span>');
                    } else {
                        alert('Failed to register for event. Please try again.');
                    }
                });
            });
        });
    </script>
</body>
</html>