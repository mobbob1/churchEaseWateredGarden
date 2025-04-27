<?php
// attendance/checkin.php
require_once '../config.php';

// Process check-in
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = $_POST['phone_number'] ?? '';
    $event_id = $_POST['event_id'] ?? '';
    $message = '';
    
    if (!empty($phone) && !empty($event_id)) {
        try {
            // Check if member exists
            $stmt = $pdo->prepare("SELECT id, name FROM members WHERE contact_number_1 = ?");
            $stmt->execute([$phone]);
            $member = $stmt->fetch();
            
            if ($member) {
                // Check if already checked in
                $stmt = $pdo->prepare("SELECT id FROM attendance WHERE member_id = ? AND event_id = ? AND DATE(checkin_time) = CURDATE()");
                $stmt->execute([$member['id'], $event_id]);
                
                if (!$stmt->fetch()) {
                    // Record attendance
                    $stmt = $pdo->prepare("INSERT INTO attendance (member_id, event_id, checkin_time) VALUES (?, ?, NOW())");
                    $stmt->execute([$member['id'], $event_id]);
                    
                    $message = "<div class='alert alert-success'>Welcome " . htmlspecialchars($member['name']) . "! Your attendance has been recorded.</div>";
                } else {
                    $message = "<div class='alert alert-warning'>You have already checked in for this event today.</div>";
                }
            } else {
                $message = "<div class='alert alert-danger'>Phone number not found. Please check the number or register as a member.</div>";
            }
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>An error occurred. Please try again.</div>";
            error_log($e->getMessage());
        }
    }
}

// Get active events
$events = [];
try {
    $stmt = $pdo->prepare("SELECT id, event_name FROM events WHERE  DATE(event_date) = CURDATE()");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Check-In - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
</head>
<body>
    <div class="wrapper">
       

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Event Check-In</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Member Check-In</div>
                                </div>
                                <div class="card-body">
                                    <?php if (isset($message)) echo $message; ?>
                                    
                                    <form method="POST">
                                        <div class="form-group">
                                            <label>Phone Number</label>
                                            <input type="text" class="form-control" name="phone_number" required 
                                                   placeholder="Enter your phone number" 
                                                   pattern="[0-9]+" 
                                                   title="Please enter numbers only">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Select Event</label>
                                            <select class="form-control" name="event_id" required>
                                                <option value="">Select Event</option>
                                                <?php foreach ($events as $event): ?>
                                                    <option value="<?php echo $event['id']; ?>">
                                                        <?php echo htmlspecialchars($event['event_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Check In</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Check-ins -->
                      
                    </div>
                </div>
            </div>
            <?php include '../res/footer.php'; ?>
        </div>
    </div>
    
    <!--   Core JS Files   -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>