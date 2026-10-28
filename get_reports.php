<?php
require_once __DIR__ . '/config.php';
// Set the content type to JSON so the browser knows it's a JSON response
header('Content-Type: application/json');

// Get filter parameters
$location = $_GET['location'] ?? ''; 
$startDate = $_GET['start_date'] ?? '2025-01-01'; // Default start date
$endDate = $_GET['end_date'] ?? '2025-12-31'; // Default end date

// Set up database connection
$serverName = DB_HOST;
$username   = DB_USER;
$password   = DB_PASS;
$database   = DB_NAME;
$conn = new mysqli($serverName, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("❌ Database Connection failed: " . $conn->connect_error);
}

// Prepare SQL query to fetch data based on the filter parameters
$query = "SELECT 
            YEAR(date) AS year,
            MONTH(date) AS month,
            COUNT(*) AS total_reports
          FROM cruelty_report
          WHERE date BETWEEN ? AND ?";

// Add location filter if provided
if ($location) {
    $query .= " AND location = ?";
}

// Group and order by year and month
$query .= " GROUP BY YEAR(date), MONTH(date) ORDER BY year ASC, month ASC";

// Prepare the statement
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die('MySQL prepare error: ' . $conn->error);
}

// Bind parameters and execute the query
if ($location) {
    $stmt->bind_param('sss', $startDate, $endDate, $location); // 3 parameters: startDate, endDate, location
} else {
    $stmt->bind_param('ss', $startDate, $endDate); // Only 2 parameters: startDate, endDate
}

$stmt->execute();

// Get the result
$result = $stmt->get_result();
$reports = [];

while ($row = $result->fetch_assoc()) {
    $reports[] = [
        'month' => $row['year'] . '-' . str_pad($row['month'], 2, '0', STR_PAD_LEFT), // Format like 2025-03
        'total_reports' => $row['total_reports']
    ];
}

// Return the data as a JSON response
echo json_encode($reports);

// Close the statement and connection
$stmt->close();
$conn->close();
?>
