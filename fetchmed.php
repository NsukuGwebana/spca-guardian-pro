<?php
require_once 'databaseConnection.php';

$view_animal_id = isset($_GET['animal_id']) ? intval($_GET['animal_id']) : 0;

$animal = null;

if ($view_animal_id > 0) {
    $stmt = $conn->prepare("SELECT Aname, Health_status FROM animals WHERE id = ?");
    $stmt->bind_param("i", $view_animal_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $animal = $result->fetch_assoc();
    $stmt->close();
}

// ✅ auto-update appointment statuses
$conn->query("
    UPDATE appointments a
    LEFT JOIN treatments t ON a.app_id = t.app_id
    SET a.app_status = CASE
        WHEN t.treatment_id IS NOT NULL THEN 'Completed'
        WHEN a.app_date < CURDATE() THEN 'InProgress'
        ELSE 'Scheduled'
    END
");

// ✅ Get latest vet_id
$existing_vet_id = null;
if ($view_animal_id > 0) {
    $stmt = $conn->prepare("
        SELECT vet_id 
        FROM appointments 
        WHERE animal_id = ? 
        ORDER BY app_date DESC, app_id DESC 
        LIMIT 1
    ");
    $stmt->bind_param("i", $view_animal_id);
    $stmt->execute();
    $stmt->bind_result($existing_vet_id);
    $stmt->fetch();
    $stmt->close();
}

$vet_readonly = true;
if (!$existing_vet_id) {
    $existing_vet_id = "";
    $vet_readonly = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Schedule Appointment</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
   html, body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    height: 100%;
    padding: 0;
    color: #134f5c;
}

.overlay {
    position: fixed; 
    top: 0;
    left: 0;
    width: 100%;
    height: 100%; 
    background: linear-gradient(
        135deg, 
        rgba(19,79,92,0.75) 0%, 
        rgba(15,63,74,0.85) 100%
    );
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 30px 20px;
    overflow-y: auto;
    z-index: 1;
}

.container {
    background: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
    max-width: 900px;
    width: 100%;
}

h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #134f5c;
}

.form-group {
    margin-bottom: 16px;
    border: none;
    padding: 0;
}

label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #134f5c;
}

input, select, textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #134f5c;
    border-radius: 8px;
    margin-bottom: 10px;
    font-size: 1rem;
}

.btn {
    display: inline-block;
    background: #134f5c;
    color: #fff;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    border: none;
    margin-right: 10px;
}

.btn:hover {
    background: #0f3f4a;
}

.back-btn {
    background: #60a5b3;
}

.back-btn:hover {
    background: #3d8a95;
}

.message {
  padding: 12px;
  margin-bottom: 20px;
  border-radius: 8px;
  text-align: center;
  font-weight: 500;
}

.error {
  color: red;
  background-color: rgba(255, 0, 0, 0.1);
  border: 1px solid #ef9a9a;
}

.success {
  color: green;
  background-color: rgba(0, 255, 0, 0.1);
  border: 1px solid #a5d6a7;
}

/* ✅ Treatment Cards */
.treatment-card {
  background: #f9f9f9;
  border-left: 6px solid #134f5c;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 15px;
  font-family: monospace;
  white-space: pre-wrap;
}

.badge {
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 0.8rem;
  color: #fff;
}

