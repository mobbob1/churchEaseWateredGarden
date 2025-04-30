<?php
require_once '../includes/auth.php';
require_once '../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['seer', 'executive_admin_1','audit'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Get current user information
$currentUserStmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$currentUserStmt->execute([$_SESSION['user_id']]);
$currentUser = $currentUserStmt->fetch();

// Create function to send SMS using mNotify
function sendSMS($phone, $message) {
    $apiKey = MNOTIFY_API_KEY;
    $senderId = MNOTIFY_SENDER_ID;
    $url = 'https://apps.mnotify.net/smsapi';

    $queryParams = http_build_query([
        'key' => $apiKey,
        'to' => $phone,
        'msg' => $message,
        'sender_id' => $senderId
    ]);

    $fullUrl = $url . '?' . $queryParams;

    // Use file_get_contents to send the request
    $response = file_get_contents($fullUrl);

    if ($response === FALSE) {
        return [
            'status' => 'error',
            'response' => 'Failed to send SMS'
        ];
    }

    return [
        'status' => 'success',
        'response' => $response
    ];
}

$logger = new AuditLogger($pdo);
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Handle status change
if (isset($_POST['change_status'])) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->execute([$_POST['status'], $_POST['user_id']]);
        $message = "<div class='alert alert-success'>User status updated successfully!</div>";
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error updating status: " . $e->getMessage() . "</div>";
    }
}

