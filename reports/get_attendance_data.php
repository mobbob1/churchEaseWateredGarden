<?php
require_once '../includes/auth.php';
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-3 months'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(a.attendance_date, '%Y-%U') as week,
            COUNT(DISTINCT a.member_id) as total_attendance,
            SUM(CASE WHEN m.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN m.gender = 'Female' THEN 1 ELSE 0 END) as female_count,
            AVG(TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE())) as avg_age
        FROM attendance a
        LEFT JOIN members m ON a.member_id = m.id
        WHERE a.attendance_date BETWEEN ? AND ?
        GROUP BY DATE_FORMAT(a.attendance_date, '%Y-%U')
        ORDER BY week ASC
    ");

    $stmt->execute([$startDate, $endDate]);
    
    $labels = [];
    $values = [];
    $tableData = [];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $weekStart = date('M d', strtotime($row['week'] . ' weeks'));
        $labels[] = "Week of " . $weekStart;
        $values[] = (int)$row['total_attendance'];
        
        $tableData[] = [
            $weekStart,
            $row['total_attendance'],
            $row['male_count'],
            $row['female_count'],
            round($row['avg_age'], 1)
        ];
    }

    echo json_encode([
        'labels' => $labels,
        'values' => $values,
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