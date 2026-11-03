<?php
// "Back to Dashboard" goes here, so every user returns to their own dashboard.
require_once __DIR__ . '/Session_handler.php'; // not signed in -> login page
switch ($_SESSION['userrole'] ?? '') {
    case 'Administrator': header("Location: adminDashboard.php"); break;
    case 'Vet':           header("Location: vetDashboard.php");    break;
    case 'Volunteer':     header("Location: volunteerDashboard.php");               break;
    default:              header("Location: Newlogin.html");
}
exit;
