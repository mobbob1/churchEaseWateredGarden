<?php
require_once '../includes/auth.php';
require_once '../config.php';

// Handle delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete from all related tables first
        // Delete attendance records
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE member_id = ?");
        $stmt->execute([$id]);
        
        // Delete from tithes if exists
//        $stmt = $pdo->prepare("DELETE FROM tithes WHERE member_id = ?");
//        $stmt->execute([$id]);
        
       
        // Delete from pledges if exists
        $stmt = $pdo->prepare("DELETE FROM messages WHERE recipient_id = ?");
        $stmt->execute([$id]);
        
    
        // Delete from relationships if exists
        $stmt = $pdo->prepare("DELETE FROM member_relationships WHERE member_id = ? OR related_member_id = ?");
        $stmt->execute([$id, $id]);
        
        // Finally delete the member
        $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['message'] = "<div class='alert alert-success'>Member and all related records deleted successfully!</div>";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['message'] = "<div class='alert alert-danger'>Error deleting member: " . $e->getMessage() . "</div>";
    }
    // Redirect to prevent form resubmission
    header("Location: view_members.php");
    exit();
}

// Fetch member types for filter
$member_types = $pdo->query("SELECT * FROM member_types ORDER BY type_name")->fetchAll();

// Add relationship types array
$relationship_types = [
    'Spouse' => ['Husband', 'Wife'],
    'Parent' => ['Father', 'Mother'],
    'Child' => ['Son', 'Daughter'],
    'Sibling' => ['Brother', 'Sister'],
    'In-law' => ['Father-in-law', 'Mother-in-law', 'Brother-in-law', 'Sister-in-law'],
    'Extended Family' => ['Uncle', 'Aunt', 'Nephew', 'Niece', 'Cousin'],
    'Guardian' => ['Guardian']
];

// Fetch all bible classes and organizations for dropdowns
try {
    $stmt = $pdo->query("SELECT * FROM bible_classes ORDER BY class_name");
    $bible_classes = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT * FROM organizations ORDER BY organization_name");
    $organizations = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Error fetching data: " . $e->getMessage() . "</div>";
}

// Handle member assignments
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'assign_bible_class') {
        $member_id = $_POST['member_id'];
        $bible_class_id = $_POST['bible_class_id'];
        
        try {
            $stmt = $pdo->prepare("UPDATE members SET bible_class_id = ? WHERE id = ?");
            $stmt->execute([$bible_class_id, $member_id]);
            $_SESSION['message'] = "<div class='alert alert-success'>Member assigned to bible class successfully!</div>";
        } catch (PDOException $e) {
            $_SESSION['message'] = "<div class='alert alert-danger'>Error assigning bible class: " . $e->getMessage() . "</div>";
        }
        header("Location: view_members.php");
        exit();
    }
    
    if ($_POST['action'] === 'assign_organization') {
        $member_id = $_POST['member_id'];
        $organization_id = $_POST['organization_id'];
        
        try {
            $stmt = $pdo->prepare("UPDATE members SET organization_id = ? WHERE id = ?");
            $stmt->execute([$organization_id, $member_id]);
            $_SESSION['message'] = "<div class='alert alert-success'>Member assigned to organization successfully!</div>";
        } catch (PDOException $e) {
            $_SESSION['message'] = "<div class='alert alert-danger'>Error assigning organization: " . $e->getMessage() . "</div>";
        }
        header("Location: view_members.php");
        exit();
    }
}

// Handle export
if (isset($_POST['export']) && $_POST['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="members_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Gender', 'Member Type', 'Contact', 'Email', 'Profession']);
    
    $stmt = $pdo->query("SELECT m.*, mt.type_name 
                         FROM members m 
                         LEFT JOIN member_types mt ON m.member_type_id = mt.id 
                         ORDER BY m.name");
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['gender'],
            $row['type_name'],
            $row['contact_number_1'],
            $row['email'],
            $row['profession']
        ]);
    }
    fclose($output);
    exit;
}

