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

// Get the year and month from query parameters or use current date
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Get all Sundays in the selected month
$firstDay = new DateTime("$year-$month-01");
$lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));
$sundays = [];

$sunday = new DateTime($firstDay->format('Y-m-d'));
$sunday->modify('next sunday');

while ($sunday <= $lastDay) {
    $sundays[] = $sunday->format('Y-m-d');
    $sunday->modify('+1 week');
}

// Fetch all bible classes
$bibleClasses = $pdo->query("SELECT * FROM bible_classes ORDER BY class_name")->fetchAll();

// Fetch summary data for each currency
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];
$summaryData = [];

foreach ($bibleClasses as $class) {
    $summaryData[$class['id']] = [
        'class_name' => $class['class_name'],
        'sundays' => []
    ];
    
    foreach ($sundays as $sunday) {
        $summaryData[$class['id']]['sundays'][$sunday] = [];
        foreach ($currencies as $currency) {
            $stmt = $pdo->prepare("
                SELECT SUM(t.amount) as total
                FROM tithes t
                JOIN members m ON t.member_id = m.id
                WHERE m.bible_class_id = ? 
                AND DATE(t.tithe_date) = ?
                AND t.currency = ?
            ");
            $stmt->execute([$class['id'], $sunday, $currency]);
            $result = $stmt->fetch();
            if ($result['total']) {
                $summaryData[$class['id']]['sundays'][$sunday][$currency] = $result['total'];
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Tithe Summary by Bible Class - ChurchEaseSuperb</title>
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
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Tithe Summary by Bible Class</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">
                                            Summary for <?php echo date('F Y', strtotime("$year-$month-01")); ?>
                                        </h4>
                                        <div class="ml-auto">
                                            <form class="form-inline">
                                                <select name="month" class="form-control mr-2">
                                                    <?php for($m = 1; $m <= 12; $m++): ?>
                                                        <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
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
                                        <h5 class="mt-4">Summary in <?php echo $currency; ?></h5>
                                        <div class="table-responsive">
                                            <table class="table table-bordered tithe-summary-table">
                                                <thead>
                                                    <tr>
                                                        <th>Bible Class</th>
                                                        <?php foreach($sundays as $sunday): ?>
                                                            <th><?php echo date('M d', strtotime($sunday)); ?></th>
                                                        <?php endforeach; ?>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($summaryData as $classId => $data): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($data['class_name']); ?></td>
                                                            <?php 
                                                            $total = 0;
                                                            foreach($sundays as $sunday): 
                                                                $amount = $data['sundays'][$sunday][$currency] ?? 0;
                                                                $total += $amount;
                                                            ?>
                                                                <td class="text-right">
                                                                    <?php echo $amount ? number_format($amount, 2) : '-'; ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($total, 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
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
            $('.tithe-summary-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                pageLength: 25,
                order: [[0, 'asc']]
            });
        });
    </script>
</body>
</html>