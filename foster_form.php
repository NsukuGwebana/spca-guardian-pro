<?php
require_once __DIR__ . '/bootstrap.php';

$conn = db();

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_foster'])) {
    $animal_id = (int)$_POST['animal_id'];
    
    // Short form: the ID document is checked in person at the home visit, so no upload is needed
    $id_copy_path = "";

    if (!$error) {
        $full_name          = trim($_POST['full_name'] ?? '');
        $id_number          = trim($_POST['id_number'] ?? '');
        $date_of_birth      = null;
        $phone              = trim($_POST['phone'] ?? '');
        $email              = trim($_POST['email'] ?? '');
        $address_street     = '';
        $address_city       = trim($_POST['address_city'] ?? '');
        $address_province   = '';
        $address_postal     = '';
        $employment_status  = '';
        $monthly_income     = null;
        $housing_type       = $_POST['housing_type'] ?? 'House';
        $has_fenced_yard    = isset($_POST['has_fenced_yard']) ? 1 : 0;
        $has_other_pets     = isset($_POST['has_other_pets']) ? 1 : 0;
        $other_pets_details = null;
        $reason             = trim($_POST['reason'] ?? '');

        // Check the answers before saving; every problem is listed at once.
        $problems = [];
        if (mb_strlen($full_name) < 3)               $problems[] = "Please enter your full name.";
        if (!valid_identity_number($id_number))      $problems[] = "That ID number doesn't look right. Use your 13-digit SA ID number or your passport number.";
        if (!valid_phone($phone))                    $problems[] = "Please enter a South African phone number, e.g. 082 123 4567.";
        if ($email !== '' && !valid_email($email))   $problems[] = "Please check your email address.";
        if ($address_city === '')                    $problems[] = "Please tell us which town or suburb you live in.";
        if (!in_array($housing_type, ['House', 'Apartment', 'Farm', 'Other'], true)) $housing_type = 'Other';
        if (!$problems && q("SELECT 1 FROM fosters WHERE animal_id = ? AND id_number = ? AND status IN ('Pending','Home visit','Approved') LIMIT 1",
                            [$animal_id, $id_number])->num_rows) {
            $problems[] = "You already have an open application for this animal. You can track it on the website.";
        }
        // An SA ID number contains the date of birth (YYMMDD), so we don't need to ask for it.
        if (!$problems && preg_match('/^\d{13}$/', $id_number)) {
            $yy = (int)substr($id_number, 0, 2);
            $date_of_birth = (($yy > (int)date('y')) ? '19' : '20') . substr($id_number, 0, 2) . '-' . substr($id_number, 2, 2) . '-' . substr($id_number, 4, 2);
        }
        if ($problems) $error = implode('<br>', array_map('e', $problems));
    }

    if (!$error) {

        $stmt = $conn->prepare("INSERT INTO fosters
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
            // Update animal status to Pending
            $upd = $conn->prepare("UPDATE animals SET status='Pending' WHERE id=?");
            $upd->bind_param("i", $animal_id);
            $upd->execute();
            $upd->close();
            $success = "✅ Foster application submitted successfully! <br> Track your foster Appication on the Website within 4-5 working days for the outcome.";
            $animalName = $animal['Aname'] ?? 'the animal';
            log_activity('New foster application', "$full_name for $animalName (#$animal_id)");
            if ($email !== '') {
                send_mail($email, "We received your foster application",
                    '<p>Dear ' . e($full_name) . ',</p><p>Thank you for applying to foster <strong>' . e($animalName) . '</strong>. '
                  . 'We will review your application within 3 to 5 working days and then contact you to arrange a home visit.</p>'
                  . '<p>You can check progress any time on our website under <em>Track foster</em>, using your ID number.</p>'
                  . '<p>Kind regards,<br>SPCA Guardian Pro team</p>');
            }
        } else {
            $error = "Sorry, we couldn't save your application. Please try again.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Foster Form | SPCA Guardian Pro</title>
<style>
/* Copy the same styles as your adoption form */
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

fieldset { border: none; padding: 0; }
label { display: block; margin-bottom: 6px; font-weight: bold; }
input[type=text], input[type=date], input[type=email], input[type=number], select, textarea {
    width: 100%; padding: 8px; margin-bottom: 14px;
    border: 1px solid #134f5c; border-radius: 6px; box-sizing: border-box;
}
textarea { resize: vertical; }

.button-container { text-align: center; margin: 30px 0; }
button[type=submit] { background-color: #134f5c; color: #fff; padding: 16px 36px; font-size: 1.2rem; border-radius: 10px; font-weight: bold; cursor: pointer; border: none; box-shadow: 0 6px 12px rgba(0,0,0,0.25); transition: all 0.25s ease; }
button[type=submit]:hover { background: linear-gradient(135deg, #0f3f4a, #1a6675); transform: scale(1.08); box-shadow: 0 8px 16px rgba(0,0,0,0.35); }

button[type=reset], .back-btn { background: #60a5b3; color: #fff; padding: 10px 24px; font-size: 1rem; border-radius: 8px; cursor: pointer; border: none; transition: all 0.2s ease; margin: 5px; }
button[type=reset]:hover, .back-btn:hover { background: #3d8a95; transform: scale(1.04); }

p.success { color: green; font-weight: bold; }
p.error { color: red; font-weight: bold; }
</style>
<link rel="stylesheet" href="spca-theme.css">
</head>
<body class="spca">
<script src="site-nav.js"></script>
<div class="overlay">
<div class="container">

<?php if ($animal): ?>
<div class="animal-photo">
    <?php if (!empty($animal['animal_picture']) && file_exists($animal['animal_picture'])): ?>
        <img src="<?php echo htmlspecialchars($animal['animal_picture']); ?>" alt="Animal Picture">
    <?php else: ?>
        <img src="Images/no-photo.svg" alt="No photo yet">
    <?php endif; ?>
</div>
<?php endif; ?>

<h2>Foster <?php echo htmlspecialchars($animal['Aname'] ?? ''); ?></h2>

<?php if ($error): ?>
    <p class='error'><?php echo $error; ?></p>
    <a href="foster.php" class="back-btn">&larr; Back to animals</a>
<?php endif; ?>

<?php if ($success): ?>
    <p class='success'><?php echo $success; ?></p>
    <a href="Homepage.html" class="back-btn">&larr; Back to home</a>
<?php endif; ?>

<?php if ($animal && !$success): ?>
<form method="post" action="" >
    <input type="hidden" name="animal_id" value="<?= e($animal['id']) ?>">
    <fieldset>
        <p class="form-intro">It takes about a minute. We'll call you to arrange a home visit, where we check your ID.</p>

        <label for="full_name">Full name *</label>
        <input type="text" id="full_name" name="full_name" value="<?= e($_POST['full_name'] ?? '') ?>" autocomplete="name" required>

        <label for="id_number">SA ID or passport number *</label>
        <input type="text" id="id_number" name="id_number" value="<?= e($_POST['id_number'] ?? '') ?>" required>

        <label for="phone">Phone number *</label>
        <input type="tel" id="phone" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" autocomplete="tel" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email">

        <label for="address_city">Town or suburb *</label>
        <input type="text" id="address_city" name="address_city" value="<?= e($_POST['address_city'] ?? '') ?>" required>

        <label for="housing_type">Where do you live?</label>
        <select id="housing_type" name="housing_type">
            <option>House</option>
            <option>Apartment</option>
            <option>Farm</option>
            <option>Other</option>
        </select>

        <div class="check-row">
            <label><input type="checkbox" name="has_fenced_yard"> I have a fenced yard</label>
            <label><input type="checkbox" name="has_other_pets"> I have other pets</label>
        </div>

        <label for="reason">Why would you like to foster this animal? *</label>
        <textarea id="reason" name="reason" rows="3" required><?= e($_POST['reason'] ?? '') ?></textarea>

        <div class="button-container">
            <button type="submit" name="submit_foster">Send application</button>
            <a href="foster.php" class="back-btn">&larr; Back to animals</a>
        </div>
    </fieldset>
</form>
<?php endif; ?>
</div>
</div>
</body>
</html>
