<?php
require_once __DIR__ . '/config.php';
// Show all errors for debugging purposes
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set the content type to JSON
header('Content-Type: application/json');

// Database connection
$serverName = DB_HOST;
$username   = DB_USER;
$password   = DB_PASS;
$database   = DB_NAME;

$conn = new mysqli($serverName, $username, $password, $database);

// Check for connection error
if ($conn->connect_error) {
    echo json_encode(['error' => 'Database Connection failed: ' . $conn->connect_error]);
    exit;
}

// Query to get the number of reports by animal type
$animalQuery = "SELECT animal_type, COUNT(*) AS total_reports FROM cruelty_report GROUP BY animal_type";
$animalResult = $conn->query($animalQuery);

// Check for SQL error or empty result
if ($conn->error) {
    echo json_encode(['error' => 'SQL Error: ' . $conn->error]);
    exit;
}

if ($animalResult->num_rows === 0) {
    echo json_encode(['error' => 'No data found for animal reports']);
    exit;
}

// Prepare animal data
$animalData = [];
while ($row = $animalResult->fetch_assoc()) {
    $animalData[] = [$row['animal_type'], (int)$row['total_reports']];
}

// Query to get the number of reports by cruelty type
$crueltyQuery = "SELECT cruelty_type, COUNT(*) AS total_reports FROM cruelty_report GROUP BY cruelty_type";
$crueltyResult = $conn->query($crueltyQuery);

// Check for SQL error or empty result
if ($conn->error) {
    echo json_encode(['error' => 'SQL Error: ' . $conn->error]);
    exit;
}

if ($crueltyResult->num_rows === 0) {
    echo json_encode(['error' => 'No data found for cruelty reports']);
    exit;
}

// Prepare cruelty type data
$crueltyData = [];
while ($row = $crueltyResult->fetch_assoc()) {
    $crueltyData[] = [$row['cruelty_type'], (int)$row['total_reports']];
}

// Query to get the number of investigations by case status
// Adjust this query if needed based on the actual column name (status or case_status)
$caseStatusQuery = "SELECT status, COUNT(*) AS total_cases FROM cruelty_investigation GROUP BY status";
$caseStatusResult = $conn->query($caseStatusQuery);

// Check for SQL error or empty result
if ($conn->error) {
    echo json_encode(['error' => 'SQL Error: ' . $conn->error]);
    exit;
}

if ($caseStatusResult->num_rows === 0) {
    echo json_encode(['error' => 'No data found for case status']);
    exit;
}

// Prepare case status data
$caseStatusData = [];
while ($row = $caseStatusResult->fetch_assoc()) {
    $caseStatusData[] = [$row['status'], (int)$row['total_cases']];
}

// Prepare the response
$response = [
    'animal_data' => $animalData,
    'cruelty_data' => $crueltyData,
    'case_status_data' => $caseStatusData
];

// Send the response as JSON
echo json_encode($response);

// Close the connection
$conn->close();
?>
