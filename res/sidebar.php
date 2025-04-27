<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

// Function to check if user has access to a menu
function hasMenuAccess($menuKey) {
    $menuAccess = [
        'dashboard' => ['admin','minister_in_charge'],
        
        //
        'admin_membership' => ['admin','minister_in_charge'],
        
        //
        'orgdashboard' => ['org_rep'],
        
       
        // Class Leaders
        'class_dashboard' => ['class_leader'],
       
        
        // Tithe Representatives
        'tithe_dashboard' => ['tithe_officer'],
       
        
        // Statistics Representatives
        'stats_dashboard' => ['statistics_rep'],
      
        
        // Secretary
        'secretary_dashboard' => ['secretary'],
       
        
        // Media Representatives
        'media_dashboard' => ['media_officer'],
       
        
        // Steward and Finance
        'steward_dashboard' => ['steward'],
       
    ];
    
    // Check if user has access through any of their roles
    foreach ($_SESSION['all_roles'] as $role) {
        if (in_array('all', $menuAccess[$menuKey] ?? []) || in_array($role, $menuAccess[$menuKey] ?? [])) {
            return true;
        }
    }
    return false;
}

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar -->
<div class="sidebar sidebar-style-2">
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <div class="user">
                <div class="avatar-sm float-left mr-2">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" alt="..." class="avatar-img rounded-circle">
                </div>
                <div class="info">
                    <a data-toggle="collapse" href="#collapseExample" aria-expanded="true">
                        <span>
                            <?php echo htmlspecialchars($currentUser['full_name'] ?? 'User'); ?>
                            <span class="user-level"><?php echo htmlspecialchars($roleDisplay); ?></span>
                            <span class="caret"></span>
                        </span>
                    </a>
                    <div class="clearfix"></div>
                </div>
            </div>

            <ul class="nav nav-primary">
                <!-- Dashboard -->
                     <?php if (hasMenuAccess('dashboard')): ?>
                <li class="nav-item <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="../home/dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
        <?php endif; ?>
                     <?php if (hasMenuAccess('orgdashboard')): ?>
                <li class="nav-item <?php echo $currentPage === 'org_dashboard.php' ? 'active' : ''; ?>">
                    <a href="../organization/org_dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
        <?php endif; ?>
                     <?php if (hasMenuAccess('stats_dashboard')): ?>
                <li class="nav-item <?php echo $currentPage === 'org_dashboard.php' ? 'active' : ''; ?>">
                    <a href="../home/stat_dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
        <?php endif; ?>
                     <?php if (hasMenuAccess('secretary_dashboard')): ?>
                <li class="nav-item <?php echo $currentPage === 'sec_dashboard.php' ? 'active' : ''; ?>">
                    <a href="../home/sec_dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
        <?php endif; ?>
                     <?php if (hasMenuAccess('tithe_dashboard')): ?>
                <li class="nav-item <?php echo $currentPage === 'tithe_dashboard.php' ? 'active' : ''; ?>">
                    <a href="../home/tithe_dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
        <?php endif; ?>
                
                <!-- Membership Management -->
                   <?php if (hasMenuAccess('admin_membership')): ?>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/membership/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#membership">
                        <i class="fas fa-users"></i>
                        <p>Membership</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/membership/') !== false) ? 'show' : ''; ?>" id="membership">
                        <ul class="nav nav-collapse">
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'member_management.php') !== false) ? 'active' : ''; ?>">
                                <a href="../membership/member_management.php">
                                    <span class="sub-item">Manage Members</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_members.php') !== false) ? 'active' : ''; ?>">
                                <a href="../membership/view_members.php">
                                    <span class="sub-item">View Members</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'upload_members.php') !== false) ? 'active' : ''; ?>">
                                <a href="../membership/upload_members.php">
                                    <span class="sub-item">Upload Members</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'family_tree.php') !== false) ? 'active' : ''; ?>">
                                <a href="../membership/family_tree.php">
                                    <span class="sub-item">Family Tree</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'organization_members.php') !== false) ? 'active' : ''; ?>">
                                <a href="../membership/organization_members.php">
                                    <span class="sub-item">Organization Members</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'bible_class_members.php') !== false) ? 'active' : ''; ?>">
                                <a href="../membership/bible_class_members.php">
                                    <span class="sub-item">Bible Classes Members</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'pending_registrations.php') !== false) ? 'active' : ''; ?>">
                                <a href="../membership/pending_registrations.php">
                                    <span class="sub-item">Self Registrations Pending</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>  
                 <!-- Organizations Management -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/organization/manage_organizations.php') !== false || strpos($_SERVER['PHP_SELF'], '/organization/manage_bible_classes.php') !== false || strpos($_SERVER['PHP_SELF'], '/organization/member_groups.php') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#organization">
                        <i class="fas fa-users"></i>
                        <p>Organizations/Bible Classes</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/organization/manage_organizations.php') !== false || strpos($_SERVER['PHP_SELF'], '/organization/manage_bible_classes.php') !== false || strpos($_SERVER['PHP_SELF'], '/organization/member_groups.php') !== false) ? 'show' : ''; ?>" id="organization">
                        <ul class="nav nav-collapse">
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'manage_organizations.php') !== false) ? 'active' : ''; ?>">
                                <a href="../organization/manage_organizations.php">
                                    <span class="sub-item">Manage Organizations</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'manage_bible_classes.php') !== false) ? 'active' : ''; ?>">
                                <a href="../organization/manage_bible_classes.php">
                                    <span class="sub-item">Bible Classes</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'member_groups.php') !== false) ? 'active' : ''; ?>">
                                <a href="../organization/member_groups.php">
                                    <span class="sub-item">Members Groups</span>
                                </a>
                            </li>
                          
                        </ul>
                    </div>
                </li>
                
                 <!-- Communication -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/communication/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#communication">
                        <i class="fas fa-comments"></i>
                        <p>Communication</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/communication/') !== false) ? 'show' : ''; ?>" id="communication">
                        <ul class="nav nav-collapse">
                            <li>
                                <a href="../communication/bulk_message.php">
                                    <span class="sub-item">Send SMS</span>
                                </a>
                            </li>
                            <li>
                                <a href="../communication/send_email.php">
                                    <span class="sub-item">Send Email</span>
                                </a>
                            </li>
                            <li>
                                <a href="../communication/all_messages.php">
                                    <span class="sub-item">Message History</span>
                                </a>
                            </li>
                             <li>
                                <a href="../communication/whatsapp_message.php">
                                    <span class="sub-item">Whatsapp Message</span>
                                </a>
                            </li>
                             <li>
                                <a href="../communication/voice_call.php">
                                    <span class="sub-item">Voice Call</span>
                                </a>
                            </li>
                             <li>
                                <a href="../communication/send_email.php">
                                    <span class="sub-item">Send Email</span>
                                </a>
                            </li>
                             <li>
                                <a href="../communication/email_templates.php">
                                    <span class="sub-item">Email Templates</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                
                <!-- Attendance -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/attendance/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#attendance">
                        <i class="fas fa-chart-bar"></i>
                        <p>Attendance</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/attendance/') !== false) ? 'show' : ''; ?>" id="attendance">
                        <ul class="nav nav-collapse">
                            <!-- Operations Statistics -->
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'statistics.php') && !strpos($_SERVER['PHP_SELF'], 'org_statistics') !== false) ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#opStats">
                                    <span class="sub-item">Operations Statistics</span>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], 'statistics.php') && !strpos($_SERVER['PHP_SELF'], 'org_statistics') !== false) ? 'show' : ''; ?>" id="opStats">
                                    <ul class="nav nav-collapse subnav">
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'weekly_statistics.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../attendance/weekly_statistics.php">
                                                <span class="sub-item">Add Statistics</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_statistics.php') && !strpos($_SERVER['PHP_SELF'], 'org_statistics.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../attendance/view_statistics.php">
                                                <span class="sub-item">View Statistics</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Organization Statistics -->
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'org_statistics') !== false) ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#orgStats">
                                    <span class="sub-item">Organization Statistics</span>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], 'org_statistics') !== false) ? 'show' : ''; ?>" id="orgStats">
                                    <ul class="nav nav-collapse subnav">
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'weekly_org_statistics.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../attendance/weekly_org_statistics.php">
                                                <span class="sub-item">Add Statistics</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_org_statistics.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../attendance/view_org_statistics.php">
                                                <span class="sub-item">View Statistics</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>
                        </ul>
                    </div>
                </li>
                 <!-- Financial -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/financial/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#financial">
                        <i class="fas fa-money-bill-wave"></i>
                        <p>Financial</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/financial/') !== false) ? 'show' : ''; ?>" id="financial">
                        <ul class="nav nav-collapse">
                            <!-- Tithes Submenu -->
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'tithe') !== false) ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#tithes">
                                    <span class="sub-item">Tithes</span>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], 'tithe') !== false) ? 'show' : ''; ?>" id="tithes">
                                    <ul class="nav nav-collapse subnav">
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'tithe_management.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/tithe_management.php">
                                                <span class="sub-item">Tithe Management</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_tithes.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/view_tithes.php">
                                                <span class="sub-item">View Tithes</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'tithe_by_bible_class.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/tithe_by_bible_class.php">
                                                <span class="sub-item">Bible Class Tithes</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'tithe_summary_by_bible_class.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/tithe_summary_by_bible_class.php">
                                                <span class="sub-item">Bible Class Tithe Summary</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'yearly_tithe_report.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/yearly_tithe_report.php">
                                                <span class="sub-item">Yearly Tithe Summary</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Offerings Submenu -->
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'offering') !== false) ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#offerings">
                                    <span class="sub-item">Offerings</span>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], 'offering') !== false) ? 'show' : ''; ?>" id="offerings">
                                    <ul class="nav nav-collapse subnav">
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'offering_management.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/offering_management.php">
                                                <span class="sub-item">Offering Management</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_offerings.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/view_offerings.php">
                                                <span class="sub-item">View Offerings</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'offering_by_service.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/offering_by_service.php">
                                                <span class="sub-item">Offerings by Service</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'yearly_offering_report.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/yearly_offering_report.php">
                                                <span class="sub-item">Yearly Offering Summary</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Donations Submenu -->
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'donation') !== false) ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#donations">
                                    <span class="sub-item">Donations</span>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], 'donation') !== false) ? 'show' : ''; ?>" id="donations">
                                    <ul class="nav nav-collapse subnav">
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'donation_management.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/donation_management.php">
                                                <span class="sub-item">Donation Management</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_donations.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/view_donations.php">
                                                <span class="sub-item">View Donations</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'donation_by_project.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/donation_by_project.php">
                                                <span class="sub-item">Donations by Project</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'yearly_donation_report.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/yearly_donation_report.php">
                                                <span class="sub-item">Yearly Donation Summary</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Expenses Submenu -->
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'expense') !== false) ? 'active' : ''; ?>">
                                <a data-toggle="collapse" href="#expenses">
                                    <span class="sub-item">Expenses</span>
                                    <span class="caret"></span>
                                </a>
                                <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], 'expense') !== false) ? 'show' : ''; ?>" id="expenses">
                                    <ul class="nav nav-collapse subnav">
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'expense_management.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/expense_management.php">
                                                <span class="sub-item">Expense Management</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_expenses.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/view_expenses.php">
                                                <span class="sub-item">View Expenses</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'expense_by_category.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/expense_by_category.php">
                                                <span class="sub-item">Expenses by Category</span>
                                            </a>
                                        </li>
                                        <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'yearly_expense_report.php') !== false) ? 'active' : ''; ?>">
                                            <a href="../financial/yearly_expense_report.php">
                                                <span class="sub-item">Yearly Expense Summary</span>
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </li>

                            <!-- Financial Reports -->
                           
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'other_income.php') !== false) ? 'active' : ''; ?>">
                                <a href="../financial/other_income.php">
                                    <span class="sub-item">Manage Other Income</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'asset_management.php') !== false) ? 'active' : ''; ?>">
                                <a href="../financial/asset_management.php">
                                    <span class="sub-item">Manage Assets</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'liability_management.php') !== false) ? 'active' : ''; ?>">
                                <a href="../financial/liability_management.php">
                                    <span class="sub-item">Manage Liability</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'equity_management.php') !== false) ? 'active' : ''; ?>">
                                <a href="../financial/equity_management.php">
                                    <span class="sub-item">Equity Management</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'income_statement.php') !== false) ? 'active' : ''; ?>">
                                <a href="../financial/income_statement.php">
                                    <span class="sub-item">Income Statement</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'balance_sheet.php') !== false) ? 'active' : ''; ?>">
                                <a href="../financial/balance_sheet.php">
                                    <span class="sub-item">Balance Sheet</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                
              
                <!-- Counselling -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/counselling/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#counselling">
                        <i class="fas fa-hands-helping"></i>
                        <p>Counselling</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/counselling/') !== false) ? 'show' : ''; ?>" id="counselling">
                        <ul class="nav nav-collapse">
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'manage_counselling.php') !== false) ? 'active' : ''; ?>">
                                <a href="../counselling/manage_counselling.php">
                                    <span class="sub-item">Schedule Counselling</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'counselling_history.php') !== false) ? 'active' : ''; ?>">
                                <a href="../counselling/counselling_history.php">
                                    <span class="sub-item">View Sessions</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'counselling_reports.php') !== false) ? 'active' : ''; ?>">
                                <a href="../counselling/counselling_reports.php">
                                    <span class="sub-item">Counselling Reports</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Settings -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/settings/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#settings">
                        <i class="fas fa-cog"></i>
                        <p>Settings</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/settings/') !== false) ? 'show' : ''; ?>" id="settings">
                        <ul class="nav nav-collapse">
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'system_settings.php') !== false) ? 'active' : ''; ?>">
                                <a href="../settings/system_settings.php">
                                    <span class="sub-item">System Settings</span>
                                </a>
                            </li>
                             <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'manage_user.php') !== false) ? 'active' : ''; ?>">
                                <a href="../users/manage_user.php">
                                    <span class="sub-item">Users Management</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_users.php') !== false) ? 'active' : ''; ?>">
                                <a href="../users/view_users.php">
                                    <span class="sub-item">View Users</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'backup_restore.php') !== false) ? 'active' : ''; ?>">
                                <a href="../settings/backup_restore.php">
                                    <span class="sub-item">Backup & Restore</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
                <!-- Branch Management -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/branches/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#branches">
                        <i class="fas fa-cog"></i>
                        <p>Branches</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/branches/') !== false) ? 'show' : ''; ?>" id="branches">
                        <ul class="nav nav-collapse">
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'manage_branch.php') !== false) ? 'active' : ''; ?>">
                                <a href="../branches/manage_branch.php">
                                    <span class="sub-item">Manage Branches</span>
                                </a>
                            </li>
                             <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'branch_users.php') !== false) ? 'active' : ''; ?>">
                                <a href="../branches/branch_users.php">
                                    <span class="sub-item">Branch Users</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'branch_reports.php') !== false) ? 'active' : ''; ?>">
                                <a href="../branches/branch_reports.php">
                                    <span class="sub-item">Branch Reports</span>
                                </a>
                            </li>
                       
                        </ul>
                    </div>
                </li>
                
                   <!-- Reports -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/reports/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#reports">
                        <i class="fas fa-chart-bar"></i>
                        <p>Reports</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/reports/') !== false) ? 'show' : ''; ?>" id="reports">
                        <ul class="nav nav-collapse">
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'reports_dashboard.php') !== false) ? 'active' : ''; ?>">
                                <a href="../reports/reports_dashboard.php">
                                    <span class="sub-item">Reports Dashboard</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'membership_growth.php') !== false) ? 'active' : ''; ?>">
                                <a href="../reports/membership_growth_report.php">
                                    <span class="sub-item">Membership Growth</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'attendance_patterns.php') !== false) ? 'active' : ''; ?>">
                                <a href="../reports/attendance_patterns.php">
                                    <span class="sub-item">Attendance Patterns</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'financial_trends.php') !== false) ? 'active' : ''; ?>">
                                <a href="../reports/financial_trends.php">
                                    <span class="sub-item">Financial Trends</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'event_participation.php') !== false) ? 'active' : ''; ?>">
                                <a href="../reports/event_participation.php">
                                    <span class="sub-item">Event Participation</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'custom_report.php') !== false) ? 'active' : ''; ?>">
                                <a href="../reports/custom_report.php">
                                    <span class="sub-item">Custom Report Generator</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
 <?php endif; ?>
                   <?php if (hasMenuAccess('class_dashboard')): ?>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'class_dashboard.php') !== false) ? 'active' : ''; ?>">
                    <a href="../home/class_dashboard.php">
                        <i class="fas fa-book"></i>
                        <p>Bible Class Dashboard</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'specific_class_members.php') !== false) ? 'active' : ''; ?>">
                    <a href="../membership/specific_class_members.php">
                        <i class="fas fa-users"></i>
                        <p>Class Members</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'view_class_members_tithes.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/view_class_members_tithes.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Member Tithes</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'approve_class_members.php') !== false) ? 'active' : ''; ?>">
                    <a href="../membership/approve_class_members.php">
                        <i class="fas fa-user-check"></i>
                        <p>Approve Members</p>
                    </a>
                </li>
                <?php endif; ?>
                   <?php if (hasMenuAccess('orgdashboard')): ?>
             
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'specific_organization_members.php') !== false) ? 'active' : ''; ?>">
                    <a href="../organization/specific_organization_members.php">
                        <i class="fas fa-users"></i>
                        <p>Organization Members</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'organization_meeting_attendance.php') !== false) ? 'active' : ''; ?>">
                    <a href="../attendance/organization_meeting_attendance.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Manage Attendance</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'add_welfare_due.php') !== false) ? 'active' : ''; ?>">
                    <a href="../organization/add_welfare_due.php">
                        <i class="fas fa-user-check"></i>
                        <p>Manage Dues</p>
                    </a>
                </li>
                <?php endif; ?>
                  <?php if (hasMenuAccess('tithe_dashboard')): ?>
             
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'tithe_management.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/tithe_management.php">
                        <i class="fas fa-users"></i>
                        <p>Record Tithe</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'view_tithes.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/view_tithes.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Tithe Reports</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'tithe_summary_by_bible_class.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/tithe_summary_by_bible_class.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Summary Bible Class Tithes</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'yearly_tithe_report.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/yearly_tithe_report.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Yearly Tithes Summary</p>
                    </a>
                </li>
               
                <?php endif; ?>
              

                <!-- Statistics Representatives -->
                <?php if (hasMenuAccess('stats_dashboard')): ?>
                  <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'weekly_statistics.php') !== false) ? 'active' : ''; ?>">
                    <a href="../attendance/weekly_statistics.php">
                        <i class="fas fa-users"></i>
                        <p>Services Attendance</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'weekly_org_statistics.php') !== false) ? 'active' : ''; ?>">
                    <a href="../attendance/weekly_org_statistics.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Organization Attendance</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'tithe_summary_by_bible_class.php') !== false) ? 'active' : ''; ?>">
                    <a href="../attendance/members_growth.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Membership Growth</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'statistical_report.php') !== false) ? 'active' : ''; ?>">
                    <a href="../attendance/statistical_report.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Statistical Reports</p>
                    </a>
                </li>
                
               
                     
                <?php endif; ?>

                <!-- Secretary -->
                 <?php if (hasMenuAccess('secretary_dashboard')): ?>
                  <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'weekly_statistics.php') !== false) ? 'active' : ''; ?>">
                    <a href="../events/event_management.php">
                        <i class="fas fa-users"></i>
                        <p>Events Management</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'bulk_message.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/bulk_message.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>SMS Bulk Messaging</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'send_email.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/send_email.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Email Messaging</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'whatsapp_message.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/whatsapp_message.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Whatsapp Messaging</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'voice_call.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/voice_call.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Voice Call</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'all_message.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/all_messages.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Message History</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'manage_visitors.php') !== false) ? 'active' : ''; ?>">
                    <a href="../membership/manage_visitors.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Visitors</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'expense_management.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/expense_management.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Expenditure</p>
                    </a>
                </li>
                
               
                     
                <?php endif; ?>
              

                <!-- Media Representatives -->
                    <?php if (hasMenuAccess('media_dashboard')): ?>
                  <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'weekly_statistics.php') !== false) ? 'active' : ''; ?>">
                    <a href="../events/event_management.php">
                        <i class="fas fa-users"></i>
                        <p>Events Management</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'bulk_message.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/bulk_message.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>SMS Bulk Messaging</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'send_email.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/send_email.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Email Messaging</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'whatsapp_message.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/whatsapp_message.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Whatsapp Messaging</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'voice_call.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/voice_call.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Voice Call</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'all_message.php') !== false) ? 'active' : ''; ?>">
                    <a href="../communication/all_messages.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Message History</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'manage_visitors.php') !== false) ? 'active' : ''; ?>">
                    <a href="../membership/manage_visitors.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Visitors</p>
                    </a>
                </li>
               
               
                     
                <?php endif; ?>
               

                <!-- Steward and Finance -->
                <?php if (hasMenuAccess('steward_dashboard')): ?>
                 <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'offering_management.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/offering_management.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Manage Offerings</p>
                    </a>
                </li>
                 <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'donation_management.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/donation_management.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Manage Donations</p>
                    </a>
                </li>
                 <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'income_statement.php') !== false) ? 'active' : ''; ?>">
                    <a href="../financial/income_statement.php">
                        <i class="fas fa-hand-holding-usd"></i>
                        <p>Income Statement</p>
                    </a>
                </li>
               
               
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<style>
.sidebar .nav-collapse {
    margin: 0 15px;
}
.sidebar .nav-collapse .nav-item {
    margin-bottom: 0;
}
.sidebar .nav-collapse .sub-item {
    padding: 5px 15px;
    display: block;
    color: #575962;
    text-decoration: none;
    position: relative;
}
.sidebar .nav .nav-item a {
    display: flex;
    align-items: center;
    color: #575962;
    padding: 10px;
    position: relative;
    text-decoration: none;
}
.sidebar .nav .nav-item a i {
    color: #575962;
    width: 20px;
    text-align: center;
    margin-right: 10px;
    font-size: 16px;
}
.nav-item.active > a {
    background: #f1f1f1;
    color: #1a2035 !important;
}
.nav-item.active > a i {
    color: #1a2035 !important;
}
</style>