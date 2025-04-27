<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'executive_admin_1'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

use PhpOffice\PhpSpreadsheet\IOFactory;


$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file'])) {
    $allowedFileType = [
        'application/vnd.ms-excel',
        'text/xls',
        'text/xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];

    if (in_array($_FILES["excel_file"]["type"], $allowedFileType)) {
        $targetPath = 'uploads/' . $_FILES['excel_file']['name'];
        
        // Create uploads directory if it doesn't exist
        if (!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        move_uploaded_file($_FILES['excel_file']['tmp_name'], $targetPath);

        try {
            $spreadsheet = IOFactory::load($targetPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $highestRow = $worksheet->getHighestRow();

            // Arrays to store data and check duplicates
            $excelData = [];
            $duplicatePhones = [];
            $existingPhones = [];

            // First, get all existing phone numbers from database
            $stmt = $pdo->query("SELECT contact_number_1 FROM members");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingPhones[] = $row['contact_number_1'];
            }

            // Read all data from Excel and check for duplicates
            for ($row = 2; $row <= $highestRow; $row++) {
                $name = trim($worksheet->getCell('A' . $row)->getValue());
                $phone = trim($worksheet->getCell('B' . $row)->getValue());

                if (!empty($phone)) {
                    // Check if phone exists in database
                    if (in_array($phone, $existingPhones)) {
                        $duplicatePhones[] = [
                            'row' => $row,
                            'name' => $name,
                            'phone' => $phone,
                            'type' => 'database'
                        ];
                        continue;
                    }

                    // Check if phone is duplicate in Excel
                    foreach ($excelData as $index => $data) {
                        if ($data['phone'] === $phone) {
                            $duplicatePhones[] = [
                                'row' => $row,
                                'name' => $name,
                                'phone' => $phone,
                                'type' => 'excel',
                                'duplicate_with_row' => $index + 2
                            ];
                            break;
                        }
                    }

                    $excelData[] = [
                        'name' => $name,
                        'phone' => $phone
                    ];
                }
            }

            // If there are duplicates, show error and don't process the import
            if (!empty($duplicatePhones)) {
                $message = "<div class='alert alert-danger'>
                            <h4>Found Duplicate Phone Numbers</h4>
                            <p>Please correct the following duplicates and try again:</p>
                            <table class='table table-bordered'>
                                <thead>
                                    <tr>
                                        <th>Excel Row</th>
                                        <th>Name</th>
                                        <th>Phone Number</th>
                                        <th>Duplicate Type</th>
                                    </tr>
                                </thead>
                                <tbody>";
                
                foreach ($duplicatePhones as $duplicate) {
                    $duplicateInfo = $duplicate['type'] === 'excel' 
                        ? "Duplicate with row {$duplicate['duplicate_with_row']}" 
                        : "Already exists in database";
                    
                    $message .= "<tr>
                                    <td>{$duplicate['row']}</td>
                                    <td>{$duplicate['name']}</td>
                                    <td>{$duplicate['phone']}</td>
                                    <td>{$duplicateInfo}</td>
                                </tr>";
                }
                
                $message .= "</tbody></table></div>";
            } else {
                // No duplicates found, proceed with import
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO members (name, contact_number_1, date_joined) 
                                     VALUES (?, ?, NOW())");

                $successCount = 0;
                $errorCount = 0;

                foreach ($excelData as $data) {
                    try {
                        $stmt->execute([$data['name'], $data['phone']]);
                        $successCount++;
                    } catch (PDOException $e) {
                        $errorCount++;
                    }
                }

                $pdo->commit();
                
                $message = "<div class='alert alert-success'>
                            Successfully imported $successCount members. Failed to import $errorCount members.
                           </div>";
            }

            // Delete uploaded file
            unlink($targetPath);

        } catch (Exception $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Invalid File Type. Upload Excel File.</div>";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Upload Members - ChurchEaseSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../loginres/images/favicon/favicon.ico" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="../loginres/images/favicon/site.webmanifest">

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
                        <h4 class="page-title">Upload Members</h4>
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
                                <a href="#">Members</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Upload Members</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Upload Members from Excel</h4>
                                        <a href="template/members_template.xlsx" class="btn btn-primary btn-round ml-auto">
                                            <i class="fa fa-download"></i>
                                            Download Template
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if(!empty($message)) echo $message; ?>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <form method="POST" enctype="multipart/form-data">
                                                <div class="form-group">
                                                    <label>Upload Excel File</label>
                                                    <input type="file" class="form-control" name="excel_file" accept=".xls,.xlsx" required>
                                                    <small class="form-text text-muted">
                                                        Please use the template file. The Excel file should have the following columns:
                                                        Name (Column A), Phone Number (Column B)
                                                    </small>
                                                </div>
                                                <button type="submit" class="btn btn-primary">Upload and Process</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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