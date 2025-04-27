<?php
// File: c:/xampp/htdocs/outpouringcrm/home/dashboard.php

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'executive_admin_1'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// Initialize default values for statistics
$memberStats = [
    'total_members' => 0,
    'male_members' => 0,
    'female_members' => 0,
    'new_members' => 0
];

$userStats = [
    'total_users' => 0,
    'super_admin_users' => 0,
    'executive_users' => 0,
    'active_users' => 0
];

$recentLogins = [];
$failedLogins = ['failed_count' => 0];
$recentMembers = [];
$memberGroups = [];

// Get various statistics
try {
    // Check if members table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'members'");
    $membersTableExists = $stmt->fetchColumn();

    if ($membersTableExists) {
        // Members Statistics
        $memberStatsQuery = $pdo->query("
            SELECT 
                COUNT(*) as total_members,
                COUNT(CASE WHEN gender = 'Male' THEN 1 END) as male_members,
                COUNT(CASE WHEN gender = 'Female' THEN 1 END) as female_members,
                COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_members
            FROM members
        ");
        if ($memberStatsQuery) {
            $memberStats = $memberStatsQuery->fetch(PDO::FETCH_ASSOC) ?: $memberStats;
        }

        // Recent Members
        $recentMembersQuery = $pdo->query("
            SELECT * FROM members 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        if ($recentMembersQuery) {
            $recentMembers = $recentMembersQuery->fetchAll(PDO::FETCH_ASSOC);
        }

        // Member Groups Distribution
        $memberGroupsQuery = $pdo->query("
            SELECT member_group, COUNT(*) as count 
            FROM members 
            WHERE member_group IS NOT NULL 
            GROUP BY member_group 
            ORDER BY count DESC
            LIMIT 5
        ");
        if ($memberGroupsQuery) {
            $memberGroups = $memberGroupsQuery->fetchAll(PDO::FETCH_ASSOC);
        }
    }

    // User Statistics using user_roles
    $userStatsQuery = $pdo->query("
        SELECT 
            COUNT(DISTINCT u.id) as total_users,
            COUNT(DISTINCT CASE WHEN ur.role_key = 'super_admin' THEN u.id END) as super_admin_users,
            COUNT(DISTINCT CASE WHEN ur.role_key IN ('executive_admin_1', 'executive_admin_2') THEN u.id END) as executive_users,
            COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.id END) as active_users
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
    ");
    if ($userStatsQuery) {
        $userStats = $userStatsQuery->fetch(PDO::FETCH_ASSOC) ?: $userStats;
    }

    // Check if audit_logs table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'audit_logs'");
    $auditLogsTableExists = $stmt->fetchColumn();

    if ($auditLogsTableExists) {
        // Recent Login Activity
        $recentLoginsQuery = $pdo->query("
            SELECT al.*, u.username, u.full_name 
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            WHERE al.action = 'login' AND al.status = 'success'
            ORDER BY al.created_at DESC
            LIMIT 5
        ");
        if ($recentLoginsQuery) {
            $recentLogins = $recentLoginsQuery->fetchAll(PDO::FETCH_ASSOC);
        }

        // Failed Login Attempts
        $failedLoginsQuery = $pdo->query("
            SELECT COUNT(*) as failed_count 
            FROM audit_logs 
            WHERE action = 'login' 
            AND status = 'failed' 
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        if ($failedLoginsQuery) {
            $failedLogins = $failedLoginsQuery->fetch(PDO::FETCH_ASSOC) ?: ['failed_count' => 0];
        }
    }

} catch (PDOException $e) {
    error_log("Dashboard Error: " . $e->getMessage());
    if (in_array($_SESSION['user_role'], ['super_admin', 'executive_admin_1', 'executive_admin_2'])) {
        $error_message = "Error loading dashboard statistics: " . htmlspecialchars($e->getMessage());
    }
}

// Prepare chart data
$chartData = [
    'memberGroups' => array_map(function($group) {
        return [
            'label' => $group['member_group'] ?? 'Unknown',
            'count' => $group['count'] ?? 0
        ];
    }, $memberGroups)
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Dashboard - ChurchEaseSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />


    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
<link rel="stylesheet" href="../res/assets/css/custom-brown.css">
    <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="../loginres/images/favicon/site.webmanifest">
    <link rel="stylesheet" href="../res/assets/css/fonts.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="panel-header bg-primary-gradient">
                    <div class="page-inner py-5">
                        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row">
                            <div>
                                <h2 class="text-white pb-2 fw-bold">Dashboard</h2>
                                <h5 class="text-white op-7 mb-2">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></h5>
                            </div>
                            <div class="ml-md-auto py-2 py-md-0">
                                <a href="../membership/member_management.php" class="btn btn-white btn-border btn-round mr-2">Manage Members</a>
                                <a href="../membership/manage_user.php" class="btn btn-secondary btn-round">Manage Users</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="page-inner mt--5">
                    <?php if (isset($error_message)): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mt--2">
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
                                                <p class="card-category">Total Members</p>
                                                <h4 class="card-title"><?php echo number_format($memberStats['total_members']); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="far fa-user"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Active Users</p>
                                                <h4 class="card-title"><?php echo number_format($userStats['active_users']); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-success bubble-shadow-small">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">New Members</p>
                                                <h4 class="card-title"><?php echo number_format($memberStats['new_members']); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                                <i class="fas fa-exclamation-triangle"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Failed Logins (24h)</p>
                                                <h4 class="card-title"><?php echo number_format($failedLogins['failed_count']); ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Member Group Distribution</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="memberGroupChart"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">User Role Distribution</div>
                                </div>
                                <div class="card-body">
                                    <canvas id="userRoleChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Row -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Recent Login Activity</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Time</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentLogins as $login): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($login['full_name'] ?? $login['username']); ?></td>
                                                    <td><?php echo date('M j, Y H:i', strtotime($login['created_at'])); ?></td>
                                                    <td>
                                                        <span class="badge badge-success">Success</span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Recent Members</div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Contact</th>
                                                    <th>Joined</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($recentMembers as $member): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['phone'] ?? $member['email']); ?></td>
                                                    <td><?php echo date('M j, Y', strtotime($member['created_at'])); ?></td>
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
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <!-- Chart Initialization -->
    <script>
        // Member Groups Chart
        var memberCtx = document.getElementById('memberGroupChart').getContext('2d');
        new Chart(memberCtx, {
            type: 'pie',
            data: {
                labels: <?php echo json_encode(array_column($chartData['memberGroups'], 'label')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($chartData['memberGroups'], 'count')); ?>,
                    backgroundColor: ['#1572E8', '#2E9CDA', '#48ABF7', '#6DBDF1', '#8FCAE7']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // User Roles Chart
        var userCtx = document.getElementById('userRoleChart').getContext('2d');
        new Chart(userCtx, {
            type: 'pie',
            data: {
                labels: ['Super Admin', 'Executive', 'Other Active Users'],
                datasets: [{
                    data: [
                        <?php echo $userStats['super_admin_users']; ?>,
                        <?php echo $userStats['executive_users']; ?>,
                        <?php echo $userStats['active_users'] - $userStats['super_admin_users'] - $userStats['executive_users']; ?>
                    ],
                    backgroundColor: ['#1572E8', '#2E9CDA', '#48ABF7']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>