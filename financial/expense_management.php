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
$message = '';
$expense = null;

// Check if this is an edit request
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM expenses WHERE id = ?");
    $stmt->execute([$id]);
    $expense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$expense) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Expense record not found!</div>";
        header("Location: view_expenses.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $description = $_POST['description'];
        $amount = $_POST['amount'];
        $currency = $_POST['currency'];
        $payment_method = $_POST['payment_method'];
        $expense_date = $_POST['expense_date'];
        $expense_type = $_POST['expense_type'];
        $category = $_POST['category'];
        $receipt_number = $_POST['receipt_number'];
        $approved_by = $_POST['approved_by'];
        $paid_to = $_POST['paid_to'];
        $notes = $_POST['notes'];

        if (isset($_POST['id'])) {
            // Update existing expense
            $stmt = $pdo->prepare("UPDATE expenses SET 
                                 description = ?,
                                 amount = ?,
                                 currency = ?,
                                 payment_method = ?,
                                 expense_date = ?,
                                 expense_type = ?,
                                 category = ?,
                                 receipt_number = ?,
                                 approved_by = ?,
                                 paid_to = ?,
                                 notes = ?
                                 WHERE id = ?");
            $stmt->execute([$description, $amount, $currency, $payment_method, $expense_date, 
                          $expense_type, $category, $receipt_number, $approved_by, $paid_to, 
                          $notes, $_POST['id']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Expense record updated successfully!</div>";
        } else {
            // Insert new expense
            $stmt = $pdo->prepare("INSERT INTO expenses (description, amount, currency, 
                                 payment_method, expense_date, expense_type, category, 
                                 receipt_number, approved_by, paid_to, notes) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$description, $amount, $currency, $payment_method, $expense_date, 
                          $expense_type, $category, $receipt_number, $approved_by, $paid_to, 
                          $notes]);
            $_SESSION['message'] = "<div class='alert alert-success'>Expense record added successfully!</div>";
        }
        header("Location: view_expenses.php");
        exit();
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Fetch categories and types
$categories = $pdo->query("SELECT name, description FROM expense_categories ORDER BY name")->fetchAll();
$types = $pdo->query("SELECT name, description FROM expense_types ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo isset($expense) ? 'Edit' : 'Add'; ?> Expense - ChurchEaseSuperb</title>
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title"><?php echo isset($expense) ? 'Edit' : 'Add'; ?> Expense</h4>
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
                                <a href="#"><?php echo isset($expense) ? 'Edit' : 'Add'; ?> Expense</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title"><?php echo isset($expense) ? 'Edit Expense Details' : 'Add New Expense'; ?></div>
                                </div>
                                <div class="card-body">
                                    <?php if(!empty($message)) echo $message; ?>
                                    <form method="POST">
                                        <?php if(isset($expense)) : ?>
                                            <input type="hidden" name="id" value="<?php echo $expense['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Description</label>
                                                    <input type="text" name="description" class="form-control" required
                                                           value="<?php echo isset($expense) ? $expense['description'] : ''; ?>"
                                                           placeholder="Enter expense description...">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Expense Date & Time</label>
                                                    <input type="text" name="expense_date" class="form-control datetimepicker" required
                                                           value="<?php echo isset($expense) ? date('Y-m-d H:i', strtotime($expense['expense_date'])) : date('Y-m-d H:i'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Amount</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-money-bill"></i>
                                                            </span>
                                                        </div>
                                                        <input type="number" name="amount" class="form-control" step="0.01" required
                                                               value="<?php echo isset($expense) ? $expense['amount'] : ''; ?>"
                                                               placeholder="Enter amount...">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Currency</label>
                                                    <select class="form-control" name="currency" required>
                                                        <?php
                                                        $currencies = [
                                                            'GHS' => 'GHS - Ghana Cedi',
                                                            'USD' => 'USD - US Dollar',
                                                            'EUR' => 'EUR - Euro',
                                                            'GBP' => 'GBP - British Pound',
                                                            'NGN' => 'NGN - Nigerian Naira'
                                                        ];
                                                        foreach ($currencies as $code => $name) {
                                                            $selected = isset($expense) && $expense['currency'] == $code ? 'selected' : '';
                                                            echo "<option value='{$code}' {$selected}>{$name}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Payment Method</label>
                                                    <select class="form-control" name="payment_method" required>
                                                        <option value="cash" <?php echo (isset($expense) && $expense['payment_method'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                                                        <option value="momo" <?php echo (isset($expense) && $expense['payment_method'] == 'momo') ? 'selected' : ''; ?>>Mobile Money</option>
                                                        <option value="card" <?php echo (isset($expense) && $expense['payment_method'] == 'card') ? 'selected' : ''; ?>>Card Payment</option>
                                                        <option value="bank" <?php echo (isset($expense) && $expense['payment_method'] == 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Category</label>
                                                    <select class="form-control select2" name="category" required>
                                                        <option value="">Select Category</option>
                                                        <?php foreach ($categories as $category) : ?>
                                                            <option value="<?php echo $category['name']; ?>" 
                                                                    <?php echo (isset($expense) && $expense['category'] == $category['name']) ? 'selected' : ''; ?>
                                                                    title="<?php echo $category['description']; ?>">
                                                                <?php echo ucfirst($category['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Expense Type</label>
                                                    <select class="form-control select2" name="expense_type" required>
                                                        <option value="">Select Type</option>
                                                        <?php foreach ($types as $type) : ?>
                                                            <option value="<?php echo $type['name']; ?>" 
                                                                    <?php echo (isset($expense) && $expense['expense_type'] == $type['name']) ? 'selected' : ''; ?>
                                                                    title="<?php echo $type['description']; ?>">
                                                                <?php echo ucfirst($type['name']); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Receipt Number</label>
                                                    <input type="text" name="receipt_number" class="form-control"
                                                           value="<?php echo isset($expense) ? $expense['receipt_number'] : ''; ?>"
                                                           placeholder="Enter receipt number...">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Approved By</label>
                                                    <input type="text" name="approved_by" class="form-control"
                                                           value="<?php echo isset($expense) ? $expense['approved_by'] : ''; ?>"
                                                           placeholder="Enter approver's name...">
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Paid To</label>
                                                    <input type="text" name="paid_to" class="form-control"
                                                           value="<?php echo isset($expense) ? $expense['paid_to'] : ''; ?>"
                                                           placeholder="Enter recipient's name...">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Notes</label>
                                                    <textarea name="notes" class="form-control" rows="3"
                                                              placeholder="Enter any additional notes..."><?php echo isset($expense) ? $expense['notes'] : ''; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-save"></i> 
                                                <?php echo isset($expense) ? 'Update' : 'Add'; ?> Expense
                                            </button>
                                            <a href="view_expenses.php" class="btn btn-danger">
                                                <i class="fa fa-times"></i> Cancel
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../res/footer.php'; ?>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%'
            });

            // Initialize DateTime Picker
            $(".datetimepicker").flatpickr({
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                time_24hr: true,
                maxDate: "today",
                defaultDate: "today",
                allowInput: true,
                altInput: true,
                altFormat: "F j, Y H:i",
                theme: "material_blue"
            });
        });
    </script>
</body>
</html>