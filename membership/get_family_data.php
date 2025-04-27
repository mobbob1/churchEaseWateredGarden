<?php
require_once '../includes/auth.php';
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = $_POST['member_id'] ?? '';

    if (!$member_id) {
        echo json_encode(['success' => false, 'message' => 'Missing member ID']);
        exit;
    }

    try {
        // Get root member info
        $stmt = $pdo->prepare("SELECT name FROM members WHERE id = ?");
        $stmt->execute([$member_id]);
        $root = $stmt->fetch();

        if (!$root) {
            echo json_encode(['success' => false, 'message' => 'Member not found']);
            exit;
        }

        // Build the tree structure
        $tree = buildFamilyTree($pdo, $member_id);
        
        echo json_encode([
            'success' => true,
            'data' => $tree
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}

function buildFamilyTree($pdo, $memberId, $processedMembers = [], $parentRelation = null) {
    if (in_array($memberId, $processedMembers)) {
        return null; // Prevent infinite recursion
    }
    $processedMembers[] = $memberId;

    // Get member info
    $stmt = $pdo->prepare("SELECT name FROM members WHERE id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();

    $node = [
        'text' => [
            'name' => $member['name']
        ]
    ];

    // Add relationship type if this is not the root node
    if ($parentRelation) {
        $node['text']['relationship'] = $parentRelation;
    }

    // Get relationships
    $stmt = $pdo->prepare("
        SELECT r.*, 
               m.id as related_id, 
               m.name, 
               r.relationship_type,
               CASE 
                   WHEN r.member_id = ? THEN 'direct'
                   ELSE 'inverse'
               END as direction
        FROM member_relationships r
        JOIN members m ON (
            CASE 
                WHEN r.member_id = ? THEN m.id = r.related_member_id
                WHEN r.related_member_id = ? THEN m.id = r.member_id
            END
        )
        WHERE r.member_id = ? OR r.related_member_id = ?
    ");
    $stmt->execute([$memberId, $memberId, $memberId, $memberId, $memberId]);
    $relationships = $stmt->fetchAll();

    if (!empty($relationships)) {
        $node['children'] = [];
        foreach ($relationships as $rel) {
            $relatedId = ($rel['member_id'] == $memberId) ? $rel['related_id'] : $rel['member_id'];
            
            // Get the appropriate relationship label
            $relationshipType = $rel['relationship_type'];
            
            // Recursively build tree for related member
            $childNode = buildFamilyTree($pdo, $relatedId, $processedMembers, $relationshipType);
            if ($childNode) {
                $node['children'][] = $childNode;
            }
        }
    }

    return $node;
}