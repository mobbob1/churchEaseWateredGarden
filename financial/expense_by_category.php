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
// Get the year and month from query parameters or use current date
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');

// Get all days in the selected month
$firstDay = new DateTime("$year-$month-01");
$lastDay = new DateTime("$year-$month-" . $firstDay->format('t'));
$days = [];

$current = clone $firstDay;
while ($current <= $lastDay) {
    $days[] = $current->format('Y-m-d');
    $current->modify('+1 day');
}

// Fetch all expense categories
$expenseCategories = $pdo->query("SELECT * FROM expense_categories ORDER BY name")->fetchAll();

// Initialize arrays for totals
$categoryTotals = [];
$dailyTotals = [];
$currencies = ['GHS', 'USD', 'EUR', 'GBP', 'NGN'];

foreach ($currencies as $currency) {
    $dailyTotals[$currency] = array_fill_keys($days, 0);
    foreach ($expenseCategories as $category) {
        $categoryTotals[$category['name']][$currency] = 0;
    }
}

// Fetch expense data
$expenseData = [];
foreach ($expenseCategories as $category) {
    $expenseData[$category['name']] = [
        'name' => $category['name'],
        'description' => $category['description'],
        'days' => array_fill_keys($days, []),
        'totals' => array_fill_keys($currencies, 0)
    ];
}

// Fetch all expenses for the month at once
$stmt = $pdo->prepare("
    SELECT e.*, ec.name as category_name, ec.description as category_description
    FROM expenses e
    JOIN expense_categories ec ON e.category = ec.name
    WHERE YEAR(e.expense_date) = ? AND MONTH(e.expense_date) = ?
    ORDER BY e.expense_date, ec.name
");
$stmt->execute([$year, $month]);
$expenses = $stmt->fetchAll();

// Organize expenses by category and day
foreach ($expenses as $expense) {
    $day = date('Y-m-d', strtotime($expense['expense_date']));
    $categoryName = $expense['category_name'];
    
    if (isset($expenseData[$categoryName])) {
        $expenseData[$categoryName]['days'][$day][] = $expense;
        $expenseData[$categoryName]['totals'][$expense['currency']] += $expense['amount'];
        $dailyTotals[$expense['currency']][$day] += $expense['amount'];
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Expenses by Category - Church Management System</title>
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
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.bootstrap4.min.css">
    
    <style>
        .currency-total { font-weight: bold; color: #2980b9; }
        .month-total { background-color: #f8f9fa; }
        .grand-total { font-weight: bold; background-color: #e9ecef; }
        .sticky-header { position: sticky; top: 0; background: white; z-index: 1; }
        .category-description { font-size: 0.85em; color: #666; }
        .expense-details { font-size: 0.9em; color: #555; }
        .expense-amount { font-weight: bold; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Expenses by Category</h4>
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
                                <a href="#">Expenses by Category</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">
                                            Expenses for <?php echo date('F Y', strtotime("$year-$month-01")); ?>
                                        </h4>
                                        <div class="ml-auto">
                                            <form class="form-inline">
                                                <select name="month" class="form-control mr-2">
                                                    <?php for($m = 1; $m <= 12; $m++): ?>
                                                        <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
                                                            <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                                <select name="year" class="form-control mr-2">
                                                    <?php for($y = date('Y'); $y >= date('Y')-5; $y--): ?>
                                                        <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
                                                            <?php echo $y; ?>
                                                        </option>
                                                    <?php endfor; ?>
                                                </select>
                                                <button type="submit" class="btn btn-primary">Filter</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($currencies as $currency): ?>
                                        <h5 class="mt-4 mb-3">Expenses in <?php echo $currency; ?></h5>
                                        <div class="table-responsive">
                                            <table class="expense-table display table table-striped table-hover">
                                                <thead class="sticky-header">
                                                    <tr>
                                                        <th>Category</th>
                                                        <?php foreach($days as $day): ?>
                                                            <th class="text-center"><?php echo date('j', strtotime($day)); ?></th>
                                                        <?php endforeach; ?>
                                                        <th class="text-right">Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach($expenseData as $categoryName => $data): ?>
                                                        <tr>
                                                            <td>
                                                                <strong><?php echo htmlspecialchars($data['name']); ?></strong>
                                                                <?php if (!empty($data['description'])): ?>
                                                                    <br><span class="category-description">
                                                                        <?php echo htmlspecialchars($data['description']); ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </td>
                                                            <?php 
                                                            $categoryTotal = 0;
                                                            foreach($days as $day): 
                                                                $dayTotal = 0;
                                                                foreach ($data['days'][$day] as $expense) {
                                                                    if ($expense['currency'] === $currency) {
                                                                        $dayTotal += $expense['amount'];
                                                                    }
                                                                }
                                                                $categoryTotal += $dayTotal;
                                                            ?>
                                                                <td class="text-right">
                                                                    <?php if ($dayTotal > 0): ?>
                                                                        <span class="expense-amount">
                                                                            <?php echo number_format($dayTotal, 2); ?>
                                                                        </span>
                                                                    <?php else: ?>
                                                                        -
                                                                    <?php endif; ?>
                                                                </td>
                                                            <?php endforeach; ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo number_format($categoryTotal, 2); ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <!-- Daily Totals Row -->
                                                    <tr class="month-total">
                                                        <td><strong>Daily Total</strong></td>
                                                        <?php 
                                                        $monthTotal = 0;
                                                        foreach($days as $day): 
                                                            $dayTotal = $dailyTotals[$currency][$day];
                                                            $monthTotal += $dayTotal;
                                                        ?>
                                                            <td class="text-right font-weight-bold">
                                                                <?php echo $dayTotal > 0 ? number_format($dayTotal, 2) : '-'; ?>
                                                            </td>
                                                        <?php endforeach; ?>
                                                        <td class="text-right grand-total">
                                                            <?php echo number_format($monthTotal, 2); ?>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                        <!-- Category Distribution Chart -->
                                        <div class="mt-4">
                                            <canvas id="categoryDistributionChart<?php echo $currency; ?>"></canvas>
                                        </div>
                                    <?php endforeach; ?>
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
    <script src="../res/assets/