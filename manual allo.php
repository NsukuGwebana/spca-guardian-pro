<?php
include("databaseConnection.php");

// Handle form submission
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal_id = intval($_POST['animal_id']);
    $kennel_id = intval($_POST['kennel_id']);
    
    try {
        // Check if animal is already assigned to a kennel
        // Using assignment_date to check for active assignments since end_date doesn't exist
        $check_stmt = $conn->prepare("SELECT kennel_id FROM animal_kennel WHERE animal_id = ?");
        $check_stmt->bind_param("i", $animal_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        // Get the new kennel name for updating animals table
        $kennel_name_stmt = $conn->prepare("SELECT kennel_name FROM kennels WHERE kennel_id = ?");
        $kennel_name_stmt->bind_param("i", $kennel_id);
        $kennel_name_stmt->execute();
        $kennel_name_result = $kennel_name_stmt->get_result();
        $new_kennel = $kennel_name_result->fetch_assoc();
        $new_kennel_name = $new_kennel['kennel_name'];
        
        if ($check_result->num_rows > 0) {
            $current_assignment = $check_result->fetch_assoc();
            if ($current_assignment['kennel_id'] == $kennel_id) {
                $message = "This animal is already assigned to the selected kennel.";
            } else {
                // Remove current assignment (since we don't have end_date, we'll delete and create new)
                $delete_stmt = $conn->prepare("DELETE FROM animal_kennel WHERE animal_id = ?");
                $delete_stmt->bind_param("i", $animal_id);
                $delete_stmt->execute();
                
                // Create new assignment
                $assign_stmt = $conn->prepare("INSERT INTO animal_kennel (animal_id, kennel_id, assignment_date) VALUES (?, ?, NOW())");
                $assign_stmt->bind_param("ii", $animal_id, $kennel_id);
                $assign_stmt->execute();
                
                // Update kennel status - set new kennel to Occupied
                $update_new_kennel = $conn->prepare("UPDATE kennels SET status = 'Occupied' WHERE kennel_id = ?");
                $update_new_kennel->bind_param("i", $kennel_id);
                $update_new_kennel->execute();
                
                // Update animal's kennel status
                $update_animal = $conn->prepare("UPDATE animals SET Kennel_status = ? WHERE id = ?");
                $update_animal->bind_param("si", $new_kennel_name, $animal_id);
                $update_animal->execute();
                
                // Update previous kennel status if it's now empty
                $prev_kennel_id = $current_assignment['kennel_id'];
                $check_prev = $conn->prepare("SELECT COUNT(*) as count FROM animal_kennel WHERE kennel_id = ?");
                $check_prev->bind_param("i", $prev_kennel_id);
                $check_prev->execute();
                $prev_result = $check_prev->get_result();
                $prev_data = $prev_result->fetch_assoc();
                
                if ($prev_data['count'] == 0) {
                    $free_kennel = $conn->prepare("UPDATE kennels SET status = 'Available' WHERE kennel_id = ?");
                    $free_kennel->bind_param("i", $prev_kennel_id);
                    $free_kennel->execute();
                }
                
                $message = "Animal successfully reassigned to new kennel.";
            }
        } else {
            // Create new assignment
            $assign_stmt = $conn->prepare("INSERT INTO animal_kennel (animal_id, kennel_id, assignment_date) VALUES (?, ?, NOW())");
            $assign_stmt->bind_param("ii", $animal_id, $kennel_id);
            $assign_stmt->execute();
            
            // Update kennel status
            $update_kennel = $conn->prepare("UPDATE kennels SET status = 'Occupied' WHERE kennel_id = ?");
            $update_kennel->bind_param("i", $kennel_id);
            $update_kennel->execute();
            
            // Update animal's kennel status
            $update_animal = $conn->prepare("UPDATE animals SET Kennel_status = ? WHERE id = ?");
            $update_animal->bind_param("si", $new_kennel_name, $animal_id);
            $update_animal->execute();
            
            $message = "Animal successfully assigned to kennel.";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
    }
}

// Get all unassigned animals or animals that can be reassigned
// Simplified query since animal_kennel doesn't have end_date
$animals_sql = "SELECT a.id, a.Aname, a.Species, a.Width, a.Height, a.status, a.Kennel_status 
                FROM animals a 
                WHERE a.status != 'Adopted' AND a.status != 'Deceased' AND a.is_deleted = 0
                ORDER BY a.Species, a.Aname";
$animals_result = $conn->query($animals_sql);

// Debug: Check if we have animals
if ($animals_result === FALSE) {
    die("Error retrieving animals: " . $conn->error);
}

// Get available kennels
$kennels_sql = "SELECT kennel_id, kennel_name, max_width, max_height, species_compatibility, kennel_type 
                FROM kennels 
                WHERE status = 'Available' AND is_deleted = 0 
                ORDER BY species_compatibility, kennel_name";
$kennels_result = $conn->query($kennels_sql);

// Debug: Check if we have kennels
if ($kennels_result === FALSE) {
    die("Error retrieving kennels: " . $conn->error);
}

// Get current kennel assignments for display
$current_assignments = [];
$assignments_sql = "SELECT animal_id, kennel_id FROM animal_kennel";
$assignments_result = $conn->query($assignments_sql);
if ($assignments_result && $assignments_result->num_rows > 0) {
    while ($row = $assignments_result->fetch_assoc()) {
        $current_assignments[$row['animal_id']] = $row['kennel_id'];
    }
}

// Get kennel names for display
$kennel_names = [];
$kennel_names_sql = "SELECT kennel_id, kennel_name FROM kennels";
$kennel_names_result = $conn->query($kennel_names_sql);
if ($kennel_names_result && $kennel_names_result->num_rows > 0) {
    while ($row = $kennel_names_result->fetch_assoc()) {
        $kennel_names[$row['kennel_id']] = $row['kennel_name'];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Assign Animal to Kennel - AnimalGuardian</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root { 
    --primary-color:#134f5c; 
    --primary-light:#60a5b3; 
    --primary-dark:#0f3f4a; 
    --success-color:#27ae60; 
    --danger-color:#e74c3c; 
    --warning-color:#f1c40f; 
    --shadow:0 4px 6px rgba(0,0,0,0.1); 
    --transition:all 0.3s ease; 
}
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed; background-size:cover; color:#134f5c; line-height:1.6;}
.overlay{background:linear-gradient(135deg, rgba(19,79,92,0.92) 0%, rgba(15,63,74,0.95) 100%); min-height:100vh; padding:40px 20px;}
.container{max-width:95%; margin:auto; padding:30px; background:#fff; border-radius:16px; box-shadow:0 12px 30px rgba(0,0,0,0.4);}
.header{display:flex; justify-content:space-between; align-items:center; margin-bottom:25px; padding-bottom:15px; border-bottom:2px solid var(--primary-light);}
.header h2{margin:0; color:var(--primary-color);}
.message{text-align:center; padding:15px; margin-bottom:20px; border-radius:10px; background-color:#d4edda; color:#155724; box-shadow:var(--shadow);}
.error{text-align:center; padding:15px; margin-bottom:20px; border-radius:10px; background-color:#f8d7da; color:#721c24; box-shadow:var(--shadow);}
.form-container{display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-bottom:30px;}
.form-section{background:#f8f9fa; padding:20px; border-radius:12px; box-shadow:var(--shadow);}
.form-section h3{margin-bottom:15px; color:var(--primary-color); padding-bottom:10px; border-bottom:1px solid var(--primary-light);}
.form-group{margin-bottom:15px;}
.form-group label{display:block; margin-bottom:5px; font-weight:600; color:var(--primary-color);}
.form-group select, .form-group input{width:100%; padding:10px; border:1px solid #ced4da; border-radius:6px; font-size:1rem;}
.animal-info, .kennel-info{background:#e9f7f9; padding:15px; border-radius:8px; margin-top:10px;}
.info-item{margin-bottom:8px; display:flex;}
.info-label{font-weight:600; min-width:120px;}
.species-badge{display:inline-block; padding:4px 8px; border-radius:12px; font-size:0.75rem; font-weight:600; margin:2px;}
.species-dog{background-color:rgba(155,89,182,0.2); color:#9b59b6;}
.species-cat{background-color:rgba(52,152,219,0.2); color:#3498db;}
.species-bird{background-color:rgba(241,196,15,0.2); color:#f1c40f;}
.species-rabbit{background-color:rgba(230,126,34,0.2); color:#e67e22;}
.species-general{background-color:rgba(149,165,166,0.2); color:#95a5a6;}
.species-emergency{background-color:rgba(231,76,60,0.2); color:#e74c3c;}
.type-badge{display:inline-block; padding:4px 8px; border-radius:12px; font-size:0.75rem; font-weight:600; margin:2px; background-color:rgba(19,79,92,0.15); color:#134f5c;}
.btn{display:inline-flex; align-items:center; gap:8px; background:var(--primary-color); color:#fff; padding:12px 20px; border-radius:8px; text-decoration:none; font-weight:600; cursor:pointer; transition:var(--transition); border:none;}
.btn:hover{background:var(--primary-dark);}
.btn-large{font-size:1.1rem; padding:14px 25px;}
.back-btn{display:inline-block; margin-top:20px; padding:10px 16px; background:#60a5b3; color:#fff; border-radius:6px; text-decoration:none; margin-right:10px;}
.table-container{overflow-x:auto; border-radius:12px; box-shadow:var(--shadow); margin-bottom:25px;}
table{width:100%; border-collapse:separate; border-spacing:0; background:#fff; border-radius:12px;}
table th{background:linear-gradient(to bottom, var(--primary-color), var(--primary-dark)); color:white; padding:16px; text-align:left; font-weight:600; position:sticky; top:0;}
table td{padding:14px 16px; border-bottom:1px solid #e0e0e0; transition:var(--transition);}
table tr:nth-child(even){background-color:#f8f9fa;}
table tr:hover{background-color:#e9f7f9;}
.compatibility-check{margin-top:15px; padding:10px; border-radius:8px; text-align:center; font-weight:600;}
.compatible{background-color:rgba(46,204,113,0.2); color:#27ae60;}
.not-compatible{background-color:rgba(231,76,60,0.2); color:#e74c3c;}
.debug-info {background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #134f5c;}
</style>
</head>
<body>
<div class="overlay">
<div class="container">
    <div class="header">
        <h2>Assign Animal to Kennel</h2>
    </div>

    <?php if(!empty($message)): ?>
        <div class="<?php echo strpos($message, 'Error') !== false ? 'error' : 'message'; ?>"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php
    // Debug information
    if ($animals_result->num_rows === 0) {
        echo "<div class='debug-info'>";
        echo "<h4>Debug Information</h4>";
        echo "<p>No animals found in the database. This could be because:</p>";
        echo "<ul>";
        echo "<li>All animals are adopted or deceased</li>";
        echo "<li>All animals are marked as deleted</li>";
        echo "<li>There are no animals in the database</li>";
        echo "</ul>";
        echo "<p>SQL Query: <code>" . htmlspecialchars($animals_sql) . "</code></p>";
        echo "</div>";
    }
    ?>

    <form method="POST" action="">
        <div class="form-container">
            <div class="form-section">
                <h3>Select Animal</h3>
                <div class="form-group">
                    <label for="animal_id">Animal:</label>
                    <select id="animal_id" name="animal_id" required onchange="updateAnimalInfo()">
                        <option value="">-- Select Animal --</option>
                        <?php
                        if($animals_result->num_rows > 0){
                            while($animal = $animals_result->fetch_assoc()){
                                $current_kennel = "Not assigned";
                                if (isset($current_assignments[$animal['id']]) && isset($kennel_names[$current_assignments[$animal['id']]])) {
                                    $current_kennel = $kennel_names[$current_assignments[$animal['id']]];
                                }
                                
                                echo "<option value='{$animal['id']}' 
                                        data-species='{$animal['Species']}' 
                                        data-width='{$animal['Width']}' 
                                        data-height='{$animal['Height']}' 
                                        data-status='{$animal['status']}'
                                        data-kennel='{$current_kennel}'>";
                                echo "{$animal['Aname']} - {$animal['Species']} (Currently in: {$current_kennel})";
                                echo "</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No animals available for assignment</option>";
                        }
                        ?>
                    </select>
                </div>
                <div id="animalInfo" class="animal-info" style="display:none;">
                    <h4>Animal Details</h4>
                    <div class="info-item"><span class="info-label">Species:</span> <span id="infoSpecies"></span></div>
                    <div class="info-item"><span class="info-label">Size:</span> <span id="infoSize"></span></div>
                    <div class="info-item"><span class="info-label">Status:</span> <span id="infoStatus"></span></div>
                    <div class="info-item"><span class="info-label">Current Kennel:</span> <span id="infoCurrentKennel"></span></div>
                </div>
            </div>

            <div class="form-section">
                <h3>Select Kennel</h3>
                <div class="form-group">
                    <label for="kennel_id">Kennel:</label>
                    <select id="kennel_id" name="kennel_id" required onchange="updateKennelInfo()">
                        <option value="">-- Select Kennel --</option>
                        <?php
                        if($kennels_result->num_rows > 0){
                            while($kennel = $kennels_result->fetch_assoc()){
                                echo "<option value='{$kennel['kennel_id']}' 
                                        data-species='{$kennel['species_compatibility']}' 
                                        data-width='{$kennel['max_width']}' 
                                        data-height='{$kennel['max_height']}' 
                                        data-type='{$kennel['kennel_type']}'>";
                                echo "{$kennel['kennel_name']} ({$kennel['species_compatibility']} - {$kennel['max_width']}m × {$kennel['max_height']}m)";
                                echo "</option>";
                            }
                        } else {
                            echo "<option value='' disabled>No kennels available</option>";
                        }
                        ?>
                    </select>
                </div>
                <div id="kennelInfo" class="kennel-info" style="display:none;">
                    <h4>Kennel Details</h4>
                    <div class="info-item"><span class="info-label">Species:</span> <span id="infoKennelSpecies"></span></div>
                    <div class="info-item"><span class="info-label">Size:</span> <span id="infoKennelSize"></span></div>
                    <div class="info-item"><span class="info-label">Type:</span> <span id="infoKennelType"></span></div>
                </div>
                
                <div id="compatibilityCheck" class="compatibility-check" style="display:none;">
                    <i class="fas fa-check-circle"></i> <span id="compatibilityMessage"></span>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <button type="submit" class="btn btn-large" id="assignButton" 
                <?php echo ($animals_result->num_rows === 0 || $kennels_result->num_rows === 0) ? 'disabled' : ''; ?>>
                <i class="fas fa-link"></i> Assign Animal to Kennel
            </button>
        </div>
    </form>

    <div style="text-align: center; margin-top: 30px;">
        <a href="admin_kennel.php" class="back-btn"><i></i>All Kennels</a>
     
        <a href="admin dashboard.php" class="back-btn"><i ></i> Back to Dashboard</a>
    </div>
</div>
</div>

<script>
function updateAnimalInfo() {
    const select = document.getElementById('animal_id');
    const infoDiv = document.getElementById('animalInfo');
    const speciesSpan = document.getElementById('infoSpecies');
    const sizeSpan = document.getElementById('infoSize');
    const statusSpan = document.getElementById('infoStatus');
    const kennelSpan = document.getElementById('infoCurrentKennel');
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const species = option.getAttribute('data-species');
        const width = option.getAttribute('data-width');
        const height = option.getAttribute('data-height');
        const status = option.getAttribute('data-status');
        const kennel = option.getAttribute('data-kennel');
        
        speciesSpan.textContent = species;
        sizeSpan.textContent = `${width}m × ${height}m`;
        statusSpan.textContent = status;
        kennelSpan.textContent = kennel;
        
        infoDiv.style.display = 'block';
        
        // Filter kennels based on animal species and size
        filterKennels(species, parseFloat(width), parseFloat(height));
    } else {
        infoDiv.style.display = 'none';
        document.getElementById('compatibilityCheck').style.display = 'none';
        document.getElementById('assignButton').disabled = true;
    }
}

function updateKennelInfo() {
    const select = document.getElementById('kennel_id');
    const infoDiv = document.getElementById('kennelInfo');
    const speciesSpan = document.getElementById('infoKennelSpecies');
    const sizeSpan = document.getElementById('infoKennelSize');
    const typeSpan = document.getElementById('infoKennelType');
    const animalSelect = document.getElementById('animal_id');
    
    if (select.value && animalSelect.value) {
        const kennelOption = select.options[select.selectedIndex];
        const animalOption = animalSelect.options[animalSelect.selectedIndex];
        
        const kennelSpecies = kennelOption.getAttribute('data-species');
        const kennelWidth = parseFloat(kennelOption.getAttribute('data-width'));
        const kennelHeight = parseFloat(kennelOption.getAttribute('data-height'));
        const kennelType = kennelOption.getAttribute('data-type');
        
        const animalSpecies = animalOption.getAttribute('data-species');
        const animalWidth = parseFloat(animalOption.getAttribute('data-width'));
        const animalHeight = parseFloat(animalOption.getAttribute('data-height'));
        
        speciesSpan.textContent = kennelSpecies;
        sizeSpan.textContent = `${kennelWidth}m × ${kennelHeight}m`;
        typeSpan.textContent = kennelType;
        
        infoDiv.style.display = 'block';
        
        // Check compatibility
        checkCompatibility(animalSpecies, animalWidth, animalHeight, kennelSpecies, kennelWidth, kennelHeight);
    } else {
        infoDiv.style.display = 'none';
        document.getElementById('compatibilityCheck').style.display = 'none';
        document.getElementById('assignButton').disabled = true;
    }
}

function filterKennels(species, width, height) {
    const kennelSelect = document.getElementById('kennel_id');
    const options = kennelSelect.options;
    
    for (let i = 0; i < options.length; i++) {
        const option = options[i];
        if (option.value === "") continue;
        
        const kennelSpecies = option.getAttribute('data-species');
        const kennelWidth = parseFloat(option.getAttribute('data-width'));
        const kennelHeight = parseFloat(option.getAttribute('data-height'));
        
        // Check if kennel is compatible
        const speciesMatch = kennelSpecies === 'General' || kennelSpecies === species || kennelSpecies === 'Emergency';
        const sizeMatch = kennelWidth >= width && kennelHeight >= height;
        
        if (speciesMatch && sizeMatch) {
            option.style.display = '';
            option.disabled = false;
        } else {
            option.style.display = 'none';
            option.disabled = true;
        }
    }
    
    // Reset selection if current selection is not compatible
    if (kennelSelect.value) {
        const selectedOption = kennelSelect.options[kennelSelect.selectedIndex];
        if (selectedOption.disabled) {
            kennelSelect.value = "";
            updateKennelInfo();
        }
    }
}

function checkCompatibility(animalSpecies, animalWidth, animalHeight, kennelSpecies, kennelWidth, kennelHeight) {
    const checkDiv = document.getElementById('compatibilityCheck');
    const messageSpan = document.getElementById('compatibilityMessage');
    const assignButton = document.getElementById('assignButton');
    
    const speciesMatch = kennelSpecies === 'General' || kennelSpecies === animalSpecies || kennelSpecies === 'Emergency';
    const sizeMatch = kennelWidth >= animalWidth && kennelHeight >= animalHeight;
    
    if (speciesMatch && sizeMatch) {
        checkDiv.className = 'compatibility-check compatible';
        messageSpan.innerHTML = `This kennel is compatible with the selected animal. <i class="fas fa-check"></i>`;
        assignButton.disabled = false;
    } else {
        checkDiv.className = 'compatibility-check not-compatible';
        let message = "This kennel is not compatible: ";
        if (!speciesMatch) message += "species mismatch";
        if (!speciesMatch && !sizeMatch) message += " and ";
        if (!sizeMatch) message += "insufficient size";
        messageSpan.innerHTML = `${message} <i class="fas fa-times"></i>`;
        assignButton.disabled = true;
    }
    
    checkDiv.style.display = 'block';
}
</script>
</body>
</html>