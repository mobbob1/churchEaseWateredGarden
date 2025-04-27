<?php
require_once '../includes/auth.php';
require_once '../config.php';


// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'finance'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Get the year from query parameters or use current year
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Get all Sundays in the year
$sundays = [];
$monthNames = [];
$sundaysByMonth = [];

$startDate = new DateTime("$year-01-01");
$endDate = new DateTime("$year-12-31");
$interval = new DateInterval('P1D');
$dateRange = new DatePeriod($startDate, $interval, $endDate);

foreach ($dateRange as $date) {
    if ($date->format('w') == 0) { // 0 = Sunday
        $month = $date->format('n');
        $sundays[] = $date->format('Y-m-d');
        $monthNames[$month] = $date->format('F');
        $sundaysByMonth[$month][] = $date->format('Y-m-d');
    }
}

// Fetch all bible classes
$bibleClasses = $pdo->query("SELECT * FROM bible_classes ORDER BY class_name")->fetchAll();

// Initialize arrays for totals
$classTotals = [];
$sundayTotals = [];
$monthlyTotals = [];
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];

foreach ($currencies as $currency) {
    $sundayTotals[$currency] = array_fill_keys($sundays, 0);
    $monthlyTotals[$currency] = array_fill_keys(range(1, 12), 0);
    foreach ($bibleClasses as $class) {
        $classTotals[$class['id']][$currency] = array_fill_keys(range(1, 12), 0);
    }
}

// Fetch tithe data for each Sunday and Bible Class
$titheData = [];
foreach ($bibleClasses as $class) {
    $titheData[$class['id']] = [
        'class_name' => $class['class_name'],
        'months' => array_fill_keys(range(1, 12), []),
        'totals' => array_fill_keys($currencies, array_fill_keys(range(1, 12), 0))
    ];
}

// Fetch all tithe data for the year
$stmt = $pdo->prepare("
    SELECT t.*, m.name as member_name, m.bible_class_id, DATE(t.tithe_date) as date
    FROM tithes t
    JOIN members m ON t.member_id = m.id
    WHERE YEAR(t.tithe_date) = ? AND DAYOFWEEK(t.tithe_date) = 1
    ORDER BY t.tithe_date, m.bible_class_id, m.name
");
$stmt->execute([$year]);
$allTithes = $stmt->fetchAll();

// Organize tithe data
foreach ($allTithes as $tithe) {
    $month = date('n', strtotime($tithe['date']));
    $classId = $tithe['bible_class_id'];
    
    if (!isset($titheData[$classId]['months'][$month][$tithe['date']])) {
        $titheData[$classId]['months'][$month][$tithe['date']] = [];
    }
    
    $titheData[$classId]['months'][$month][$tithe['date']][] = $tithe;
    $titheData[$classId]['totals'][$tithe['currency']][$month] += $tithe['amount'];
    $sundayTotals[$tithe['currency']][date('Y-m-d', strtotime($tithe['date']))] += $tithe['amount'];
    $monthlyTotals[$tithe['currency']][$month] += $tithe['amount'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Yearly Tithe Report - ChurchEaseSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
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
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.bootstrap4.min.css">
    
    <style>
        .currency-total { font-weight: bold; color: #2980b9; }
        .sunday-total { background-color: #f8f9fa; }
        .grand-total { font-weight: bold; background-color: #e9ecef; }
        .month-header { background-color: #f1f8ff; font-weight: bold; }
        .sticky-header { position: sticky; top: 0; background: white; z-index: 1; }
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
                        <h4 class="page-title">Yearly Tithe Report</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">
                                            Tithes for Year <?php echo $year; ?>
                                        </h4>
                                        <div class="ml-auto">
                                            <form class="form-inline">
                                                <select name="year" class="form-control mr-2">
                                                    <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                                            <?php echo $y; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($currencies as $currency): ?>
                                        <h5 class="mt-4 mb-3">Tithes in <?php echo $currency; ?></h5>
                                        <div class="table-responsive">
                                            <table class="tithe-table display table table-striped table-hover">
                                                <thead class="sticky-header">
                                                    <tr>
                                                        <th>Bible Class</th>
                                                        <?php foreach(range(1, 12) as $month): ?>
                                                            <th colspan="<?php echo count($sundaysByMonth[$month] ?? []); ?>" 
                                                                class="text-center month-header">
                                                                <?php echo $monthNames[$month]; ?>
                                                            </th>
                                                            <th class="text-center month-header">Total</th>
                                                        <?php endforeach; ?>
                                                        <th>Year Total</th>
                                                    </tr>
                                                    <tr>
                                                        <th>Class Name</th>
                                                        <?php foreach(range(1, 12) as $month): ?>
                                                            <?php foreach($sundaysByMonth[$month] ?? [] as $sunday): ?>
                                                                <th><?php echo date('d', strtotime($sunday)); ?></th>
                                                            <?php endforeach; ?>
                                                            <th>Total</th>
                                                        <?php endforeach; ?>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($titheData as $classId => $data): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($data['class_name']); ?></td>
                                                            <?php 
                                                            $yearTotal = 0;
                                                            foreach(range(1, 12) as $month): 
                                                                $monthTotal = 0;
                                                                foreach($sundaysByMonth[$month] ?? [] as $sunday):
                                                                    $sundayData = $data['months'][$month][$sunday] ?? [];
                                                                    $sundayTotal = 0;
                                                                    foreach($sundayData as $tithe) {
                                                                        if ($tithe['currency'] === $currency) {
                                                                            $sundayTotal += $tithe['amount'];
                                                                            $monthTotal += $tithe['amount'];
                                                                        }
                                                                    }
                                                            ?>
                                                                    <td class="text-right">
                                                                        <?php echo $sundayTotal > 0 ? number_format($sundayTotal, 2) : '-'; ?>
                                                                    </td>
                                                            <?php 
                                                                endforeach;
                                                                $yearTotal += $monthTotal;
                                                            ?>
                                                                <td class="text-right font-weight-bold">
                                                                    <?php echo number_format($monthTotal, 2); ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($yearTotal, 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <!-- Monthly Totals Row -->
                                                    <tr class="sunday-total">
                                                        <td><strong>Monthly Total</strong></td>
                                                        <?php 
                                                        $yearTotal = 0;
                                                        foreach(range(1, 12) as $month): 
                                                            foreach($sundaysByMonth[$month] ?? [] as $sunday):
                                                        ?>
                                                                <td class="text-right font-weight-bold">
                                                                    <?php echo number_format($sundayTotals[$currency][$sunday], 2); ?>
                                                                </td>
                                                        <?php 
                                                            endforeach;
                                                            $yearTotal += $monthlyTotals[$currency][$month];
                                                        ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($monthlyTotals[$currency][$month], 2); ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                        <td class="text-right grand-total">
                                                            <?php echo number_format($yearTotal, 2); ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endforeach; ?>
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
    
    <!-- DataTables -->
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
            $('.tithe-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 
                    'csv', 
                    {
                        extend: 'excel',
                        title: 'Yearly Tithe Report - ' + <?php echo $year; ?>,
                        messageTop: 'Bible Class Tithes - All Sundays'
                    },
                    {
                        extend: 'pdf',
                        title: 'Yearly Tithe Report - ' + <?php echo $year; ?>,
                        messageTop: 'Bible Class Tithes - All Sundays'
                    },
                    'print'
                ],
                pageLength: 25,
                order: [[0, 'asc']],
                scrollX: true
            });
        });
    </script>
</body>
</html>