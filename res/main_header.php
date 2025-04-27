	<div class="main-header">
			<!-- Logo Header -->
                        <div class="logo-header" data-background-color="#964B00" style="background-color: #063970">
				
				<a href="dashboard.php" class="logo">
                                    <img src="../loginres/images/wateredgardenchurch.jpg" alt="navbar brand" class="navbar-brand" height="87%">
				</a>
				<button class="navbar-toggler sidenav-toggler ml-auto" type="button" data-toggle="collapse" data-target="collapse" aria-expanded="false" aria-label="Toggle navigation">
					<span class="navbar-toggler-icon">
						<i class="icon-menu"></i>
					</span>
				</button>
				<button class="topbar-toggler more"><i class="icon-options-vertical"></i></button>
				<div class="nav-toggle">
					<button class="btn btn-toggle toggle-sidebar">
						<i class="icon-menu"></i>
					</button>
				</div>
			</div>
			<!-- End Logo Header -->
			<!-- Navbar Header -->
			<nav class="navbar navbar-header navbar-expand-lg" style="background-color: #063970">
				
				<div class="container-fluid">
					<div class="collapse" id="search-nav">
						<form class="navbar-left navbar-form nav-search mr-md-3">
							<div class="input-group">
								<div class="input-group-prepend">
									<button type="submit" class="btn btn-search pr-1">
										<i class="fa fa-search search-icon"></i>
									</button>
								</div>
								<input type="text" placeholder="Search ..." class="form-control">
							</div>
						</form>
					</div>
					<ul class="navbar-nav topbar-nav ml-md-auto align-items-center">
						<li class="nav-item toggle-nav-search hidden-caret">
							<a class="nav-link" data-toggle="collapse" href="#search-nav" role="button" aria-expanded="false" aria-controls="search-nav">
								<i class="fa fa-search"></i>
							</a>
						</li>
						
						
						<li class="nav-item dropdown hidden-caret">
							<a class="nav-link" data-toggle="dropdown" href="#" aria-expanded="false">
								<i class="fas fa-layer-group"></i>
							</a>
							<div class="dropdown-menu quick-actions quick-actions-info animated fadeIn">
								<div class="quick-actions-header">
									<span class="title mb-1">Quick Actions</span>
									<span class="subtitle op-8">Shortcuts</span>
								</div>
								<div class="quick-actions-scroll scrollbar-outer">
									<div class="quick-actions-items">
										<div class="row m-0">
											<a class="col-6 col-md-4 p-0" href="../membership/member_management.php">
												<div class="quick-actions-item">
													<i class="fas fa-users"></i>
													<span class="text">Add Member</span>
												</div>
											</a>
											<a class="col-6 col-md-4 p-0" href="../attendance/checkin.php">
												<div class="quick-actions-item">
													<i class="fas fa-check-circle"></i>
													<span class="text">Check In</span>
												</div>
											</a>
											<a class="col-6 col-md-4 p-0" href="../communication/bulk_message.php">
												<div class="quick-actions-item">
													<i class="fas fa-envelope"></i>
													<span class="text">Send Message</span>
												</div>
											</a>
											<a class="col-6 col-md-4 p-0" href="../financial/view_donations.php">
												<div class="quick-actions-item">
													<i class="fas fa-hand-holding-usd"></i>
													<span class="text">Donations</span>
												</div>
											</a>
											<a class="col-6 col-md-4 p-0" href="../attendance/view_attendance.php">
												<div class="quick-actions-item">
													<i class="fas fa-clipboard-list"></i>
													<span class="text">Attendance</span>
												</div>
											</a>
											<a class="col-6 col-md-4 p-0" href="../settings/system_settings.php">
												<div class="quick-actions-item">
													<i class="fas fa-cog"></i>
													<span class="text">Settings</span>
												</div>
											</a>
										</div>
									</div>
								</div>
							</div>
						</li>
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
    : '../res/assets/img/usercoloredicon.png';
?>
                                                <a style="color:white"><?php echo htmlspecialchars($currentUser['full_name'] ?? 'User'); ?></a><li class="nav-item dropdown hidden-caret">
							<a class="dropdown-toggle profile-pic" data-toggle="dropdown" href="#" aria-expanded="false">
								<div class="avatar-sm">
									<img src="../res/assets/img/usercoloredicon.png" alt="..." class="avatar-img rounded-circle">
								</div>
							</a>
							<ul class="dropdown-menu dropdown-user animated fadeIn">
    <div class="dropdown-user-scroll scrollbar-outer">
        <li>
            <div class="user-box">
                <div class="avatar-lg">
                    <img src="../res/assets/img/usercoloredicon.png" alt="image profile" class="avatar-img rounded">
                </div>
                <div class="u-text">
                    <h4>  <?php echo htmlspecialchars($currentUser['full_name'] ?? 'User'); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($roleDisplay); ?></p>
                    <a href="../users/profile.php" class="btn btn-xs btn-secondary btn-sm">View Profile</a>
                </div>
            </div>
        </li>
        <li>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="../users/profile.php">My Profile</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item" href="../logout.php">Logout</a>
        </li>
    </div>
</ul>
						</li>
                                                
                                                
                                                
					</ul>
				</div>
			</nav>
			<!-- End Navbar -->
		</div>