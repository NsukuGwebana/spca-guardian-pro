<?php
// Personal thank-you email for a large donation. POST only (from the donations list).
$ALLOWED_ROLES = ['Administrator'];
require_once __DIR__ . '/Session_handler.php';
require_once __DIR__ . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: viewDonations.php'); exit; }

$donor_id = (int)($_POST['donor_id'] ?? 0);
$date     = (string)($_POST['date'] ?? '');

$row = q("SELECT d.dtype, d.dlocation, d.damount, d.ddate, r.F_name, r.L_name, r.E_address
          FROM donations d JOIN donor r ON d.donor_id = r.donor_id
          WHERE d.donor_id = ? AND d.ddate = ? LIMIT 1", [$donor_id, $date])->fetch_assoc();
if (!$row) show_error_page(404, 'Donation not found', "We couldn't find that donation. It may have been removed.");

$html = '<p>Dear ' . e($row['F_name'] . ' ' . $row['L_name']) . ',</p>'
      . '<p>We sincerely appreciate your generous donation of <strong>R ' . e(number_format((float)$row['damount'], 2)) . '</strong>'
      . ($row['dtype'] ? ' towards <strong>' . e($row['dtype']) . '</strong>' : '')
      . ' on <strong>' . e(date('j F Y', strtotime($row['ddate']))) . '</strong>.</p>'
      . '<p>Your support helps us protect and care for animals in need. Thank you for being part of our community.</p>'
      . '<p>Kind regards,<br>SPCA Guardian Pro team</p>';

if (send_mail((string)$row['E_address'], 'Thank you for your donation', $html)) {
    q("UPDATE donations SET seen = 1 WHERE donor_id = ? AND ddate = ?", [$donor_id, $date]);
    log_activity('Sent thank-you email', "Donor #$donor_id on $date");
    $_SESSION['flash'] = ['ok', "Thank-you email sent to {$row['F_name']} {$row['L_name']}."];
} else {
    $_SESSION['flash'] = ['bad', "The email to {$row['F_name']} {$row['L_name']} could not be sent. Check their email address and try again."];
}
header('Location: viewDonations.php');
exit;
