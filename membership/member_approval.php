<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['class_leader', 'seer', 'admin'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: ../access_denied.php');
    exit();
}

// Initialize audit logger
$logger = new AuditLogger($pdo);

// Get the class leader's assigned class
$stmt = $pdo->prepare("SELECT bible_class_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classLeaderData = $stmt->fetch();
$bible_class_id = $classLeaderData['bible_class_id'];

if (!$bible_class_id && $_SESSION['user_role'] === 'class_leader') {
    $_SESSION['message'] = "<div class='alert alert-danger'>You are not assigned to any bible class.</div>";
    header('Location: ../dashboard.php');
    exit();
}

// Handle bulk approval/rejection
if (isset($_POST['bulk_action']) && isset($_POST['selected_members'])) {
    $action = $_POST['bulk_action'];
    $selected_members = $_POST['selected_members'];
    
    try {
        $pdo->beginTransaction();
        
        foreach ($selected_members as $member_id) {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("
                    UPDATE members 
                    SET status = 'active', 
                        approved_by = ?, 
                        approved_at = NOW() 
                    WHERE id = ? AND bible_class_id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $member_id, $bible_class_id]);
                
                // Log approval
                $logger->log(
                    'member_approval',
                    'membership',
                    $_SESSION['user_id'],
                    "Approved member ID: $member_id",
                    $member_id
                );
            } else if ($action === 'reject') {
                $stmt = $pdo->prepare("
                    UPDATE members 
                    SET status = 'rejected', 
                        rejected_by = ?, 
                        rejected_at = NOW() 
                    WHERE id = ? AND bible_class_id = ?
                ");
                $stmt->execute([$_SESSION['user_id'], $member_id, $bible_class_id]);
                
                // Log rejection
                $logger->log(
                    'member_rejection',
                    'membership',
                    $_SESSION['user_id'],
                    "Rejected member ID: $member_id",
                    $member_id
                );
            }
        }
        
        $pdo->commit();
        $_SESSION['message'] = "<div class='alert alert-success'>Selected members have been " . 
                             ($action === 'approve' ? 'approved' : 'rejected') . " successfully!</div>";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = "<div class='alert alert-danger'>Error processing members: " . $e->getMessage() . "</div>";
    }
    
    header("Location: member_approval.php");
    exit();
}

// Get class details
if ($bible_class_id) {
    $stmt = $pdo->prepare("SELECT class_name FROM bible_classes WHERE id = ?");
    $stmt->execute([$bible_class_id]);
    $class_details = $stmt->fetch();
}

// Get pending members
$stmt = $pdo->prepare("
    SELECT m.*, 
           CONCAT(r.first_name, ' ', r.surname) as referred_by_name
    FROM members m
    LEFT JOIN members r ON m.referred_by = r.id
    WHERE m.bible_class_id = ? 
    AND m.status = 'pending'
    ORDER BY m.created_at DESC
");
$stmt->execute([$bible_class_id]);
$pending_members = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Approval - ChurchEase</title>
    <?php include '../res/assets.php'; ?>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Member Approval</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../home/dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Member Approval</a>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if(isset($class_details)): ?>
                    <div class="alert alert-info">
                        Managing approvals for class: <?php echo htmlspecialchars($class_details['class_name']); ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if(isset($_SESSION['message'])) {
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                    } ?>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Pending Members</h4>
                                        <?php if (!empty($pending_members)): ?>
                                        <div class="ml-auto">
                                            <button type="button" class="btn btn-success btn-sm" onclick="bulkAction('approve')">
                                                <i class="fa fa-check"></i> Approve Selected
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="bulkAction('reject')">
                                                <i class="fa fa-times"></i> Reject Selected
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($pending_members)): ?>
                                        <div class="alert alert-info">No pending members to approve.</div>
                                    <?php else: ?>
                                    <form id="approval-form" method="POST">
                                        <input type="hidden" name="bulk_action" id="bulk_action">
                                        <div class="table-responsive">
                                            <table id="pending-members-table" class="display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>
                                                            <div class="form-check">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input" type="checkbox" id="select-all">
                                                                    <span class="form-check-sign"></span>
                                                                </label>
                                                            </div>
                                                        </th>
                                                        <th>Name</th>
                                                        <th>Gender</th>
                                                        <th>Contact</th>
                                                        <th>Email</th>
                                                        <th>Referred By</th>
                                                        <th>Requested On</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pending_members as $member): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="form-check">
                                                                <label class="form-check-label">
                                                                    <input class="form-check-input member-checkbox" type="checkbox" 
                                                                           name="selected_members[]" value="<?php echo $member['id']; ?>">
                                                                    <span class="form-check-sign"></span>
                                                                </label>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['gender']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['contact_number_1']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($member['referred_by_name'] ?? 'N/A'); ?></td>
                                                        <td><?php echo date('Y-m-d', strtotime($member['created_at'])); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-icon btn-link btn-primary" 
                                                                    onclick="viewDetails(<?php echo $member['id']; ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Member Details Modal -->
    <div class="modal fade" id="memberDetailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Member Details</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="memberDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="approveBtn">
                        <i class="fa fa-check"></i> Approve
                    </button>
                    <button type="button" class="btn btn-danger" id="rejectBtn">
                        <i class="fa fa-times"></i> Reject
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <?php include '../res/scripts.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#pending-members-table').DataTable({
                "pageLength": 25,
                "order": [[6, "desc"]], // Sort by request date by default
                "responsive": true
            });

            // Handle select all checkbox
            $('#select-all').change(function() {
                $('.member-checkbox').prop('checked', $(this).prop('checked'));
            });
        });

        // Handle bulk actions
        function bulkAction(action) {
            if ($('.member-checkbox:checked').length === 0) {
                alert('Please select at least one member.');
                return;
            }

            if (confirm('Are you sure you want to ' + action + ' the selected members?')) {
                $('#bulk_action').val(action);
                $('#approval-form').submit();
            }
        }

        // View member details
        function viewDetails(memberId) {
            $.ajax({
                url: 'get_pending_member_details.php',
                type: 'GET',
                data: { id: memberId },
                success: function(response) {
                    $('#memberDetails').html(response);
                    $('#memberDetailsModal').modal('show');
                    
                    // Set up approval/rejection buttons
                    $('#approveBtn').off('click').on('click', function() {
                        if (confirm('Are you sure you want to approve this member?')) {
                            $('#bulk_action').val('approve');
                            $('input[name="selected_members[]"]').prop('checked', false);
                            $('input[value="' + memberId + '"]').prop('checked', true);
                            $('#approval-form').submit();
                        }
                    });
                    
                    $('#rejectBtn').off('click').on('click', function() {
                        if (confirm('Are you sure you want to reject this member?')) {
                            $('#bulk_action').val('reject');
                            $('input[name="selected_members[]"]').prop('checked', false);
                            $('input[value="' + memberId + '"]').prop('checked', true);
                            $('#approval-form').submit();
                        }
                    });
                },
                error: function() {
                    alert('Error fetching member details');
                }
            });
        }
    </script>
</body>
</html>