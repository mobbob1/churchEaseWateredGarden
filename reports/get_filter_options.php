<?php
require_once '../includes/auth.php';
require_once '../config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

try {
    // Get filter options from database
    $options = [
        'gender' => [
            ['value' => 'Male', 'label' => 'Male'],
            ['value' => 'Female', 'label' => 'Female']
        ],
        'membership_status' => [
            ['value' => 'active', 'label' => 'Active'],
            ['value' => 'inactive', 'label' => 'Inactive'],
            ['value' => 'pending', 'label' => 'Pending']
        ],
        'event_type' => [
            ['value' => 'service', 'label' => 'Service'],
            ['value' => 'bible_study', 'label' => 'Bible Study'],
            ['value' => 'youth', 'label' => 'Youth Program'],
            ['value' => 'other', 'label' => 'Other']
        ],
        'transaction_type' => [
            ['value' => 'income', 'label' => 'Income'],
            ['value' => 'expense', 'label' => 'Expense']
        ],
        'category' => [
            ['value' => 'tithe', 'label' => 'Tithe'],
            ['value' => 'offering', 'label' => 'Offering'],
            ['value' => 'donation', 'label' => 'Donation'],
            ['value' => 'salary', 'label' => 'Salary'],
            ['value' => 'utilities', 'label' => 'Utilities'],
            ['value' => 'maintenance', 'label' => 'Maintenance'],
            ['value' => 'other', 'label' => 'Other']
        ]
    ];

    echo json_encode($options);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>