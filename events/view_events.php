<?php
require_once '../includes/auth.php';
// Handle delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // First delete related attendance records
        $stmt = $pdo->prepare("DELETE FROM attendance WHERE event_id = ?");
        $stmt->execute([$id]);
        
        // Then delete the event
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$id]);
        
        // Commit transaction
        $pdo->commit();
        
        $_SESSION['message'] = "<div class='alert alert-success'>Event deleted successfully!</div>";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $_SESSION['message'] = "<div class='alert alert-danger'>Error deleting event: " . $e->getMessage() . "</div>";
    }
    header("Location: view_events.php");
    exit();
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
    <title>View Events - OutpouringCRM</title>
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
</head>
<body>
    <div class="wrapper">
        <!-- Header -->
        <?php include '../res/main_header.php'; ?>
        <!-- End Header -->
        <!-- Sidebar -->
        <?php include '../res/sidebar.php'; ?>
        <!-- End Sidebar -->
        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Events Management</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="#">
                                    <i class="flaticon-home"></i>
                                </a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Events</a>
                            </li>
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">View Events</a>
                            </li>
                        </ul>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">Events List</h4>
                                        <button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#addEventModal">
                                            <i class="fa fa-plus"></i>
                                            Add Event
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php if(isset($message)) echo $message; ?>
                                    <!-- Modal -->
                                    <div class="modal fade" id="addEventModal" tabindex="-1" role="dialog" aria-hidden="true">
                                        <div class="modal-dialog modal-lg" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header no-bd">
                                                    <h5 class="modal-title">
                                                        <span class="fw-mediumbold">New</span> 
                                                        <span class="fw-light">Event</span>
                                                    </h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="addEventForm" method="POST" action="event_management.php">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>Event Name</label>
                                                                    <input type="text" name="event_name" class="form-control" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>Event Date</label>
                                                                    <input type="datetime-local" name="event_date" class="form-control" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <div class="form-group">
                                                                    <label>Location</label>
                                                                    <input type="text" name="location" class="form-control" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-12">
                                                                <div class="form-group">
                                                                    <label>Description</label>
                                                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="modal-footer no-bd">
                                                    <button type="submit" form="addEventForm" class="btn btn-primary">Add</button>
                                                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive">
                                        <table id="events-table" class="display table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Event Name</th>
                                                    <th>Date</th>
                                                    <th>Location</th>
                                                    <th>Description</th>
                                                    <th style="width: 10%">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $sql = "SELECT * FROM events ORDER BY event_date DESC";
                                                $stmt = $pdo->query($sql);
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo "<tr>";
                                                    echo "<td>{$row['event_name']}</td>";
                                                    echo "<td>" . date('Y-m-d H:i', strtotime($row['event_date'])) . "</td>";
                                                    echo "<td>{$row['location']}</td>";
                                                    echo "<td>{$row['description']}</td>";
                                                    echo "<td>
                                                            <div class='form-button-action'>
                                                                <a href='event_management.php?id={$row['id']}' 
                                                                   data-toggle='tooltip' 
                                                                   title='' 
                                                                   class='btn btn-link btn-primary btn-lg' 
                                                                   data-original-title='Edit Event'>
                                                                    <i class='fa fa-edit'></i>
                                                                </a>
                                                                <button type='button' 
                                                                        data-toggle='tooltip' 
                                                                        title='' 
                                                                        class='btn btn-link btn-danger' 
                                                                        data-original-title='Remove'
                                                                        onclick='deleteEvent({$row['id']})'>
                                                                    <i class='fa fa-times'></i>
                                                                </button>
                                                            </div>
                                                          </td>";
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

    <script>
        $(document).ready(function() {
            $('#events-table').DataTable({
                "pageLength": 10,
            });
        });

        function deleteEvent(id) {
            if(confirm('Are you sure you want to delete this event? This will also delete all attendance records for this event.')) {
                window.location.href = 'view_events.php?delete=' + id;
            }
        }
    </script>
</body>
</html>