<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'databaseConnection.php';

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect form values
    $volunteer_name = trim($_POST['volunteer_Fname']);
    $volunteer_surname = trim($_POST['volunteer_Lname']);
    $contact_number = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $date = trim($_POST['date']);
    $activity_type = trim($_POST['activities_name']);
    $activity_description = trim($_POST['activity_description']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $total_hours = trim($_POST['lapse_time']);
    $supervisor_name = trim($_POST['supervisor_name']);
    $supervisor_surname = trim($_POST['supervisor_surname']);

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("INSERT INTO community_engagement 
        (volunteer_Fname, volunteer_Lname, contact_number, email, date, activities_name, activity_description, start_time, end_time, lapse_time, supervisor_name, supervisor_surname) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("ssssssssssss", 
            $volunteer_name, 
            $volunteer_surname, 
            $contact_number, 
            $email, 
            $date, 
            $activity_type, 
            $activity_description, 
            $start_time, 
            $end_time, 
            $total_hours, 
            $supervisor_name, 
            $supervisor_surname
        );
        
        if ($stmt->execute()) {
            $message = "<h3>Volunteer Registered successfully!</h3>";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $message = "Error preparing the SQL statement: " . $conn->error;
    }
} else {
    $message = "Invalid request method.";
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Volunteer Registration Status</title>
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
            <button onclick="window.location.href='Community.html'">Go Back</button>
        </div>
    </div>
</body>
</html>


