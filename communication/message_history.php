<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Get message history with member details
$stmt = $pdo->prepare("
    SELECT 
        m.first_name,
        m.surname,
        m.contact_number_1,
        n.notification_text,
        n.notification_date
    FROM notifications n
    JOIN members m ON n.member_id = m.id
    ORDER BY n.notification_date DESC
");
$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Message History - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>
    
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
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Message History</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Communication</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Message History</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Message History</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="message-history" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Member Name</th>
                                                    <th>Contact Number</th>
                                                    <th>Message</th>
                                                    <th>Date Sent</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($messages as $message): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($message['first_name'] . ' ' . $message['surname']) ?></td>
                                                        <td><?= htmlspecialchars($message['contact_number_1']) ?></td>
                                                        <td><?= htmlspecialchars($message['notification_text']) ?></td>
                                                        <td><?= date('M d, Y h:i A', strtotime($message['notification_date'])) ?></td>
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

    <!--   Core JS Files   -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    
    <!-- jQuery UI -->
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
    
    <!-- jQuery Scrollbar -->
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- Datatables -->
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#message-history').DataTable({
                "order": [[ 3, "desc" ]],  // Sort by date column descending
                "pageLength": 25,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                }
            });
        });
    </script>
</body>
</html>
