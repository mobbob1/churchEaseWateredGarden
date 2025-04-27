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

// Get branch ID from URL
$user_id = $_SESSION['user_id'];
$branch_id = isset($_GET['id']) ? $_GET['id'] : 0;

// Verify branch access based on role hierarchy
$access_query = "
    SELECT 
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM user_roles ur
                WHERE ur.user_id = :user_id 
                AND ur.role_key IN ('seer', 'executive_admin_1')
            ) THEN 'global'
            WHEN EXISTS (
                SELECT 1 FROM user_roles ur
                INNER JOIN branches b ON b.head_pastor_id = :user_id
                WHERE ur.user_id = :user_id 
                AND ur.role_key = 'pastorate'
                AND b.id = :branch_id
            ) THEN 'branch_pastor'
            WHEN EXISTS (
                SELECT 1 FROM user_roles ur
                WHERE ur.user_id = :user_id 
                AND ur.role_key IN ('executive_admin_2', 'finance', 'hr', 'comms')
                AND EXISTS (
                    SELECT 1 FROM branches b
                    WHERE b.id = :branch_id
                )
            ) THEN 'branch_staff'
            ELSE 'none'
        END as access_level";

$stmt = $pdo->prepare($access_query);
$stmt->execute([
    'user_id' => $user_id,
    'branch_id' => $branch_id
]);
$access = $stmt->fetch(PDO::FETCH_ASSOC);

if ($access['access_level'] === 'none') {
    // Log unauthorized access attempt
    $audit_query = "
        INSERT INTO audit_logs (
            user_id, action, module, record_id,
            description, ip_address, user_agent, status,
            error_message
        ) VALUES (
            :user_id, 'access_denied', 'branch', :branch_id,
            'Unauthorized branch access attempt', :ip_address,
            :user_agent, 'failed', 'Insufficient permissions'
        )";
    
    $stmt = $pdo->prepare($audit_query);
    $stmt->execute([
        'user_id' => $user_id,
        'branch_id' => $branch_id,
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);

    $_SESSION['error'] = "You don't have permission to access this branch.";
    header("Location: ../index.php");
    exit();
}

// Get branch details
$branch_query = "
    SELECT b.*, u.full_name as head_pastor_name
    FROM branches b
    LEFT JOIN users u ON b.head_pastor_id = u.id
    WHERE b.id = :branch_id";

$stmt = $pdo->prepare($branch_query);
$stmt->execute(['branch_id' => $branch_id]);
$branch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$branch) {
    $_SESSION['error'] = "Branch not found.";
    header("Location: dashboard.php");
    exit();
}

// Get branch users based on role hierarchy
$branch_users_query = "
    SELECT DISTINCT 
        u.id, 
        u.full_name, 
        u.email, 
        u.status,
        u.phone,
        GROUP_CONCAT(DISTINCT r.name ORDER BY 
            CASE r.role_key
                WHEN 'seer' THEN 0           /* Super Admin */
                WHEN 'executive_admin_1' THEN 1  /* Head Office Admin */
                WHEN 'executive_admin_2' THEN 2  /* Branch Admin */
                WHEN 'pastorate' THEN 4      /* Branch Pastor */
                ELSE 5                       /* Other branch roles */
            END
            SEPARATOR ', '
        ) as roles
    FROM users u 
    INNER JOIN user_roles ur ON u.id = ur.user_id
    INNER JOIN roles r ON ur.role_key = r.role_key
    LEFT JOIN branches b ON b.head_pastor_id = u.id
    WHERE (
        /* Super Admin and Head Office Admin can view all */
        ur.role_key IN ('seer', 'executive_admin_1')
        OR
        /* Branch-specific roles */
        (ur.role_key IN (
            'executive_admin_2',  /* Branch Admin */
            'pastorate',         /* Branch Pastor */
            'finance', 'hr', 'comms'
        ) AND b.id = :branch_id)
    )
    GROUP BY u.id
    ORDER BY
        MIN(CASE r.role_key
            WHEN 'seer' THEN 0
            WHEN 'executive_admin_1' THEN 1
            WHEN 'executive_admin_2' THEN 2
            WHEN 'pastorate' THEN 4
            ELSE 5
        END),
        u.full_name ASC";

