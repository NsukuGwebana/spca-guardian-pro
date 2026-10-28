
<?php
include 'databaseConnection.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // Debugging

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $damount = floatval($_POST['amount']);
    $dname = $_POST['fname'];
    $dsurname = $_POST['lname'];
    $donorphone = !empty($_POST['phone']) ? $_POST['phone'] : null;
    $donationtype = $_POST['type'];
    $donoremail = $_POST['email'];
    $dlocation = $_POST['location'];
    $date = $_POST['date'];

    // Insert donor
    $sql1 = "INSERT INTO donor(F_name, L_name, E_address, Phone_num) VALUES (?, ?, ?, ?)";
    $stmt1 = $conn->prepare($sql1);
    $stmt1->bind_param("ssss", $dname, $dsurname, $donoremail, $donorphone);

    if (!$stmt1->execute()) {
        die("Unable to add donor information! " . $stmt1->error);
    }

    $donid = $conn->insert_id;

    // Insert donation
    $sql2 = "INSERT INTO donations(donor_id, damount, dlocation, dtype, ddate) VALUES (?, ?, ?, ?, ?)";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("idsss", $donid, $damount, $dlocation, $donationtype, $date);

    if (!$stmt2->execute()) {
        die("Unable to add donation information! " . $stmt2->error);
    }

    // Redirect with donor name + amount
    $fullname = $dname . " " . $dsurname;
    header("Location: pay.html?amount=" . urlencode($damount) . "&name=" . urlencode($fullname));
    exit();
}
?>

