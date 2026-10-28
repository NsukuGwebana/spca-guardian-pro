<?php
// Database connection
include("databaseConnection.php");

// Get kennel details if ID is provided
$kennel = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT kennel_id, kennel_name, max_width, max_height, species_compatibility, kennel_type, status, notes FROM kennels WHERE kennel_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $kennel = $result->fetch_assoc();
    } else {
        echo "<p>Kennel not found.</p>";
        exit;
    }
    $stmt->close();
}

// Handle form submission
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $kennel_name = $_POST['kennel_name'];
    $max_width = $_POST['max_width'];
    $max_height = $_POST['max_height'];
    $species_compatibility = $_POST['species_compatibility'];
    $kennel_type = $_POST['kennel_type'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("UPDATE kennels 
        SET kennel_name=?, max_width=?, max_height=?, species_compatibility=?, kennel_type=?, status=?, notes=? 
        WHERE kennel_id=?");
    $stmt->bind_param(
        "sddssssi",
        $kennel_name,
        $max_width,
        $max_height,
        $species_compatibility,
        $kennel_type,
        $status,
        $notes,
        $id
    );

    if ($stmt->execute()) {
        $success = true;
    } else {
        $errorMsg = $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit Kennel</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
    color: #134f5c;
}
.overlay {
    background: rgba(19, 79, 92, 0.85);
    min-height: 100vh;
    padding: 30px 0;
}
.container {
    max-width: 700px;
    margin: auto;
    padding: 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
}
h1, h2 {
    text-align: center;
    margin-bottom: 25px;
    color: #134f5c;
}
form label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
    color: #134f5c;
}
form input, form select, form textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #134f5c;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 1rem;
}
.form-row {
    display: flex;
    gap: 20px;
    margin-bottom: 16px;
}
.form-col {
    flex: 1;
}
textarea { resize: vertical; }
.btn {
    display: inline-block;
    background: #134f5c;
    color: #fff;
    padding: 12px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    cursor: pointer;
    border: none;
}
.btn:hover { background: #0f3f4a; }
.back-btn {
    display: block;
    width: fit-content;
    background: #60a5b3;
    color: #fff;
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: bold;
    margin: 20px auto 0;
    text-align: center;
    cursor: pointer;
}
.back-btn:hover { background: #3d8a95; }
.message { text-align: center; margin-top: 20px; font-size: 18px; color: green; }
.error { text-align: center; margin-top: 20px; font-size: 18px; color: red; }
</style>
</head>
<body>
<div class="overlay">
<div class="container">
<?php if($success): ?>
    <h1>✅ Kennel Updated Successfully</h1>
    <p class="message">The kennel details have been updated in the system.</p>
    <a href="admin_kennel.php" class="back-btn">⬅ Back to Kennels</a>
<?php else: ?>
    <h2>Edit Kennel</h2>
    <?php if(isset($errorMsg)) echo "<p class='error'>Error updating kennel: $errorMsg</p>"; ?>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($kennel['kennel_id']); ?>">

        <label>Kennel Name</label>
        <input type="text" name="kennel_name" value="<?php echo htmlspecialchars($kennel['kennel_name']); ?>" required>

        <div class="form-row">
            <div class="form-col">
                <label>Max Width (m)</label>
                <input type="number" step="0.01" min="0.1" name="max_width" value="<?php echo htmlspecialchars($kennel['max_width']); ?>" required>
            </div>
            <div class="form-col">
                <label>Max Height (m)</label>
                <input type="number" step="0.01" min="0.1" name="max_height" value="<?php echo htmlspecialchars($kennel['max_height']); ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-col">
                <label>Species Compatibility</label>
                <select name="species_compatibility" required>
                    <option value="General" <?php if ($kennel['species_compatibility']=='General') echo 'selected'; ?>>General (All Species)</option>
                    <option value="Dog" <?php if ($kennel['species_compatibility']=='Dog') echo 'selected'; ?>>Dog</option>
                    <option value="Cat" <?php if ($kennel['species_compatibility']=='Cat') echo 'selected'; ?>>Cat</option>
                    <option value="Bird" <?php if ($kennel['species_compatibility']=='Bird') echo 'selected'; ?>>Bird</option>
                    <option value="Rabbit" <?php if ($kennel['species_compatibility']=='Rabbit') echo 'selected'; ?>>Rabbit</option>
                    <option value="Emergency" <?php if ($kennel['species_compatibility']=='Emergency') echo 'selected'; ?>>Emergency</option>
                </select>
            </div>
            <div class="form-col">
                <label>Kennel Type</label>
                <select name="kennel_type" required>
                    <option value="Standard" <?php if ($kennel['kennel_type']=='Standard') echo 'selected'; ?>>Standard</option>
                    <option value="Large" <?php if ($kennel['kennel_type']=='Large') echo 'selected'; ?>>Large</option>
                    <option value="Outdoor" <?php if ($kennel['kennel_type']=='Outdoor') echo 'selected'; ?>>Outdoor</option>
                    <option value="Indoor" <?php if ($kennel['kennel_type']=='Indoor') echo 'selected'; ?>>Indoor</option>
                    <option value="Quarantine" <?php if ($kennel['kennel_type']=='Quarantine') echo 'selected'; ?>>Quarantine</option>
                    <option value="Emergency" <?php if ($kennel['kennel_type']=='Emergency') echo 'selected'; ?>>Emergency</option>
                </select>
            </div>
        </div>

        <label>Status</label>
        <select name="status" required>
            <option value="Available" <?php if ($kennel['status']=='Available') echo 'selected'; ?>>Available</option>
            <option value="Occupied" <?php if ($kennel['status']=='Occupied') echo 'selected'; ?>>Occupied</option>
            <option value="Maintenance" <?php if ($kennel['status']=='Maintenance') echo 'selected'; ?>>Maintenance</option>
        </select>

        <label>Notes</label>
        <textarea name="notes" rows="3"><?php echo htmlspecialchars($kennel['notes']); ?></textarea>

        <button type="submit" class="btn">Update Kennel</button>
        <a href="admin_kennel.php" class="back-btn">Cancel / Back</a>
    </form>
<?php endif; ?>
</div>
</div>
</body>
</html>
