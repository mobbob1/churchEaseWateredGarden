<?php
require_once '../includes/auth.php';
require_once '../config.php';
require_once '../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: ../index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['tithe_manager', 'finance', 'admin'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: ../access_denied.php');
    exit();
}

// Initialize audit logger
$logger = new AuditLogger($pdo);

// Get bible classes for filter
$classes = $pdo->query("
    SELECT id, class_name 
    FROM bible_classes 
    WHERE status = 'active' 
    ORDER BY class_name
")->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Validate and sanitize input
        $member_id = (int)$_POST['member_id'];
        $amount = (float)$_POST['amount'];
        $currency = $_POST['currency'];
        $payment_date = $_POST['payment_date'];
        $payment_method = $_POST['payment_method'];
        $reference_number = $_POST['reference_number'] ?? null;
        $notes = $_POST['notes'] ?? null;
        
        // Insert tithe record
        $stmt = $pdo->prepare("
            INSERT INTO tithes (
                member_id, amount, currency, payment_date, payment_method,
                reference_number, notes, recorded_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        
        $stmt->execute([
            $member_id,
            $amount,
            $currency,
            $payment_date,
            $payment_method,
            $reference_number,
            $notes,
            $_SESSION['user_id']
        ]);
        
        $tithe_id = $pdo->lastInsertId();
        
        // Log the action
        $logger->log(
            'tithe_recording',
            'financial',
            $_SESSION['user_id'],
            "Recorded tithe payment for member ID: $member_id",
            $tithe_id,
            [
                'amount' => $amount,
                'currency' => $currency,
                'payment_date' => $payment_date,
                'payment_method' => $payment_method
            ]
        );
        
        $pdo->commit();
        $_SESSION['message'] = "<div class='alert alert-success'>Tithe payment recorded successfully!</div>";
        header('Location: tithe_tracking.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['message'] = "<div class='alert alert-danger'>Error recording tithe payment: " . $e->getMessage() . "</div>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Record Tithe - ChurchEase</title>
    <?php include '../res/assets.php'; ?>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Record Tithe</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../home/dashboard.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="tithe_tracking.php">Tithe Tracking</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Record Tithe</a>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if(isset($_SESSION['message'])) {
                        echo $_SESSION['message'];
                        unset($_SESSION['message']);
                    } ?>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Record Tithe Payment</div>
                                </div>
                                <div class="card-body">
                                    <form method="POST" class="form-horizontal">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Bible Class</label>
                                                    <select id="class_filter" class="form-control" onchange="loadMembers()">
                                                        <option value="">Select Class</option>
                                                        <?php foreach ($classes as $class): ?>
                                                        <option value="<?php echo $class['id']; ?>">
                                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Member <span class="text-danger">*</span></label>
                                                    <select name="member_id" id="member_id" class="form-control select2" required>
                                                        <option value="">Select Member</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Member Details Section -->
                                        <div id="member_details"></div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Amount <span class="text-danger">*</span></label>
                                                    <input type="number" name="amount" class="form-control" step="0.01" min="0" required>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Currency <span class="text-danger">*</span></label>
                                                    <select name="currency" class="form-control" required>
                                                        <option value="GHS">GHS</option>
                                                        <option value="USD">USD</option>
                                                        <option value="EUR">EUR</option>
                                                        <option value="GBP">GBP</option>
                                                        <option value="NGN">NGN</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Payment Date <span class="text-danger">*</span></label>
                                                    <input type="date" name="payment_date" class="form-control" required 
                                                           value="<?php echo date('Y-m-d'); ?>" 
                                                           max="<?php echo date('Y-m-d'); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Payment Method <span class="text-danger">*</span></label>
                                                    <select name="payment_method" class="form-control" required>
                                                        <option value="Cash">Cash</option>
                                                        <option value="Bank Transfer">Bank Transfer</option>
                                                        <option value="Mobile Money">Mobile Money</option>
                                                        <option value="Check">Check</option>
                                                        <option value="Card">Card</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Reference Number</label>
                                                    <input type="text" name="reference_number" class="form-control" 
                                                           placeholder="Transaction ID, Check Number, etc.">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Notes</label>
                                            <textarea name="notes" class="form-control" rows="3" 
                                                      placeholder="Any additional notes about the tithe payment"></textarea>
                                        </div>
                                        
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-save"></i> Record Payment
                                            </button>
                                            <a href="tithe_tracking.php" class="btn btn-danger">
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
    <?php include '../res/scripts.php'; ?>
    
    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: "bootstrap"
            });
        });
        
        // Load members based on selected class
        function loadMembers() {
            var classId = $('#class_filter').val();
            var memberSelect = $('#member_id');
            
            memberSelect.empty().append('<option value="">Select Member</option>');
            
            if (classId) {
                $.ajax({
                    url: 'get_class_members.php',
                    type: 'GET',
                    data: { class_id: classId },
                    success: function(response) {
                        var members = JSON.parse(response);
                        members.forEach(function(member) {
                            memberSelect.append(new Option(member.full_name, member.id));
                        });
                        memberSelect.trigger('change'); // Refresh Select2
                    },
                    error: function() {
                        alert('Error loading members');
                    }
                });
            }
        }
    </script>
</body>
</html>