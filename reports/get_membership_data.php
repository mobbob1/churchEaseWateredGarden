<?php
require_once '../includes/auth.php';
require_once '../config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-1 year'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');

    // Get monthly membership data
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(date_joined, '%Y-%m') as month,
            COUNT(*) as new_members,
            (
                SELECT COUNT(*) 
                FROM members 
                WHERE date_joined <= LAST_DAY(m.date_joined)
            ) as total_members
        FROM members m
        WHERE date_joined BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(date_joined, '%Y-%m')
        ORDER BY month ASC
    ");

    $stmt->execute([$startDate, $endDate]);
    
    $labels = [];
    $values = [];
    $totals = [];
    $growthRates = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $values[] = (int)$row['new_members'];
        $totals[] = (int)$row['total_members'];
        
        // Calculate growth rate
        $previousTotal = end($totals) ?: 0;
        $growthRate = $previousTotal > 0 ? 
            (($row['total_members'] - $previousTotal) / $previousTotal * 100) : 0;
        $growthRates[] = round($growthRate, 2);
    }

    // If no data found for the period, provide empty months
    if (empty($labels)) {
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        $interval = new DateInterval('P1M');
        $dateRange = new DatePeriod($startDateTime, $interval, $endDateTime);

        foreach ($dateRange as $date) {
            $labels[] = $date->format('M Y');
            $values[] = 0;
            $totals[] = 0;
            $growthRates[] = 0;
        }
    }

    // Return data for charts
    echo json_encode([
        'labels' => $labels,
        'values' => $values,
        'totals' => $totals,
        'growthRates' => $growthRates,
        'success' => true
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'success' => false
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage(),
        'success' => false
    ]);
}
?>