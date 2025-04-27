<?php
require_once '../includes/auth.php';
require_once '../includes/AuditLogger.php';

// Only admin and managers can view audit logs
if ($user['role'] !== 'admin' && $user['role'] !== 'manager') {
    header('Location: ../dashboard.php');
    exit();
}

// Initialize filters
$filters = [
    'module' => $_GET['module'] ?? '',
    'action' => $_GET['action'] ?? '',
    'status' => $_GET['status'] ?? '',
    'user_id' => $_GET['user_id'] ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to' => $_GET['date_to'] ?? '',
];

// Build query
$query = "SELECT al.*, u.username, u.full_name 
          FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($filters['module']) {
    $query .= " AND al.module = ?";
    $params[] = $filters['module'];
}
if ($filters['action']) {
    $query .= " AND al.action = ?";
    $params[] = $filters['action'];
}
if ($filters['status']) {
    $query .= " AND al.status = ?";
    $params[] = $filters['status'];
}
if ($filters['user_id']) {
    $query .= " AND al.user_id = ?";
    $params[] = $filters['user_id'];
}
if ($filters['date_from']) {
    $query .= " AND DATE(al.created_at) >= ?";
    $params[] = $filters['date_from'];
}
if ($filters['date_to']) {
    $query .= " AND DATE(al.created_at) <= ?";
    $params[] = $filters['date_to'];
}

$query .= " ORDER BY al.created_at DESC";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Audit Logs - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.bootstrap4.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Audit Logs</h4>
                    </div>
                    
                    <!-- Filters -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Filters</h4>
                                </div>
                                <div class="card-body">
                                    <form method="GET" class="row">
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Module</label>
                                                <select name="module" class="form-control">
                                                    <option value="">All Modules</option>
                                                    <?php
                                                    $modules = $pdo->query("SELECT DISTINCT module FROM audit_logs ORDER BY module")->fetchAll();
                                                    foreach ($modules as $module) {
                                                        $selected = ($filters['module'] === $module['module']) ? 'selected' : '';
                                                        echo "<option value='{$module['module']}' {$selected}>" . 
                                                             ucfirst($module['module']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Action</label>
                                                <select name="action" class="form-control">
                                                    <option value="">All Actions</option>
                                                    <?php
                                                    $actions = $pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll();
                                                    foreach ($actions as $action) {
                                                        $selected = ($filters['action'] === $action['action']) ? 'selected' : '';
                                                        echo "<option value='{$action['action']}' {$selected}>" . 
                                                             ucfirst($action['action']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Status</label>
                                                <select name="status" class="form-control">
                                                    <option value="">All Status</option>
                                                    <option value="success" <?php echo $filters['status'] === 'success' ? 'selected' : ''; ?>>Success</option>
                                                    <option value="failed" <?php echo $filters['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>User</label>
                                                <select name="user_id" class="form-control">
                                                    <option value="">All Users</option>
                                                    <?php
                                                    $users = $pdo->query("SELECT id, username, full_name FROM users ORDER BY username")->fetchAll();
                                                    foreach ($users as $u) {
                                                        $selected = ($filters['user_id'] == $u['id']) ? 'selected' : '';
                                                        echo "<option value='{$u['id']}' {$selected}>" . 
                                                             htmlspecialchars($u['username'] . ' (' . $u['full_name'] . ')') . 
                                                             "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Date From</label>
                                                <input type="date" name="date_from" class="form-control" 
                                                       value="<?php echo $filters['date_from']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Date To</label>
                                                <input type="date" name="date_to" class="form-control" 
                                                       value="<?php echo $filters['date_to']; ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-filter"></i> Apply Filters
                                            </button>
                                            <a href="view_logs.php" class="btn btn-danger">
                                                <i class="fa fa-times"></i> Clear Filters
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Logs Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="audit-logs" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date/Time</th>
                                                    <th>User</th>
                                                    <th>Module</th>
                                                    <th>Action</th>
                                                    <th>Status</th>
                                                    <th>Description</th>
                                                    <th>Details</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->prepare($query);
                                                $stmt->execute($params);
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>" . date('Y-m-d H:i:s', strtotime($row['created_at'])) . "</td>";
                                                    echo "<td>" . ($row['username'] ? 
                                                         htmlspecialchars($row['username']) : 'System') . "</td>";
                                                    echo "<td>" . ucfirst($row['module']) . "</td>";
                                                    echo "<td>" . ucfirst($row['action']) . "</td>";
                                                    echo "<td><span class='badge badge-" . 
                                                         ($row['status'] === 'success' ? 'success' : 'danger') . "'>" . 
                                                         ucfirst($row['status']) . "</span></td>";
                                                    echo "<td>" . htmlspecialchars($row['description']) . "</td>";
                                                    echo "<td>";
                                                    echo "<button type='button' class='btn btn-info btn-sm' " . 
                                                         "onclick='showDetails(" . 
                                                         json_encode([
                                                             "old_values" => json_decode($row['old_values'], true),
                                                             "new_values" => json_decode($row['new_values'], true),
                                                             "error_message" => $row['error_message'],
                                                             "ip_address" => $row['ip_address'],
                                                             "user_agent" => $row['user_agent']
                                                         ]) . ")'>";
                                                    echo "<i class='fa fa-info-circle'></i></button>";
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

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Log Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="changesContainer"></div>
                    <div id="errorContainer" class="alert alert-danger" style="display: none;"></div>
                    <div class="technical-details">
                        <h6>Technical Details</h6>
                        <p><strong>IP Address:</strong> <span id="ipAddress"></span></p>
                        <p><strong>User Agent:</strong> <span id="userAgent"></span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
            $('#audit-logs').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'collection',
                        text: 'Export',
                        buttons: [
                            'copy',
                            {
                                extend: 'excel',
                                title: 'Audit_Logs_' + new Date().toISOString().slice(0,10)
                            },
                            {
                                extend: 'pdf',
                                title: 'Audit_Logs_' + new Date().toISOString().slice(0,10)
                            },
                            'print'
                        ]
                    }
                ],
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });

        function showDetails(data) {
            let changesHtml = '';
            
            if (data.old_values || data.new_values) {
                changesHtml += '<h6>Changes</h6><table class="table table-bordered">';
                changesHtml += '<thead><tr><th>Field</th><th>Old Value</th><th>New Value</th></tr></thead><tbody>';
                
                const allFields = new Set([
                    ...Object.keys(data.old_values || {}),
                    ...Object.keys(data.new_values || {})
                ]);
                
                for (const field of allFields) {
                    const oldVal = data.old_values ? data.old_values[field] : '';
                    const newVal = data.new_values ? data.new_values[field] : '';
                    if (oldVal !== newVal) {
                        changesHtml += `<tr>
                            <td>${field}</td>
                            <td>${oldVal}</td>
                            <td>${newVal}</td>
                        </tr>`;
                    }
                }
                changesHtml += '</tbody></table>';
            }
            
            $('#changesContainer').html(changesHtml);
            
            if (data.error_message) {
                $('#errorContainer').show().text(data.error_message);
            } else {
                $('#errorContainer').hide();
            }
            
            $('#ipAddress').text(data.ip_address);
            $('#userAgent').text(data.user_agent);
            
            $('#detailsModal').modal('show');
        }
    </script>
</body>
</html>