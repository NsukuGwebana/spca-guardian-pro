<?php
// ... (Your connection and session code)

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $animal_id = $_POST['animal_id'];
    $procedure_status = $_POST['procedure_status'];

    // 1. Insert the new medical procedure
    $sql_insert = "INSERT INTO medical_care (...) VALUES (...)";
    // ... (Your existing insertion code)

    if ($stmt_insert->execute()) {
        // 2. Determine the new health and treatment status based on the procedure
        $new_health_status = 'Unhealthy';
        $new_treatment_status = 'Under Treatment';

        if ($procedure_status == 'Completed') {
            // Check if all procedures for this animal are completed
            $sql_check_pending = "SELECT COUNT(*) FROM medical_care WHERE id = ? AND procedure_status = 'Pending'";
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