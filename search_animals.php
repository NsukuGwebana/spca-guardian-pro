<?php
require_once __DIR__ . '/config.php';
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// Check if search term is provided
if (isset($_POST['search_term']) && !empty($_POST['search_term'])) {
    $searchTerm = $conn->real_escape_string($_POST['search_term']);
    
    // Build the query with wildcard search across multiple fields
    $query = "SELECT 
                id, Aname, Species, Animal_breed, Aage, Color, 
                Kennel_status, Aweight, Width, Height, Health_status, 
                Intake_date, status, animal_picture, treatment_status, seen
              FROM animals 
              WHERE is_deleted = 0 
                AND (Aname LIKE '%$searchTerm%' 
                     OR Species LIKE '%$searchTerm%' 
                     OR Animal_breed LIKE '%$searchTerm%' 
                     OR Color LIKE '%$searchTerm%' 
                     OR Kennel_status LIKE '%$searchTerm%' 
                     OR Health_status LIKE '%$searchTerm%' 
                     OR status LIKE '%$searchTerm%' 
                     OR treatment_status LIKE '%$searchTerm%'
                     OR id LIKE '%$searchTerm%')
              ORDER BY Aname";
    
    $result = $conn->query($query);
    
    $animals = array();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $animals[] = $row;
        }
    }
    
    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($animals);
} else {
    echo json_encode(array());
}

$conn->close();
?>