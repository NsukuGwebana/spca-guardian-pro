<?php
require_once 'databaseConnection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $animal_id        = (int)($_POST['animal_id'] ?? 0);
    $vet_id           = (int)($_POST['vet_id'] ?? 0);
    $treatment_date   = $_POST['Treatmentdate'] ?? date('Y-m-d');
    $treatment_type   = $_POST['Treatmenttype'] ?? '';
    $procedure_status = $_POST['procedure_status'] ?? 'Pending';

    // 1. Insert the new medical procedure (fields from log_procedure.php)
    $stmt_insert = $conn->prepare(
        "INSERT INTO medical_care (Animal_id, Vet_id, Appointment_Date, Treatment_type, procedure_status)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt_insert->bind_param("iisss", $animal_id, $vet_id, $treatment_date, $treatment_type, $procedure_status);

    if ($stmt_insert->execute()) {
        // 2. Determine the new health and treatment status based on the procedure
        $new_health_status = 'Unhealthy';
        $new_treatment_status = 'Under Treatment';

        if ($procedure_status == 'Completed') {
            // Check if all procedures for this animal are completed
            $sql_check_pending = "SELECT COUNT(*) FROM medical_care WHERE Animal_id = ? AND procedure_status = 'Pending'";
            $stmt_check = $conn->prepare($sql_check_pending);
            $stmt_check->bind_param("i", $animal_id);
            $stmt_check->execute();
            $pending_count = $stmt_check->get_result()->fetch_row()[0];

            if ($pending_count == 0) {
                $new_health_status = 'Healthy';
                $new_treatment_status = 'Treatment Completed';
            }
        }

        // 3. Update the animal's status
        $sql_update_animal = "UPDATE animals SET Health_status = ?, treatment_status = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update_animal);
        $stmt_update->bind_param("ssi", $new_health_status, $new_treatment_status, $animal_id);
        
        if ($stmt_update->execute()) {
            $message = "Procedure saved, and animal status updated.";
        } else {
            $message = "Procedure saved, but animal status not updated: " . $stmt_update->error;
        }
    } else {
        $message = "Error saving procedure: " . $stmt_insert->error;
    }

    header("Location: regA.php?message=" . urlencode($message));
    exit();
}
?>