// Handle password reset and SMS
if (isset($_POST['reset_password'])) {
    try {
        // Get user information including member_id
        $userStmt = $pdo->prepare("SELECT u.*, m.contact_number_1, m.first_name, m.surname 
                                  FROM users u 
                                  LEFT JOIN members m ON u.member_id = m.id 
                                  WHERE u.id = ?");
        $userStmt->execute([$_POST['user_id']]);
        $user = $userStmt->fetch();
        
        if (!$user) {
            throw new Exception("User not found in the database");
        }
        
        // Get phone number (first try member contact, then user phone)
        $phone = !empty($user['contact_number_1']) ? $user['contact_number_1'] : $user['phone'];
        
        if (empty($phone)) {
            throw new Exception("User does not have a phone number. Please update their profile with a phone number first.");
        }
        
        // Format phone number to be used as username/password
        $phone = preg_replace('/[^0-9]/', '', $phone); // Remove non-numeric characters
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '233' . substr($phone, 1); // Convert 0xx format to 233xx format
        }
        
        // Update user credentials
        $hashedPassword = password_hash($phone, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET username = ?, password = ?, password_changed = 0 WHERE id = ?");
        $updateStmt->execute([$phone, $hashedPassword, $user['id']]);
        
        // Get name for SMS
        $name = '';
        if (!empty($user['first_name']) && !empty($user['surname'])) {
            $name = $user['first_name'] . ' ' . $user['surname'];
        } elseif (!empty($user['full_name'])) {
            $name = $user['full_name'];
        } else {
            $name = 'User';
        }
        
        // Create SMS message
        $message_text = "Dear {$name}, your Outpouring Oasis account credentials:\nUsername: {$phone}\nPassword: {$phone}\n\nPlease change your password after logging in.";
        
        // Send SMS
        $response = sendSMS($phone, $message_text);
        
        // Log SMS details
        $logger->log(
            'password_reset',
            'users',
            $user['id'],
            "Password reset and credentials sent via SMS",
            null,
            [
                'phone' => $phone,
                'username_updated' => true,
                'api_response' => $response['response']
            ],
            $response['status'] == 'success' ? 'success' : 'failed'
        );
        
        $message = "<div class='alert alert-success'>Password reset and new credentials sent to user via SMS!</div>";
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        $logger->log('password_reset', 'users', $_POST['user_id'] ?? null, "Failed password reset attempt", null, null, 'failed', $e->getMessage());
    }
}

// Handle sending SMS
if (isset($_POST['send_sms'])) {
    try {
        // Get user information including member_id
        $userStmt = $pdo->prepare("SELECT u.*, m.contact_number_1, m.first_name, m.surname 
                                  FROM users u 
                                  LEFT JOIN members m ON u.member_id = m.id 
                                  WHERE u.id = ?");
        $userStmt->execute([$_POST['user_id']]);
        $user = $userStmt->fetch();

        if (!$user) {
            throw new Exception("User not found");
        }

        // Get phone number (first try member contact, then user phone)
        $phone = !empty($user['contact_number_1']) ? $user['contact_number_1'] : $user['phone'];

        if (empty($phone)) {
            throw new Exception("No phone number found for this user");
        }

        // Format phone number for Ghana
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '233' . substr($phone, 1);
        }

        // Get name (first try member name, then user full_name, then username)
        $name = '';
        if (!empty($user['first_name']) && !empty($user['surname'])) {
            $name = $user['first_name'] . ' ' . $user['surname'];
        } elseif (!empty($user['full_name'])) {
            $name = $user['full_name'];
        } else {
            $name = $user['username'];
        }

        // Prepare message with debug info
        $message_text = $_POST['message'] . "\n";

        // Send SMS
        $response = sendSMS($phone, $message_text);

        // Log the SMS sending
        $logger->log(
            'send_sms',
            'users',
            $user['id'],
            "SMS sent to user",
            null,
            [
                'phone' => $phone,
                'message' => $message_text,
                'response' => $response['response']
            ],
            $response['status'] == 'success' ? 'success' : 'failed'
        );

        $message = "<div class='alert alert-success'>SMS sent successfully!</div>";
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        $logger->log('send_sms', 'users', $_POST['user_id'] ?? null, "Failed SMS attempt", null, null, 'failed', $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Members - OutpouringCRM</title>
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


    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.css"/>

    <style>
        .dt-buttons { display: none; }
        .dataTables_filter { display: none; }
        .filter-select {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
            border: 1px solid #eee;
        }
        .export-buttons .dropdown-item {
            cursor: pointer;
        }
        .table th { position: relative; }
        .table th select {
            position: relative;
            width: 100%;
            margin-top: 5px;
            padding: 3px;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">User Management</h4>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Users List</h4>
                                        <div class="ml-auto mr-3">
                                            <select id="member-filter" class="form-control">
                                                <option value="all">All Users</option>
                                                <option value="linked">Linked to Member</option>
                                                <option value="unlinked">Not Linked to Member</option>
                                            </select>
                                        </div>
                                        <a href="manage_user.php" class="btn btn-primary btn-round">
                                            <i class="fa fa-plus"></i> Add New User
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if($message) echo $message; ?>
                                    <div class="table-responsive">
                                        <table id="users-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>ID</th>
                                                    <th>Profile</th>
                                                    <th>Full Name</th>
                                                    <th>Username</th>
                                                    <th>Email</th>
                                                    <th>Linked Member</th>
                                                    <th>Role</th>
                                                    <th>Department</th>
                                                    <th>Status</th>
                                                    <th>Last Login</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("SELECT 
                                                    u.id as user_id,
                                                    u.username,
                                                    u.full_name,
                                                    u.email,
                                                    u.profile_image,
                                                    u.status,
                                                    u.last_login,
                                                    u.member_id,
                                                    u.department,
                                                    u.created_at,
                                                    r.role_key,
                                                    CONCAT(m.first_name, ' ', m.surname) as member_name,
                                                    m.contact_number_1 as member_phone,
                                                    m.id as member_id,
                                                    m.email as member_email
                                                FROM users u 
                                                LEFT JOIN members m ON u.member_id = m.id 
                                                LEFT JOIN user_roles r ON u.id = r.user_id 
                                                ORDER BY u.created_at DESC");
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>{$row['user_id']}</td>";
                                                    echo "<td>";
                                                    if ($row['profile_image']) {
                                                        echo "<img src='../" . htmlspecialchars($row['profile_image']) . 
                                                             "' class='rounded-circle' width='40'>";
                                                    } else {
                                                        echo "<img src='../res/assets/img/default-avatar.png' 
                                                              class='rounded-circle' width='40'>";
                                                    }
                                                    echo "</td>";
                                                    echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                                    echo "<td>";
                                                    if ($row['member_id']) {
                                                        echo "<div class='member-info'>";
                                                        echo "<div class='member-name'>" . 
                                                             htmlspecialchars($row['member_name']) . "</div>";
                                                        echo "<div class='member-phone'>" . 
                                                             htmlspecialchars($row['member_phone']) . "</div>";
                                                        echo "</div>";
                                                    } else {
                                                        echo "<span class='badge badge-warning'>Not Linked</span>";
                                                    }
                                                    echo "</td>";
                                                     echo "<td>" . htmlspecialchars($row['role_key']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                                                    echo "<td><span class='badge badge-" . 
                                                         ($row['status'] === 'active' ? 'success' : 
                                                          ($row['status'] === 'suspended' ? 'danger' : 'warning')) . 
                                                         "'>" . ucfirst($row['status']) . "</span></td>";
                                                    echo "<td>" . ($row['last_login'] ? 
                                                         date('M d, Y H:i', strtotime($row['last_login'])) : 'Never') . 
                                                         "</td>";
                                                    echo "<td>";
                                                  
                                                            echo "<div class='btn-group'>";
                                                        echo "<a href='manage_user.php?id={$row['user_id']}' 
                                                              class='btn btn-primary btn-sm' title='Edit'>
                                                              <i class='fa fa-edit'></i></a>";
                                                        if ($row['user_id'] !== $currentUser['id']) {
                                                            echo "<button type='button' 
                                                                  class='btn btn-" . 
                                                                  ($row['status'] === 'active' ? 'warning' : 'success') . 
                                                                  " btn-sm' 
                                                                  onclick='changeStatus({$row['user_id']}, \"" . 
                                                                  ($row['status'] === 'active' ? 'suspended' : 'active') . 
                                                                  "\")' title='" . 
                                                                  ($row['status'] === 'active' ? 'Suspend' : 'Activate') . 
                                                                  "'><i class='fa fa-" . 
                                                                  ($row['status'] === 'active' ? 'ban' : 'check') . 
                                                                  "'></i></button>";
                                                             
                                                             // Add SMS credentials button
                                                             echo "<button type='button' 
                                                                   class='btn btn-info btn-sm' 
                                                                   onclick='sendCredentials({$row['user_id']})' 
                                                                   title='Send Credentials via SMS'>
                                                                   <i class='fa fa-sms'></i></button>";
                                                             // Add SMS button
                                                             echo "<button type='button' 
                                                                   class='btn btn-info btn-sm' 
                                                                   onclick='sendSMS({$row['user_id']})' 
                                                                   title='Send SMS'>
                                                                   <i class='fa fa-envelope'></i></button>";
                                                        }
                                                        echo "</div>";
                                                   
                                                    echo "</td>";
                                                    echo "</tr>";
                                                }
                                                ?>
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

    <!-- Status Change Form -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="statusUserId">
        <input type="hidden" name="status" id="statusValue">
        <input type="hidden" name="change_status" value="1">
    </form>
    
    <!-- Password Reset Form -->
    <form id="resetPasswordForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="resetPasswordUserId">
        <input type="hidden" name="reset_password" value="1">
    </form>

    <!-- SMS Form -->
    <form id="smsForm" method="POST" style="display: none;">
        <input type="hidden" name="user_id" id="smsUserId">
        <input type="hidden" name="message" id="smsMessage">
        <input type="hidden" name="send_sms" value="1">
    </form>

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#users-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'collection',
                        text: 'Export',
                        buttons: [
                            'copy',
                            {
                                extend: 'excel',
                                title: 'Users_List_' + new Date().toISOString().slice(0,10)
                            },
                            {
                                extend: 'pdf',
                                title: 'Users_List_' + new Date().toISOString().slice(0,10)
                            },
                            'print'
                        ]
                    }
                ],
                "pageLength": 25,
                "order": [[0, "desc"]]
            });

            $('#member-filter').on('change', function() {
                var value = $(this).val();
                
                if (value === 'all') {
                    table.column(5).search('').draw();
                } else if (value === 'linked') {
                    table.column(5).search('Not Linked', true, false, true).draw();
                } else {
                    table.column(5).search('^Not Linked$', true, false, true).draw();
                }
            });
        });

        function changeStatus(userId, status) {
            if (confirm('Are you sure you want to ' + 
                       (status === 'active' ? 'activate' : 'suspend') + 
                       ' this user?')) {
                document.getElementById('statusUserId').value = userId;
                document.getElementById('statusValue').value = status;
                document.getElementById('statusForm').submit();
            }
        }
        
        function sendCredentials(userId) {
            if (confirm('Are you sure you want to reset this user\'s password and send their credentials via SMS?')) {
                document.getElementById('resetPasswordUserId').value = userId;
                document.getElementById('resetPasswordForm').submit();
            }
        }
        
        function sendSMS(userId) {
            var message = prompt('Enter your message:');
            if (message) {
                document.getElementById('smsUserId').value = userId;
                document.getElementById('smsMessage').value = message;
                document.getElementById('smsForm').submit();
            }
        }
    </script>
</body>
</html>