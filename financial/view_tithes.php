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
// Handle delete request
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tithes WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $_SESSION['message'] = "<div class='alert alert-success'>Tithe record deleted successfully!</div>";
    } catch (PDOException $e) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Error deleting record: " . $e->getMessage() . "</div>";
    }
}

// Display message if any
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Calculate summary data
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];
$today = date('Y-m-d');
$thisWeekStart = date('Y-m-d', strtotime('monday this week'));
$thisMonthStart = date('Y-m-01');

try {
    // Summary by currency
    $currencySummary = [];
    foreach ($currencies as $currency) {
        // Get today's total
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM tithes 
                               WHERE tithe_date = CURDATE() AND currency = ?");
        $stmt->execute([$currency]);
        $todayTotal = $stmt->fetch()['total'] ?: 0;

        // Get this week's total
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM tithes 
                               WHERE tithe_date >= ? AND currency = ?");
        $stmt->execute([$thisWeekStart, $currency]);
        $weekTotal = $stmt->fetch()['total'] ?: 0;

        // Get this month's total
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM tithes 
                               WHERE tithe_date >= ? AND currency = ?");
        $stmt->execute([$thisMonthStart, $currency]);
        $monthTotal = $stmt->fetch()['total'] ?: 0;

        $currencySummary[] = [
            'currency' => $currency,
            'today' => $todayTotal,
            'week' => $weekTotal,
            'month' => $monthTotal
        ];
    }

    // Total sum of all tithes
    $totalAll = $pdo->query("SELECT currency, SUM(amount) AS total FROM tithes GROUP BY currency")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching summary data: " . $e->getMessage());
    $currencySummary = [];
    $totalAll = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Tithes - ChurchEaseSuperb</title>
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
                        <h4 class="page-title">View Tithes</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Financial</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">View Tithes</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Summary Cards -->
                            <div class="row">
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-primary card-round">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="icon-big text-center">
                                                        <i class="fas fa-calendar-day"></i>
                                                    </div>
                                                </div>
                                                <div class="col-7 col-stats">
                                                    <div class="numbers">
                                                        <p class="card-category">Today's Tithes</p>
                                                        <?php foreach ($currencySummary as $summary) : ?>
                                                            <h4 class="card-title"><?php echo $summary['currency'] . " " . number_format($summary['today'], 2); ?></h4>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-info card-round">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="icon-big text-center">
                                                        <i class="fas fa-calendar-week"></i>
                                                    </div>
                                                </div>
                                                <div class="col-7 col-stats">
                                                    <div class="numbers">
                                                        <p class="card-category">This Week's Tithes</p>
                                                        <?php foreach ($currencySummary as $summary) : ?>
                                                            <h4 class="card-title"><?php echo $summary['currency'] . " " . number_format($summary['week'], 2); ?></h4>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-success card-round">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="icon-big text-center">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </div>
                                                </div>
                                                <div class="col-7 col-stats">
                                                    <div class="numbers">
                                                        <p class="card-category">This Month's Tithes</p>
                                                        <?php foreach ($currencySummary as $summary) : ?>
                                                            <h4 class="card-title"><?php echo $summary['currency'] . " " . number_format($summary['month'], 2); ?></h4>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary by Currency -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Tithes Summary by Currency</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Currency</th>
                                                    <th>Today's Total</th>
                                                    <th>This Week's Total</th>
                                                    <th>This Month's Total</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($currencySummary as $summary): ?>
                                                    <tr>
                                                        <td><strong><?php echo $summary['currency']; ?></strong></td>
                                                        <td><?php echo number_format($summary['today'], 2); ?></td>
                                                        <td><?php echo number_format($summary['week'], 2); ?></td>
                                                        <td><?php echo number_format($summary['month'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Tithes List -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Tithes List</h4>
                                        <a href="tithe_management.php" class="btn btn-primary btn-round ml-auto">
                                            <i class="fa fa-plus"></i>
                                            Add Tithe
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($message)) echo $message; ?>
                                    <div class="table-responsive">
                                        <table id="tithes-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Member Name</th>
                                                    <th>Amount</th>
                                                    <th>Currency</th>
                                                    <th>Payment Method</th>
                                                    <th>Tithe Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("SELECT t.id, m.name as member_name, t.amount, t.currency, t.payment_method, t.tithe_date FROM tithes t JOIN members m ON t.member_id = m.id");
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo "<tr>";
                                                    echo "<td>{$row['id']}</td>";
                                                    echo "<td>{$row['member_name']}</td>";
                                                    echo "<td>{$row['amount']}</td>";
                                                    echo "<td>{$row['currency']}</td>";
                                                    echo "<td>{$row['payment_method']}</td>";
                                                    echo "<td>{$row['tithe_date']}</td>";
                                                    echo "<td>
                                                        <form method='POST' style='display:inline-block;'>
                                                            <input type='hidden' name='delete_id' value='{$row['id']}'>
                                                            <button type='submit' class='btn btn-danger btn-sm'>Delete</button>
                                                        </form>
                                                    </td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="7" style="text-align:right">
                                                        <?php foreach ($totalAll as $total): ?>
                                                            <?php echo $total['currency'] . " " . number_format($total['total'], 2) . " "; ?>
                                                        <?php endforeach; ?>
                                                    </th>
                                                </tr>
                                            </tfoot>
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

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Confirmation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this record?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger">Delete</button>
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
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.colVis.min.js"></script>

    <script>
        $(document).ready(function() {
            var table = $('#tithes-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ],
                footerCallback: function ( row, data, start, end, display ) {
                    var api = this.api(), data;
    
                    // Remove the formatting to get integer data for summation
                    var intVal = function ( i ) {
                        return typeof i === 'string' ?
                            i.replace(/[\$,]/g, '')*1 :
                            typeof i === 'number' ?
                                i : 0;
                    };
    
                    // Total over all pages
                    total = api
                        .column(2)
                        .data()
                        .reduce( function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0 );
    
                    // Total over this page
                    pageTotal = api
                        .column(2, { page: 'current'} )
                        .data()
                        .reduce( function (a, b) {
                            return intVal(a) + intVal(b);
                        }, 0 );
    
                    // Update footer
                    $( api.column(2).footer() ).html(
                        '<?php foreach ($totalAll as $total): ?><?php echo $total['currency'] . " " . number_format($total['total'], 2) . " "; ?><?php endforeach; ?>'
                    );
                }
            });
        });
    </script>
</body>
</html>