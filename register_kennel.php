
<?php
// register_kennel.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "databaseConnection.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Kennel Registration Status</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #134f5c;
}

.overlay {
    background: rgba(19, 79, 92, 0.85);
    min-height: 100vh;
    padding: 30px 0;
}

.container {
    max-width: 700px;
    margin: auto;
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
    text-align: center;
}

h2 {
    margin-bottom: 20px;
    color: #134f5c;
}

p, strong {
    display: block;
    margin: 10px 0;
    color: #134f5c;
}

.btn, button {
    display: inline-block;
    background: #134f5c;
    color: #fff;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    border: none;
    margin: 10px 5px 0 5px;
}

.btn:hover, button:hover {
    background: #0f3f4a;
}

.back-btn {
    display: block;
    width: fit-content;
    background: #60a5b3;
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    margin: 20px auto 0;
    text-align: center;
    cursor: pointer;
}

.back-btn:hover {
    background: #3d8a95;
}
</style>
</head>
<body>
<div class="overlay">
<div class="container">
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kennel_name = $_POST['kennel_name'] ?? '';
    $max_width = $_POST['max_width'] ?? 0;
    $max_height = $_POST['max_height'] ?? 0;
    $status = $_POST['status'] ?? 'Available';
    $notes = $_POST['notes'] ?? '';
    
    try {
        $stmt = $conn->prepare("INSERT INTO kennels (kennel_name, max_width, max_height, status, notes, created_at) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        
        if (!$stmt) {
            throw new Exception("Database preparation failed: " . $conn->error);
        }
        
        $stmt->bind_param("sdsss", $kennel_name, $max_width, $max_height, $status, $notes);
        
        if ($stmt->execute()) {
            echo "<h2>KENNEL REGISTERED SUCCESSFULLY!</h2>";
            echo "<strong>Kennel Name:</strong> $kennel_name";
            echo "<strong>Maximum Width:</strong> $max_width meters";
            echo "<strong>Maximum Height:</strong> $max_height meters";
            echo "<strong>Status:</strong> $status";
            
            if (!empty($notes)) {
                echo "<strong>Notes:</strong> $notes";
            }
        } else {
            throw new Exception("Database execution failed: " . $stmt->error);
        }
        
        $stmt->close();
    } catch (Exception $e) {
        echo "<h2>ERROR</h2>";
        echo "There was a problem saving the kennel: " . $e->getMessage();
    }
} else {
    echo "<h2>NOTE</h2>";
    echo "Please use the registration form to submit kennel data.";
}
?>
    <br>
    <a href='register_kennel.html' class="back-btn"> Register Another Kennel</a>
    <a href='admin dashboard.php' class="back-btn">Back to Dashboard</a>
</div>
</div>
</body>
</html>




