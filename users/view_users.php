<?php
require_once '../includes/auth.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'executive_admin_1','audit'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Members - OutpouringCRM</title>
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
                                                $stmt = $pdo->query("SELECT u.*,r.*,
                                                                           name as member_name,
                                                                           m.phone as member_phone
                                                                    FROM users u 
                                                                    LEFT JOIN members m ON u.member_id = m.id 
                                                                    LEFT JOIN user_roles r ON u.id = r.user_id 
                                                                    ORDER BY u.created_at DESC");
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>{$row['id']}</td>";
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
                                                    if ($row['role_key'] === 'seer' || 
                                                        ($row['role_key'] === 'executive_admin_1' && $row['role'] !== 'seer')) {
                                                        echo "<div class='btn-group'>";
                                                        echo "<a href='manage_user.php?id={$row['id']}' 
                                                              class='btn btn-primary btn-sm' title='Edit'>
                                                              <i class='fa fa-edit'></i></a>";
                                                        if ($row['id'] !== $user['id']) {
                                                            echo "<button type='button' 
                                                                  class='btn btn-" . 
                                                                  ($row['status'] === 'active' ? 'warning' : 'success') . 
                                                                  " btn-sm' 
                                                                  onclick='changeStatus({$row['id']}, \"" . 
                                                                  ($row['status'] === 'active' ? 'suspended' : 'active') . 
                                                                  "\")' title='" . 
                                                                  ($row['status'] === 'active' ? 'Suspend' : 'Activate') . 
                                                                  "'><i class='fa fa-" . 
                                                                  ($row['status'] === 'active' ? 'ban' : 'check') . 
                                                                  "'></i></button>";
                                                        }
                                                        echo "</div>";
                                                    }
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
    </script>
</body>
</html>