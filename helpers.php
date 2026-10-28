<?php
function allocate_kennel($conn, $animalId, $width, $height, $species, $health_status) {
    // Determine size category based on species and dimensions
    $size_category = determine_size_category($width, $height, $species);
    
    // For unhealthy animals, prioritize medical/sick kennels
    if ($health_status === "Unhealthy") {
        $query = "SELECT kennel_id, kennel_name FROM kennels 
                  WHERE (status = 'Available' OR kennel_name LIKE '%sick%' OR kennel_name LIKE '%medical%' OR kennel_name LIKE '%emergency%')
                  AND max_width >= ? 
                  AND max_height >= ?
                  ORDER BY 
                    (kennel_name LIKE '%sick%' OR kennel_name LIKE '%medical%') DESC,
                    (max_width * max_height) ASC 
                  LIMIT 1";
    } else {
        // For healthy animals, prioritize species-specific kennels
        $query = "SELECT kennel_id, kennel_name FROM kennels 
                  WHERE status = 'Available' 
                  AND (kennel_name LIKE CONCAT('%', ?, '%') OR notes LIKE CONCAT('%', ?, '%') OR kennel_name LIKE CONCAT('%', ?, '%'))
                  AND max_width >= ? 
                  AND max_height >= ?
                  ORDER BY 
                    (kennel_name LIKE CONCAT('%', ?, '%') OR notes LIKE CONCAT('%', ?, '%')) DESC,
                    (kennel_name LIKE CONCAT('%', ?, '%')) DESC,
                    (max_width * max_height) ASC 
                  LIMIT 1";
    }
    
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    if ($health_status === "Unhealthy") {
        $stmt->bind_param("dd", $width, $height);
    } else {
        $stmt->bind_param("sssddsds", $species, $species, $size_category, $width, $height, $species, $species, $size_category);
    }
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $kennel = $result->fetch_assoc();
        $kennelId = $kennel['kennel_id'];
        $kennelName = $kennel['kennel_name'];
        
        // Update kennel status to occupied
        $updateKennel = $conn->prepare("UPDATE kennels SET status = 'Occupied' WHERE kennel_id = ?");
        if (!$updateKennel) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $updateKennel->bind_param("i", $kennelId);
        if (!$updateKennel->execute()) {
            throw new Exception("Execute failed: " . $updateKennel->error);
        }
        
        // Create animal-kennel assignment
        $assign = $conn->prepare("INSERT INTO animal_kennel (animal_id, kennel_id, assignment_date) VALUES (?, ?, NOW())");
        if (!$assign) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $assign->bind_param("ii", $animalId, $kennelId);
        if (!$assign->execute()) {
            throw new Exception("Execute failed: " . $assign->error);
        }
        
        return array('kennel_id' => $kennelId, 'kennel_name' => $kennelName);
    }
    
    return false; // No suitable kennel found
}

// Helper function to determine size category
function determine_size_category($width, $height, $species) {
    $size = $width * $height;
    
    if ($species === "Dog") {
        if ($size < 2) return "small";
        if ($size < 6) return "medium";
        if ($size < 12) return "large";
        return "extra-large";
    } elseif ($species === "Cat") {
        if ($size < 1.5) return "small";
        return "medium";
    } elseif ($species === "Bird") {
        if ($size < 0.5) return "small";
        if ($size < 2) return "medium";
        return "large";
    } elseif ($species === "Rabbit") {
        if ($size < 1) return "small";
        if ($size < 2) return "medium";
        return "large";
    } else {
        if ($size < 1) return "small";
        if ($size < 3) return "medium";
        return "large";
    }
}

