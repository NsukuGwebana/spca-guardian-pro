<?php
// Handles the public Contact Us form (contact.html) and saves the message
// to message_submissions. contact.html reads the plain-text reply:
// any reply containing "Error" is shown to the visitor as a failure.
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/databaseConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: contact.html");
    exit;
}

$first   = trim($_POST['first_name'] ?? '');
$last    = trim($_POST['last_name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($first === '' || $last === '' || $subject === '' || $message === '') {
    echo "Error: please fill in every field.";
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Error: please enter a valid email address.";
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO message_submissions (first_name, last_name, email, subject, message, submission_date)
     VALUES (?, ?, ?, ?, ?, NOW())"
);
$stmt->bind_param("sssss", $first, $last, $email, $subject, $message);

if ($stmt->execute()) {
    echo "Message saved";
} else {
    echo "Error: your message could not be sent. Please try again later.";
}

$stmt->close();
