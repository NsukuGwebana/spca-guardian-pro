<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8");

if(!isset($_GET['donor_id']) || !isset($_GET['date'])) die("Invalid request.");

$donor_id = $_GET['donor_id'];
$date = $_GET['date'];

// Fetch donation & donor info
$sql = "SELECT d.dtype, d.dlocation, d.damount, d.donor_id, d.ddate, d.seen,
               r.F_name, r.L_name, r.E_address, r.Phone_num
        FROM donations d
        JOIN donor r ON d.donor_id = r.donor_id
        WHERE d.donor_id = ? AND d.ddate = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $donor_id, $date);
$stmt->execute();
$result = $stmt->get_result();
if(!$result || $result->num_rows == 0) die("Donation not found.");

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Receipt</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #fff;
}

.overlay {
    background: rgba(19, 79, 92, 0.85);
    min-height: 100vh;
    padding: 30px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.receipt-container {
    background: #134f5c;
    padding: 30px;
    border-radius: 12px;
    width: 600px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    border: 1px solid #60a5fa;
}

h2, h3 {
    color: #e0f7fa;
    text-align: center;
}

.receipt-details {
    margin-top: 20px;
    padding: 15px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.receipt-details p {
    margin: 5px 0;
}

.action-btn {
    display: inline-block;
    padding: 10px 16px;
    margin: 10px 5px 0 0;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    color: #fff;
    cursor: pointer;
    background-color: #60a5fa;
    border: none;
}

.action-btn:hover {
    background-color: #3b82f6;
}
</style>
</head>
<body>
<div class="overlay">
    <div class="receipt-container">
        <h2>Animal Guardian Organization</h2>
        <p style="text-align:center;">Old Bay Road, Makhanda 6139, South Africa | Tel: +27 76 856 0086 | Email: spcaguardianpro@gmail.com</p>
        <hr style="border-color:#60a5fa;">
        <h3>Donation Receipt</h3>
        <div class="receipt-details">
            <p><strong>Receipt No:</strong> RCPT-<?php echo $row['donor_id'].'-'.date('Ymd'); ?></p>
            <p><strong>Date:</strong> <?php echo $row['ddate']; ?></p>
            <p><strong>Donor:</strong> <?php echo $row['F_name'].' '.$row['L_name']; ?></p>
            <p><strong>Email:</strong> <?php echo $row['E_address']; ?></p>
            <p><strong>Phone:</strong> <?php echo $row['Phone_num']; ?></p>
            <p><strong>Donation Amount:</strong> R<?php echo $row['damount']; ?></p>
            <p><strong>Donation Type:</strong> <?php echo $row['dtype']; ?></p>
            <p><strong>Location:</strong> <?php echo $row['dlocation']; ?></p>
        </div>

        <?php if($row['seen'] == 0): ?>
            <a href="send_receipt.php?donor_id=<?php echo $row['donor_id']; ?>&date=<?php echo $row['ddate']; ?>" class="action-btn">Send Receipt</a>
        <?php else: ?>
            <p style="margin-top:10px; color:#4ade80; font-weight:bold;">Receipt already sent</p>
        <?php endif; ?>
        
        <button class="action-btn" onclick="window.history.back()">⬅ Back</button>
    </div>
</div>
</body>
</html>
