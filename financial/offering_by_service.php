<?php
require_once '../includes/auth.php';
require_once '../config.php';


// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'finance'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// Get the year and month from query parameters or use current date
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Get all days in the selected month
$firstDay = new DateTime("$year-$month-01");
$lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));
$days = [];

$current = clone $firstDay;
while ($current <= $lastDay) {
    $days[] = $current->format('Y-m-d');
    $current->modify('+1 day');
}

// Fetch all events
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_name");
$events = $stmt->fetchAll();

// Initialize arrays for totals
$eventTotals = [];
$dailyTotals = [];
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];

foreach ($currencies as $currency) {
    $dailyTotals[$currency] = array_fill_keys($days, 0);
    foreach ($events as $event) {
        $eventTotals[$event['id']][$currency] = 0;
    }
}

// Fetch offering data
$offeringData = [];
foreach ($events as $event) {
    $offeringData[$event['id']] = [
        'event_name' => $event['event_name'],
        'location' => $event['location'],
        'days' => [],
        'totals' => array_fill_keys($currencies, 0)
    ];
    
    foreach ($days as $day) {
        $stmt = $pdo->prepare("
            SELECT o.*, e.event_name, e.location
            FROM offerings o
            JOIN events e ON o.event_id = e.id
            WHERE o.event_id = ? AND DATE(o.offering_date) = ?
            ORDER BY o.offering_date
        ");
        $stmt->execute([$event['id'], $day]);
        $dayOfferings = $stmt->fetchAll();
        $offeringData[$event['id']]['days'][$day] = $dayOfferings;
        
        foreach ($dayOfferings as $offering) {
            $offeringData[$event['id']]['totals'][$offering['currency']] += $offering['amount'];
            $dailyTotals[$offering['currency']][$day] += $offering['amount'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Offerings by Event - Church Management System</title>
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
        .day-total { background-color: #f8f9fa; }
        .grand-total { font-weight: bold; background-color: #e9ecef; }
        .event-location { font-size: 0.9em; color: #666; }
        .offering-notes { font-size: 0.85em; color: #777; font-style: italic; }
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
                        <h4 class="page-title">Offerings by Event</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">
                                            Offerings for <?php echo date('F Y', strtotime("$year-$month-01")); ?>
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
                                        <h5 class="mt-4 mb-3">Offerings in <?php echo $currency; ?></h5>
                                        <div class="table-responsive">
                                            <table class="offering-table display table table-striped table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Event</th>
                                                        <?php foreach($days as $day): ?>
                                                            <th><?php echo date('M d', strtotime($day)); ?></th>
                                                        <?php endforeach; ?>
                                                        <th>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($offeringData as $eventId => $data): ?>
                                                        <tr>
                                                            <td>
                                                                <?php 
                                                                echo htmlspecialchars($data['event_name']);
                                                                if ($data['location']) {
                                                                    echo '<br><span class="event-location">@ ' . htmlspecialchars($data['location']) . '</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <?php foreach($days as $day): ?>
                                                                <td class="text-right">
                                                                    <?php
                                                                    $dayTotal = 0;
                                                                    $notes = [];
                                                                    foreach ($data['days'][$day] as $offering) {
                                                                        if ($offering['currency'] === $currency) {
                                                                            $dayTotal += $offering['amount'];
                                                                            if ($offering['notes']) {
                                                                                $notes[] = $offering['notes'];
                                                                            }
                                                                        }
                                                                    }
                                                                    if ($dayTotal > 0) {
                                                                        echo number_format($dayTotal, 2);
                                                                        if (!empty($notes)) {
                                                                            echo '<br><span class="offering-notes">' . 
                                                                                  htmlspecialchars(implode(', ', array_unique($notes))) . 
                                                                                  '</span>';
                                                                        }
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                    ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($data['totals'][$currency], 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <!-- Daily Totals Row -->
                                                    <tr class="day-total">
                                                        <td><strong>Daily Total</strong></td>
                                                        <?php 
                                                        $monthTotal = 0;
                                                        foreach($days as $day): 
                                                            $monthTotal += $dailyTotals[$currency][$day];
                                                        ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($dailyTotals[$currency][$day], 2); ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                        <td class="text-right grand-total">
                                                            <?php echo number_format($monthTotal, 2); ?>
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
            $('.offering-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 
                    'csv', 
                    {
                        extend: 'excel',
                        title: 'Offerings by Event - ' + $('.card-title').text(),
                        messageTop: 'Event Offerings'
                    },
                    {
                        extend: 'pdf',
                        title: 'Offerings by Event - ' + $('.card-title').text(),
                        messageTop: 'Event Offerings'
                    },
                    'print'
                ],
                pageLength: 25,
                order: [[0, 'asc']]
            });
        });
    </script>
</body>
</html>