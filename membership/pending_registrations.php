<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';
require_once __DIR__ . '/../config.php';

$logger = new AuditLogger($pdo);

// Check if user has appropriate role
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: /churchsuperb/index.php?error=unauthorized');
    exit();
}

// Handle Accept/Reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['member_id'])) {
    $action = $_POST['action'];
    $member_id = $_POST['member_id'];
    $new_status = ($action === 'accept') ? 'ACCEPTED' : 'REJECTED';
    
    try {
        $stmt = $pdo->prepare("UPDATE members SET status = ? WHERE id = ? AND mode_of_registration = 'SELF_REGISTRATION' AND status = 'PENDING'");
        $stmt->execute([$new_status, $member_id]);
        
        if ($stmt->rowCount() > 0) {
            // Get member details for logging
            $stmt = $pdo->prepare("SELECT name FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            
            // Log the action
            $logger->log(
                'registration_review',
                'members',
                $member_id,
                "Registration " . strtolower($new_status) . " for member: " . $member['name'],
                $_SESSION['user_id'],
                ['status' => $new_status],
                'success'
            );
            
            $message = "<div class='alert alert-success'>Registration successfully " . strtolower($new_status) . ".</div>";
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $message = "<div class='alert alert-danger'>Error processing request.</div>";
    }
}

// Fetch pending self-registrations
try {
    $stmt = $pdo->prepare("
        SELECT m.*, 
               bc.class_name as bible_class_name,
               GROUP_CONCAT(DISTINCT o.organization_name SEPARATOR ', ') as organization_name,
               mt.type_name as member_type
        FROM members m
        LEFT JOIN bible_classes bc ON m.bible_class_id = bc.id
        LEFT JOIN organizations o ON (
            o.id = m.first_organization OR 
            o.id = m.second_organization OR 
            o.id = m.third_organization OR 
            o.id = m.fourth_organization
        )
        LEFT JOIN member_types mt ON m.member_type_id = mt.id
        WHERE m.mode_of_registration = 'SELF_REGISTRATION' 
        AND m.status = 'PENDING'
        GROUP BY m.id
        ORDER BY m.created_at DESC
    ");
    
    // Add error handling to check if the query can be prepared
    try {
        $stmt->execute();
        $pending_registrations = $stmt->fetchAll();
        
        // Debug information
        if (empty($pending_registrations)) {
            error_log("No pending registrations found. SQL: " . $stmt->queryString);
            // Check if there are any records that should match
            $check_stmt = $pdo->query("
                SELECT COUNT(*) as count 
                FROM members 
                WHERE mode_of_registration = 'SELF_REGISTRATION' AND status = 'PENDING'
            ");
            $count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            error_log("Total matching records without joins: " . $count);
        }
    } catch (PDOException $inner_e) {
        // If the original query fails, try a simpler query without the problematic join
        error_log("Original query failed: " . $inner_e->getMessage());
        $stmt = $pdo->prepare("
            SELECT m.*, 
                   bc.class_name as bible_class_name,
                   NULL as organization_name,
                   mt.type_name as member_type
            FROM members m
            LEFT JOIN bible_classes bc ON m.bible_class_id = bc.id
            LEFT JOIN member_types mt ON m.member_type_id = mt.id
            WHERE m.mode_of_registration = 'SELF_REGISTRATION' 
            AND m.status = 'PENDING'
            ORDER BY m.created_at DESC
        ");
        $stmt->execute();
        $pending_registrations = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    $pending_registrations = [];
    $message = "<div class='alert alert-danger'>Error fetching pending registrations: " . $e->getMessage() . "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Pending Registrations - ChurchEase</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../loginres/images/favicon/favicon.ico" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="../loginres/images/favicon/site.webmanifest">

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
                        <h4 class="page-title">Pending Registrations</h4>
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
                                <a href="#">Pending Registrations</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Self-Registration Requests</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($message)) echo $message; ?>
                                    
                                    <?php if (empty($pending_registrations)): ?>
                                        <div class="alert alert-info">No pending registrations found.</div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Name</th>
                                                        <th>Contact</th>
                                                        <th>Email</th>
                                                        <th>Member Type</th>
                                                        <th>Bible Class</th>
                                                        <th>Organization</th>
                                                        <th>Registration Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($pending_registrations as $registration): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($registration['name']); ?></td>
                                                            <td><?php echo htmlspecialchars($registration['contact_number_1']); ?></td>
                                                            <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                                            <td><?php echo htmlspecialchars($registration['member_type']); ?></td>
                                                            <td><?php echo htmlspecialchars($registration['bible_class_name'] ?? 'Not Selected'); ?></td>
                                                            <td><?php echo htmlspecialchars($registration['organization_name'] ?? 'Not Selected'); ?></td>
                                                            <td><?php echo date('Y-m-d H:i', strtotime($registration['created_at'])); ?></td>
                                                            <td>
                                                                <div class="btn-group">
                                                                    <button type="button" 
                                                                            class="btn btn-sm btn-info" 
                                                                            data-toggle="modal" 
                                                                            data-target="#detailsModal<?php echo $registration['id']; ?>">
                                                                        <i class="fa fa-eye"></i>
                                                                    </button>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="member_id" value="<?php echo $registration['id']; ?>">
                                                                        <input type="hidden" name="action" value="accept">
                                                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to accept this registration?')">
                                                                            <i class="fa fa-check"></i>
                                                                        </button>
                                                                    </form>
                                                                    <form method="POST" style="display: inline;">
                                                                        <input type="hidden" name="member_id" value="<?php echo $registration['id']; ?>">
                                                                        <input type="hidden" name="action" value="reject">
                                                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this registration?')">
                                                                            <i class="fa fa-times"></i>
                                                                        </button>
                                                                    </form>
                                                                </div>

                                                                <!-- Details Modal -->
                                                                <div class="modal fade" id="detailsModal<?php echo $registration['id']; ?>" tabindex="-1" role="dialog">
                                                                    <div class="modal-dialog modal-lg" role="document">
                                                                        <div class="modal-content">
                                                                            <div class="modal-header">
                                                                                <h5 class="modal-title">Registration Details</h5>
                                                                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                                    <span aria-hidden="true">&times;</span>
                                                                                </button>
                                                                            </div>
                                                                            <div class="modal-body">
                                                                                <div class="row">
                                                                                    <div class="col-md-6">
                                                                                        <p><strong>Full Name:</strong> <?php echo htmlspecialchars($registration['name']); ?></p>
                                                                                        <p><strong>First Name:</strong> <?php echo htmlspecialchars($registration['first_name']); ?></p>
                                                                                        <p><strong>Surname:</strong> <?php echo htmlspecialchars($registration['surname']); ?></p>
                                                                                        <p><strong>Gender:</strong> <?php echo htmlspecialchars($registration['gender']); ?></p>
                                                                                        <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($registration['date_of_birth']); ?></p>
                                                                                        <p><strong>Profession:</strong> <?php echo htmlspecialchars($registration['profession']); ?></p>
                                                                                    </div>
                                                                                    <div class="col-md-6">
                                                                                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($registration['contact_number_1']); ?></p>
                                                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($registration['email']); ?></p>
                                                                                        <p><strong>Home Address:</strong> <?php echo htmlspecialchars($registration['home_address']); ?></p>
                                                                                        <p><strong>Work Address:</strong> <?php echo htmlspecialchars($registration['work_address']); ?></p>
                                                                                        <p><strong>Bible Class:</strong> <?php echo htmlspecialchars($registration['bible_class_name'] ?? 'Not Selected'); ?></p>
                                                                                        <p><strong>Organization:</strong> <?php echo htmlspecialchars($registration['organization_name'] ?? 'Not Selected'); ?></p>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="modal-footer">
                                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
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
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>