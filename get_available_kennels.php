<?php
// get_available_kennels.php
header('Content-Type: application/json');

require_once "databaseConnection.php";

$species = $_GET['species'] ?? '';
$width   = $_GET['width'] ?? 0;
$height  = $_GET['height'] ?? 0;

try {
    // Prepare SQL to select available kennels matching size
    $stmt = $conn->prepare("
        SELECT kennel_id, kennel_name, max_width, max_height, status 
        FROM kennels 
        WHERE status = 'Available'
        AND max_width >= ? 
        AND max_height >= ?
        AND (species_allowed = ? OR species_allowed = 'Any')
    ");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("dds", $width, $height, $species);
    $stmt->execute();

    $result = $stmt->get_result();
    $kennels = [];

    while ($row = $result->fetch_assoc()) {
        $kennels[] = $row;
    }

    echo json_encode($kennels);
} catch (Exception $e) {
    echo json_encode([]);
}


