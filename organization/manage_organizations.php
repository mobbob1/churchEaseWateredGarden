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

// Handle form submission for organization management
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $organization_name = $_POST['organization_name'];
            $slogan = $_POST['slogan'];
            $leader_name = $_POST['leader_name'];
            
            try {
                $stmt = $pdo->prepare("INSERT INTO organizations (organization_name, slogan, leader_name) VALUES (?, ?, ?)");
                $stmt->execute([$organization_name, $slogan, $leader_name]);
                $message = "<div class='alert alert-success'>Organization added successfully!</div>";
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Error adding organization: " . $e->getMessage() . "</div>";
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $organization_name = $_POST['organization_name'];
            $slogan = $_POST['slogan'];
            $leader_name = $_POST['leader_name'];
            
            try {
                $stmt = $pdo->prepare("UPDATE organizations SET organization_name = ?, slogan = ?, leader_name = ? WHERE id = ?");
                $stmt->execute([$organization_name, $slogan, $leader_name, $id]);
                $message = "<div class='alert alert-success'>Organization updated successfully!</div>";
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Error updating organization: " . $e->getMessage() . "</div>";
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            
            try {
                // Check if organization has any members
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE organization_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Cannot delete: This organization has members assigned to it.");
                }
                
                $stmt = $pdo->prepare("DELETE FROM organizations WHERE id = ?");
                $stmt->execute([$id]);
                $message = "<div class='alert alert-success'>Organization deleted successfully!</div>";
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
            }
        }
    }
}

// Fetch all organizations
try {
    $stmt = $pdo->query("SELECT * FROM organizations ORDER BY organization_name");
    $organizations = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Error fetching organizations: " . $e->getMessage() . "</div>";
    $organizations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Manage Organizations - ChurchEaseSuperb</title>
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
    <!-- Add Select2 CSS -->
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
                        <h4 class="page-title">Manage Organizations</h4>
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
                                <a href="#">Organizations</a>
                            </li>
                        </ul>
                    </div>

                    <?php if (isset($message)) echo $message; ?>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Manage Organizations</h4>
                                        <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#addOrganizationModal">
                                            <i class="fa fa-plus"></i>
                                            Add Organization
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table id="organizations-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Organization Name</th>
                                                    <th>Slogan</th>
                                                    <th>Leader Name</th>
                                                    <th>Created At</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($organizations as $org): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($org['organization_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['slogan']); ?></td>
                                                    <td><?php echo htmlspecialchars($org['leader_name']); ?></td>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($org['created_at'])); ?></td>
                                                    <td>
                                                        <div class="form-button-action">
                                                            <button type="button" data-toggle="tooltip" 
                                                                    class="btn btn-link btn-primary btn-lg" 
                                                                    data-original-title="Edit" 
                                                                    onclick="editOrganization(<?php echo htmlspecialchars(json_encode($org)); ?>)">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                            <button type="button" data-toggle="tooltip" 
                                                                    class="btn btn-link btn-danger" 
                                                                    data-original-title="Delete"
                                                                    onclick="deleteOrganization(<?php echo $org['id']; ?>)">
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

    <!-- Add Organization Modal -->
    <div class="modal fade" id="addOrganizationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Organization</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Organization Name</label>
                            <input type="text" class="form-control" name="organization_name" required>
                        </div>
                        <div class="form-group">
                            <label>Slogan</label>
                            <input type="text" class="form-control" name="slogan" required>
                        </div>
                        <div class="form-group">
                            <label>Leader</label>
                            <select class="form-control select2-member" name="leader_name" required>
                                <option value="">Select Leader</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo htmlspecialchars($member['full_name']); ?>">
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Organization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Organization Modal -->
    <div class="modal fade" id="editOrganizationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Organization</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Organization Name</label>
                            <input type="text" class="form-control" name="organization_name" id="edit_organization_name" required>
                        </div>
                        <div class="form-group">
                            <label>Slogan</label>
                            <input type="text" class="form-control" name="slogan" id="edit_slogan" required>
                        </div>
                        <div class="form-group">
                            <label>Leader</label>
                            <select class="form-control select2-member" name="leader_name" id="edit_leader_name" required>
                                <option value="">Select Leader</option>
                                <?php foreach ($members as $member): ?>
                                    <option value="<?php echo htmlspecialchars($member['full_name']); ?>">
                                        <?php echo htmlspecialchars($member['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Organization</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Organization Modal -->
    <div class="modal fade" id="deleteOrganizationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="delete_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Delete Organization</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Are you sure you want to delete this organization?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Organization</button>
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
    <!-- Atlantis JS -->
    <script src="../res/assets/js/atlantis.min.js"></script>
    <!-- Add Select2 JS -->
    <script src="../res/assets/js/plugin/select2/select2.full.min.js"></script>
    
    <script>
        $(document).ready(function() {
            $('#organizations-table').DataTable({
                "order": [[ 0, "asc" ]],
                "pageLength": 25,
            });

            // Initialize Select2 Elements
            $('.select2-member').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Search for a member...',
                allowClear: true,
                minimumInputLength: 1,
                escapeMarkup: function(markup) {
                    return markup;
                }
            });
        });

        function editOrganization(org) {
            $('#edit_id').val(org.id);
            $('#edit_organization_name').val(org.organization_name);
            $('#edit_slogan').val(org.slogan);
            $('#edit_leader_name').val(org.leader_name).trigger('change');
            $('#editOrganizationModal').modal('show');
        }

        function deleteOrganization(id) {
            $('#delete_id').val(id);
            $('#deleteOrganizationModal').modal('show');
        }
    </script>
</body>
</html>