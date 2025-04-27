<?php
require_once './config.php';
session_start();

$message = '';
$event_id = isset($_GET['event']) ? (int)$_GET['event'] : 0;

// Verify event exists and is active
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ? AND event_date >= CURDATE()");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    $message = "<div class='alert alert-danger'>Invalid or expired event.</div>";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $event) {
    $phone = $_POST['phone'];
    
    // Check if member exists
    $stmt = $pdo->prepare("SELECT id FROM members WHERE phone = ?");
    $stmt->execute([$phone]);
    $member = $stmt->fetch();
    
    if ($member) {
        // Check if already checked in
        $stmt = $pdo->prepare("SELECT id FROM attendance 
                              WHERE member_id = ? AND event_id = ? 
                              AND DATE(checkin_time) = CURDATE()");
        $stmt->execute([$member['id'], $event_id]);
        
        if (!$stmt->fetch()) {
            // Record attendance
            $stmt = $pdo->prepare("INSERT INTO attendance (member_id, event_id, checkin_time) 
                                 VALUES (?, ?, NOW())");
            $stmt->execute([$member['id'], $event_id]);
            $message = "<div class='alert alert-success'>Attendance recorded successfully!</div>";
        } else {
            $message = "<div class='alert alert-info'>You have already checked in for this event.</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Phone number not found. Please register first.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Check-in</title>
    <link rel="stylesheet" href="./res/assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .check-in-container {
            max-width: 400px;
            width: 90%;
            padding: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 150px;
        }
    </style>
</head>
<body>
    <div class="check-in-container">
        <div class="logo">
            <img src="./res/assets/img/logo.png" alt="Church Logo">
        </div>
        <div class="card">
            <div class="card-body">
                <?php if ($event): ?>
                    <h4 class="card-title text-center mb-4"><?php echo htmlspecialchars($event['event_name']); ?></h4>
                    <p class="text-center text-muted">
                        <?php echo date('F j, Y', strtotime($event['event_date'])); ?>
                    </p>
                    
                    <?php if ($message) echo $message; ?>
                    
                    <form method="POST" class="mt-4">
                        <div class="form-group">
                            <label>Enter your phone number</label>
                            <input type="tel" name="phone" class="form-control form-control-lg" 
                                   required pattern="[0-9]+" 
                                   placeholder="Enter phone number..."
                                   autofocus>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg btn-block mt-4">
                            Check In
                        </button>
                    </form>
                <?php else: ?>
                    <?php echo $message; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="./res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="./res/assets/js/core/bootstrap.min.js"></script>
</body>
</html>