<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';
require_once __DIR__ . '/../config.php'; // Include config.php

$logger = new AuditLogger($pdo); // No user yet since this is login page

// Function to send SMS using mNotify
function sendSMS($phone, $message) {
    $apiKey = MNOTIFY_API_KEY; // Use API key from config.php
    $senderId = MNOTIFY_SENDER_ID; // Use sender name from config.php
    $url = 'https://apps.mnotify.net/smsapi';

    // Construct the URL with query parameters
    $queryParams = http_build_query([
        'key' => $apiKey,
        'to' => $phone,
        'msg' => $message,
        'sender_id' => $senderId
    ]);

    $fullUrl = $url . '?' . $queryParams;

    // Use file_get_contents to send the request
    $result = file_get_contents($fullUrl);
    if ($result === FALSE) {
        return [
            'phone' => $phone,
            'status' => 'failed',
            'response' => json_encode(['error' => 'Failed to connect to SMS API'])
        ];
    }
    $decodedResult = json_decode($result, true);
    return [
        'phone' => $phone,
        'status' => isset($decodedResult['status']) && $decodedResult['status'] == 'success' ? 'success' : 'failed',
        'response' => $result
    ];
}

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
if (!in_array($_SESSION['user_role'], ['admin', 'manager'])) {
    header('Location: /outpouringcrm/index.php?error=unauthorized');
    exit();
}

