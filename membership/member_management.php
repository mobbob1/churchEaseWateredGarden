<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check permissions
$canManageMembers = checkPermission('manage_members');
$canAddMembers = checkPermission('add_members');
$canViewMembers = checkPermission('view_member');

if (!$canManageMembers && !$canAddMembers && !$canViewMembers) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Set page mode based on permissions
$readOnly = !$canManageMembers && !$canAddMembers;

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

// Fetch member types for the dropdown
try {
    $stmt = $pdo->query("SELECT * FROM member_types ORDER BY type_name");
    $member_types = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log($e->getMessage());
    $member_types = [];
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $member_type_id = $_POST['member_type_id'] ?? null;
    
    // Get member type name
    $stmt = $pdo->prepare("SELECT type_name FROM member_types WHERE id = ?");
    $stmt->execute([$member_type_id]);
    $member_type = $stmt->fetchColumn();
    $is_visitor = strtolower($member_type) === 'visitor';
    $is_child = strtolower($member_type) === 'child';

    // Get form data
    if ($is_visitor) {
        // Process visitor form
        $name = $_POST['visitor_name'] ?? '';
        $contact_number = $_POST['visitor_contact'] ?? '';
        $address = $_POST['visitor_address'] ?? '';
        $reason = $_POST['visitor_reason'] ?? '';
        $gender = $_POST['visitor_gender'] ?? '';
    } else {
        // Process regular member form
        $first_name = $_POST['first_name'] ?? '';
        $surname = $_POST['surname'] ?? '';
        $name = $first_name . ' ' . $surname;
        $email = $_POST['email'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $profession = $_POST['profession'] ?? '';
        $home_address = $_POST['home_address'] ?? '';
        $work_address = $_POST['work_address'] ?? '';
        $gps_number = $_POST['gps_number'] ?? '';
        $contact_number = $_POST['contact_number_1'] ?? '';
        $contact_number_2 = $_POST['contact_number_2'] ?? '';
        $next_of_kin_name = $_POST['next_of_kin_name'] ?? '';
        $next_of_kin_contact_number = $_POST['next_of_kin_contact_number'] ?? '';
        $bible_class_id = $_POST['bible_class_id'] ?? null;
        $organization_id = $_POST['organization_id'] ?? null;
    }

    try {
        // Check if contact number already exists (except for child members)
        if (!$is_child && !empty($contact_number)) {
            $check_contact = $pdo->prepare("SELECT id, name FROM members WHERE contact_number_1 = ? AND id != ?");
            $check_contact->execute([$contact_number, $_POST['id'] ?? 0]);
            $existing_member = $check_contact->fetch();

            if ($existing_member) {
                throw new Exception("Contact number already exists for member: " . $existing_member['name']);
            }
        }

        if (isset($_POST['id'])) {
            // Update existing member
            if ($is_visitor) {
                $sql = "UPDATE members SET 
                        name = ?, 
                        contact_number_1 = ?, 
                        home_address = ?, 
                        notes = ?,
                        gender = ?,
                        member_type_id = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $params = [$name, $contact_number, $address, $reason, $gender, $member_type_id, $_POST['id']];
                $stmt->execute($params);
            } else {
                $sql = "UPDATE members SET 
                        name = ?, first_name = ?, surname = ?, email = ?, 
                        gender = ?, profession = ?, home_address = ?, 
                        work_address = ?, gps_number = ?, contact_number_1 = ?, 
                        contact_number_2 = ?, next_of_kin_name = ?, 
                        next_of_kin_contact_number = ?, bible_class_id = ?, first_organization = ?, member_type_id = ?
                        WHERE id = ?";
                $stmt = $pdo->prepare($sql);
                $params = [
                    $name, $first_name, $surname, $email, $gender, 
                    $profession, $home_address, $work_address, $gps_number, 
                    $contact_number, $contact_number_2, $next_of_kin_name, 
                    $next_of_kin_contact_number, $bible_class_id, $organization_id, $member_type_id, $_POST['id']
                ];
                $stmt->execute($params);
            }
            
            $message = "<div class='alert alert-success'>Member updated successfully!</div>";
            $logger->log('member_update', 'members', $_POST['id'], "Updated member: $name", $_SESSION['user_id'], $params);
        } else {
            // Insert new member
            if ($is_visitor) {
                $sql = "INSERT INTO members (
                        name, contact_number_1, home_address, notes, gender, member_type_id
                    ) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $params = [$name, $contact_number, $address, $reason, $gender, $member_type_id];
                $stmt->execute($params);
            } else {
                $sql = "INSERT INTO members (
                        name, first_name, surname, email, gender, profession, 
                        home_address, work_address, gps_number, contact_number_1, 
                        contact_number_2, next_of_kin_name, next_of_kin_contact_number, 
                        bible_class_id, first_organization, member_type_id
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $pdo->prepare($sql);
                $params = [
                    $name, $first_name, $surname, $email, $gender, 
                    $profession, $home_address, $work_address, $gps_number, 
                    $contact_number, $contact_number_2, $next_of_kin_name, 
                    $next_of_kin_contact_number, $bible_class_id, $organization_id, $member_type_id
                ];
                $stmt->execute($params);
            }
            
            $member_id = $pdo->lastInsertId();
            $message = "<div class='alert alert-success'>Member added successfully!</div>";
            $logger->log('member_create', 'members', $member_id, "Added new member: $name", $_SESSION['user_id'], $params);
        }
    } catch (Exception $e) {
        error_log($e->getMessage());
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        $logger->log('member_create', 'members', null, "Failed to add member: $name", $_SESSION['user_id'], null, 'failed', $e->getMessage());
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
                                        <form method="POST" id="memberForm">
                                            <?php if (isset($member)) : ?>
                                                <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                            <?php endif; ?>
                                            
                                            <?php if ($readOnly): ?>
                                            <div class="alert alert-info">
                                                You are in view-only mode. Contact an administrator if you need to make changes.
                                            </div>
                                            <?php endif; ?>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Member Type <span class="text-danger">*</span></label>
                                                        <select name="member_type_id" id="memberTypeSelect" class="form-control" required>
                                                            <option value="">Select Member Type</option>
                                                            <?php foreach ($member_types as $type): ?>
                                                                <option value="<?php echo $type['id']; ?>" 
                                                                    <?php echo (isset($member) && $member['member_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($type['type_name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Parent Phone Field -->
                                            <div class="row" id="parentPhoneField" style="display: none;">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Parent's Phone Number</label>
                                                        <input type="text" name="parent_phone" class="form-control"
                                                               value="<?php echo isset($member) && $member['parent_id'] ? (function() use ($pdo, $member) {
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
                                                        <div class="custom-control custom-checkbox mt-2">
                                                            <input type="checkbox" class="custom-control-input" id="linkParent" name="link_parent_phone" value="1">
                                                            <label class="custom-control-label" for="linkParent">Link to parent member</label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Bible Class and Organization Fields -->
                                            <div class="row" id="additionalFields" style="display: none;">
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Bible Class</label>
                                                        <select name="bible_class_id" class="form-control">
                                                            <option value="">Select Bible Class</option>
                                                            <?php
                                                            try {
                                                                $stmt = $pdo->query("SELECT id, class_name FROM bible_classes ORDER BY class_name");
                                                                while ($class = $stmt->fetch()) {
                                                                    $selected = (isset($member) && $member['bible_class_id'] == $class['id']) ? 'selected' : '';
                                                                    echo "<option value='" . $class['id'] . "' $selected>" . htmlspecialchars($class['class_name']) . "</option>";
                                                                }
                                                            } catch (PDOException $e) {
                                                                error_log($e->getMessage());
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>Organization</label>
                                                        <select name="organization_id" class="form-control">
                                                            <option value="">Select Organization</option>
                                                            <?php
                                                            try {
                                                                $stmt = $pdo->query("SELECT id, organization_name FROM organizations ORDER BY organization_name");
                                                                while ($org = $stmt->fetch()) {
                                                                    $selected = (isset($member) && $member['organization_id'] == $org['id']) ? 'selected' : '';
                                                                    echo "<option value='" . $org['id'] . "' $selected>" . htmlspecialchars($org['organization_name']) . "</option>";
                                                                }
                                                            } catch (PDOException $e) {
                                                                error_log($e->getMessage());
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Visitor Fields -->
                                            <div id="visitorFields" style="display: none;">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Full Name <span class="text-danger">*</span></label>
                                                            <input type="text" name="visitor_name" class="form-control visitor-field" required
                                                                value="<?php echo isset($member) ? htmlspecialchars($member['name']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Contact Number <span class="text-danger">*</span></label>
                                                            <input type="text" name="visitor_contact" class="form-control visitor-field" required
                                                                value="<?php echo isset($member) ? htmlspecialchars($member['contact_number_1']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Gender <span class="text-danger">*</span></label>
                                                            <select name="visitor_gender" class="form-control visitor-field" required>
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
                                                            <label>Address (Residential/Work)</label>
                                                            <textarea name="visitor_address" class="form-control visitor-field" rows="3"><?php echo isset($member) ? htmlspecialchars($member['home_address']) : ''; ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Reason for Visit <span class="text-danger">*</span></label>
                                                            <textarea name="visitor_reason" class="form-control visitor-field" rows="3" required><?php echo isset($member) ? htmlspecialchars($member['notes']) : ''; ?></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Regular Member Fields -->
                                            <div id="regularMemberFields">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>First Name <span class="text-danger">*</span></label>
                                                            <input type="text" name="first_name" class="form-control regular-field" 
                                                                value="<?php echo isset($member) ? htmlspecialchars($member['first_name']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Surname <span class="text-danger">*</span></label>
                                                            <input type="text" name="surname" class="form-control regular-field"
                                                                value="<?php echo isset($member) ? htmlspecialchars($member['surname']) : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Gender <span class="text-danger">*</span></label>
                                                            <select name="gender" class="form-control regular-field" required>
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
                                                            <label>Email <span class="email-required text-danger" style="display: none;">*</span></label>
                                                            <input type="email" name="email" class="form-control regular-field"
                                                                   value="<?php echo isset($member) ? $member['email'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Profession</label>
                                                            <input type="text" name="profession" id="professionField" class="form-control regular-field"
                                                                   value="<?php echo isset($member) ? $member['profession'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Home Address</label>
                                                            <input type="text" name="home_address" class="form-control regular-field"
                                                                   value="<?php echo isset($member) ? $member['home_address'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label id="workAddressLabel">Work Address</label>
                                                            <input type="text" name="work_address" class="form-control regular-field"
                                                                   value="<?php echo isset($member) ? $member['work_address'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>GPS Number</label>
                                                            <input type="text" name="gps_number" class="form-control regular-field"
                                                                   value="<?php echo isset($member) ? $member['gps_number'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Contact Number 1 <span class="text-danger">*</span></label>
                                                            <input type="text" name="contact_number_1" id="contactNumber1" class="form-control regular-field" required
                                                                   value="<?php echo isset($member) ? $member['contact_number_1'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="form-group">
                                                            <label>Contact Number 2</label>
                                                            <input type="text" name="contact_number_2" id="contactNumber2" class="form-control regular-field"
                                                                   value="<?php echo isset($member) ? $member['contact_number_2'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Next of Kin Name</label>
                                                            <input type="text" name="next_of_kin_name" class="form-control regular-field"
                                                                   value="<?php echo isset($member) ? $member['next_of_kin_name'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-group">
                                                            <label>Next of Kin Contact Number</label>
                                                            <input type="text" name="next_of_kin_contact_number" class="form-control regular-field"
                                                                   value="<?php echo isset($member) ? $member['next_of_kin_contact_number'] : ''; ?>">
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="card-action">
                                                <?php if (!$readOnly): ?>
                                                <button type="submit" class="btn btn-success">Submit</button>
                                                <?php endif; ?>
                                                <a href="view_members.php" class="btn btn-danger">Back</a>
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
            $(document).ready(function() {
                function toggleFields() {
                    var selectedType = $("#memberTypeSelect option:selected").text().trim().toLowerCase();
                    
                    // Hide both sections initially
                    $("#parentPhoneField, #additionalFields").hide();
                    
                    if (selectedType === 'child') {
                        $("#parentPhoneField").show();
                        $("#workAddressLabel").text("School Address");
                        $("#professionField").val("Student").prop('readonly', true);
                        $("input[name='email']").prop('required', false);
                        $(".email-required").hide();
                        
                        // Store current contact numbers if they exist
                        var contact1 = $("#contactNumber1").val();
                        var contact2 = $("#contactNumber2").val();
                        
                        // Only set to parent's number if the fields are empty
                        if (!contact1 && !contact2) {
                            $("#contactNumber1").val($("#parentPhone").val());
                        }
                    } else {
                        $("#workAddressLabel").text("Work Address");
                        $("#professionField").prop('readonly', false);
                        if (selectedType !== 'visitor') {
                            $("#additionalFields").show();
                            $("input[name='email']").prop('required', true);
                            $(".email-required").show();
                        }
                        
                        // Clear profession if it's "Student" and type is changed from child
                        if ($("#professionField").val() === "Student") {
                            $("#professionField").val("");
                        }
                    }
                }

                // Initial toggle
                toggleFields();

                // Toggle on member type change
                $("#memberTypeSelect").on('change', toggleFields);

                // Update contact numbers when parent phone changes
                $("#parentPhone").on('change', function() {
                    var selectedType = $("#memberTypeSelect option:selected").text().trim().toLowerCase();
                    if (selectedType === 'child') {
                        // Only update if contact fields are empty
                        if (!$("#contactNumber1").val() && !$("#contactNumber2").val()) {
                            $("#contactNumber1").val($(this).val());
                        }
                    }
                });

                // Function to toggle fields based on member type
                function toggleVisitorFields() {
                    var selectedType = $("#memberTypeSelect option:selected").text().trim();
                    
                    if (selectedType.toLowerCase() === 'visitor') {
                        $("#visitorFields").show();
                        $("#regularMemberFields").hide();
                        // Make visitor fields required
                        $(".visitor-field").prop('required', true);
                        // Make regular fields not required
                        $(".regular-field").prop('required', false);
                        // Copy gender if editing
                        if ($("select[name='gender']").val()) {
                            $("select[name='visitor_gender']").val($("select[name='gender']").val());
                        }
                    } else {
                        $("#visitorFields").hide();
                        $("#regularMemberFields").show();
                        // Make visitor fields not required
                        $(".visitor-field").prop('required', false);
                        // Make regular fields required
                        $(".regular-field").prop('required', true);
                    }
                }

                // Initial toggle on page load
                toggleVisitorFields();

                // Toggle on member type change
                $("#memberTypeSelect").on('change', toggleVisitorFields);

                // Form submission handler
                $("#memberForm").on('submit', function(e) {
                    var selectedType = $("#memberTypeSelect option:selected").text().trim();
                    if (selectedType.toLowerCase() === 'visitor') {
                        // For visitors, copy the values to the main fields
                        $("input[name='first_name']").val($("input[name='visitor_name']").val());
                        $("input[name='surname']").val('');
                        $("select[name='gender']").val($("select[name='visitor_gender']").val());
                        $("input[name='contact_number_1']").val($("input[name='visitor_contact']").val());
                        $("textarea[name='home_address']").val($("textarea[name='visitor_address']").val());
                        $("textarea[name='notes']").val($("textarea[name='visitor_reason']").val());
                    }
                });

                // Add debug logging for gender field
                console.log("Gender field exists:", $("select[name='visitor_gender']").length > 0);
                console.log("Gender field HTML:", $("#visitorFields").html());

                // Function to fetch parent details
                function fetchParentDetails(phone) {
                    if (phone) {
                        $.ajax({
                            url: 'lookup_parent.php',
                            method: 'POST',
                            data: { phone: phone, fetch_details: true },
                            success: function(response) {
                                var data = JSON.parse(response);
                                if (data.success) {
                                    $("#parentName").val(data.name);
                                    $("#parentId").val(data.id);
                                    
                                    // Fill in parent's data
                                    $("#emailField").val(data.email);
                                    $("#homeAddressField").val(data.home_address);
                                    $("#gpsNumberField").val(data.gps_number);
                                    $("#contactNumber1").val(data.contact_number_1);
                                    $("#contactNumber2").val(data.contact_number_2);
                                    $("#nextOfKinNameField").val(data.next_of_kin_name);
                                    $("#nextOfKinContactField").val(data.next_of_kin_contact_number);
                                    
                                    // Make fields readonly
                                    $(".parent-data").prop('readonly', true);
                                } else {
                                    alert("No member found with this phone number");
                                    $("#parentName").val('');
                                    $("#parentId").val('');
                                    // Clear and enable parent-data fields
                                    $(".parent-data").val('').prop('readonly', false);
                                }
                            },
                            error: function() {
                                alert("Error looking up parent member");
                            }
                        });
                    }
                }

                function toggleFields() {
                    var selectedType = $("#memberTypeSelect option:selected").text().trim().toLowerCase();
                    
                    // Hide both sections initially
                    $("#parentPhoneField, #additionalFields").hide();
                    
                    if (selectedType === 'child') {
                        $("#parentPhoneField").show();
                        $("#workAddressLabel").text("School Address");
                        $("#professionField").val("Student").prop('readonly', true);
                        
                        // If parent is already selected, make fields readonly
                        if ($("#parentId").val()) {
                            $(".parent-data").prop('readonly', true);
                        }
                    } else {
                        $("#workAddressLabel").text("Work Address");
                        $("#professionField").prop('readonly', false);
                        $(".parent-data").prop('readonly', false);
                        
                        if (selectedType !== 'visitor') {
                            $("#additionalFields").show();
                        }
                        
                        // Clear profession if it's "Student"
                        if ($("#professionField").val() === "Student") {
                            $("#professionField").val("");
                        }
                    }
                }

                // Initial toggle
                toggleFields();

                // Toggle on member type change
                $("#memberTypeSelect").on('change', function() {
                    toggleFields();
                    if ($(this).find("option:selected").text().trim().toLowerCase() !== 'child') {
                        // Clear and enable parent-data fields when changing from child
                        $(".parent-data").val('').prop('readonly', false);
                    }
                });

                // Fetch parent details when parent phone changes
                $("#parentPhone").on('change', function() {
                    var selectedType = $("#memberTypeSelect option:selected").text().trim().toLowerCase();
                    if (selectedType === 'child') {
                        fetchParentDetails($(this).val());
                    }
                });
            });
        </script>
    </body>
</html>