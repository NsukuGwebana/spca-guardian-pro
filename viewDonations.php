<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8");

// Fetch donations with donor info
$sql = "SELECT d.dtype, d.dlocation, d.damount, d.donor_id, d.ddate, d.seen,
               r.F_name, r.L_name, r.E_address, r.Phone_num
        FROM donations d
        JOIN donor r ON d.donor_id = r.donor_id
        ORDER BY d.ddate DESC";

$result = $conn->query($sql);
if(!$result) die("SQL Error: " . $conn->error);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Donations</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #fff;
}
.overlay { background: rgba(19, 79, 92, 0.85); min-height: 100vh; padding: 30px; }
h1 { text-align: center; margin-bottom: 20px; color: #e0f7fa; }
table { width: 100%; border-collapse: collapse; margin-top: 20px; }
th, td { border: 1px solid #60a5fa; padding: 8px; text-align: left; }
th { background-color: #134f5c; color: #fff; }
td { background-color: rgba(255,255,255,0.1); }
.action-btn { padding: 6px 12px; border-radius: 6px; text-decoration: none; font-weight: bold; color: #fff; cursor: pointer; background-color: #60a5fa; border: none; margin: 2px; }
.action-btn:hover { background-color: #3b82f6; }
.back-btn { display: block; width: fit-content; background: #60a5fa; color: #fff; padding: 10px 16px; border-radius: 6px; font-weight: bold; margin: 20px auto; text-align: center; text-decoration: none; }
.back-btn:hover { background: #3b82f6; }
</style>
</head>
<body>
<div class="overlay">
    <h1>Donation Records</h1>

    <table>
        <thead>
            <tr>
                <th>Donor Name</th>
                <th>Amount (R)</th>
                <th>Type</th>
                <th>Location</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['F_name'].' '.$row['L_name']) ?></td>
                <td>R<?= number_format($row['damount'], 2) ?> <?= ($row['damount']>500)? '🚩' : '' ?></td>
                <td><?= htmlspecialchars(ucfirst($row['dtype'])) ?></td>
                <td><?= htmlspecialchars($row['dlocation']) ?></td>
                <td><?= date('d M Y', strtotime($row['ddate'])) ?></td>
                <td>
                    <!-- View Receipt -->
                    <button class="action-btn" onclick="window.location.href='receipt.php?donor_id=<?= $row['donor_id'] ?>&date=<?= $row['ddate'] ?>'">View</button>

                    <!-- Send Email if not sent -->
                    <?php if(!$row['seen']): ?>
                        <button class="action-btn" onclick="window.location.href='send_receipt.php?donor_id=<?= $row['donor_id'] ?>&date=<?= $row['ddate'] ?>'">Send Receipts</button>
                    <?php endif; ?>

                    <!-- Acknowledge Flagged Donations if not acknowledged -->
                    <?php if($row['damount'] > 500 && !$row['seen']): ?>
                        <button class="action-btn" onclick="window.location.href='acknowledge_email.php?donor_id=<?= $row['donor_id'] ?>&date=<?= $row['ddate'] ?>'">Acknowledge</button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <a href="admin dashboard.php" class="back-btn">Back to Dashboard</a>
</div>
</body>
</html>




