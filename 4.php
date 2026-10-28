<?php
require_once __DIR__ . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'databaseConnection.php';

$messageSent = false;
$errorMsg = '';

$donors = $volunteers = $subscribers = $adopters = [];

//  Fetch donors with name + email
$result = $conn->query("SELECT F_name, L_name, E_address FROM donor WHERE E_address IS NOT NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $donors[] = [
            'name'  => trim($row['F_name'] . ' ' . $row['L_name']),
            'email' => $row['E_address']
        ];
    }
}

//  Fetch volunteers from community_engagement table
$result = $conn->query("SELECT volunteer_Fname, volunteer_Lname, email FROM community_engagement WHERE email IS NOT NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $volunteers[] = [
            'name'  => trim($row['volunteer_Fname'] . ' ' . $row['volunteer_Lname']),
            'email' => $row['email']
        ];
    }
}

// Fetch subscribers (email only)
$result = $conn->query("SELECT email FROM spca_subscribers WHERE email IS NOT NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $subscribers[] = [
            'name'  => "Subscriber",
            'email' => $row['email']
        ];
    }
}

// ✅ Fetch adopters with name + email
$result = $conn->query("SELECT full_name, email FROM adoptions WHERE email IS NOT NULL");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $adopters[] = [
            'name'  => $row['full_name'],
            'email' => $row['email']
        ];
    }
}

