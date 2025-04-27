<?php
// config.php
$host = 'localhost';
$db = 'churchease'; // Change this to your database name
$user = 'root'; // Change this to your database username
$pass = 'pass'; // Change this to your database password

    
// Define API constants with checks
if (!defined('MNOTIFY_API_KEY')) {
    define('MNOTIFY_API_KEY', 'QZ8hL817hx6M8llclyXSjYmPe');  // Use this key instead
}
if (!defined('MNOTIFY_SENDER_ID')) {
    define('MNOTIFY_SENDER_ID', 'Outpouring');
}
define('APP_URL', 'https://portal.theoutpouringcity.org/');


// Email Configuration
define('SMTP_HOST', 'smtp.gmail.com'); // or your SMTP server
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-specific-password');
define('SMTP_PORT', 587);
define('CHURCH_EMAIL', 'your-church-email@gmail.com');
define('CHURCH_NAME', 'Your Church Name');

// Role-based access control configuration
$ROLE_PERMISSIONS = [
   'admin' => [
        'name' => 'Admin',
        'description' => 'Super user with full system access',
        'permissions' => ['*'] // All permissions
    ],
    'org_rep' => [
        'name' => 'Organizational Representative',
        'description' => 'Organization management and tracking',
        'permissions' => [
            'view_members',
            'track_attendance',
            'manage_org_dues',
            'view_org_reports',
            'export_org_data',
            'view_org_members_list',
            'track_org_attendance'
        ]
    ],
    'class_leader' => [
        'name' => 'Class Leader',
        'description' => 'Class management and member oversight',
        'permissions' => [
            'manage_class_members',
            'view_class_tithes',
            'approve_new_members',
            'view_class_reports',
            'track_member_tithes',
            'view_class_member_list',
            'view_class_tithe_history',
            'generate_class_reports'
        ]
    ],
    'tithe_manager' => [
        'name' => 'Tithe Manager',
        'description' => 'Tithe recording and reporting',
        'permissions' => [
            'record_tithes',
            'view_tithe_reports',
            'generate_tithe_summaries',
            'export_tithe_data',
            'view_weekly_tithes',
            'view_monthly_tithes',
            'view_yearly_tithes',
            'generate_tithe_reports'
        ]
    ],
    'statistics_rep' => [
        'name' => 'Statistics Representative',
        'description' => 'Church statistics and reporting',
        'permissions' => [
            'record_attendance',
            'view_membership_growth',
            'generate_statistics_reports',
            'export_statistics_data',
            'record_weekly_attendance',
            'record_quarterly_attendance',
            'track_membership_trends',
            'generate_growth_reports'
        ]
    ],
    'secretary' => [
        'name' => 'Secretary',
        'description' => 'Church administration and events',
        'permissions' => [
            'manage_events',
            'manage_announcements',
            'manage_reminders',
            'manage_visitors',
            'record_expenditure',
            'view_church_info',
            'export_admin_data',
            'track_event_attendance',
            'manage_visitor_status',
            'track_joining_requests',
            'manage_church_expenditure',
            'generate_expenditure_reports'
        ]
    ],
    'media' => [
        'name' => 'Media',
        'description' => 'Church media and communications',
        'permissions' => [
            'manage_announcements',
            'manage_reminders',
            'manage_events',
            'manage_media_content',
            'schedule_events',
            'broadcast_announcements'
        ]
    ],
    'steward' => [
        'name' => 'Steward and F&D',
        'description' => 'Financial management and stewardship',
        'permissions' => [
            'manage_finances',
            'record_offerings',
            'record_donations',
            'record_income',
            'generate_financial_reports',
            'view_church_info',
            'export_financial_data',
            'record_church_offering',
            'manage_other_income',
            'track_donations',
            'generate_income_reports',
            'manage_financial_records',
            'view_consolidated_reports'
        ]
    ]
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Could not connect to the database $db :" . $e->getMessage());
}
?>