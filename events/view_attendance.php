<?php
// attendance/view_attendance.php
require_once '../includes/auth.php';
require_once '../vendor/autoload.php'; // For PhpSpreadsheet and TCPDF

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Handle exports
if (isset($_GET['export'])) {
    $selected_event = $_GET['event_id'] ?? '';
    $selected_date = $_GET['date'] ?? date('Y-m-d');
    
    // Prepare the query
    $where_clause = "WHERE DATE(a.checkin_time) = ?";
    $params = [$selected_date];
    
    if (!empty($selected_event)) {
        $where_clause .= " AND a.event_id = ?";
        $params[] = $selected_event;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            m.name as member_name,
            m.contact_number_1 as phone,
            e.event_name,
            a.checkin_time 
        FROM attendance a 
        JOIN members m ON a.member_id = m.id 
        JOIN events e ON a.event_id = e.id 
        $where_clause
        ORDER BY a.checkin_time DESC
    ");
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    switch($_GET['export']) {
        case 'csv':
            // CSV Export
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="attendance_' . $selected_date . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Member Name', 'Phone Number', 'Event', 'Check-in Time']);
            foreach($data as $row) {
                fputcsv($output, [
                    $row['member_name'],
                    $row['phone'],
                    $row['event_name'],
                    date('Y-m-d h:i A', strtotime($row['checkin_time']))
                ]);
            }
            fclose($output);
            exit();
            break;

        case 'excel':
            // Excel Export
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Headers
            $sheet->setCellValue('A1', 'Member Name');
            $sheet->setCellValue('B1', 'Phone Number');
            $sheet->setCellValue('C1', 'Event');
            $sheet->setCellValue('D1', 'Check-in Time');
            
            // Style the header
            $sheet->getStyle('A1:D1')->getFont()->setBold(true);
            
            // Data
            $row = 2;
            foreach($data as $record) {
                $sheet->setCellValue('A' . $row, $record['member_name']);
                $sheet->setCellValue('B' . $row, $record['phone']);
                $sheet->setCellValue('C' . $row, $record['event_name']);
                $sheet->setCellValue('D' . $row, date('Y-m-d h:i A', strtotime($record['checkin_time'])));
                $row++;
            }
            
            // Auto-size columns
            foreach(range('A','D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="attendance_' . $selected_date . '.xlsx"');
            header('Cache-Control: max-age=0');
            
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit();
            break;

        case 'pdf':
            // PDF Export
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('OutpouringCRM');
            $pdf->SetTitle('Attendance Report - ' . $selected_date);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            
            // Add a page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('helvetica', '', 11);
            
            // Title
            $pdf->Cell(0, 10, 'Attendance Report - ' . $selected_date, 0, 1, 'C');
            $pdf->Ln(5);
            
            // Table headers
            $headers = ['Member Name', 'Phone Number', 'Event', 'Check-in Time'];
            $pdf->SetFillColor(240, 240, 240);
            $pdf->SetFont('helvetica', 'B', 10);
            
            // Calculate column widths
            $w = array(50, 40, 50, 40);
            
            foreach($headers as $i => $header) {
                $pdf->Cell($w[$i], 7, $header, 1, 0, 'C', true);
            }
            $pdf->Ln();
            
            // Data rows
            $pdf->SetFont('helvetica', '', 10);
            foreach($data as $row) {
                $pdf->Cell($w[0], 6, $row['member_name'], 1);
                $pdf->Cell($w[1], 6, $row['phone'], 1);
                $pdf->Cell($w[2], 6, $row['event_name'], 1);
                $pdf->Cell($w[3], 6, date('Y-m-d h:i A', strtotime($row['checkin_time'])), 1);
                $pdf->Ln();
            }
            
            // Output PDF
            $pdf->Output('attendance_' . $selected_date . '.pdf', 'D');
            exit();
            break;
    }
}

// Get selected event and date filters
$selected_event = $_GET['event_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Get all events for filter dropdown
try {
    $stmt = $pdo->prepare("SELECT id, event_name, event_date FROM events ORDER BY event_date DESC");
    $stmt->execute();
    $events = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Attendance - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link rel="stylesheet" href="../res/assets/css/datepicker.css">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">View Attendance</h4>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Attendance Records</div>
                                    <div class="card-category">View and filter attendance records by event and date</div>
                                </div>
                                <div class="card-body">
                                    <!-- Filters -->
                                    <form method="GET" class="mb-4">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Select Event</label>
                                                    <select class="form-control" name="event_id">
                                                        <option value="">All Events</option>
                                                        <?php foreach ($events as $event): ?>
                                                            <option value="<?php echo $event['id']; ?>" 
                                                                <?php echo ($selected_event == $event['id']) ? 'selected' : ''; ?> >
                                                                <?php echo htmlspecialchars($event['event_name']) . ' (' . date('Y-m-d', strtotime($event['event_date'])) . ')'; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Select Date</label>
                                                    <input type="date" class="form-control" name="date" 
                                                           value="<?php echo $selected_date; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label>Export Options</label>
                                                    <div class="btn-group d-block">
                                                        <button type="button" class="btn btn-success dropdown-toggle btn-block" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <i class="fas fa-file-export"></i> Export
                                                        </button>
                                                        <div class="dropdown-menu">
                                                            <button type="submit" name="export" value="csv" class="dropdown-item">
                                                                <i class="fas fa-file-csv"></i> Export as CSV
                                                            </button>
                                                            <button type="submit" name="export" value="excel" class="dropdown-item">
                                                                <i class="fas fa-file-excel"></i> Export as Excel
                                                            </button>
                                                            <button type="submit" name="export" value="pdf" class="dropdown-item">
                                                                <i class="fas fa-file-pdf"></i> Export as PDF
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="form-group">
                                                    <label>&nbsp;</label>
                                                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Attendance Stats -->
                                    <?php
                                    try {
                                        $where_clause = "WHERE DATE(a.checkin_time) = ?";
                                        $params = [$selected_date];
                                        
                                        if (!empty($selected_event)) {
                                            $where_clause .= " AND a.event_id = ?";
                                            $params[] = $selected_event;
                                        }
                                        
                                        $stmt = $pdo->prepare("
                                            SELECT COUNT(DISTINCT a.member_id) as total_attendees
                                            FROM attendance a
                                            $where_clause
                                        ");
                                        $stmt->execute($params);
                                        $stats = $stmt->fetch();
                                        
                                        echo "<div class='alert alert-info'>
                                            Total Attendees: " . $stats['total_attendees'] . "
                                        </div>";
                                    } catch (PDOException $e) {
                                        error_log($e->getMessage());
                                    }
                                    ?>

                                    <!-- Attendance Table -->
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Member</th>
                                                    <th>Phone Number</th>
                                                    <th>Event</th>
                                                    <th>Check-in Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                try {
                                                    $where_clause = "WHERE DATE(a.checkin_time) = ?";
                                                    $params = [$selected_date];
                                                    
                                                    if (!empty($selected_event)) {
                                                        $where_clause .= " AND a.event_id = ?";
                                                        $params[] = $selected_event;
                                                    }
                                                    
                                                    $stmt = $pdo->prepare("
                                                        SELECT 
                                                            m.name as member_name,
                                                            m.contact_number_1 as phone,
                                                            e.event_name,
                                                            a.checkin_time 
                                                        FROM attendance a 
                                                        JOIN members m ON a.member_id = m.id 
                                                        JOIN events e ON a.event_id = e.id 
                                                        $where_clause
                                                        ORDER BY a.checkin_time DESC
                                                    ");
                                                    $stmt->execute($params);
                                                    
                                                    while ($row = $stmt->fetch()) {
                                                        echo "<tr>";
                                                        echo "<td>" . htmlspecialchars($row['member_name']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                                        echo "<td>" . htmlspecialchars($row['event_name']) . "</td>";
                                                        echo "<td>" . date('h:i A', strtotime($row['checkin_time'])) . "</td>";
                                                        echo "</tr>";
                                                    }
                                                } catch (PDOException $e) {
                                                    error_log($e->getMessage());
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
            <?php include '../res/footer.php'; ?>
        </div>
    </div>
    
    <!--   Core JS Files   -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>