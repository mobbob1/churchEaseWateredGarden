<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';

// Check if user is logged in and is a member
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'general') {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Get member ID
$stmt = $pdo->prepare("SELECT member_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member_id = $stmt->fetchColumn();

// Get notifications/announcements
$stmt = $pdo->prepare("
    SELECT 
        n.*,
        m.message_title,
        m.message as message_content,
        m.message_type,
        m.sent_time,
        COALESCE(u.full_name, 'System') as sender_name
    FROM notifications n
    LEFT JOIN messages m ON n.notification_text = m.message
    LEFT JOIN users u ON m.sender_id = u.id
    WHERE n.member_id = ?
    ORDER BY n.notification_date DESC
");
$stmt->execute([$member_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Notifications & Announcements - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link rel="stylesheet" href="../res/assets/css/dataTables.bootstrap4.min.css">
    
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
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/memberMenu.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Notifications & Announcements</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="notifications-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Message</th>
                                                    <th>Type</th>
                                                    <th>Sender</th>
                                                    <th>Date</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($notifications as $notification): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($notification['message_title']) ?></td>
                                                    <td><?= htmlspecialchars($notification['message_content']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $notification['message_type'] === 'bulk' ? 'info' : 'primary' ?>">
                                                            <?= htmlspecialchars(ucfirst($notification['message_type'])) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($notification['sender_name']) ?></td>
                                                    <td><?= date('F j, Y g:i A', strtotime($notification['notification_date'])) ?></td>
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
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#notifications-table').DataTable({
                "order": [[4, "desc"]],
                "pageLength": 25
            });
        });
    </script>
</body>
</html>