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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_asset':
                $stmt = $pdo->prepare("
                    INSERT INTO assets (
                        name, type, category, purchase_date, purchase_amount,
                        currency, current_value, depreciation_rate, description
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['category'],
                    $_POST['purchase_date'],
                    $_POST['purchase_amount'],
                    $_POST['currency'],
                    $_POST['purchase_amount'], // Initially same as purchase amount
                    $_POST['depreciation_rate'],
                    $_POST['description']
                ]);

                // Record the purchase transaction
                $assetId = $pdo->lastInsertId();
                $stmt = $pdo->prepare("
                    INSERT INTO asset_transactions (
                        asset_id, transaction_type, amount, currency,
                        transaction_date, description
                    ) VALUES (?, 'purchase', ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $assetId,
                    $_POST['purchase_amount'],
                    $_POST['currency'],
                    $_POST['purchase_date'],
                    'Initial purchase'
                ]);
                break;

            case 'update_asset':
                $stmt = $pdo->prepare("
                    UPDATE assets SET
                        name = ?,
                        type = ?,
                        category = ?,
                        current_value = ?,
                        depreciation_rate = ?,
                        description = ?,
                        status = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['type'],
                    $_POST['category'],
                    $_POST['current_value'],
                    $_POST['depreciation_rate'],
                    $_POST['description'],
                    $_POST['status'],
                    $_POST['asset_id']
                ]);
                break;

            case 'record_depreciation':
                // Record depreciation transaction
                $stmt = $pdo->prepare("
                    INSERT INTO asset_transactions (
                        asset_id, transaction_type, amount, currency,
                        transaction_date, description
                    ) VALUES (?, 'depreciation', ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_POST['asset_id'],
                    $_POST['depreciation_amount'],
                    $_POST['currency'],
                    $_POST['transaction_date'],
                    'Periodic depreciation'
                ]);

                // Update current value in assets table
                $stmt = $pdo->prepare("
                    UPDATE assets SET
                        current_value = current_value - ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $_POST['depreciation_amount'],
                    $_POST['asset_id']
                ]);
                break;
        }
    }
}

// Fetch all assets
$assets = $pdo->query("
    SELECT * FROM assets 
    ORDER BY type, category, name
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Asset Management - ChurchEaseSuperb</title>
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
                        <h4 class="page-title">Asset Management</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Assets</h4>
                                        <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#addAssetModal">
                                            <i class="fa fa-plus"></i> Add Asset
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="assets-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Name</th>
                                                    <th>Type</th>
                                                    <th>Category</th>
                                                    <th>Purchase Date</th>
                                                    <th>Purchase Amount</th>
                                                    <th>Current Value</th>
                                                    <th>Currency</th>
                                                    <th>Status</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assets as $asset): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($asset['name']); ?></td>
                                                        <td><?php echo ucfirst($asset['type']); ?></td>
                                                        <td><?php echo ucfirst($asset['category']); ?></td>
                                                        <td><?php echo date('Y-m-d', strtotime($asset['purchase_date'])); ?></td>
                                                        <td><?php echo number_format($asset['purchase_amount'], 2); ?></td>
                                                        <td><?php echo number_format($asset['current_value'], 2); ?></td>
                                                        <td><?php echo $asset['currency']; ?></td>
                                                        <td><?php echo ucfirst($asset['status']); ?></td>
                                                        <td>
                                                            <div class="btn-group">
                                                                <button type="button" class="btn btn-primary btn-sm" 
                                                                        onclick="editAsset(<?php echo $asset['id']; ?>)">
                                                                    Edit
                                                                </button>
                                                                <button type="button" class="btn btn-info btn-sm"
                                                                        onclick="recordDepreciation(<?php echo $asset['id']; ?>)">
                                                                    Depreciation
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
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

    <!-- Add Asset Modal -->
    <div class="modal fade" id="addAssetModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add_asset">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Asset</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Asset Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Type</label>
                                    <select class="form-control" name="type" required>
                                        <option value="current">Current Asset</option>
                                        <option value="fixed">Fixed Asset</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category</label>
                                    <input type="text" class="form-control" name="category" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Purchase Date</label>
                                    <input type="date" class="form-control" name="purchase_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Purchase Amount</label>
                                    <input type="number" step="0.01" class="form-control" name="purchase_amount" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Currency</label>
                                    <select class="form-control" name="currency" required>
                                        <option value="GHS">GHS</option>
                                        <option value="USD">USD</option>
                                        <option value="EUR">EUR</option>
                                        <option value="GBP">GBP</option>
                                        <option value="NGN">NGN</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Depreciation Rate (%)</label>
                                    <input type="number" step="0.01" class="form-control" name="depreciation_rate">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Asset Modal -->
    <div class="modal fade" id="editAssetModal" tabindex="-1" role="dialog">
        <!-- Similar structure to Add Asset Modal, but with pre-filled values -->
    </div>

    <!-- Record Depreciation Modal -->
    <div class="modal fade" id="depreciationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="record_depreciation">
                    <input type="hidden" name="asset_id" id="depreciation_asset_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Record Depreciation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Depreciation Amount</label>
                            <input type="number" step="0.01" class="form-control" name="depreciation_amount" required>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" class="form-control" name="transaction_date" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Record Depreciation</button>
                    </div>
                </form>
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
    
    <script>
        $(document).ready(function() {
            $('#assets-table').DataTable({
                "pageLength": 25,
                "order": [[1, "asc"]]
            });
        });

        function editAsset(assetId) {
            // Implement edit functionality
        }

        function recordDepreciation(assetId) {
            $('#depreciation_asset_id').val(assetId);
            $('#depreciationModal').modal('show');
        }
    </script>
</body>
</html>