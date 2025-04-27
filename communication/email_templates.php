<?php
require_once '../config.php';
require_once '../includes/auth.php';
require_once '../includes/SessionManager.php';
require_once '../includes/AuditLogger.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /outpouringcrm/index.php');
    exit();
}

// Check if user has appropriate role
$allowedRoles = ['admin', 'manager', 'media_officer'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /outpouringcrm/access_denied.php');
    exit();
}

// Initialize AuditLogger
$logger = new AuditLogger($pdo);

$message = '';

// Handle template operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                    $stmt = $pdo->prepare("INSERT INTO email_templates (template_name, description, content, created_by) VALUES (?, ?, ?, ?)");
                    $stmt->execute([
                        $_POST['template_name'],
                        $_POST['description'],
                        $_POST['content'],
                        $_SESSION['user_id']
                    ]);
                    $message = "<div class='alert alert-success'>Template created successfully!</div>";
                    break;
                    
                case 'update':
                    $stmt = $pdo->prepare("UPDATE email_templates SET template_name = ?, description = ?, content = ? WHERE id = ?");
                    $stmt->execute([
                        $_POST['template_name'],
                        $_POST['description'],
                        $_POST['content'],
                        $_POST['template_id']
                    ]);
                    $message = "<div class='alert alert-success'>Template updated successfully!</div>";
                    break;
                    
                case 'delete':
                    $stmt = $pdo->prepare("DELETE FROM email_templates WHERE id = ?");
                    $stmt->execute([$_POST['template_id']]);
                    $message = "<div class='alert alert-success'>Template deleted successfully!</div>";
                    break;
            }
            
            // Log the action
            $logger->log(
                'email_templates',
                'communication',
                "Template {$_POST['action']} operation performed",
                $_SESSION['user_id']
            );
        }
    } catch (PDOException $e) {
        $message = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    }
}

// Get all templates
$stmt = $pdo->query("
    SELECT t.*, u.full_name as creator_name 
    FROM email_templates t 
    LEFT JOIN users u ON t.created_by = u.id 
    ORDER BY t.template_name
");
$templates = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Email Templates - SuperbChurch</title>
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
    <link href="../res/assets/js/plugin/summernote/summernote-bs4.css" rel="stylesheet">
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Email Templates</h4>
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
                                <a href="#">Email Templates</a>
                            </li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Email Templates</h4>
                                        <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#addTemplateModal">
                                            <i class="fa fa-plus"></i>
                                            Add Template
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if($message) echo $message; ?>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Template Name</th>
                                                    <th>Description</th>
                                                    <th>Created By</th>
                                                    <th>Created At</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($templates as $template): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($template['template_name']) ?></td>
                                                    <td><?= htmlspecialchars($template['description']) ?></td>
                                                    <td><?= htmlspecialchars($template['creator_name']) ?></td>
                                                    <td><?= date('Y-m-d H:i', strtotime($template['created_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info" onclick="viewTemplate(<?= $template['id'] ?>)">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-primary" onclick="editTemplate(<?= $template['id'] ?>)">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="deleteTemplate(<?= $template['id'] ?>)">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
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

    <!-- Add Template Modal -->
    <div class="modal fade" id="addTemplateModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Email Template</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="form-group">
                            <label>Template Name</label>
                            <input type="text" class="form-control" name="template_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Content</label>
                            <textarea class="form-control summernote" name="content"></textarea>
                            <small class="form-text text-muted">
                                Available placeholders: {CHURCH_NAME}, {DATE}
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Template Modal -->
    <div class="modal fade" id="editTemplateModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Email Template</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="template_id" id="edit_template_id">
                        
                        <div class="form-group">
                            <label>Template Name</label>
                            <input type="text" class="form-control" name="template_name" id="edit_template_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Content</label>
                            <textarea class="form-control summernote" name="content" id="edit_content"></textarea>
                            <small class="form-text text-muted">
                                Available placeholders: {CHURCH_NAME}, {DATE}
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Template Modal -->
    <div class="modal fade" id="viewTemplateModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">View Email Template</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <h4 id="view_template_name"></h4>
                    <p class="text-muted" id="view_description"></p>
                    <hr>
                    <div id="view_content"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
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
        });
        
        // View template
        function viewTemplate(id) {
            $.get('get_template.php', {id: id}, function(response) {
                $('#view_template_name').text(response.template_name);
                $('#view_description').text(response.description);
                $('#view_content').html(response.content);
                $('#viewTemplateModal').modal('show');
            });
        }
        
        // Edit template
        function editTemplate(id) {
            $.get('get_template.php', {id: id}, function(response) {
                $('#edit_template_id').val(response.id);
                $('#edit_template_name').val(response.template_name);
                                $('#edit_description').val(response.description);
                $('.summernote').summernote('code', response.content);
                $('#editTemplateModal').modal('show');
            });
        }
        
        // Delete template
        function deleteTemplate(id) {
            if (confirm('Are you sure you want to delete this template?')) {
                var form = $('<form method="post">')
                    .append($('<input type="hidden" name="action" value="delete">'))
                    .append($('<input type="hidden" name="template_id">').val(id));
                $('body').append(form);
                form.submit();
            }
        }
    </script>
</body>
</html>