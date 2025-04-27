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
        $stmt = $pdo->prepare("DELETE FROM expenses WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
        $_SESSION['message'] = "<div class='alert alert-success'>Expense record deleted successfully!</div>";
    } catch (PDOException $e) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Error deleting record: " . $e->getMessage() . "</div>";
    }
}

// Display message if any
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Expenses - ChurchEaseSuperb</title>
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
                        <h4 class="page-title">View Expenses</h4>
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
                                <a href="#">View Expenses</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <!-- Summary Cards -->
                            <div class="row">
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-danger card-round">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="icon-big text-center">
                                                        <i class="fas fa-calendar-day"></i>
                                                    </div>
                                                </div>
                                                <div class="col-7 col-stats">
                                                    <div class="numbers">
                                                        <p class="card-category">Today's Expenses</p>
                                                        <?php
                                                        $stmt = $pdo->query("SELECT currency, SUM(amount) as total 
                                                                           FROM expenses 
                                                                           WHERE DATE(expense_date) = CURDATE() 
                                                                           GROUP BY currency");
                                                        while ($row = $stmt->fetch()) {
                                                            echo "<h4 class='card-title'>{$row['currency']} " . number_format($row['total'], 2) . "</h4>";
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-warning card-round">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="icon-big text-center">
                                                        <i class="fas fa-calendar-week"></i>
                                                    </div>
                                                </div>
                                                <div class="col-7 col-stats">
                                                    <div class="numbers">
                                                        <p class="card-category">This Week's Expenses</p>
                                                        <?php
                                                        $stmt = $pdo->query("SELECT currency, SUM(amount) as total 
                                                                           FROM expenses 
                                                                           WHERE YEARWEEK(expense_date) = YEARWEEK(CURDATE())
                                                                           GROUP BY currency");
                                                        while ($row = $stmt->fetch()) {
                                                            echo "<h4 class='card-title'>{$row['currency']} " . number_format($row['total'], 2) . "</h4>";
                                                        }
                                                        ?>
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
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </div>
                                                </div>
                                                <div class="col-7 col-stats">
                                                    <div class="numbers">
                                                        <p class="card-category">This Month's Expenses</p>
                                                        <?php
                                                        $stmt = $pdo->query("SELECT currency, SUM(amount) as total 
                                                                           FROM expenses 
                                                                           WHERE MONTH(expense_date) = MONTH(CURDATE()) 
                                                                           AND YEAR(expense_date) = YEAR(CURDATE())
                                                                           GROUP BY currency");
                                                        while ($row = $stmt->fetch()) {
                                                            echo "<h4 class='card-title'>{$row['currency']} " . number_format($row['total'], 2) . "</h4>";
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6 col-md-3">
                                    <div class="card card-stats card-secondary card-round">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-5">
                                                    <div class="icon-big text-center">
                                                        <i class="fas fa-tags"></i>
                                                    </div>
                                                </div>
                                                <div class="col-7 col-stats">
                                                    <div class="numbers">
                                                        <p class="card-category">Categories</p>
                                                        <?php
                                                        $stmt = $pdo->query("SELECT COUNT(DISTINCT category) as total FROM expenses");
                                                        $row = $stmt->fetch();
                                                        echo "<h4 class='card-title'>" . $row['total'] . "</h4>";
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Summary -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Expenses by Category (This Month)</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Category</th>
                                                    <th>Currency</th>
                                                    <th>Total Amount</th>
                                                    <th>Count</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("SELECT category, currency, 
                                                                           COUNT(*) as count, 
                                                                           SUM(amount) as total
                                                                    FROM expenses 
                                                                    WHERE MONTH(expense_date) = MONTH(CURDATE()) 
                                                                    AND YEAR(expense_date) = YEAR(CURDATE())
                                                                    GROUP BY category, currency
                                                                    ORDER BY total DESC");
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>
                                                            <td>" . ucfirst($row['category']) . "</td>
                                                            <td>{$row['currency']}</td>
                                                            <td>" . number_format($row['total'], 2) . "</td>
                                                            <td>{$row['count']}</td>
                                                          </tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Expenses List -->
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Expenses List</h4>
                                        <a href="expense_management.php" class="btn btn-primary btn-round ml-auto">
                                            <i class="fa fa-plus"></i> Add New Expense
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($message)) echo $message; ?>
                                    <div class="table-responsive">
                                        <table id="expenses-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Description</th>
                                                    <th>Category</th>
                                                    <th>Amount</th>
                                                    <th>Currency</th>
                                                    <th>Payment Method</th>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Approved By</th>
                                                    <th>Receipt #</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("SELECT * FROM expenses ORDER BY expense_date DESC");
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>{$row['id']}</td>";
                                                    echo "<td>{$row['description']}</td>";
                                                    echo "<td>" . ucfirst($row['category']) . "</td>";
                                                    echo "<td>" . number_format($row['amount'], 2) . "</td>";
                                                    echo "<td>{$row['currency']}</td>";
                                                    echo "<td><span class='badge badge-" . get_payment_badge($row['payment_method']) . "'>" . 
                                                         ucfirst($row['payment_method']) . "</span></td>";
                                                    echo "<td>" . date('M d, Y H:i', strtotime($row['expense_date'])) . "</td>";
                                                    echo "<td>" . ucfirst($row['expense_type']) . "</td>";
                                                    echo "<td>{$row['approved_by']}</td>";
                                                    echo "<td>{$row['receipt_number']}</td>";
                                                    echo "<td>
                                                            <div class='btn-group'>
                                                                <a href='expense_management.php?id={$row['id']}' 
                                                                   class='btn btn-primary btn-sm' title='Edit'>
                                                                    <i class='fa fa-edit'></i>
                                                                </a>
                                                                <button type='button' 
                                                                        class='btn btn-danger btn-sm' 
                                                                        onclick='deleteExpense({$row['id']})' 
                                                                        title='Delete'>
                                                                    <i class='fa fa-trash'></i>
                                                                </button>
                                                            </div>
                                                          </td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th colspan="3">Total:</th>
                                                    <th colspan="8"></th>
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
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this expense record?</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="delete_id" id="delete_id">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
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
            $('#expenses-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'collection',
                        text: 'Export',
                        buttons: [
                            'copy',
                            {
                                extend: 'excel',
                                title: 'Expenses_Report_' + new Date().toISOString().slice(0,10)
                            },
                            {
                                extend: 'csv',
                                title: 'Expenses_Report_' + new Date().toISOString().slice(0,10)
                            },
                            {
                                extend: 'pdf',
                                title: 'Expenses_Report_' + new Date().toISOString().slice(0,10)
                            },
                            'print'
                        ]
                    }
                ],
                "pageLength": 25,
                "order": [[6, "desc"]],
                "responsive": true,
                "footerCallback": function(row, data, start, end, display) {
                    var api = this.api();
                    
                    // Remove formatting to get numeric data for summation
                    var intVal = function(i) {
                        return typeof i === 'string' ? 
                            i.replace(/[\$,]/g, '') * 1 :
                            typeof i === 'number' ? i : 0;
                    };

                    // Calculate totals for each currency
                    let currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];
                    let totals = {};
                    
                    currencies.forEach(function(currency) {
                        let total = api
                            .rows({ search: 'applied' })
                            .data()
                            .filter(row => row[4] === currency)
                            .reduce((sum, row) => sum + intVal(row[3]), 0);
                        
                        if (total > 0) {
                            totals[currency] = total;
                        }
                    });

                    // Format the footer
                    let footerHtml = Object.entries(totals)
                        .map(([currency, total]) => `${currency} ${number_format(total, 2)}`)
                        .join(' | ');

                    $(api.column(3).footer()).html(footerHtml);
                }
            });
        });

        function deleteExpense(id) {
            $('#delete_id').val(id);
            $('#deleteModal').modal('show');
        }

        function number_format(number, decimals) {
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(number);
        }
    </script>

    <?php
    function get_payment_badge($payment_method) {
        switch ($payment_method) {
            case 'cash':
                return 'success';
            case 'momo':
                return 'info';
            case 'card':
                return 'primary';
            case 'bank':
                return 'warning';
            default:
                return 'secondary';
        }
    }
    ?>
</body>
</html>