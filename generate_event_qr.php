<?php

require_once './config.php';
session_start();

// Get active events
$stmt = $pdo->query("SELECT id, event_name, event_date 
                     FROM events 
                     WHERE event_date >= CURDATE() 
                     ORDER BY event_date ASC");
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Event QR Codes - OutpouringCRM</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="./res/assets/img/icon.ico" type="image/x-icon"/>

    <!-- CSS Files -->
    <link rel="stylesheet" href="./res/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="./res/assets/css/atlantis.min.css">
</head>
<body>
    <div class="wrapper">
     

        <div class="main-panel">
            <div class="content">
                <div class="page-inner">
                    <div class="page-header">
                        <h4 class="page-title">Oasis Outpouring Event QR Codes</h4>
                    </div>
                    <div class="row">
                        <?php foreach ($events as $event): ?>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h4><?php echo htmlspecialchars($event['event_name']); ?></h4>
                                    <p><?php echo date('F j, Y', strtotime($event['event_date'])); ?></p>
                                    <div class="qr-code">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php 
                                            echo urlencode("http://" . $_SERVER['HTTP_HOST'] . 
                                                         "/attendance/checkin.php?event=" . 
                                                         $event['id']); 
                                        ?>" alt="Event QR Code">
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-primary btn-sm" onclick="printQR(this)">
                                            <i class="fa fa-print"></i> Print QR Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="./res/assets/js/core/jquery.3.2.1.min.js"></script>
    <script src="./res/assets/js/core/bootstrap.min.js"></script>
    <script>
        function printQR(button) {
            const qrCode = button.parentElement.previousElementSibling;
            const printWindow = window.open('', '', 'width=600,height=600');
            printWindow.document.write('<html><head><title>Print QR Code</title>');
            printWindow.document.write('</head><body style="text-align: center;">');
            printWindow.document.write(qrCode.innerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }
    </script>
</body>
</html>