$stmt = $pdo->prepare($branch_users_query);
$stmt->execute(['branch_id' => $branch_id]);
$branch_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available pastors for assignment if user has appropriate permissions
if ($access['access_level'] === 'global') {
    $pastors_query = "
        SELECT u.id, u.full_name
        FROM users u
        INNER JOIN user_roles ur ON u.id = ur.user_id
        WHERE ur.role_key = 'pastorate'
        AND u.status = 'active'
        AND (
            u.id = :branch_id
            OR NOT EXISTS (
                SELECT 1 FROM branches b
                WHERE b.head_pastor_id = u.id
                AND b.id != :branch_id
            )
        )
        ORDER BY u.full_name ASC";

    $stmt = $pdo->prepare($pastors_query);
    $stmt->execute(['branch_id' => $branch_id]);
    $pastors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get branch statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT m.id) as total_members,
        COUNT(DISTINCT CASE WHEN m.status = 'active' THEN m.id END) as active_members,
        COUNT(DISTINCT e.id) as total_events,
        SUM(CASE WHEN ft.type = 'income' THEN ft.amount ELSE 0 END) as total_income,
        SUM(CASE WHEN ft.type = 'expense' THEN ft.amount ELSE 0 END) as total_expenses
    FROM branches b
    LEFT JOIN members m ON m.branch_id = b.id
    LEFT JOIN events e ON e.branch_id = b.id
    LEFT JOIN financial_transactions ft ON ft.branch_id = b.id
    WHERE b.id = :branch_id";

