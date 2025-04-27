<?php
require_once 'config.php';
require_once 'includes/AuditLogger.php';

$logger = new AuditLogger($pdo);
$message = '';

// Fetch bible classes for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM bible_classes ORDER BY class_name");
    $bible_classes = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $bible_classes = [];
}

// Fetch organizations for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM organizations ORDER BY organization_name");
    $organizations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $organizations = [];
}

// Fetch institutions for dropdown
try {
    $stmt = $pdo->query("SELECT * FROM institutions ORDER BY institution_name");
    $institutions = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $institutions = [];
}

// Fetch member types for dropdown (excluding child type)
try {
    $stmt = $pdo->query("SELECT * FROM member_types WHERE LOWER(type_name) != 'child' AND LOWER(type_name) != 'visitor' ORDER BY type_name");
    $member_types = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $member_types = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate contact number
        $contact_number = $_POST['contact_number_1'] ?? '';
        if (!empty($contact_number)) {
            $check_contact = $pdo->prepare("SELECT id, name FROM members WHERE contact_number_1 = ?");
            $check_contact->execute([$contact_number]);
            if ($check_contact->fetch()) {
                throw new Exception("This contact number is already registered.");
            }
        }

        // Handle email validation
        $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
        if ($email !== null && $email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Please enter a valid email address.");
            }
            // Check if email already exists
            $check_email = $pdo->prepare("SELECT id FROM members WHERE email = ?");
            $check_email->execute([$email]);
            if ($check_email->fetch()) {
                throw new Exception("This email address is already registered.");
            }
        } else {
            $email = null; // Ensure empty strings are converted to NULL
        }

        // Get member type info to check if student
        $member_type_id = $_POST['member_type_id'] ?? '';
        if (empty($member_type_id)) {
            throw new Exception("Member type is required.");
        }
        
        $check_type = $pdo->prepare("SELECT type_name FROM member_types WHERE id = ?");
        $check_type->execute([$member_type_id]);
        $member_type = $check_type->fetch();
        
        if (!$member_type) {
            throw new Exception("Invalid member type selected.");
        }
        
        $is_student = strtolower($member_type['type_name']) === 'student';

        // Validate and format the date
        $birth_month = (int)$_POST['birth_month'];
        $birth_day = (int)$_POST['birth_day'];
        
        // Validate month and day
        if ($birth_month < 1 || $birth_month > 12) {
            throw new Exception("Invalid month selected.");
        }
        
        // Check if the day is valid for the selected month
        $days_in_month = cal_days_in_month(CAL_GREGORIAN, $birth_month, 2000);
        if ($birth_day < 1 || $birth_day > $days_in_month) {
            throw new Exception("Invalid day selected for the chosen month.");
        }
        
        // Format the date using year 2000 as reference
        $birth_month = str_pad($birth_month, 2, '0', STR_PAD_LEFT);
        $birth_day = str_pad($birth_day, 2, '0', STR_PAD_LEFT);
        $date_of_birth = "2000-{$birth_month}-{$birth_day}";

        // Insert new member
        $sql = "INSERT INTO members (
            name, first_name, surname, email, gender, profession, 
            home_address, contact_number_1, member_type_id, 
            date_of_birth, institution_id, hostel_residence, course_name,
            status, mode_of_registration
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', 'SELF_REGISTRATION')";

        $name = $_POST['first_name'] . ' ' . $_POST['surname'];
        
        // Set student-related fields only if member type is student
        $institution_id = $is_student ? ($_POST['institution_id'] ?? null) : null;
        $hostel_residence = $is_student ? ($_POST['hostel_residence'] ?? null) : null;
        $course_name = $is_student ? ($_POST['course_name'] ?? null) : null;
        
        $stmt = $pdo->prepare($sql);
        $params = [
            $name,
            $_POST['first_name'],
            $_POST['surname'],
            $email, // Use our validated and null-handled email
            $_POST['gender'],
            $_POST['profession'] ?? null,
            $_POST['home_address'] ?? null,
            $_POST['contact_number_1'],
            $_POST['member_type_id'],
            $date_of_birth,
            $institution_id,
            $hostel_residence,
            $course_name
        ];
        
        $stmt->execute($params);
//        $member_id = $pdo->lastInsertId();
        
        // Create user account with default role
