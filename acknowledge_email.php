<?php
require_once __DIR__ . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$server = DB_HOST;
$user   = DB_USER;
$pass   = DB_PASS;
$db     = DB_NAME;

$conn = new mysqli($server, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8");

// Validate input
if(!isset($_GET['donor_id']) || !isset($_GET['date'])) die("Invalid request.");
$donor_id = $_GET['donor_id'];
$date = $_GET['date'];

// Fetch donor & donation info
$sql = "SELECT d.dtype, d.dlocation, d.damount, d.donor_id, d.ddate, d.seen,
               r.F_name, r.L_name, r.E_address, r.Phone_num
        FROM donations d
        JOIN donor r ON d.donor_id = r.donor_id
        WHERE d.donor_id = ? AND d.ddate = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $donor_id, $date);
$stmt->execute();
$result = $stmt->get_result();
if(!$result || $result->num_rows == 0) die("Donation not found.");

$row = $result->fetch_assoc();
$to = $row['E_address'];

// --- Send acknowledgment email using PHPMailer ---
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS; // App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom(SMTP_USER, 'Animal Guardian Admin');
    $mail->addAddress($to, $row['F_name'].' '.$row['L_name']);
    $mail->isHTML(true);
    $mail->Subject = "Acknowledgment of Your Donation #{$row['donor_id']}-".date('Ymd');
    $mail->Body    = "
        <p>Dear {$row['F_name']} {$row['L_name']},</p>
        <p>We sincerely appreciate your generous donation of <strong>R{$row['damount']}</strong> towards <strong>{$row['dtype']}</strong> at <strong>{$row['dlocation']}</strong> on <strong>".date('d M Y', strtotime($date))."</strong>.</p>
        <p>Your support helps us continue our mission to protect and care for animals in need.</p>
        <p>Thank you for being part of our community!</p>
        <p>— Animal Guardian Team</p>
    ";

    $mail->send();

    // Mark donation as acknowledged
    $seen = $conn->prepare("UPDATE donations SET seen = 1 WHERE donor_id = ? AND ddate = ?");
    $seen->bind_param("ss", $donor_id, $date);
    $seen->execute();

    echo "<script>alert('Acknowledgment email sent successfully.'); window.location.href='viewDonations.php';</script>";

} catch(Exception $e) {
    echo "<script>alert('Failed to send acknowledgment email: {$mail->ErrorInfo}'); window.location.href='viewDonations.php';</script>";
}
?>


