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

// WhatsApp API Configuration
define('WHATSAPP_API_TOKEN', 'EACBq8ZB43LPQBO3dZAUcOJovgr91vAQIXpQIayfKOC4sV4RZCXkabhK2TIF7dqBvCeiWRO0ZB4HtqPmQXsuCAjZASSpdgFDzgRfbPnZAl39JxC7WpzDlsbvtKWpXSmxNGnzrxykMkYEHeVGS2Asf1LhNwixeugYAg5kwHoQg7ODQoS6RweQecQ4zNehbG6CiayueFnoAVEBZCZCHRdXevshUGi3bVZCMZD');
define('WHATSAPP_PHONE_NUMBER_ID', '568742602979392');

$message = '';

// Function to validate phone numbers
function isValidGhanaianPhoneNumber($number) {
    // Remove any non-numeric characters
    $number = preg_replace('/[^0-9]/', '', $number);
    
    // Check if it already has country code
    if (substr($number, 0, 3) === '233') {
        return strlen($number) === 12; // 233 + 9 digits
    }
    
    // Check if it's a valid local number (9 digits after removing leading zero)
    $number = ltrim($number, '0');
    return strlen($number) === 9;
}

// Function to format phone number
function formatPhoneNumber($number) {
    // Remove any non-numeric characters
    $number = preg_replace('/[^0-9]/', '', $number);
    
    // Remove leading zeros
    $number = ltrim($number, '0');
    
    // Check if number already has country code
    if (substr($number, 0, 3) === '233') {
        return $number;
    }
    
    // Add country code if number is valid
    if (strlen($number) === 9) {
        return '233' . $number;
    }
    
    return null;
}

