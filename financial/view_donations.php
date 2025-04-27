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
function get_payment_badge($payment_method) {
    $badge_class = 'badge ';
    switch(strtolower($payment_method)) {
        case 'cash':
            $badge_class .= 'badge-success';
            break;
        case 'bank transfer':
            $badge_class .= 'badge-info';
            break;
        case 'mobile money':
            $badge_class .= 'badge-primary';
            break;
        case 'check':
            $badge_class .= 'badge-warning';
            break;
        default:
            $badge_class .= 'badge-secondary';
    }
    return '<span class="' . $badge_class . '">' . htmlspecialchars($payment_method) . '</span>';
}

// First, check if the currency column exists in donations table
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM donations LIKE 'currency'");
    if ($stmt->rowCount() == 0) {
        // Add currency and payment_method columns if they don't exist
        $pdo->exec("ALTER TABLE `donations` 
                   ADD COLUMN `currency` VARCHAR(10) NOT NULL DEFAULT 'GHS' AFTER `amount`,
                   ADD COLUMN `payment_method` VARCHAR(20) NOT NULL DEFAULT 'cash' AFTER `currency`");
    }
} catch (PDOException $e) {
    $_SESSION['message'] = "<div class='alert alert-danger'>Database Error: " . $e->getMessage() . "</div>";
}

// Handle delete request
if (isset($_POST['delete_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM donations WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $_SESSION['message'] = "<div class='alert alert-success'>Donation record deleted successfully!</div>";
    } catch (PDOException $e) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Error deleting record: " . $e->getMessage() . "</div>";
    }
}

