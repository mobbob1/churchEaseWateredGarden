<?php
require_once '../includes/auth.php';

// Add session check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header('Location: /churcheasesuperb/index.php');
    exit();
}

// Check if user has appropriate role - only seer and executive admins can access this dashboard
$allowedRoles = ['admin', 'executive_admin_1'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    header('Location: /churcheasesuperb/access_denied.php');
    exit();
}

// Fetch all members for dropdown selections
try {
    $stmt = $pdo->query("SELECT id, CONCAT(first_name, ' ', surname) as full_name FROM members ORDER BY first_name, surname");
    $members = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Error fetching members: " . $e->getMessage() . "</div>";
    $members = [];
}

// Handle form submission for bible class management
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $class_name = $_POST['class_name'];
            $leader_id = $_POST['leader_id'];
            $assistant_leader_id = $_POST['assistant_leader_id'];
            
            try {
                $stmt = $pdo->prepare("INSERT INTO bible_classes (class_name, leader_id, assistant_leader_id) VALUES (?, ?, ?)");
                $stmt->execute([$class_name, $leader_id, $assistant_leader_id]);
                $message = "<div class='alert alert-success'>Bible class added successfully!</div>";
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Error adding bible class: " . $e->getMessage() . "</div>";
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $class_name = $_POST['class_name'];
            $leader_id = $_POST['leader_id'];
            $assistant_leader_id = $_POST['assistant_leader_id'];
            
            try {
                $stmt = $pdo->prepare("UPDATE bible_classes SET class_name = ?, leader_id = ?, assistant_leader_id = ? WHERE id = ?");
                $stmt->execute([$class_name, $leader_id, $assistant_leader_id, $id]);
                $message = "<div class='alert alert-success'>Bible class updated successfully!</div>";
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Error updating bible class: " . $e->getMessage() . "</div>";
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            
            try {
                // Check if class has any members
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE bible_class_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Cannot delete: This bible class has members assigned to it.");
                }
                
                $stmt = $pdo->prepare("DELETE FROM bible_classes WHERE id = ?");
                $stmt->execute([$id]);
                $message = "<div class='alert alert-success'>Bible class deleted successfully!</div>";
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
            }
        }
    }
}

