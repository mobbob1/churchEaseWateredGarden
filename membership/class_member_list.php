<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has appropriate role - only class leaders can access this page
$allowedRoles = ['class_leader', 'seer'];
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

if (!$bible_class_id) {
    $_SESSION['message'] = "<div class='alert alert-danger'>You are not assigned to any bible class.</div>";
    header('Location: ../dashboard.php');
    exit();
}

// Handle member approval/rejection
if (isset($_POST['action'])) {
    $member_id = $_POST['member_id'];
    
    if ($_POST['action'] === 'approve_member') {
        try {
            $pdo->beginTransaction();
            
            // Update member status
            $stmt = $pdo->prepare("UPDATE members SET status = 'active' WHERE id = ? AND bible_class_id = ?");
            $stmt->execute([$member_id, $bible_class_id]);
            
            // Log the approval
            $logger->log(
                'member_approval',
                'membership',
                $_SESSION['user_id'],
                "Approved member ID: $member_id",
                $member_id
            );
            
            $pdo->commit();
            $_SESSION['message'] = "<div class='alert alert-success'>Member approved successfully!</div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['message'] = "<div class='alert alert-danger'>Error approving member: " . $e->getMessage() . "</div>";
        }
    } elseif ($_POST['action'] === 'reject_member') {
        try {
            $pdo->beginTransaction();
            
            // Update member status
            $stmt = $pdo->prepare("UPDATE members SET status = 'rejected' WHERE id = ? AND bible_class_id = ?");
            $stmt->execute([$member_id, $bible_class_id]);
            
            // Log the rejection
            $logger->log(
                'member_rejection',
                'membership',
                $_SESSION['user_id'],
                "Rejected member ID: $member_id",
                $member_id
            );
            
            $pdo->commit();
            $_SESSION['message'] = "<div class='alert alert-warning'>Member rejected.</div>";
        } catch (PDOException $e) {
            $pdo->rollBack();
            $_SESSION['message'] = "<div class='alert alert-danger'>Error rejecting member: " . $e->getMessage() . "</div>";
        }
    }
    
    header("Location: class_member_list.php");
    exit();
}

// Handle CSV export
if (isset($_POST['export']) && $_POST['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="class_members_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, ['Name', 'Gender', 'Contact', 'Email', 'Status', 'Join Date', 'Last Attendance']);
    
    // Fetch and write member data
    $stmt = $pdo->prepare("
        SELECT m.*, 
               MAX(a.attendance_date) as last_attendance
        FROM members m
        LEFT JOIN attendance a ON m.id = a.member_id
        WHERE m.bible_class_id = ?
        GROUP BY m.id
        ORDER BY m.name
    ");
    $stmt->execute([$bible_class_id]);
    
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['name'],
            $row['gender'],
            $row['contact_number_1'],
            $row['email'],
            $row['status'],
            $row['created_at'],
            $row['last_attendance']
        ]);
    }
    
    fclose($output);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Member List - ChurchEase</title>
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
                        <h4 class="page-title">Class Member List</h4>
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
                                <a href="#">Class Members</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Member Management</h4>
                                        <div class="ml-auto">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="export" value="csv">
                                                <button type="submit" class="btn btn-primary btn-sm">
                                                    <i class="fa fa-download"></i> Export CSV
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($_SESSION['message'])) {
                                        echo $_SESSION['message'];
                                        unset($_SESSION['message']);
                                    } ?>
                                    
                                    <div class="table-responsive">
                                        <table id="members-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Gender</th>
                                                    <th>Contact</th>
                                                    <th>Email</th>
                                                    <th>Status</th>
                                                    <th>Last Attendance</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->prepare("
                                                    SELECT m.*, 
                                                           MAX(a.attendance_date) as last_attendance
                                                    FROM members m
                                                    LEFT JOIN attendance a ON m.id = a.member_id
                                                    WHERE m.bible_class_id = ?
                                                    GROUP BY m.id
                                                    ORDER BY m.name
                                                ");
                                                $stmt->execute([$bible_class_id]);
                                                
                                                $counter = 1;
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>{$counter}</td>";
                                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['contact_number_1']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                                    echo "<td>";
                                                    $statusClass = '';
                                                    switch($row['status']) {
                                                        case 'active':
                                                            $statusClass = 'success';
                                                            break;
                                                        case 'pending':
                                                            $statusClass = 'warning';
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = 'danger';
                                                            break;
                                                    }
                                                    echo "<span class='badge badge-{$statusClass}'>" . ucfirst($row['status']) . "</span>";
                                                    echo "</td>";
                                                    echo "<td>" . ($row['last_attendance'] ? date('Y-m-d', strtotime($row['last_attendance'])) : 'Never') . "</td>";
                                                    echo "<td>";
                                                    echo "<div class='btn-group'>";
                                                    
                                                    // View Details Button
                                                    echo "<button type='button' class='btn btn-icon btn-sm btn-primary view-details' data-id='{$row['id']}' title='View Details'>";
                                                    echo "<i class='fas fa-eye'></i>";
                                                    echo "</button>";
                                                    
                                                    // Show approve/reject buttons only for pending members
                                                    if ($row['status'] === 'pending') {
                                                        echo "<form method='POST' class='d-inline ml-1'>";
                                                        echo "<input type='hidden' name='member_id' value='{$row['id']}'>";
                                                        echo "<input type='hidden' name='action' value='approve_member'>";
                                                        echo "<button type='submit' class='btn btn-icon btn-sm btn-success' title='Approve Member'>";
                                                        echo "<i class='fas fa-check'></i>";
                                                        echo "</button>";
                                                        echo "</form>";
                                                        
                                                        echo "<form method='POST' class='d-inline ml-1'>";
                                                        echo "<input type='hidden' name='member_id' value='{$row['id']}'>";
                                                        echo "<input type='hidden' name='action' value='reject_member'>";
                                                        echo "<button type='submit' class='btn btn-icon btn-sm btn-danger' title='Reject Member'>";
                                                        echo "<i class='fas fa-times'></i>";
                                                        echo "</button>";
                                                        echo "</form>";
                                                    }
                                                    
                                                    echo "</div>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                    $counter++;
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
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <?php include '../res/scripts.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            $('#members-table').DataTable({
                "pageLength": 25,
                "order": [[1, "asc"]], // Sort by name by default
                "responsive": true
            });

            // Handle View Details button click
            $('.view-details').click(function() {
                var memberId = $(this).data('id');
                $.ajax({
                    url: 'get_member_details.php',
                    type: 'GET',
                    data: { id: memberId },
                    success: function(response) {
                        $('#memberDetails').html(response);
                        $('#memberDetailsModal').modal('show');
                    },
                    error: function() {
                        alert('Error fetching member details');
                    }
                });
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Confirm delete
            $('.delete-member').click(function(e) {
                if (!confirm('Are you sure you want to delete this member?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>