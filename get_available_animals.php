<?php
require_once __DIR__ . '/config.php';
// get_available_animals.php
header('Content-Type: application/json');

// DB connection - using your credentials
$serverName = DB_HOST;
$username   = DB_USER;
$password   = DB_PASS;
$database   = DB_NAME;

$conn = new mysqli($serverName, $username, $password, $database);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed: " . $conn->connect_error]);
    exit();
}
$conn->set_charset("utf8mb4");

// Fetch available healthy animals
$animals = [];
$res = $conn->query("
    SELECT id, Aname, Species, Animal_breed, Aage, Color, animal_picture
    FROM animals
    WHERE Health_status = 'Healthy' AND status = 'Available' AND is_deleted = 0
    ORDER BY created_at DESC, id DESC
    LIMIT 4
");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $animals[] = $row;
    }
} else {
    http_response_code(500);
    echo json_encode(["error" => "Query failed: " . $conn->error]);
    $conn->close();
    exit();
}

$conn->close();
echo json_encode($animals);
?>