<?php
require 'databaseConnection.php';

$animal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
function fetchById($conn, $table, $id) {
    // $table is a fixed name chosen in code, never user input
    $stmt = $conn->prepare("SELECT * FROM $table WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

$animal = fetchById($conn, 'animals', $animal_id);
$msg = "";

// Handle Kennel Assignment
if (isset($_POST['assign_kennel'])) {
    $animal_id = (int)($_POST['animal_id'] ?? 0);
    $kennel_id = (int)($_POST['kennel_id'] ?? 0);

    $kennel = fetchById($conn, 'kennels', $kennel_id);
    $animal = fetchById($conn, 'animals', $animal_id);

    if ($kennel['current_occupancy'] >= $kennel['capacity']) {
        $msg = "❌ Kennel '{$kennel['kennel_name']}' is full!";
    } elseif ($animal['Width'] > $kennel['width'] || $animal['Height'] > $kennel['height']) {
        $msg = "❌ '{$kennel['kennel_name']}' is too small for this animal!";
    } else {
        // Assign animal to kennel
        $stmt = $conn->prepare("UPDATE animals SET Kennel_status=? WHERE id=?");
        $stmt->bind_param("si", $kennel['kennel_name'], $animal_id);
        $stmt->execute();

        // Update kennel occupancy
        $upd = $conn->prepare("UPDATE kennels SET current_occupancy = current_occupancy + 1 WHERE id = ?");
        $upd->bind_param("i", $kennel_id);
        $upd->execute();

        // Update kennel status if full
        if ($kennel['current_occupancy'] + 1 >= $kennel['capacity']) {
            $full = $conn->prepare("UPDATE kennels SET status = 'Full' WHERE id = ?");
            $full->bind_param("i", $kennel_id);
            $full->execute();
        }

        $msg = "✅ '{$animal['Aname']}' assigned to '{$kennel['kennel_name']}'!";
        $animal = fetchById($conn, 'animals', $animal_id);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Animal Profile</title>
</head>
<body>

<h2>Animal Profile: <?php echo $animal['Aname']; ?></h2>

<img src="uploads/<?php echo $animal['animal_picture']; ?>" width="250"><br><br>

<strong>Species:</strong> <?php echo $animal['Species']; ?><br>
<strong>Breed:</strong> <?php echo $animal['Animal_breed']; ?><br>
<strong>Age:</strong> <?php echo $animal['Aage']; ?> years<br>
<strong>Weight:</strong> <?php echo $animal['Aweight']; ?> kg<br>
<strong>Width:</strong> <?php echo $animal['Width']; ?> meters<br>
<strong>Height:</strong> <?php echo $animal['Height']; ?> meters<br>
<strong>Color:</strong> <?php echo $animal['Color']; ?><br>
<strong>Health Status:</strong> <?php echo $animal['Health_status']; ?><br>
<strong>Intake Date:</strong> <?php echo $animal['Intake_date']; ?><br>
<strong>Status:</strong> <?php echo $animal['status']; ?><br>
<strong>Kennel:</strong> <?php echo $animal['Kennel_status'] ?: "Unassigned"; ?><br><br>

<?php if ($msg) echo "<p>$msg</p>"; ?>

<?php if (!$animal['Kennel_status'] || $animal['Kennel_status'] == 'Unassigned'): ?>

<?php
// Fetch kennels that match size and fit dimensions
$kennels = $conn->query("
    SELECT * FROM kennels 
    WHERE status='Available'
    AND width >= {$animal['Width']}
    AND height >= {$animal['Height']}
    AND capacity > current_occupancy
");
?>

<?php if ($kennels->num_rows > 0): ?>
    <form method="post">
        <input type="hidden" name="assign_kennel" value="1">
        <input type="hidden" name="animal_id" value="<?php echo $animal['id']; ?>">

        <label>Select Kennel:</label>
        <select name="kennel_id" required>
            <?php while ($k = $kennels->fetch_assoc()): ?>
                <option value="<?php echo $k['id']; ?>">
                    <?php echo $k['kennel_name']; ?> 
                    (Occupied: <?php echo $k['current_occupancy']; ?>/<?php echo $k['capacity']; ?>, 
                    W: <?php echo $k['width']; ?>m, H: <?php echo $k['height']; ?>m)
                </option>
            <?php endwhile; ?>
        </select>
        <input type="submit" value="Assign Kennel">
    </form>
<?php else: ?>
    <p style="color:red;">❌ No available kennels match the animal’s width, height, and capacity requirements.</p>
<?php endif; ?>

<?php endif; ?>

<br>
<a href=" admin dashboard.php"><button type="button">Back to Dashboard</button></a>
<a href="register_animal.html"><button type="button">Register Another Animal</button></a>

</body>
</html>


