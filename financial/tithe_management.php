<?php
require_once '../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';
require_once __DIR__ . '/../config.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Check if user has appropriate role for tithe management
$allowedRoles = [
    'admin',
    'finance' // Only finance role can manage tithes
];

if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// Check specific permission
if (!checkPermission('manage_tithes')) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

$message = '';
$tithe = null;
$logger = new AuditLogger($pdo); // Initialize AuditLogger

// Function to send SMS using mNotify
function sendSMS($phone, $message) {
    $apiKey = MNOTIFY_API_KEY; // Use API key from config.php
    $senderId = MNOTIFY_SENDER_ID; // Use sender name from config.php
    $url = 'https://apps.mnotify.net/smsapi';

    // Construct the URL with query parameters
    $queryParams = http_build_query([
        'key' => $apiKey,
        'to' => $phone,
        'msg' => $message,
        'sender_id' => $senderId
    ]);

    $fullUrl = $url . '?' . $queryParams;

    // Use file_get_contents to send the request
    $result = file_get_contents($fullUrl);
    if ($result === FALSE) {
        return [
            'phone' => $phone,
            'status' => 'failed',
            'response' => json_encode(['error' => 'Failed to connect to SMS API'])
        ];
    }
    $decodedResult = json_decode($result, true);
    return [
        'phone' => $phone,
        'status' => isset($decodedResult['status']) && $decodedResult['status'] == 'success' ? 'success' : 'failed',
        'response' => $result
    ];
}

// Fetch members for the dropdown
try {
    $stmt = $pdo->query("SELECT id, name, contact_number_1 FROM members ORDER BY name");
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log($e->getMessage());
    $members = [];
}

