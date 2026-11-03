<?php
$ALLOWED_ROLES = ['Administrator']; // who may open this page
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
require_once __DIR__ . '/bootstrap.php';

$conn = db();

if (!isset($_GET['donor_id'], $_GET['date'])) show_error_page(400, 'Missing details', 'Please open this from the donations list.');

$donor_id = $_GET['donor_id'];
$date = $_GET['date'];

// Fetch donation & donor info
$sql = "SELECT d.dtype, d.dlocation, d.damount, d.donor_id, d.ddate, d.seen,
               r.F_name, r.L_name, r.E_address, r.Phone_num
        FROM donations d
        JOIN donor r ON d.donor_id = r.donor_id
        WHERE d.donor_id = ? AND d.ddate = ? LIMIT 1";

$result = q($sql, [(int)$donor_id, (string)$date]); // prepared statement: values can't change the SQL
if ($result->num_rows == 0) show_error_page(404, 'Donation not found', "We couldn't find that donation. It may have been removed.");

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Receipt | SPCA Guardian Pro</title>
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
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<?php include __DIR__ . '/partials/topbar.php'; ?>
<div class="overlay">
    <div class="receipt-container">
        <h2>Animal Guardian Organization</h2>
        <p style="text-align:center;">Old Bay Road, Makhanda 6139, South Africa | Tel: +27 76 856 0086 | Email: spcaguardianpro@gmail.com</p>
        <hr style="border-color:#60a5fa;">
        <h3>Donation Receipt</h3>
        <?php if (!empty($_SESSION['flash'])): ?><div class="flash <?= e($_SESSION['flash'][0]) ?>"><?= e($_SESSION['flash'][1]) ?></div><?php unset($_SESSION['flash']); endif; ?>
        <div class="receipt-details">
            <p><strong>Receipt No:</strong> RCPT-<?= (int)$row['donor_id'] . '-' . date('Ymd') ?></p>
            <p><strong>Date:</strong> <?= e($row['ddate']) ?></p>
            <p><strong>Donor:</strong> <?= e($row['F_name'] . ' ' . $row['L_name']) ?></p>
            <p><strong>Email:</strong> <?= e($row['E_address']) ?></p>
            <p><strong>Phone:</strong> <?= e($row['Phone_num']) ?></p>
            <p><strong>Donation Amount:</strong> R<?= e($row['damount']) ?></p>
            <p><strong>Donation Type:</strong> <?= e($row['dtype']) ?></p>
            <p><strong>Location:</strong> <?= e($row['dlocation']) ?></p>
        </div>

        <?php if($row['seen'] == 0): ?>
            <form method="post" action="send_receipt.php" style="display:inline">
                <input type="hidden" name="donor_id" value="<?= (int)$row['donor_id'] ?>">
                <input type="hidden" name="date" value="<?= e($row['ddate']) ?>">
                <input type="hidden" name="back" value="receipt">
                <button type="submit" class="action-btn">Send receipt</button>
            </form>
        <?php else: ?>
            <p style="margin-top:10px; color:#4ade80; font-weight:bold;">Receipt already sent</p>
        <?php endif; ?>
        
        <a href="viewDonations.php" class="back-btn">&larr; Back to donations</a>
    </div>
</div>
</body>
</html>
