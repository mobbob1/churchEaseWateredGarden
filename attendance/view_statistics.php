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

// Define fields array
$fields = [
    'church_services' => 'Church Services Attendance',
    'adults_services' => 'Adults Services Attendance',
    'youth_services' => 'Youth/Teens Services Attendance',
    'children_services' => 'Children Services Attendance',
    'communion_services' => 'Communion Services Participants',
    'new_members' => 'New Members Received at Leaders Meeting',
    'visitors' => 'Visitors',
    'prayer_meeting' => 'Prayer Meeting Attendance',
    'mprp_attendance' => 'MPRP Attendance',
    'revival_meeting' => 'Revival Meeting Attendance',
    'outreach_members' => 'Outreach Members Participating',
    'souls_won' => 'Souls Won(New Members)',
    'marriages' => 'Marriages',
    'births' => 'Births',
    'baptisms' => 'Baptisms',
    'confirmations' => 'Confirmations',
    'deaths' => 'Deaths'
];

// Handle export
if (isset($_POST['export']) && $_POST['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="statistics_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // Write headers
    $headers = ['Month'];
    foreach ($fields as $field_name) {
        $headers[] = $field_name;
    }
    fputcsv($output, $headers);
    
    // Fetch and write data
    $stmt = $pdo->query("
        SELECT cs.month, csd.operation, SUM(csd.total) as total
        FROM church_statistics cs
        LEFT JOIN church_statistics_details csd ON cs.id = csd.statistics_id
        GROUP BY cs.month, csd.operation
        ORDER BY cs.month DESC
    ");
    
    $data = [];
    while ($row = $stmt->fetch()) {
        if (!isset($data[$row['month']])) {
            $data[$row['month']] = array_fill_keys(array_keys($fields), 0);
        }
        $data[$row['month']][$row['operation']] = $row['total'];
    }
    
    foreach ($data as $month => $values) {
        $row = [$month];
        foreach ($fields as $field_key => $field_name) {
            $row[] = $values[$field_key] ?? 0;
        }
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Get all statistics grouped by month
$stmt = $pdo->query("
    SELECT 
        cs.month,
        cs.id as statistics_id,
        csd.operation,
        csd.sunday1,
        csd.sunday2,
        csd.sunday3,
        csd.sunday4,
        csd.sunday5,
        csd.total
    FROM church_statistics cs
    LEFT JOIN church_statistics_details csd ON cs.id = csd.statistics_id
    ORDER BY cs.month DESC
");

// Reorganize the data
$statistics = [];
while ($row = $stmt->fetch()) {
    if (!isset($statistics[$row['month']])) {
        $statistics[$row['month']] = [];
    }
    $statistics[$row['month']][$row['operation']] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Statistics - ChurchEase</title>
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
                        <h4 class="page-title">View Statistics</h4>
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
                                        <h4 class="card-title">Monthly Statistics</h4>
                                        <div class="ml-auto">
                                            <a href="weekly_statistics.php" class="btn btn-primary">
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
                                    <?php if (empty($statistics)): ?>
                                        <div class="alert alert-info">No statistics available yet.</div>
                                    <?php else: ?>
                                        <?php foreach ($statistics as $month => $month_data): ?>
                                            <div class="mb-4">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h5><?php echo date('F Y', strtotime($month)); ?></h5>
                                                    <a href="weekly_statistics.php?month=<?php echo $month; ?>" 
                                                    class="btn btn-sm btn-primary">
                                                        <i class="fa fa-edit"></i> Edit
                                                    </a>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                        <thead>
                                                            <tr>
                                                                <th>Operations</th>
                                                                <?php 
                                                                $year = substr($month, 0, 4);
                                                                $m = substr($month, 5, 2);
                                                                $sundays = getSundaysInMonth($year, $m);
                                                                foreach ($sundays as $sunday): 
                                                                ?>
                                                                    <th><?php echo date('M d', strtotime($sunday)); ?></th>
                                                                <?php endforeach; ?>
                                                                <th>Total</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php 
                                                            $monthly_totals = array_fill(0, count($sundays), 0);
                                                            $grand_total = 0;
                                                            
                                                            foreach ($fields as $field_key => $field_name): 
                                                                $field_data = $month_data[$field_key] ?? null;
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo $field_name; ?></td>
                                                                    <?php 
                                                                    $row_total = 0;
                                                                    for ($i = 1; $i <= count($sundays); $i++): 
                                                                        $value = isset($field_data["sunday$i"]) ? $field_data["sunday$i"] : 0;
                                                                        $row_total += $value;
                                                                        $monthly_totals[$i-1] += $value;
                                                                    ?>
                                                                        <td><?php echo $value; ?></td>
                                                                    <?php endfor; ?>
                                                                    <td><strong><?php echo $row_total; ?></strong></td>
                                                                    <?php $grand_total += $row_total; ?>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                            <tr class="bg-light">
                                                                <td><strong>Daily Totals</strong></td>
                                                                <?php foreach ($monthly_totals as $total): ?>
                                                                    <td><strong><?php echo $total; ?></strong></td>
                                                                <?php endforeach; ?>
                                                                <td><strong><?php echo $grand_total; ?></strong></td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
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
    <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>