$stmt = $pdo->prepare($stats_query);
$stmt->execute(['branch_id' => $branch_id]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent activities
$activities_query = "
    (SELECT 'member' as type, 
            CONCAT('New member ', m.first_name, ' ', m.surname, ' added') as description, 
            m.created_at
     FROM members m
     WHERE m.branch_id = :branch_id
     ORDER BY m.created_at DESC
     LIMIT 5)
    UNION
    (SELECT 'event' as type, 
            CONCAT('Event ', e.title, ' scheduled') as description, 
            e.created_at
     FROM events e
     WHERE e.branch_id = :branch_id
     ORDER BY e.created_at DESC
     LIMIT 5)
    UNION
    (SELECT 'transaction' as type, 
            CONCAT(ft.type, ' transaction of ', ft.amount, ' recorded') as description, 
            ft.transaction_date as created_at
     FROM financial_transactions ft
     WHERE ft.branch_id = :branch_id
     ORDER BY ft.transaction_date DESC
     LIMIT 5)
    ORDER BY created_at DESC
    LIMIT 10";

$stmt = $pdo->prepare($activities_query);
$stmt->execute(['branch_id' => $branch_id]);
$activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Log branch profile view
$audit_query = "
    INSERT INTO audit_logs (
        user_id, action, module, record_id,
        description, ip_address, user_agent, status
    ) VALUES (
        :user_id, 'view', 'branch', :branch_id,
        'Viewed branch profile', :ip_address,
        :user_agent, 'success'
    )";

$stmt = $pdo->prepare($audit_query);
$stmt->execute([
    'user_id' => $user_id,
    'branch_id' => $branch_id,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT']
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Branch Profile - <?php echo htmlspecialchars($branch['name']); ?> - ChurchEaseSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    
    <style>
    .timeline {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .timeline-item {
        padding: 1rem 0;
        position: relative;
        border-left: 2px solid #e3e6f0;
        margin-left: 1rem;
    }

    .timeline-item:before {
        content: '';
        position: absolute;
        left: -9px;
        top: 1.5rem;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #4e73df;
    }

    .timeline-item-content {
        margin-left: 1.5rem;
        position: relative;
    }

    .timeline-item-content .tag {
        color: #fff;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        border-radius: 15px;
        display: inline-block;
        margin-bottom: 0.5rem;
    }

    .timeline-item-content .time {
        font-size: 0.75rem;
        color: #858796;
        display: block;
        margin-top: 0.25rem;
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
                        <h4 class="page-title">Branch Profile</h4>
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
                                <a href="dashboard.php">Branches</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#"><?php echo htmlspecialchars($branch['name']); ?></a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-end">
                                <?php if (check_role_privileges(['admin', 'manager'])): ?>
                                <a href="manage_branch.php?id=<?php echo $branch_id; ?>" class="btn btn-primary btn-sm mr-2">
                                    <i class="fa fa-edit"></i> Edit Branch
                                </a>
                                <?php endif; ?>
                                <a href="dashboard.php" class="btn btn-secondary btn-sm">
                                    <i class="fa fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Branch Overview -->
                    <div class="row">
                        <!-- Branch Info Card -->
                        <div class="col-xl-4 col-lg-5">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Branch Information</div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex flex-column">
                                        <div class="text-center mb-3">
                                            <div class="avatar avatar-xl">
                                                <span class="avatar-title rounded-circle bg-primary">
                                                    <?php echo strtoupper(substr($branch['name'], 0, 1)); ?>
                                                </span>
                                            </div>
                                            <h2 class="mt-2"><?php echo htmlspecialchars($branch['name']); ?></h2>
                                            <span class="badge badge-<?php echo $branch['status'] == 'active' ? 'success' : 'danger'; ?>">
                                                <?php echo ucfirst($branch['status']); ?>
                                            </span>
                                        </div>
                                        
                                        <div class="user-profile-info">
                                            <div class="user-info-item">
                                                <h5>Location</h5>
                                                <p><?php echo htmlspecialchars($branch['location']); ?></p>
                                            </div>
                                            
                                            <div class="user-info-item">
                                                <h5>Contact</h5>
                                                <p><?php echo htmlspecialchars($branch['contact_number']); ?></p>
                                            </div>
                                            
                                            <div class="user-info-item">
                                                <h5>Email</h5>
                                                <p><?php echo htmlspecialchars($branch['email']); ?></p>
                                            </div>
                                            
                                            <div class="user-info-item">
                                                <h5>Address</h5>
                                                <p><?php echo nl2br(htmlspecialchars($branch['address'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Recent Activities Card -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Recent Activities</div>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <?php foreach ($activities as $activity): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-item-content">
                                                <span class="tag" style="background-color: 
                                                    <?php echo $activity['type'] == 'member' ? '#1572E8' : 
                                                              ($activity['type'] == 'event' ? '#2BB930' : '#6861CE'); ?>">
                                                    <?php echo ucfirst($activity['type']); ?>
                                                </span>
                                                <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                                <span class="time">
                                                    <?php echo date('M d, Y', strtotime($activity['created_at'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($activities)): ?>
                                        <div class="text-center py-3">
                                            <p class="text-muted">No recent activities found</p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Head Pastor and Statistics -->
                        <div class="col-xl-8 col-lg-7">
                            <!-- Head Pastor Card -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Head Pastor</div>
                                    <?php if ($access['access_level'] === 'global'): ?>
                                    <div class="card-category">
                                        <a href="manage_branch.php?id=<?php echo $branch_id; ?>" class="btn btn-sm btn-link">
                                            <i class="fa fa-edit"></i> Change
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($branch['head_pastor_id']) && !empty($branch['head_pastor_name'])): ?>
                                    <div class="d-flex">
                                        <div class="avatar avatar-lg">
                                            <span class="avatar-title rounded-circle bg-info">
                                                <?php echo strtoupper(substr($branch['head_pastor_name'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div class="info-post ml-3">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($branch['head_pastor_name']); ?></h5>
                                            <p class="mb-0">Head Pastor</p>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-center py-3">
                                        <p class="text-muted">No head pastor assigned</p>
                                        <?php if ($access['access_level'] === 'global'): ?>
                                        <a href="manage_branch.php?id=<?php echo $branch_id; ?>" class="btn btn-sm btn-primary">
                                            <i class="fa fa-user-plus"></i> Assign Head Pastor
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Statistics Cards -->
                            <div class="row">
                                <!-- Members Card -->
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-round">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-icon">
                                                    <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                        <i class="fas fa-users"></i>
                                                    </div>
                                                </div>
                                                <div class="col col-stats ml-3 ml-sm-0">
                                                    <div class="numbers">
                                                        <p class="card-category">Members</p>
                                                        <h4 class="card-title"><?php echo $stats['total_members']; ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Events Card -->
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-round">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-icon">
                                                    <div class="icon-big text-center icon-success bubble-shadow-small">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </div>
                                                </div>
                                                <div class="col col-stats ml-3 ml-sm-0">
                                                    <div class="numbers">
                                                        <p class="card-category">Events</p>
                                                        <h4 class="card-title"><?php echo $stats['total_events']; ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Income Card -->
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-round">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-icon">
                                                    <div class="icon-big text-center icon-info bubble-shadow-small">
                                                        <i class="fas fa-dollar-sign"></i>
                                                    </div>
                                                </div>
                                                <div class="col col-stats ml-3 ml-sm-0">
                                                    <div class="numbers">
                                                        <p class="card-category">Income</p>
                                                        <h4 class="card-title"><?php echo number_format($stats['total_income'] ?? 0, 2); ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Expenses Card -->
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-round">
                                        <div class="card-body">
                                            <div class="row align-items-center">
                                                <div class="col-icon">
                                                    <div class="icon-big text-center icon-warning bubble-shadow-small">
                                                        <i class="fas fa-credit-card"></i>
                                                    </div>
                                                </div>
                                                <div class="col col-stats ml-3 ml-sm-0">
                                                    <div class="numbers">
                                                        <p class="card-category">Expenses</p>
                                                        <h4 class="card-title"><?php echo number_format($stats['total_expenses'] ?? 0, 2); ?></h4>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Branch Users Card -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <div class="card-title">Branch Users</div>
                                        <?php if (check_role_privileges(['admin', 'manager'])): ?>
                                        <a href="branch_users.php?branch_id=<?php echo $branch_id; ?>" class="btn btn-primary btn-round ml-auto btn-sm">
                                            <i class="fa fa-user-plus"></i> Manage Users
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Role</th>
                                                    <th>Email</th>
                                                    <th>Phone</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (empty($branch_users)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No users assigned to this branch</td>
                                                </tr>
                                                <?php else: ?>
                                                <?php foreach ($branch_users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($user['roles']); ?></span></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                                <?php endif; ?>
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

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    
    <!-- jQuery UI -->
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    
    <!-- jQuery Scrollbar -->
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>