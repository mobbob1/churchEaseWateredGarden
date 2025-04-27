<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Handle export requests
if (isset($_POST['export'])) {
    $format = $_POST['format'];
    
    // Get messages data with member details
    $stmt = $pdo->prepare("
        SELECT 
            m.first_name,
            m.surname,
            m.contact_number_1,
            ms.message as message_content,
            ms.message_type,
            ms.status,
            ms.message_title,
            ms.sent_time,
            ms.api_response,
            ms.recipient_group,
            ms.recipient_count
        FROM messages ms
        LEFT JOIN members m ON ms.recipient_id = m.id
        ORDER BY ms.sent_time DESC
    ");
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    switch ($format) {
        case 'csv':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="message_history.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, array_keys($messages[0])); // Header row
            foreach ($messages as $row) {
                fputcsv($output, $row);
            }
            fclose($output);
            exit;
            
        case 'excel':
            require_once '../vendor/autoload.php';
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Headers
            $columns = array_keys($messages[0]);
            foreach ($columns as $index => $column) {
                $sheet->setCellValueByColumnAndRow($index + 1, 1, $column);
            }
            
            // Data
            foreach ($messages as $rowIndex => $row) {
                foreach ($columns as $columnIndex => $column) {
                    $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 2, $row[$column]);
                }
            }
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="message_history.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
            $writer->save('php://output');
            exit;
            
        case 'pdf':
            require_once '../vendor/autoload.php';
            
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetTitle('Message History');
            $pdf->SetHeaderData('', 0, 'Message History', '');
            $pdf->setHeaderFont(Array('helvetica', '', 12));
            $pdf->setFooterFont(Array('helvetica', '', 10));
            $pdf->SetDefaultMonospacedFont('helvetica');
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(TRUE, 15);
            
            $pdf->AddPage();
            
            // Create the table content
            $html = '<table border="1" cellpadding="4">
                <tr>';
            foreach (array_keys($messages[0]) as $header) {
                $html .= '<th>' . htmlspecialchars($header) . '</th>';
            }
            $html .= '</tr>';
            
            foreach ($messages as $row) {
                $html .= '<tr>';
                foreach ($row as $cell) {
                    $html .= '<td>' . htmlspecialchars($cell) . '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
            
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output('message_history.pdf', 'D');
            exit;
    }
}

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';
$searchType = isset($_GET['search_type']) ? $_GET['search_type'] : 'all';

// Base query
$query = "
    SELECT 
        m.first_name,
        m.surname,
        m.contact_number_1,
        ms.message as message_content,
        ms.message_type,
        ms.status,
        ms.message_title,
        ms.sent_time,
        ms.api_response,
        ms.recipient_group,
        ms.recipient_count
    FROM messages ms
    LEFT JOIN members m ON ms.recipient_id = m.id
    WHERE 1=1
";

// Add search conditions based on search type
if ($search !== '') {
    switch ($searchType) {
        case 'name':
            $query .= " AND (m.first_name LIKE :search OR m.surname LIKE :search)";
            break;
        case 'phone':
            $query .= " AND m.contact_number_1 LIKE :search";
            break;
        case 'message':
            $query .= " AND (ms.message LIKE :search OR ms.message_title LIKE :search)";
            break;
        case 'status':
            $query .= " AND ms.status LIKE :search";
            break;
        case 'type':
            $query .= " AND ms.message_type LIKE :search";
            break;
        default:
            $query .= " AND (
                m.first_name LIKE :search OR 
                m.surname LIKE :search OR 
                m.contact_number_1 LIKE :search OR 
                ms.message LIKE :search OR 
                ms.message_title LIKE :search OR 
                ms.status LIKE :search OR 
                ms.message_type LIKE :search
            )";
    }
}

$query .= " ORDER BY ms.sent_time DESC";

$stmt = $pdo->prepare($query);

if ($search !== '') {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}

$stmt->execute();
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Message History - ChurchEaseSuperb</title>
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
    <link rel="stylesheet" href="../res/assets/css/dataTables.bootstrap4.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Message History</h4>
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
                                <a href="#">Communication</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Message History</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Message History</h4>
                                        <div class="ml-auto">
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="export" value="1">
                                                <div class="btn-group">
                                                    <button type="submit" name="format" value="csv" class="btn btn-primary btn-sm">
                                                        <i class="fa fa-file-csv"></i> CSV
                                                    </button>
                                                    <button type="submit" name="format" value="excel" class="btn btn-success btn-sm">
                                                        <i class="fa fa-file-excel"></i> Excel
                                                    </button>
                                                    <button type="submit" name="format" value="pdf" class="btn btn-danger btn-sm">
                                                        <i class="fa fa-file-pdf"></i> PDF
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- Search Form -->
                                    <form method="get" class="mb-4">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
                                            </div>
                                            <div class="col-md-3">
                                                <select name="search_type" class="form-control">
                                                    <option value="all" <?= $searchType === 'all' ? 'selected' : '' ?>>All Fields</option>
                                                    <option value="name" <?= $searchType === 'name' ? 'selected' : '' ?>>Name</option>
                                                    <option value="phone" <?= $searchType === 'phone' ? 'selected' : '' ?>>Phone</option>
                                                    <option value="message" <?= $searchType === 'message' ? 'selected' : '' ?>>Message</option>
                                                    <option value="status" <?= $searchType === 'status' ? 'selected' : '' ?>>Status</option>
                                                    <option value="type" <?= $searchType === 'type' ? 'selected' : '' ?>>Message Type</option>
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <button type="submit" class="btn btn-primary">
                                                    <i class="fa fa-search"></i> Search
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="table-responsive">
                                        <table id="message-history" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Member Name</th>
                                                    <th>Contact</th>
                                                    <th>Message Title</th>
                                                    <th>Message</th>
                                                    <th>Type</th>
                                                    <th>Group</th>
                                                    <th>Recipients</th>
                                                    <th>Status</th>
                                                    <th>Date Sent</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($messages as $message): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($message['first_name'] . ' ' . $message['surname']) ?></td>
                                                    <td><?= htmlspecialchars($message['contact_number_1']) ?></td>
                                                    <td><?= htmlspecialchars($message['message_title']) ?></td>
                                                    <td><?= htmlspecialchars($message['message_content']) ?></td>
                                                    <td><?= htmlspecialchars($message['message_type']) ?></td>
                                                    <td><?= htmlspecialchars($message['recipient_group']) ?></td>
                                                    <td><?= htmlspecialchars($message['recipient_count']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $message['status'] === 'sent' ? 'success' : ($message['status'] === 'pending' ? 'warning' : 'danger') ?>">
                                                            <?= htmlspecialchars($message['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($message['sent_time']) ?></td>
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

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    
    <!-- jQuery UI -->
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    
    <!-- jQuery Scrollbar -->
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    
    <!-- DataTables -->
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#message-history').DataTable({
                "order": [[8, "desc"]], // Sort by date sent by default
                "pageLength": 25,
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