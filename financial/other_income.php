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

// Create other_income table if it doesn't exist


// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_income':
                $stmt = $pdo->prepare("
                    INSERT INTO other_income (
                        name, category, amount, currency,
                        income_date, description
                    ) VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['category'],
                    $_POST['amount'],
                    $_POST['currency'],
                    $_POST['income_date'],
                    $_POST['description']
                ]);
                break;

            case 'update_income':
                $stmt = $pdo->prepare("
                    UPDATE other_income SET
                        name = ?,
                        category = ?,
                        amount = ?,
                        currency = ?,
                        income_date = ?,
                        description = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['category'],
                    $_POST['amount'],
                    $_POST['currency'],
                    $_POST['income_date'],
                    $_POST['description'],
                    $_POST['income_id']
                ]);
                break;

            case 'delete_income':
                $stmt = $pdo->prepare("DELETE FROM other_income WHERE id = ?");
                $stmt->execute([$_POST['income_id']]);
                break;
        }
    }
}

// Fetch all other income records
$other_income = $pdo->query("
    SELECT * FROM other_income 
    ORDER BY income_date DESC, name
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Other Income - Church Management System</title>
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
                        <h4 class="page-title">Other Income Management</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Other Income</h4>
                                        <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#addIncomeModal">
                                            <i class="fa fa-plus"></i> Add Other Income
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="other-income-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Name</th>
                                                    <th>Category</th>
                                                    <th>Amount</th>
                                                    <th>Currency</th>
                                                    <th>Description</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($other_income as $income): ?>
                                                    <tr>
                                                        <td><?php echo date('Y-m-d', strtotime($income['income_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($income['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($income['category']); ?></td>
                                                        <td><?php echo number_format($income['amount'], 2); ?></td>
                                                        <td><?php echo $income['currency']; ?></td>
                                                        <td><?php echo htmlspecialchars($income['description']); ?></td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-primary btn-sm" 
                                                                        onclick="editIncome(<?php echo $income['id']; ?>)">
                                                                    Edit
                                                                </button>
                                                                <button type="button" class="btn btn-danger btn-sm"
                                                                        onclick="deleteIncome(<?php echo $income['id']; ?>)">
                                                                    Delete
                                                                </button>
                                                            </div>
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

    <!-- Add Income Modal -->
    <div class="modal fade" id="addIncomeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_income">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Other Income</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" class="form-control" name="category" required>
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
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" name="income_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Income</button>
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
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#other-income-table').DataTable({
                "pageLength": 25,
                "order": [[0, "desc"]]
            });
        });

        function editIncome(incomeId) {
            // Implement edit functionality
        }

        function deleteIncome(incomeId) {
            if (confirm('Are you sure you want to delete this income record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_income">
                    <input type="hidden" name="income_id" value="${incomeId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>