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

// Get date from request or default to current date
$as_of_date = isset($_GET['as_of_date']) ? $_GET['as_of_date'] : date('Y-m-d');
$currency = isset($_GET['currency']) ? $_GET['currency'] : 'GHS';

// Function to get assets by type
function getAssets($pdo, $as_of_date, $currency, $type) {
    $stmt = $pdo->prepare("
        SELECT 
            a.*,
            COALESCE(SUM(at.amount * CASE 
                WHEN at.transaction_type = 'depreciation' THEN -1 
                ELSE 1 
            END), 0) as value_change
        FROM assets a
        LEFT JOIN asset_transactions at ON a.id = at.asset_id 
            AND at.transaction_date <= ?
        WHERE a.type = ?
        AND a.currency = ?
        AND a.status = 'active'
        GROUP BY a.id
        ORDER BY a.category, a.name
    ");
    $stmt->execute([$as_of_date, $type, $currency]);
    return $stmt->fetchAll();
}

// Function to get liabilities by type
function getLiabilities($pdo, $as_of_date, $currency, $type) {
    $stmt = $pdo->prepare("
        SELECT 
            l.*,
            COALESCE(SUM(lp.amount), 0) as total_payments
        FROM liabilities l
        LEFT JOIN liability_payments lp ON l.id = lp.liability_id 
            AND lp.payment_date <= ?
        WHERE l.type = ?
        AND l.currency = ?
        AND l.status = 'active'
        GROUP BY l.id
        ORDER BY l.category, l.name
    ");
    $stmt->execute([$as_of_date, $type, $currency]);
    return $stmt->fetchAll();
}

// Function to get equity
function getEquity($pdo, $as_of_date, $currency) {
    $stmt = $pdo->prepare("
        SELECT 
            type,
            SUM(amount) as total
        FROM equity
        WHERE date <= ?
        AND currency = ?
        GROUP BY type
        ORDER BY type
    ");
    $stmt->execute([$as_of_date, $currency]);
    return $stmt->fetchAll();
}

// Get financial data
$current_assets = getAssets($pdo, $as_of_date, $currency, 'current');
$fixed_assets = getAssets($pdo, $as_of_date, $currency, 'fixed');
$current_liabilities = getLiabilities($pdo, $as_of_date, $currency, 'current');
$long_term_liabilities = getLiabilities($pdo, $as_of_date, $currency, 'long_term');
$equity = getEquity($pdo, $as_of_date, $currency);

// Calculate totals
$total_current_assets = array_sum(array_map(function($asset) {
    return $asset['current_value'] + $asset['value_change'];
}, $current_assets));

$total_fixed_assets = array_sum(array_map(function($asset) {
    return $asset['current_value'] + $asset['value_change'];
}, $fixed_assets));

$total_assets = $total_current_assets + $total_fixed_assets;

$total_current_liabilities = array_sum(array_map(function($liability) {
    return $liability['amount'] - $liability['total_payments'];
}, $current_liabilities));

$total_long_term_liabilities = array_sum(array_map(function($liability) {
    return $liability['amount'] - $liability['total_payments'];
}, $long_term_liabilities));

$total_liabilities = $total_current_liabilities + $total_long_term_liabilities;

$total_equity = array_sum(array_column($equity, 'total'));

// Get available currencies
$currencies = $pdo->query("
    SELECT DISTINCT currency 
    FROM (
        SELECT currency FROM assets
        UNION
        SELECT currency FROM liabilities
        UNION
        SELECT currency FROM equity
    ) as currencies
    ORDER BY currency
")->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Balance Sheet - ChurchEaseSuperb</title>
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
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Balance Sheet</h4>
                    </div>
                    
                    <!-- Filters -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <form method="GET" class="form-inline">
                                        <div class="form-group mx-sm-3">
                                            <label for="as_of_date" class="mr-2">As of Date:</label>
                                            <input type="date" class="form-control" id="as_of_date" name="as_of_date" 
                                                value="<?php echo $as_of_date; ?>">
                                        </div>
                                        <div class="form-group mx-sm-3">
                                            <label for="currency" class="mr-2">Currency:</label>
                                            <select class="form-control" name="currency" id="currency">
                                                <?php foreach ($currencies as $curr): ?>
                                                    <option value="<?php echo $curr; ?>" <?php echo $curr === $currency ? 'selected' : ''; ?>>
                                                        <?php echo $curr; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Sheet -->
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Balance Sheet</h4>
                                        <button class="btn btn-primary btn-round ml-auto" onclick="printBalanceSheet()">
                                            <i class="fa fa-print"></i> Print Balance Sheet
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th colspan="2" class="text-center">
                                                        Balance Sheet<br>
                                                        As of <?php echo date('F d, Y', strtotime($as_of_date)); ?><br>
                                                        (In <?php echo $currency; ?>)
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <!-- Assets Section -->
                                                <tr>
                                                    <th colspan="2">Assets</th>
                                                </tr>
                                                <tr>
                                                    <th class="pl-4">Current Assets</th>
                                                    <td></td>
                                                </tr>
                                                <?php foreach ($current_assets as $asset): ?>
                                                    <tr>
                                                        <td class="pl-5"><?php echo htmlspecialchars($asset['name']); ?></td>
                                                        <td class="text-right">
                                                            <?php echo number_format($asset['current_value'] + $asset['value_change'], 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-info">
                                                    <td class="pl-4">Total Current Assets</td>
                                                    <td class="text-right"><?php echo number_format($total_current_assets, 2); ?></td>
                                                </tr>

                                                <tr>
                                                    <th class="pl-4">Fixed Assets</th>
                                                    <td></td>
                                                </tr>
                                                <?php foreach ($fixed_assets as $asset): ?>
                                                    <tr>
                                                        <td class="pl-5"><?php echo htmlspecialchars($asset['name']); ?></td>
                                                        <td class="text-right">
                                                            <?php echo number_format($asset['current_value'] + $asset['value_change'], 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-info">
                                                    <td class="pl-4">Total Fixed Assets</td>
                                                    <td class="text-right"><?php echo number_format($total_fixed_assets, 2); ?></td>
                                                </tr>

                                                <tr class="table-primary">
                                                    <th>Total Assets</th>
                                                    <th class="text-right"><?php echo number_format($total_assets, 2); ?></th>
                                                </tr>

                                                <!-- Liabilities Section -->
                                                <tr>
                                                    <th colspan="2">Liabilities</th>
                                                </tr>
                                                <tr>
                                                    <th class="pl-4">Current Liabilities</th>
                                                    <td></td>
                                                </tr>
                                                <?php foreach ($current_liabilities as $liability): ?>
                                                    <tr>
                                                        <td class="pl-5"><?php echo htmlspecialchars($liability['name']); ?></td>
                                                        <td class="text-right">
                                                            <?php echo number_format($liability['amount'] - $liability['total_payments'], 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-info">
                                                    <td class="pl-4">Total Current Liabilities</td>
                                                    <td class="text-right"><?php echo number_format($total_current_liabilities, 2); ?></td>
                                                </tr>

                                                <tr>
                                                    <th class="pl-4">Long-term Liabilities</th>
                                                    <td></td>
                                                </tr>
                                                <?php foreach ($long_term_liabilities as $liability): ?>
                                                    <tr>
                                                        <td class="pl-5"><?php echo htmlspecialchars($liability['name']); ?></td>
                                                        <td class="text-right">
                                                            <?php echo number_format($liability['amount'] - $liability['total_payments'], 2); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-info">
                                                    <td class="pl-4">Total Long-term Liabilities</td>
                                                    <td class="text-right"><?php echo number_format($total_long_term_liabilities, 2); ?></td>
                                                </tr>

                                                <tr class="table-danger">
                                                    <th>Total Liabilities</th>
                                                    <th class="text-right"><?php echo number_format($total_liabilities, 2); ?></th>
                                                </tr>

                                                <!-- Equity Section -->
                                                <tr>
                                                    <th colspan="2">Equity</th>
                                                </tr>
                                                <?php foreach ($equity as $item): ?>
                                                    <tr>
                                                        <td class="pl-4"><?php echo ucwords(str_replace('_', ' ', $item['type'])); ?></td>
                                                        <td class="text-right"><?php echo number_format($item['total'], 2); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <tr class="table-success">
                                                    <th>Total Equity</th>
                                                    <th class="text-right"><?php echo number_format($total_equity, 2); ?></th>
                                                </tr>

                                                <!-- Total Liabilities and Equity -->
                                                <tr class="table-primary">
                                                    <th>Total Liabilities and Equity</th>
                                                    <th class="text-right"><?php echo number_format($total_liabilities + $total_equity, 2); ?></th>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Assets Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="assetsChart"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Liabilities & Equity Distribution</h4>
                                </div>
                                <div class="card-body">
                                    <canvas id="liabilitiesEquityChart"></canvas>
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
    <script src="../res/assets/js/plugin/chart.js/chart.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            // Initialize assets chart
            new Chart(document.getElementById('assetsChart'), {
                type: 'pie',
                data: {
                    labels: ['Current Assets', 'Fixed Assets'],
                    datasets: [{
                        data: [
                            <?php echo $total_current_assets; ?>,
                            <?php echo $total_fixed_assets; ?>
                        ],
                        backgroundColor: ['#36a2eb', '#ff6384']
                    }]
                }
            });

            // Initialize liabilities & equity chart
            new Chart(document.getElementById('liabilitiesEquityChart'), {
                type: 'pie',
                data: {
                    labels: ['Current Liabilities', 'Long-term Liabilities', 'Equity'],
                    datasets: [{
                        data: [
                            <?php echo $total_current_liabilities; ?>,
                            <?php echo $total_long_term_liabilities; ?>,
                            <?php echo $total_equity; ?>
                        ],
                        backgroundColor: ['#ff6384', '#36a2eb', '#4bc0c0']
                    }]
                }
            });
        });

        function printBalanceSheet() {
            window.print();
        }
    </script>
</body>
</html>