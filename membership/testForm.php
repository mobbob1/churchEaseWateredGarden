
<?php
require_once '../includes/auth.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $email = $_POST['email'] ?? '';
    $profession = $_POST['profession'] ?? '';
    $home_address = $_POST['home_address'] ?? '';
    $work_address = $_POST['work_address'] ?? '';
    $gps_number = $_POST['gps_number'] ?? '';
    $contact_number_1 = $_POST['contact_number_1'] ?? '';
    $contact_number_2 = $_POST['contact_number_2'] ?? '';
    $next_of_kin_name = $_POST['next_of_kin_name'] ?? '';
    $next_of_kin_contact_number = $_POST['next_of_kin_contact_number'] ?? '';
    $name = $first_name . ' ' . $surname;
    $message = '';

    try {
        if (!empty($_POST['id'])) {
            // Update existing member
            $sql = "UPDATE members SET 
                    name = :name,
                    first_name = :first_name,
                    surname = :surname,
                    email = :email,
                    profession = :profession,
                    home_address = :home_address,
                    work_address = :work_address,
                    gps_number = :gps_number,
                    contact_number_1 = :contact_number_1,
                    contact_number_2 = :contact_number_2,
                    next_of_kin_name = :next_of_kin_name,
                    next_of_kin_contact_number = :next_of_kin_contact_number
                    WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name' => $name,
                'first_name' => $first_name,
                'surname' => $surname,
                'email' => $email,
                'profession' => $profession,
                'home_address' => $home_address,
                'work_address' => $work_address,
                'gps_number' => $gps_number,
                'contact_number_1' => $contact_number_1,
                'contact_number_2' => $contact_number_2,
                'next_of_kin_name' => $next_of_kin_name,
                'next_of_kin_contact_number' => $next_of_kin_contact_number,
                'id' => $_POST['id']
            ]);
            $message = "<div class='alert alert-success'>Member Updated Successfully!</div>";
        } else {
            // Add new member
            $sql = "INSERT INTO members (
                    name, first_name, surname, email, profession, home_address, 
                    work_address, gps_number, contact_number_1, contact_number_2, 
                    next_of_kin_name, next_of_kin_contact_number
                ) VALUES (
                    :name, :first_name, :surname, :email, :profession, :home_address,
                    :work_address, :gps_number, :contact_number_1, :contact_number_2,
                    :next_of_kin_name, :next_of_kin_contact_number
                )";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'name' => $name,
                'first_name' => $first_name,
                'surname' => $surname,
                'email' => $email,
                'profession' => $profession,
                'home_address' => $home_address,
                'work_address' => $work_address,
                'gps_number' => $gps_number,
                'contact_number_1' => $contact_number_1,
                'contact_number_2' => $contact_number_2,
                'next_of_kin_name' => $next_of_kin_name,
                'next_of_kin_contact_number' => $next_of_kin_contact_number
            ]);
            $message = "<div class='alert alert-success'>Member Added Successfully!</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Manage Members - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>

    <!-- Fonts and icons -->
    <script src="../res/assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {"families":["Lato:300,400,700,900"]},
            custom: {"families":["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['../res/assets/css/fonts.min.css']},
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
</head>
<body>
	<div class="wrapper">
	 <!-- header--->
         <?php include '../res/main_header.php'; ?>
         
	 <!-- End header--->

		<!-- Sidebar -->
		 <?php include '../res/sidebar.php'; ?>
		<!-- End Sidebar -->

		<div class="main-panel">
			<div class="content">
				<div class="page-inner">
					<div class="page-header">
						<h4 class="page-title">Forms</h4>
						<ul class="breadcrumbs">
							<li class="nav-home">
								<a href="#">
									<i class="flaticon-home"></i>
								</a>
							</li>
							<li class="separator">
								<i class="flaticon-right-arrow"></i>
							</li>
							<li class="nav-item">
								<a href="#">Forms</a>
							</li>
							<li class="separator">
								<i class="flaticon-right-arrow"></i>
							</li>
							<li class="nav-item">
								<a href="#">Basic Form</a>
							</li>
						</ul>
					</div>
					<div class="row">
						<div class="col-md-12">
							<div class="card">
                                            
								<div class="card-header">
									<div class="card-title">Form Elements</div>
								</div>
								<div class="card-body">
                                                                              <form method="POST" action="">
												<?php if(isset($message)) echo $message; ?>
									<div class="row">
                                                                           
                                                                    
									

										<div class="col-md-6 col-lg-6">
									



									
										<div class="col-md-6">
											<div class="form-group">
												<label for="id">Member ID (for updates)</label>
												<input type="text" class="form-control" id="id" name="id" placeholder="Enter Member ID">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="first_name">First Name</label>
												<input type="text" class="form-control" id="first_name" name="first_name" required placeholder="Enter First Name">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="surname">Surname</label>
												<input type="text" class="form-control" id="surname" name="surname" required placeholder="Enter Surname">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="email">Email</label>
												<input type="email" class="form-control" id="email" name="email" required placeholder="Enter Email">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="profession">Profession</label>
												<input type="text" class="form-control" id="profession" name="profession" placeholder="Enter Profession">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="home_address">Home Address</label>
												<input type="text" class="form-control" id="home_address" name="home_address" placeholder="Enter Home Address">
											</div>
										</div>
										
										
										
								

										</div>
										<div class="col-md-6 col-lg-6">
									



									
										
										<div class="col-md-6">
											<div class="form-group">
												<label for="work_address">Work Address</label>
												<input type="text" class="form-control" id="work_address" name="work_address" placeholder="Enter Work Address">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="gps_number">GPS Number</label>
												<input type="text" class="form-control" id="gps_number" name="gps_number" placeholder="Enter GPS Number">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="contact_number_1">Contact Number 1</label>
												<input type="text" class="form-control" id="contact_number_1" name="contact_number_1" placeholder="Enter Primary Contact">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="contact_number_2">Contact Number 2</label>
												<input type="text" class="form-control" id="contact_number_2" name="contact_number_2" placeholder="Enter Secondary Contact">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="next_of_kin_name">Next of Kin Name</label>
												<input type="text" class="form-control" id="next_of_kin_name" name="next_of_kin_name" placeholder="Enter Next of Kin Name">
											</div>
										</div>
										<div class="col-md-6">
											<div class="form-group">
												<label for="next_of_kin_name">Next of Kin Contact</label>
												<input type="text" class="form-control" id="next_of_kin_contact_number" name="next_of_kin_contact_number" placeholder="Enter Next of Kin Contact Number">
											</div>
										</div>
										
								

										</div>
                                                                       
									</div>
                                                                                  
                                                                                  <div class="card-action">
									<button class="btn btn-success">Submit</button>
									<button class="btn btn-danger">Cancel</button>
								</div>
                                                                     </form> 
								</div>
								
							</div>
						</div>
					</div>
				</div>
			</div>
			<?php include '../res/footer.php'; ?>
		</div>
		
		
		<!-- End Custom template -->
	</div>
	<!--   Core JS Files   -->
	<script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../res/assets/js/core/popper.min.js"></script>
	<script src="../res/assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>


	<!-- Chart JS -->
	<script src="../res/assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- jQuery Sparkline -->
	<script src="../res/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

	<!-- Chart Circle -->
	<script src="../res/assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../res/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../res/assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../res/assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Sweet Alert -->
	<script src="../res/assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Atlantis JS -->
	<script src="../res/assets/js/atlantis.min.js"></script>

	<!-- Atlantis DEMO methods, don't include it in your project! -->
	<script src="../res/assets/js/setting-demo.js"></script>
	<script src="../res/assets/js/demo.js"></script>
	<script>
		Circles.create({
			id:'circles-1',
			radius:45,
			value:60,
			maxValue:100,
			width:7,
			text: 5,
			colors:['#f1f1f1', '#FF9E27'],
			duration:400,
			wrpClass:'circles-wrp',
			textClass:'circles-text',
			styleWrapper:true,
			styleText:true
		})

		Circles.create({
			id:'circles-2',
			radius:45,
			value:70,
			maxValue:100,
			width:7,
			text: 36,
			colors:['#f1f1f1', '#2BB930'],
			duration:400,
			wrpClass:'circles-wrp',
			textClass:'circles-text',
			styleWrapper:true,
			styleText:true
		})

		Circles.create({
			id:'circles-3',
			radius:45,
			value:40,
			maxValue:100,
			width:7,
			text: 12,
			colors:['#f1f1f1', '#F25961'],
			duration:400,
			wrpClass:'circles-wrp',
			textClass:'circles-text',
			styleWrapper:true,
			styleText:true
		})

		var totalIncomeChart = document.getElementById('totalIncomeChart').getContext('2d');

		var mytotalIncomeChart = new Chart(totalIncomeChart, {
			type: 'bar',
			data: {
				labels: ["S", "M", "T", "W", "T", "F", "S", "S", "M", "T"],
				datasets : [{
					label: "Total Income",
					backgroundColor: '#ff9e27',
					borderColor: 'rgb(23, 125, 255)',
					data: [6, 4, 9, 5, 4, 6, 4, 3, 8, 10],
				}],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				legend: {
					display: false,
				},
				scales: {
					yAxes: [{
						ticks: {
							display: false //this will remove only the label
						},
						gridLines : {
							drawBorder: false,
							display : false
						}
					}],
					xAxes : [ {
						gridLines : {
							drawBorder: false,
							display : false
						}
					}]
				},
			}
		});

		$('#lineChart').sparkline([105,103,123,100,95,105,115], {
			type: 'line',
			height: '70',
			width: '100%',
			lineWidth: '2',
			lineColor: '#ffa534',
			fillColor: 'rgba(255, 165, 52, .14)'
		});
	</script>
</body>
</html>