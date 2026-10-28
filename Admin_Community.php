<?php
include 'databaseConnection.php';

// Handle actions (approve / remove)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $volunteer_id = intval($_GET['id']);
    $action = $_GET['action'];

    if ($action == 'approve') {
        $sql_update = "UPDATE community_engagement SET status='Approved' WHERE volunteer_id='$volunteer_id'";
        $conn->query($sql_update);
    } elseif ($action == 'remove') {
        $sql_delete = "DELETE FROM community_engagement WHERE volunteer_id='$volunteer_id'";
        $conn->query($sql_delete);
    }
}

// Fetch volunteers
$sql = "SELECT * FROM community_engagement ORDER BY date DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Community Engagement Management</title>
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
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        tr:nth-child(even) td {
            background: rgba(255, 255, 255, 0.05);
        }

        a.button {
            display: inline-block;
            padding: 6px 12px;
            margin: 2px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            color: #fff;
        }

        .approve {
            background: #27ae60;
        }

        .remove {
            background: #e74c3c;
        }

        .approve:hover {
            background-color: #219d52;
        }

        .remove:hover {
            background-color: #d84335;
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
        <h1>Community Engagement Management</h1>
        <table>
            <thead>
                <tr>
                    <th>Volunteer Name</th>
                    <th>Contact</th>
                    <th>Email</th>
                    <th>Date</th>
                    <th>Activity</th>
                    <th>Description</th>
                    <th>Supervisor</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?= htmlspecialchars($row['volunteer_Fname'] . " " . $row['volunteer_Lname']) ?></td>
                        <td><?= htmlspecialchars($row['contact_number']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td><?= htmlspecialchars($row['activities_name']) ?></td>
                        <td><?= htmlspecialchars($row['activity_description']) ?></td>
                        <td><?= htmlspecialchars($row['supervisor_name'] . " " . $row['supervisor_surname']) ?></td>
                        <td><?= htmlspecialchars(isset($row['status']) ? $row['status'] : 'Pending') ?></td>
                        <td>
                            <a href="?action=approve&id=<?= $row['volunteer_id'] ?>" class="button approve">Approve</a>
                            <a href="?action=remove&id=<?= $row['volunteer_id'] ?>" class="button remove" onclick="return confirm('Remove this volunteer?')">Remove</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <button class="back-btn" onclick="history.back()">Back</button>
    </div>
</body>
</html>
