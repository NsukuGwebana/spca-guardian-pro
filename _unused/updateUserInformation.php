<?php
require_once __DIR__ . '/Session_handler.php'; // staff only: redirects to login if not signed in
include 'databaseConnection.php';

$idnum = $_GET['idnumber'];

$sql_select = "SELECT * FROM user where idnumber = '$idnum'";
$result = $conn->query($sql_select);
$row2 = $result->fetch_assoc();

echo "User idnumber = ". $idnum . "extracted from the URL using GET<br>";

echo "User idnumber = ". $row2['idnumber'] . " extracted from the database using query";


if (isset($_POST['update'])) {
    $idnum = $_GET['idnumber'];
    $surname = $_POST['surname'];
    $firstname = $_POST['firstname'];
    $username = $_POST ['username'];
    $pword = $_POST ['userpassword'];
    $userrole = $_POST['userrole'];

    $sql_update = "UPDATE user SET surname = '$surname',
    firstname = '$firstname',
    username = '$username',
    userpassword = '$pword',
    userrole = '$userrole' WHERE idnumber = '$idnum'";

    $query_execute = $conn->query($sql_update);

    if($query_execute){
        echo "User information successfully updated";
        //header("Location: RegistrationForm.html ");
        $conn->close();
    } else {
        echo "Could not update user information";
    }
}
?>

