
<?php
include 'databaseConnection.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Debugging

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $damount = floatval($_POST['amount'] ?? 0);
    $dname = trim($_POST['fname'] ?? '');
    $dsurname = trim($_POST['lname'] ?? '');
    $donorphone = null; // no longer asked on the short form
    $donationtype = $_POST['type'] ?? 'one-time';
    $donoremail = trim($_POST['email'] ?? '');
    $dlocation = 'Online';
    $date = date('Y-m-d'); // donations are recorded on the day they're made

    // Insert donor
    $sql1 = "INSERT INTO donor(F_name, L_name, E_address, Phone_num) VALUES (?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ssss", $dname, $dsurname, $donoremail, $donorphone);

    if (!$stmt1->execute()) {
        die("Unable to add donor information!. Please try again.");
    }

    $donid = $conn->insert_id;

    // Insert donation
    $sql2 = "INSERT INTO donations(donor_id, damount, dlocation, dtype, ddate) VALUES (?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("idsss", $donid, $damount, $dlocation, $donationtype, $date);

    if (!$stmt2->execute()) {
        die("Unable to add donation information!. Please try again.");
    }

    // Redirect with donor name + amount
    $fullname = $dname . " " . $dsurname;
    header("Location: pay.html?amount=" . urlencode($damount) . "&name=" . urlencode($fullname));
    exit();
}
?>

