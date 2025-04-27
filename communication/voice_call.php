<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit();
}

// Get current user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$currentUser = $stmt->fetch();

if (!$currentUser) {
    header('Location: ../index.php');
    exit();
}

// Mnotify API Configuration
//define('MNOTIFY_API_KEY', 'BS9AIOkjhtfgQBDQllN9KIJsZVG4D64fZdQH2a0jDgAtS');
$message = '';

// Fetch all member groups for filtering
$stmt = $pdo->query("SELECT DISTINCT member_group FROM members WHERE member_group IS NOT NULL");
$groups = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all member types for filtering
$stmt = $pdo->query("
    SELECT DISTINCT mt.type_name 
    FROM members m 
    JOIN member_types mt ON m.member_type_id = mt.id 
    WHERE m.member_type_id IS NOT NULL
    ORDER BY mt.type_name
");
$memberTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $callTitle = $_POST['call_title'];
        $voiceFile = $_FILES['voice_file'];
        $recipientType = $_POST['recipient_type'];
        $selectedGroups = isset($_POST['groups']) ? $_POST['groups'] : [];
        $selectedTypes = isset($_POST['member_types']) ? $_POST['member_types'] : [];
        $scheduleTime = !empty($_POST['schedule_time']) ? $_POST['schedule_time'] : null;

        // Log the start of voice call process
        $logger->log(
            'voice_call',
            'communication',
            $currentUser['id'],
            $callTitle,
            [
                'call_title' => $callTitle,
                'recipient_type' => $recipientType,
                'selected_groups' => $selectedGroups,
                'selected_types' => $selectedTypes,
                'scheduled_time' => $scheduleTime
            ],
            'pending'
        );

        // Build query based on recipient type and filters
        if ($recipientType === 'all') {
            $query = "SELECT contact_number_1 FROM members WHERE contact_number_1 IS NOT NULL";
        } elseif ($recipientType === 'group') {
            $placeholders = str_repeat('?,', count($selectedGroups) - 1) . '?';
            $query = "SELECT contact_number_1 FROM members WHERE member_group IN ($placeholders) AND contact_number_1 IS NOT NULL";
            $params = $selectedGroups;
        } elseif ($recipientType === 'type') {
            $placeholders = str_repeat('?,', count($selectedTypes) - 1) . '?';
            $query = "
                SELECT m.contact_number_1 
                FROM members m 
                JOIN member_types mt ON m.member_type_id = mt.id 
                WHERE mt.type_name IN ($placeholders) 
                AND m.contact_number_1 IS NOT NULL
            ";
            $params = $selectedTypes;
        }

        // Execute query with appropriate parameters
        $stmt = $pdo->prepare($query);
        if ($recipientType === 'group') {
            $stmt->execute($selectedGroups);
        } elseif ($recipientType === 'type') {
            $stmt->execute($selectedTypes);
        } else {
            $stmt->execute();
        }
        
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($recipients)) {
            // Format phone numbers (remove leading zero and add country code)
            $formattedNumbers = array_map(function($number) {
                $number = preg_replace('/[^0-9]/', '', $number);
                if (strlen($number) > 9) {
                    return '233' . substr($number, -9);
                }
                return $number;
            }, $recipients);

            // Log success
            $logger->log(
                'voice_call',
                'communication',
                null,
                "Voice call sent successfully",
                null,
                [
                    'recipients' => count($formattedNumbers),
                    'call_title' => $callTitle,
                    'scheduled_time' => $scheduleTime
                ],
                'success'
            );

            $message = "<div class='alert alert-success'>
                <h4><i class='fa fa-check'></i> Success!</h4>
                Voice call queued for " . count($formattedNumbers) . " recipients!
                " . ($scheduleTime ? "<br>Scheduled for: " . date('M d, Y H:i', strtotime($scheduleTime)) : "") . "
            </div>";

            // Upload voice file
            $tempFile = $voiceFile['tmp_name'];
            
            // API endpoint and configuration
            $endPoint = 'https://api.mnotify.com/api/voice/quick';
            $url = $endPoint . '?key=' . MNOTIFY_API_KEY;
            
            // Create CURLFile from the uploaded file
            $curlFile = new CURLFile(
                $tempFile,
                $voiceFile['type'],
                $voiceFile['name']
            );
            
            // Format recipients
            $recipientString = implode(',', $formattedNumbers);
            
            // Prepare API data
            $data = [
                'campaign' => $callTitle,
                'recipient' => $recipientString,
                'file' => $curlFile,
                'voice_id' => '',
                'is_schedule' => $scheduleTime ? 'true' : 'false',
                'schedule_date' => $scheduleTime ?? ''
            ];

            // Initialize cURL
            $ch = curl_init();
            
            // Set cURL options
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $data,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_VERBOSE => true
            ]);
            
            // Execute the request
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Check for cURL errors
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                error_log("cURL Error: " . $error);
                $message = "<div class='alert alert-danger'>
                    <h4><i class='fa fa-times'></i> Error!</h4>
                    Failed to send voice call. cURL Error: " . htmlspecialchars($error) . "
                </div>";
            }
            
            curl_close($ch);
            
            // Log raw response for debugging
            error_log("Mnotify API Response: " . $response);
            error_log("HTTP Code: " . $httpCode);
            
            // Decode response
            $responseData = json_decode($response, true);
            
            if ($responseData === null) {
                error_log("JSON Decode Error: " . json_last_error_msg());
            }

            if ($httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success') {
                $message = "<div class='alert alert-success'>
                    <h4><i class='fa fa-check'></i> Success!</h4>
                    Voice call " . ($scheduleTime ? "scheduled" : "sent") . " successfully!
                    " . ($scheduleTime ? "<br>Scheduled for: " . date('M d, Y H:i', strtotime($scheduleTime)) : "") . "
                </div>";
            } else {
                $errorMsg = isset($responseData['message']) ? $responseData['message'] : 
                           (isset($responseData['error']) ? $responseData['error'] : 'Unknown error');
                           
                error_log("Voice Call API Error: " . $errorMsg);
                error_log("Full Response: " . print_r($responseData, true));
                
                $message = "<div class='alert alert-danger'>
                    <h4><i class='fa fa-times'></i> Error!</h4>
                    Failed to send voice call. Please try again.<br>
                    Error: " . htmlspecialchars($errorMsg) . "
                </div>";
            }

            // Insert into voice_calls table
            $stmt = $pdo->prepare("
                INSERT INTO voice_calls (
                    user_id, call_title, voice_file, recipient_filter,
                    status, schedule_time, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $status = ($httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success') 
                ? 'sent' : 'failed';
                
            $stmt->execute([
                $currentUser['id'],
                $callTitle,
                $voiceFile['name'],
                $recipientType === 'group' ? implode(',', $selectedGroups) : 
                    ($recipientType === 'type' ? implode(',', $selectedTypes) : 'all'),
                $status,
                $scheduleTime
            ]);
            
            $voiceCallId = $pdo->lastInsertId();

            // Log recipient details
            if (!empty($memberIds)) {
                $values = array_fill(0, count($memberIds), "(?, ?)");
                $insertQuery = "INSERT INTO voice_call_recipients (voice_call_id, member_id) VALUES " . implode(", ", $values);
                
                $insertParams = [];
                foreach ($memberIds as $memberId) {
                    $insertParams[] = $voiceCallId;
                    $insertParams[] = $memberId;
                }

                $stmt = $pdo->prepare($insertQuery);
                $stmt->execute($insertParams);
            }
        } else {
            // Log when no recipients found
            $logger->log(
                'voice_call',
                'communication',
                null,
                "No recipients found",
                null,
                [
                    'recipient_type' => $recipientType,
                    'selected_groups' => $selectedGroups,
                    'selected_types' => $selectedTypes
                ],
                'failed'
            );
            
            $message = "<div class='alert alert-warning'>
                <h4><i class='fa fa-exclamation-triangle'></i> Warning!</h4>
                No recipients found for the selected criteria.
            </div>";
        }
    } catch (Exception $e) {
        $message = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        
        // Log error
        $logger->log(
            'voice_call',
            'communication',
            $currentUser['id'],
            isset($callTitle) ? $callTitle : null,
            ['error' => $e->getMessage()],
            'error'
        );
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Voice Calls - ChurchEaseSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
  <link rel="icon" href="../loginres/images/favicon/favicon.ico" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
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
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-theme@0.1.0-beta.10/dist/select2-bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        .select2-container {
            width: 100% !important;
        }
        .select2-selection--multiple {
            min-height: 38px !important;
            border: 1px solid #ebedf2 !important;
        }
        .select2-container--default .select2-selection--multiple {
            border-radius: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Voice Calls</h4>
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
                                <a href="#">Communication</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Voice Calls</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Send Voice Call</h4>
                                </div>
                                <div class="card-body">
                                    <?php if($message) echo $message; ?>
                                    <form method="POST" enctype="multipart/form-data">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Call Title</label>
                                                    <input type="text" name="call_title" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Voice File</label>
                                                    <input type="file" name="voice_file" class="form-control" accept="audio/*" required>
                                                    <small class="form-text text-muted">Supported formats: MP3, WAV (max size: 10MB)</small>
                                                </div>
                                                <div class="form-group">
                                                    <label>Select Recipients</label>
                                                    <select name="recipient_type" class="form-control" id="recipientType" required>
                                                        <option value="all">All Members</option>
                                                        <option value="group">Select by Group</option>
                                                        <option value="type">Select by Member Type</option>
                                                    </select>
                                                </div>
                                                <div class="form-group" id="groupSelection" style="display: none;">
                                                    <label>Select Groups</label>
                                                    <select name="groups[]" class="form-control select2" multiple="multiple" style="width: 100%">
                                                        <?php foreach ($groups as $group): ?>
                                                            <option value="<?php echo htmlspecialchars($group); ?>">
                                                                <?php echo htmlspecialchars($group); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group" id="typeSelection" style="display: none;">
                                                    <label>Select Member Types</label>
                                                    <select name="member_types[]" class="form-control select2" multiple="multiple" style="width: 100%">
                                                        <?php foreach ($memberTypes as $type): ?>
                                                            <option value="<?php echo htmlspecialchars($type); ?>">
                                                                <?php echo htmlspecialchars($type); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Schedule Time (Optional)</label>
                                                    <input type="text" name="schedule_time" class="form-control datetimepicker"
                                                           placeholder="Select date and time...">
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-phone"></i> Send Voice Call
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Call History -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Voice Call History</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Title</th>
                                                    <th>Recipients</th>
                                                    <th>Status</th>
                                                    <th>Scheduled</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("
                                                    SELECT vc.*, COUNT(vcr.member_id) as recipient_count,
                                                           vc.call_title as title, 
                                                           vc.schedule_time as scheduled_time,
                                                           vc.status
                                                    FROM voice_calls vc
                                                    LEFT JOIN voice_call_recipients vcr ON vc.id = vcr.voice_call_id
                                                    GROUP BY vc.id
                                                    ORDER BY vc.created_at DESC
                                                    LIMIT 50
                                                ");
                                                while ($call = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>" . date('Y-m-d H:i', strtotime($call['created_at'])) . "</td>";
                                                    echo "<td>" . htmlspecialchars($call['title'] ?? $call['call_title']) . "</td>";
                                                    echo "<td>" . $call['recipient_count'] . " recipients</td>";
                                                    echo "<td><span class='badge badge-" . 
                                                         ($call['status'] === 'sent' ? 'success' : 
                                                          ($call['status'] === 'pending' ? 'warning' : 'danger')) . 
                                                         "'>" . ucfirst($call['status']) . "</span></td>";
                                                    echo "<td>" . ($call['scheduled_time'] ?? $call['schedule_time'] ? 
                                                         date('Y-m-d H:i', strtotime($call['scheduled_time'] ?? $call['schedule_time'])) : '-') . 
                                                         "</td>";
                                                    echo "</tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include '../res/footer.php'; ?>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        $(document).ready(function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap',
                width: '100%',
                placeholder: "Select options...",
                allowClear: true
            });

            // Initialize datetime picker
            $('.datetimepicker').flatpickr({
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today"
            });

            // Handle recipient type selection
            $('#recipientType').change(function() {
                var selectedType = $(this).val();
                if (selectedType === 'group') {
                    $('#groupSelection').show();
                    $('#typeSelection').hide();
                    $('select[name="member_types[]"]').val(null).trigger('change');
                } else if (selectedType === 'type') {
                    $('#groupSelection').hide();
                    $('#typeSelection').show();
                    $('select[name="groups[]"]').val(null).trigger('change');
                } else {
                    $('#groupSelection').hide();
                    $('#typeSelection').hide();
                    $('select[name="groups[]"]').val(null).trigger('change');
                    $('select[name="member_types[]"]').val(null).trigger('change');
                }
            });

            // Form validation
            $('form').submit(function(e) {
                var selectedType = $('#recipientType').val();
                if (selectedType === 'group' && !$('select[name="groups[]"]').val().length) {
                    e.preventDefault();
                    alert('Please select at least one group');
                    return false;
                }
                if (selectedType === 'type' && !$('select[name="member_types[]"]').val().length) {
                    e.preventDefault();
                    alert('Please select at least one member type');
                    return false;
                }
            });

            // File size validation
            $('input[name="voice_file"]').change(function() {
                var file = this.files[0];
                var maxSize = 10 * 1024 * 1024; // 10MB in bytes

                if (file && file.size > maxSize) {
                    alert('File size exceeds 10MB limit. Please choose a smaller file.');
                    this.value = '';
                }
            });
        });
    </script>
</body>
</html>