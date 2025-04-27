<?php
require_once '../includes/auth.php';
require_once '../config.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['tithe_manager', 'finance', 'admin'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: ../access_denied.php');
    exit();
}

// Get filter parameters
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
$selectedMember = isset($_GET['member_id']) ? (int)$_GET['member_id'] : 0;
$selectedClass = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get available years for the filter
$years = $pdo->query("
    SELECT DISTINCT YEAR(payment_date) as year 
    FROM tithes 
    ORDER BY year DESC
")->fetchAll(PDO::FETCH_COLUMN);

// If no years in database yet, add current year
if (empty($years)) {
    $years = [date('Y')];
}

// Get bible classes for filter
$classes = $pdo->query("
    SELECT id, class_name 
    FROM bible_classes 
    WHERE status = 'active' 
    ORDER BY class_name
")->fetchAll();

// Get members based on selected class
$memberQuery = "
    SELECT id, CONCAT(first_name, ' ', surname) as full_name 
    FROM members 
    WHERE status = 'active'
";
if ($selectedClass) {
    $memberQuery .= " AND bible_class_id = " . $selectedClass;
}
$memberQuery .= " ORDER BY first_name, surname";
$members = $pdo->query($memberQuery)->fetchAll();

// Calculate tithe statistics
try {
    // Monthly tithe totals
    $monthlyQuery = "
        SELECT 
            currency,
            SUM(amount) as total_amount,
            COUNT(DISTINCT member_id) as unique_tithers
        FROM tithes 
        WHERE YEAR(payment_date) = ? AND MONTH(payment_date) = ?
    ";
    $params = [$selectedYear, $selectedMonth];
    
    if ($selectedMember) {
        $monthlyQuery .= " AND member_id = ?";
        $params[] = $selectedMember;
    }
    if ($selectedClass) {
        $monthlyQuery .= " AND member_id IN (SELECT id FROM members WHERE bible_class_id = ?)";
        $params[] = $selectedClass;
    }
    
    $monthlyQuery .= " GROUP BY currency";
    $stmt = $pdo->prepare($monthlyQuery);
    $stmt->execute($params);
    $monthlyTotals = $stmt->fetchAll();
    
    // Yearly tithe totals
    $yearlyQuery = "
        SELECT 
            currency,
            SUM(amount) as total_amount,
            COUNT(DISTINCT member_id) as unique_tithers
        FROM tithes 
        WHERE YEAR(payment_date) = ?
    ";
    $params = [$selectedYear];
    
    if ($selectedMember) {
        $yearlyQuery .= " AND member_id = ?";
        $params[] = $selectedMember;
    }
    if ($selectedClass) {
        $yearlyQuery .= " AND member_id IN (SELECT id FROM members WHERE bible_class_id = ?)";
        $params[] = $selectedClass;
    }
    
    $yearlyQuery .= " GROUP BY currency";
    $stmt = $pdo->prepare($yearlyQuery);
    $stmt->execute($params);
    $yearlyTotals = $stmt->fetchAll();
    
    // Get tithe records
    $recordsQuery = "
        SELECT 
            t.*,
            CONCAT(m.first_name, ' ', m.surname) as member_name,
            bc.class_name,
            CONCAT(u.first_name, ' ', u.surname) as recorded_by_name
        FROM tithes t
        JOIN members m ON t.member_id = m.id
        LEFT JOIN bible_classes bc ON m.bible_class_id = bc.id
        LEFT JOIN users u ON t.recorded_by = u.id
        WHERE YEAR(t.payment_date) = ? AND MONTH(t.payment_date) = ?
    ";
    $params = [$selectedYear, $selectedMonth];
    
    if ($selectedMember) {
        $recordsQuery .= " AND t.member_id = ?";
        $params[] = $selectedMember;
    }
    if ($selectedClass) {
        $recordsQuery .= " AND m.bible_class_id = ?";
        $params[] = $selectedClass;
    }
    
    $recordsQuery .= " ORDER BY t.payment_date DESC, t.created_at DESC";
    $stmt = $pdo->prepare($recordsQuery);
    $stmt->execute($params);
    $titheRecords = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $_SESSION['message'] = "<div class='alert alert-danger'>Error fetching tithe data: " . $e->getMessage() . "</div>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tithe Tracking - ChurchEase</title>
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
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Tithe Tracking</h4>
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
                                <a href="#">Tithe Tracking</a>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if(isset($_SESSION['message'])) {
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                    } ?>
                    
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="form-inline">
                                        <div class="form-group mr-2">
                                            <label class="mr-2">Year:</label>
                                            <select name="year" class="form-control">
                                                <?php foreach ($years as $year): ?>
                                                <option value="<?php echo $year; ?>" <?php echo $year == $selectedYear ? 'selected' : ''; ?>>
                                                    <?php echo $year; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2">
                                            <label class="mr-2">Month:</label>
                                            <select name="month" class="form-control">
                                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == $selectedMonth ? 'selected' : ''; ?>>
                                                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                                </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2">
                                            <label class="mr-2">Class:</label>
                                            <select name="class_id" class="form-control">
                                                <option value="">All Classes</option>
                                                <?php foreach ($classes as $class): ?>
                                                <option value="<?php echo $class['id']; ?>" <?php echo $class['id'] == $selectedClass ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group mr-2">
                                            <label class="mr-2">Member:</label>
                                            <select name="member_id" class="form-control select2">
                                                <option value="">All Members</option>
                                                <?php foreach ($members as $member): ?>
                                                <option value="<?php echo $member['id']; ?>" <?php echo $member['id'] == $selectedMember ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($member['full_name']); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Statistics -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Monthly Statistics (<?php echo date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)); ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($monthlyTotals)): ?>
                                        <div class="alert alert-info">No tithe records found for this month.</div>
                                    <?php else: ?>
                                        <?php foreach ($monthlyTotals as $total): ?>
                                        <p>
                                            <strong><?php echo $total['currency']; ?>:</strong> 
                                            <?php echo number_format($total['total_amount'], 2); ?>
                                        </p>
                                        <?php endforeach; ?>
                                        <p><strong>Unique Tithers:</strong> <?php echo $monthlyTotals[0]['unique_tithers']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Yearly Statistics (<?php echo $selectedYear; ?>)</h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($yearlyTotals)): ?>
                                        <div class="alert alert-info">No tithe records found for this year.</div>
                                    <?php else: ?>
                                        <?php foreach ($yearlyTotals as $total): ?>
                                        <p>
                                            <strong><?php echo $total['currency']; ?>:</strong> 
                                            <?php echo number_format($total['total_amount'], 2); ?>
                                        </p>
                                        <?php endforeach; ?>
                                        <p><strong>Unique Tithers:</strong> <?php echo $yearlyTotals[0]['unique_tithers']; ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tithe Records -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Tithe Records</h4>
                                        <div class="ml-auto">
                                            <a href="tithe_recording.php" class="btn btn-primary btn-round ml-auto">
                                                <i class="fa fa-plus"></i> Record Tithe
                                            </a>
                                            <button class="btn btn-success btn-round ml-2" onclick="exportToExcel()">
                                                <i class="fa fa-file-excel"></i> Export to Excel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="tithe-records" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Member</th>
                                                    <th>Class</th>
                                                    <th>Amount</th>
                                                    <th>Currency</th>
                                                    <th>Payment Method</th>
                                                    <th>Reference</th>
                                                    <th>Recorded By</th>
                                                    <th>Recorded On</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($titheRecords as $record): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d', strtotime($record['payment_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($record['member_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                                    <td><?php echo number_format($record['amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($record['currency']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['payment_method']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['reference_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($record['recorded_by_name']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?></td>
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
    <?php include '../res/scripts.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: "bootstrap"
            });
            
            // Initialize DataTable
            $('#tithe-records').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]],
                "responsive": true
            });
        });
        
        // Export to Excel function
        function exportToExcel() {
            window.location.href = 'tithe_export.php?' + new URLSearchParams(window.location.search);
        }
    </script>
</body>
</html>