<?php
$ALLOWED_ROLES = ['Administrator', 'Vet']; // who may open this page
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
require_once 'databaseConnection.php';
$animal_id = (int)($_GET['id'] ?? 0);

//$sql3 = "INSERT INTO medical_care() ";
//$stmt = $conn->prepare($sql3);
//$stmt3->bind_param("i", $animal_id);
//$stmt3->execute();
//$medStatus = $stmt->get_result()->fetch_assoc();

$sql = "SELECT Aname, Health_status FROM animals WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $animal_id);
$stmt->execute();
$animal = $stmt->get_result()->fetch_assoc();
if (!$animal) { die('<p>Animal not found.</p>'); }

$sql2 = "SELECT COUNT(*) AS count FROM medical_care WHERE id = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $animal_id);
$stmt2->execute();
$record = $stmt2->get_result()->fetch_assoc();

//if ($animal['Health_status'] == 'Healthy') {
    // (status message is shown in the page body below)
//} elseif ($record['count'] > 0) {
    //echo "Medical record already exists.";
//} else {
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log Medical Procedure for <?= htmlspecialchars($animal['Aname']) ?></title>
    <link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<header class="spca-topbar"><a class="spca-brand" href="dashboard.php"><span>SPCA</span>Guardian Pro</a><nav><a href="dashboard.php">&larr; My dashboard</a><a href="logoutForm.html">Log out</a></nav></header>
    <h2>Log Medical Procedure for <?= htmlspecialchars($animal['Aname']) ?></h2>
    <?php 
    if ($animal['Health_status'] == 'Healthy') {
        echo "<p>This animal is currently listed as healthy. You can still log a procedure, which will update its status to 'Under Treatment'.</p>";
    }
    ?>

    <form method="POST" action="SaveProcedure.php">
        <input type="hidden" name="animal_id" value="<?= $animal_id ?>">
        <label>Treatment Type:</label><input type="text" name="Treatmenttype" required><br>
        <label>Date:</label><input type="date" name="Treatmentdate" value="<?= date('Y-m-d') ?>" required><br>
        <label>Vet ID:</label><input type="number" name="vet_id" required><br>
        <label>Notes:</label><textarea name="notes" required></textarea><br>
        
        <label>Procedure Status:</label>
        <select name="procedure_status" required>
            <option value="Pending">Pending</option>
            <option value="Completed">Completed</option>
        </select><br>

        <button type="submit">Save Procedure Information</button>
    </form>
</body>
</html>
<?php  ?>
