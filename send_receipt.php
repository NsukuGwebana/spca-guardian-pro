<?php
$ALLOWED_ROLES = ['Administrator']; // who may open this page
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
require_once __DIR__ . '/bootstrap.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'tcpdf/tcpdf.php'; // Correct lowercase path



if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: viewDonations.php'); exit; }
$donor_id = (int)($_POST['donor_id'] ?? 0);
$date     = (string)($_POST['date'] ?? '');
$back     = ($_POST['back'] ?? '') === 'receipt' ? 'receipt.php?' . http_build_query(['donor_id' => $donor_id, 'date' => $date]) : 'viewDonations.php';

$result = q("SELECT d.dtype, d.dlocation, d.damount, d.donor_id, d.ddate, d.seen,
                    r.F_name, r.L_name, r.E_address, r.Phone_num
             FROM donations d JOIN donor r ON d.donor_id = r.donor_id
             WHERE d.donor_id = ? AND d.ddate = ? LIMIT 1", [$donor_id, $date]);
if ($result->num_rows == 0) show_error_page(404, 'Donation not found', "We couldn't find that donation. It may have been removed.");

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
    <p><strong>Receipt No:</strong> RCPT-" . e($row['donor_id']) . "-".date('Ymd')."</p>
    <p><strong>Date:</strong> ".date('Y-m-d')."</p>
    <p><strong>Donor:</strong> " . e($row['F_name']) . " " . e($row['L_name']) . "</p>
    <p><strong>Email:</strong> " . e($row['E_address']) . "</p>
    <p><strong>Phone:</strong> " . e($row['Phone_num']) . "</p>
    <p><strong>Donation Amount:</strong> R" . e($row['damount']) . "</p>
    <p><strong>Donation Type:</strong> " . e($row['dtype']) . "</p>
    <p><strong>Location:</strong> " . e($row['dlocation']) . "</p>
    <hr>
    <p>Thank you for supporting our animal welfare initiatives. This receipt may be tax deductible.</p>
    ";

    $pdf->writeHTML($html, true, false, true, false, '');
    $pdf_file = sys_get_temp_dir() . '/receipt_'.$row['donor_id'].'_'.date('Ymd').'.pdf';
    $pdf->Output($pdf_file, 'F');
} catch (Throwable $e) {
    log_error('Receipt PDF failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['bad', "The receipt PDF couldn't be created. Please try again."];
    header("Location: $back");
    exit;
}

// --- Send email using PHPMailer ---
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USER;
    $mail->Password   = MAIL_PASS; // App password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = MAIL_PORT;

    $mail->setFrom(MAIL_FROM, 'Animal Guardian Admin');
    $mail->addAddress($to, $row['F_name'].' '.$row['L_name']);
    $mail->isHTML(true);
    $mail->Subject = "Donation Receipt #RCPT-" . (int)$row['donor_id'] . "-" . date('Ymd');
    $mail->Timeout = 15;
    $mail->Body    = "
        <p>Dear " . e($row['F_name']) . " " . e($row['L_name']) . ",</p>
        <p>Thank you for your generous donation of <strong>R" . e($row['damount']) . "</strong>.</p>
        <p>Please find your donation receipt attached as a PDF.</p>
        <p>Sincerely,<br>Animal Guardian Team</p>
    ";

    $mail->addAttachment($pdf_file);

    $mail->send();

    // Mark as seen in database
    q("UPDATE donations SET seen=1 WHERE donor_id = ? AND ddate = ?", [(int)$donor_id, (string)$date]);
    log_activity('Sent receipt', 'Donor #' . (int)$donor_id . ' on ' . $date);

    // Delete PDF after sending
    if(file_exists($pdf_file)) unlink($pdf_file);

    $_SESSION['flash'] = ['ok', "Receipt emailed to {$row['F_name']} {$row['L_name']}."];
} catch (Throwable $e) {
    log_error('Receipt email failed: ' . $mail->ErrorInfo);
    if (isset($pdf_file) && file_exists($pdf_file)) unlink($pdf_file);
    $_SESSION['flash'] = ['bad', "The receipt couldn't be emailed to {$row['F_name']} {$row['L_name']}. Check their email address and try again."];
}
header("Location: $back");
exit;



