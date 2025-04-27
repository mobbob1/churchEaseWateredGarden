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
$donation = null;

// Check if this is an edit request
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT d.*, m.name as member_name, m.phone, pc.name as project_category_name 
                          FROM donations d 
                          LEFT JOIN members m ON d.member_id = m.id 
                          LEFT JOIN project_categories pc ON d.project_category_id = pc.id 
                          WHERE d.id = ?");
    $stmt->execute([$id]);
    $donation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$donation) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Donation record not found!</div>";
        header("Location: view_donations.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $payment_method = $_POST['payment_method'];
    $donation_date = $_POST['donation_date'];
    $project_category_id = $_POST['project_category_id'];
    $purpose = $_POST['purpose'];
    $reference_no = $_POST['reference_no'] ?? null;
    $notes = $_POST['notes'] ?? null;
    
    try {
        if (isset($_POST['id'])) {
            // Update existing donation
            $stmt = $pdo->prepare("UPDATE donations SET 
                                 member_id = ?, 
                                 amount = ?, 
                                 currency = ?,
                                 payment_method = ?,
                                 donation_date = ?,
                                 project_category_id = ?,
                                 purpose = ?,
                                 reference_no = ?,
                                 notes = ?
                                 WHERE id = ?");
            $stmt->execute([$member_id, $amount, $currency, $payment_method, $donation_date, 
                          $project_category_id, $purpose, $reference_no, $notes, $_POST['id']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Donation record updated successfully!</div>";
        } else {
            // Insert new donation
            $stmt = $pdo->prepare("INSERT INTO donations (member_id, amount, currency, payment_method, 
                                 donation_date, project_category_id, purpose, reference_no, notes) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $amount, $currency, $payment_method, $donation_date, 
                          $project_category_id, $purpose, $reference_no, $notes]);
            $_SESSION['message'] = "<div class='alert alert-success'>Donation record added successfully!</div>";
        }
        header("Location: view_donations.php");
        exit();
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo isset($donation) ? 'Edit' : 'Add'; ?> Donation - Church Management System</title>
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
                        <h4 class="page-title"><?php echo isset($donation) ? 'Edit' : 'Add'; ?> Donation</h4>
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
                                <a href="#"><?php echo isset($donation) ? 'Edit' : 'Add'; ?> Donation</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title"><?php echo isset($donation) ? 'Edit Donation Details' : 'Add New Donation'; ?></div>
                                </div>
                                <div class="card-body">
                                    <?php if(!empty($message)) echo $message; ?>
                                    <form method="POST">
                                        <?php if(isset($donation)) : ?>
                                            <input type="hidden" name="id" value="<?php echo $donation['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Member</label>
                                                    <select class="form-control select2" name="member_id" required>
                                                        <option value="">Select Member</option>
                                                        <?php
                                                        $stmt = $pdo->query("SELECT id, name, phone FROM members ORDER BY name");
                                                        while ($row = $stmt->fetch()) {
                                                            $selected = isset($donation) && $donation['member_id'] == $row['id'] ? 'selected' : '';
                                                            $member_info = "{$row['name']} - {$row['phone']}";
                                                            echo "<option value='{$row['id']}' {$selected}>{$member_info}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Donation Date</label>
                                                    <input type="text" name="donation_date" class="form-control datepicker" required
                                                           value="<?php echo isset($donation) ? date('Y-m-d', strtotime($donation['donation_date'])) : date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Amount</label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">
                                                                <i class="fas fa-money-bill"></i>
                                                            </span>
                                                        </div>
                                                        <input type="number" name="amount" class="form-control" step="0.01" required
                                                               value="<?php echo isset($donation) ? $donation['amount'] : ''; ?>"
                                                               placeholder="Enter amount...">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Currency</label>
                                                    <select class="form-control" name="currency" required>
                                                        <?php
                                                        $currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];
                                                        foreach ($currencies as $curr) {
                                                            $selected = isset($donation) && $donation['currency'] == $curr ? 'selected' : '';
                                                            echo "<option value='{$curr}' {$selected}>{$curr}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Project Category</label>
                                                    <select class="form-control select2" name="project_category_id" required>
                                                        <option value="">Select Project Category</option>
                                                        <?php
                                                        $stmt = $pdo->query("SELECT * FROM project_categories WHERE status = 'active' ORDER BY name");
                                                        while ($row = $stmt->fetch()) {
                                                            $selected = isset($donation) && $donation['project_category_id'] == $row['id'] ? 'selected' : '';
                                                            echo "<option value='{$row['id']}' {$selected}>" . htmlspecialchars($row['name']) . "</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Payment Method</label>
                                                    <select class="form-control" name="payment_method" required>
                                                        <?php
                                                        $payment_methods = ['Cash', 'Bank Transfer', 'Mobile Money', 'Check', 'Card Payment'];
                                                        foreach ($payment_methods as $method) {
                                                            $selected = isset($donation) && $donation['payment_method'] == $method ? 'selected' : '';
                                                            echo "<option value='{$method}' {$selected}>{$method}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Reference Number</label>
                                                    <input type="text" name="reference_no" class="form-control"
                                                           value="<?php echo isset($donation) ? htmlspecialchars($donation['reference_no']) : ''; ?>"
                                                           placeholder="Enter reference number...">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Purpose</label>
                                                    <textarea name="purpose" class="form-control" rows="3" 
                                                              placeholder="Enter specific purpose..."><?php echo isset($donation) ? htmlspecialchars($donation['purpose']) : ''; ?></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Additional Notes</label>
                                                    <textarea name="notes" class="form-control" rows="3" 
                                                              placeholder="Enter any additional notes..."><?php echo isset($donation) ? htmlspecialchars($donation['notes']) : ''; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-save"></i> 
                                                <?php echo isset($donation) ? 'Update' : 'Add'; ?> Donation
                                            </button>
                                            <a href="view_donations.php" class="btn btn-danger">
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
                theme: 'bootstrap-5'
            });

            // Initialize Flatpickr
            $(".datepicker").flatpickr({
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                defaultHour: new Date().getHours(),
                defaultMinute: new Date().getMinutes()
            });
        });
    </script>
</body>
</html>