if (isset($_POST['send'])) {
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';
    
    // Collect all recipients
    $recipients = [];
    
    // Add individual recipient if specified
    $individualEmail = $_POST['donor_email'] ?: $_POST['volunteer_email'] ?: $_POST['subscriber_email'] ?: $_POST['adopter_email'] ?: $_POST['to'];
    if ($individualEmail) {
        $recipients[] = $individualEmail;
    }
    
    // Add group recipients based on checkboxes
    if (isset($_POST['send_to_donors']) && $_POST['send_to_donors'] == 'on') {
        foreach ($donors as $donor) {
            $recipients[] = $donor['email'];
        }
    }
    
    if (isset($_POST['send_to_volunteers']) && $_POST['send_to_volunteers'] == 'on') {
        foreach ($volunteers as $volunteer) {
            $recipients[] = $volunteer['email'];
        }
    }
    
    if (isset($_POST['send_to_subscribers']) && $_POST['send_to_subscribers'] == 'on') {
        foreach ($subscribers as $subscriber) {
            $recipients[] = $subscriber['email'];
        }
    }
    
    if (isset($_POST['send_to_adopters']) && $_POST['send_to_adopters'] == 'on') {
        foreach ($adopters as $adopter) {
            $recipients[] = $adopter['email'];
        }
    }
    
    // Remove duplicates
    $recipients = array_unique($recipients);
    
    if (!empty($recipients) && $subject && $body) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER; // Your Gmail
            $mail->Password   = SMTP_PASS; // Gmail App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom(SMTP_USER, 'Animal Guardian Admin');
            
            // Add all recipients
            foreach ($recipients as $email) {
                $mail->addAddress($email);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);

            $mail->send();
            $messageSent = true;
        } catch (Exception $e) {
            $errorMsg = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        $errorMsg = "Please select at least one recipient or type an email, and fill in subject/message.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Send Email</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: url('https://images.unsplash.com/photo-1518717758536-85ae29035b6d?auto=format&fit=crop&w=1950&q=80') no-repeat center center fixed;
        background-size: cover;
        margin: 0;
        padding: 20px;
        color: #134f5c;
    }
    .overlay {
        background: rgba(19, 79, 92, 0.85);
        padding: 30px;
        border-radius: 12px;
        max-width: 700px;
        margin: 0 auto;
    }
    .container {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.35);
    }
    h2 {
        text-align: center;
        margin-bottom: 25px;
        color: #134f5c;
    }
    form label {
        display: block;
        margin-top: 15px;
        font-weight: bold;
        color: #134f5c;
    }
    form input, form textarea, form select {
        width: 100%;
        padding: 10px;
        border: 1px solid #134f5c;
        border-radius: 8px;
        margin-bottom: 10px;
        font-size: 1rem;
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
        margin-right: 10px;
    }
    .btn:hover { background: #0f3f4a; }
    .back-btn { background: #60a5b3; }
    .back-btn:hover { background: #3d8a95; }
    .success { color: green; margin-bottom: 15px; }
    .error { color: red; margin-bottom: 15px; }
    .hidden { display: none; }
    .group-checkboxes {
        margin: 15px 0;
        padding: 15px;
        background-color: #f0f8ff;
        border-radius: 8px;
        border: 1px solid #cce5ff;
    }
    .group-checkboxes label {
        display: inline-block;
        margin-right: 15px;
        font-weight: normal;
    }
    .group-checkboxes input[type="checkbox"] {
        width: auto;
        margin-right: 5px;
    }
</style>
<script>
function toggleDropdowns() {
    const type = document.getElementById('recipientType').value;
    document.getElementById('donorDropdown').classList.add('hidden');
    document.getElementById('volunteerDropdown').classList.add('hidden');
    document.getElementById('subscriberDropdown').classList.add('hidden');
    document.getElementById('adopterDropdown').classList.add('hidden');
    document.getElementById('manualEmail').classList.add('hidden');

    if (type === 'donor') document.getElementById('donorDropdown').classList.remove('hidden');
    if (type === 'volunteer') document.getElementById('volunteerDropdown').classList.remove('hidden');
    if (type === 'subscriber') document.getElementById('subscriberDropdown').classList.remove('hidden');
    if (type === 'adopter') document.getElementById('adopterDropdown').classList.remove('hidden');
    if (type === 'manual') document.getElementById('manualEmail').classList.remove('hidden');
}
</script>
</head>
<body>
<div class="overlay">
<div class="container">
    <h2>Send Email</h2>

    <?php if ($messageSent): ?>
        <p class="success">Email sent successfully to <?php echo count($recipients); ?> recipients!</p>
    <?php elseif ($errorMsg): ?>
        <p class="error"><?= htmlspecialchars($errorMsg) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <!-- Group selection checkboxes -->
        <div class="group-checkboxes">
            <strong>Send to Groups:</strong><br>
            <label><input type="checkbox" name="send_to_donors"> All Donors (<?php echo count($donors); ?>)</label>
            <label><input type="checkbox" name="send_to_volunteers"> All Volunteers (<?php echo count($volunteers); ?>)</label>
            <label><input type="checkbox" name="send_to_subscribers"> All Subscribers (<?php echo count($subscribers); ?>)</label>
            <label><input type="checkbox" name="send_to_adopters"> All Adopters (<?php echo count($adopters); ?>)</label>
        </div>

        <label>Or Choose Individual Recipient:</label>
        <select id="recipientType" onchange="toggleDropdowns()">
            <option value="">-- Select Type --</option>
            <option value="donor">Donor</option>
            <option value="volunteer">Volunteer</option>
            <option value="subscriber">Subscriber</option>
            <option value="adopter">Adopter</option>
            <option value="manual">Custom Email</option>
        </select>

        <!-- Donors -->
        <div id="donorDropdown" class="hidden">
            <label>Select Donor Email:</label>
            <select name="donor_email">
                <option value="">-- Choose Donor --</option>
                <?php foreach ($donors as $donor): ?>
                    <option value="<?= htmlspecialchars($donor['email']) ?>">
                        <?= htmlspecialchars($donor['name']) ?> (<?= htmlspecialchars($donor['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Volunteers -->
        <div id="volunteerDropdown" class="hidden">
            <label>Select Volunteer Email:</label>
            <select name="volunteer_email">
                <option value="">-- Choose Volunteer --</option>
                <?php foreach ($volunteers as $volunteer): ?>
                    <option value="<?= htmlspecialchars($volunteer['email']) ?>">
                        <?= htmlspecialchars($volunteer['name']) ?> (<?= htmlspecialchars($volunteer['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Subscribers -->
        <div id="subscriberDropdown" class="hidden">
            <label>Select Subscriber Email:</label>
            <select name="subscriber_email">
                <option value="">-- Choose Subscriber --</option>
                <?php foreach ($subscribers as $subscriber): ?>
                    <option value="<?= htmlspecialchars($subscriber['email']) ?>">
                        <?= htmlspecialchars($subscriber['name']) ?> (<?= htmlspecialchars($subscriber['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Adopters -->
        <div id="adopterDropdown" class="hidden">
            <label>Select Adopter Email:</label>
            <select name="adopter_email">
                <option value="">-- Choose Adopter --</option>
                <?php foreach ($adopters as $adopter): ?>
                    <option value="<?= htmlspecialchars($adopter['email']) ?>">
                        <?= htmlspecialchars($adopter['name']) ?> (<?= htmlspecialchars($adopter['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Manual email input -->
        <div id="manualEmail" class="hidden">
            <label>Enter Email:</label>
            <input type="email" name="to" placeholder="Enter recipient email">
        </div>

        <label>Subject:</label>
        <input type="text" name="subject" required placeholder="Enter email subject">

        <label>Message:</label>
        <textarea name="body" rows="6" required placeholder="Enter your message"></textarea>

        <button type="submit" name="send" class="btn">Send Email</button>
        <a href=" admin dashboard.php" class="btn back-btn">Back to Dashboard</a>
    </form>
</div>
</div>
</body>
</html>