//        $username = strtolower($_POST['first_name'] . '.' . $_POST['surname']);
//        $password = password_hash($_POST['contact_number_1'], PASSWORD_DEFAULT);
//        
//        $sql = "INSERT INTO users (username, password, member_id, status, password_changed) VALUES (?, ?, ?, 'active', 0)";
//        $stmt = $pdo->prepare($sql);
//        $stmt->execute([$username, $password, $member_id]);
//        $user_id = $pdo->lastInsertId();
//        
//        // Assign default 'general' role
//        $sql = "INSERT INTO user_roles (user_id, role_key) VALUES (?, 'general')";
//        $stmt = $pdo->prepare($sql);
//        $stmt->execute([$user_id]);
//        
//        $logger->log('member_registration', 'members', $member_id, "New member registration: $name", null, $params);
//        $message = "<div class='alert alert-success'>Registration successful! Your application will be reviewed by the church administration.<br>
//                    Your username is: <strong>$username</strong><br>
//                    Your temporary password is your phone number. Please change it when you first log in.</div>";
//        
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        $logger->log('member_registration', 'members', null, "Failed registration attempt", null, null, 'failed', $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Member Registration - Outpouring Oasis</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="res/assets/img/icon.ico" type="image/x-icon"/>

    <!-- Fonts and icons -->
    <script src="res/assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {"families":["Lato:300,400,700,900"]},
            custom: {"families":["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['res/assets/css/fonts.min.css']},
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="res/assets/css/atlantis.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .registration-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            max-width: 150px;
        }
        .student-fields {
            display: none;
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <div class="logo">
            <img src="loginres/images/outpouringlogo.png" alt="Logo">
        </div>
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Member Registration Form</h4>
            </div>
            <div class="card-body">
                <?php echo $message; ?>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Surname <span class="text-danger">*</span></label>
                                <input type="text" name="surname" class="form-control" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Number <span class="text-danger">*</span></label>
                                <input type="tel" name="contact_number_1" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Gender <span class="text-danger">*</span></label>
                                <select name="gender" class="form-control" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Date of Birth (Month and Day) <span class="text-danger">*</span></label>
                                <div class="row">
                                    <div class="col-6">
                                        <select name="birth_month" class="form-control" required>
                                            <option value="">Month</option>
                                            <?php
                                            $months = [
                                                1 => 'January', 2 => 'February', 3 => 'March',
                                                4 => 'April', 5 => 'May', 6 => 'June',
                                                7 => 'July', 8 => 'August', 9 => 'September',
                                                10 => 'October', 11 => 'November', 12 => 'December'
                                            ];
                                            foreach ($months as $num => $name) {
                                                echo "<option value=\"$num\">$name</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <select name="birth_day" class="form-control" required>
                                            <option value="">Day</option>
                                            <?php
                                            for ($i = 1; $i <= 31; $i++) {
                                                echo "<option value=\"$i\">$i</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Member Type <span class="text-danger">*</span></label>
                                <select name="member_type_id" class="form-control" required onchange="toggleStudentFields(this.value)">
                                    <option value="">Select Member Type</option>
                                    <?php foreach ($member_types as $type): ?>
                                        <option value="<?php echo $type['id']; ?>"><?php echo $type['type_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Profession</label>
                                <input type="text" name="profession" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Home Address</label>
                                <textarea name="home_address" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Student-specific fields -->
                    <div class="student-fields">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Institution <span class="text-danger">*</span></label>
                                    <select name="institution_id" class="form-control">
                                        <option value="">Select Institution</option>
                                        <?php foreach ($institutions as $institution): ?>
                                            <option value="<?php echo $institution['id']; ?>"><?php echo $institution['institution_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Hostel/Hall of Residence <span class="text-danger">*</span></label>
                                    <input type="text" name="hostel_residence" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>Course Name <span class="text-danger">*</span></label>
                                    <input type="text" name="course_name" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
    

                    <div class="card-action">
                        <button type="submit" class="btn btn-success">Submit Registration</button>
                        <button type="reset" class="btn btn-danger">Reset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!--   Core JS Files   -->
    <script src="res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="res/assets/js/core/popper.min.js"></script>
    <script src="res/assets/js/core/bootstrap.min.js"></script>
    <script>
        function toggleStudentFields(memberTypeId) {
            // Get the member type name from the selected option
            const selectedOption = document.querySelector(`select[name="member_type_id"] option[value="${memberTypeId}"]`);
            const isStudent = selectedOption && selectedOption.textContent.toLowerCase() === 'student';
            
            // Show/hide student fields
            const studentFields = document.querySelector('.student-fields');
            if (studentFields) {
                studentFields.style.display = isStudent ? 'block' : 'none';
                
                // Make student fields required when visible
                const requiredFields = studentFields.querySelectorAll('input, select');
                requiredFields.forEach(field => {
                    field.required = isStudent;
                });
            }
        }
    </script>
</body>
</html>