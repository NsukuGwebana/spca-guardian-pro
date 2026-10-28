<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Animal Registration Success</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
        }

        .overlay {
            background: rgba(19, 79, 92, 0.85);
            min-height: 100vh;
            padding: 30px 0;
            color: #fff;
        }

        .container {
            max-width: 800px;
            margin: auto;
            padding: 30px;
            background: #fff;
            color: #134f5c;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.35);
        }

        h2, h3 {
            text-align: center;
            margin-bottom: 25px;
            color: #134f5c;
        }

        .animal-profile-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 1px solid #dee2e6;
            position: relative;
            overflow: hidden;
        }

        .animal-profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #134f5c, #1a6675);
        }

        .profile-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px dashed #ced4da;
        }

        .animal-id {
            background: #134f5c;
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        .animal-name {
            font-size: 1.8rem;
            font-weight: bold;
            color: #134f5c;
            margin: 0;
        }

        .profile-content {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 25px;
        }

        .animal-image {
            text-align: center;
        }

        .animal-image img {
            max-width: 100%;
            height: auto;
            border-radius: 10px;
            border: 3px solid #134f5c;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .animal-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .detail-item {
            margin-bottom: 12px;
            padding: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .detail-item strong {
            display: block;
            color: #134f5c;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.1rem;
            color: #2c3e50;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: bold;
            margin-top: 5px;
        }

        .status-available {
            background: #d4edda;
            color: #155724;
        }

        .status-not-available {
            background: #f8d7da;
            color: #721c24;
        }

        .health-status {
            background: #e8f4fd;
            color: #0c5460;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #c3e6cb;
        }

        .kennel-info {
            background-color: #cce5ff;
            color: #004085;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #b8daff;
        }

        .emergency-info {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #ffeeba;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 1px solid #f5c6cb;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        button, .back-btn {
            background-color: #134f5c;
            color: #fff;
            padding: 12px 25px;
            font-size: 1rem;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            border: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: all 0.25s ease;
            margin: 5px;
            text-decoration: none;
            display: inline-block;
        }

        button:hover, .back-btn:hover {
            background: linear-gradient(135deg, #0f3f4a, #1a6675);
            transform: scale(1.05);
            box-shadow: 0 6px 12px rgba(0,0,0,0.3);
        }

        .button-secondary {
            background: #60a5b3;
        }

        .button-secondary:hover {
            background: #3d8a95;
        }
    </style>
</head>
<body>
<div class="overlay">
<div class="container">
<?php
// register_animal.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering
ob_start();

// Include database connection
require_once "databaseConnection.php";
require_once "helpers.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the form data
    $Aname = $_POST['Aname'] ?? '';
    $Species = $_POST['Species'] ?? '';
    
    // Handle "Other" species selection
    if ($Species === "Other") {
        $Species = $_POST['OtherSpecies'] ?? '';
    }
    
    $Animal_breed = $_POST['Animal_breed'] ?? '';
    
    // Handle "Other" breed selection
    if ($Animal_breed === "Other") {
        $Animal_breed = $_POST['OtherBreed'] ?? '';
    }
    
    $Aage = $_POST['Aage'] ?? 0;
    $Color = $_POST['Color'] ?? '';
    $Aweight = $_POST['Aweight'] ?? 0;
    $Width = $_POST['width'] ?? 0;
    $Height = $_POST['height'] ?? 0;
    $Health_status = $_POST['Health_status'] ?? '';
    $Intake_date = $_POST['Intake_date'] ?? '';
    
    // Set status based on health status
    // Get status from form submission
    $Status = $_POST['status'] ?? 'Available';
    
    // Get allocation method
    $allocation_method = $_POST['allocation_method'] ?? 'auto';
    $selected_kennel_id = $_POST['kennel_id'] ?? 0;

    // Image upload
    $photoPath = null;
    if (!empty($_FILES['animal_picture']['name']) && $_FILES['animal_picture']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = time() . "_" . basename($_FILES["animal_picture"]["name"]);
        $photoPath = $targetDir . $fileName;
        
        if (!move_uploaded_file($_FILES["animal_picture"]["tmp_name"], $photoPath)) {
            echo "<p>Warning: Could not upload image.</p>";
            $photoPath = null;
        }
    }

    try {
        // Prepare and execute SQL
        $stmt = $conn->prepare("INSERT INTO animals 
            (Aname, Species, Animal_breed, Aage, Color, Aweight, Width, Height, Health_status, Intake_date, status, animal_picture, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        if (!$stmt) {
            throw new Exception("Database preparation failed: " . $conn->error);
        }

        $stmt->bind_param(
            "sssisddsssss",
            $Aname, $Species, $Animal_breed, $Aage, $Color, $Aweight, 
            $Width, $Height, $Health_status, $Intake_date, $Status, $photoPath
        );

        if ($stmt->execute()) {
            $animalId = $stmt->insert_id;
            
            // ANIMAL PROFILE SUCCESS MESSAGE
            echo "<div class='success-message'>";
            echo "<h2>ANIMAL REGISTERED SUCCESSFULLY!</h2>";
            echo "</div>";
            
            echo "<div class='animal-profile-card'>";
            echo "<div class='profile-header'>";
            echo "<h3 class='animal-name'>$Aname</h3>";
            echo "<div class='animal-id'>ID: $animalId</div>";
            echo "</div>";
            
            echo "<div class='profile-content'>";
            
            if ($photoPath) {
                echo "<div class='animal-image'>";
                echo "<img src='$photoPath' alt='$Aname'>";
                echo "</div>";
            } else {
                echo "<div class='animal-image'>";
                echo "<div style='background: #ddd; width: 100%; height: 200px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #666;'>No Image Available</div>";
                echo "</div>";
            }
            
            echo "<div class='animal-details'>";
            echo "<div class='detail-item'><strong>Species</strong><span class='detail-value'>$Species</span></div>";
            echo "<div class='detail-item'><strong>Breed</strong><span class='detail-value'>$Animal_breed</span></div>";
            echo "<div class='detail-item'><strong>Age</strong><span class='detail-value'>$Aage years</span></div>";
            echo "<div class='detail-item'><strong>Color</strong><span class='detail-value'>$Color</span></div>";
            echo "<div class='detail-item'><strong>Weight</strong><span class='detail-value'>$Aweight kg</span></div>";
            echo "<div class='detail-item'><strong>Size</strong><span class='detail-value'>{$Width}m × {$Height}m</span></div>";
            echo "<div class='detail-item'><strong>Health Status</strong><span class='detail-value'><span class='status-badge health-status'>$Health_status</span></span></div>";
            echo "<div class='detail-item'><strong>Intake Date</strong><span class='detail-value'>$Intake_date</span></div>";
            echo "<div class='detail-item'><strong>Adoption Status</strong><span class='detail-value'><span class='status-badge " . ($Status == 'Available' ? 'status-available' : 'status-not-available') . "'>$Status</span></span></div>";
            echo "</div>"; // Close animal-details
            echo "</div>"; // Close profile-content
            echo "</div>"; // Close animal-profile-card
            
            // Handle kennel allocation based on method
            if ($allocation_method === 'manual' && !empty($selected_kennel_id)) {
                // Manual allocation
                $kennel_stmt = $conn->prepare("SELECT kennel_name FROM kennels WHERE kennel_id = ?");
                $kennel_stmt->bind_param("i", $selected_kennel_id);
                $kennel_stmt->execute();
                $kennel_result = $kennel_stmt->get_result();
                
                if ($kennel_result->num_rows > 0) {
                    $kennel = $kennel_result->fetch_assoc();
                    $kennel_name = $kennel['kennel_name'];
                    
                    // Create animal-kennel assignment
                    $assign = $conn->prepare("INSERT INTO animal_kennel (animal_id, kennel_id, assignment_date) VALUES (?, ?, NOW())");
                    $assign->bind_param("ii", $animalId, $selected_kennel_id);
                    $assign->execute();
                    
                    // Update kennel status
                    $update_kennel = $conn->prepare("UPDATE kennels SET status = 'Occupied' WHERE kennel_id = ?");
                    $update_kennel->bind_param("i", $selected_kennel_id);
                    $update_kennel->execute();
                    
                    // Update animal kennel status
                    $update_animal = $conn->prepare("UPDATE animals SET Kennel_status = ? WHERE id = ?");
                    $update_animal->bind_param("si", $kennel_name, $animalId);
                    $update_animal->execute();
                    
                    echo "<div class='kennel-info'>";
                    echo "<h3>✅ Kennel Manually Assigned: $kennel_name</h3>";
                    echo "</div>";
                } else {
                    throw new Exception("Selected kennel not found");
                }
            } else {
                // Automatic allocation
                $kennelInfo = allocate_kennel($conn, $animalId, $Width, $Height, $Species, $Health_status);
                
                if ($kennelInfo) {
                    $kennelName = $kennelInfo['kennel_name'];
                    echo "<div class='kennel-info'>";
                    echo "<h3>✅ Kennel Assigned: $kennelName</h3>";
                    echo "</div>";
                    
                    // Update animal with kennel status
                    $updateStmt = $conn->prepare("UPDATE animals SET Kennel_status = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $kennelName, $animalId);
                    $updateStmt->execute();
                } else {
                    $emergencyId = place_in_emergency_cell($conn, $animalId, $Width, $Height);
                    echo "<div class='emergency-info'>";
                    echo "<h3>⚠️ Emergency Placement</h3>";
                    echo "Animal placed in emergency cell<br>";
                    echo "</div>";
                }
            }
            
        } else {
            throw new Exception("Database execution failed: " . $stmt->error);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo "<div class='error-message'>";
        echo "<h2>ERROR</h2>";
        echo "There was a problem saving the animal: " . $e->getMessage() . "<br>";
        echo "</div>";
    }

} else {
    echo "<div class='error-message'>";
    echo "<h2>NOTE</h2>";
    echo "Please use the registration form to submit animal data.<br>";
    echo "</div>";
}

echo "<div class='button-container'>";
echo "<a href='register_animal.html'><button>Register Another Animal</button></a>";
echo "<a href='admin dashboard.php'><button class='button-secondary'>Back to Dashboard</button></a>";
echo "</div>";

// Flush output buffer
ob_end_flush();
?>
</div>
</div>
</body>
</html>



