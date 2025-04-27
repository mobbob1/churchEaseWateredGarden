<?php
require_once '../includes/auth.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role for event management
$allowedRoles = [
    'seer',
    'hr',           // HR handles event management
    'pastorate',    // Pastoral staff can manage events
    'admin_assistant' // Admin assistants can register events
];

if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Check specific permission
if (!checkPermission('manage_events')) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

$message = '';
$event = null;

// Check if this is an edit request
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        $_SESSION['message'] = "<div class='alert alert-danger'>Event not found!</div>";
        header("Location: view_events.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = $_POST['event_name'];
    $event_date = $_POST['event_date'];
    $location = $_POST['location'];
    $description = $_POST['description'];
    
    try {
        if (isset($_POST['id'])) {
            // Update existing event
            $stmt = $pdo->prepare("UPDATE events SET 
                                 event_name = ?, 
                                 event_date = ?, 
                                 location = ?, 
                                 description = ? 
                                 WHERE id = ?");
            $stmt->execute([$event_name, $event_date, $location, $description, $_POST['id']]);
            $_SESSION['message'] = "<div class='alert alert-success'>Event updated successfully!</div>";
        } else {
            // Insert new event
            $stmt = $pdo->prepare("INSERT INTO events (event_name, event_date, location, description) 
                                 VALUES (?, ?, ?, ?)");
            $stmt->execute([$event_name, $event_date, $location, $description]);
            $_SESSION['message'] = "<div class='alert alert-success'>Event added successfully!</div>";
        }
        header("Location: view_events.php");
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
    <title><?php echo isset($event) ? 'Edit' : 'Add'; ?> Event - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>

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
        <!-- Header -->
        <?php include '../res/main_header.php'; ?>
        <!-- End Header -->
        <!-- Sidebar -->
        <?php include '../res/sidebar.php'; ?>
        <!-- End Sidebar -->
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title"><?php echo isset($event) ? 'Edit' : 'Add'; ?> Event</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Events</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#"><?php echo isset($event) ? 'Edit' : 'Add'; ?> Event</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title"><?php echo isset($event) ? 'Edit Event Details' : 'Add New Event'; ?></div>
                                </div>
                                <div class="card-body">
                                    <?php if(!empty($message)) echo $message; ?>
                                    <form method="POST">
                                        <?php if(isset($event)) : ?>
                                            <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                        <?php endif; ?>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Event Name</label>
                                                    <input type="text" name="event_name" class="form-control" required 
                                                           value="<?php echo isset($event) ? $event['event_name'] : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Event Date</label>
                                                    <input type="datetime-local" name="event_date" class="form-control" required
                                                           value="<?php echo isset($event) ? date('Y-m-d\TH:i', strtotime($event['event_date'])) : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Location</label>
                                                    <input type="text" name="location" class="form-control" required
                                                           value="<?php echo isset($event) ? $event['location'] : ''; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Description</label>
                                                    <textarea name="description" class="form-control" rows="3"><?php echo isset($event) ? $event['description'] : ''; ?></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success"><?php echo isset($event) ? 'Update' : 'Add'; ?> Event</button>
                                            <a href="view_events.php" class="btn btn-danger">Cancel</a>
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

    <!--   Core JS Files   -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>