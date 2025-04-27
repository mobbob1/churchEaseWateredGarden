<?php
require_once '../includes/auth.php';
require_once '../config.php';

header('Content-Type: application/json');

$group = $_GET['group'] ?? '';
$data = array();

try {
    error_log("Requested group: " . $group);
    
    switch ($group) {
        case 'gender':
        case 'memberType':
        case 'memberTypes':
            $query = "
                SELECT m.*, mt.type_name,
                       bc.class_name,
                       o.organization_name
                FROM members m
                LEFT JOIN member_types mt ON m.member_type_id = mt.id
                LEFT JOIN bible_classes bc ON m.bible_class_id = bc.id
                LEFT JOIN organizations o ON m.organization_id = o.id
                WHERE mt.type_name != 'Visitor'
                ORDER BY m.name
            ";
            break;
            
        case 'bibleClasses':
            $query = "
                SELECT m.*, mt.type_name,
                       bc.class_name,
                       o.organization_name
                FROM members m
                INNER JOIN member_types mt ON m.member_type_id = mt.id
                INNER JOIN bible_classes bc ON m.bible_class_id = bc.id
                LEFT JOIN organizations o ON m.organization_id = o.id
                WHERE mt.type_name != 'Visitor'
                  AND m.bible_class_id IS NOT NULL
                ORDER BY bc.class_name, m.name
            ";
            break;
            
        case 'organizations':
            $query = "
                SELECT m.*, mt.type_name,
                       bc.class_name,
                       o.organization_name
                FROM members m
                INNER JOIN member_types mt ON m.member_type_id = mt.id
                LEFT JOIN bible_classes bc ON m.bible_class_id = bc.id
                INNER JOIN organizations o ON m.organization_id = o.id
                WHERE mt.type_name != 'Visitor'
                  AND m.organization_id IS NOT NULL
                ORDER BY o.organization_name, m.name
            ";
            break;

        case 'visitors':
            $query = "
                SELECT m.*, mt.type_name
                FROM members m
                INNER JOIN member_types mt ON m.member_type_id = mt.id
                WHERE mt.type_name = 'Visitor'
                ORDER BY m.created_at DESC
            ";
            break;

        default:
            error_log("Invalid group specified: " . $group);
            throw new Exception("Invalid group specified");
    }
    
    error_log("Executing query: " . $query);
    $stmt = $pdo->query($query);
    
    $counter = 1;
    if ($group === 'visitors') {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                "0" => $counter++,
                "1" => htmlspecialchars($row['name']),
                "2" => htmlspecialchars($row['gender']),
                "3" => htmlspecialchars($row['contact_number_1']),
                "4" => htmlspecialchars($row['home_address']),
                "5" => htmlspecialchars($row['notes']),
                "6" => date('Y-m-d', strtotime($row['created_at'])),
                "7" => '<div class="form-button-action">
                        <a href="member_management.php?id='.$row['id'].'" class="btn btn-link btn-primary btn-lg">
                            <i class="fa fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link btn-danger" onclick="deleteMember('.$row['id'].')">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>'
            );
        }
    } else {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                "0" => $counter++,
                "1" => htmlspecialchars($row['name']),
                "2" => htmlspecialchars($row['type_name'] ?? 'Not Assigned'),
                "3" => htmlspecialchars($row['gender']),
                "4" => htmlspecialchars($row['contact_number_1']),
                "5" => htmlspecialchars($row['email']),
                "6" => htmlspecialchars($row['class_name'] ?? 'Not Assigned'),
                "7" => htmlspecialchars($row['organization_name'] ?? 'Not Assigned'),
                "8" => '<div class="form-button-action">
                        <a href="member_management.php?id='.$row['id'].'" class="btn btn-link btn-primary btn-lg">
                            <i class="fa fa-edit"></i>
                        </a>
                        <button type="button" class="btn btn-link btn-danger" onclick="deleteMember('.$row['id'].')">
                            <i class="fa fa-times"></i>
                        </button>
                    </div>'
            );
        }
    }
    
    error_log("Found " . count($data) . " records for group: " . $group);
    
    $response = array(
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        "recordsTotal" => count($data),
        "recordsFiltered" => count($data),
        "data" => $data
    );
    
    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(array(
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "error" => $e->getMessage(),
        "data" => []
    ));
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(array(
        "draw" => isset($_GET['draw']) ? intval($_GET['draw']) : 1,
        "recordsTotal" => 0,
        "recordsFiltered" => 0,
        "error" => $e->getMessage(),
        "data" => []
    ));
}