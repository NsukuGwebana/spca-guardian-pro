<?php
require_once __DIR__ . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

$messageSent = false;
$errorMsg = '';

if (isset($_POST['send'])) {
    $to = $_POST['to'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';

    if ($to && $subject && $body) {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USER; // Your Gmail
            $mail->Password   = SMTP_PASS; // Gmail password or App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom(SMTP_USER, 'Animal Guardian Admin');
            $mail->addAddress($to);

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
        $errorMsg = "Please fill in all fields.";
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
        margin-bottom: 6px;
        font-weight: bold;
        color: #134f5c;
    }
    form input, form textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #134f5c;
        border-radius: 8px;
        margin-bottom: 16px;
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
    .btn:hover {
        background: #0f3f4a;
    }
    .back-btn {
        background: #60a5b3;
    }
    .back-btn:hover {
        background: #3d8a95;
    }
    .success { color: green; margin-bottom: 15px; }
    .error { color: red; margin-bottom: 15px; }
</style>
</head>
<body>
<div class="overlay">
<div class="container">
    <h2>Send Email</h2>

    <?php if ($messageSent): ?>
        <p class="success">Email sent successfully!</p>
    <?php elseif ($errorMsg): ?>
        <p class="error"><?= htmlspecialchars($errorMsg) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Recipient Email:</label>
        <input type="email" name="to" required placeholder="Enter recipient email">

        <label>Subject:</label>
        <input type="text" name="subject" required placeholder="Enter email subject">

        <label>Message:</label>
        <textarea name="body" rows="6" required placeholder="Enter your message"></textarea>

        <button type="submit" name="send" class="btn">Send Email</button>
        <a href="admin dashboard.php" class="btn back-btn">Back to Dashboard</a>
    </form>
</div>
</div>
</body>
</html>