// Fetch all bible classes with leader names
try {
    $stmt = $pdo->query("
        SELECT bc.*, 
               CONCAT(l.first_name, ' ', l.surname) as leader_name,
               CONCAT(al.first_name, ' ', al.surname) as assistant_leader_name
        FROM bible_classes bc
        LEFT JOIN members l ON bc.leader_id = l.id
        LEFT JOIN members al ON bc.assistant_leader_id = al.id
        ORDER BY bc.class_name
    ");
    $bible_classes = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Error fetching bible classes: " . $e->getMessage() . "</div>";
    $bible_classes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Manage Bible Classes - ChurchEaseSuperb</title>
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
    <link href="../res/assets/css/select2.min.css" rel="stylesheet" />
    <link href="../res/assets/css/select2-bootstrap4.min.css" rel="stylesheet" />
</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Manage Bible Classes</h4>
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
                                <a href="#">Bible Classes</a>
                            </li>
                        </ul>
                    </div>

                    <?php if (isset($message)) echo $message; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Manage Bible Classes</h4>
                                        <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#addBibleClassModal">
                                            <i class="fa fa-plus"></i>
                                            Add Bible Class
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="bible-classes-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Class Name</th>
                                                    <th>Class Leader</th>
                                                    <th>Assistant Leader</th>
                                                    <th>Created At</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($bible_classes as $class): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($class['leader_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($class['assistant_leader_name']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($class['created_at'])); ?></td>
                                                    <td>
                                                        <div class="form-button-action">
                                                            <button type="button" data-toggle="tooltip" 
                                                                    class="btn btn-link btn-primary btn-lg" 
                                                                    data-original-title="Edit" 
                                                                    onclick="editBibleClass(<?php echo htmlspecialchars(json_encode($class)); ?>)">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button type="button" data-toggle="tooltip" 
                                                                    class="btn btn-link btn-danger" 
                                                                    data-original-title="Delete"
                                                                    onclick="deleteBibleClass(<?php echo $class['id']; ?>)">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        </div>
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

    <!-- Add Bible Class Modal -->
    <div class="modal fade" id="addBibleClassModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Bible Class</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Class Name</label>
                            <input type="text" class="form-control" name="class_name" required>
                        </div>
                        <div class="form-group">
                            <label>Class Leader</label>
                            <select class="form-control select2-member" name="leader_id" required>
                                <option value="">Search and select Class Leader</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assistant Class Leader</label>
                            <select class="form-control select2-member" name="assistant_leader_id" required>
                                <option value="">Search and select Assistant Leader</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Bible Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Bible Class Modal -->
    <div class="modal fade" id="editBibleClassModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Bible Class</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Class Name</label>
                            <input type="text" class="form-control" name="class_name" id="edit_class_name" required>
                        </div>
                        <div class="form-group">
                            <label>Class Leader</label>
                            <select class="form-control select2-member" name="leader_id" id="edit_leader_id" required>
                                <option value="">Search and select Class Leader</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Assistant Class Leader</label>
                            <select class="form-control select2-member" name="assistant_leader_id" id="edit_assistant_leader_id" required>
                                <option value="">Search and select Assistant Leader</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo $member['id']; ?>">
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Bible Class</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Bible Class Modal -->
    <div class="modal fade" id="deleteBibleClassModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Bible Class</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this bible class?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Bible Class</button>
                    </div>
                </form>
            </div>
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
    <!-- Datatables -->
    <script src="../res/assets/js/plugin/datatables/datatables.min.js"></script>
    <!-- Select2 -->
    <script src="../res/assets/js/plugin/select2/select2.full.min.js"></script>
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#bible-classes-table').DataTable({
                "order": [[ 0, "asc" ]],
                "pageLength": 25,
            });

            // Initialize Select2 Elements with enhanced search
            $('.select2-member').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Type to search members...',
                allowClear: true,
                minimumInputLength: 1,
                escapeMarkup: function(markup) {
                    return markup;
                },
                templateResult: formatMember,
                templateSelection: formatMemberSelection
            });

            // Prevent same member being selected as both leader and assistant
            $('select[name="leader_id"]').on('change', function() {
                var selectedValue = $(this).val();
                var assistantSelect = $('select[name="assistant_leader_id"]');
                assistantSelect.find('option').prop('disabled', false);
                if(selectedValue) {
                    assistantSelect.find('option[value="' + selectedValue + '"]').prop('disabled', true);
                }
                assistantSelect.select2('destroy').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });
            });

            $('select[name="assistant_leader_id"]').on('change', function() {
                var selectedValue = $(this).val();
                var leaderSelect = $('select[name="leader_id"]');
                leaderSelect.find('option').prop('disabled', false);
                if(selectedValue) {
                    leaderSelect.find('option[value="' + selectedValue + '"]').prop('disabled', true);
                }
                leaderSelect.select2('destroy').select2({
                    theme: 'bootstrap4',
                    width: '100%'
                });
            });
        });

        // Format member display in dropdown
        function formatMember(member) {
            if (!member.id) return member.text;
            return '<div class="member-option">' + member.text + '</div>';
        }

        // Format member display when selected
        function formatMemberSelection(member) {
            return member.text;
        }

        function editBibleClass(bibleClass) {
            $('#edit_id').val(bibleClass.id);
            $('#edit_class_name').val(bibleClass.class_name);
            $('#edit_leader_id').val(bibleClass.leader_id).trigger('change');
            $('#edit_assistant_leader_id').val(bibleClass.assistant_leader_id).trigger('change');
            $('#editBibleClassModal').modal('show');
        }

        function deleteBibleClass(id) {
            $('#delete_id').val(id);
            $('#deleteBibleClassModal').modal('show');
        }
    </script>
</body>
</html>