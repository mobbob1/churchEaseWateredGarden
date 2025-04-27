<?php
// Get current user details
$stmt = $pdo->prepare("SELECT u.*, COALESCE(u.profile_image, '../res/assets/img/profile.jpg') as display_image 
                       FROM users u 
                       WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

// Format role for display
$roleDisplay = ucfirst($currentUser['role'] ?? 'User');

// Get profile image with fallback
$profileImage = !empty($currentUser['profile_image']) && file_exists($currentUser['profile_image']) 
    ? $currentUser['profile_image'] 
    : '../res/assets/img/usericon.jpg';
?>

<!-- Sidebar -->
<div class="sidebar sidebar-style-2">
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <div class="user">
                <div class="avatar-sm float-left mr-2">
                    <img src="<?php echo htmlspecialchars($profileImage); ?>" 
                         alt="Profile Picture" 
                         class="avatar-img rounded-circle"
                         onerror="this.src='../res/assets/img/usericon.jpg';">
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
                    <div class="collapse in" id="collapseExample">
                        <ul class="nav">
                            <li>
                                <a href="../profile/my_profile.php">
                                    <span class="link-collapse">My Profile</span>
                                </a>
                            </li>
                            <li>
                                <a href="../profile/edit_profile.php">
                                    <span class="link-collapse">Edit Profile</span>
                                </a>
                            </li>
                            <li>
                                <a href="../profile/change_password.php">
                                    <span class="link-collapse">Change Password</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <ul class="nav nav-primary">
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'dashboard.php') !== false) ? 'active' : ''; ?>">
                    <a href="../home/dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                <!-- Membership Management -->
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
                        <p>Member Groups</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/organization/manage_organizations.php') !== false || strpos($_SERVER['PHP_SELF'], '/organization/manage_bible_classes.php') !== false || strpos($_SERVER['PHP_SELF'], '/organization/member_groups.php') !== false) ? 'show' : ''; ?>" id="organization">
                        <ul class="nav nav-collapse">
                           
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
                                <a href="../communication/send_sms.php">
                                    <span class="sub-item">Send SMS</span>
                                </a>
                            </li>
                            <li>
                                <a href="../communication/send_email.php">
                                    <span class="sub-item">Send Email</span>
                                </a>
                            </li>
                            <li>
                                <a href="../communication/message_history.php">
                                    <span class="sub-item">Message History</span>
                                </a>
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

                <!-- Counselling -->
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/counselling/') !== false) ? 'active' : ''; ?>">
                    <a data-toggle="collapse" href="#counselling">
                        <i class="fas fa-hands-helping"></i>
                        <p>Counselling</p>
                        <span class="caret"></span>
                    </a>
                    <div class="collapse <?php echo (strpos($_SERVER['PHP_SELF'], '/counselling/') !== false) ? 'show' : ''; ?>" id="counselling">
                        <ul class="nav nav-collapse">
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'schedule_counselling.php') !== false) ? 'active' : ''; ?>">
                                <a href="../counselling/schedule_counselling.php">
                                    <span class="sub-item">Schedule Counselling</span>
                                </a>
                            </li>
                            <li class="<?php echo (strpos($_SERVER['PHP_SELF'], 'view_sessions.php') !== false) ? 'active' : ''; ?>">
                                <a href="../counselling/view_sessions.php">
                                    <span class="sub-item">View Sessions</span>
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
            </ul>
        </div>
    </div>
</div>

<style>
.nav-collapse .subnav {
    padding-left: 15px;
}

.nav-collapse .subnav .sub-item {
    font-size: 13px;
    color: #777;
}

.nav-collapse .subnav .active .sub-item {
    color: #1572E8;
    font-weight: bold;
}

.avatar-sm {
    width: 36px;
    height: 36px;
}

.avatar-sm img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
</style>