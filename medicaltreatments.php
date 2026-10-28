<?php 
include ('databaseConnection.php');

$vet_id = null; 
$app_date = null; // 👈 store appointment date

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $appointment_id = $_POST['app_id'];
    $treatments = $_POST['treatment_type'] ?? [];
    $custom = $_POST['treatment_details'] ?? '';

    // ✅ Get vet info + appointment date
    $stmt = $conn->prepare("SELECT animal_id, app_date, vet_id FROM appointments WHERE app_id=?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $animal_id = $row['animal_id'];
    $vet_id = $row['vet_id']; 
    $app_date = $row['app_date']; // 👈 use as treatment date
    $stmt->close();

    $compulsory = ['Vaccination', 'Spaying', 'Neutering'];

    if (empty($treatments)) {
        $message = "<p style='color:red;'>❌ Please select at least one treatment.</p>";
    } else {
        foreach ($treatments as $type) {
            $details = ($type === 'Other') ? $custom : $type;
            $treatment_date = $app_date; // 👈 force treatment date = appointment date
            $stmt = $conn->prepare("
                INSERT INTO treatments (app_id, treatment_types, treatment_details, treatment_date, recorded_by, treatment_status)
                VALUES (?, ?, ?, ?, ?, 'InProgress')
            ");
            $stmt->bind_param("issss", $appointment_id, $type, $details, $treatment_date, $vet_id);
            $stmt->execute();
        }

        // ✅ Check compulsory treatments
        $stmt = $conn->prepare("
            SELECT DISTINCT treatment_types
            FROM treatments
            WHERE app_id IN (SELECT app_id FROM appointments WHERE animal_id=?)
        ");
        $stmt->bind_param("i", $animal_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $done = [];
        while ($r = $result->fetch_assoc()) {
            if (in_array($r['treatment_types'], $compulsory)) {
                $done[] = $r['treatment_types'];
            }
        }
        $unique_done = array_unique($done);

        $treatment_status = (count(array_intersect($compulsory, $unique_done)) === count($compulsory))
            ? 'Completed'
            : 'InProgress';

        // ✅ Update treatments
        $stmt = $conn->prepare("
            UPDATE treatments SET treatment_status=? 
            WHERE app_id IN (SELECT app_id FROM appointments WHERE animal_id=?)
        ");
        $stmt->bind_param("si", $treatment_status, $animal_id);
        $stmt->execute();

        // ✅ Mark appointment completed
        $app_status = 'Completed';
        $stmt = $conn->prepare("UPDATE appointments SET app_status=? WHERE app_id=?");
        $stmt->bind_param("si", $app_status, $appointment_id);
        $stmt->execute();

        $message = "<p style='color:green;'>✅ Treatment logged. Appointment <b>$app_status</b>. Animal’s treatments <b>$treatment_status</b>. Vet ID <b>$vet_id</b> was recorded.</p>";
    }
}

// Load appointments
$appointments = [];
$result = $conn->query("
    SELECT a.app_id, a.app_date, animals.Aname
    FROM appointments a
    JOIN animals ON a.animal_id = animals.id
    ORDER BY a.app_id ASC
");
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }
}

// Treatment options
$all_treatments = ['Vaccination','Spaying','Neutering','Antibiotics','Surgery','Dental Care','Other'];
$available_treatments = $all_treatments;

$app_id = $_POST['app_id'] ?? null;

if (!empty($app_id)) { 
    $app_id = intval($app_id);
    $stmt = $conn->prepare("SELECT animal_id, vet_id, app_date FROM appointments WHERE app_id=?");
    $stmt->bind_param("i", $app_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $animal = $res->fetch_assoc();
    $animal_id = $animal['animal_id'] ?? null;
    $vet_id = $animal['vet_id'] ?? null;
    $app_date = $animal['app_date'] ?? null;

    if ($animal_id) {
        $stmt = $conn->prepare("
            SELECT DISTINCT treatment_types FROM treatments
            WHERE app_id IN (SELECT app_id FROM appointments WHERE animal_id=?)
        ");
        $stmt->bind_param("i", $animal_id);
        $stmt->execute();
        $done_res = $stmt->get_result();

        $already_done = [];
        while ($row = $done_res->fetch_assoc()) {
            $already_done[] = $row['treatment_types'];
        }
        $available_treatments = array_diff($all_treatments, $already_done);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log Medical Treatment</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    min-height: 100vh;  /* page always at least 100% viewport height */
    background: linear-gradient(
      135deg, 
      rgba(19, 79, 92, 0.92),
      rgba(15, 63, 74, 0.95)
    );
    background-attachment: fixed; /* 👈 keeps gradient fixed when scrolling */
    color: #134f5c;
}

section {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 60px 20px;
}


    article {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.35);
        max-width: 800px;
        width: 100%;
    }
    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #134f5c;
    }
    label { font-weight: bold; display: block; margin-bottom: 6px; }
    input, select { width: 100%; padding: 10px; border: 1px solid #134f5c; border-radius: 8px; margin-bottom: 16px; }
    input[type="checkbox"] { width: auto; margin-right: 8px; }
    .btn { background: #134f5c; color: #fff; padding: 12px 18px; border-radius: 8px; cursor: pointer; border: none; }
    .btn:hover { background: #0f3f4a; }
    .back-btn { background: #60a5b3; margin-left: 10px; }
    .back-btn:hover { background: #3d8a95; }
  </style>
</head>
<body>
<section>
<article>
  <h2><i class="fas fa-notes-medical"></i> Log Medical Treatment</h2>
  <?php if (isset($message)) echo $message; ?>
  <form method="POST">
    <label><i class="fas fa-id-card"></i> Appointment:</label>
    <select name="app_id" required onchange="this.form.submit()">
      <option value="">-- Select Appointment --</option>
      <?php foreach ($appointments as $app): ?>
        <option value="<?= $app['app_id'] ?>" <?= (!empty($_POST['app_id']) && $_POST['app_id'] == $app['app_id']) ? 'selected' : '' ?>>
          <?= $app['app_id'] ?> - <?= htmlspecialchars($app['Aname']) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label><i class="fas fa-syringe"></i> Select Treatments:</label>
    <?php if (!empty($available_treatments)): ?>
      <?php foreach ($available_treatments as $t): ?>
        <p><input type="checkbox" name="treatment_type[]" value="<?= htmlspecialchars($t) ?>"> <?= htmlspecialchars($t) ?></p>
      <?php endforeach; ?>
    <?php else: ?>
      <p style="color:red;">All treatments already recorded.</p>
    <?php endif; ?>

    <label><i class="fas fa-comment-dots"></i> If 'Other', specify:</label>
    <input type="text" name="treatment_details">

    <label><i class="fas fa-calendar-day"></i> Date of Treatment (matches appointment):</label>
    <input type="date" value="<?= htmlspecialchars($app_date ?? '') ?>" readonly>

    <?php if (!empty($vet_id)): ?>
        <label><i class="fas fa-user-md"></i> Vet ID:</label>
        <input type="text" value="<?= htmlspecialchars($vet_id) ?>" readonly>
    <?php endif; ?>

    <button type="submit" class="btn"><i class="fas fa-save"></i> Save Treatment</button>
<a href="VetDashboard.php" class="btn back-btn"><i></i> Back</a>

  </form>
</article>
</section>
</body>
</html>
