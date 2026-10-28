<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
ini_set('display_errors', 1);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

$message = "";

// Handle appointment scheduling
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['schedule'])) {
    $adoption_id = $_POST['adoption_id'];
    $appointment_date = $_POST['appointment_date'];
    $notes = $_POST['notes'];

    $stmt = $conn->prepare("INSERT INTO appointment (adoption_id, appointment_date, notes) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $adoption_id, $appointment_date, $notes);

    if ($stmt->execute()) {
        // Fetch adopter info
        $adopterQuery = $conn->prepare("SELECT full_name, email FROM adoptions WHERE id = ?");
        $adopterQuery->bind_param("i", $adoption_id);
        $adopterQuery->execute();
        $adopter = $adopterQuery->get_result()->fetch_assoc();

        if ($adopter) {
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                $mail->setFrom(SMTP_USER, 'Animal Guardian Admin');
                $mail->addAddress($adopter['email'], $adopter['full_name']);

                $mail->isHTML(true);
                $mail->Subject = "Post-Adoption Appointment Scheduled";
                $mail->Body = "
                    <p>Dear {$adopter['full_name']},</p>
                    <p>Your post-adoption checkup has been scheduled.Please Visit our facility.</p>
                    <p><b>Date & Time:</b> {$appointment_date}</p>
                    <p><b>Notes:</b> {$notes}</p>
                    <p>Thank you,<br>Animal Guardian Team</p>
                ";

                $mail->send();
            } catch (Exception $e) {
                error_log("Email not sent: {$mail->ErrorInfo}");
            }
        }

        $message = "Appointment scheduled successfully and email sent!";
    } else {
        $message = "Failed to schedule appointment.";
    }
}

// Fetch approved adoptions
$result = $conn->query("
    SELECT a.id AS adoption_id, ani.Aname, ani.Species, ani.Animal_breed,
           a.full_name, a.email
    FROM adoptions a
    JOIN animals ani ON a.animal_id = ani.id
    WHERE a.status='Approved'
    ORDER BY a.id DESC
");
$adoptions = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
<title>Approved Adoptions</title>
<style>
body { font-family: Arial, sans-serif; background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed; background-size: cover; margin:0; padding:0; color:#fff; }
.overlay { background: rgba(19, 79, 92, 0.85); min-height: 100vh; padding: 30px; }
h1 { text-align:center; margin-bottom:20px; color:#e0f7fa; }
table { width:100%; border-collapse: collapse; margin-top:20px; }
th, td { border:1px solid #60a5fa; padding:8px; text-align:left; }
th { background:#134f5c; color:#fff; }
td { background: rgba(255,255,255,0.1); }
button { padding:6px 12px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; margin:2px; }
.schedule-btn { background:#4CAF50; color:white; }
.schedule-btn:hover { background:#388e3c; }
p.message { text-align:center; font-weight:bold; font-size:1.1rem; color:#ffd700; }
.back-btn { display:block; width:fit-content; background:#60a5fa; color:#fff; padding:10px 16px; border-radius:6px; font-weight:bold; margin:20px auto; text-align:center; text-decoration:none; }
.back-btn:hover { background:#3b82f6; }

/* Modal */
#appointmentModal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); z-index:9999; text-align:center; }
#appointmentModal form { display:inline-block; background: rgba(255,255,255,0.1); padding:20px; margin-top:50px; border-radius:12px; color:#fff; max-width:400px; width:90%; text-align:left; }
#appointmentModal input, #appointmentModal textarea, #appointmentModal button { width:100%; margin:10px 0; padding:10px; border-radius:8px; border:none; }
#closeModal { position:absolute; top:20px; left:20px; background:#60a5fa; color:#fff; padding:10px 18px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; }
#closeModal:hover { background:#3b82f6; }
</style>
</head>
<body>
<div class="overlay">
<h1>Approved Adoptions</h1>

<?php if($message): ?>
<p class="message"><?php echo $message; ?></p>
<?php endif; ?>

<?php if(count($adoptions) === 0): ?>
<p style="text-align:center;">No approved adoptions available.</p>
<?php else: ?>
<table>
<tr>
<th>Animal</th>
<th>Species / Breed</th>
<th>Adopter</th>
<th>Email</th>
<th>Actions</th>
</tr>

<?php foreach($adoptions as $a): ?>
<tr>
<td><?php echo htmlspecialchars($a['Aname']); ?></td>
<td><?php echo htmlspecialchars($a['Species'] . ' / ' . $a['Animal_breed']); ?></td>
<td><?php echo htmlspecialchars($a['full_name']); ?></td>
<td><?php echo htmlspecialchars($a['email']); ?></td>
<td>
<button class="schedule-btn" data-id="<?php echo $a['adoption_id']; ?>" data-name="<?php echo htmlspecialchars($a['full_name']); ?>">Follow-up</button>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<a href="admin dashboard.php" class="back-btn"> Back to Dashboard</a>

<!-- Appointment Modal -->
<div id="appointmentModal">
<button id="closeModal">⬅ Close</button>
<form method="POST">
    <h3>Schedule Appointment for <span id="adopterName"></span></h3>
    <input type="hidden" name="adoption_id" id="adoption_id">
    <label>Date & Time:</label>
    <input type="datetime-local" name="appointment_date" required>
    <label>Notes:</label>
    <textarea name="notes" rows="3"></textarea>
    <button type="submit" name="schedule">Schedule</button>
</form>
</div>

<script>
const modal = document.getElementById('appointmentModal');
const adopterName = document.getElementById('adopterName');
const adoptionInput = document.getElementById('adoption_id');

document.querySelectorAll('.schedule-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        adopterName.textContent = btn.dataset.name;
        adoptionInput.value = btn.dataset.id;
        modal.style.display = 'block';
    });
});

document.getElementById('closeModal').addEventListener('click', () => {
    modal.style.display = 'none';
});
</script>

</div>
</body>
</html>
