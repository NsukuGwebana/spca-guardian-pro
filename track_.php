<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$volunteerHours = [];
$totalHours = 0;
$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $contact_info = trim($_POST['contact_info']);

    $stmt = $conn->prepare("
        SELECT volunteer_id, volunteer_Fname, volunteer_Lname, date, activities_name, 
               activity_description, start_time, end_time, lapse_time, 
               supervisor_name, supervisor_surname
        FROM community_engagement 
        WHERE email = ? OR contact_number = ?
        ORDER BY date DESC
    ");
    $stmt->bind_param("ss", $contact_info, $contact_info);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $volunteerHours[] = $row;
            
            // Calculate total hours if lapse_time is stored properly
            if (!empty($row['lapse_time'])) {
                // Parse the lapse_time safely
                $timeParts = explode(':', $row['lapse_time']);
                
                // Ensure we have at least hours and minutes
                if (count($timeParts) >= 2) {
                    $hours = (int)$timeParts[0];
                    $minutes = isset($timeParts[1]) ? (int)$timeParts[1] : 0;
                    $seconds = isset($timeParts[2]) ? (int)$timeParts[2] : 0;
                    
                    $totalHours += $hours + ($minutes / 60) + ($seconds / 3600);
                }
            } else if (!empty($row['start_time']) && !empty($row['end_time'])) {
                // Calculate duration from start and end times if lapse_time is empty
                $start = strtotime($row['start_time']);
                $end = strtotime($row['end_time']);
                
                if ($start !== false && $end !== false && $end > $start) {
                    $duration = ($end - $start) / 3600; // Convert seconds to hours
                    $totalHours += $duration;
                    
                    // Update the lapse_time for display
                    $volunteerHours[count($volunteerHours) - 1]['lapse_time'] = 
                        gmdate('H:i:s', $end - $start);
                }
            }
        }
    } else {
        $message = "No volunteer hours found for that email or phone number.";
    }
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Track Volunteer Hours</title>
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
            align-items: flex-start;
        }

        .tracking-box {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            width: 100%;
            max-width: 800px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
        }

        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #e0f7fa;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        input[type="text"] {
            padding: 10px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
        }

        button {
            background: #60a5fa;
            border: none;
            padding: 10px;
            border-radius: 6px;
            font-weight: bold;
            color: #fff;
            cursor: pointer;
        }

        button:hover {
            background: #3b82f6;
        }

        .hours-summary {
            margin-top: 20px;
            padding: 15px;
            border-radius: 8px;
            background-color: rgba(76, 175, 80, 0.3);
            color: #8bc34a;
            text-align: center;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        th {
            background-color: rgba(0, 0, 0, 0.3);
            color: #e0f7fa;
        }

        tr:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .back-btn {
            display: block;
            text-align: center;
            margin-top: 20px;
            background: #60a5fa;
            color: white;
            padding: 10px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        .back-btn:hover { background: #3b82f6; }
    </style>
</head>
<body>
<div class="overlay">
    <div class="tracking-box">
        <h1>Track Volunteer Hours</h1>
        <form method="POST">
            <input type="text" name="contact_info" placeholder="Enter your Email or Phone Number" required>
            <button type="submit">Track Hours</button>
        </form>

        <?php if ($message): ?>
            <p style="text-align:center; margin-top:15px; color:#ffd700;"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (!empty($volunteerHours)): ?>
            <div class="hours-summary">
                <p>Total Volunteer Hours: <?php echo number_format($totalHours, 2); ?> hours</p>
                <p>Volunteer: <?php echo htmlspecialchars($volunteerHours[0]['volunteer_Fname'] . ' ' . htmlspecialchars($volunteerHours[0]['volunteer_Lname'])); ?></p>
                <p>Volunteer ID: <?php echo htmlspecialchars($volunteerHours[0]['volunteer_id']); ?></p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activity</th>
                        <th>Description</th>
                        <th>Start Time</th>
                        <th>End Time</th>
                        <th>Duration</th>
                        <th>Supervisor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($volunteerHours as $entry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($entry['date']); ?></td>
                        <td><?php echo htmlspecialchars($entry['activities_name']); ?></td>
                        <td><?php echo htmlspecialchars($entry['activity_description']); ?></td>
                        <td><?php echo htmlspecialchars($entry['start_time']); ?></td>
                        <td><?php echo htmlspecialchars($entry['end_time']); ?></td>
                        <td><?php echo !empty($entry['lapse_time']) ? htmlspecialchars($entry['lapse_time']) : 'N/A'; ?></td>
                        <td><?php echo htmlspecialchars($entry['supervisor_name'] . ' ' . htmlspecialchars($entry['supervisor_surname'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <a href="Homepage.html" class="back-btn"> Back </a>
    </div>
</div>
</body>
</html>