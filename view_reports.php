<?php
require_once __DIR__ . '/config.php';
// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Run query with join to show status
$sql = "SELECT cr.id, cr.reporter_name, cr.contact_info, cr.animal_type, cr.cruelty_type, 
               cr.location, cr.witnesses, cr.date, 
               IFNULL(ci.status, 'Pending') AS case_status
        FROM cruelty_report cr
        LEFT JOIN cruelty_investigation ci ON cr.id = ci.cruelty_id";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Animal Cruelty Reports</title>
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

h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #e0f7fa;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

th, td {
    border: 1px solid #60a5fa;
    padding: 8px;
    text-align: left;
}

th {
    background-color: #134f5c;
    color: #fff;
}

td {
    background-color: rgba(255,255,255,0.1);
}

.action-btn {
    display: inline-block;
    padding: 6px 12px;
    margin: 2px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    color: #fff;
    cursor: pointer;
    background-color: #60a5fa;
}

.action-btn:hover {
    background-color: #3b82f6;
}

.back-btn {
    display: block;
    width: fit-content;
    background: #60a5fa;
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    font-weight: bold;
    margin: 20px auto;
    text-align: center;
    text-decoration: none;
}

.back-btn:hover {
    background: #3b82f6;
}
</style>
</head>
<body>
<div class="overlay">
    <h1>Animal Cruelty Reports</h1>

    <?php if ($result && $result->num_rows > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Reporter</th>
                    <th>Contact</th>
                    <th>Animal</th>
                    <th>Cruelty Type</th>
                    <th>Location</th>
                    <th>Witnesses</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['reporter_name']) ?></td>
                    <td><?= htmlspecialchars($row['contact_info']) ?></td>
                    <td><?= htmlspecialchars($row['animal_type']) ?></td>
                    <td><?= htmlspecialchars($row['cruelty_type']) ?></td>
                    <td><?= htmlspecialchars($row['location']) ?></td>
                    <td><?= htmlspecialchars($row['witnesses']) ?></td>
                    <td><?= htmlspecialchars($row['date']) ?></td>
                    <td><?= htmlspecialchars($row['case_status']) ?></td>
                    <td>
                        <a class="action-btn" href="investigation_form.php?cruelty_id=<?= $row['id'] ?>">Investigate</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p style="text-align:center;">No reports found.</p>
    <?php endif; ?>

 <a href="admin dashboard.php" class="back-btn">Back to Dashboard</a>
</div>
</body>
</html>
<?php $conn->close(); ?>
