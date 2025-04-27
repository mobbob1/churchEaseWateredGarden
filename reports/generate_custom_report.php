<?php
require_once '../includes/auth.php';
require_once '../config.php';
require_once '../vendor/autoload.php'; // For PDF generation

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $config = json_decode($_POST['report_config'], true);
    $exportType = $_POST['export_type'];
    
    // Get report data
    $data = [];
    foreach ($config['types'] as $type) {
        switch ($type) {
            case 'membership':
                $data[$type] = getMembershipData($config, $type);
                break;
            case 'attendance':
                $data[$type] = getAttendanceData($config, $type);
                break;
            case 'financial':
                $data[$type] = getFinancialData($config, $type);
                break;
            case 'events':
                $data[$type] = getEventData($config, $type);
                break;
        }
    }
    
    // Generate export file based on type
    switch ($exportType) {
        case 'pdf':
            generatePDF($data, $config);
            break;
        case 'excel':
            generateExcel($data, $config);
            break;
        case 'csv':
            generateCSV($data, $config);
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function generatePDF($data, $config) {
    // Implementation for PDF generation
    // Use a library like DOMPDF or TCPDF
}

function generateExcel($data, $config) {
    // Implementation for Excel generation
    // Use PhpSpreadsheet
}

function generateCSV($data, $config) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="custom_report_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    foreach ($data as $type => $reportData) {
        fputcsv($output, [$type . ' Report']);
        fputcsv($output, array_column($reportData['tableData']['columns'], 'title'));
        
        foreach ($reportData['tableData']['data'] as $row) {
            fputcsv($output, $row);
        }
        
        fputcsv($output, []); // Empty line between reports
    }
    
    fclose($output);
}

// Include the same data retrieval functions as in get_custom_report_data.php
?>