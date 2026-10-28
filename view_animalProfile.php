<?php
// Include database connection
include ("databaseConnection.php");
session_start();

if(!isset($_SESSION['access']) || $_SESSION['access']!="yes"){
    header("Location: Newlogin.html"); exit();
}
// Get animal data
$animal = null;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM animals WHERE id = $id AND is_deleted = 0";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $animal = $result->fetch_assoc();
    }
    if (!$animal) {
        die("Animal not found or has been removed.");
    }
   // Determine health status based on treatments
$health_status = 'Unknown';
$appointments = [];

$app_stmt = $conn->prepare("SELECT app_id, app_date, vet_id, Reason, app_status FROM appointments WHERE animal_id = ? ORDER BY app_date DESC");
$app_stmt->bind_param("i", $id);
$app_stmt->execute();
$app_result = $app_stmt->get_result();

while ($row = $app_result->fetch_assoc()) {
    $appointments[] = $row; // ✅ Use $row, not $app_row
}

// Optional: Use the latest appointment for health status logic
if (!empty($appointments)) {
    $latest = $appointments[0]; // ✅ First one is the latest due to DESC order
    $appointment_id = $latest['app_id'];

    $treat_stmt = $conn->prepare("SELECT treatment_types FROM treatments WHERE app_id = ?");
    $treat_stmt->bind_param("i", $appointment_id);
    $treat_stmt->execute();
    $treat_result = $treat_stmt->get_result();

    $completed = [];
    while ($t = $treat_result->fetch_assoc()) {
        $completed[] = $t['treatment_types'];
    }

    $compulsory = ['Vaccination', 'Spaying', 'Neutering'];
    $unique_completed = array_unique($completed);

    if (count(array_intersect($compulsory, $unique_completed)) === count($compulsory)) {
        $health_status = 'Healthy';
    } elseif (count(array_intersect($compulsory, $unique_completed)) >= 1) {
        $health_status = 'Under Treatment';
    } else {
        $health_status = 'Unhealthy';
    }
}


}

