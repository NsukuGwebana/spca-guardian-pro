<?php
require_once __DIR__ . '/config.php';
// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Collect form data
$cruelty_id   = $_POST['cruelty_id'];
$investigator = $_POST['investigator'];
$status       = $_POST['status'];
$actions      = $_POST['actions'];
$follow_up    = $_POST['follow_up'];
$notes        = $_POST['notes'];

// Insert or update investigation
$sql = "INSERT INTO cruelty_investigation (cruelty_id, investigator, status, actions, follow_up, notes)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            investigator = VALUES(investigator),
            status = VALUES(status),
            actions = VALUES(actions),
            follow_up = VALUES(follow_up),
            notes = VALUES(notes)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssss", $cruelty_id, $investigator, $status, $actions, $follow_up, $notes);

if ($stmt->execute()) {
    header("Location: view_reports.php?success=1");
    exit();
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
