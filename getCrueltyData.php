<?php
require_once __DIR__ . '/config.php';
// Database connection
$serverName = DB_HOST;
$username = DB_USER;
$password = DB_PASS;
$database = DB_NAME;

$conn = new mysqli($serverName, $username, $password, $database);

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Get filter parameters
$timeFilter = $_GET['time'] ?? 'all';
$animalFilter = $_GET['animal'] ?? 'all';
$crueltyFilter = $_GET['cruelty'] ?? 'all';

// Build WHERE clause based on filters
$whereClause = "WHERE 1=1";

if ($timeFilter !== 'all') {
    if ($timeFilter === 'month') {
        $whereClause .= " AND date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
    } elseif ($timeFilter === 'week') {
        $whereClause .= " AND date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
    }
}

if ($animalFilter !== 'all') {
    $whereClause .= " AND animal_type = '" . $conn->real_escape_string($animalFilter) . "'";
}

if ($crueltyFilter !== 'all') {
    $whereClause .= " AND cruelty_type = '" . $conn->real_escape_string($crueltyFilter) . "'";
}

// Get summary data
$summary = [
    'total_reports' => 0,
    'common_cruelty' => 'N/A',
    'common_animal' => 'N/A'
];

$result = $conn->query("SELECT COUNT(*) as total FROM cruelty_report $whereClause");
if ($result) {
    $summary['total_reports'] = $result->fetch_assoc()['total'];
}

$result = $conn->query("SELECT cruelty_type, COUNT(*) as count FROM cruelty_report $whereClause GROUP BY cruelty_type ORDER BY count DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $summary['common_cruelty'] = $result->fetch_assoc()['cruelty_type'];
}

$result = $conn->query("SELECT animal_type, COUNT(*) as count FROM cruelty_report $whereClause GROUP BY animal_type ORDER BY count DESC LIMIT 1");
if ($result && $result->num_rows > 0) {
    $summary['common_animal'] = $result->fetch_assoc()['animal_type'];
}

// Get data for charts
$data = [
    'summary' => $summary,
    'cruelty_types' => [],
    'animal_types' => [],
    'timeline' => [],
    'locations' => [],
    'recent_reports' => []
];

// Cruelty types data
$result = $conn->query("SELECT cruelty_type, COUNT(*) as count FROM cruelty_report $whereClause GROUP BY cruelty_type");
while ($row = $result->fetch_assoc()) {
    $data['cruelty_types'][] = [$row['cruelty_type'], (int)$row['count']];
}

// Animal types data
$result = $conn->query("SELECT animal_type, COUNT(*) as count FROM cruelty_report $whereClause GROUP BY animal_type");
while ($row = $result->fetch_assoc()) {
    $data['animal_types'][] = [$row['animal_type'], (int)$row['count']];
}

// Timeline data (last 6 months)
$result = $conn->query("
    SELECT DATE_FORMAT(date, '%Y-%m') as month, COUNT(*) as count 
    FROM cruelty_report 
    WHERE date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(date, '%Y-%m') 
    ORDER BY month
");
while ($row = $result->fetch_assoc()) {
    $data['timeline'][] = [$row['month'], (int)$row['count']];
}

// Location data (top 10 locations)
$result = $conn->query("SELECT location, COUNT(*) as count FROM cruelty_report $whereClause GROUP BY location ORDER BY count DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $data['locations'][] = [$row['location'], (int)$row['count']];
}

// Recent reports
$result = $conn->query("SELECT date, reporter_name, animal_type, cruelty_type, location FROM cruelty_report $whereClause ORDER BY date DESC LIMIT 10");
while ($row = $result->fetch_assoc()) {
    $data['recent_reports'][] = $row;
}

// Close connection
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode($data);
?>