// Company information (consistent with PDF)
$companyName = "SPCA Guardian Pro";
$systemName = "SPCA Guardian Pro";
$companySlogan = "A Home For Every Animal ";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $animal['Aname']; ?> - Animal Profile</title>
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
            max-width: 1000px;
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
        
        .company-header {
            text-align: center;
            margin-bottom: 30px;
            color: white;
        }
        
        .company-header h2 {
            color: white;
            margin-bottom: 5px;
        }
        
        .company-header p {
            margin: 0;
            font-style: italic;
        }
        
        .section-title {
            background-color: #f0f0f0;
            padding: 10px;
            border-radius: 5px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
<div class="overlay">
    <div class="company-header">
        <h2><?php echo $companyName; ?></h2>
        <p><em><?php echo $companySlogan; ?></em></p>
    </div>
    
    <div class="container">
        <h2>Animal Profile - <?php echo $animal['Aname']; ?></h2>
        
        <div class="animal-profile-card">
            <div class="profile-header">
                <h3 class="animal-name"><?php echo $animal['Aname']; ?></h3>
                <div class="animal-id">ID: #<?php echo $animal['id']; ?></div>
            </div>
            
            <div class="profile-content">
                <div class="animal-image">
                    <?php if (!empty($animal['animal_picture'])): ?>
                        <img src="<?php echo $animal['animal_picture']; ?>" alt="<?php echo $animal['Aname']; ?>">
                        <br><br>
                        <a href="<?php echo $animal['animal_picture']; ?>" target="_blank">View Full Size Image</a>
                    <?php else: ?>
                        <div style="background: #ddd; width: 100%; height: 200px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #666; border: 3px solid #134f5c;">
                            No Image Available
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="animal-details">
                    <div class="detail-item">
                        <strong>Species</strong>
                        <span class="detail-value"><?php echo $animal['Species']; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Breed</strong>
                        <span class="detail-value"><?php echo $animal['Animal_breed']; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Age</strong>
                        <span class="detail-value"><?php echo $animal['Aage']; ?> years</span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Color</strong>
                        <span class="detail-value"><?php echo $animal['Color']; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Weight</strong>
                        <span class="detail-value"><?php echo $animal['Aweight']; ?> kg</span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Size</strong>
                        <span class="detail-value"><?php echo $animal['Width']; ?>m × <?php echo $animal['Height']; ?>m</span>
                    </div>
                    
                    <div class="detail-item">
    <strong>Health Status</strong>
    <span class="detail-value">
        <span class="status-badge <?php 
            echo ($health_status == 'Healthy') ? 'status-healthy' : 
                 (($health_status == 'Under Treatment') ? 'status-warning' : 'status-unhealthy'); ?>">
            <?php echo $health_status; ?>
        </span>
    </span>
</div>

                     <div class="detail-item">
                        <strong>Adoption Status</strong>
                        <span class="detail-value">
                            <span class="status-badge <?php echo ($animal['status'] == 'Available') ? 'status-available' : 'status-not-available'; ?>">
                                <?php echo $animal['status']; ?>
                            </span>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Intake Date</strong>
                        <span class="detail-value"><?php echo $animal['Intake_date']; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Registered On</strong>
                        <span class="detail-value"><?php echo $animal['created_at']; ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <strong>Kennel Status</strong>
                        <span class="detail-value"><?php echo $animal['Kennel_status']; ?></span>
                    </div>
                </div>
            </div>
        </div>
        
       <div class="animal-profile-card">
    <div class="profile-header">
        <h3>Health & Medical Care Information</h3>
    </div>

    <div class="animal-details" style="display:block;">
        <?php if (!empty($appointments)): ?>
            <?php foreach ($appointments as $app): ?>
                <!-- Appointment block -->
                <div class="detail-item" style="margin-bottom:20px;">
                    <h4 style="margin-top:0; color:#134f5c;">
                        Appointment on <?= htmlspecialchars($app['app_date']) ?> 
                        (Vet ID: <?= htmlspecialchars($app['vet_id']) ?>)
                    </h4>
                    <p><strong>Reason:</strong> <?= htmlspecialchars($app['Reason']) ?></p>
                    <p>
                        <strong>Status:</strong> 
                        <?php if ($app['app_status'] == 'Confirmed'): ?>
                            <i class="fas fa-check-circle" style="color:green;"></i>
                        <?php else: ?>
                            <i class="fas fa-clock" style="color:red;"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($app['app_status']) ?>
                    </p>

                    <?php
                    // Get treatments grouped by treatment_date
                    $treat_stmt = $conn->prepare("
                        SELECT treatment_types, treatment_details, treatment_date, recorded_by 
                        FROM treatments 
                        WHERE app_id = ? 
                        ORDER BY treatment_date ASC
                    ");
                    $treat_stmt->bind_param("i", $app['app_id']);
                    $treat_stmt->execute();
                    $treat_result = $treat_stmt->get_result();

                    $treatments_by_date = [];
                    while ($t = $treat_result->fetch_assoc()) {
                        $date_key = $t['treatment_date'] ?? 'Pending';
                        $treatments_by_date[$date_key][] = $t;
                    }
                    ?>

                    <?php if (!empty($treatments_by_date)): ?>
                        <?php foreach ($treatments_by_date as $treat_date => $treatments): ?>
                            <h5 style="margin:15px 0 10px; color:#134f5c;">
                                Treatment Details (<?= htmlspecialchars($treat_date) ?>)
                            </h5>
                            <table style="width:100%; border-collapse: collapse; margin-bottom:20px; background:#fff;">
                                <thead style="background-color: #003366; color: white;">
                                    <tr>
                                        <th style="padding: 8px; border: 1px solid #ccc;">Type</th>
                                        <th style="padding: 8px; border: 1px solid #ccc;">Details</th>
                                        <th style="padding: 8px; border: 1px solid #ccc;">Recorded By</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($treatments as $t): ?>
                                        <tr>
                                            <td style="padding: 8px; border: 1px solid #ccc;"><?= htmlspecialchars($t['treatment_types']) ?></td>
                                            <td style="padding: 8px; border: 1px solid #ccc;"><?= htmlspecialchars($t['treatment_details']) ?></td>
                                            <td style="padding: 8px; border: 1px solid #ccc;"><?= htmlspecialchars($t['recorded_by']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No treatments recorded for this appointment.</p>
                    <?php endif; ?>
                    <?php $treat_stmt->close(); ?>
                </div>
                <hr>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No appointment or treatment records found for this animal.</p>
        <?php endif; ?>
    </div>
</div>

<div class="animal-profile-card">
            <div class="profile-header">
                <h3>Additional Notes</h3>
            </div>
            <p><em>This animal profile was generated by the <?php echo $companyName; ?> system on <?php echo date('Y-m-d H:i:s'); ?>.</em></p>
        </div>
        
        <div class="button-container">
            <a href='regA.php'><button type='button'>Back to Animal Profiles</button></a>
            <a href='admin dashboard.php'><button type='button'>Back to Dashboard</button></a>
           
            <button type="button" onclick="window.print()">Print Profile</button>

         
        </div>
    </div>
     
</div>

    </div>
</div>
</body>
</html>