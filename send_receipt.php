<?php
require_once __DIR__ . '/config.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'tcpdf/tcpdf.php'; // Correct lowercase path

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

// Fetch donation & donor info
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

// --- Generate PDF using TCPDF ---
try {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Animal Guardian Admin');
    $pdf->SetAuthor('Animal Guardian');
    $pdf->SetTitle('Donation Receipt');
    $pdf->SetMargins(15, 20, 15);
    $pdf->AddPage();

    $html = "
    <h2 style='color:#134f5c;'>Animal Guardian Pro</h2>
    <p>Old Bay Road, Makhanda 6139, South Africa</p>
    <p>Tel: +27 76 856 0086 | Email: spcaguardianpro@gmail.com</p>
    <hr>
    <h3>Donation Receipt</h3>
    <p><strong>Receipt No:</strong> RCPT-{$row['donor_id']}-".date('Ymd')."</p>
    <p><strong>Date:</strong> ".date('Y-m-d')."</p>
    <p><strong>Donor:</strong> {$row['F_name']} {$row['L_name']}</p>
    <p><strong>Email:</strong> {$row['E_address']}</p>
    <p><strong>Phone:</strong> {$row['Phone_num']}</p>
    <p><strong>Donation Amount:</strong> R{$row['damount']}</p>
    <p><strong>Donation Type:</strong> {$row['dtype']}</p>
    <p><strong>Location:</strong> {$row['dlocation']}</p>
    <hr>
    <p>Thank you for supporting our animal welfare initiatives. This receipt may be tax deductible.</p>
    ";

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf_file = __DIR__.'/receipt_'.$row['donor_id'].'_'.date('Ymd').'.pdf';
    $pdf->Output($pdf_file, 'F');
} catch(Exception $e) {
    die("PDF generation failed: ".$e->getMessage());
}

// --- Send email using PHPMailer ---
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
    $mail->Subject = "Donation Receipt #RCPT-{$row['donor_id']}-".date('Ymd');
    $mail->Body    = "
        <p>Dear {$row['F_name']} {$row['L_name']},</p>
        <p>Thank you for your generous donation of <strong>R{$row['damount']}</strong>.</p>
        <p>Please find your donation receipt attached as a PDF.</p>
        <p>Sincerely,<br>Animal Guardian Team</p>
    ";

    $mail->addAttachment($pdf_file);

    $mail->send();

    // Mark as seen in database
    $seen = $conn->prepare("UPDATE donations SET seen = 1 WHERE donor_id = ? AND ddate = ?");
    $seen->bind_param("ss", $donor_id, $date);
    $seen->execute();

    // Delete PDF after sending
    if(file_exists($pdf_file)) unlink($pdf_file);

    echo "<script>alert('Receipt sent successfully.'); window.location.href='receipt.php?donor_id=$donor_id&date=$date';</script>";

} catch(Exception $e) {
    echo "<script>alert('Failed to send email: {$mail->ErrorInfo}'); window.location.href='receipt.php?donor_id=$donor_id&date=$date';</script>";
}



