<?php
require_once '../includes/auth.php';
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    $startDate = $_POST['start_date'] ?? date('Y-m-d', strtotime('-6 months'));
    $endDate = $_POST['end_date'] ?? date('Y-m-d');

    $stmt = $pdo->prepare("
        SELECT 
            e.event_name,
            e.event_date,
            COUNT(DISTINCT ep.member_id) as total_participants,
            SUM(CASE WHEN m.gender = 'Male' THEN 1 ELSE 0 END) as male_count,
            SUM(CASE WHEN m.gender = 'Female' THEN 1 ELSE 0 END) as female_count,
            AVG(TIMESTAMPDIFF(YEAR, m.date_of_birth, CURDATE())) as avg_age
        FROM events e
        LEFT JOIN event_participation ep ON e.id = ep.event_id
        LEFT JOIN members m ON ep.member_id = m.id
        WHERE e.event_date BETWEEN ? AND ?
        GROUP BY e.id, e.event_name, e.event_date
        ORDER BY e.event_date DESC
    ");

    $stmt->execute([$startDate, $endDate]);
    
    $labels = [];
    $participants = [];
    $tableData = [];
    $totalMale = 0;
    $totalFemale = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $labels[] = $row['event_name'];
        $participants[] = (int)$row['total_participants'];
        $totalMale += (int)$row['male_count'];
        $totalFemale += (int)$row['female_count'];
        
        $tableData[] = [
            $row['event_name'],
            date('Y-m-d', strtotime($row['event_date'])),
            $row['total_participants'],
            $row['male_count'],
            $row['female_count'],
            round($row['avg_age'], 1)
        ];
    }

    echo json_encode([
        'labels' => $labels,
        'participants' => $participants,
        'totalMale' => $totalMale,
        'totalFemale' => $totalFemale,
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