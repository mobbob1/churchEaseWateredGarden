<?php
require_once '../includes/auth.php';
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-1 year'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(transaction_date, '%Y-%m') as month,
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expenses,
            SUM(CASE WHEN category = 'tithe' THEN amount ELSE 0 END) as tithes,
            SUM(CASE WHEN category = 'offering' THEN amount ELSE 0 END) as offerings,
            SUM(CASE WHEN category = 'other' AND type = 'income' THEN amount ELSE 0 END) as other_income
        FROM financial_transactions
        WHERE transaction_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(transaction_date, '%Y-%m')
        ORDER BY month ASC
    ");

    $stmt->execute([$startDate, $endDate]);
    
    $labels = [];
    $income = [];
    $expenses = [];
    $tableData = [];
    $totalTithes = 0;
    $totalOfferings = 0;
    $totalOtherIncome = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = date('M Y', strtotime($row['month'] . '-01'));
        $income[] = (float)$row['income'];
        $expenses[] = (float)$row['expenses'];
        $net = $row['income'] - $row['expenses'];
        
        $totalTithes += (float)$row['tithes'];
        $totalOfferings += (float)$row['offerings'];
        $totalOtherIncome += (float)$row['other_income'];
        
        $tableData[] = [
            date('M Y', strtotime($row['month'] . '-01')),
            number_format($row['income'], 2),
            number_format($row['expenses'], 2),
            number_format($net, 2),
            number_format($row['tithes'], 2),
            number_format($row['offerings'], 2),
            number_format($row['other_income'], 2)
        ];
    }

    echo json_encode([
        'labels' => $labels,
        'income' => $income,
        'expenses' => $expenses,
        'totalTithes' => $totalTithes,
        'totalOfferings' => $totalOfferings,
        'totalOtherIncome' => $totalOtherIncome,
        'tableData' => $tableData,
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