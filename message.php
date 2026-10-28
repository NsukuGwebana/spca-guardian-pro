<?php
/**
 * Contact form handler (contact.html posts here with fetch).
 * Saves the message to contact_submissions, which admins read in View_contact.php.
 * The page treats any response containing "Error" as a failure and shows it to the visitor.
 */
require_once __DIR__ . '/config.php';
require_once 'databaseConnection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Error: please submit the contact form.');
}

$first_name = trim($_POST['first_name'] ?? '');
$last_name  = trim($_POST['last_name'] ?? '');
$email      = trim($_POST['email'] ?? '');
$subject    = trim($_POST['subject'] ?? '');
$message    = trim($_POST['message'] ?? '');

if ($first_name === '' || $last_name === '' || $email === '' || $subject === '' || $message === '') {
    exit('Error: please fill in every field.');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Error: please enter a valid email address.');
}

// Create the table on first use, matching the columns View_contact.php displays
$conn->query("CREATE TABLE IF NOT EXISTS contact_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $conn->prepare(
    "INSERT INTO contact_submissions (first_name, last_name, email, subject, message, submission_date)
     VALUES (?, ?, ?, ?, ?, NOW())"
);
if (!$stmt) {
    exit('Error: the message could not be saved. Please try again later.');
}
$stmt->bind_param("sssss", $first_name, $last_name, $email, $subject, $message);

if ($stmt->execute()) {
    echo 'Message received';
} else {
    echo 'Error: the message could not be saved. Please try again later.';
}
$stmt->close();
$conn->close();