// Display message if exists
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the message after displaying
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>View Members - ChurchEaseSuperb</title>
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


    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.css"/>

    <style>
        .dt-buttons { display: none; }
        .dataTables_filter { display: none; }
        .filter-select {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
            border: 1px solid #eee;
        }
        .export-buttons .dropdown-item {
            cursor: pointer;
        }
        .table th { position: relative; }
        .table th select {
            position: relative;
            width: 100%;
            margin-top: 5px;
            padding: 3px;
            border: 1px solid #ddd;
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
                        <h4 class="page-title">View Members</h4>
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
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <div class="form-group">
                                                <label>Search</label>
                                                <input type="text" id="searchInput" class="form-control" placeholder="Search...">
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Member Type</label>
                                                <select id="memberTypeFilter" class="form-control">
                                                    <option value="">All Member Types</option>
                                                    <?php
                                                    $typeStmt = $pdo->query("SELECT DISTINCT type_name FROM member_types ORDER BY type_name");
                                                    while ($type = $typeStmt->fetch()) {
                                                        echo "<option value='" . htmlspecialchars($type['type_name']) . "'>" . 
                                                             htmlspecialchars($type['type_name']) . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Gender</label>
                                                <select id="genderFilter" class="form-control">
                                                    <option value="">All Genders</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Bible Class</label>
                                                <select id="bibleClassFilter" class="form-control">
                                                    <option value="">All Bible Classes</option>
                                                    <?php foreach ($bible_classes as $class): ?>
                                                        <option value="<?php echo $class['class_name']; ?>">
                                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="form-group">
                                                <label>Organization</label>
                                                <select id="organizationFilter" class="form-control">
                                                    <option value="">All Organizations</option>
                                                    <?php foreach ($organizations as $org): ?>
                                                        <option value="<?php echo $org['organization_name']; ?>">
                                                            <?php echo htmlspecialchars($org['organization_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <div class="form-group">
                                                <label>&nbsp;</label>
                                                <button id="applyFilters" class="btn btn-primary btn-block">
                                                    <i class="fa fa-filter"></i> Filter
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($message)) echo $message; ?>
                                    <div class="table-responsive">
                                        <table id="members-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Gender</th>
                                                    <th>Member Type</th>
                                                    <th>Contact</th>
                                                    <th>Email</th>
                                                    <th>Profession</th>
                                                    <th>Bible Class</th>
                                                    <th>Organization</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $stmt = $pdo->query("
                                                    SELECT m.*, mt.type_name,
                                                           bc.class_name as bible_class_name,
                                                           CONCAT(m.first_name, ' ', m.surname) as full_name,
                                                           o1.organization_name as org1_name,
                                                           o2.organization_name as org2_name,
                                                           o3.organization_name as org3_name,
                                                           o4.organization_name as org4_name
                                                    FROM members m 
                                                    LEFT JOIN member_types mt ON m.member_type_id = mt.id
                                                    LEFT JOIN bible_classes bc ON m.bible_class_id = bc.id
                                                    LEFT JOIN organizations o1 ON m.first_organization = o1.id
                                                    LEFT JOIN organizations o2 ON m.second_organization = o2.id
                                                    LEFT JOIN organizations o3 ON m.third_organization = o3.id
                                                    LEFT JOIN organizations o4 ON m.fourth_organization = o4.id
                                                    ORDER BY m.first_name, m.surname
                                                ");

                                                $counter = 1;
                                                while ($row = $stmt->fetch()) {
                                                    $is_visitor = strtolower($row['type_name']) === 'visitor';
                                                    $name = $is_visitor ? $row['first_name'] : $row['full_name'];
                                                    
                                                    // Get organizations
                                                    $organizations = array_filter([
                                                        $row['org1_name'],
                                                        $row['org2_name'],
                                                        $row['org3_name'],
                                                        $row['org4_name']
                                                    ]);
                                                    
                                                    echo "<tr>";
                                                    echo "<td>{$counter}</td>";
                                                    echo "<td>" . htmlspecialchars($name) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['gender']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['type_name']) . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['contact_number_1']) . 
                                                         ($row['contact_number_2'] ? "<br>" . htmlspecialchars($row['contact_number_2']) : "") . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['email'] ?? '') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['profession'] ?? '') . "</td>";
                                                    echo "<td>" . htmlspecialchars($row['bible_class_name'] ?? 'Not Assigned') . "</td>";
                                                    echo "<td>" . ($organizations ? implode("<br>", array_map('htmlspecialchars', $organizations)) : 'Not Assigned') . "</td>";
                                                    
                                                    // Action buttons
                                                    echo "<td class='text-center'>";
                                                    echo "<div class='btn-group'>";
                                                    // View button
                                                    echo "<button type='button' class='btn btn-icon btn-round btn-info btn-sm' onclick='viewMember({$row['id']})' title='View Details'>";
                                                    echo "<i class='fa fa-eye'></i>";
                                                    echo "</button>";
                                                    
                                                    if (!$is_visitor) {
                                                        // Edit button - only for non-visitors
                                                        echo "<a href='member_management.php?id={$row['id']}' class='btn btn-icon btn-round btn-warning btn-sm' title='Edit Member'>";
                                                        echo "<i class='fa fa-edit'></i>";
                                                        echo "</a>";
                                                        
                                                        // Relationship button - only for non-visitors
                                                        echo "<button type='button' class='btn btn-icon btn-round btn-primary btn-sm' onclick='manageRelationships({$row['id']})' title='Manage Relationships'>";
                                                        echo "<i class='fa fa-users'></i>";
                                                        echo "</button>";
                                                    }
                                                    
                                                    // Delete button
                                                    echo "<button type='button' class='btn btn-icon btn-round btn-danger btn-sm' onclick='deleteMember({$row['id']})' title='Delete Member'>";
                                                    echo "<i class='fa fa-trash'></i>";
                                                    echo "</button>";
                                                    echo "</div>";
                                                    echo "</td>";
                                                    echo "</tr>";
                                                    $counter++;
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
        </div>
    </div>

    <!-- Member Details Modal -->
    <div class="modal fade" id="memberDetailsModal" tabindex="-1" role="dialog" aria-labelledby="memberDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="memberDetailsModalLabel">Member Details</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Name:</strong> <span class="member-name"></span></p>
                            <p><strong>Gender:</strong> <span class="member-gender"></span></p>
                            <p><strong>Member Type:</strong> <span class="member-type"></span></p>
                            <p><strong>Contact:</strong> <span class="member-contact"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Email:</strong> <span class="member-email"></span></p>
                            <p><strong>Address:</strong> <span class="member-address"></span></p>
                            <p><strong>Bible Class:</strong> <span class="member-bible-class"></span></p>
                            <p><strong>Organization:</strong> <span class="member-organization"></span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bible Class Assignment Modal -->
    <div class="modal fade" id="assignBibleClassModal" tabindex="-1" role="dialog" aria-labelledby="assignBibleClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="assign_bible_class">
                    <input type="hidden" name="member_id" id="bible_class_member_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignBibleClassModalLabel">Assign Bible Class</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="bible_class_id">Select Bible Class</label>
                            <select class="form-control select2" name="bible_class_id" id="bible_class_id" required>
                                <option value="">Choose a Bible Class</option>
                                <?php foreach ($bible_classes as $class): ?>
                                    <option value="<?php echo $class['id']; ?>">
                                        <?php echo htmlspecialchars($class['class_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Organization Assignment Modal -->
    <div class="modal fade" id="assignOrganizationModal" tabindex="-1" role="dialog" aria-labelledby="assignOrganizationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="assign_organization">
                    <input type="hidden" name="member_id" id="organization_member_id">
                    <div class="modal-header">
                        <h5 class="modal-title" id="assignOrganizationModalLabel">Assign Organization</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="organization_id">Select Organization</label>
                            <select class="form-control select2" name="organization_id" id="organization_id" required>
                                <option value="">Choose an Organization</option>
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?php echo $org['id']; ?>">
                                        <?php echo htmlspecialchars($org['organization_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Relationship Modal -->
    <div class="modal fade" id="relationshipModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Manage Member Relationships</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="relationshipForm">
                        <input type="hidden" id="member_id" name="member_id">
                        <div class="form-group">
                            <label>Related Member</label>
                            <select class="form-control" id="related_member_id" name="related_member_id" required>
                                <option value="">Select Member</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Relationship Category</label>
                            <select class="form-control" id="relationship_category" required>
                                <option value="">Select Category</option>
                                <?php foreach ($relationship_types as $category => $types): ?>
                                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Relationship Type</label>
                            <select class="form-control" id="relationship_type" name="relationship_type" required>
                                <option value="">Select Relationship Type</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Relationship</button>
                    </form>
                    <hr>
                    <div id="currentRelationships">
                        <h6>Current Relationships</h6>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Related Member</th>
                                        <th>Relationship</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="relationshipsList">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

      <script src="../res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="../res/assets/js/core/popper.min.js"></script>
    <script src="../res/assets/js/core/bootstrap.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>



    <script>
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-toggle="tooltip"]').tooltip();

            // Member Details Modal Function
            window.viewMemberDetails = function(memberId) {
                $.ajax({
                    url: 'get_member_details.php',
                    method: 'GET',
                    data: { id: memberId },
                    success: function(response) {
                        try {
                            var data = JSON.parse(response);
                            if (data.success) {
                                $('#memberDetailsModal .modal-body').html(data.html);
                                $('#memberDetailsModal').modal('show');
                            } else {
                                alert(data.message || 'Error loading member details');
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                            alert('Error loading member details');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX Error:', error);
                        alert('Error loading member details');
                    }
                });
            };

            // Bible Class Assignment Function
            window.assignBibleClass = function(memberId) {
                $('#assignBibleClassModal input[name="member_id"]').val(memberId);
                $('#assignBibleClassModal').modal('show');
            };

            // Organization Assignment Function
            window.assignOrganization = function(memberId) {
                $('#assignOrganizationModal input[name="member_id"]').val(memberId);
                $('#assignOrganizationModal').modal('show');
            };

            // Delete Member Function
            window.deleteMember = function(memberId) {
                if (confirm('Are you sure you want to delete this member? This action cannot be undone.')) {
                    window.location.href = 'view_members.php?delete=' + memberId;
                }
            };

            // Initialize DataTable
            var table = $('#members-table').DataTable({
                "pageLength": 25,
                "order": [[ 1, "asc" ]],
                "dom": 'Bfrtip',
                responsive: true,
                "buttons": [
                    'copy', 'csv', 'excel', 'pdf', 'print'
                ]
            });

            // Search functionality
            $('#searchInput').on('keyup', function() {
                table.search(this.value).draw();
            });
        });
    </script>
</body>
</html>