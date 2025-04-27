<?php
require_once '../includes/auth.php';
require_once '../config.php';

$title = "Custom Report Generator";


// Get available report types
$reportTypes = [
    'membership' => [
        'name' => 'Membership',
        'metrics' => ['total_members', 'new_members', 'gender_distribution', 'age_distribution'],
        'filters' => ['date_range', 'gender', 'age_range', 'membership_status']
    ],
    'attendance' => [
        'name' => 'Attendance',
        'metrics' => ['total_attendance', 'gender_distribution', 'age_distribution', 'weekly_trend'],
        'filters' => ['date_range', 'gender', 'age_range', 'event_type']
    ],
    'financial' => [
        'name' => 'Financial',
        'metrics' => ['total_income', 'total_expenses', 'income_breakdown', 'expense_breakdown'],
        'filters' => ['date_range', 'transaction_type', 'category']
    ],
    'events' => [
        'name' => 'Events',
        'metrics' => ['total_events', 'participation_rate', 'gender_distribution', 'age_distribution'],
        'filters' => ['date_range', 'event_type', 'gender', 'age_range']
    ]
];

// Handle export
if (isset($_POST['export'])) {
    $reportConfig = json_decode($_POST['report_config'], true);
    $type = $_POST['export_type'];
    
    // Generate and export report based on configuration
    include 'generate_custom_report.php';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Custom Report Generator - ChurchSuperb</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
     <link rel="icon" href="../loginres/images/favicon/favicon.ico" type="image/x-icon"/>
        <link rel="apple-touch-icon" sizes="180x180" href="../loginres/images/favicon/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="../loginres/images/favicon/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../loginres/images/favicon/favicon-16x16.png">
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <style>
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
            width: 100%;
        }
        .select2-container {
            width: 100% !important;
        }
        .report-section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #eee;
            border-radius: 5px;
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
                        <h4 class="page-title">Custom Report Generator</h4>
                        <ul class="breadcrumbs">
                            <li class="nav-home">
                                <a href="../index.php"><i class="flaticon-home"></i></a>
                            </li>
                            <li class="separator"><i class="flaticon-right-arrow"></i></li>
                            <li class="nav-item"><a href="#">Reports</a></li>
                            <li class="separator"><i class="flaticon-right-arrow"></i></li>
                            <li class="nav-item"><a href="#">Custom Report</a></li>
                        </ul>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">Report Configuration</h4>
                                </div>
                                <div class="card-body">
                                    <form id="reportForm">
                                        <div class="form-group">
                                            <label>Report Type</label>
                                            <select class="form-control" id="reportType" multiple>
                                                <?php foreach ($reportTypes as $key => $type): ?>
                                                <option value="<?php echo $key; ?>"><?php echo $type['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label>Date Range</label>
                                            <div class="input-group">
                                                <input type="date" class="form-control" id="startDate" 
                                                       value="<?php echo date('Y-m-d', strtotime('-1 year')); ?>">
                                                <input type="date" class="form-control" id="endDate" 
                                                       value="<?php echo date('Y-m-d'); ?>">
                                            </div>
                                        </div>

                                        <div id="metricsContainer">
                                            <!-- Metrics will be dynamically loaded based on report type -->
                                        </div>

                                        <div id="filtersContainer">
                                            <!-- Filters will be dynamically loaded based on report type -->
                                        </div>

                                        <div class="form-group">
                                            <label>Chart Type</label>
                                            <select class="form-control" id="chartType">
                                                <option value="bar">Bar Chart</option>
                                                <option value="line">Line Chart</option>
                                                <option value="pie">Pie Chart</option>
                                                <option value="doughnut">Doughnut Chart</option>
                                            </select>
                                        </div>

                                        <button type="button" class="btn btn-primary btn-block" onclick="generateReport()">
                                            Generate Report
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between">
                                        <h4 class="card-title">Report Preview</h4>
                                        <div class="dropdown">
                                            <button class="btn btn-secondary dropdown-toggle" type="button" 
                                                    data-toggle="dropdown">
                                                Export
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="#" onclick="exportReport('pdf')">PDF</a>
                                                <a class="dropdown-item" href="#" onclick="exportReport('excel')">Excel</a>
                                                <a class="dropdown-item" href="#" onclick="exportReport('csv')">CSV</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="reportContainer">
                                        <!-- Report charts will be rendered here -->
                                    </div>
                                    <div class="table-responsive mt-4">
                                        <table id="reportTable" class="display table table-striped table-hover">
                                            <!-- Table data will be dynamically loaded -->
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
    <script src="../res/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
    <script src="../res/assets/js/atlantis.min.js"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Select2 -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    const reportTypes = <?php echo json_encode($reportTypes); ?>;
    let currentCharts = [];

    $(document).ready(function() {
        $('#reportType').select2({
            placeholder: 'Select report type(s)',
            maximumSelectionLength: 3
        });

        $('#reportType').on('change', function() {
            updateMetricsAndFilters();
        });

        initializeForm();
    });

    function initializeForm() {
        updateMetricsAndFilters();
    }

    function updateMetricsAndFilters() {
        const selectedTypes = $('#reportType').val();
        let metricsHtml = '';
        let filtersHtml = '';

        selectedTypes.forEach(type => {
            const reportType = reportTypes[type];
            
            metricsHtml += `
                <div class="report-section">
                    <h6>${reportType.name} Metrics</h6>
                    <select class="form-control metrics-select" multiple data-type="${type}">
                        ${reportType.metrics.map(metric => `
                            <option value="${metric}">${formatMetricName(metric)}</option>
                        `).join('')}
                    </select>
                </div>
            `;

            filtersHtml += `
                <div class="report-section">
                    <h6>${reportType.name} Filters</h6>
                    ${reportType.filters.map(filter => `
                        <div class="form-group">
                            <label>${formatMetricName(filter)}</label>
                            <select class="form-control filter-select" data-type="${type}" data-filter="${filter}">
                                <option value="">All</option>
                            </select>
                        </div>
                    `).join('')}
                </div>
            `;
        });

        $('#metricsContainer').html(metricsHtml);
        $('#filtersContainer').html(filtersHtml);

        $('.metrics-select').select2({
            placeholder: 'Select metrics',
            maximumSelectionLength: 4
        });

        $('.filter-select').select2({
            placeholder: 'Select filter'
        });

        // Load filter options
        loadFilterOptions();
    }

    function loadFilterOptions() {
        // Load filter options via AJAX
        $.ajax({
            url: 'get_filter_options.php',
            type: 'POST',
            success: function(response) {
                const options = JSON.parse(response);
                populateFilterOptions(options);
            }
        });
    }

    function populateFilterOptions(options) {
        // Populate filter dropdowns based on their types
        $('.filter-select').each(function() {
            const filterType = $(this).data('filter');
            if (options[filterType]) {
                options[filterType].forEach(option => {
                    $(this).append(`<option value="${option.value}">${option.label}</option>`);
                });
            }
        });
    }

    function formatMetricName(name) {
        return name.split('_').map(word => 
            word.charAt(0).toUpperCase() + word.slice(1)
        ).join(' ');
    }

    function generateReport() {
        const config = getReportConfig();
        
        // Clear existing charts
        currentCharts.forEach(chart => chart.destroy());
        currentCharts = [];
        $('#reportContainer').empty();

        // Generate report via AJAX
        $.ajax({
            url: 'get_custom_report_data.php',
            type: 'POST',
            data: {
                config: JSON.stringify(config)
            },
            success: function(response) {
                const data = JSON.parse(response);
                renderReport(data, config);
            }
        });
    }

    function getReportConfig() {
        const config = {
            types: $('#reportType').val(),
            dateRange: {
                start: $('#startDate').val(),
                end: $('#endDate').val()
            },
            metrics: {},
            filters: {},
            chartType: $('#chartType').val()
        };

        $('.metrics-select').each(function() {
            const type = $(this).data('type');
            config.metrics[type] = $(this).val();
        });

        $('.filter-select').each(function() {
            const type = $(this).data('type');
            const filter = $(this).data('filter');
            if (!config.filters[type]) config.filters[type] = {};
            config.filters[type][filter] = $(this).val();
        });

        return config;
    }

    function renderReport(data, config) {
        data.forEach((reportData, index) => {
            const containerId = `chart-${index}`;
            $('#reportContainer').append(`
                <div class="chart-container mb-4">
                    <canvas id="${containerId}"></canvas>
                </div>
            `);

            const ctx = document.getElementById(containerId).getContext('2d');
            const chart = new Chart(ctx, {
                type: config.chartType,
                data: reportData.chartData,
                options: reportData.chartOptions
            });
            currentCharts.push(chart);
        });

        // Render table
        if (data[0].tableData) {
            $('#reportTable').DataTable({
                data: data[0].tableData.data,
                columns: data[0].tableData.columns,
                destroy: true
            });
        }
    }

    function exportReport(format) {
        const config = getReportConfig();
        
        // Create form and submit
        const form = $('<form>')
            .attr('method', 'POST')
            .attr('action', 'generate_custom_report.php');

        $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'report_config')
            .attr('value', JSON.stringify(config))
            .appendTo(form);

        $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'export_type')
            .attr('value', format)
            .appendTo(form);

        form.appendTo('body').submit().remove();
    }
    </script>
</body>
</html>