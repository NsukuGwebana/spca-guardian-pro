
<?php
include "databaseConnection.php";

$sql = "SELECT * FROM events ORDER BY event_date ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>View Events</title>
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
        <h1>All Events</h1>
        <table>
            <tr>
                <th>ID</th>
                <th>Event Name</th>
                <th>Date</th>
                <th>Time</th>
                <th>Location</th>
                <th>Description</th>
                <th>Max Volunteers</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['event_name'] ?></td>
                <td><?= $row['event_date'] ?></td>
                <td><?= $row['event_time'] ?></td>
                <td><?= $row['location'] ?></td>
                <td><?= $row['description'] ?></td>
                <td><?= $row['max_volunteers'] ?></td>
            </tr>
            <?php endwhile; ?>
        </table>

       <button class="back-btn" onclick="history.back()">Back</button>
    </div>
</body>
</html>

<?php $conn->close(); ?>










