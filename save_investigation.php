<?php
$ALLOWED_ROLES = ['Administrator']; // who may open this page
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['cruelty_id'])) { header('Location: view_reports.php'); exit; } // form must be submitted
require_once __DIR__ . '/bootstrap.php';
// Database connection
$conn = db();

// Collect form data
$cruelty_id   = $_POST['cruelty_id'] ?? '';
$investigator = $_POST['investigator'] ?? '';
$status       = $_POST['status'] ?? '';
$actions      = $_POST['actions'] ?? '';
$follow_up    = $_POST['follow_up'] ?? '';
$notes        = $_POST['notes'] ?? '';

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
    log_activity('Saved cruelty investigation', "Report #$cruelty_id, status $status");
    header("Location: view_reports.php?success=1");
    exit();
} else {
    echo "Error. Please try again.";
}

$stmt->close();
?>
