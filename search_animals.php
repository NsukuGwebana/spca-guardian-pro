<?php
require_once __DIR__ . '/bootstrap.php';
// Enable error reporting for debugging

$conn = db();

// Check if search term is provided
if (isset($_POST['search_term']) && !empty($_POST['search_term'])) {
    $searchTerm = trim($_POST['search_term']);
    $like = '%' . $searchTerm . '%';
    
    // Build the query with wildcard search across multiple fields
    $query = "SELECT 
                id, Aname, Species, Animal_breed, Aage, Color, 
                Kennel_status, Aweight, Width, Height, Health_status, 
                Intake_date, status, animal_picture, treatment_status, seen
              FROM animals 
              WHERE is_deleted = 0 
                AND (Aname LIKE ? OR Species LIKE ? OR Animal_breed LIKE ? OR Color LIKE ? OR Kennel_status LIKE ?
                     OR Health_status LIKE ? OR status LIKE ? OR treatment_status LIKE ? OR id LIKE ?)
              ORDER BY Aname";
    
    $result = q($query, array_fill(0, 9, $like)); // prepared statement
    
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
?>