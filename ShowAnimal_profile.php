<?php
// Connect to database
include "databaseConnection.php";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get animal ID from URL
$animal_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch animal info
$animal_sql = "SELECT * FROM animals WHERE id = $animal_id";
$animal_result = $conn->query($animal_sql);

if ($animal_result->num_rows > 0) {
    $animal = $animal_result->fetch_assoc();
    
    echo "<h2>Animal Profile</h2>";
    echo "<p><strong>Animal id:</strong> " . htmlspecialchars($animal['id']) . "</p>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($animal['Aname']) . "</p>";
    echo "<p><strong>Breed:</strong> " . htmlspecialchars($animal['Animal_breed']) . "</p>";
    //echo "<p><strong>Height:</strong> " . htmlspecialchars($animal['Animal_height']) . "</p>";
    echo "<p><strong>Weight:</strong> " . htmlspecialchars($animal['Aweight']) . "</p>";
    echo "<p><strong>Health Status:</strong> " . htmlspecialchars($animal['Health_status']) . "</p>";
    echo "<p><strong>Date Registered:</strong> " . htmlspecialchars($animal['Intake_date']) . "</p>";
    


    // Fetch medical info
   $medication_sql = "SELECT * FROM medical_care WHERE Animal_id = $animal_id ORDER BY Appointment_Date DESC";
   $med_result = $conn->query($medication_sql);

    if ($med_result->num_rows > 0) {
        echo "<h3>Medical Records</h3>";
        while ($med = $med_result->fetch_assoc()) {
            echo "<table='border:1px solid #ccc; padding:10px; margin-bottom:10px;'>";
            echo "<p><strong>Vet id:</strong> " . htmlspecialchars($med['Vet_id']). "</p>";
            echo "<p><strong>Visit Date:</strong> " . htmlspecialchars($med['Appointment_Date']) . "</p>";
            echo "<p><strong>Treatment:</strong> " . htmlspecialchars($med['Treatment_type']) . "</p>";
            echo "</table>";
        }
    } else {
        echo "<p>No medical records available yet.</p>";
    }
} else {
    echo "<p>Animal not found.</p>";
}
//If the user is vet show the following buttonS (Add medical record and update medical record)

    session_start(); // Ensure session is active

//if ($_SESSION['userrole'] === 'Vet') {
   // echo '<a href="addAnimalmed.php?animal_id=' . $animal_id . '">Add a medical record</a>';

//} else{

$conn->close();
//}
?>
