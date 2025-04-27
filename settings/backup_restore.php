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

// Create backup directory if it doesn't exist
$backupDir = __DIR__ . '/../backups';
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0777, true);
}

$message = '';
$messageType = '';

// Function to create database backup
function createDatabaseBackup($pdo, $backupFile) {
    try {
        $tables = [];
        
        // Get all tables
        $result = $pdo->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $output = "-- OutpouringCRM Database Backup\n";
        $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        // Process each table
        foreach ($tables as $table) {
            // Get create table syntax
            $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $output .= "\n\n" . $row[1] . ";\n\n";
            
            // Get table data
            $stmt = $pdo->query("SELECT * FROM `$table`");
            $columnCount = $stmt->columnCount();
            
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                $output .= "INSERT INTO `$table` VALUES (";
                for ($j = 0; $j < $columnCount; $j++) {
                    if ($row[$j] === null) {
                        $output .= "NULL";
                    } else {
                        $output .= "'" . str_replace("'", "''", $row[$j]) . "'";
                    }
                    if ($j < ($columnCount - 1)) {
                        $output .= ",";
                    }
                }
                $output .= ");\n";
            }
            $output .= "\n";
        }
        
        $output .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        
        // Save the backup
        file_put_contents($backupFile, $output);
        
        return true;
    } catch (Exception $e) {
        throw new Exception("Backup failed: " . $e->getMessage());
    }
}

// Handle backup request
if (isset($_POST['backup'])) {
    try {
        // Generate backup filename with timestamp
        $backupFile = $backupDir . '/backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Create backup
        if (createDatabaseBackup($pdo, $backupFile)) {
            $message = "Database backup created successfully!";
            $messageType = "success";
            
            // Log the backup action
            $logger->log(
                'backup',
                'database',
                $_SESSION['user_id'],
                "Database backup created",
                null,
                ['file' => basename($backupFile)]
            );
        }
    } catch (Exception $e) {
        $message = "Error creating backup: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Handle restore request
if (isset($_POST['restore']) && isset($_FILES['backup_file'])) {
    try {
        $uploadedFile = $_FILES['backup_file']['tmp_name'];
        $fileName = $_FILES['backup_file']['name'];
        
        // Verify file is SQL
        if (pathinfo($fileName, PATHINFO_EXTENSION) !== 'sql') {
            throw new Exception("Invalid file type. Only SQL files are allowed.");
        }
        
        // Read and execute the SQL file
        $sql = file_get_contents($uploadedFile);
        if ($sql === false) {
            throw new Exception("Unable to read backup file.");
        }
        
        // Disable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
        
        // Split and execute SQL commands
        $queries = explode(';', $sql);
        foreach ($queries as $query) {
            $query = trim($query);
            if (!empty($query)) {
                $pdo->exec($query);
            }
        }
        
        // Re-enable foreign key checks
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
        
        $message = "Database restored successfully!";
        $messageType = "success";
        
        // Log the restore action
        $logger->log(
            'restore',
            'database',
            $_SESSION['user_id'],
            "Database restored from backup",
            null,
            ['file' => $fileName]
        );
    } catch (Exception $e) {
        $message = "Error restoring backup: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Get list of existing backups
$backups = [];
if (is_dir($backupDir)) {
    $files = scandir($backupDir);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
            $backups[] = [
                'name' => $file,
                'size' => filesize($backupDir . '/' . $file),
                'date' => date('Y-m-d H:i:s', filemtime($backupDir . '/' . $file))
            ];
        }
    }
    // Sort backups by date (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Backup & Restore - OutpouringCRM</title>
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
                        <h4 class="page-title">Backup & Restore</h4>
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
                                <a href="#">Backup & Restore</a>
                            </li>
                        </ul>
                    </div>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?>" role="alert">
                            <?= htmlspecialchars($message) ?>
                        </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Create Backup</h4>
                                </div>
                                <div class="card-body">
                                    <form method="post">
                                        <p>Create a backup of your database. This will save all your data in a SQL file.</p>
                                        <button type="submit" name="backup" class="btn btn-primary">
                                            <i class="fa fa-download"></i> Create Backup
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Restore Backup</h4>
                                </div>
                                <div class="card-body">
                                    <form method="post" enctype="multipart/form-data">
                                        <div class="form-group">
                                            <label for="backup_file">Select Backup File</label>
                                            <input type="file" class="form-control" id="backup_file" name="backup_file" accept=".sql" required>
                                        </div>
                                        <button type="submit" name="restore" class="btn btn-warning" onclick="return confirm('Warning: This will overwrite your current database. Are you sure you want to proceed?');">
                                            <i class="fa fa-upload"></i> Restore Backup
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Backup History</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="backup-history" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Backup File</th>
                                                    <th>Size</th>
                                                    <th>Date Created</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($backups as $backup): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($backup['name']) ?></td>
                                                        <td><?= number_format($backup['size'] / 1024, 2) ?> KB</td>
                                                        <td><?= $backup['date'] ?></td>
                                                        <td>
                                                            <a href="../backups/<?= urlencode($backup['name']) ?>" 
                                                               class="btn btn-sm btn-primary"
                                                               download>
                                                                <i class="fa fa-download"></i> Download
                                                            </a>
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
    
    <!-- Datatables -->
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#backup-history').DataTable({
                "order": [[ 2, "desc" ]],  // Sort by date column descending
                "pageLength": 10,
                "language": {
                    "paginate": {
                        "previous": "<",
                        "next": ">"
                    }
                }
            });
        });
    </script>
</body>
</html>
