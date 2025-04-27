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
                    <a href="../members/dashboard.php">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'events.php') !== false) ? 'active' : ''; ?>">
                    <a href="../members/events.php">
                        <i class="fas fa-home"></i>
                        <p>Events</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'tithes.php') !== false) ? 'active' : ''; ?>">
                    <a href="../members/tithes.php">
                        <i class="fas fa-home"></i>
                        <p>Your Tithes</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'donations.php') !== false) ? 'active' : ''; ?>">
                    <a href="../members/donations.php">
                        <i class="fas fa-home"></i>
                        <p>Your Donations</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'announcements.php') !== false) ? 'active' : ''; ?>">
                    <a href="../members/announcements.php">
                        <i class="fas fa-home"></i>
                        <p>Notifications/Annoucements</p>
                    </a>
                </li>
                <li class="nav-item <?php echo (strpos($_SERVER['PHP_SELF'], 'counselling.php') !== false) ? 'active' : ''; ?>">
                    <a href="../members/counselling.php">
                        <i class="fas fa-home"></i>
                        <p>Counselling</p>
                    </a>
                </li>

        


              
                

           

                <!-- Logout -->
                <li class="nav-item">
                    <a href="../logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <p>Logout</p>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->

<style>
.avatar-sm img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.sidebar .nav-item.active > a {
    background: #1572E8 !important;
    box-shadow: 4px 4px 10px 0 rgba(0,0,0,.1),4px 4px 15px -5px rgba(21,114,232,.4) !important;
}

.sidebar .nav-item.active > a p {
    color: #fff !important;
}

.sidebar .nav-collapse li.active a .sub-item {
    color: #1572E8;
    font-weight: bold;
}
</style>