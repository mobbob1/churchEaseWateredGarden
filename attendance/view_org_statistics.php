<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';
require_once __DIR__ . '/../config.php';

$logger = new AuditLogger($pdo);

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: /outpouringcrm/index.php?error=unauthorized');
    exit();
}

// Function to get all Sundays in a month
function getSundaysInMonth($year, $month) {
    $sundays = [];
    $date = new DateTime("$year-$month-01");
    $date->modify('first sunday of this month');
    
    while ($date->format('m') == $month) {
        $sundays[] = $date->format('Y-m-d');
        $date->modify('next sunday');
    }
    return $sundays;
}

// Get all organizations
try {
    $stmt = $pdo->query("SELECT * FROM organizations ORDER BY organization_name");
    $organizations = $stmt->fetchAll();
    
    // Create fields array from organizations
    $fields = [];
    foreach ($organizations as $org) {
        $fields[$org['id']] = $org['organization_name'];
    }
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Error fetching organizations: " . $e->getMessage() . "</div>";
    $organizations = [];
    $fields = [];
}

// Get statistics grouped by month
try {
    $stmt = $pdo->query("
        SELECT o.id as organization_id, o.organization_name, os.month, osd.*
        FROM organizations o
        LEFT JOIN organization_statistics os ON o.id = os.organization_id
        LEFT JOIN organization_statistics_details osd ON os.id = osd.statistics_id
        ORDER BY os.month DESC, o.organization_name
    ");
    $allStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group statistics by month
    $statistics = [];
    foreach ($allStats as $stat) {
        if (!isset($statistics[$stat['month']])) {
            $statistics[$stat['month']] = [];
        }
        $statistics[$stat['month']][$stat['organization_id']] = $stat;
    }
    krsort($statistics); // Sort months in descending order
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Error fetching statistics: " . $e->getMessage() . "</div>";
    $statistics = [];
}

// Handle export
if (isset($_POST['export']) && $_POST['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="organization_statistics_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    $headers = ['Month', 'Organization', 'Sunday 1', 'Sunday 2', 'Sunday 3', 'Sunday 4', 'Sunday 5', 'Total'];
    fputcsv($output, $headers);
    
    // Write data
    foreach ($statistics as $month => $month_stats) {
        foreach ($fields as $org_id => $org_name) {
            $row = [
                $month,
                $org_name
            ];
            
            $stat = $month_stats[$org_id] ?? null;
            $total = 0;
            for ($i = 1; $i <= 5; $i++) {
                $value = $stat ? ($stat["sunday$i"] ?? 0) : 0;
                $row[] = $value;
                $total += $value;
            }
            $row[] = $total;
            
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Organization Statistics - ChurchEase</title>
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
    
    <!-- DataTables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.css"/>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">View Organization Statistics</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Statistics</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Organization Statistics</h4>
                                        <div class="ml-auto">
                                            <a href="weekly_org_statistics.php" class="btn btn-primary">
                                                <i class="fa fa-plus"></i> Add New Statistics
                                            </a>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    Export
                                                </button>
                                                <div class="dropdown-menu">
                                                    <form method="POST">
                                                        <input type="hidden" name="export" value="csv">
                                                        <button type="submit" class="dropdown-item">Export to CSV</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($statistics as $month => $month_stats): ?>
                                        <div class="mb-4">
                                            <h5 class="mb-3"><?php echo date('F Y', strtotime($month)); ?></h5>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>Organization</th>
                                                            <?php 
                                                            $sundays = getSundaysInMonth(substr($month, 0, 4), substr($month, 5, 2));
                                                            foreach ($sundays as $index => $sunday): 
                                                            ?>
                                                                <th>Sunday <?php echo $index + 1; ?><br>(<?php echo date('M d', strtotime($sunday)); ?>)</th>
                                                            <?php endforeach; ?>
                                                            <th>Total</th>
                                                            <th>Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($fields as $org_id => $org_name): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($org_name); ?></td>
                                                                <?php 
                                                                $stat = $month_stats[$org_id] ?? null;
                                                                $total = 0;
                                                                for ($i = 1; $i <= count($sundays); $i++): 
                                                                    $value = $stat ? ($stat["sunday$i"] ?? 0) : 0;
                                                                    $total += $value;
                                                                ?>
                                                                    <td><?php echo $value; ?></td>
                                                                <?php endfor; ?>
                                                                <td><strong><?php echo $total; ?></strong></td>
                                                                <td>
                                                                    <a href="weekly_org_statistics.php?month=<?php echo $month; ?>" 
                                                                       class="btn btn-sm btn-primary">
                                                                        <i class="fa fa-edit"></i> Edit
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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
    <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <!-- DataTables -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
</body>
</html>