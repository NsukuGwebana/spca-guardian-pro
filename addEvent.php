<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "databaseConnection.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect and sanitize inputs
    $event_name     = trim($_POST['event_name']);
    $event_date     = trim($_POST['event_date']);
    $event_time     = trim($_POST['event_time']);
    $location       = trim($_POST['location']);
    $description    = trim($_POST['description']);
    $max_volunteers = (int) $_POST['max_volunteers'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO sysmasters.events 
        (event_name, event_date, event_time, location, description, max_volunteers, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())");

    if ($stmt) {
        $stmt->bind_param("sssssi", 
            $event_name, 
            $event_date, 
            $event_time, 
            $location, 
            $description, 
            $max_volunteers
        );

        if ($stmt->execute()) {
            $message = "<h3>New event created successfully!</h3>";
        } else {
            $message = "<p class='error-message'>Error: " . $stmt->error . "</p>";
        }
        $stmt->close();
    } else {
        $message = "<p class='error-message'>Error preparing the SQL statement: " . $conn->error . "</p>";
    }
} else {
    $message = "<p class='error-message'>Invalid request method.</p>";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Event Creation Status</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
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

        .status-box {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 20px;
            width: 100%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3);
            text-align: center;
        }

        h3 {
            color: #e0f7fa;
        }
        
        button {
            background: #60a5fa;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
        }
        
        button:hover {
            background: #3b82f6;
        }

        .error-message {
            color: #f44336;
        }
    </style>
</head>
<body>
    <div class="overlay">
        <div class="status-box">
            <?php echo $message; ?>
            <button onclick="window.location.href='Event.html'">Go Back</button>
        </div>
    </div>
</body>
</html>

