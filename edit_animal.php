<?php
// Resume session and check access
session_start();
if (!isset($_SESSION['access']) || $_SESSION['access'] !== "yes") {
    header("Location: Newlogin.html"); 
    exit();
}

// Database connection
include("databaseConnection.php");

// Get animal details if ID is provided
$animal = null;
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT id, Aname, Species, Animal_breed, Aage, Color, Kennel_status, Aweight, Width, Height, Health_status, Intake_date, status, animal_picture FROM animals WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $animal = $result->fetch_assoc();
    } else {
        echo "<p>Animal not found.</p>";
        exit;
    }
    $stmt->close();
}

// Handle form submission
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id']);
    $Aname = $_POST['Aname'];
    $Species = $_POST['Species'];
    $Animal_breed = $_POST['Animal_breed'];
    $Aage = $_POST['Aage'];
    $Color = $_POST['Color'];
    $Kennel_status = $_POST['Kennel_status'];
    $Aweight = $_POST['Aweight'];
    $Width = $_POST['Width'];
    $Height = $_POST['Height'];
    $Health_status = $_POST['Health_status'];
    $Intake_date = $_POST['Intake_date'];
    $status = $_POST['status'];

    // Handle optional file upload
    $animal_picture = $animal['animal_picture'];
    if (isset($_FILES['animal_picture']) && $_FILES['animal_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = basename($_FILES['animal_picture']['name']);
        $targetFile = $uploadDir . time() . "_" . $fileName;
        if (move_uploaded_file($_FILES['animal_picture']['tmp_name'], $targetFile)) {
            $animal_picture = $targetFile;
        }
    }

    $stmt = $conn->prepare("UPDATE animals 
        SET Aname=?, Species=?, Animal_breed=?, Aage=?, Color=?, Kennel_status=?, Aweight=?, Width=?, Height=?, Health_status=?, Intake_date=?, status=?, animal_picture=? 
        WHERE id=?");

    $stmt->bind_param(
        "sssdssdddssssi",
        $Aname,
        $Species,
        $Animal_breed,
        $Aage,
        $Color,
        $Kennel_status,
        $Aweight,
        $Width,
        $Height,
        $Health_status,
        $Intake_date,
        $status,
        $animal_picture,
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
<title>Edit Animal</title>
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
form input, form select {
    width: 100%;
    padding: 10px;
    border: 1px solid #134f5c;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 1rem;
}
form input[type="file"] {
    padding: 3px;
}
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
.btn:hover {
    background: #0f3f4a;
}
.reset-btn {
    background: #60a5b3;
    margin-left: 10px;
}
.reset-btn:hover {
    background: #3d8a95;
}
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
.back-btn:hover {
    background: #3d8a95;
}
img { max-width: 100px; margin-bottom: 10px; display: block; }
.message { text-align: center; margin-top: 20px; font-size: 18px; color: green; }
.error { text-align: center; margin-top: 20px; font-size: 18px; color: red; }
</style>
</head>
<body>
<div class="overlay">
<div class="container">
<?php if($success): ?>
    <h1>✅ Animal Updated Successfully</h1>
    <p class="message">The animal details have been updated in the system.</p>
    <a href="regA.php" class="back-btn">⬅ Back to Animals</a>
<?php else: ?>
    <h2>Edit Animal</h2>
    <?php if(isset($errorMsg)) echo "<p class='error'>Error updating animal: $errorMsg</p>"; ?>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($animal['id']); ?>">

        <label>Name</label>
        <input type="text" name="Aname" value="<?php echo htmlspecialchars($animal['Aname']); ?>" required>

        <label>Species</label>
        <input type="text" name="Species" value="<?php echo htmlspecialchars($animal['Species']); ?>" required>

        <label>Breed</label>
        <input type="text" name="Animal_breed" value="<?php echo htmlspecialchars($animal['Animal_breed']); ?>">

        <label>Age</label>
        <input type="number" step="0.1" name="Aage" value="<?php echo htmlspecialchars($animal['Aage']); ?>">

        <label>Color</label>
        <input type="text" name="Color" value="<?php echo htmlspecialchars($animal['Color']); ?>">

        <label>Kennel Status</label>
        <input type="text" name="Kennel_status" value="<?php echo htmlspecialchars($animal['Kennel_status']); ?>">

        <label>Weight (kg)</label>
        <input type="number" step="0.01" name="Aweight" value="<?php echo htmlspecialchars($animal['Aweight']); ?>">

        <label>Width (cm)</label>
        <input type="number" step="0.01" name="Width" value="<?php echo htmlspecialchars($animal['Width']); ?>">

        <label>Height (cm)</label>
        <input type="number" step="0.01" name="Height" value="<?php echo htmlspecialchars($animal['Height']); ?>">

        <label>Health Status</label>
        <select name="Health_status" required>
            <option value="Healthy" <?php if ($animal['Health_status']=='Healthy') echo 'selected'; ?>>Healthy</option>
            <option value="Unhealthy" <?php if ($animal['Health_status']=='Unhealthy') echo 'selected'; ?>>Unhealthy</option>
        </select>

        <label>Intake Date</label>
        <input type="date" name="Intake_date" value="<?php echo htmlspecialchars($animal['Intake_date']); ?>">

        <label>Status</label>
        <select name="status" required>
            <option value="Available" <?php if ($animal['status']=='Available') echo 'selected'; ?>>Available</option>
            <option value="Pending" <?php if ($animal['status']=='Pending') echo 'selected'; ?>>Pending</option>
            <option value="Not Available" <?php if ($animal['status']=='Not Available') echo 'selected'; ?>>Not Available</option>
        </select>

        <label>Animal Picture</label>
        <?php if (!empty($animal['animal_picture'])): ?>
            <img src="<?php echo htmlspecialchars($animal['animal_picture']); ?>" alt="Animal Picture">
        <?php endif; ?>
        <input type="file" name="animal_picture" accept="image/*">

        <button type="submit" class="btn">Update Animal</button>
        <a href="javascript:void(0);" class="back-btn" 
   onclick="if (history.length > 1) { history.back(); } else { window.location.href='regA.php'; }">
   ⬅ Cancel / Back
</a>

    </form>
<?php endif; ?>
</div>
</div>
</body>
</html>



