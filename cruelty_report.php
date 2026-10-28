<?php
require_once __DIR__ . '/config.php';
// Show ALL errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$serverName = DB_HOST;
$username   = DB_USER;
$password   = DB_PASS;
$database   = DB_NAME;

$conn = new mysqli($serverName, $username, $password, $database);

if ($conn->connect_error) {
    die("❌ Database Connection failed: " . $conn->connect_error);
}

// Only process if form submitted
if ($_SERVER["REQUEST_METHOD"] === "POST") {

   
    // Collect POST values
    $reporter_name = $_POST['reporter_name'] ?? '';
    $contact_info  = $_POST['contact_info'] ?? '';
    $animal_type   = $_POST['animal_type'] ?? '';
    $cruelty_type  = $_POST['cruelty_type'] ?? '';
    $location      = $_POST['location'] ?? '';
    $witnesses     = $_POST['witnesses'] ?? '';
    $date          = $_POST['date'] ?? '';
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
        die("❌ SQL Prepare failed: " . $conn->error);
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
        echo "<h2 style='color:green; text-align:center;'>✅ Report submitted successfully!</h2>";
        echo "<p style='text-align:center;'><a href='dashboard.html'>Back to Dashboard</a></p>";
    } else {
        echo "<h2 style='color:red; text-align:center;'>❌ Error submitting report: " . $stmt->error . "</h2>";
    }

    $stmt->close();

} else {
    // This else matches the POST check
    echo "⚠️ No form data received.";
}

// Close connection
$conn->close();
?>

