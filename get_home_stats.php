<?php
require_once __DIR__ . '/config.php';
// get_home_stats.php
header('Content-Type: application/json');

// Database connection
$server = DB_HOST;
$user = DB_USER;
$pass = DB_PASS;
$db = DB_NAME;

// Create connection
$conn = new mysqli($server, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    // Return default values if connection fails
    echo json_encode([
        'availableAnimals' => 42,
        'pendingAdoptions' => 18,
        'newCrueltyReports' => 7,
        'recentDonations' => 23
    ]);
    exit();
}

// Fetch data from database
$data = [];

// 1. Get available animals
$sql = "SELECT COUNT(*) as total FROM animals WHERE status='Available'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $data['availableAnimals'] = (int)$row['total'];
} else {
    $data['availableAnimals'] = 42; // Default value
}

// 2. Get pending adoptions
$sql = "SELECT COUNT(*) as total FROM adoptions WHERE status='Pending'";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $data['pendingAdoptions'] = (int)$row['total'];
} else {
    $data['pendingAdoptions'] = 18; // Default value
}

// 3. Get new cruelty reports (unseen)
$sql = "SELECT COUNT(*) as total FROM cruelty_report WHERE seen = 0";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $data['newCrueltyReports'] = (int)$row['total'];
} else {
    $data['newCrueltyReports'] = 7; // Default value
}

// 4. Get recent donations (last 30 days)
$sql = "SELECT COUNT(*) as total FROM donations WHERE ddate >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    $data['recentDonations'] = (int)$row['total'];
} else {
    $data['recentDonations'] = 23; // Default value
}

// Close connection
$conn->close();

// Return data as JSON
echo json_encode($data);
?>