// Function to verify WhatsApp number
function verifyWhatsAppNumber($recipient, $token, $phoneNumberId, $logger) {
    $verificationUrl = "https://graph.facebook.com/v21.0/" . $phoneNumberId . "/messages";
    $verificationData = [
        "messaging_product" => "whatsapp",
        "to" => $recipient,
        "type" => "text",
        "text" => [
            "preview_url" => false,
            "body" => "👋 Verification message"
        ]
    ];

    $ch = curl_init($verificationUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verificationData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $responseData = json_decode($response, true);
    curl_close($ch);

    // Log verification attempt
    $logger->log(
        'whatsapp_message',
        'communication',
        "Number verification attempt for {$recipient}:\n" .
        "Response Code: {$httpCode}\n" .
        "Response: " . json_encode($responseData, JSON_PRETTY_PRINT),
        $_SESSION['user_id']
    );

    return [
        'success' => ($httpCode === 200 && isset($responseData['messages'][0]['id'])),
        'response' => $responseData,
        'httpCode' => $httpCode
    ];
}

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
        $selectedGroup = isset($_POST['member_group']) ? $_POST['member_group'] : '';
        $selectedType = isset($_POST['member_type']) ? $_POST['member_type'] : '';

        // Build query based on filters
        $query = "SELECT contact_number_1 FROM members WHERE contact_number_1 IS NOT NULL AND contact_number_1 != ''";
        $params = [];

        if ($selectedGroup) {
            $query .= " AND member_group = ?";
            $params[] = $selectedGroup;
        }

        if ($selectedType) {
            $query .= " AND member_type_id = (SELECT id FROM member_types WHERE type_name = ?)";
            $params[] = $selectedType;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($recipients)) {
            // Format and validate phone numbers
            $formattedNumbers = array_map(function($number) use ($logger) {
                $formatted = formatPhoneNumber($number);
                if ($formatted === null) {
                    $logger->log(
                        'whatsapp_message',
                        'communication',
                        "Invalid phone number format: {$number}",
                        $_SESSION['user_id']
                    );
                }
                return $formatted;
            }, $recipients);

            // Remove invalid numbers
            $formattedNumbers = array_filter($formattedNumbers);

            if (empty($formattedNumbers)) {
                throw new Exception("No valid phone numbers found in the selected recipients.");
            }

            $successCount = 0;
            $failCount = 0;
            $verificationFailures = 0;

            foreach ($formattedNumbers as $recipient) {
                // Verify WhatsApp number
                $verification = verifyWhatsAppNumber($recipient, WHATSAPP_API_TOKEN, WHATSAPP_PHONE_NUMBER_ID, $logger);
                
                if (!$verification['success']) {
                    $verificationFailures++;
                    continue;
                }

                $url = "https://graph.facebook.com/v21.0/" . WHATSAPP_PHONE_NUMBER_ID . "/messages";
                
                $data = [
                    "messaging_product" => "whatsapp",
                    "to" => $recipient,
                    "type" => "text",
                    "text" => [
                        "preview_url" => false,
                        "body" => $messageText
                    ]
                ];

                // Log the request payload
                $logger->log(
                    'whatsapp_message',
                    'communication',
                    "Sending message to {$recipient}. Payload: " . json_encode($data, JSON_PRETTY_PRINT),
                    $_SESSION['user_id']
                );

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . WHATSAPP_API_TOKEN,
                    'Content-Type: application/json'
                ]);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $responseData = json_decode($response, true);
                $curlError = curl_error($ch);
                curl_close($ch);

                // Log complete response
                $logger->log(
                    'whatsapp_message',
                    'communication',
                    "API Response for {$recipient}:\n" .
                    "HTTP Code: {$httpCode}\n" .
                    "Response: " . $response . "\n" .
                    "cURL Error: " . ($curlError ?: 'None'),
                    $_SESSION['user_id']
                );

                if ($httpCode === 200 && isset($responseData['messages'][0]['id'])) {
                    $successCount++;
                    $messageId = $responseData['messages'][0]['id'];

                    // Store message in database
                    try {
                        $stmt = $pdo->prepare("
                            INSERT INTO whatsapp_messages 
                            (recipient_number, message_id, message_content, status, response_data, created_at) 
                            VALUES (?, ?, ?, 'SENT', ?, NOW())
                        ");
                        $stmt->execute([
                            $recipient,
                            $messageId,
                            $messageText,
                            json_encode($responseData)
                        ]);
                    } catch (Exception $e) {
                        $logger->log(
                            'whatsapp_message',
                            'communication',
                            "Failed to store message in database: " . $e->getMessage(),
                            $_SESSION['user_id']
                        );
                    }
                } else {
                    $failCount++;
                    $errorMessage = "Failed to send WhatsApp message to {$recipient}. ";
                    $errorMessage .= "HTTP Code: {$httpCode}. ";
                    
                    if (!empty($curlError)) {
                        $errorMessage .= "cURL Error: {$curlError}. ";
                    }
                    
                    if (isset($responseData['error'])) {
                        $errorMessage .= "API Error: " . json_encode($responseData['error']) . ". ";
                    }
                    
                    $logger->log(
                        'whatsapp_message',
                        'communication',
                        $errorMessage,
                        $_SESSION['user_id']
                    );
                }
            }

            // Log final summary
            $logger->log(
                'whatsapp_message',
                'communication',
                "WhatsApp message batch summary:\n" .
                "Title: '{$messageTitle}'\n" .
                "Total attempts: " . count($formattedNumbers) . "\n" .
                "Successful: {$successCount}\n" .
                "Failed: {$failCount}\n" .
                "Verification Failures: {$verificationFailures}",
                $_SESSION['user_id']
            );

            $message = "<div class='alert alert-info'>
                <h4><i class='fa fa-info-circle'></i> Message Status</h4>
                <p>Message sent successfully to {$successCount} recipients.</p>
                " . ($failCount > 0 ? "<p>Failed to send to {$failCount} recipients.</p>" : "") . "
                " . ($verificationFailures > 0 ? "<p>{$verificationFailures} numbers failed WhatsApp verification.</p>" : "") . "
                <p>Check the audit logs for detailed information.</p>
            </div>";

        } else {
            $message = "<div class='alert alert-warning'>No recipients found matching the selected criteria.</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        $logger->log(
            'whatsapp_message',
            'communication',
            'Error in WhatsApp messaging process: ' . $e->getMessage(),
            $_SESSION['user_id']
        );
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title>Whatsapp Messaging - ChurchEase</title>
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
    </head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">WhatsApp Messages</h4>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Send WhatsApp Message</h4>
                                </div>
                                <div class="card-body">
                                    <?php if($message) echo $message; ?>
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Message Title</label>
                                                    <input type="text" class="form-control" name="message_title" required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Member Group</label>
                                                    <select class="form-control" name="member_group">
                                                        <option value="">All Groups</option>
                                                        <?php foreach($groups as $group): ?>
                                                            <option value="<?php echo htmlspecialchars($group); ?>">
                                                                <?php echo htmlspecialchars($group); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Member Type</label>
                                                    <select class="form-control" name="member_type">
                                                        <option value="">All Types</option>
                                                        <?php foreach($memberTypes as $type): ?>
                                                            <option value="<?php echo htmlspecialchars($type); ?>">
                                                                <?php echo htmlspecialchars($type); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Message</label>
                                            <textarea class="form-control" name="message" rows="4" required></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Send Message</button>
                                    </form>
                                </div>
                            </div>

                            <!-- Recent Messages -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Recent Messages</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Recipient</th>
                                                    <th>Message</th>
                                                    <th>Status</th>
                                                    <th>Sent At</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("
                                                    SELECT * FROM whatsapp_messages 
                                                    ORDER BY created_at DESC 
                                                    LIMIT 10
                                                ");
                                                while ($row = $stmt->fetch()) {
                                                    echo "<tr>";
                                                    echo "<td>" . htmlspecialchars($row['recipient_number']) . "</td>";
                                                    echo "<td>" . htmlspecialchars(substr($row['message_content'], 0, 50)) . "...</td>";
                                                    echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
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
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/ready.min.js"></script>
</body>
</html>