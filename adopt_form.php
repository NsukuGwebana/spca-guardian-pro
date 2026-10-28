<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB connection
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

// Get selected animal
$animal_id = isset($_GET['animal_id']) ? (int)$_GET['animal_id'] : 0;
$animal = null;
if ($animal_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM animals WHERE id = ? AND Health_status='Healthy' AND status='Available' AND is_deleted=0");
    $stmt->bind_param("i", $animal_id);
    $stmt->execute();
    $animal = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_adoption'])) {
    $animal_id = (int)$_POST['animal_id'];
    
    // Handle ID upload
    $id_copy_path = "";
    if (isset($_FILES['id_copy']) && $_FILES['id_copy']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/id_copy/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["id_copy"]["name"]);
        $id_copy_path = $targetDir . $fileName;
        move_uploaded_file($_FILES["id_copy"]["tmp_name"], $id_copy_path);
    } else {
        $error = "You must upload a certified ID copy.";
    }

    if (!$error) {
        $full_name          = $_POST['full_name'];
        $id_number          = $_POST['id_number'];
        $date_of_birth      = $_POST['date_of_birth'] ?: null;
        $phone              = $_POST['phone'];
        $email              = $_POST['email'];
        $address_street     = $_POST['address_street'];
        $address_city       = $_POST['address_city'];
        $address_province   = $_POST['address_province'];
        $address_postal     = $_POST['address_postal_code'];
        $employment_status  = $_POST['employment_status'];
        $monthly_income     = $_POST['monthly_income'] ?: null;
        $housing_type       = $_POST['housing_type'];
        $has_fenced_yard    = isset($_POST['has_fenced_yard']) ? 1 : 0;
        $has_other_pets     = isset($_POST['has_other_pets']) ? 1 : 0;
        $other_pets_details = $has_other_pets ? $_POST['other_pets_details'] : null;
        $reason             = $_POST['reason'];

        $stmt = $conn->prepare("INSERT INTO adoptions
            (animal_id, full_name, id_number, date_of_birth, phone, email, address_street, address_city, address_province, address_postal_code, employment_status, monthly_income, housing_type, has_fenced_yard, has_other_pets, other_pets_details, reason, id_copy)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param("issssssssssssiisss",
            $animal_id,
            $full_name,
            $id_number,
            $date_of_birth,
            $phone,
            $email,
            $address_street,
            $address_city,
            $address_province,
            $address_postal,
            $employment_status,
            $monthly_income,
            $housing_type,
            $has_fenced_yard,
            $has_other_pets,
            $other_pets_details,
            $reason,
            $id_copy_path
        );

        if ($stmt->execute()) {
            $upd = $conn->prepare("UPDATE animals SET status='Pending' WHERE id=?");
            $upd->bind_param("i", $animal_id);
            $upd->execute();
            $upd->close();
            $success = "✅ Application submitted successfully! Your application will be reviewed within 3-5 business days. Please track your status on the website after the due period.";
        } else {
            $error = "Failed to save application: " . $stmt->error;
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Adoption Form</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
    padding: 0;
}

.overlay {
    background: rgba(19, 79, 92, 0.85);
    min-height: 100vh;
    padding: 30px 0;
    color: #fff;
}

.container {
    max-width: 700px;
    margin: auto;
    padding: 20px;
    background: #fff;
    color: #134f5c;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.35);
}

.animal-photo {
    text-align: center;
    margin-bottom: 15px;
}
.animal-photo img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 10px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
}

h2 {
    text-align: center;
    margin-bottom: 15px;
    color: #134f5c;
}

fieldset {
    border: none;
    padding: 0;
}

label {
    display: block;
    margin-bottom: 6px;
    font-weight: bold;
}

input[type=text], input[type=date], input[type=email], input[type=number], select, textarea {
    width: 100%;
    padding: 8px;
    margin-bottom: 14px;
    border: 1px solid #134f5c;
    border-radius: 6px;
    box-sizing: border-box;
}

textarea { resize: vertical; }

/* Button container */
.button-container { text-align: center; margin: 30px 0; }

button[type=submit] {
    background-color: #134f5c;
    color: #fff;
    padding: 16px 36px;
    font-size: 1.2rem;
    border-radius: 10px;
    font-weight: bold;
    cursor: pointer;
    border: none;
    box-shadow: 0 6px 12px rgba(0,0,0,0.25);
    transition: all 0.25s ease;
}
button[type=submit]:hover {
    background: linear-gradient(135deg, #0f3f4a, #1a6675);
    transform: scale(1.08);
    box-shadow: 0 8px 16px rgba(0,0,0,0.35);
}

button[type=reset], .back-btn {
    background: #60a5b3;
    color: #fff;
    padding: 10px 24px;
    font-size: 1rem;
    border-radius: 8px;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
    margin: 5px;
}
button[type=reset]:hover, .back-btn:hover {
    background: #3d8a95;
    transform: scale(1.04);
}

p.success { color: green; font-weight: bold; }
p.error { color: red; font-weight: bold; }
</style>
</head>
<body>
<div class="overlay">
<div class="container">

<?php if ($animal): ?>
<div class="animal-photo">
    <?php if (!empty($animal['animal_picture']) && file_exists($animal['animal_picture'])): ?>
        <img src="<?php echo htmlspecialchars($animal['animal_picture']); ?>" alt="Animal Picture">
    <?php else: ?>
        <p><i>No image available for this animal.</i></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<h2>Adopt <?php echo htmlspecialchars($animal['Aname'] ?? ''); ?></h2>

<?php if ($error): ?>
    <p class='error'><?php echo $error; ?></p>
    <button class='back-btn' onclick='window.history.back()'>⬅ Back</button>
<?php endif; ?>

<?php if ($success): ?>
    <p class='success'><?php echo $success; ?></p>
    <button class='back-btn' onclick="window.location.href='adoption_form.php'">Back to Animals</button>
<?php endif; ?>

<?php if ($animal && !$success): ?>
<form method="post" action="" enctype="multipart/form-data">
    <input type="hidden" name="animal_id" value="<?php echo $animal['id']; ?>">
    <fieldset>
         <strong style="font-size:18px;">Personal Details</strong><br><br>

        <label>Full Name *</label>
        <input type="text" name="full_name" required>

        <label>ID Number / Passport: *</label>
        <input type="text" name="id_number" required>

        <label>Date of Birth:</label>
        <input type="date" name="date_of_birth">

        <label>Phone: *</label>
        <input type="text" name="phone" required>

        <label>Email:</label>
        <input type="email" name="email">

        <label>Street Address: *</label>
        <input type="text" name="address_street" required>

        <label>City: *</label>
        <input type="text" name="address_city" required>

        <label>Province/State: *</label>
        <input type="text" name="address_province" required>

        <label>Postal Code: *</label>
        <input type="text" name="address_postal_code" required>

        <label>Employment Status:*</label>
        <select name="employment_status" required>
            <option value="">-- Select --</option>
            <option>Employed</option>
            <option>Self-employed</option>
            <option>Student</option>
            <option>Unemployed</option>
            <option>Retired</option>
        </select>

        <label>Monthly Income:</label>
        <input type="number" step="0.01" name="monthly_income">

        <label>Housing Type:</label>
        <select name="housing_type">
            <option>House</option>
            <option>Apartment</option>
            <option>Farm</option>
            <option>Other</option>
        </select>

        <label><input type="checkbox" name="has_fenced_yard"> Fenced Yard</label>
        <label><input type="checkbox" name="has_other_pets"> Other Pets</label>
        <label>Other Pets Details</label>
        <input type="text" name="other_pets_details">

        <label>Why do you want to adopt this animal?: *</label>
        <textarea name="reason" required></textarea>

        <label>Upload Certified ID:*</label>
        <input type="file" name="id_copy" accept=".jpg,.jpeg,.png,.pdf" required><br><br>

        <div class="button-container">
            <button type="submit" name="submit_adoption">Submit Application</button><br><br>
            <button type="reset">Reset</button>
            <button type="button" class="back-btn" onclick="window.history.back()">Back</button>
        </div>
    </fieldset>
</form>
<?php endif; ?>
</div>
</div>
</body>
</html>