// Check if this is an edit request
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT t.*, m.name, m.contact_number_1 
                          FROM tithes t 
                          JOIN members m ON t.member_id = m.id 
                          WHERE t.id = ?");
    $stmt->execute([$id]);
    $tithe = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tithe) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Tithe record not found!</div>";
        header("Location: view_tithes.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $currency = $_POST['currency'];
    $payment_method = $_POST['payment_method'];
    $tithe_date = $_POST['tithe_date'];
    $tithe_month = $_POST['tithe_month'] . "-01"; // Convert YYYY-MM to YYYY-MM-DD
    
    try {
        if (isset($_POST['id'])) {
            // Update existing tithe
            $stmt = $pdo->prepare("UPDATE tithes SET 
                                 member_id = ?, 
                                 amount = ?, 
                                 currency = ?,
                                 payment_method = ?,
                                 tithe_date = ?,
                                 tithe_month = ?
                                 WHERE id = ?");
            $stmt->execute([$member_id, $amount, $currency, $payment_method, $tithe_date, $tithe_month, $_POST['id']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Tithe record updated successfully!</div>";
        } else {
            // Insert new tithe
            $stmt = $pdo->prepare("INSERT INTO tithes (member_id, amount, currency, payment_method, tithe_date, tithe_month) 
                                 VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $amount, $currency, $payment_method, $tithe_date, $tithe_month]);
            $_SESSION['message'] = "<div class='alert alert-success'>Tithe record added successfully!</div>";
        }
        
        // Fetch member details
        $stmt = $pdo->prepare("SELECT name, contact_number_1 FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Format SMS message
        $smsMessage = "Hello " . $member['name'] . ", we have received an amount of " . $currency . " " . $amount . " being your tithe payment for " . $tithe_date . ". Thank you.";
        
        // Send SMS
        $response = sendSMS($member['contact_number_1'], $smsMessage);
        $http_status = $response['status'] == 'success' ? 200 : 500;

        // Log the message in the database
        try {
            $stmt = $pdo->prepare("INSERT INTO messages (
                sender_id,
                recipient_id,
                message,
                message_type,
                status,
                message_title,
                api_response,
                recipient_count,
                sent_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

            $stmt->execute([
                $_SESSION['user_id'], // sender_id (current logged-in user)
                $member_id, // recipient_id (member paying the tithe)
                $smsMessage, // message content
                'single', // message_type
                $http_status == 200 ? 'sent' : 'failed', // status
                'Tithe Payment Confirmation', // message_title
                $response['response'], // api_response
                1 // recipient_count
            ]);

            // Log the action using AuditLogger for message sending
            $logger->log(
                'sms',
                'tithe_payment',
                $member_id,
                "SMS sent for tithe payment confirmation",
                null,
                [
                    'phone' => $member['contact_number_1'],
                    'status' => $response['status'],
                    'response' => $response['response']
                ],
                $response['status']
            );
        } catch (PDOException $e) {
            error_log($e->getMessage());
        }

        // Log the action using AuditLogger for tithe payment
        $logger->log(
            'tithe',
            'tithe_payment',
            $member_id,
            "Tithe payment processed",
            null,
            [
                'amount' => $amount,
                'currency' => $currency,
                'date' => $tithe_date
            ],
            'success'
        );

        header("Location: view_tithes.php");
        exit();
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        
        // Log the action using AuditLogger for tithe payment failure
        $logger->log(
            'tithe',
            'tithe_payment',
            $member_id,
            "Tithe payment processing failed",
            null,
            [
                'amount' => $amount,
                'currency' => $currency,
                'date' => $tithe_date,
                'error' => $e->getMessage()
            ],
            'failed'
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo isset($tithe) ? 'Edit' : 'Add'; ?> Tithe - ChurchEase</title>
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
                        <h4 class="page-title"><?php echo isset($tithe) ? 'Edit' : 'Add'; ?> Tithe</h4>
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
                                <a href="#"><?php echo isset($tithe) ? 'Edit' : 'Add'; ?> Tithe</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title"><?php echo isset($tithe) ? 'Edit Tithe Details' : 'Add New Tithe'; ?></div>
                                </div>
                                <div class="card-body">
                                    <?php if(!empty($message)) echo $message; ?>
                                    <form method="POST">
                                        <?php if(isset($tithe)) : ?>
                                            <input type="hidden" name="id" value="<?php echo $tithe['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Member</label>
                                                    <select class="form-control select2" name="member_id" required>
                                                        <option value="">Select Member</option>
                                                        <?php foreach ($members as $member): ?>
                                                            <option value="<?php echo $member['id']; ?>">
                                                                <?php echo $member['name'] . ' - ' . $member['contact_number_1']; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Tithe Date</label>
                                                    <input type="text" name="tithe_date" class="form-control datepicker" required
                                                           value="<?php echo isset($tithe) ? date('Y-m-d', strtotime($tithe['tithe_date'])) : date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Tithe Month</label>
                                                    <input type="text" name="tithe_month" class="form-control monthpicker" required
                                                           value="<?php echo isset($tithe) ? date('Y-m', strtotime($tithe['tithe_month'])) : date('Y-m'); ?>">
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
                                                               value="<?php echo isset($tithe) ? $tithe['amount'] : ''; ?>"
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
                                                            $selected = isset($tithe) && $tithe['currency'] == $code ? 'selected' : '';
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
                                                        <option value="cash" <?php echo (isset($tithe) && $tithe['payment_method'] == 'cash') ? 'selected' : ''; ?>>Cash</option>
                                                        <option value="momo" <?php echo (isset($tithe) && $tithe['payment_method'] == 'momo') ? 'selected' : ''; ?>>Mobile Money</option>
                                                        <option value="card" <?php echo (isset($tithe) && $tithe['payment_method'] == 'card') ? 'selected' : ''; ?>>Card Payment</option>
                                                        <option value="bank" <?php echo (isset($tithe) && $tithe['payment_method'] == 'bank') ? 'selected' : ''; ?>>Bank Transfer</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-save"></i> 
                                                <?php echo isset($tithe) ? 'Update' : 'Add'; ?> Tithe
                                            </button>
                                            <a href="view_tithes.php" class="btn btn-danger">
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
                placeholder: 'Search for a member...',
                allowClear: true
            });

            // Initialize Date Picker
            $(".datepicker").flatpickr({
                enableTime: false,
                dateFormat: "Y-m-d",
                maxDate: "today",
                defaultDate: "today",
                allowInput: true,
                weekNumbers: true,
                altInput: true,
                altFormat: "F j, Y",
                theme: "material_blue"
            });

            // Initialize Month Picker
            $(".monthpicker").flatpickr({
                enableTime: false,
                dateFormat: "Y-m",
                plugins: [
                    new monthSelectPlugin({
                        shorthand: true,
                        dateFormat: "Y-m",
                        altFormat: "F Y"
                    })
                ],
                altInput: true,
                altFormat: "F Y",
                theme: "material_blue"
            });
        });
    </script>
</body>
</html>