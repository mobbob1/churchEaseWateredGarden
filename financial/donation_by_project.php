<?php
require_once '../includes/auth.php';
require_once '../config.php';


// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'finance'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}
// Get filter parameters
$selectedDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : 'all';

// Fetch all project categories
$categories = $pdo->query("SELECT * FROM project_categories WHERE status = 'active' ORDER BY name")->fetchAll();

// Initialize arrays for totals
$categoryTotals = [];
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];
foreach ($currencies as $currency) {
    $categoryTotals[$currency] = 0;
}

// Prepare the SQL query based on filters
$sql = "
    SELECT 
        d.*,
        m.name as donor_name,
        m.phone as donor_phone,
        pc.name as project_category,
        pc.description as category_description
    FROM donations d
    LEFT JOIN members m ON d.member_id = m.id
    LEFT JOIN project_categories pc ON d.project_category_id = pc.id
    WHERE DATE(d.donation_date) = :date
";

if ($selectedCategory !== 'all') {
    $sql .= " AND d.project_category_id = :category_id";
}

$sql .= " ORDER BY d.donation_date DESC, d.id DESC";

$stmt = $pdo->prepare($sql);
$params = ['date' => $selectedDate];

if ($selectedCategory !== 'all') {
    $params['category_id'] = $selectedCategory;
}

$stmt->execute($params);
$donations = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Donations by Project - ChurchEaseSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
     <link rel="icon" href="../loginres/images/favicon/favicon.ico" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.bootstrap4.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Donations by Project</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Donations for <?php echo date('F j, Y', strtotime($selectedDate)); ?></h4>
                                        <div class="ml-auto">
                                            <form class="form-inline">
                                                <input type="date" name="date" class="form-control mr-2" value="<?php echo $selectedDate; ?>">
                                                <select name="category" class="form-control mr-2">
                                                    <option value="all">All Categories</option>
                                                    <?php foreach($categories as $category): ?>
                                                        <option value="<?php echo $category['id']; ?>" 
                                                                <?php echo $selectedCategory == $category['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($category['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="donations-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Time</th>
                                                    <th>Donor</th>
                                                    <th>Project Category</th>
                                                    <th>Amount</th>
                                                    <th>Currency</th>
                                                    <th>Payment Method</th>
                                                    <th>Reference</th>
                                                    <th>Notes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach($donations as $donation): 
                                                    $categoryTotals[$donation['currency']] += $donation['amount'];
                                                ?>
                                                    <tr>
                                                        <td><?php echo date('H:i', strtotime($donation['donation_date'])); ?></td>
                                                        <td>
                                                            <?php 
                                                            echo htmlspecialchars($donation['donor_name'] ?? 'Anonymous');
                                                            if ($donation['donor_phone']) {
                                                                echo '<br><small class="text-muted">' . 
                                                                     htmlspecialchars($donation['donor_phone']) . '</small>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            echo htmlspecialchars($donation['project_category'] ?? 'General');
                                                            if ($donation['category_description']) {
                                                                echo '<br><small class="text-muted">' . 
                                                                     htmlspecialchars($donation['category_description']) . '</small>';
                                                            }
                                                            ?>
                                                        </td>
                                                        <td class="text-right"><?php echo number_format($donation['amount'], 2); ?></td>
                                                        <td><?php echo htmlspecialchars($donation['currency']); ?></td>
                                                        <td><?php echo htmlspecialchars($donation['payment_method']); ?></td>
                                                        <td><?php echo htmlspecialchars($donation['reference_no']); ?></td>
                                                        <td><?php echo htmlspecialchars($donation['notes'] ?? ''); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                            <tfoot>
                                                <?php foreach($currencies as $currency): 
                                                    if ($categoryTotals[$currency] > 0):
                                                ?>
                                                    <tr class="font-weight-bold">
                                                        <td colspan="3" class="text-right">Total (<?php echo $currency; ?>):</td>
                                                        <td class="text-right"><?php echo number_format($categoryTotals[$currency], 2); ?></td>
                                                        <td colspan="4"></td>
                                                    </tr>
                                                <?php 
                                                    endif;
                                                endforeach; 
                                                ?>
                                            </tfoot>
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

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <!-- DataTables -->
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#donations-table').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    'copy', 
                    'csv', 
                    {
                        extend: 'excel',
                        title: 'Donations Report - ' + '<?php echo date('Y-m-d', strtotime($selectedDate)); ?>',
                        messageTop: 'Donations by Project Category'
                    },
                    {
                        extend: 'pdf',
                        title: 'Donations Report - ' + '<?php echo date('Y-m-d', strtotime($selectedDate)); ?>',
                        messageTop: 'Donations by Project Category'
                    },
                    'print'
                ],
                pageLength: 25,
                order: [[0, 'desc']]
            });
        });
    </script>
</body>
</html>