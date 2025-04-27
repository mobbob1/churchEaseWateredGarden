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
$offering = null;

// Check if this is an edit request
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT o.*, e.event_name 
                          FROM offerings o 
                          JOIN events e ON o.event_id = e.id 
                          WHERE o.id = ?");
    $stmt->execute([$id]);
    $offering = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$offering) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Offering record not found!</div>";
        header("Location: view_offerings.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $payment_method = $_POST['payment_method'];
    $offering_date = $_POST['offering_date'];
    $offering_type = $_POST['offering_type'];
    $notes = $_POST['notes'];
    
    try {
        if (isset($_POST['id'])) {
            // Update existing offering
            $stmt = $pdo->prepare("UPDATE offerings SET 
                                 event_id = ?, 
                                 amount = ?, 
                                 currency = ?,
                                 payment_method = ?,
                                 offering_date = ?,
                                 offering_type = ?,
                                 notes = ?
                                 WHERE id = ?");
            $stmt->execute([$event_id, $amount, $currency, $payment_method, $offering_date, $offering_type, $notes, $_POST['id']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Offering record updated successfully!</div>";
        } else {
            // Insert new offering
            $stmt = $pdo->prepare("INSERT INTO offerings (event_id, amount, currency, payment_method, offering_date, offering_type, notes) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$event_id, $amount, $currency, $payment_method, $offering_date, $offering_type, $notes]);
            $_SESSION['message'] = "<div class='alert alert-success'>Offering record added successfully!</div>";
        }
        header("Location: view_offerings.php");
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
    <title><?php echo isset($offering) ? 'Edit' : 'Add'; ?> Offering - ChurchEaseSuperb</title>
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
                        <h4 class="page-title"><?php echo isset($offering) ? 'Edit' : 'Add'; ?> Offering</h4>
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
                                <a href="#"><?php echo isset($offering) ? 'Edit' : 'Add'; ?> Offering</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title"><?php echo isset($offering) ? 'Edit Offering Details' : 'Add New Offering'; ?></div>
                                </div>
                                <div class="card-body">
                                    <?php if(!empty($message)) echo $message; ?>
                                    <form method="POST">
                                        <?php if(isset($offering)) : ?>
                                            <input type="hidden" name="id" value="<?php echo $offering['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Event</label>
                                                    <select class="form-control select2" name="event_id" required>
                                                        <option value="">Select Event</option>
                                                        <?php
                                                        $stmt = $pdo->query("SELECT id, event_name, event_date FROM events ORDER BY event_date DESC");
                                                        while ($row = $stmt->fetch()) {
                                                            $selected = isset($offering) && $offering['event_id'] == $row['id'] ? 'selected' : '';
                                                            $event_info = "{$row['event_name']} (" . date('M d, Y', strtotime($row['event_date'])) . ")";
                                                            echo "<option value='{$row['id']}' {$selected}>{$event_info}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Offering Date & Time</label>
                                                    <input type="text" name="offering_date" class="form-control datetimepicker" required
                                                           value="<?php echo isset($offering) ? date('Y-m-d H:i', strtotime($offering['offering_date'])) : date('Y-m-d H:i'); ?>">
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
                                                               value="<?php echo isset($offering) ? $offering['amount'] : ''; ?>"
                                                               placeholder="Enter amount...">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
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
                                                            $selected = isset($offering) && $offering['currency'] == $code ? 'selected' : '';
                                                            echo "<option value='{$code}' {$selected}>{$name}</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Payment Method</label>
                                                    <select class="form-control" name="payment_method" required>
                                                        <option value="cash" <?php echo (isset($offering) && $offering['payment_method'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                                                        <option value="momo" <?php echo (isset($offering) && $offering['payment_method'] == 'momo') ? 'selected' : ''; ?>>Mobile Money</option>
                                                        <option value="card" <?php echo (isset($offering) && $offering['payment_method'] == 'card') ? 'selected' : ''; ?>>Card Payment</option>
                                                        <option value="bank" <?php echo (isset($offering) && $offering['payment_method'] == 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Offering Type</label>
                                                    <select class="form-control" name="offering_type" required>
                                                        <option value="general" <?php echo (isset($offering) && $offering['offering_type'] == 'general') ? 'selected' : ''; ?>>General Offering</option>
                                                        <option value="special" <?php echo (isset($offering) && $offering['offering_type'] == 'special') ? 'selected' : ''; ?>>Special Offering</option>
                                                        <option value="thanksgiving" <?php echo (isset($offering) && $offering['offering_type'] == 'thanksgiving') ? 'selected' : ''; ?>>Thanksgiving</option>
                                                        <option value="project" <?php echo (isset($offering) && $offering['offering_type'] == 'project') ? 'selected' : ''; ?>>Project Offering</option>
                                                        <option value="missions" <?php echo (isset($offering) && $offering['offering_type'] == 'missions') ? 'selected' : ''; ?>>Missions Offering</option>
                                                        <option value="firstfruit" <?php echo (isset($offering) && $offering['offering_type'] == 'firstfruit') ? 'selected' : ''; ?>>First Fruit</option>
                                                        <option value="other" <?php echo (isset($offering) && $offering['offering_type'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Notes</label>
                                                    <textarea name="notes" class="form-control" rows="3"
                                                              placeholder="Enter any additional notes..."><?php echo isset($offering) ? $offering['notes'] : ''; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-save"></i> 
                                                <?php echo isset($offering) ? 'Update' : 'Add'; ?> Offering
                                            </button>
                                            <a href="view_offerings.php" class="btn btn-danger">
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
                width: '100%',
                placeholder: 'Select an event...',
                allowClear: true
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