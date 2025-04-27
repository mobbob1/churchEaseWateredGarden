<?php
require_once '../includes/auth.php';
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $config = json_decode($_POST['config'], true);
    $reports = [];

    foreach ($config['types'] as $type) {
        $reportData = [];
        
        switch ($type) {
            case 'membership':
                $reportData = getMembershipData($config, $type);
                break;
            case 'attendance':
                $reportData = getAttendanceData($config, $type);
                break;
            case 'financial':
                $reportData = getFinancialData($config, $type);
                break;
            case 'events':
                $reportData = getEventData($config, $type);
                break;
        }
        
        $reports[] = $reportData;
    }

    echo json_encode($reports);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function getMembershipData($config, $type) {
    global $pdo;
    
    $startDate = $config['dateRange']['start'];
    $endDate = $config['dateRange']['end'];
    $metrics = $config['metrics'][$type];
    $filters = $config['filters'][$type];
    
    $whereClause = "WHERE date_joined BETWEEN :start_date AND :end_date";
    $params = [':start_date' => $startDate, ':end_date' => $endDate];
    
    // Apply filters
    if (!empty($filters['gender'])) {
        $whereClause .= " AND gender = :gender";
        $params[':gender'] = $filters['gender'];
    }
    
    if (!empty($filters['membership_status'])) {
        $whereClause .= " AND status = :status";
        $params[':status'] = $filters['membership_status'];
    }
    
    $data = [];
    $labels = [];
    $datasets = [];
    
    foreach ($metrics as $metric) {
        switch ($metric) {
            case 'total_members':
                $stmt = $pdo->prepare("
                    SELECT DATE_FORMAT(date_joined, '%Y-%m') as month,
                           COUNT(*) as total
                    FROM members
                    $whereClause
                    GROUP BY DATE_FORMAT(date_joined, '%Y-%m')
                    ORDER BY month
                ");
                $stmt->execute($params);
                
                while ($row = $stmt->fetch()) {
                    $labels[] = date('M Y', strtotime($row['month'] . '-01'));
                    $data[] = $row['total'];
                }
                
                $datasets[] = [
                    'label' => 'Total Members',
                    'data' => $data,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'borderWidth' => 1
                ];
                break;
                
            // Add other metric cases
        }
    }
    
    return [
        'chartData' => [
            'labels' => $labels,
            'datasets' => $datasets
        ],
        'chartOptions' => [
            'responsive' => true,
            'plugins' => [
                'title' => [
                    'display' => true,
                    'text' => 'Membership Report'
                ]
            ]
        ],
        'tableData' => [
            'columns' => [
                ['title' => 'Month'],
                ['title' => 'Total Members']
            ],
            'data' => array_map(function($label, $value) {
                return [$label, $value];
            }, $labels, $data)
        ]
    ];
}

// Similar functions for attendance, financial, and event data
function getAttendanceData($config, $type) {
    // Implementation similar to getMembershipData
}

function getFinancialData($config, $type) {
    // Implementation similar to getMembershipData
}

function getEventData($config, $type) {
    // Implementation similar to getMembershipData
}
?>