// Function to place animal in emergency cell
function place_in_emergency_cell($conn, $animalId, $width, $height) {
    // Check if emergency cell exists, if not create one large enough
    $checkQuery = "SELECT kennel_id, max_width, max_height FROM kennels WHERE kennel_name = 'Emergency Cell'";
    $result = $conn->query($checkQuery);
    
    if ($result && $result->num_rows === 0) {
        // Create emergency cell large enough for the animal
        $emergencyWidth = max(10, $width * 1.5); // At least 1.5x animal width
        $emergencyHeight = max(10, $height * 1.5); // At least 1.5x animal height
        
        $createQuery = "INSERT INTO kennels (kennel_name, max_width, max_height, status, notes) 
                        VALUES ('Emergency Cell', ?, ?, 'Occupied', 'Emergency holding area for animals without allocated kennels')";
        
        $createStmt = $conn->prepare($createQuery);
        if (!$createStmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $createStmt->bind_param("dd", $emergencyWidth, $emergencyHeight);
        if (!$createStmt->execute()) {
            throw new Exception("Execute failed: " . $createStmt->error);
        }
        
        $emergencyId = $conn->insert_id;
    } else if ($result) {
        $row = $result->fetch_assoc();
        $emergencyId = $row['kennel_id'];
        
        // Check if emergency cell is large enough, if not, update it
        if ($row['max_width'] < $width || $row['max_height'] < $height) {
            $newWidth = max($row['max_width'], $width * 1.5);
            $newHeight = max($row['max_height'], $height * 1.5);
            
            $updateQuery = "UPDATE kennels SET max_width = ?, max_height = ? WHERE kennel_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            if (!$updateStmt) {
                throw new Exception("Prepare failed: " . $conn->error);
            }
            
            $updateStmt->bind_param("ddi", $newWidth, $newHeight, $emergencyId);
            if (!$updateStmt->execute()) {
                throw new Exception("Execute failed: " . $updateStmt->error);
            }
        }
    } else {
        throw new Exception("Error checking emergency cell: " . $conn->error);
    }
    
    // Assign animal to emergency cell
    $assign = $conn->prepare("INSERT INTO animal_kennel (animal_id, kennel_id, assignment_date, is_emergency) VALUES (?, ?, NOW(), 1)");
    if (!$assign) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $assign->bind_param("ii", $animalId, $emergencyId);
    if (!$assign->execute()) {
        throw new Exception("Execute failed: " . $assign->error);
    }
    
    // Update animal status to indicate it's in emergency cell
    $updateAnimal = $conn->prepare("UPDATE animals SET Kennel_status = 'Emergency Holding', status = 'Not Available' WHERE id = ?");
    if (!$updateAnimal) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $updateAnimal->bind_param("i", $animalId);
    if (!$updateAnimal->execute()) {
        throw new Exception("Execute failed: " . $updateAnimal->error);
    }
    
    return $emergencyId;
}

// Function to get animals in emergency cell
function get_emergency_animals($conn) {
    $query = "SELECT a.id, a.Aname, a.Species, a.Width, a.Height 
              FROM animals a 
              JOIN animal_kennel ak ON a.id = ak.animal_id 
              JOIN kennels k ON ak.kennel_id = k.kennel_id 
              WHERE k.kennel_name = 'Emergency Cell' AND ak.is_emergency = 1 AND ak.release_date IS NULL";
    
    $result = $conn->query($query);
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    return $result;
}

// Function to manually allocate kennel to emergency animal
function manual_allocate_kennel($conn, $animalId, $kennelId) {
    $conn->begin_transaction();
    try {
        // Remove from emergency cell
        $remove = $conn->prepare("UPDATE animal_kennel SET release_date = NOW() WHERE animal_id = ? AND is_emergency = 1 AND release_date IS NULL");
        if (!$remove) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $remove->bind_param("i", $animalId);
        if (!$remove->execute()) {
            throw new Exception("Execute failed: " . $remove->error);
        }
        
        // Assign to new kennel
        $assign = $conn->prepare("INSERT INTO animal_kennel (animal_id, kennel_id, assignment_date) VALUES (?, ?, NOW())");
        if (!$assign) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $assign->bind_param("ii", $animalId, $kennelId);
        if (!$assign->execute()) {
            throw new Exception("Execute failed: " . $assign->error);
        }
        
        // Update kennel status
        $updateKennel = $conn->prepare("UPDATE kennels SET status = 'Occupied' WHERE kennel_id = ?");
        if (!$updateKennel) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $updateKennel->bind_param("i", $kennelId);
        if (!$updateKennel->execute()) {
            throw new Exception("Execute failed: " . $updateKennel->error);
        }
        
        // Update animal status
        $kennel_stmt = $conn->prepare("SELECT kennel_name FROM kennels WHERE kennel_id = ?");
        $kennel_stmt->bind_param("i", $kennelId);
        $kennel_stmt->execute();
        $kennel_result = $kennel_stmt->get_result();
        $kennel = $kennel_result->fetch_assoc();
        
        $updateAnimal = $conn->prepare("UPDATE animals SET Kennel_status = ?, status = 'Available' WHERE id = ?");
        if (!$updateAnimal) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $updateAnimal->bind_param("si", $kennel['kennel_name'], $animalId);
        if (!$updateAnimal->execute()) {
            throw new Exception("Execute failed: " . $updateAnimal->error);
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}
?>
