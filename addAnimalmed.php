<?php
include 'databaseConnection.php';
 
    $medid = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $vetid = intval($_POST['Vet_id']);
    $Appdate = $_POST['Appointment_Date'];
    $treatment = $_POST['Treatment_type'];

$stmt = $conn->prepare("INSERT INTO medical_care (Animal_id, Vet_id, Appointment_Date, Treatment_type)
         VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiss", $medid, $vetid, $Appdate, $treatment);
$result = $stmt->execute();
if ($result===False)
    {echo "Information can't be added to the database";}
else{
    echo "Medical information successfully added in the database";
}
echo "<a href='ShowAnimal_profile.php?id=" . $medid . "' class='btn update-btn'><button>Back to show animal profile</button></a>";

//If the user is vet show the following buttonS (Add medical record and update medical record)

    session_start(); // Ensure session is active

//if ($_SESSION['userrole'] === 'Vet') {
   // echo '<a href="addAnimalmed.php?animal_id=' . $animal_id . '">Add a medical record</a>';

//} else{

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Entering animal medical information</title>
</head>
<body>
<h2>Add Medical information</h2>
<form action="" method="POST">
<fieldset>
<br>
<label for="animal_id">Animal id:
<input type="number" id="dog_id" name="dog_id" value="<?php echo $medid; ?>" readonly><br><br>
<label for="Vet_id">Vet_id:</label>
<input type="number" id="Vet_id" name="Vet_id" required><br><br>

<label for="Appointment_Date"> Appointment Date:</label>
<input type="date" id="Appointment_Date" name="Appointment_Date" required><br><br>
        
            
<label for="Treatment_type">Treatment type:</label>
<select id="Treatment_type" name="Treatment_type">
<option value="Treatment_type">Vaccination</option>
<option value="Treatment_type">Spayed</option>
<option value="Treatment_type">Neutering</option>
</select><br><br>
<input type="submit" value="Submit Medical care info"><br><br>
</form>
</fieldset>
</body>
</html>