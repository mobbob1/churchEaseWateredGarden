<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Check session and authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check permissions based on role hierarchy
$userRole = $_SESSION['user_role'];
$canManageBranches = checkPermission('manage_branches');
$canViewBranches = checkPermission('view_branches');

if (!$canManageBranches && !$canViewBranches) {
    header('Location: ../access_denied.php');
    exit();
}

// Get all branches with basic stats
$query = "SELECT b.*, 
    COUNT(DISTINCT m.id) as total_members,
    COUNT(DISTINCT e.id) as total_events,
    (SELECT SUM(amount) FROM financial_transactions ft WHERE ft.branch_id = b.id AND type = 'income') as total_income,
    (SELECT SUM(amount) FROM financial_transactions ft WHERE ft.branch_id = b.id AND type = 'expense') as total_expenses,
    u.full_name as head_pastor_name
    FROM branches b 
    LEFT JOIN users u ON b.head_pastor_id = u.id
    LEFT JOIN members m ON m.branch_id = b.id
    LEFT JOIN events e ON e.branch_id = b.id
    " . (!in_array($userRole, ['super_admin', 'head_office_admin']) ? "WHERE b.id = ?" : "") . "
    GROUP BY b.id";

try {
    if (!in_array($userRole, ['admin', 'super_admin', 'head_office_admin'])) {
        // For non-admin users, they must have a branch_id
        if (!isset($_SESSION['branch_id'])) {
            // If branch_id is not set, redirect to an error page or set a default
            $_SESSION['error'] = "No branch assigned to your account.";
            header('Location: ../index.php');
            exit();
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute([$_SESSION['branch_id']]);
    } else {
        // For admin users, show all branches
        $stmt = $pdo->query($query);
    }
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $logger->log('branch_query_error', 'branches', null, 
        "Error fetching branch data: " . $e->getMessage(), 
        $_SESSION['user_id'], null, 'error');
    $branches = [];
}

// Calculate statistics
$totalBranches = count($branches);
$activeBranches = array_reduce($branches, function($carry, $branch) {
    return $carry + ($branch['status'] == 'active' ? 1 : 0);
}, 0);
$totalMembers = array_reduce($branches, function($carry, $branch) {
    return $carry + $branch['total_members'];
}, 0);
$totalEvents = array_reduce($branches, function($carry, $branch) {
    return $carry + $branch['total_events'];
}, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Branch Management Dashboard - ChurchEaseSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    
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
                        <h4 class="page-title">Branch Management Dashboard</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../index.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Branches</a>
                            </li>
                        </ul>
                    </div>

                    <!-- Branch Statistics Overview -->
                    <div class="row">
                        <!-- Total Branches Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-building"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Branches</p>
                                                <h4 class="card-title"><?php echo $totalBranches; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Branches Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="fas fa-check-circle"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Active Branches</p>
                                                <h4 class="card-title"><?php echo $activeBranches; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Members Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Members</p>
                                                <h4 class="card-title"><?php echo $totalMembers; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Total Events Card -->
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-warning bubble-shadow-small">
                                                <i class="fas fa-calendar-alt"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Events</p>
                                                <h4 class="card-title"><?php echo $totalEvents; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Branch List -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Branch List</h4>
                                        <?php if ($canManageBranches): ?>
                                        <a href="manage_branch.php" class="btn btn-primary btn-round ml-auto">
                                            <i class="fa fa-plus"></i> Add New Branch
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="branchTable" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Branch Name</th>
                                                    <th>Location</th>
                                                    <th>Head Pastor</th>
                                                    <th>Contact</th>
                                                    <th>Members</th>
                                                    <th>Events</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($branches as $branch): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($branch['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($branch['location']); ?></td>
                                                    <td><?php echo htmlspecialchars($branch['head_pastor_name'] ?? 'Not Assigned'); ?></td>
                                                    <td><?php echo htmlspecialchars($branch['contact_number']); ?></td>
                                                    <td><?php echo $branch['total_members']; ?></td>
                                                    <td><?php echo $branch['total_events']; ?></td>
                                                    <td>
                                                        <span class="badge badge-<?php echo $branch['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                            <?php echo ucfirst($branch['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="form-button-action">
                                                            <?php if ($canViewBranches): ?>
                                                            <a href="branch_details.php?id=<?php echo $branch['id']; ?>" 
                                                               class="btn btn-link btn-info btn-lg" 
                                                               data-toggle="tooltip" title="View Details">
                                                                <i class="fa fa-eye"></i>
                                                            </a>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($canManageBranches): ?>
                                                            <a href="manage_branch.php?id=<?php echo $branch['id']; ?>" 
                                                               class="btn btn-link btn-primary btn-lg" 
                                                               data-toggle="tooltip" title="Edit Branch">
                                                                <i class="fa fa-edit"></i>
                                                            </a>
                                                            
                                                            <button type="button" 
                                                                    class="btn btn-link btn-<?php echo $branch['status'] == 'active' ? 'danger' : 'success'; ?> toggle-status" 
                                                                    data-toggle="tooltip" 
                                                                    title="<?php echo $branch['status'] == 'active' ? 'Deactivate' : 'Activate'; ?>"
                                                                    data-id="<?php echo $branch['id']; ?>"
                                                                    data-status="<?php echo $branch['status']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($branch['name']); ?>">
                                                                <i class="fa fa-<?php echo $branch['status'] == 'active' ? 'times' : 'check'; ?>"></i>
                                                            </button>
                                                            <?php endif; ?>
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
    
    <!-- Status Change Confirmation Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1" role="dialog" aria-labelledby="statusModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="statusModalLabel">Confirm Status Change</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Are you sure you want to <span id="actionText"></span> branch "<span id="branchName"></span>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmStatusChange">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    
    <!-- jQuery UI -->
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    
    <!-- jQuery Scrollbar -->
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- DataTables -->
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    
    <!-- Bootstrap Notify -->
    <script src="../res/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>
    
    <!-- Sweet Alert -->
    <script src="../res/assets/js/plugin/sweetalert/sweetalert.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#branchTable').DataTable({
                "pageLength": 25,
                "order": [[0, "asc"]]
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Handle status toggle
            $('.toggle-status').click(function() {
                var button = $(this);
                var branchId = button.data('id');
                var currentStatus = button.data('status');
                var branchName = button.data('name');
                var newStatus = currentStatus === 'active' ? 'inactive' : 'active';
                var actionText = currentStatus === 'active' ? 'deactivate' : 'activate';
                
                $('#actionText').text(actionText);
                $('#branchName').text(branchName);
                $('#statusModal').modal('show');
                
                $('#confirmStatusChange').off('click').on('click', function() {
                    $.ajax({
                        url: 'toggle_status.php',
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            branch_id: branchId,
                            status: newStatus
                        },
                        success: function(response) {
                            $('#statusModal').modal('hide');
                            
                            if (response.success) {
                                $.notify({
                                    icon: 'fas fa-check',
                                    message: response.message
                                }, {
                                    type: 'success',
                                    placement: {
                                        from: 'top',
                                        align: 'right'
                                    },
                                    time: 1000
                                });
                                
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                $.notify({
                                    icon: 'fas fa-times',
                                    message: response.message
                                }, {
                                    type: 'danger',
                                    placement: {
                                        from: 'top',
                                        align: 'right'
                                    }
                                });
                            }
                        },
                        error: function() {
                            $('#statusModal').modal('hide');
                            $.notify({
                                icon: 'fas fa-times',
                                message: 'Error processing request'
                            }, {
                                type: 'danger',
                                placement: {
                                    from: 'top',
                                    align: 'right'
                                }
                            });
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>