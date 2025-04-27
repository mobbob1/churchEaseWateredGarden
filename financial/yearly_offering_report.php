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

// Initialize arrays for totals
$eventTotals = [];
$monthlyTotals = [];
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];

// Fetch all events
$events = $pdo->query("SELECT * FROM events ORDER BY event_name")->fetchAll();

foreach ($currencies as $currency) {
    $monthlyTotals[$currency] = array_fill_keys(range(1, 12), 0);
    foreach ($events as $event) {
        $eventTotals[$event['id']][$currency] = array_fill_keys(range(1, 12), 0);
    }
}

// Fetch offering data for the year
$offeringData = [];
foreach ($events as $event) {
    $offeringData[$event['id']] = [
        'event_name' => $event['event_name'],
        'location' => $event['location'],
        'description' => $event['description'],
        'months' => array_fill_keys(range(1, 12), []),
        'totals' => array_fill_keys($currencies, array_fill_keys(range(1, 12), 0))
    ];
}

// Fetch all offerings for the year
$stmt = $pdo->prepare("
    SELECT o.*, e.event_name, e.location, e.description, MONTH(o.offering_date) as month
    FROM offerings o
    JOIN events e ON o.event_id = e.id
    WHERE YEAR(o.offering_date) = ?
    ORDER BY o.offering_date, e.event_name
");
$stmt->execute([$year]);
$allOfferings = $stmt->fetchAll();

// Organize offering data
foreach ($allOfferings as $offering) {
    $month = $offering['month'];
    $eventId = $offering['event_id'];
    
    $offeringData[$eventId]['months'][$month][] = $offering;
    $offeringData[$eventId]['totals'][$offering['currency']][$month] += $offering['amount'];
    $monthlyTotals[$offering['currency']][$month] += $offering['amount'];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Yearly Offering Report - Church Management System</title>
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
        .month-total { background-color: #f8f9fa; }
        .grand-total { font-weight: bold; background-color: #e9ecef; }
        .sticky-header { position: sticky; top: 0; background: white; z-index: 1; }
        .event-location { font-size: 0.9em; color: #666; }
        .event-description { font-size: 0.85em; color: #777; font-style: italic; }
        .offering-notes { color: #28a745; font-size: 0.85em; }
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
                        <h4 class="page-title">Yearly Offering Report</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">
                                            Offerings for Year <?php echo $year; ?>
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
                                        <h5 class="mt-4 mb-3">Offerings in <?php echo $currency; ?></h5>
                                        <div class="table-responsive">
                                            <table class="offering-table display table table-striped table-hover">
                                                <thead class="sticky-header">
                                                    <tr>
                                                        <th>Event</th>
                                                        <?php foreach(range(1, 12) as $month): ?>
                                                            <th class="text-center"><?php echo date('F', mktime(0, 0, 0, $month, 1)); ?></th>
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
                                                                    echo '<br><span class="event-location">@ ' . 
                                                                         htmlspecialchars($data['location']) . '</span>';
                                                                }
                                                                if ($data['description']) {
                                                                    echo '<br><span class="event-description">' . 
                                                                         htmlspecialchars($data['description']) . '</span>';
                                                                }
                                                                ?>
                                                            </td>
                                                            <?php 
                                                            $yearTotal = 0;
                                                            foreach(range(1, 12) as $month): 
                                                                $monthTotal = $data['totals'][$currency][$month];
                                                                $yearTotal += $monthTotal;
                                                            ?>
                                                                <td class="text-right">
                                                                    <?php
                                                                    if ($monthTotal > 0) {
                                                                        echo number_format($monthTotal, 2);
                                                                        // Get unique notes for this month's offerings
                                                                        $notes = array_unique(array_filter(array_map(function($o) {
                                                                            return $o['notes'];
                                                                        }, $data['months'][$month])));
                                                                        if (!empty($notes)) {
                                                                            echo '<br><span class="offering-notes">' . 
                                                                                 htmlspecialchars(implode(', ', $notes)) . '</span>';
                                                                        }
                                                                    } else {
                                                                        echo '-';
                                                                    }
                                                                    ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($yearTotal, 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <!-- Monthly Totals Row -->
                                                    <tr class="month-total">
                                                        <td><strong>Monthly Total</strong></td>
                                                        <?php 
                                                        $yearTotal = 0;
                                                        foreach(range(1, 12) as $month): 
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

                                        <!-- Yearly Trend Charts -->
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="trend-chart">
                                                    <canvas id="monthlyTrendChart<?php echo $currency; ?>"></canvas>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="trend-chart">
                                                    <canvas id="eventDistributionChart<?php echo $currency; ?>"></canvas>
                                                </div>
                                            </div>
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            $('.offering-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 
                    'csv', 
                    {
                        extend: 'excel',
                        title: 'Yearly Offering Report - ' + <?php echo $year; ?>,
                        messageTop: 'Event Offerings by Month'
                    },
                    {
                        extend: 'pdf',
                        title: 'Yearly Offering Report - ' + <?php echo $year; ?>,
                        messageTop: 'Event Offerings by Month'
                    },
                    'print'
                ],
                pageLength: 25,
                order: [[0, 'asc']]
            });

            // Create charts for each currency
            <?php foreach ($currencies as $currency): ?>
                // Monthly trend line chart
                new Chart(document.getElementById('monthlyTrendChart<?php echo $currency; ?>'), {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode(array_map(function($m) { 
                            return date('F', mktime(0, 0, 0, $m, 1)); 
                        }, range(1, 12))); ?>,
                        datasets: [{
                            label: 'Monthly Offerings (<?php echo $currency; ?>)',
                            data: <?php echo json_encode(array_values($monthlyTotals[$currency])); ?>,
                            borderColor: '#2980b9',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Monthly Offering Trend - ' + <?php echo $year; ?> + ' (<?php echo $currency; ?>)'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Event distribution pie chart
                new Chart(document.getElementById('eventDistributionChart<?php echo $currency; ?>'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_map(function($event) { 
                            return $event['event_name']; 
                        }, $events)); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_map(function($event) use ($offeringData, $currency) { 
                                return array_sum($offeringData[$event['id']]['totals'][$currency]);
                            }, $events)); ?>,
                            backgroundColor: [
                                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                                '#FF9F40', '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0'
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Offering Distribution by Event - ' + <?php echo $year; ?> + ' (<?php echo $currency; ?>)'
                            }
                        }
                    }
                });
            <?php endforeach; ?>
        });
    </script>
</body>
</html>