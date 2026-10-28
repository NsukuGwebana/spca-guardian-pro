<?php
require_once("databaseConnection.php");

// establish a connection
$conn = new mysqli($serverName, $username, $password, $database);

// check if connection is successful
if ($conn->connect_error) {
    die('<p class="error">Connection to the database failed!</p>');
}

// query to view the user table
$sql = "SELECT 
            donor.F_name,
            donor.L_name,
            donor.E_address,
            donor.Phone_num,
            donations.dtype,
            donations.damount,
            donations.dlocation,
            donations.ddate
        FROM donor
        INNER JOIN donations ON donor.donor_id = donations.donor_id
        ORDER BY donations.ddate DESC";

$result = $conn->query($sql);

if ($result === FALSE) {
    die('<p class="error">Query failed! Unable to view user table!</p>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>System Users - AnimalGuardian</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --primary-color:#134f5c;
    --primary-light:#60a5b3;
    --primary-dark:#0f3f4a;
    --accent-color:#ff9f43;
    --success-color:#27ae60;
    --danger-color:#e74c3c;
    --light-bg:#f8f9fa;
    --text-dark:#2c3e50;
    --shadow:0 4px 6px rgba(0,0,0,0.1);
    --transition:all 0.3s ease;
}
*{box-sizing:border-box;margin:0;padding:0;}
body{
    font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;
    background:url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size:cover;
    color:var(--text-dark);
    line-height:1.6;
}
.overlay{
    background:linear-gradient(135deg, rgba(19,79,92,0.92) 0%, rgba(15,63,74,0.95) 100%);
    min-height:100vh;
    padding:40px 20px;
}
.container{
    max-width:95%;
    margin:auto;
    padding:30px;
    background:#fff;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,0.4);
}
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
    padding-bottom:15px;
    border-bottom:2px solid var(--primary-light);
}
.header h2{
    margin:0;
    color:var(--primary-color);
    position:relative;
    padding-bottom:10px;
}
.header h2::after{
    content:'';
    position:absolute;
    bottom:-2px;
    left:0;
    width:60px;
    height:4px;
    background:linear-gradient(90deg, var(--primary-light), var(--primary-color));
    border-radius:2px;
}
.table-container{
    overflow-x:auto;
    border-radius:12px;
    box-shadow:var(--shadow);
    margin-bottom:25px;
}
table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    background:#fff;
    border-radius:12px;
}
table th{
    background:linear-gradient(to bottom, var(--primary-color), var(--primary-dark));
    color:white;
    padding:16px;
    text-align:left;
    font-weight:600;
    position:sticky;
    top:0;
}
table th:first-child{border-top-left-radius:12px;}
table th:last-child{border-top-right-radius:12px;}
table td{
    padding:14px 16px;
    border-bottom:1px solid #e0e0e0;
    transition:var(--transition);
}
table tr:nth-child(even){background-color:#f8f9fa;}
table tr:last-child td:first-child{border-bottom-left-radius:12px;}
table tr:last-child td:last-child{border-bottom-right-radius:12px;}
table tr:hover{
    background-color:#e9f7f9;
    transform:scale(1.01);
    box-shadow:0 2px 8px rgba(0,0,0,0.05);
}
.btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:var(--primary-color);
    color:#fff;
    padding:10px 16px;
    border-radius:8px;
    text-decoration:none;
    font-weight:600;
    cursor:pointer;
    transition:var(--transition);
    box-shadow:var(--shadow);
}
.btn:hover{
    background:var(--primary-dark);
    transform:translateY(-3px);
    box-shadow:0 6px 12px rgba(0,0,0,0.15);
}
.back-btn-container {
    text-align: center;
    margin-top: 20px;
}
</style>
</head>
<body>
<div class="overlay">
    <div class="container">
        <div class="header">
            <h2>Donations information</h2>
           
        </div>

        <div class="table-container">
            <table>
  <tr>
    <th>Maximum Amount Flag</th>
    <th>Name</th>
    <th>Email</th>
    <th>Phone</th>
    <th>Donation Type</th>
    <th>Amount</th>
    <th>Location</th>
    <th>Date</th>
  </tr>
  <?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= ($row['damount'] > 500) ? '🚩' : '' ?>
    </td>
    <td><?= htmlspecialchars($row['F_name'] . ' ' . $row['L_name']) ?></td>
      <td><?= htmlspecialchars($row['E_address']) ?></td>
      <td><?= htmlspecialchars($row['Phone_num']) ?></td>
      <td><?= htmlspecialchars($row['dtype']) ?></td>
      <td>R<?= number_format($row['damount'], 2) ?></td>
      <td><?= htmlspecialchars($row['dlocation']) ?></td>
      <td><?= htmlspecialchars($row['ddate']) ?></td>
      
    </tr>
  <?php endwhile; 
     ?>
</table>

        <!-- Back to Dashboard Button -->
        <div class="back-btn-container">
            <a href="admin dashboard.php" class="btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>