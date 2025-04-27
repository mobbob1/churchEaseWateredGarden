<?php
require_once '../includes/auth.php';

// Handle form submission for new member type
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $type_name = $_POST['type_name'];
            $description = $_POST['description'];
            
            try {
                $stmt = $pdo->prepare("INSERT INTO member_types (type_name, description) VALUES (?, ?)");
                $stmt->execute([$type_name, $description]);
                $message = "<div class='alert alert-success'>Member type added successfully!</div>";
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Error adding member type: " . $e->getMessage() . "</div>";
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $type_name = $_POST['type_name'];
            $description = $_POST['description'];
            
            try {
                $stmt = $pdo->prepare("UPDATE member_types SET type_name = ?, description = ? WHERE id = ?");
                $stmt->execute([$type_name, $description, $id]);
                $message = "<div class='alert alert-success'>Member type updated successfully!</div>";
            } catch (PDOException $e) {
                $message = "<div class='alert alert-danger'>Error updating member type: " . $e->getMessage() . "</div>";
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = $_POST['id'];
            
            try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM members WHERE member_type_id = ?");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("Cannot delete: This member type is currently in use.");
                }
                
                $stmt = $pdo->prepare("DELETE FROM member_types WHERE id = ?");
                $stmt->execute([$id]);
                $message = "<div class='alert alert-success'>Member type deleted successfully!</div>";
            } catch (Exception $e) {
                $message = "<div class='alert alert-danger'>" . $e->getMessage() . "</div>";
            }
        }
    }
}

// Fetch all member types
try {
    $stmt = $pdo->query("SELECT * FROM member_types ORDER BY type_name");
    $member_types = $stmt->fetchAll();
} catch (PDOException $e) {
    $message = "<div class='alert alert-danger'>Error fetching member types: " . $e->getMessage() . "</div>";
    $member_types = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	<title>Member Types - OutpouringCRM</title>
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
		<?php include '../res/main_header.php'; ?>
		<?php include '../res/sidebar.php'; ?>

		<div class="main-panel">
			<div class="content">
				<div class="page-inner">
					<div class="page-header">
						<h4 class="page-title">Member Types</h4>
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
								<a href="#">Member Types</a>
							</li>
						</ul>
					</div>

                    <?php if (isset($message)) echo $message; ?>

					<div class="row">
						<div class="col-md-12">
							<div class="card">
								<div class="card-header">
									<div class="d-flex align-items-center">
										<h4 class="card-title">Manage Member Types</h4>
										<button class="btn btn-primary btn-round ml-auto" data-toggle="modal" data-target="#addMemberTypeModal">
											<i class="fa fa-plus"></i>
											Add Member Type
										</button>
									</div>
								</div>
								<div class="card-body">
                                    <div class="table-responsive">
										<table id="member-types-table" class="display table table-striped table-hover">
											<thead>
												<tr>
													<th>Type Name</th>
													<th>Description</th>
													<th>Created At</th>
													<th>Action</th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($member_types as $type): ?>
												<tr>
													<td><?php echo htmlspecialchars($type['type_name']); ?></td>
													<td><?php echo htmlspecialchars($type['description']); ?></td>
													<td><?php echo date('Y-m-d H:i', strtotime($type['created_at'])); ?></td>
													<td>
														<div class="form-button-action">
															<button type="button" data-toggle="tooltip" 
																	class="btn btn-link btn-primary btn-lg" 
																	data-original-title="Edit" 
																	onclick="editType(<?php echo htmlspecialchars(json_encode($type)); ?>)">
																<i class="fa fa-edit"></i>
															</button>
															<button type="button" data-toggle="tooltip" 
																	class="btn btn-link btn-danger" 
																	data-original-title="Delete"
																	onclick="deleteType(<?php echo $type['id']; ?>)">
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

    <!-- Add Member Type Modal -->
    <div class="modal fade" id="addMemberTypeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Member Type</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Type Name</label>
                            <input type="text" class="form-control" name="type_name" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Add Member Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Member Type Modal -->
    <div class="modal fade" id="editMemberTypeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Member Type</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Type Name</label>
                            <input type="text" class="form-control" name="type_name" id="edit_type_name" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Update Member Type</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="delete_id">
    </form>

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
            $('#member-types-table').DataTable({
                "pageLength": 10,
            });
        });

        function editType(type) {
            $('#edit_id').val(type.id);
            $('#edit_type_name').val(type.type_name);
            $('#edit_description').val(type.description);
            $('#editMemberTypeModal').modal('show');
        }

        function deleteType(id) {
            if (confirm('Are you sure you want to delete this member type?')) {
                $('#delete_id').val(id);
                $('#deleteForm').submit();
            }
        }
    </script>
</body>
</html>