// Fetch member types for the dropdown
try {
    $stmt = $pdo->query("SELECT * FROM member_types ORDER BY type_name");
    $member_types = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $member_types = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $surname = $_POST['surname'] ?? '';
    $email = $_POST['email'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $profession = $_POST['profession'] ?? '';
    $home_address = $_POST['home_address'] ?? '';
    $work_address = $_POST['work_address'] ?? '';
    $gps_number = $_POST['gps_number'] ?? '';
    $contact_number_1 = $_POST['contact_number_1'] ?? '';
    $contact_number_2 = $_POST['contact_number_2'] ?? '';
    $next_of_kin_name = $_POST['next_of_kin_name'] ?? '';
    $next_of_kin_contact_number = $_POST['next_of_kin_contact_number'] ?? '';
    $member_type_id = $_POST['member_type_id'] ?? null;
    $parent_phone = $_POST['parent_phone'] ?? '';
    $link_parent_phone = $_POST['link_parent_phone'] ?? '';
    $name = $first_name . ' ' . $surname;
    $message = '';

    // Handle parent linking for child members
    $parent_id = null;
    if ($member_type_id) {
        $stmt = $pdo->prepare("SELECT type_name FROM member_types WHERE id = ?");
        $stmt->execute([$member_type_id]);
        $member_type = $stmt->fetchColumn();

        if ($member_type === 'child' && !empty($parent_phone) && $link_parent_phone) {
            // Look up parent by phone number
            $stmt = $pdo->prepare("SELECT id FROM members WHERE contact_number_1 = ?");
            $stmt->execute([$parent_phone]);
            $parent_id = $stmt->fetchColumn();

            if (!$parent_id) {
                $message = "<div class='alert alert-danger'>Parent with this phone number not found!</div>";
            }
        }
    }

    try {
        if (!empty($_POST['id'])) {
            // Update existing member
            $sql = "UPDATE members SET 
                    name = :name,
                    first_name = :first_name,
                    surname = :surname,
                    email = :email,
                    gender = :gender,
                    profession = :profession,
                    home_address = :home_address,
                    work_address = :work_address,
                    gps_number = :gps_number,
                    contact_number_1 = :contact_number_1,
                    contact_number_2 = :contact_number_2,
                    next_of_kin_name = :next_of_kin_name,
                    next_of_kin_contact_number = :next_of_kin_contact_number,
                    member_type_id = :member_type_id,
                    parent_id = :parent_id
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':first_name' => $first_name,
                ':surname' => $surname,
                ':email' => $email,
                ':gender' => $gender,
                ':profession' => $profession,
                ':home_address' => $home_address,
                ':work_address' => $work_address,
                ':gps_number' => $gps_number,
                ':contact_number_1' => $contact_number_1,
                ':contact_number_2' => $contact_number_2,
                ':next_of_kin_name' => $next_of_kin_name,
                ':next_of_kin_contact_number' => $next_of_kin_contact_number,
                ':member_type_id' => $member_type_id,
                ':parent_id' => $parent_id,
                ':id' => $_POST['id']
            ]);
        } else {
            // Insert new member
            $sql = "INSERT INTO members (
                    name, first_name, surname, email, gender, profession, 
                    home_address, work_address, gps_number, contact_number_1, 
                    contact_number_2, next_of_kin_name, next_of_kin_contact_number, 
                    member_type_id, parent_id
                    ) VALUES (
                    :name, :first_name, :surname, :email, :gender, :profession, 
                    :home_address, :work_address, :gps_number, :contact_number_1, 
                    :contact_number_2, :next_of_kin_name, :next_of_kin_contact_number, 
                    :member_type_id, :parent_id
                    )";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':name' => $name,
                ':first_name' => $first_name,
                ':surname' => $surname,
                ':email' => $email,
                ':gender' => $gender,
                ':profession' => $profession,
                ':home_address' => $home_address,
                ':work_address' => $work_address,
                ':gps_number' => $gps_number,
                ':contact_number_1' => $contact_number_1,
                ':contact_number_2' => $contact_number_2,
                ':next_of_kin_name' => $next_of_kin_name,
                ':next_of_kin_contact_number' => $next_of_kin_contact_number,
                ':member_type_id' => $member_type_id,
                ':parent_id' => $parent_id
            ]);
            $member_id = $pdo->lastInsertId();

            if ($member_type === 'organic') {
                // Check if user already exists with this phone number
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$contact_number_1]);
                $existing_user = $stmt->fetch();

                if (!$existing_user) {
                    // Generate a random password
                    $password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // Send SMS with credentials
                    $smsMessage = "Welcome to ChurchEase! Your account has been created.\n";
                    $smsMessage .= "Username: " . $contact_number_1 . "\n";
                    $smsMessage .= "Password: " . $password . "\n";
                    $smsMessage .= "Login at: " . APP_URL . "\n";
                    $smsMessage .= "Please change your password after login.";

                    $response = sendSMS($contact_number_1, $smsMessage);
                    $http_status = $response['status'] == 'success' ? 200 : 500;

                    // Track message in database
                    try {
                        $stmt = $pdo->prepare("INSERT INTO messages (
                            sender_id,
                            recipient_id,
                            message,
                            message_type,
                            status,
                            message_title,
                            api_response,
                            recipient_count,
                            sent_time
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");

                        $stmt->execute([
                            $_SESSION['user_id'], // sender_id (current logged-in user)
                            $member_id, // recipient_id (new member)
                            $smsMessage, // message content
                            'single', // message_type
                            $http_status == 200 ? 'sent' : 'failed', // status
                            'New Member Credentials', // message_title
                            $response['response'], // api_response
                            1 // recipient_count
                        ]);

                        // Log the SMS sending
                        $logger->log(
                            'sms',
                            'member_registration',
                            $member_id,
                            "SMS credentials sent to new member",
                            null,
                            [
                                'phone' => $contact_number_1,
                                'status' => $response['status'],
                                'response' => $response['response']
                            ],
                            $response['status']
                        );
                    } catch (PDOException $e) {
                        error_log("Error tracking SMS: " . $e->getMessage());
                        // Log the error
                        $logger->log(
                            'sms_error',
                            'member_registration',
                            $member_id,
                            "Failed to record SMS in database",
                            null,
                            ['error' => $e->getMessage()],
                            'failed'
                        );
                    }
                }
            }

            $message = "<div class='alert alert-success'>Member added successfully!</div>";
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