.badge.inprogress { background: #f39c12; }
.badge.completed  { background: #27ae60; }
.badge.scheduled  { background: #2980b9; }
  </style>
</head>
<body>
<section class="overlay">
  <article class="container">
    <h2><i class="fas fa-calendar-plus"></i> Schedule Appointment</h2>
    
    <section class="form-container">
      <?php
      include('databaseConnection.php');
      
      $message = '';
      $message_class = '';
      
      if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $app_id = $_POST['app_id'];
        $form_animal_id = intval($_POST['animal_id']);
        $vet_id = $_POST['vet_id'];
        $app_date = $_POST['app_date'];
        $reason = $_POST['reason'];
        
        if (empty($app_id) || empty($form_animal_id) || empty($vet_id) || empty($app_date) || empty($reason)) {
          $message = "Please fill in all fields.";
          $message_class = "error";
        } else {
          $stmt = $conn->prepare("INSERT INTO appointments 
              (app_id, animal_id, vet_id, app_date, reason, app_status) 
              VALUES (?, ?, ?, ?, ?, 'Scheduled')");
          $stmt->bind_param("iiiss", $app_id, $form_animal_id, $vet_id, $app_date, $reason);

          if ($stmt->execute()) {
              $message = "✅ The appointment has been scheduled successfully and its status is set to <strong>Scheduled</strong>.";
              $message_class = "success";
          } else {
              $message = 'Error saving appointment: ' . $stmt->error;
              $message_class = 'error';
          }
        }
      }
      
      if (!empty($message)) {
        echo "<p class='message $message_class'><i class='fas fa-info-circle'></i> $message</p>";
      }
      
      $animals = [];
      $result = $conn->query("
        SELECT id, Aname 
        FROM animals 
        WHERE is_deleted = 0 
        ORDER BY Aname ASC
      ");

      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          $animals[] = $row;
        }
      }
      ?>
      
      <form method="POST" action="">
        <fieldset class="form-group">
            <label for="animal_id"><i class="fas fa-dog"></i> Select Animal:</label>
            <select name="animal_id" required>
              <option value="">-- Select Animal --</option>
              <?php foreach ($animals as $a): ?>
                <option value="<?= $a['id'] ?>" <?= ($view_animal_id == $a['id']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($a['Aname']) ?> (ID: <?= $a['id'] ?>)
                </option>
              <?php endforeach; ?>
           </select>
        </fieldset>

        <fieldset class="form-group">
          <label for="app_id"><i class="fas fa-hashtag"></i> Appointment ID:</label>
          <input type="number" id="app_id" name="app_id" required value="<?php echo rand(1000, 9999); ?>">
        </fieldset>    

        <fieldset class="form-group">
          <label for="vet_id"><i class="fas fa-user-md"></i> Vet registration number:</label>
          <input type="text" id="vet_id" name="vet_id" 
                 value="<?= htmlspecialchars($existing_vet_id) ?>" 
                 <?= $vet_readonly ? 'readonly' : 'required' ?>>
        </fieldset>

        <fieldset class="form-group">
          <label for="app_date"><i class="fas fa-calendar-day"></i> Appointment Date:</label>
          <input type="date" id="app_date" name="app_date" required value="<?php echo date('Y-m-d'); ?>">
        </fieldset>

        <fieldset class="form-group">
          <label for="reason"><i class="fas fa-stethoscope"></i> Reason:</label>
          <textarea id="reason" name="reason" rows="3" required></textarea>
        </fieldset>

        <button type="submit" class="btn"><i class="fas fa-save"></i> Save Appointment</button>
        <a href="vetDashboard.php" class="btn back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
      </form>

      <!-- ✅ Show Treatments as Code-Block Style Cards -->
      <?php
      if ($view_animal_id > 0) {
          $treatments = $conn->prepare("
              SELECT t.treatment_type, t.status, t.treatment_date, t.details
              FROM treatments t
              INNER JOIN appointments a ON t.app_id = a.app_id
              WHERE a.animal_id = ?
              ORDER BY t.treatment_date DESC
          ");
          $treatments->bind_param("i", $view_animal_id);
          $treatments->execute();
          $res = $treatments->get_result();

          if ($res->num_rows > 0) {
              echo "<h2><i class='fas fa-syringe'></i> Treatments</h2>";
              while ($row = $res->fetch_assoc()) {
                  echo "<div class='treatment-card'>";
                  echo "Treatment: " . htmlspecialchars($row['treatment_type']) . "\n";
                  echo "Status: [" . "<span class='badge ".strtolower($row['status'])."'>".htmlspecialchars($row['status'])."</span>" . "]\n";
                  echo "Date: " . htmlspecialchars($row['treatment_date']) . "\n";
                  echo "Details: " . htmlspecialchars($row['details']);
                  echo "</div>";
              }
          }
          $treatments->close();
      }
      ?>
    </section>
  </article>
</section>
</body>
</html>
