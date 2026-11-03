<?php
require_once __DIR__ . '/bootstrap.php';
// get_available_animals.php
header('Content-Type: application/json');

$conn = db();

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
    echo json_encode(["error" => "Query failed. Please try again."]);
    exit();
}
echo json_encode($animals);
?>