output_page:
// Get member data if editing
$member = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title><?php echo isset($member) ? 'Edit' : 'Add'; ?> Member - ChurchEase</title>
        <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
        <link rel="icon" href="../res/assets/img/icon.ico" type="image/x-icon"/>

        <!-- Fonts and icons -->
        <script src="../res/assets/js/plugin/webfont/webfont.min.js"></script>
        <script>
            WebFont.load({
                google: {"families": ["Lato:300,400,700,900"]},
                custom: {"families": ["Flaticon", "Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['../res/assets/css/fonts.min.css']},
                active: function () {
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
            <?php include '../res/main_header.php'; ?>
            <?php include '../res/sidebar.php'; ?>
            <div class="main-panel">
                <div class="content">
                    <div class="page-inner">
                        <div class="page-header">
                            <h4 class="page-title"><?php echo isset($member) ? 'Edit' : 'Add'; ?> Member</h4>
                            <ul class="breadcrumbs">
                                <li class="nav-home">
                                    <a href="../index.php">
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
                                    <a href="#"><?php echo isset($member) ? 'Edit' : 'Add'; ?> Member</a>
                                </li>
                            </ul>
                        </div>

                        <?php if (!empty($message)) echo $message; ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header">
                                        <div class="card-title"><?php echo isset($member) ? 'Edit Member Details' : 'Add New Member'; ?></div>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <?php if (isset($member)) : ?>
                                                <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                            <?php endif; ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Member Type <span class="text-danger">*</span></label>
                                                        <select name="member_type_id" class="form-control" required>
                                                            <option value="">Select Member Type</option>
                                                            <?php foreach ($member_types as $type): ?>
                                                                <option value="<?php echo $type['id']; ?>" 
                                                                        <?php echo (isset($member) && $member['member_type_id'] == $type['id']) ? 'selected' : ''; ?> >
                                                                        <?php echo htmlspecialchars($type['type_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 child-options-section" style="display: none;">
                                                    <div class="form-group">
                                                        <div class="custom-control custom-checkbox">
                                                            <input type="checkbox" class="custom-control-input" id="linkParentPhone" name="link_parent_phone" 
                                                                   <?php echo (isset($member) && $member['parent_id']) ? 'checked' : ''; ?> >
                                                            <label class="custom-control-label" for="linkParentPhone">Link to Parent Member</label>
                                                        </div>
                                                        <div class="parent-phone-section mt-3" style="display: none;">
                                                            <label>Parent's Phone Number <span class="text-danger">*</span></label>
                                                            <input type="text" name="parent_phone" class="form-control" 
                                                                   value="<?php
                                                                   echo isset($member) && $member['parent_id'] ?
                                                                           (function () use ($pdo, $member) {
                                                                               $stmt = $pdo->prepare("SELECT contact_number_1 FROM members WHERE id = ?");
                                                                               $stmt->execute([$member['parent_id']]);
                                                                               return $stmt->fetchColumn();
                                                                           })() : '';
                                                                   ?>">
                                                            <small class="form-text text-muted">Enter the phone number of the parent member</small>
                                                            <?php if (isset($member) && $member['parent_id']): ?>
                                                                <?php
                                                                $stmt = $pdo->prepare("SELECT name FROM members WHERE id = ?");
                                                                $stmt->execute([$member['parent_id']]);
                                                                $parentName = $stmt->fetchColumn();
                                                                ?>
                                                                <div class="text-info mt-2">
                                                                    <small>Currently linked to: <?php echo htmlspecialchars($parentName); ?></small>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>First Name <span class="text-danger">*</span></label>
                                                        <input type="text" name="first_name" class="form-control" required
                                                               value="<?php echo isset($member) ? $member['first_name'] : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Surname <span class="text-danger">*</span></label>
                                                        <input type="text" name="surname" class="form-control" required
                                                               value="<?php echo isset($member) ? $member['surname'] : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Gender <span class="text-danger">*</span></label>
                                                        <select name="gender" class="form-control" required>
                                                            <option value="">Select Gender</option>
                                                            <option value="Male" <?php echo (isset($member) && $member['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                                            <option value="Female" <?php echo (isset($member) && $member['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Email</label>
                                                        <input type="email" name="email" class="form-control"
                                                               value="<?php echo isset($member) ? $member['email'] : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Profession</label>
                                                        <input type="text" name="profession" class="form-control"
                                                               value="<?php echo isset($member) ? $member['profession'] : ''; ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Home Address</label>
                                                        <input type="text" name="home_address" class="form-control"
                                                               value="<?php echo isset($member) ? $member['home_address'] : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Work Address</label>
                                                        <input type="text" name="work_address" class="form-control"
                                                               value="<?php echo isset($member) ? $member['work_address'] : ''; ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>GPS Number</label>
                                                        <input type="text" name="gps_number" class="form-control"
                                                               value="<?php echo isset($member) ? $member['gps_number'] : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Contact Number 1 <span class="contact-required text-danger">*</span></label>
                                                        <input type="text" name="contact_number_1" class="form-control" 
                                                               value="<?php echo isset($member) ? $member['contact_number_1'] : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="form-group">
                                                        <label>Contact Number 2</label>
                                                        <input type="text" name="contact_number_2" class="form-control"
                                                               value="<?php echo isset($member) ? $member['contact_number_2'] : ''; ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Next of Kin Name</label>
                                                        <input type="text" name="next_of_kin_name" class="form-control"
                                                               value="<?php echo isset($member) ? $member['next_of_kin_name'] : ''; ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Next of Kin Contact Number</label>
                                                        <input type="text" name="next_of_kin_contact_number" class="form-control"
                                                               value="<?php echo isset($member) ? $member['next_of_kin_contact_number'] : ''; ?>">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="card-action">
                                                <button type="submit" class="btn btn-success">
                                                    <i class="fa fa-save"></i> 
                                                    <?php echo isset($member) ? 'Update' : 'Add'; ?> Member
                                                </button>
                                                <a href="view_members.php" class="btn btn-danger">
                                                    <i class="fa fa-times"></i> Cancel
                                                </a>
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
        <!-- Atlantis JS -->
        <script src="../res/assets/js/atlantis.min.js"></script>

        <script>
            $(document).ready(function () {
                function toggleChildOptions() {
                    var selectedOption = $('select[name="member_type_id"] option:selected');
                    var selectedText = selectedOption.text().trim().toLowerCase();

                    if (selectedText === 'child') {
                        $('.child-options-section').show();
                        // Remove required from contact number 1 for child
                        $('input[name="contact_number_1"]').prop('required', false);
                        $('.contact-required').hide();
                    } else {
                        $('.child-options-section').hide();
                        $('#linkParentPhone').prop('checked', false);
                        $('.parent-phone-section').hide();
                        $('input[name="parent_phone"]').prop('required', false);
                        // Make contact number 1 required for non-child
                        $('input[name="contact_number_1"]').prop('required', true);
                        $('.contact-required').show();
                    }
                }

                function toggleParentPhone() {
                    if ($('#linkParentPhone').is(':checked')) {
                        $('.parent-phone-section').show();
                        $('input[name="parent_phone"]').prop('required', true);
                    } else {
                        $('.parent-phone-section').hide();
                        $('input[name="parent_phone"]').prop('required', false);
                    }
                }

                // Initial check
                toggleChildOptions();
                toggleParentPhone();

                // On member type change
                $('select[name="member_type_id"]').change(function () {
                    toggleChildOptions();
                });

                // On checkbox change
                $('#linkParentPhone').change(function () {
                    toggleParentPhone();
                });

                // When parent phone is entered
                $('input[name="parent_phone"]').blur(function () {
                    var phone = $(this).val();
                    if (phone) {
                        // Ajax call to check if parent exists
                        $.ajax({
                            url: 'check_parent.php',
                            method: 'POST',
                            data: {phone: phone},
                            success: function (response) {
                                try {
                                    var result = JSON.parse(response);
                                    if (!result.exists) {
                                        alert('Parent with this phone number not found. Please enter a valid parent\'s phone number.');
                                        $('input[name="parent_phone"]').val('');
                                    }
                                } catch (e) {
                                    console.error('Error parsing response:', e);
                                }
                            },
                            error: function () {
                                alert('Error checking parent phone number');
                            }
                        });
                    }
                });
            });
        </script>
    </body>
</html>