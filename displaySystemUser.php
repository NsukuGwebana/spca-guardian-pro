<?php
require_once("databaseConnection.php");

// establish a connection
$conn = new mysqli($serverName, $username, $password, $database);

// check if connection is successful
if ($conn->connect_error) {
    die('<p class="error">Connection to the database failed!</p>');
}

// query to view the user table
$sql = "SELECT idnumber, surname, firstname, username, userrole, registration_date FROM users";
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
}

.container {
    background: rgba(0, 0, 0, 0.4);
    padding: 30px;
    border-radius: 16px;
    max-width: 95%;
    margin: auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.header h2 {
    color: #e0f7fa;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background-color: #60a5fa;
    color: #fff;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
}

.btn:hover {
    background-color: #3b82f6;
}

.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    border: 1px solid #60a5fa;
    padding: 10px;
    text-align: left;
}

th {
    background-color: #134f5c;
    color: #fff;
}

td {
    background-color: rgba(255,255,255,0.1);
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
            <h2>System Users</h2>
            <a href="SearchUser.html" class="btn"><i class="fas fa-search"></i> Search User</a>
        </div>

        <div class="table-container">
            <table>
                <tr>
                    <th>ID Number</th>
                    <th>Surname</th>
                    <th>Firstname</th>
                    <th>Username</th>
                    <th>User Role</th>
                    <th>Registration Date</th>
                </tr>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idnumber'] ?></td>
                        <td><?= $row['surname'] ?></td>
                        <td><?= $row['firstname'] ?></td>
                        <td><?= $row['username'] ?></td>
                        <td><?= $row['userrole'] ?></td>
                        <td><?= $row['registration_date'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>

        <div class="back-btn-container">
            <a href="admin dashboard.php" class="btn"><i></i> Back to Dashboard</a>
        </div>
    </div>
</div>
</body>
</html>
<?php
$conn->close();
?>

