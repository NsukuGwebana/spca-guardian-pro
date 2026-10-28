<?php
include_once "databaseConnection.php";
$message = "";
$appointments = [];
$animal = null;

// Fetch animal data
if (isset($_GET['id'])) {
    $animal_id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM animals WHERE id = ?");
    $stmt->bind_param("i", $animal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $animal = $result->fetch_assoc();
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal_id = intval($_POST['id']);
    $vet_id = intval($_POST['vet_id']);
    $app_date = $_POST['app_date'];
    $reason = trim($_POST['reason']);
    $treatments = $_POST['treatments'] ?? [];
    $recorded_by = $_POST['recorded_by'];

    $health_status = 'Unhealthy';
    if (in_array('Vaccination', $treatments) && in_array('Spaying', $treatments) && in_array('Neutering', $treatments)) {
        $health_status = 'Healthy';
    } elseif (!empty($treatments)) {
        $health_status = 'Under Treatment';
    }

    $conn->begin_transaction();
    try {
        // Insert appointment
        $stmt = $conn->prepare("INSERT INTO appointments (animal_id, vet_id, app_date, Reason, app_status) VALUES (?, ?, ?, ?, ?)");
        $app_status = 'Scheduled';
        $stmt->bind_param("iisss", $animal_id, $vet_id, $app_date, $reason, $app_status);
        $stmt->execute();
        $appointment_id = $stmt->insert_id;
        $stmt->close();

        // Insert treatments
        $treat_stmt = $conn->prepare("INSERT INTO treatments (appointment_id, treatment_types, treatment_details, treatment_date, recorded_by) VALUES (?, ?, ?, ?, ?)");
        foreach ($treatments as $treatment) {
            $treat_stmt->bind_param("issss", $appointment_id, $treatment, $treatment, $app_date, $recorded_by);
            $treat_stmt->execute();
        }
        $treat_stmt->close();

        // Update animal health status
        $update_stmt = $conn->prepare("UPDATE animals SET Health_status = ? WHERE id = ?");
        $update_stmt->bind_param("si", $health_status, $animal_id);
        $update_stmt->execute();
        $update_stmt->close();

        $conn->commit();
        $message = "✅ Appointment and treatments successfully recorded.";
    } catch (Exception $e) {
        $conn->rollback();
        $message = "❌ Error: " . $e->getMessage();
    }
}

// Fetch updated appointments
$app_stmt = $conn->prepare("SELECT app_id, app_date, vet_id, Reason, app_status FROM appointments WHERE animal_id = ? ORDER BY app_date DESC");
$app_stmt->bind_param("i", $animal_id);
$app_stmt->execute();
$app_result = $app_stmt->get_result();
while ($row = $app_result->fetch_assoc()) {
    $appointments[] = $row;
}
$app_stmt->close();

$treat_stmt = $conn->prepare("SELECT treatment_types, treatment_details, treatment_date, recorded_by FROM treatments WHERE appointment_id = ?");
$treat_stmt->bind_param("i", $app['app_id']);
$treat_stmt->execute();
$treat_result = $treat_stmt->get_result();

if ($treat_result->num_rows > 0): ?>
    <table style="width:100%; border-collapse: collapse; margin-top: 10px;">
        <thead style="background-color: #3498db; color: white;">
            <tr>
                <th>Type</th>
                <th>Details</th>
                <th>Date</th>
                <th>Recorded By</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($t = $treat_result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($t['treatment_types']) ?></td>
                    <td><?= htmlspecialchars($t['treatment_details']) ?></td>
                    <td><?= htmlspecialchars($t['treatment_date'] ?? 'Pending') ?></td>
                    <td><?= htmlspecialchars($t['recorded_by']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No treatments recorded for this appointment.</p>
<?php endif; ?>
<?php $treat_stmt->close();



// Fetch vet list
$vet_result = $conn->query("SELECT DISTINCT vet_id FROM appointments WHERE vet_id IS NOT NULL ORDER BY vet_id ASC");
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Medical Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            padding: 20px;
            color: #2c3e50;
            max-width: 600px;
            margin: 0 auto;
        }
        
        h2 {
            text-align: center;
            color: #3498db;
            margin-bottom: 20px;
        }
        
        form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        section {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: #e8f4f8;
            border-radius: 8px;
        }
        
        img {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #3498db;
        }
        
        span.status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 5px;
            background: #d4edda;
            color: #155724;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        input, select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        fieldset {
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        legend {
            font-weight: 600;
            padding: 0 10px;
        }
        
        p.checkbox {
            display: flex;
            align-items: center;
            margin: 8px 0;
        }
        
        p.checkbox input {
            width: auto;
            margin-right: 8px;
            margin-bottom: 0;
        }
        
        p.buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        button, a.button {
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            width: 48%;
            text-align: center;
            text-decoration: none;
        }
        
        button {
            background: #3498db;
            color: white;
        }
        
        a.button {
            background: #95a5a6;
            color: white;
            display: inline-block;
        }
        
        p.message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            text-align: center;
            background: #d4edda;
            color: #155724;
        }
    </style>
</head>
<body>
    <h2><i class="fas fa-heartbeat"></i> Update Medical Records</h2>
    
    <section>
  <?php if (!empty($animal['animal_picture'])): ?>
    <img src="<?= htmlspecialchars($animal['animal_picture']) ?>" alt="<?= htmlspecialchars($animal['Aname']) ?>">
  <?php else: ?>
    <img src="default-placeholder.jpg" alt="No image available">
  <?php endif; ?>

  <div>
    <h3><?= htmlspecialchars($animal['Aname']) ?> (ID: <?= $animal['id'] ?>)</h3>
    <p><?= htmlspecialchars($animal['Species']) ?> | <?= htmlspecialchars($animal['Animal_breed']) ?> | <?= htmlspecialchars($animal['Aage']) ?> years</p>
    <span class="status"><?= htmlspecialchars($animal['Health_status']) ?></span>
  </div>
</section>

    
    <p class="message">
        <i class="fas fa-info-circle"></i> Update medical treatments for this animal
    </p>
    
    <form method="post" action="">
        <input type="hidden" name="id" read-only>
        
        <label for="vet_id"><i class="fas fa-user-md"></i> Veterinarian ID:</label>
        <select name="vet_id" required>
  <option value="">-- Select Vet --</option>
  <?php
  while ($vet = $vet_result->fetch_assoc()) {
      echo "<option value='{$vet['vet_id']}'>Vet ID: {$vet['vet_id']}</option>";
  }
  ?>
</select>


<label for="app_date"><i class="fas fa-calendar-alt"></i> Appointment Date:</label>
        <input type="date" id="app_date" name="app_date" value="<?php echo date('Y-m-d'); ?>" required>
        
        <fieldset>
            <legend><i class="fas fa-stethoscope"></i> Treatments:</legend>
            
            <p class="checkbox">
                <input type="checkbox" id="vaccination" name="treatments[]" value="Vaccination">
                <label for="vaccination">Vaccination</label>
            </p>
            
            <p class="checkbox">
                <input type="checkbox" id="spaying" name="treatments[]" value="Spaying">
                <label for="spaying">Spaying</label>
            </p>
            
            <p class="checkbox">
                <input type="checkbox" id="neutering" name="treatments[]" value="Neutering">
                <label for="neutering">Neutering</label>
            </p>
            
            <p class="checkbox">
                <input type="checkbox" id="dental" name="treatments[]" value="Dental">
                <label for="dental">Dental Cleaning</label>
            </p>
            
            <p class="checkbox">
                <input type="checkbox" id="deworming" name="treatments[]" value="Deworming">
                <label for="deworming">Deworming</label>
            </p>
            
            <p class="checkbox">
                <input type="checkbox" id="flea" name="treatments[]" value="Flea">
                <label for="flea">Flea Treatment</label>
            </p>
        </fieldset>
        
        <label for="notes"><i class="fas fa-notes-medical"></i> Notes:</label>
        <textarea id="notes" name="notes" rows="3"></textarea>
        
        <label for="reason"><i class="fas fa-notes-medical"></i> Notes:</label>
        <textarea id="notes" name="notes" rows="3"></textarea>

        <p class="buttons">
            <button type="submit"><i class="fas fa-save"></i> Save Record</button>
            <a href="view_animalProfile.php?id=53" class="button"><i class="fas fa-arrow-left"></i> Back</a>
        </p>
    </form>
</body>
</html>