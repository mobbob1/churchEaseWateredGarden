<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// PHPMailer classes
require_once '../libraries/PHPMailer/src/PHPMailer.php';
require_once '../libraries/PHPMailer/src/SMTP.php';
require_once '../libraries/PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
if (!in_array($_SESSION['user_role'], ['admin', 'secretary'])) {
    header('Location: /outpouringcrm/index.php?error=unauthorized');
    exit();
}

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

// Get email templates
$stmt = $pdo->query("SELECT * FROM email_templates ORDER BY template_name");
$templates = $stmt->fetchAll();

// Get member groups for filtering
$stmt = $pdo->query("SELECT * FROM member_types ORDER BY type_name");
$memberTypes = $stmt->fetchAll();

$stmt = $pdo->query("SELECT * FROM bible_classes ORDER BY class_name");
$bibleClasses = $stmt->fetchAll();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $recipientType = $_POST['recipient_type'] ?? '';
        $recipients = [];
        
        // Get recipients based on selection
        switch ($recipientType) {
            case 'all':
                $stmt = $pdo->query("SELECT email FROM members WHERE email IS NOT NULL AND email != ''");
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                break;
                
            case 'member_type':
                $typeId = $_POST['member_type_id'];
                $stmt = $pdo->prepare("SELECT email FROM members WHERE member_type_id = ? AND email IS NOT NULL AND email != ''");
                $stmt->execute([$typeId]);
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                break;
                
            case 'bible_class':
                $classId = $_POST['bible_class_id'];
                $stmt = $pdo->prepare("SELECT email FROM members WHERE bible_class_id = ? AND email IS NOT NULL AND email != ''");
                $stmt->execute([$classId]);
                $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN);
                break;
                
            case 'specific':
                $recipients = array_filter(explode(',', $_POST['specific_emails']));
                $recipients = array_map('trim', $recipients);
                break;
        }
        
        if (!empty($recipients)) {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = SMTP_PORT;
            
            // Sender
            $mail->setFrom(CHURCH_EMAIL, CHURCH_NAME);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $_POST['subject'];
            
            // Check if using template
            if (!empty($_POST['template_id'])) {
                $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE id = ?");
                $stmt->execute([$_POST['template_id']]);
                $template = $stmt->fetch();
                
                if ($template) {
                    $content = $template['content'];
                    // Replace placeholders if any
                    // You can add more placeholders as needed
                    $content = str_replace('{CHURCH_NAME}', CHURCH_NAME, $content);
                    $content = str_replace('{DATE}', date('Y-m-d'), $content);
                    $mail->Body = $content;
                }
            } else {
                $mail->Body = $_POST['message'];
            }
            
            // Add recipients
            foreach ($recipients as $email) {
                $mail->addBCC($email);
            }
            
            if ($mail->send()) {
                // Log success
                $logger->log(
                    'send_email',
                    'communication',
                    "Bulk email sent successfully to " . count($recipients) . " recipients",
                    $_SESSION['user_id']
                );
                
                $message = "<div class='alert alert-success'>Email sent successfully to " . count($recipients) . " recipients!</div>";
            }
        } else {
            $message = "<div class='alert alert-warning'>No recipients found with valid email addresses.</div>";
        }
    } catch (Exception $e) {
        $message = "<div class='alert alert-danger'>Error sending email: " . $e->getMessage() . "</div>";
        
        // Log error
        $logger->log(
            'send_email',
            'communication',
            "Error sending bulk email: " . $e->getMessage(),
            $_SESSION['user_id']
        );
    }
}
?>

<!DOCTYPE html>
<html lang="en">
  <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <title>Send Email - ChurchEaseSuperb</title>
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
                        <h4 class="page-title">Send Email</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../">
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
                                <a href="#">Send Email</a>
                            </li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Compose Email</h4>
                                </div>
                                <div class="card-body">
                                    <?php if($message) echo $message; ?>
                                    <form method="POST">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Select Recipients</label>
                                                    <select class="form-control" name="recipient_type" id="recipient_type" required>
                                                        <option value="">Select recipient type</option>
                                                        <option value="all">All Members</option>
                                                        <option value="member_type">By Member Type</option>
                                                        <option value="bible_class">By Bible Class</option>
                                                        <option value="specific">Specific Email Addresses</option>
                                                    </select>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6">
                                                <div class="form-group member-type-group" style="display: none;">
                                                    <label>Select Member Type</label>
                                                    <select class="form-control" name="member_type_id">
                                                        <?php foreach ($memberTypes as $type): ?>
                                                            <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group bible-class-group" style="display: none;">
                                                    <label>Select Bible Class</label>
                                                    <select class="form-control" name="bible_class_id">
                                                        <?php foreach ($bibleClasses as $class): ?>
                                                            <option value="<?= $class['id'] ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <div class="form-group specific-emails-group" style="display: none;">
                                                    <label>Enter Email Addresses (comma-separated)</label>
                                                    <textarea class="form-control" name="specific_emails" rows="3" placeholder="email1@example.com, email2@example.com"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Email Template (Optional)</label>
                                            <select class="form-control" name="template_id" id="template_id">
                                                <option value="">Select a template or write custom message</option>
                                                <?php foreach ($templates as $template): ?>
                                                    <option value="<?= $template['id'] ?>"><?= htmlspecialchars($template['template_name']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Subject</label>
                                            <input type="text" class="form-control" name="subject" required>
                                        </div>
                                        
                                        <div class="form-group custom-message-group">
                                            <label>Message</label>
                                            <textarea class="form-control summernote" name="message" rows="10"></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">Send Email</button>
                                        <a href="email_templates.php" class="btn btn-info">Manage Templates</a>
                                    </form>
                                </div>
                            </div>

                            <!-- Email History -->
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Email History</h4>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Subject</th>
                                                    <th>Recipients</th>
                                                    <th>Status</th>
                                                    <th>Sent By</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("
                                                    SELECT e.*, u.full_name as sender_name 
                                                    FROM email_history e 
                                                    LEFT JOIN users u ON e.sent_by = u.id 
                                                    ORDER BY e.sent_at DESC 
                                                    LIMIT 50
                                                ");
                                                while ($row = $stmt->fetch()): 
                                                ?>
                                                <tr>
                                                    <td><?= date('Y-m-d H:i', strtotime($row['sent_at'])) ?></td>
                                                    <td><?= htmlspecialchars($row['subject']) ?></td>
                                                    <td><?= $row['recipient_count'] ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $row['status'] === 'success' ? 'success' : 'danger' ?>">
                                                            <?= ucfirst($row['status']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($row['sender_name']) ?></td>
                                                </tr>
                                                <?php endwhile; ?>
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
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/summernote/summernote-bs4.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize Summernote
            $('.summernote').summernote({
                height: 300,
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
            
            // Handle recipient type selection
            $('#recipient_type').change(function() {
                $('.member-type-group, .bible-class-group, .specific-emails-group').hide();
                switch($(this).val()) {
                    case 'member_type':
                        $('.member-type-group').show();
                        break;
                    case 'bible_class':
                        $('.bible-class-group').show();
                        break;
                    case 'specific':
                        $('.specific-emails-group').show();
                        break;
                }
            });
            
            // Handle template selection
            $('#template_id').change(function() {
                if ($(this).val()) {
                    $.get('get_template.php', {id: $(this).val()}, function(response) {
                        $('.summernote').summernote('code', response.content);
                    });
                }
            });
        });
    </script>
</body>
</html>