// Display message if any
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Define currencies
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Donations - Church Management System</title>
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
                        <h4 class="page-title">View Donations</h4>
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
                                <a href="#">View Donations</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Summary Card -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Donations Summary</h4>
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
                                                <?php
                                                foreach ($currencies as $currency) {
                                                    // Get today's total
                                                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations 
                                                                         WHERE DATE(donation_date) = CURDATE()
                                                                         AND (currency = ? OR (currency IS NULL AND ? = 'GHS'))");
                                                    $stmt->execute([$currency, $currency]);
                                                    $today = $stmt->fetch()['total'] ?: 0;

                                                    // Get this week's total
                                                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations 
                                                                         WHERE YEARWEEK(donation_date) = YEARWEEK(CURDATE())
                                                                         AND (currency = ? OR (currency IS NULL AND ? = 'GHS'))");
                                                    $stmt->execute([$currency, $currency]);
                                                    $week = $stmt->fetch()['total'] ?: 0;

                                                    // Get this month's total
                                                    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM donations 
                                                                         WHERE MONTH(donation_date) = MONTH(CURDATE()) 
                                                                         AND YEAR(donation_date) = YEAR(CURDATE())
                                                                         AND (currency = ? OR (currency IS NULL AND ? = 'GHS'))");
                                                    $stmt->execute([$currency, $currency]);
                                                    $month = $stmt->fetch()['total'] ?: 0;

                                                    echo "<tr>
                                                            <td><strong>{$currency}</strong></td>
                                                            <td>" . number_format($today, 2) . "</td>
                                                            <td>" . number_format($week, 2) . "</td>
                                                            <td>" . number_format($month, 2) . "</td>
                                                          </tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Project Category Summary -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h4 class="card-title">Donations by Project Category (This Month)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Project Category</th>
                                                    <?php foreach ($currencies as $currency): ?>
                                                        <th><?php echo $currency; ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("SELECT 
                                                                       pc.name as category_name,
                                                                       d.currency,
                                                                       COALESCE(SUM(d.amount), 0) as total
                                                                   FROM project_categories pc
                                                                   LEFT JOIN donations d ON pc.id = d.project_category_id
                                                                       AND MONTH(d.donation_date) = MONTH(CURDATE())
                                                                       AND YEAR(d.donation_date) = YEAR(CURDATE())
                                                                   GROUP BY pc.name, d.currency
                                                                   ORDER BY pc.name");
                                                
                                                $categoryTotals = [];
                                                while ($row = $stmt->fetch()) {
                                                    if (!isset($categoryTotals[$row['category_name']])) {
                                                        $categoryTotals[$row['category_name']] = array_fill_keys($currencies, 0);
                                                    }
                                                    if ($row['currency']) {
                                                        $categoryTotals[$row['category_name']][$row['currency']] = $row['total'];
                                                    }
                                                }

                                                foreach ($categoryTotals as $category => $totals) {
                                                    echo "<tr>
                                                            <td><strong>" . htmlspecialchars($category) . "</strong></td>";
                                                    foreach ($currencies as $currency) {
                                                        echo "<td>" . number_format($totals[$currency], 2) . "</td>";
                                                    }
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Donations List -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Donations List</h4>
                                        <a href="donation_management.php" class="btn btn-primary btn-round ml-auto">
                                            <i class="fa fa-plus"></i> Add New Donation
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($message)) echo $message; ?>
                                    <div class="table-responsive">
                                        <table id="donations-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Member</th>
                                                    <th>Date</th>
                                                    <th>Amount</th>
                                                    <th>Currency</th>
                                                    <th>Project Category</th>
                                                    <th>Payment Method</th>
                                                    <th>Purpose</th>
                                                    <th>Reference</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("SELECT d.*, m.name as member_name, pc.name as project_category_name 
                                                                    FROM donations d 
                                                                    LEFT JOIN members m ON d.member_id = m.id
                                                                    LEFT JOIN project_categories pc ON d.project_category_id = pc.id
                                                                    ORDER BY d.donation_date DESC");
                                                
                                                $counter = 1;
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>
                                                            <td>{$counter}</td>
                                                            <td>" . htmlspecialchars($row['member_name'] ?? 'Anonymous') . "</td>
                                                            <td>" . date('Y-m-d H:i', strtotime($row['donation_date'])) . "</td>
                                                            <td class='text-right'>" . number_format($row['amount'], 2) . "</td>
                                                            <td>" . htmlspecialchars($row['currency']) . "</td>
                                                            <td>" . htmlspecialchars($row['project_category_name'] ?? 'General') . "</td>
                                                            <td>" . get_payment_badge($row['payment_method']) . "</td>
                                                            <td>" . htmlspecialchars($row['purpose']) . "</td>
                                                            <td>" . htmlspecialchars($row['reference_no'] ?? '') . "</td>
                                                            <td>
                                                                <div class='form-button-action'>
                                                                    <a href='donation_management.php?id=" . $row['id'] . "' 
                                                                       data-toggle='tooltip' title='Edit' class='btn btn-link btn-primary btn-lg'>
                                                                        <i class='fa fa-edit'></i>
                                                                    </a>
                                                                    <button type='button' data-toggle='tooltip' title='Delete'
                                                                            class='btn btn-link btn-danger delete-btn'
                                                                            data-id='" . $row['id'] . "' data-toggle='modal' data-target='#deleteModal'>
                                                                        <i class='fa fa-times'></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                          </tr>";
                                                    $counter++;
                                                }
                                                ?>
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

    <!-- Delete Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Donation</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this donation record?</p>
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="delete_id" id="delete_id">
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" form="deleteForm" class="btn btn-danger">Delete</button>
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
            $('#donations-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 
                    'csv', 
                    {
                        extend: 'excel',
                        title: 'Donations Report',
                        messageTop: 'List of all donations'
                    },
                    {
                        extend: 'pdf',
                        title: 'Donations Report',
                        messageTop: 'List of all donations'
                    },
                    'print'
                ],
                pageLength: 25,
                order: [[2, 'desc']], // Order by date column descending
                responsive: true
            });

            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Handle delete button click
            $('.delete-btn').click(function() {
                var id = $(this).data('id');
                $('#delete_id').val(id);
            });
        });
    </script>
</body>
</html>