<?php
require_once '../config.php';

if (isset($_POST['class_id'])) {
    $classId = $_POST['class_id'];
    
    try {
        // Get the class name first
        $stmt = $pdo->prepare("SELECT class_name FROM bible_classes WHERE id = ?");
        $stmt->execute([$classId]);
        $className = $stmt->fetch()['class_name'];
        
        // Get members of this class
        $stmt = $pdo->prepare("
            SELECT m.name, m.gender, m.contact_number_1 as phone, m.email,
                   mt.type_name as member_type
            FROM members m
            LEFT JOIN member_types mt ON m.member_type_id = mt.id
            WHERE m.bible_class_id = ?
            ORDER BY m.name
        ");
        $stmt->execute([$classId]);
        
        echo "<h5>" . htmlspecialchars($className) . " Members</h5>";
        echo "<div class='table-responsive'>";
        echo "<table class='table table-bordered'>";
        echo "<thead><tr>
                <th>Name</th>
                <th>Gender</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Member Type</th>
              </tr></thead><tbody>";
        
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
            echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['member_type']) . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody></table></div>";
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>