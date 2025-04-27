<?php

/* 
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/EmptyPHP.php to edit this template
 */

require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);



// Default settings with their descriptions
$default_settings = [
    'church_name' => ['Church Name', ''],
    'church_address' => ['Church Address', ''],
    'church_phone' => ['Church Contact Number', ''],
    'church_email' => ['Church Email', ''],
    'sms_sender_id' => ['SMS Sender ID', 'ID used for sending SMS messages'],
    'sms_api_key' => ['SMS API Key', 'API key for SMS service'],
    'currency_symbol' => ['Currency Symbol', '₵'],
    'date_format' => ['Date Format', 'Y-m-d'],
    'time_format' => ['Time Format', 'H:i:s'],
    'session_timeout' => ['Session Timeout (minutes)', '30'],
    'items_per_page' => ['Items Per Page', '25'],
    'backup_retention_days' => ['Backup Retention Days', '30'],
    'system_email' => ['System Email', 'For sending system notifications'],
    'maintenance_mode' => ['Maintenance Mode', '0'],
    'debug_mode' => ['Debug Mode', '0']
];

// Initialize settings if they don't exist
foreach ($default_settings as $key => $details) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO system_settings (setting_key, setting_value, setting_description) VALUES (?, ?, ?)");
    $stmt->execute([$key, $details[1], $details[0]]);
}

$message = '';
$messageType = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Update each setting
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
        }
        
        // Log the changes
        $logger->log(
            'update',
            'settings',
            $_SESSION['user_id'],
            "System settings updated",
            null,
            ['updated_keys' => array_keys($_POST['settings'])]
        );
        
        $pdo->commit();
        $message = "Settings updated successfully!";
        $messageType = "success";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error updating settings: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Get current settings
$stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
$settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>System Settings - OutpouringCRM</title>
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
                        <h4 class="page-title">System Settings</h4>
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
                                <a href="#">Settings</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">System Settings</a>
                            </li>
                        </ul>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?>" role="alert">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Configure System Settings</h4>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h4 class="card-title">Church Information</h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php foreach ($settings as $setting): ?>
                                                            <?php if (in_array($setting['setting_key'], ['church_name', 'church_address', 'church_phone', 'church_email'])): ?>
                                                                <div class="form-group">
                                                                    <label><?= htmlspecialchars($setting['setting_description']) ?></label>
                                                                    <input type="text" 
                                                                           class="form-control" 
                                                                           name="settings[<?= htmlspecialchars($setting['setting_key']) ?>]"
                                                                           value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h4 class="card-title">SMS Settings</h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php foreach ($settings as $setting): ?>
                                                            <?php if (in_array($setting['setting_key'], ['sms_sender_id', 'sms_api_key'])): ?>
                                                                <div class="form-group">
                                                                    <label><?= htmlspecialchars($setting['setting_description']) ?></label>
                                                                    <input type="<?= $setting['setting_key'] === 'sms_api_key' ? 'password' : 'text' ?>" 
                                                                           class="form-control" 
                                                                           name="settings[<?= htmlspecialchars($setting['setting_key']) ?>]"
                                                                           value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h4 class="card-title">System Preferences</h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php foreach ($settings as $setting): ?>
                                                            <?php if (in_array($setting['setting_key'], ['currency_symbol', 'date_format', 'time_format', 'items_per_page'])): ?>
                                                                <div class="form-group">
                                                                    <label><?= htmlspecialchars($setting['setting_description']) ?></label>
                                                                    <input type="text" 
                                                                           class="form-control" 
                                                                           name="settings[<?= htmlspecialchars($setting['setting_key']) ?>]"
                                                                           value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="card">
                                                    <div class="card-header">
                                                        <h4 class="card-title">System Configuration</h4>
                                                    </div>
                                                    <div class="card-body">
                                                        <?php foreach ($settings as $setting): ?>
                                                            <?php if (in_array($setting['setting_key'], ['session_timeout', 'backup_retention_days', 'maintenance_mode', 'debug_mode'])): ?>
                                                                <div class="form-group">
                                                                    <label><?= htmlspecialchars($setting['setting_description']) ?></label>
                                                                    <?php if (in_array($setting['setting_key'], ['maintenance_mode', 'debug_mode'])): ?>
                                                                        <select class="form-control" name="settings[<?= htmlspecialchars($setting['setting_key']) ?>]">
                                                                            <option value="0" <?= $setting['setting_value'] == '0' ? 'selected' : '' ?>>Disabled</option>
                                                                            <option value="1" <?= $setting['setting_value'] == '1' ? 'selected' : '' ?>>Enabled</option>
                                                                        </select>
                                                                    <?php else: ?>
                                                                        <input type="number" 
                                                                               class="form-control" 
                                                                               name="settings[<?= htmlspecialchars($setting['setting_key']) ?>]"
                                                                               value="<?= htmlspecialchars($setting['setting_value']) ?>">
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="text-right mt-3">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fa fa-save"></i> Save Settings
                                            </button>
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
    
    <!-- jQuery UI -->
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
    
    <!-- jQuery Scrollbar -->
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Add confirmation before form submission
            $('form').on('submit', function(e) {
                if (!confirm('Are you sure you want to update these settings?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>
