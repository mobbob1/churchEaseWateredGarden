<?php
require_once '../includes/auth.php';
// Get member data
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$member) {
        header("Location: view_members.php");
        exit();
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id = $_POST['id'];
    $first_name = $_POST['first_name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $profession = $_POST['profession'];
    $home_address = $_POST['home_address'];
    $work_address = $_POST['work_address'];
    $gps_number = $_POST['gps_number'];
    $contact_number_1 = $_POST['contact_number_1'];
    $contact_number_2 = $_POST['contact_number_2'];
    $next_of_kin_name = $_POST['next_of_kin_name'];
    $next_of_kin_contact_number = $_POST['next_of_kin_contact_number'];
    $name = $first_name . ' ' . $surname;

    try {
        $sql = "UPDATE members SET 
                name = ?, first_name = ?, surname = ?, email = ?,
                profession = ?, home_address = ?, work_address = ?,
                gps_number = ?, contact_number_1 = ?, contact_number_2 = ?,
                next_of_kin_name = ?, next_of_kin_contact_number = ?
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$name, $first_name, $surname, $email, $profession,
                       $home_address, $work_address, $gps_number,
                       $contact_number_1, $contact_number_2,
                       $next_of_kin_name, $next_of_kin_contact_number, $id]);
        
        $message = "<div class='alert alert-success'>Member Updated Successfully!</div>";
        header("Location: view_members.php?message=updated");
        exit();
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Edit Member - OutpouringCRM</title>
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
        <!-- Header -->
        <?php include '../res/main_header.php'; ?>
        <!-- End Header -->

        <!-- Sidebar -->
        <?php include '../res/sidebar.php'; ?>
        <!-- End Sidebar -->

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Edit Member</h4>
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
                                <a href="#">Members</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Edit Member</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="card-title">Edit Member Information</div>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($message)) echo $message; ?>
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>First Name</label>
                                                    <input type="text" class="form-control" name="first_name" 
                                                           value="<?php echo $member['first_name']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Surname</label>
                                                    <input type="text" class="form-control" name="surname" 
                                                           value="<?php echo $member['surname']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Email</label>
                                                    <input type="email" class="form-control" name="email" 
                                                           value="<?php echo $member['email']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Profession</label>
                                                    <input type="text" class="form-control" name="profession" 
                                                           value="<?php echo $member['profession']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Home Address</label>
                                                    <input type="text" class="form-control" name="home_address" 
                                                           value="<?php echo $member['home_address']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Work Address</label>
                                                    <input type="text" class="form-control" name="work_address" 
                                                           value="<?php echo $member['work_address']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>GPS Number</label>
                                                    <input type="text" class="form-control" name="gps_number" 
                                                           value="<?php echo $member['gps_number']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Contact Number 1</label>
                                                    <input type="text" class="form-control" name="contact_number_1" 
                                                           value="<?php echo $member['contact_number_1']; ?>" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Contact Number 2</label>
                                                    <input type="text" class="form-control" name="contact_number_2" 
                                                           value="<?php echo $member['contact_number_2']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Next of Kin Name</label>
                                                    <input type="text" class="form-control" name="next_of_kin_name" 
                                                           value="<?php echo $member['next_of_kin_name']; ?>">
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Next of Kin Contact</label>
                                                    <input type="text" class="form-control" name="next_of_kin_contact_number" 
                                                           value="<?php echo $member['next_of_kin_contact_number']; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-action">
                                            <button type="submit" class="btn btn-success">Update Member</button>
                                            <a href="view_members.php" class="btn btn-danger">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
</body>
</html>