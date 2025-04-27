<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'executive_admin_1'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// Handle export
if (isset($_POST['export']) && $_POST['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="bible_class_members_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Bible Class', 'Total Members', 'Male', 'Female']);
    
    $stmt = $pdo->query("SELECT bc.class_name,
                         COUNT(m.id) as total_members,
                         SUM(CASE WHEN m.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
                         SUM(CASE WHEN m.gender = 'Female' THEN 1 ELSE 0 END) as female_count
                         FROM bible_classes bc 
                         LEFT JOIN members m ON bc.id = m.bible_class_id 
                         GROUP BY bc.id, bc.class_name
                         ORDER BY bc.class_name");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['class_name'],
            $row['total_members'],
            $row['male_count'],
            $row['female_count']
        ]);
    }
    fclose($output);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Bible Class Members - ChurchEaseSuperb</title>
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
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.css"/>
    
    <style>
        .dt-buttons { display: none; }
        .dataTables_filter { display: none; }
        .filter-select {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
            border: 1px solid #eee;
        }
        .export-buttons .dropdown-item {
            cursor: pointer;
        }
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
                        <h4 class="page-title">Bible Class Members</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../index.php">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Bible Class Members</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Search</label>
                                                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                                            </div>
                                        </div>
                                        <div class="col-md-6"></div>
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Export</label>
                                                <div class="dropdown">
                                                    <button class="btn btn-secondary dropdown-toggle w-100" type="button" id="exportDropdown" data-toggle="dropdown">
                                                        Export Data
                                                    </button>
                                                    <div class="dropdown-menu w-100">
                                                        <form method="POST" class="export-buttons">
                                                            <button type="submit" name="export" value="csv" class="dropdown-item">Export to CSV</button>
                                                        </form>
                                                        <a class="dropdown-item" onclick="exportToExcel()">Export to Excel</a>
                                                        <a class="dropdown-item" onclick="exportToPDF()">Export to PDF</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="bible-class-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Bible Class</th>
                                                    <th>Total Members</th>
                                                    <th>Male</th>
                                                    <th>Female</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $counter = 1;
                                                $stmt = $pdo->query("
                                                    SELECT bc.id, bc.class_name,
                                                    COUNT(m.id) as total_members,
                                                    SUM(CASE WHEN m.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
                                                    SUM(CASE WHEN m.gender = 'Female' THEN 1 ELSE 0 END) as female_count
                                                    FROM bible_classes bc 
                                                    LEFT JOIN members m ON bc.id = m.bible_class_id 
                                                    GROUP BY bc.id, bc.class_name
                                                    ORDER BY bc.class_name
                                                ");
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>{$counter}</td>";
                                                    echo "<td>" . htmlspecialchars($row['class_name']) . "</td>";
                                                    echo "<td>" . $row['total_members'] . "</td>";
                                                    echo "<td>" . $row['male_count'] . "</td>";
                                                    echo "<td>" . $row['female_count'] . "</td>";
                                                    echo "<td>
                                                            <button class='btn btn-primary btn-sm' onclick='viewMembers({$row['id']})'>
                                                                View Members
                                                            </button>
                                                          </td>";
                                                    echo "</tr>";
                                                    $counter++;
                                                }
                                                ?>
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

    <!-- Members Modal -->
    <div class="modal fade" id="membersModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Class Members</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="membersList">
                </div>
            </div>
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <!-- DataTables -->
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
    
    <!-- Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    
    <script>
        $(document).ready(function() {
            var table = $('#bible-class-table').DataTable({
                "order": [[1, "asc"]],
                "pageLength": 25,
                "dom": 'Bfrtip',
                "buttons": ['copy', 'csv', 'excel', 'pdf']
            });

            $('#searchInput').keyup(function(){
                table.search($(this).val()).draw();
            });
        });

        function viewMembers(classId) {
            $.ajax({
                url: 'get_class_members.php',
                type: 'POST',
                data: { class_id: classId },
                success: function(response) {
                    $('#membersList').html(response);
                    $('#membersModal').modal('show');
                }
            });
        }

        function exportToExcel() {
            $('.buttons-excel').click();
        }

        function exportToPDF() {
            $('.buttons-pdf').click();
        }
    </script>
</body>
</html>