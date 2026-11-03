<?php
require_once __DIR__ . '/bootstrap.php';
// Show ALL errors

$conn = db();


// Only process if form submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

   
    // Collect POST values
    $reporter_name = $_POST['reporter_name'] ?? '';
    $contact_info  = $_POST['contact_info'] ?? '';
    $animal_type   = $_POST['animal_type'] ?? '';
    $cruelty_type  = $_POST['cruelty_type'] ?? '';
    $location      = $_POST['location'] ?? '';
    $witnesses     = $_POST['witnesses'] ?? '';
    $date          = date('Y-m-d'); // reports are dated the day they're sent
    $description   = $_POST['description'] ?? '';

    // Check if required fields are empty
    if (empty($reporter_name) || empty($contact_info) || empty($animal_type) || empty($cruelty_type) || empty($location) || empty($date) || empty($description)) {
        die("⚠️ Please fill in all required fields.");
    }

    // Prepare query
    $sql = "INSERT INTO cruelty_report 
        (reporter_name, contact_info, animal_type, cruelty_type, location, witnesses, date, description, created_at, seen) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 0)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        die("❌ SQL Prepare failed. Please try again.");
    }

    $stmt->bind_param("ssssssss", 
        $reporter_name,
        $contact_info,
        $animal_type,
        $cruelty_type,
        $location,
        $witnesses,
        $date,
        $description
    );

    if ($stmt->execute()) {
        log_activity('New cruelty report', 'Report #' . $stmt->insert_id);
        echo "<h2 style='color:green; text-align:center;'>✅ Report submitted successfully!</h2>";
        echo "<p style='text-align:center;'><a href='Homepage.html' class='back-btn'>&larr; Back to home</a></p>";
    } else {
        echo "<p class='error'>Sorry, your report couldn't be sent. Please try again or call us.</p>";
    }

    $stmt->close();

} else {
    // This else matches the POST check
    echo "⚠️ No form data received.";
}

// Close connection
?>

