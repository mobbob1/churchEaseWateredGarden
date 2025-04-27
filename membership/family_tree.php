<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/AuditLogger.php';

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

// Get all members for the dropdown
$members = $pdo->query("SELECT id, name FROM members ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Family Tree - ChurchEaseSuperb</title>
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/treant-js/1.0/Treant.css">
    <style>
        #tree {
            width: 100%;
            height: 800px;
            overflow: auto;
            background: #f8f9fa;
            padding: 20px;
            transform-origin: 0 0;
            transition: transform 0.3s ease;
        }
        .node {
            padding: 12px;
            border-radius: 8px;
            background-color: #fff;
            border: 2px solid #3498db;
            width: 220px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .node p {
            margin: 0;
            line-height: 1.4;
        }
        .node-name {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1em;
            margin-bottom: 5px !important;
        }
        .node-relationship {
            color: #e74c3c;
            font-size: 0.9em;
            font-style: italic;
            border-top: 1px solid #eee;
            padding-top: 5px;
            margin-top: 5px !important;
        }
        .Treant > .node {
            border: 2px solid #3498db;
        }
        .Treant > .node:hover {
            background-color: #f7f9fc;
            transform: scale(1.02);
            transition: all 0.2s ease;
        }
        .Treant .collapse-switch {
            width: 20px;
            height: 20px;
            border: 2px solid #3498db;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            right: -30px;
            cursor: pointer;
            background: #fff;
        }
        .node.collapsed {
            background-color: #f8f9fa;
        }
        .node.collapsed .node-relationship {
            display: none;
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
                        <h4 class="page-title">Family Tree</h4>
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
                            <li class="separator">
                                <i class="flaticon-right-arrow"></i>
                            </li>
                            <li class="nav-item">
                                <a href="#">Family Tree</a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex align-items-center">
                                        <h4 class="card-title">View Family Tree</h4>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Select Root Member</label>
                                                <select class="form-control" id="rootMember">
                                                    <option value="">Select a member</option>
                                                    <?php foreach ($members as $member): ?>
                                                        <option value="<?php echo $member['id']; ?>">
                                                            <?php echo htmlspecialchars($member['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label>Tree Controls</label><br>
                                                <button class="btn btn-primary btn-sm" onclick="expandAll()">Expand All</button>
                                                <button class="btn btn-primary btn-sm" onclick="collapseAll()">Collapse All</button>
                                                <button class="btn btn-secondary btn-sm" onclick="resetZoom()">Reset Zoom</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="tree"></div>
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
    <script src="../res/assets/js/atlantis.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/treant-js/1.0/Treant.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.3.0/raphael.min.js"></script>

    <script>
        let currentTree = null;
        let currentScale = 1;
        const scaleStep = 0.1;
        const maxScale = 2;
        const minScale = 0.5;

        $(document).ready(function() {
            $('#rootMember').on('change', function() {
                var memberId = $(this).val();
                if (memberId) {
                    loadFamilyTree(memberId);
                } else {
                    $('#tree').empty();
                }
            });

            // Add mouse wheel zoom
            $('#tree').on('wheel', function(e) {
                if (e.ctrlKey) {
                    e.preventDefault();
                    const delta = e.originalEvent.deltaY;
                    if (delta > 0) {
                        zoomOut();
                    } else {
                        zoomIn();
                    }
                }
            });
        });

        function loadFamilyTree(memberId) {
            $.ajax({
                url: 'get_family_data.php',
                method: 'POST',
                data: { member_id: memberId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        renderFamilyTree(response.data);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error loading family tree data');
                }
            });
        }

        function renderFamilyTree(data) {
            var config = {
                container: "#tree",
                levelSeparation: 100,
                siblingSeparation: 80,
                subTeeSeparation: 80,
                nodeAlign: "CENTER",
                connectors: {
                    type: 'step',
                    style: {
                        'stroke-width': 2,
                        'stroke': '#3498db',
                        'arrow-end': 'classic-wide-long'
                    }
                },
                node: {
                    HTMLclass: 'node',
                    collapsable: true
                },
                animation: {
                    nodeAnimation: "easeOutBounce",
                    nodeSpeed: 700,
                    connectorsAnimation: "bounce",
                    connectorsSpeed: 700
                }
            };

            // Create node template
            var nodeStructure = createNodeStructure(data);
            
            // Clear previous tree
            $('#tree').empty();
            
            // Reset zoom
            currentScale = 1;
            $('#tree').css('transform', `scale(${currentScale})`);
            
            // Create new Treant instance
            currentTree = new Treant({
                chart: config,
                nodeStructure: nodeStructure
            });

            // Store tree nodes for expand/collapse functionality
            window.treeNodes = document.querySelectorAll('.node');
        }

        function createNodeStructure(data) {
            var node = {
                innerHTML: `
                    <p class="node-name">${data.text.name}</p>
                    ${data.text.relationship ? `<p class="node-relationship">${data.text.relationship}</p>` : ''}
                `,
                collapsable: true
            };

            if (data.children && data.children.length > 0) {
                node.children = data.children.map(child => createNodeStructure(child));
            }

            return node;
        }

        function expandAll() {
            if (window.treeNodes) {
                window.treeNodes.forEach(node => {
                    if (node.classList.contains('collapsed')) {
                        node.click();
                    }
                });
            }
        }

        function collapseAll() {
            if (window.treeNodes) {
                window.treeNodes.forEach(node => {
                    if (!node.classList.contains('collapsed') && node.querySelector('.collapse-switch')) {
                        node.click();
                    }
                });
            }
        }

        function zoomIn() {
            if (currentScale < maxScale) {
                currentScale += scaleStep;
                applyZoom();
            }
        }

        function zoomOut() {
            if (currentScale > minScale) {
                currentScale -= scaleStep;
                applyZoom();
            }
        }

        function resetZoom() {
            currentScale = 1;
            applyZoom();
        }

        function applyZoom() {
            $('#tree').css('transform', `scale(${currentScale})`);
        }
    </script>
</body>
</html>