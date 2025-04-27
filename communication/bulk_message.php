<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Check if user is logged in (using existing auth.php)
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

// Use Mnotify configuration from config.php
$sender = MNOTIFY_SENDER_ID;
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
        $messageTitle = $_POST['message_title'];
        $messageText = $_POST['message'];
        $recipientType = $_POST['recipient_type'];
        $selectedGroups = isset($_POST['groups']) ? $_POST['groups'] : [];
        $selectedTypes = isset($_POST['member_types']) ? $_POST['member_types'] : [];
        $scheduleTime = !empty($_POST['schedule_time']) ? $_POST['schedule_time'] : null;
        
        // Log the start of bulk message process
        $logger->log(
            'bulk_message',
            'communication',
            null,
            "Starting bulk SMS process",
            null,
            [
                'message_title' => $messageTitle,
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
            $number = substr($number, -9); // Get last 9 digits
        }
        return '233' . $number; // Add Ghana country code
    }, $recipients);
    
    // Prepare Mnotify API request
    $endPoint = 'https://api.mnotify.com/api/sms/quick';
    
    $data = [
        'recipient' => $formattedNumbers,
        'sender' => MNOTIFY_SENDER_ID,
        'message' => $messageText,
        'is_schedule' => !empty($scheduleTime) ? 'true' : 'false',
        'schedule_date' => $scheduleTime ?? ''
    ];

 // Initialize cURL
    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, $endPoint);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: ' . MNOTIFY_API_KEY
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Check for cURL errors
    if (curl_errno($ch)) {
        throw new Exception('Curl error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    // Decode response
    $responseData = json_decode($response, true);
    
    // If message was sent successfully, create notifications for each recipient
    if ($httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success') {
        // Get member IDs based on recipient type
        if ($recipientType === 'all') {
            $memberStmt = $pdo->query("SELECT id FROM members WHERE contact_number_1 IS NOT NULL");
        } elseif ($recipientType === 'group') {
            $placeholders = str_repeat('?,', count($selectedGroups) - 1) . '?';
            $memberStmt = $pdo->prepare("SELECT id FROM members WHERE member_group IN ($placeholders) AND contact_number_1 IS NOT NULL");
            $memberStmt->execute($selectedGroups);
        } elseif ($recipientType === 'type') {
            $placeholders = str_repeat('?,', count($selectedTypes) - 1) . '?';
            $memberStmt = $pdo->prepare("
                SELECT m.id 
                FROM members m 
                JOIN member_types mt ON m.member_type_id = mt.id 
                WHERE mt.type_name IN ($placeholders) 
                AND m.contact_number_1 IS NOT NULL
            ");
            $memberStmt->execute($selectedTypes);
        }
        
        $memberIds = $memberStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Prepare notification insert statement
        $notificationStmt = $pdo->prepare("INSERT INTO notifications (member_id, notification_text) VALUES (?, ?)");
        
        // Insert notification for each member
        foreach ($memberIds as $memberId) {
            $notificationStmt->execute([$memberId, $messageText]);
        }
    }
    
    // Store message in database
    $stmt = $pdo->prepare("INSERT INTO messages (
        sender_id, 
        message_type, 
        message_title, 
        message, 
        recipient_group, 
        status, 
        api_response, 
        scheduled_time,
        recipient_count
    ) VALUES (?, 'bulk', ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->execute([
        $currentUser['id'],
        $messageTitle,
        $messageText,
        $recipientType === 'group' ? implode(',', $selectedGroups) : ($recipientType === 'type' ? implode(',', $selectedTypes) : 'all'),
        $httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success' ? 'sent' : 'failed',
        $response,
        $scheduleTime,
        count($formattedNumbers)
    ]);
    
    $messageId = $pdo->lastInsertId();
    
    // Log the result
    $logger->log(
        'bulk_message',
        'communication',
        $messageId,
        $httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success' 
            ? "Bulk SMS sent successfully" 
            : "Bulk SMS failed",
        null,
        [
            'recipient_count' => count($formattedNumbers),
            'http_code' => $httpCode,
            'api_response' => $response,
            'scheduled_time' => $scheduleTime
        ],
        $httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success' 
            ? 'success' 
            : 'failed'
    );
    
    if ($httpCode === 200 && isset($responseData['status']) && $responseData['status'] === 'success') {
        $message = "<div class='alert alert-success'>
            <h4><i class='fa fa-check'></i> Success!</h4>
            Message sent successfully to " . count($formattedNumbers) . " recipients!
            " . ($scheduleTime ? "<br>Scheduled for: " . date('M d, Y H:i', strtotime($scheduleTime)) : "") . "
        </div>";
    } else {
        $message = "<div class='alert alert-danger'>
            <h4><i class='fa fa-times'></i> Error!</h4>
            Failed to send message. Error: " . 
            (isset($responseData['message']) ? $responseData['message'] : 'Unknown error') . 
        "</div>";
    }
} else {
    // Log when no recipients found
    $logger->log(
        'bulk_message',
        'communication',
        null,
        "No recipients found for bulk SMS",
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
        // Log any errors that occur
        $logger->log(
            'bulk_message',
            'communication',
            null,
            "Error in bulk SMS process",
            null,
            ['error' => $e->getMessage()],
            'failed'
        );
        
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title>Bulk Messaging - ChurchEaseSuperb</title>
        <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
         <link rel="icon" href="../loginres/images/favicon/favicon.ico" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
    <link rel="manifest" href="../loginres/images/favicon/site.webmanifest">
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
                        <h4 class="page-title">Bulk Messages</h4>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Send Bulk Message</h4>
                                </div>
                                <div class="card-body">
                                    <?php if($message) echo $message; ?>
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Message Title</label>
                                                    <input type="text" name="message_title" class="form-control" required>
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
                                                    <select name="groups[]" class="form-control" multiple>
                                                        <?php foreach ($groups as $group): ?>
                                                            <option value="<?php echo htmlspecialchars($group); ?>">
                                                                <?php echo htmlspecialchars($group); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="form-group" id="typeSelection" style="display: none;">
                                                    <label>Select Member Types</label>
                                                    <select name="member_types[]" class="form-control" multiple>
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
                                            <div class="col-md-12">
                                                <div class="form-group">
                                                    <label>Message</label>
                                                    <textarea name="message" class="form-control" rows="5" required
                                                              placeholder="Enter your message..."></textarea>
                                                    <small class="text-muted">
                                                        Character count: <span id="charCount">0</span>
                                                        Messages: <span id="messageCount">1</span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-paper-plane"></i> Send Message
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <!-- Message History -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Message History</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Title</th>
                                                    <th>Recipients</th>
                                                    <th>Message</th>
                                                    <th>Status</th>
                                                    <th>Sent Time</th>
                                                    <th>Scheduled Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("SELECT * FROM messages WHERE message_type = 'bulk' 
                                                                    ORDER BY sent_time DESC LIMIT 50");
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row['message_title']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['recipient_group']) . "</td>";
                                                    echo "<td>" . htmlspecialchars(substr($row['message'], 0, 50)) . "...</td>";
                                                    echo "<td><span class='badge badge-" . 
                                                         ($row['status'] === 'sent' ? 'success' : 
                                                          ($row['status'] === 'pending' ? 'warning' : 'danger')) . 
                                                         "'>" . ucfirst($row['status']) . "</span></td>";
                                                    echo "<td>" . date('M d, Y H:i', strtotime($row['sent_time'])) . "</td>";
                                                    echo "<td>" . ($row['scheduled_time'] ? 
                                                         date('M d, Y H:i', strtotime($row['scheduled_time'])) : '-') . 
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

        <!-- Core JS Files -->
        <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
        <script src="../res/assets/js/core/bootstrap.min.js"></script>
        <script src="../res/assets/js/atlantis.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <script>
            $(document).ready(function() {
                // Initialize Select2
                $('.select2').select2({
                    theme: 'bootstrap'
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
                    } else if (selectedType === 'type') {
                        $('#groupSelection').hide();
                        $('#typeSelection').show();
                    } else {
                        $('#groupSelection').hide();
                        $('#typeSelection').hide();
                    }
                });

                // Character counter
                $('textarea[name="message"]').on('input', function() {
                    const text = $(this).val();
                    const charCount = text.length;
                    const messageCount = Math.ceil(charCount / 160);
                    
                    $('#charCount').text(charCount);
                    $('#messageCount').text(messageCount);
                });
            });
        </script>
    </body>
</html>