<?php
// attendance/qrcode_generator.php
require_once '../config.php';
require_once '../vendor/autoload.php'; // Make sure the QR code library is included

use Endroid\QrCode\QrCode;

if (isset($_GET['member_id']) && isset($_GET['event_id'])) {
    $member_id = $_GET['member_id'];
    $event_id = $_GET['event_id'];
    $checkin_url = "http://yourdomain.com/attendance/checkin.php?member_id=$member_id&event_id=$event_id"; // Update with your domain

    $qrCode = new QrCode($checkin_url);
    header('Content-Type: image/png');
    echo $qrCode->writeString();
} else {
    echo "<h1>QR Code Generator</h1>";
    echo "<form method='GET' action=''>
            <label for='member_id'>Member ID:</label>
            <input type='text' id='member_id' name='member_id' required>
            <label for='event_id'>Event ID:</label>
            <input type='text' id='event_id' name='event_id' required>
            <input type='submit' value='Generate QR Code'>
          </form>";
}
?>
                </div>
            </div>
            <?php include '../res/footer.php'; ?>
        </div>
    </div>