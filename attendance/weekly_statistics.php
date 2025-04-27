<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';
require_once __DIR__ . '/../config.php';

$logger = new AuditLogger($pdo);

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: /outpouringcrm/index.php?error=unauthorized');
    exit();
}

// Function to get all Sundays in a month
function getSundaysInMonth($year, $month) {
    $sundays = [];
    $date = new DateTime("$year-$month-01");
    $date->modify('first sunday of this month');
    
    while ($date->format('m') == $month) {
        $sundays[] = $date->format('Y-m-d');
        $date->modify('next sunday');
    }
    return $sundays;
}

// Define fields array
$fields = [
    'church_services' => 'Church Services Attendance',
    'adults_services' => 'Adults Services Attendance',
    'youth_services' => 'Youth/Teens Services Attendance',
    'children_services' => 'Children Services Attendance',
    'communion_services' => 'Communion Services Participants',
    'new_members' => 'New Members Received at Leaders Meeting',
    'visitors' => 'Visitors',
    'prayer_meeting' => 'Prayer Meeting Attendance',
    'mprp_attendance' => 'MPRP Attendance',
    'revival_meeting' => 'Revival Meeting Attendance',
    'outreach_members' => 'Outreach Members Participating',
    'souls_won' => 'Souls Won(New Members)',
    'marriages' => 'Marriages',
    'births' => 'Births',
    'baptisms' => 'Baptisms',
    'confirmations' => 'Confirmations',
    'deaths' => 'Deaths'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $month = $_POST['month'];
    
    try {
        $pdo->beginTransaction();

        // Get the number of Sundays for this month
        $year = substr($month, 0, 4);
        $m = substr($month, 5, 2);
        $sundays = getSundaysInMonth($year, $m);
        $numSundays = count($sundays);

        // Check if statistics exist for this month
        $stmt = $pdo->prepare("SELECT id FROM church_statistics WHERE month = ?");
        $stmt->execute([$month]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Update existing statistics
            $stmt = $pdo->prepare("UPDATE church_statistics SET updated_at = NOW() WHERE id = ?");
            $stmt->execute([$existing['id']]);
            
            foreach ($fields as $field_key => $field_name) {
                $stmt = $pdo->prepare("UPDATE church_statistics_details SET 
                    sunday1 = ?, sunday2 = ?, sunday3 = ?, sunday4 = ?, sunday5 = ?, total = ?
                    WHERE statistics_id = ? AND operation = ?");
                
                $total = 0;
                $values = [];
                for ($i = 1; $i <= 5; $i++) {
                    // Only process existing Sundays, set others to 0
                    if ($i <= $numSundays) {
                        $value = isset($_POST["{$field_key}_sunday{$i}"]) ? intval($_POST["{$field_key}_sunday{$i}"]) : 0;
                    } else {
                        $value = 0;
                    }
                    $values[] = $value;
                    $total += $value;
                }
                $values[] = $total;
                $values[] = $existing['id'];
                $values[] = $field_key;
                
                $stmt->execute($values);
            }
            
            $message = "<div class='alert alert-success'>Statistics updated successfully!</div>";
            $logger->log('statistics_update', 'church_statistics', $existing['id'], "Updated statistics for month: $month", $_SESSION['user_id']);
        } else {
            // Insert new statistics
            $stmt = $pdo->prepare("INSERT INTO church_statistics (month, created_at) VALUES (?, NOW())");
            $stmt->execute([$month]);
            $statistics_id = $pdo->lastInsertId();
            
            foreach ($fields as $field_key => $field_name) {
                $stmt = $pdo->prepare("INSERT INTO church_statistics_details 
                    (statistics_id, operation, sunday1, sunday2, sunday3, sunday4, sunday5, total)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                
                $total = 0;
                $values = [$statistics_id, $field_key];
                for ($i = 1; $i <= 5; $i++) {
                    // Only process existing Sundays, set others to 0
                    if ($i <= $numSundays) {
                        $value = isset($_POST["{$field_key}_sunday{$i}"]) ? intval($_POST["{$field_key}_sunday{$i}"]) : 0;
                    } else {
                        $value = 0;
                    }
                    $values[] = $value;
                    $total += $value;
                }
                $values[] = $total;
                
                $stmt->execute($values);
            }
            
            $message = "<div class='alert alert-success'>Statistics added successfully!</div>";
            $logger->log('statistics_create', 'church_statistics', $statistics_id, "Added new statistics for month: $month", $_SESSION['user_id']);
        }
        
        $pdo->commit();
        
        // Redirect to view page after successful save
        header("Location: view_statistics.php");
        exit();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        $logger->log('statistics_error', 'church_statistics', null, "Error with statistics for month: $month", $_SESSION['user_id'], null, 'failed', $e->getMessage());
    }
}

// Get statistics data if editing
$statistics_data = [];
if (isset($_GET['month'])) {
    $stmt = $pdo->prepare("
        SELECT cs.*, csd.* 
        FROM church_statistics cs
        LEFT JOIN church_statistics_details csd ON cs.id = csd.statistics_id
        WHERE cs.month = ?
    ");
    $stmt->execute([$_GET['month']]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($results as $row) {
        $statistics_data[$row['operation']] = $row;
    }
}

// Get current month if not editing
$selected_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$year = substr($selected_month, 0, 4);
$month = substr($selected_month, 5, 2);
$sundays = getSundaysInMonth($year, $month);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title><?php echo isset($_GET['month']) ? 'Edit' : 'Add'; ?> Statistics - ChurchEase</title>
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
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title"><?php echo isset($_GET['month']) ? 'Edit' : 'Add'; ?> Monthly Statistics</h4>
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
                                <a href="#">Statistics</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#"><?php echo isset($_GET['month']) ? 'Edit' : 'Add'; ?> Statistics</a>
                            </li>
                        </ul>
                    </div>
                    
                    <?php if (isset($message)) echo $message; ?>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title"><?php echo isset($_GET['month']) ? 'Edit Statistics' : 'Add New Statistics'; ?></div>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="statisticsForm">
                                        <div class="row mb-4">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Select Month <span class="text-danger">*</span></label>
                                                    <input type="month" name="month" class="form-control" value="<?php echo $selected_month; ?>" <?php echo isset($_GET['month']) ? 'readonly' : ''; ?> required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Operations</th>
                                                        <?php foreach ($sundays as $index => $sunday): ?>
                                                            <th>
                                                                <?php echo date('M d', strtotime($sunday)); ?>
                                                                <br>
                                                                <small class="text-muted">Sunday <?php echo $index + 1; ?></small>
                                                            </th>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($fields as $field_key => $field_name): ?>
                                                        <tr>
                                                            <td><?php echo $field_name; ?></td>
                                                            <?php for ($i = 1; $i <= count($sundays); $i++): ?>
                                                                <td>
                                                                    <input type="number" 
                                                                           name="<?php echo $field_key . '_sunday' . $i; ?>" 
                                                                           class="form-control" 
                                                                           min="0"
                                                                           value="<?php echo isset($statistics_data[$field_key]["sunday$i"]) ? $statistics_data[$field_key]["sunday$i"] : '0'; ?>">
                                                                </td>
                                                            <?php endfor; ?>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success">
                                                <i class="fa fa-save"></i> Save Statistics
                                            </button>
                                            <a href="view_statistics.php" class="btn btn-danger">
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