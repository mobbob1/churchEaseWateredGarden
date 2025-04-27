<?php
require_once '../includes/auth.php';
require_once '../config.php';

// Fetch all the necessary data
try {
    // Get member types
    $typeStmt = $pdo->query("SELECT * FROM member_types ORDER BY type_name");
    $member_types = $typeStmt->fetchAll();

    // Get bible classes
    $classStmt = $pdo->query("SELECT * FROM bible_classes ORDER BY class_name");
    $bible_classes = $classStmt->fetchAll();

    // Get organizations
    $orgStmt = $pdo->query("SELECT * FROM organizations ORDER BY organization_name");
    $organizations = $orgStmt->fetchAll();
} catch (PDOException $e) {
    $_SESSION['message'] = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Member Groups - ChurchEaseSuperb</title>
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
<script src="[https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
    <!-- CSS Files -->
    <link rel="stylesheet" href="../res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../res/assets/css/atlantis.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.css"/>

</head>
<body>
    <div class="wrapper">
        <?php include '../res/main_header.php'; ?>
        <?php include '../res/sidebar.php'; ?>

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Member Groups</h4>
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
                                <a href="#">Member Groups</a>
                            </li>
                        </ul>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                                        <li class="nav-item">
                                            <a class="nav-link active" data-toggle="tab" href="#memberType" role="tab">Members</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#gender" role="tab">By Gender</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#memberTypes" role="tab">Member Types</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#visitors" role="tab">Visitors</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#bibleClasses" role="tab">Bible Classes</a>
                                        </li>
                                        <li class="nav-item">
                                            <a class="nav-link" data-toggle="tab" href="#organizations" role="tab">Organizations</a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="card-body">
                                    <div class="tab-content">
                                        <!-- Members Tab -->
                                        <div class="tab-pane fade show active" id="memberType" role="tabpanel">
                                            <div class="card-body bg-light mb-3">
                                                <div class="row">
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Member Type</label>
                                                            <select class="form-control" id="filter-member-type">
                                                                <option value="">All Types</option>
                                                                <?php foreach ($member_types as $type): ?>
                                                                <option value="<?= htmlspecialchars($type['type_name']) ?>"><?= htmlspecialchars($type['type_name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Bible Class</label>
                                                            <select class="form-control" id="filter-bible-class">
                                                                <option value="">All Classes</option>
                                                                <?php foreach ($bible_classes as $class): ?>
                                                                <option value="<?= htmlspecialchars($class['class_name']) ?>"><?= htmlspecialchars($class['class_name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Organization</label>
                                                            <select class="form-control" id="filter-organization">
                                                                <option value="">All Organizations</option>
                                                                <?php foreach ($organizations as $org): ?>
                                                                <option value="<?= htmlspecialchars($org['organization_name']) ?>"><?= htmlspecialchars($org['organization_name']) ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="form-group">
                                                            <label>Gender</label>
                                                            <select class="form-control" id="filter-gender">
                                                                <option value="">All</option>
                                                                <option value="Male">Male</option>
                                                                <option value="Female">Female</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12 text-right">
                                                        <button id="apply-filters" class="btn btn-primary"><i class="fa fa-filter"></i> Apply Filters</button>
                                                        <button id="reset-filters" class="btn btn-secondary"><i class="fa fa-refresh"></i> Reset</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="table-responsive">
                                                <table id="memberType-table" class="display table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Name</th>
                                                            <th>Member Type</th>
                                                            <th>Gender</th>
                                                            <th>Contact</th>
                                                            <th>Email</th>
                                                            <th>Bible Class</th>
                                                            <th>Organization</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>
<!-- Add this right above the table -->
<div class="mb-3">
    <div class="btn-group">
        <button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Bulk Actions
        </button>
        <div class="dropdown-menu">
            <a class="dropdown-item bulk-action" href="#" data-action="export-csv">Export Selected (CSV)</a>
            <a class="dropdown-item bulk-action" href="#" data-action="export-pdf">Export Selected (PDF)</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item bulk-action" href="#" data-action="assign-class">Assign Bible Class</a>
            <a class="dropdown-item bulk-action" href="#" data-action="assign-org">Assign Organization</a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item bulk-action text-danger" href="#" data-action="delete">Delete Selected</a>
        </div>
    </div>
</div>
                                        <!-- Gender Tab -->
                                        <div class="tab-pane fade" id="gender" role="tabpanel">
                                            <div class="table-responsive">
                                                <table id="gender-table" class="display table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Name</th>
                                                            <th>Member Type</th>
                                                            <th>Gender</th>
                                                            <th>Contact</th>
                                                            <th>Email</th>
                                                            <th>Bible Class</th>
                                                            <th>Organization</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Member Types Tab -->
                                        <div class="tab-pane fade" id="memberTypes" role="tabpanel">
                                            <div class="table-responsive">
                                                <table id="memberTypes-table" class="display table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Name</th>
                                                            <th>Member Type</th>
                                                            <th>Gender</th>
                                                            <th>Contact</th>
                                                            <th>Email</th>
                                                            <th>Bible Class</th>
                                                            <th>Organization</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Visitors Tab -->
                                        <div class="tab-pane fade" id="visitors" role="tabpanel">
                                            <div class="table-responsive">
                                                <table id="visitors-table" class="display table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>ID</th>
                                                            <th>Name</th>
                                                            <th>Gender</th>
                                                            <th>Contact</th>
                                                            <th>Address</th>
                                                            <th>Notes</th>
                                                            <th>Visit Date</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Bible Classes Tab -->
                                        <div class="tab-pane fade" id="bibleClasses" role="tabpanel">
                                            <div class="table-responsive">
                                                <table id="bibleClasses-table" class="display table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Name</th>
                                                            <th>Member Type</th>
                                                            <th>Gender</th>
                                                            <th>Contact</th>
                                                            <th>Email</th>
                                                            <th>Bible Class</th>
                                                            <th>Organization</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                </table>
                                            </div>
                                        </div>

                                        <!-- Organizations Tab -->
                                        <div class="tab-pane fade" id="organizations" role="tabpanel">
                                            <div class="table-responsive">
                                                <table id="organizations-table" class="display table table-striped table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Name</th>
                                                            <th>Member Type</th>
                                                            <th>Gender</th>
                                                            <th>Contact</th>
                                                            <th>Email</th>
                                                            <th>Bible Class</th>
                                                            <th>Organization</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
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
        </div>
    </div>

    <!-- Core JS Files -->
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
            // Common DataTable configuration
            const commonConfig = {
                processing: true,
                serverSide: false,
                responsive: true,
                dom: 'Bfrtip',
                buttons: [
                    'copyHtml5',
                    'csvHtml5',
                    'excelHtml5',
                    'pdfHtml5',
                    'print'
                ],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                ajax: {
                    dataSrc: function(json) {
                        console.log('DataTables received data:', json);
                        return json.data || [];
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX error:', error, thrown);
                    }
                },
                initComplete: function(settings, json) {
                    console.log('DataTable initialization complete:', settings.sTableId);
                },
                drawCallback: function(settings) {
                    console.log('DataTable draw complete:', settings.sTableId, 'Rows:', settings._iDisplayLength);
                }
            };

            // Regular member tables (same structure)
            const regularTables = ['memberType', 'gender', 'memberTypes', 'bibleClasses', 'organizations'];
            regularTables.forEach(tableId => {
                console.log('Initializing table:', tableId);
                $(`#${tableId}-table`).DataTable({
                    ...commonConfig,
                    ajax: {
                        url: `get_grouped_members.php?group=${tableId}`,
                        dataSrc: function(json) {
                            console.log(`${tableId} table received data:`, json);
                            return json.data || [];
                        }
                    },
                    columns: [
                        { data: "0" },
                        { data: "1" },
                        { data: "2" },
                        { data: "3" },
                        { data: "4" },
                        { data: "5" },
                        { data: "6" },
                        { data: "7" },
                        { data: "8", orderable: false }
                    ]
                });
            });

            // Visitors table
            console.log('Initializing visitors table');
            $('#visitors-table').DataTable({
                ...commonConfig,
                ajax: {
                    url: 'get_grouped_members.php?group=visitors',
                    dataSrc: function(json) {
                        console.log('Visitors table received data:', json);
                        return json.data || [];
                    }
                },
                columns: [
                    { data: "0", title: "ID" },
                    { data: "1", title: "Name" },
                    { data: "2", title: "Gender" },
                    { data: "3", title: "Contact" },
                    { data: "4", title: "Address" },
                    { data: "5", title: "Notes" },
                    { data: "6", title: "Visit Date" },
                    { data: "7", title: "Actions", orderable: false }
                ]
            });

            // Function to delete member
            window.deleteMember = function(memberId) {
                if (confirm('Are you sure you want to delete this member?')) {
                    $.ajax({
                        url: 'delete_member.php',
                        type: 'POST',
                        data: { id: memberId },
                        success: function(response) {
                            console.log('Delete response:', response);
                            response = typeof response === 'string' ? JSON.parse(response) : response;
                            if (response.success) {
                                // Reload all tables
                                $('.display').DataTable().ajax.reload();
                                $.notify({
                                    icon: 'fa fa-check',
                                    message: 'Member deleted successfully!'
                                }, {
                                    type: 'success'
                                });
                            } else {
                                $.notify({
                                    icon: 'fa fa-times',
                                    message: 'Error deleting member: ' + response.message
                                }, {
                                    type: 'danger'
                                });
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error('Delete error:', status, error);
                        }
                    });
                }
            };

            // Reload tables when switching tabs
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                console.log('Tab switched:', e.target.href);
                $($.fn.dataTable.tables(true)).DataTable()
                    .columns.adjust()
                    .responsive.recalc();
            });

            // Apply filters
            $('#apply-filters').on('click', function() {
                const memberType = $('#filter-member-type').val();
                const bibleClass = $('#filter-bible-class').val();
                const organization = $('#filter-organization').val();
                const gender = $('#filter-gender').val();

                // Reload table with filters
                $('#memberType-table').DataTable().ajax.reload({
                    data: {
                        memberType,
                        bibleClass,
                        organization,
                        gender
                    }
                });
            });

            // Reset filters
            $('#reset-filters').on('click', function() {
                $('#filter-member-type').val('');
                $('#filter-bible-class').val('');
                $('#filter-organization').val('');
                $('#filter-gender').val('');

                // Reload table without filters
                $('#memberType-table').DataTable().ajax.reload();
            });
        });
    </script>
</body>
</html>