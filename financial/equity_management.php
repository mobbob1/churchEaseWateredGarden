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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_equity':
                $stmt = $pdo->prepare("
                    INSERT INTO equity (
                        type, amount, currency, date, description
                    ) VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['type'],
                    $_POST['amount'],
                    $_POST['currency'],
                    $_POST['date'],
                    $_POST['description']
                ]);
                break;

            case 'update_equity':
                $stmt = $pdo->prepare("
                    UPDATE equity SET
                        type = ?,
                        amount = ?,
                        currency = ?,
                        date = ?,
                        description = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['type'],
                    $_POST['amount'],
                    $_POST['currency'],
                    $_POST['date'],
                    $_POST['description'],
                    $_POST['equity_id']
                ]);
                break;
        }
    }
}

// Calculate total equity
$totalEquity = $pdo->query("
    SELECT 
        currency,
        SUM(amount) as total_amount
    FROM equity
    GROUP BY currency
")->fetchAll();

// Fetch all equity records
$equityRecords = $pdo->query("
    SELECT *
    FROM equity
    ORDER BY date DESC, type
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Equity Management - Church Management System</title>
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
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Equity Management</h4>
                    </div>
                    
                    <!-- Total Equity Summary -->
                    <div class="row">
                        <?php foreach ($totalEquity as $total): ?>
                            <div class="col-sm-6 col-md-3">
                                <div class="card card-stats card-round">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-icon">
                                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                    <i class="fas fa-dollar-sign"></i>
                                                </div>
                                            </div>
                                            <div class="col col-stats ml-3 ml-sm-0">
                                                <div class="numbers">
                                                    <p class="card-category">Total Equity (<?php echo $total['currency']; ?>)</p>
                                                    <h4 class="card-title"><?php echo number_format($total['total_amount'], 2); ?></h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Equity Records</h4>
                                        <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#addEquityModal">
                                            <i class="fa fa-plus"></i> Add Equity Record
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="equity-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Type</th>
                                                    <th>Amount</th>
                                                    <th>Currency</th>
                                                    <th>Description</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($equityRecords as $record): ?>
                                                    <tr>
                                                        <td><?php echo date('Y-m-d', strtotime($record['date'])); ?></td>
                                                        <td><?php echo ucfirst(str_replace('_', ' ', $record['type'])); ?></td>
                                                        <td><?php echo number_format($record['amount'], 2); ?></td>
                                                        <td><?php echo $record['currency']; ?></td>
                                                        <td><?php echo htmlspecialchars($record['description']); ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-primary btn-sm" 
                                                                    onclick="editEquity(<?php echo $record['id']; ?>)">
                                                                Edit
                                                            </button>
                                                        </td>
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

    <!-- Add Equity Modal -->
    <div class="modal fade" id="addEquityModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_equity">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Equity Record</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Type</label>
                                    <select class="form-control" name="type" required>
                                        <option value="opening_balance">Opening Balance</option>
                                        <option value="retained_earnings">Retained Earnings</option>
                                        <option value="adjustments">Adjustments</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" name="date" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="amount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Currency</label>
                                    <select class="form-control" name="currency" required>
                                        <option value="GHS">GHS</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                        <option value="GBP">GBP</option>
                                        <option value="NGN">NGN</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Equity Modal -->
    <div class="modal fade" id="editEquityModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="update_equity">
                    <input type="hidden" name="equity_id" id="edit_equity_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Equity Record</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- Same form fields as Add Equity Modal -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Type</label>
                                    <select class="form-control" name="type" id="edit_type" required>
                                        <option value="opening_balance">Opening Balance</option>
                                        <option value="retained_earnings">Retained Earnings</option>
                                        <option value="adjustments">Adjustments</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" name="date" id="edit_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="amount" id="edit_amount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Currency</label>
                                    <select class="form-control" name="currency" id="edit_currency" required>
                                        <option value="GHS">GHS</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                        <option value="GBP">GBP</option>
                                        <option value="NGN">NGN</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Record</button>
                    </div>
                </form>
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
    
    <script>
        $(document).ready(function() {
            $('#equity-table').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });

        function editEquity(equityId) {
            // Fetch equity record details via AJAX
            $.ajax({
                url: 'get_equity_details.php',
                type: 'POST',
                data: { id: equityId },
                dataType: 'json',
                success: function(data) {
                    $('#edit_equity_id').val(data.id);
                    $('#edit_type').val(data.type);
                    $('#edit_date').val(data.date);
                    $('#edit_amount').val(data.amount);
                    $('#edit_currency').val(data.currency);
                    $('#edit_description').val(data.description);
                    $('#editEquityModal').modal('show');
                }
            });
        }
    </script>
</body>
</html>